<?php
/**
 * Billing: what the Hub sells and the rules around it (plain PHP, no WordPress, no Stripe).
 * Prices are in pence and include VAT (the treatment is to be confirmed with Harrow Online before launch).
 * Each price has a Stripe lookup key (hub_{product}_{interval}) so the code never stores Stripe price IDs.
 */
defined( 'ABSPATH' ) || exit;

const HUB_BILLING_CURRENCY = 'gbp';

/**
 * Stripe Managed Payments (Stripe as merchant of record, handling VAT): off for now, so Harrow Online stays the
 * seller and prices are shown including VAT (client to decide before going live, 19 Nov 2026). Turning it on needs
 * a Stripe product tax code on every product (stripe-setup-local.php), as Stripe refuses checkouts without them.
 */
const HUB_BILLING_MANAGED_PAYMENTS = false;

/** Plans (subscriptions): key => { plan (the business's biz_plan), name, prices: { monthly, annual } } */
const HUB_BILLING_PLANS = array(
	'featured' => array( 'plan' => 'premium', 'name' => 'Featured', 'prices' => array( 'monthly' => 2900, 'annual' => 29000 ) ),
	'partner'  => array( 'plan' => 'partner', 'name' => 'Local Partner', 'prices' => array( 'monthly' => 7900, 'annual' => 79000 ) ),
);

/** One-off purchases: key => { name, amount, what (shown at checkout) } */
// Newsletter adverts keep their sponsor_* keys: they're the Stripe price lookup keys (hub_sponsor_daily_day_once...).
const HUB_BILLING_ITEMS = array(
	'event_listing'      => array( 'name' => 'One-off event listing', 'amount' => 1500, 'what' => 'One event on What&rsquo;s On until its date, no plan needed' ),
	'feature_7days'      => array( 'name' => 'Feature this (7 days)', 'amount' => 1500, 'what' => 'Shown first on What&rsquo;s On or Local Offers, and in The Weekend newsletter, for 7 days' ),
	'sponsor_daily_day'  => array( 'name' => 'Daily News advert (one day)', 'amount' => 4500, 'what' => 'Your advert in one Daily News issue' ),
	'sponsor_daily_week' => array( 'name' => 'Daily News advert (full week)', 'amount' => 18000, 'what' => 'Your advert in the Monday to Friday Daily News issues' ),
	'sponsor_weekend'    => array( 'name' => 'The Weekend advert', 'amount' => 6000, 'what' => 'Your advert in one Thursday issue of The Weekend' ),
	'sponsor_business'   => array( 'name' => 'Harrow Business advert', 'amount' => 5000, 'what' => 'Your advert in one Monday issue of Harrow Business' ),
);

const HUB_BILLING_INTERVALS = array( 'monthly' => 'month', 'annual' => 'year' );

/** Stripe price lookup key: hub_featured_monthly, hub_partner_annual, hub_event_listing_once */
function hub_billing_rule_lookup_key( $product, $interval = 'once' ) {
	return 'hub_' . $product . '_' . $interval;
}

/** [ plan key, interval ] from a lookup key, or null for one-off items and unknown keys. */
function hub_billing_rule_parse_lookup_key( $key ) {
	if ( preg_match( '/^hub_(featured|partner)_(monthly|annual)$/', (string) $key, $m ) ) {
		return array( $m[1], $m[2] );
	}
	return null;
}

/** True when real Stripe keys are set (the placeholders in .env don't count). */
function hub_billing_rule_configured( $secret, $publishable ) {
	return (bool) preg_match( '/^sk_(test|live)_[A-Za-z0-9]{20,}$/', (string) $secret )
		&& (bool) preg_match( '/^pk_(test|live)_[A-Za-z0-9]{20,}$/', (string) $publishable );
}

/** Test or live mode from the secret key. */
function hub_billing_rule_mode( $secret ) {
	return 0 === strpos( (string) $secret, 'sk_live_' ) ? 'live' : 'test';
}

/**
 * The business's plan from a subscription's state: paying (or in Stripe's retry period) keeps the plan; cancelled,
 * unpaid or never-completed subscriptions drop back to Basic.
 */
function hub_billing_rule_plan_for_status( $plan_key, $status ) {
	if ( in_array( $status, array( 'active', 'trialing', 'past_due' ), true ) && isset( HUB_BILLING_PLANS[ $plan_key ] ) ) {
		return HUB_BILLING_PLANS[ $plan_key ]['plan'];
	}
	return 'basic';
}

/** "£29", "£290", "£7.99" */
function hub_billing_rule_money( $pence ) {
	return '£' . ( 0 === $pence % 100 ? number_format( $pence / 100 ) : number_format( $pence / 100, 2 ) );
}
