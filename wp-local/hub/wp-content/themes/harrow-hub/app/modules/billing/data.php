<?php
/**
 * Billing: the only place that talks to Stripe (stripe-php, in the theme's vendor/) or stores billing data.
 *
 * Settings: STRIPE_SECRET_KEY, STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET, declared in wp-config.php from the server
 * environment or the repository's .env file (never committed). Stored: the user's Stripe customer ID (user meta "hub_stripe_customer"),
 * the business's subscription (post meta "_hub_subscription") and a private log of payments ("hub_payment" posts).
 */
defined( 'ABSPATH' ) || exit;

/** A setting from the environment, else from the first .env found from WordPress's folder upwards. */
function hub_billing_db_env( $key ) {
	static $file = null;
	if ( defined( $key ) && '' !== (string) constant( $key ) ) {
		return (string) constant( $key );   // wp-config.php declares the Stripe keys (values from the environment or .env)
	}
	$v = getenv( $key );
	if ( false !== $v && '' !== $v ) {
		return $v;
	}
	if ( null === $file ) {
		$file = array();
		$dir  = rtrim( ABSPATH, '/\\' );
		for ( $i = 0; $i < 4; $i++, $dir = dirname( $dir ) ) {
			if ( is_readable( $dir . '/.env' ) ) {
				foreach ( file( $dir . '/.env', FILE_IGNORE_NEW_LINES ) as $line ) {
					if ( preg_match( '/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/', $line, $m ) && '#' !== substr( ltrim( $line ), 0, 1 ) ) {
						$file[ $m[1] ] = trim( $m[2], "\"'" );
					}
				}
				break;
			}
		}
	}
	return isset( $file[ $key ] ) ? $file[ $key ] : '';
}

/** The Stripe client (null when the keys aren't set up). */
function hub_billing_db_stripe() {
	static $client = false;
	if ( false === $client ) {
		$client = null;
		$secret = hub_billing_db_env( 'STRIPE_SECRET_KEY' );
		if ( hub_billing_rule_configured( $secret, hub_billing_db_env( 'STRIPE_PUBLISHABLE_KEY' ) ) ) {
			require_once get_template_directory() . '/vendor/autoload.php';
			$client = new \Stripe\StripeClient( $secret );   // the API version stripe-php was built for
		}
	}
	return $client;
}

/* ------------------------------------------------------------------ customers and subscriptions (WordPress side) */

function hub_billing_db_customer_id( $user_id ) {
	return (string) get_user_meta( (int) $user_id, 'hub_stripe_customer', true );
}

function hub_billing_db_save_customer_id( $user_id, $customer_id ) {
	update_user_meta( (int) $user_id, 'hub_stripe_customer', $customer_id );
}

/** The user with this Stripe customer ID, or 0. */
function hub_billing_db_user_by_customer( $customer_id ) {
	$users = get_users( array( 'meta_key' => 'hub_stripe_customer', 'meta_value' => $customer_id, 'number' => 1, 'fields' => 'ID' ) );
	return $users ? (int) $users[0] : 0;
}

/** The business's subscription as last told by Stripe: { id, status, plan_key, interval, period_end, cancel_at_period_end, cancel_at }. */
function hub_billing_db_subscription( $business_id ) {
	$s = get_post_meta( (int) $business_id, '_hub_subscription', true );
	return is_array( $s ) ? $s : array();
}

function hub_billing_db_save_subscription( $business_id, array $s ) {
	update_post_meta( (int) $business_id, '_hub_subscription', $s );
}

/** The business whose subscription has this Stripe ID, or 0. */
function hub_billing_db_business_by_subscription( $subscription_id ) {
	$ids = get_posts( array(
		'post_type'      => 'hub_business',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_hub_subscription', 'value' => '"' . $subscription_id . '"', 'compare' => 'LIKE' ) ),
	) );
	return $ids ? (int) $ids[0] : 0;
}

/* ------------------------------------------------------------------ payment log (private posts, wp-admin only) */

/** Record a payment or billing event once (by its Stripe ID): { stripe_id, kind, item, amount, status, user, business, ref }. */
function hub_billing_db_log( array $p ) {
	$existing = get_posts( array( 'post_type' => 'hub_payment', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_hub_stripe_id', 'meta_value' => $p['stripe_id'] ) );
	$title    = $p['kind'] . ': ' . $p['item'] . ( $p['amount'] ? ' ' . hub_billing_rule_money( (int) $p['amount'] ) : '' ) . ' (' . $p['status'] . ')';
	$args     = array( 'post_type' => 'hub_payment', 'post_status' => 'private', 'post_title' => $title, 'meta_input' => array(
		'_hub_stripe_id' => $p['stripe_id'],
		'_hub_payment'   => $p,
		'_hub_user'      => (int) $p['user'],
		'_hub_business'  => (int) $p['business'],
	) );
	if ( $existing ) {
		$args['ID'] = $existing[0];
		return wp_update_post( $args );
	}
	return wp_insert_post( $args );
}

/** The logged payment with this Stripe ID (an invoice in_..., a Checkout session cs_..., a refund's charge ch_...), or null. */
function hub_billing_db_logged( $stripe_id ) {
	$ids = get_posts( array( 'post_type' => 'hub_payment', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_hub_stripe_id', 'meta_value' => (string) $stripe_id ) );
	return $ids ? hub_billing_db_payment( $ids[0] ) : null;
}

/**
 * What a payment (PaymentIntent) paid for, as the Stripe ID the payment log uses: the invoice (plans) or the Checkout
 * session (one-off items). '' when Stripe doesn't say.
 */
function hub_billing_db_payment_source( $payment_intent ) {
	$stripe = hub_billing_db_stripe();
	try {
		$paid = $stripe->invoicePayments->all( array( 'payment' => array( 'type' => 'payment_intent', 'payment_intent' => $payment_intent ), 'limit' => 1 ) )->data;
		if ( $paid ) {
			return (string) $paid[0]->invoice;
		}
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		hub_debug_log( 'billing: invoice payments lookup: ' . $e->getMessage() );
	}
	try {
		$sessions = $stripe->checkout->sessions->all( array( 'payment_intent' => $payment_intent, 'limit' => 1 ) )->data;
		if ( $sessions ) {
			return (string) $sessions[0]->id;
		}
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		hub_debug_log( 'billing: checkout session lookup: ' . $e->getMessage() );
	}
	return '';
}

/** One logged payment: { stripe_id, kind, item, amount, status, user, business, ref }, or null. */
function hub_billing_db_payment( $post_id ) {
	$p = get_post_meta( (int) $post_id, '_hub_payment', true );
	return is_array( $p ) ? $p : null;
}

/** Prices staff saved in wp-admin > Hub settings > Prices: { plans: { key: { monthly, annual } }, items: { key: amount } } (pence). */
function hub_billing_db_saved_prices() {
	$p = get_option( 'hub_billing_prices', array() );
	return is_array( $p ) ? $p : array();
}

function hub_billing_db_save_prices( array $prices ) {
	update_option( 'hub_billing_prices', $prices, false );
}

/** True when this webhook event was already handled (Stripe can send an event more than once). */
function hub_billing_db_seen_event( $event_id ) {
	$key = 'hub_stripe_evt_' . md5( $event_id );
	if ( get_transient( $key ) ) {
		return true;
	}
	set_transient( $key, 1, 3 * DAY_IN_SECONDS );
	return false;
}

/** Send a plain-text billing email. False when WordPress couldn't hand it to the mail server. */
function hub_billing_db_send_email( $to, $subject, $body ) {
	return wp_mail( $to, $subject, $body, array( 'Reply-To: Harrow Online <hub@harrowonline.org>' ) );
}

/* ------------------------------------------------------------------ Stripe calls */

/** Prices by lookup key: { lookup_key: \Stripe\Price } */
function hub_billing_db_prices( array $lookup_keys ) {
	$out = array();
	foreach ( hub_billing_db_stripe()->prices->all( array( 'lookup_keys' => array_values( $lookup_keys ), 'active' => true, 'limit' => 100 ) )->data as $p ) {
		$out[ $p->lookup_key ] = $p;
	}
	return $out;
}

/** The user's Stripe customer, created on first use. */
function hub_billing_db_ensure_customer( WP_User $u, $business_name ) {
	$id = hub_billing_db_customer_id( $u->ID );
	if ( $id ) {
		return $id;
	}
	$c = hub_billing_db_stripe()->customers->create( array(
		'email'    => $u->user_email,
		'name'     => '' !== $business_name ? $business_name : $u->display_name,
		'metadata' => array( 'hub_user' => (string) $u->ID ),
	) );
	hub_billing_db_save_customer_id( $u->ID, $c->id );
	return $c->id;
}

/** @return \Stripe\Checkout\Session */
function hub_billing_db_checkout( array $args ) {
	$args['managed_payments'] = array( 'enabled' => HUB_BILLING_MANAGED_PAYMENTS );   // rules.php
	return hub_billing_db_stripe()->checkout->sessions->create( $args );
}

/**
 * Stripe's billing portal for a customer, with the Hub's own portal settings (hub_billing_db_sync_portal()) when they
 * have been set up; Stripe's default settings otherwise.
 *
 * @return \Stripe\BillingPortal\Session
 */
function hub_billing_db_portal( $customer_id, $return_url ) {
	$args   = array( 'customer' => $customer_id, 'return_url' => $return_url );
	$config = _hub_billing_db_portal_config_id();
	if ( '' !== $config ) {
		$args['configuration'] = $config;
	}
	return hub_billing_db_stripe()->billingPortal->sessions->create( $args );
}

/**
 * Create or update the Hub's portal settings in Stripe (idempotent): change between the plans and billing periods
 * listed, update the card, see invoices, cancel at the end of the period (with a reason). Its ID is kept per Stripe
 * mode (test / live) in the option "hub_stripe_portal_{mode}".
 *
 * @param array  $plans    product ID => price lookup keys customers can switch between
 * @param string $headline shown at the top of the portal
 * @return string what was done
 */
function hub_billing_db_sync_portal( array $plans, $headline ) {
	$stripe   = hub_billing_db_stripe();
	$products = array();
	foreach ( $plans as $product => $keys ) {
		$prices = $stripe->prices->all( array( 'lookup_keys' => array_values( $keys ), 'active' => true, 'limit' => 10 ) )->data;
		if ( $prices ) {
			$products[] = array( 'product' => $product, 'prices' => array_map( function ( $p ) { return $p->id; }, $prices ) );
		}
	}
	$args = array(
		'business_profile' => array( 'headline' => $headline ),
		'features'         => array(
			'customer_update'       => array( 'enabled' => true, 'allowed_updates' => array( 'email', 'address' ) ),
			'invoice_history'       => array( 'enabled' => true ),
			'payment_method_update' => array( 'enabled' => true ),
			'subscription_cancel'   => array(
				'enabled'             => true,
				'mode'                => 'at_period_end',
				'cancellation_reason' => array( 'enabled' => true, 'options' => array( 'too_expensive', 'missing_features', 'switched_service', 'unused', 'other' ) ),
			),
			'subscription_update'   => array(
				'enabled'                 => HUB_BILLING_PLAN_CHANGES && (bool) $products,   // rules.php launch switch
				'default_allowed_updates' => array( 'price' ),
				'proration_behavior'      => 'create_prorations',
				'products'                => $products,
			),
		),
		'metadata'         => array( 'hub_portal' => '1' ),
	);
	$id = _hub_billing_db_portal_config_id();
	if ( '' !== $id ) {
		try {
			$stripe->billingPortal->configurations->update( $id, $args + array( 'active' => true ) );
			return 'billing portal: updated (' . ( HUB_BILLING_PLAN_CHANGES ? count( $products ) . ' plans to switch between' : 'plan changes off' ) . ')';
		} catch ( \Stripe\Exception\InvalidRequestException $e ) {
			$id = '';   // gone, or from the other mode: make a new one
		}
	}
	$config = $stripe->billingPortal->configurations->create( $args );
	update_option( _hub_billing_db_portal_option(), $config->id, false );
	return 'billing portal: created (' . ( HUB_BILLING_PLAN_CHANGES ? count( $products ) . ' plans to switch between' : 'plan changes off' ) . ')';
}

/**
 * The Hub's portal settings ID: from the option, else found in Stripe by its "hub_portal" tag and kept again. The
 * dev server's database is replaced on each deploy, so the option can go missing while the settings are still in
 * Stripe; '' when there are none yet (run the Stripe setup).
 */
function _hub_billing_db_portal_config_id() {
	$id = (string) get_option( _hub_billing_db_portal_option(), '' );
	if ( '' !== $id ) {
		return $id;
	}
	try {
		foreach ( hub_billing_db_stripe()->billingPortal->configurations->all( array( 'active' => true, 'limit' => 50 ) )->data as $c ) {
			if ( isset( $c->metadata->hub_portal ) && '1' === (string) $c->metadata->hub_portal ) {
				update_option( _hub_billing_db_portal_option(), $c->id, false );
				return $c->id;
			}
		}
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		hub_debug_log( 'billing: portal settings lookup: ' . $e->getMessage() );
	}
	return '';
}

/** The option that keeps the portal settings' ID for the Stripe mode in use (test and live are separate). */
function _hub_billing_db_portal_option() {
	return 'hub_stripe_portal_' . hub_billing_rule_mode( hub_billing_db_env( 'STRIPE_SECRET_KEY' ) );
}

/**
 * The customer's card and recent invoices, straight from Stripe, kept for 10 minutes (cleared when a webhook arrives
 * for the customer): { card: { brand, last4, exp_month, exp_year } | null,
 *   invoices: [ { number, created, reason, lines[] (descriptions), total, status, refunded ('' | 'part' | 'full'), refunded_amount, pdf, url } ] }.
 * The card is the subscription's own when it has one, else the customer's default, else the latest card added.
 */
function hub_billing_db_details( $customer_id, $subscription_id = '' ) {
	$key    = 'hub_billing_' . md5( $customer_id );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$stripe = hub_billing_db_stripe();
	$pm     = null;
	if ( '' !== $subscription_id ) {
		$sub = $stripe->subscriptions->retrieve( $subscription_id, array( 'expand' => array( 'default_payment_method' ) ) );
		$pm  = is_object( $sub->default_payment_method ) ? $sub->default_payment_method : null;
	}
	if ( ! $pm ) {
		$cus = $stripe->customers->retrieve( $customer_id, array( 'expand' => array( 'invoice_settings.default_payment_method' ) ) );
		$pm  = isset( $cus->invoice_settings->default_payment_method ) && is_object( $cus->invoice_settings->default_payment_method ) ? $cus->invoice_settings->default_payment_method : null;
	}
	if ( ! $pm ) {
		$list = $stripe->paymentMethods->all( array( 'customer' => $customer_id, 'type' => 'card', 'limit' => 1 ) )->data;
		$pm   = $list ? $list[0] : null;
	}
	// Refunds are on the charges: payment => [ refunded in full, amount refunded ]
	$refunds = array();
	foreach ( $stripe->charges->all( array( 'customer' => $customer_id, 'limit' => 24 ) )->data as $ch ) {
		if ( (int) $ch->amount_refunded > 0 && $ch->payment_intent ) {
			$refunds[ is_string( $ch->payment_intent ) ? $ch->payment_intent : $ch->payment_intent->id ] = array( (bool) $ch->refunded, (int) $ch->amount_refunded );
		}
	}
	$invoices = array();
	foreach ( $stripe->invoices->all( array( 'customer' => $customer_id, 'limit' => 12, 'expand' => array( 'data.payments' ) ) )->data as $i ) {
		if ( 'draft' === $i->status ) {
			continue;
		}
		$refund = null;
		foreach ( isset( $i->payments->data ) ? $i->payments->data : array() as $pay ) {
			$pi = isset( $pay->payment->payment_intent ) ? $pay->payment->payment_intent : '';
			$pi = is_object( $pi ) ? $pi->id : (string) $pi;
			if ( isset( $refunds[ $pi ] ) ) {
				$refund = $refunds[ $pi ];
			}
		}
		$invoices[] = array(
			'number'   => (string) $i->number,
			'created'  => (int) $i->created,
			'reason'   => (string) $i->billing_reason,
			'lines'    => array_map( function ( $l ) { return array( (string) $l->description, (int) $l->amount ); }, $i->lines->data ),
			'total'    => (int) $i->total,
			'status'   => (string) $i->status,
			'refunded' => $refund ? ( $refund[0] ? 'full' : 'part' ) : ( isset( $i->post_payment_credit_notes_amount ) && (int) $i->post_payment_credit_notes_amount > 0 ? 'part' : '' ),
			'refunded_amount' => $refund ? $refund[1] : ( isset( $i->post_payment_credit_notes_amount ) ? (int) $i->post_payment_credit_notes_amount : 0 ),
			'pdf'      => (string) $i->invoice_pdf,
			'url'      => (string) $i->hosted_invoice_url,
		);
	}
	$out = array(
		'card'     => $pm && isset( $pm->card ) ? array( 'brand' => (string) $pm->card->brand, 'last4' => (string) $pm->card->last4, 'exp_month' => (int) $pm->card->exp_month, 'exp_year' => (int) $pm->card->exp_year ) : null,
		'invoices' => $invoices,
	);
	set_transient( $key, $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

/** Forget a customer's card and invoices, so the billing page asks Stripe again (after a webhook). */
function hub_billing_db_forget_details( $customer_id ) {
	delete_transient( 'hub_billing_' . md5( (string) $customer_id ) );
}

/** @return \Stripe\Subscription */
function hub_billing_db_get_subscription( $id ) {
	return hub_billing_db_stripe()->subscriptions->retrieve( $id, array( 'expand' => array( 'items.data.price' ) ) );
}

/** A verified webhook event (throws on a bad signature). */
function hub_billing_db_webhook_event( $payload, $signature ) {
	require_once get_template_directory() . '/vendor/autoload.php';
	return \Stripe\Webhook::constructEvent( $payload, $signature, hub_billing_db_env( 'STRIPE_WEBHOOK_SECRET' ) );
}

/**
 * Create or update the Hub's products and prices in Stripe (idempotent). Products have fixed IDs (hub_featured,
 * hub_event_listing...); a changed amount makes a new price that takes over the lookup key, and the old one is archived.
 *
 * @param array $catalogue [ { product, name, description, amount, interval ('month'|'year'|null), lookup_key } ]
 * @return string[] what was done, one line per price
 */
function hub_billing_db_sync_catalogue( array $catalogue ) {
	$stripe = hub_billing_db_stripe();
	$done   = array();
	foreach ( $catalogue as $row ) {
		$pid = 'hub_' . $row['product'];
		try {
			$stripe->products->update( $pid, array( 'name' => $row['name'], 'description' => $row['description'], 'active' => true ) );
		} catch ( \Stripe\Exception\InvalidRequestException $e ) {
			$stripe->products->create( array( 'id' => $pid, 'name' => $row['name'], 'description' => $row['description'] ) );
		}
		$existing = $stripe->prices->all( array( 'lookup_keys' => array( $row['lookup_key'] ), 'limit' => 1 ) )->data;
		$cur      = $existing ? $existing[0] : null;
		$same     = $cur && (int) $cur->unit_amount === (int) $row['amount'] && $cur->active
			&& ( $row['interval'] ? ( $cur->recurring && $cur->recurring->interval === $row['interval'] ) : ! $cur->recurring );
		if ( $same ) {
			$done[] = $row['lookup_key'] . ': unchanged (' . hub_billing_rule_money( (int) $row['amount'] ) . ')';
			continue;
		}
		$args = array(
			'product'             => $pid,
			'currency'            => HUB_BILLING_CURRENCY,
			'unit_amount'         => (int) $row['amount'],
			'tax_behavior'        => 'inclusive',
			'lookup_key'          => $row['lookup_key'],
			'transfer_lookup_key' => true,
		);
		if ( $row['interval'] ) {
			$args['recurring'] = array( 'interval' => $row['interval'] );
		}
		$stripe->prices->create( $args );
		if ( $cur ) {
			$stripe->prices->update( $cur->id, array( 'active' => false ) );
		}
		$done[] = $row['lookup_key'] . ': ' . ( $cur ? 'new price ' : 'created ' ) . hub_billing_rule_money( (int) $row['amount'] ) . ( $row['interval'] ? '/' . $row['interval'] : '' );
	}
	return $done;
}
