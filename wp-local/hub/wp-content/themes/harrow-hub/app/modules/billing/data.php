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

/** The business's subscription as last told by Stripe: { id, status, plan_key, interval, period_end, cancel_at_period_end }. */
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

/** @return \Stripe\BillingPortal\Session */
function hub_billing_db_portal( $customer_id, $return_url ) {
	return hub_billing_db_stripe()->billingPortal->sessions->create( array( 'customer' => $customer_id, 'return_url' => $return_url ) );
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
