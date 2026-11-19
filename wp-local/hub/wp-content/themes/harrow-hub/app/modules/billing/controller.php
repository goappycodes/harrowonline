<?php
/**
 * Billing: what pages and the API call. Nothing outside this module calls anything else in it.
 *
 *   hub_billing_status()                     the signed-in business's plan and subscription
 *   hub_billing_catalogue()                  plans and one-off items with their prices (pricing page, app)
 *   hub_billing_checkout_plan( $params )     Stripe Checkout for Featured or Local Partner, monthly or annual
 *   hub_billing_checkout_item( $params )     Stripe Checkout for a one-off item (event listing, feature, sponsor slot)
 *   hub_billing_portal()                     Stripe's billing portal: card, invoices, change plan, cancel
 *   hub_billing_webhook( $payload, $sig )    Stripe's events: switch plans on and off, record payments
 *   hub_billing_setup()                      admin/CLI: create the products and prices in Stripe
 *
 * Checkout and portal actions return { ok, message, redirect (Stripe's page, or back to the Hub), unavailable }.
 * Without Stripe keys (placeholders in .env) they return unavailable = true and never call Stripe.
 * Other modules act on one-off payments through the action "hub_billing_item_paid" ( $item, $ref, $user_id, $business_id ).
 */
defined( 'ABSPATH' ) || exit;

/** The signed-in business's plan and subscription. */
function hub_billing_status( $mode = HUB_WEB ) {
	$account = hub_account_current();
	if ( ! $account['signed_in'] ) {
		return hub_respond_error( 'signed_out', 'Sign in to see your plan.', $mode, 401 );
	}
	$biz = $account['business'] ? (int) $account['business']['id'] : 0;
	return hub_respond( hub_billing_shape(
		_hub_billing_configured(),
		hub_billing_rule_mode( hub_billing_db_env( 'STRIPE_SECRET_KEY' ) ),
		$account['plan'],
		$biz ? hub_billing_db_subscription( $biz ) : array(),
		'' !== hub_billing_db_customer_id( $account['id'] )
	), $mode );
}

/** Plans and one-off items with their prices. */
function hub_billing_catalogue( $mode = HUB_WEB ) {
	$plans = $items = array();
	foreach ( HUB_BILLING_PLANS as $key => $p ) {
		$plans[] = array(
			'key'           => $key,
			'name'          => $p['name'],
			'monthly'       => hub_billing_rule_money( $p['prices']['monthly'] ),
			'annual'        => hub_billing_rule_money( $p['prices']['annual'] ),
			'annual_saving' => hub_billing_rule_money( 12 * $p['prices']['monthly'] - $p['prices']['annual'] ),
		);
	}
	foreach ( HUB_BILLING_ITEMS as $key => $i ) {
		$items[] = array( 'key' => $key, 'name' => $i['name'], 'price' => hub_billing_rule_money( $i['amount'] ), 'what' => html_entity_decode( $i['what'], ENT_QUOTES, 'UTF-8' ) );
	}
	return hub_respond( array( 'plans' => $plans, 'items' => $items, 'vat' => 'Prices include VAT' ), $mode );
}

/** @param array $params { plan ('featured' | 'partner'), billing ('monthly' | 'annual') } */
function hub_billing_checkout_plan( array $params, $mode = HUB_WEB ) {
	$plan     = hub_param_slug( $params, 'plan' );
	$interval = hub_param_slug( $params, 'billing' );
	if ( ! isset( HUB_BILLING_PLANS[ $plan ] ) || ! isset( HUB_BILLING_INTERVALS[ $interval ] ) ) {
		return _hub_billing_result( false, 'Choose Featured or Local Partner, monthly or annual.', hub_url( 'pricing/' ), $mode );
	}
	$account = hub_account_current();
	if ( $err = _hub_billing_need_business( $account, $mode ) ) {
		return $err;
	}
	if ( ! _hub_billing_configured() ) {
		return _hub_billing_unavailable( $mode );
	}
	$biz = (int) $account['business']['id'];
	$sub = hub_billing_db_subscription( $biz );
	if ( ! empty( $sub['id'] ) && in_array( isset( $sub['status'] ) ? $sub['status'] : '', array( 'active', 'trialing', 'past_due' ), true ) ) {
		// Already subscribed: changing plan or billing period happens in Stripe's portal (it works out the difference)
		return hub_billing_portal( $mode );
	}
	try {
		$key    = hub_billing_rule_lookup_key( $plan, $interval );
		$prices = hub_billing_db_prices( array( $key ) );
		if ( empty( $prices[ $key ] ) ) {
			hub_debug_log( 'billing: no Stripe price ' . $key . ' (run the Stripe setup)' );
			return _hub_billing_result( false, 'This plan isn&rsquo;t available to buy yet. Please try again later.', hub_url( 'account/billing/' ), $mode );
		}
		$meta    = array( 'hub_user' => (string) $account['id'], 'hub_business' => (string) $biz, 'hub_plan' => $plan, 'hub_interval' => $interval );
		$session = hub_billing_db_checkout( array(
			'mode'                  => 'subscription',
			'customer'              => hub_billing_db_ensure_customer( get_userdata( $account['id'] ), $account['business']['name'] ),
			'line_items'            => array( array( 'price' => $prices[ $key ]->id, 'quantity' => 1 ) ),
			'allow_promotion_codes' => true,
			'client_reference_id'   => (string) $biz,
			'metadata'              => $meta,
			'subscription_data'     => array( 'metadata' => $meta ),
			'success_url'           => hub_url( 'account/billing/?checkout=success' ),
			'cancel_url'            => hub_url( 'account/billing/?checkout=cancelled' ),
		) );
		return _hub_billing_result( true, '', $session->url, $mode );
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		return _hub_billing_stripe_error( $e, $mode );
	}
}

/**
 * @param array $params { item (a HUB_BILLING_ITEMS key), ref (what it's for: an event, offer or sponsor slot), return (a Hub URL) }
 */
function hub_billing_checkout_item( array $params, $mode = HUB_WEB ) {
	$item = isset( $params['item'] ) ? sanitize_key( (string) $params['item'] ) : '';
	if ( ! isset( HUB_BILLING_ITEMS[ $item ] ) ) {
		return _hub_billing_result( false, 'Unknown item.', hub_url( 'account/' ), $mode );
	}
	$account = hub_account_current();
	if ( ! $account['signed_in'] ) {
		return _hub_billing_result( false, 'Sign in to pay.', hub_url( 'sign-in/' ), $mode );
	}
	if ( ! _hub_billing_configured() ) {
		return _hub_billing_unavailable( $mode );
	}
	$ref    = isset( $params['ref'] ) ? sanitize_text_field( wp_unslash( (string) $params['ref'] ) ) : '';
	$return = _hub_billing_safe_return( isset( $params['return'] ) ? esc_url_raw( wp_unslash( (string) $params['return'] ) ) : '' );
	$biz    = $account['business'] ? (int) $account['business']['id'] : 0;
	try {
		$key    = hub_billing_rule_lookup_key( $item );
		$prices = hub_billing_db_prices( array( $key ) );
		if ( empty( $prices[ $key ] ) ) {
			hub_debug_log( 'billing: no Stripe price ' . $key . ' (run the Stripe setup)' );
			return _hub_billing_result( false, 'This isn&rsquo;t available to buy yet. Please try again later.', $return, $mode );
		}
		$meta    = array( 'hub_user' => (string) $account['id'], 'hub_business' => (string) $biz, 'hub_item' => $item, 'hub_ref' => $ref );
		$session = hub_billing_db_checkout( array(
			'mode'                => 'payment',
			'customer'            => hub_billing_db_ensure_customer( get_userdata( $account['id'] ), $account['business'] ? $account['business']['name'] : '' ),
			'line_items'          => array( array( 'price' => $prices[ $key ]->id, 'quantity' => 1 ) ),
			'invoice_creation'    => array( 'enabled' => true ),   // a proper invoice for the business's records
			'metadata'            => $meta,
			'payment_intent_data' => array( 'metadata' => $meta ),
			'success_url'         => add_query_arg( 'paid', $item, $return ),
			'cancel_url'          => $return,
		) );
		return _hub_billing_result( true, '', $session->url, $mode );
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		return _hub_billing_stripe_error( $e, $mode );
	}
}

/** Stripe's billing portal for the signed-in user; Pricing when they've never paid. */
function hub_billing_portal( $mode = HUB_WEB ) {
	$account = hub_account_current();
	if ( ! $account['signed_in'] ) {
		return _hub_billing_result( false, 'Sign in to manage billing.', hub_url( 'sign-in/' ), $mode );
	}
	if ( ! _hub_billing_configured() ) {
		return _hub_billing_unavailable( $mode );
	}
	$customer = hub_billing_db_customer_id( $account['id'] );
	if ( '' === $customer ) {
		return _hub_billing_result( true, 'Choose a plan to start.', hub_url( 'pricing/' ), $mode );
	}
	try {
		return _hub_billing_result( true, '', hub_billing_db_portal( $customer, hub_url( 'account/billing/' ) )->url, $mode );
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		return _hub_billing_stripe_error( $e, $mode );
	}
}

/**
 * Handle a Stripe webhook. Answers { ok, type, handled } (200) or an error (400) for a bad signature.
 *
 * @param string $payload   The raw request body.
 * @param string $signature The Stripe-Signature header.
 */
function hub_billing_webhook( $payload, $signature, $mode = HUB_API ) {
	try {
		$event = hub_billing_db_webhook_event( $payload, $signature );
	} catch ( \Exception $e ) {
		return hub_respond_error( 'bad_signature', 'Webhook signature check failed.', $mode, 400 );
	}
	if ( hub_billing_db_seen_event( $event->id ) ) {
		return hub_respond( array( 'ok' => true, 'type' => $event->type, 'handled' => 'duplicate' ), $mode );
	}
	$o       = $event->data->object;
	$handled = 'ignored';
	switch ( $event->type ) {
		case 'checkout.session.completed':
			$user = (int) ( isset( $o->metadata->hub_user ) ? $o->metadata->hub_user : 0 );
			if ( $user && $o->customer ) {
				hub_billing_db_save_customer_id( $user, $o->customer );
			}
			if ( 'subscription' === $o->mode && $o->subscription ) {
				_hub_billing_sync_subscription( hub_billing_db_get_subscription( $o->subscription ), (int) $o->metadata->hub_business );
				$handled = 'plan';
			} elseif ( 'payment' === $o->mode && 'paid' === $o->payment_status ) {
				$m = $o->metadata;
				hub_billing_db_log( array( 'stripe_id' => $o->id, 'kind' => 'Payment', 'item' => $m->hub_item, 'amount' => (int) $o->amount_total, 'status' => 'paid', 'user' => $user, 'business' => (int) $m->hub_business, 'ref' => (string) $m->hub_ref ) );
				do_action( 'hub_billing_item_paid', (string) $m->hub_item, (string) $m->hub_ref, $user, (int) $m->hub_business );
				$handled = 'item';
			}
			break;
		case 'customer.subscription.created':
		case 'customer.subscription.updated':
		case 'customer.subscription.deleted':
			$biz = isset( $o->metadata->hub_business ) ? (int) $o->metadata->hub_business : hub_billing_db_business_by_subscription( $o->id );
			if ( $biz ) {
				_hub_billing_sync_subscription( hub_billing_db_get_subscription( $o->id ), $biz );
				$handled = 'plan';
			}
			break;
		case 'invoice.paid':
		case 'invoice.payment_failed':
			$user = hub_billing_db_user_by_customer( (string) $o->customer );
			$biz  = $user ? hub_account_business_id( $user ) : 0;
			$paid = 'invoice.paid' === $event->type;
			hub_billing_db_log( array( 'stripe_id' => $o->id, 'kind' => 'Invoice', 'item' => (string) $o->number, 'amount' => (int) ( $paid ? $o->amount_paid : $o->amount_due ), 'status' => $paid ? 'paid' : 'payment failed', 'user' => $user, 'business' => $biz, 'ref' => '' ) );
			if ( ! $paid ) {
				do_action( 'hub_billing_payment_failed', $user, $biz );   // email the business (newsletter/email module)
			}
			$handled = 'invoice';
			break;
	}
	return hub_respond( array( 'ok' => true, 'type' => $event->type, 'handled' => $handled ), $mode );
}

/** Admin or CLI: create or update the Hub's products and prices in Stripe. Answers { ok, mode, done[] }. */
function hub_billing_setup( $mode = HUB_WEB ) {
	if ( ! ( defined( 'WP_CLI' ) || 'cli' === PHP_SAPI || current_user_can( 'manage_options' ) ) ) {
		return hub_respond_error( 'forbidden', 'Admins only.', $mode, 403 );
	}
	if ( ! _hub_billing_configured() ) {
		return hub_respond( array( 'ok' => false, 'mode' => null, 'done' => array( 'Stripe keys are not set up (.env has placeholders).' ) ), $mode );
	}
	$rows = array();
	foreach ( HUB_BILLING_PLANS as $key => $p ) {
		foreach ( HUB_BILLING_INTERVALS as $interval => $per ) {
			$rows[] = array( 'product' => $key, 'name' => $p['name'] . ' plan', 'description' => 'Harrow Online Hub ' . $p['name'] . ' plan', 'amount' => $p['prices'][ $interval ], 'interval' => $per, 'lookup_key' => hub_billing_rule_lookup_key( $key, $interval ) );
		}
	}
	foreach ( HUB_BILLING_ITEMS as $key => $i ) {
		$rows[] = array( 'product' => $key, 'name' => $i['name'], 'description' => html_entity_decode( $i['what'], ENT_QUOTES, 'UTF-8' ), 'amount' => $i['amount'], 'interval' => null, 'lookup_key' => hub_billing_rule_lookup_key( $key ) );
	}
	try {
		$done = hub_billing_db_sync_catalogue( $rows );
	} catch ( \Stripe\Exception\ApiErrorException $e ) {
		return hub_respond( array( 'ok' => false, 'mode' => null, 'done' => array( 'Stripe: ' . $e->getMessage() ) ), $mode );
	}
	return hub_respond( array( 'ok' => true, 'mode' => hub_billing_rule_mode( hub_billing_db_env( 'STRIPE_SECRET_KEY' ) ), 'done' => $done ), $mode );
}

/* ------------------------------------------------------------------ internal */

function _hub_billing_configured() {
	return null !== hub_billing_db_stripe();
}

/** Save Stripe's view of a subscription on the business and set its plan to match. */
function _hub_billing_sync_subscription( $sub, $business_id ) {
	if ( ! $business_id ) {
		return;
	}
	$item   = $sub->items->data ? $sub->items->data[0] : null;
	$parsed = $item ? hub_billing_rule_parse_lookup_key( $item->price->lookup_key ) : null;
	// The billing period end is on the item in newer Stripe API versions, on the subscription in older ones
	$end = $item && isset( $item->current_period_end ) ? $item->current_period_end : ( isset( $sub->current_period_end ) ? $sub->current_period_end : 0 );
	hub_billing_db_save_subscription( $business_id, array(
		'id'                   => $sub->id,
		'status'               => $sub->status,
		'plan_key'             => $parsed ? $parsed[0] : null,
		'interval'             => $parsed ? $parsed[1] : null,
		'period_end'           => (int) $end,
		'cancel_at_period_end' => (bool) $sub->cancel_at_period_end,
	) );
	hub_business_set_plan( $business_id, hub_billing_rule_plan_for_status( $parsed ? $parsed[0] : '', $sub->status ) );
}

/** Only come back to a Hub page after paying (never another site); the account page otherwise. */
function _hub_billing_safe_return( $url ) {
	return ( '' !== $url && 0 === strpos( $url, hub_url() ) ) ? $url : hub_url( 'account/' );
}

function _hub_billing_need_business( array $account, $mode ) {
	if ( ! $account['signed_in'] ) {
		return _hub_billing_result( false, 'Sign in to choose a plan.', hub_url( 'sign-in/' ), $mode );
	}
	if ( ! $account['business'] ) {
		return _hub_billing_result( false, 'Plans are for a business listing. Claim or add your business first.', hub_url( 'search/' ), $mode );
	}
	return null;
}

function _hub_billing_result( $ok, $message, $redirect, $mode, $unavailable = false ) {
	return hub_respond( array( 'ok' => (bool) $ok, 'message' => $message, 'redirect' => $redirect, 'unavailable' => (bool) $unavailable ), $mode, $ok ? 200 : 400 );
}

function _hub_billing_unavailable( $mode ) {
	return _hub_billing_result( false, 'Online payments aren&rsquo;t switched on yet. Please contact us to upgrade.', hub_url( 'account/billing/?billing=unavailable' ), $mode, true );
}

function _hub_billing_stripe_error( \Exception $e, $mode ) {
	hub_debug_log( 'billing: Stripe error: ' . $e->getMessage() );
	return _hub_billing_result( false, 'We couldn&rsquo;t reach the payment service. Please try again in a minute.', hub_url( 'account/billing/?billing=error' ), $mode );
}
