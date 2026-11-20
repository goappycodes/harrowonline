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

/**
 * Launch switches (launch 26-27 Nov 2026, full launch 1 Dec 2026). Turn back on by setting them to true, then run
 * the Stripe setup once (php wp-local/hub/stripe-setup-local.php) so the customer portal follows.
 *   HUB_BILLING_PLAN_CHANGES       false: a business can buy its first plan online, but not switch plan or billing
 *                                  period afterwards (the portal still updates the card, shows invoices and cancels);
 *                                  staff change plans on request.
 *   HUB_BILLING_NEWSLETTER_ADVERTS false: no newsletter adverts on the Hub; /advertise/newsletter/ goes to
 *                                  HUB_BILLING_ADVERTISE_URL, and adverts can't be bought online.
 */
const HUB_BILLING_PLAN_CHANGES       = false;
const HUB_BILLING_NEWSLETTER_ADVERTS = false;
const HUB_BILLING_ADVERTISE_URL      = 'https://harrowonline.org/advertise/';

/** True for the one-off items that are newsletter adverts (sponsor_*). */
function hub_billing_rule_is_newsletter_advert( $item ) {
	return 0 === strpos( (string) $item, 'sponsor_' );
}

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

/**
 * What an invoice was for, from Stripe's billing reason and line descriptions:
 *   "1 × Featured plan (at £29.00 / month)"            -> "Featured plan"
 *   plan change ("Remaining time on Local Partner plan after 20 Nov 2026", ...) -> "Plan change: Local Partner plan"
 */
function hub_billing_rule_invoice_for( $reason, array $lines ) {
	$texts = array_map( function ( $l ) { return (string) $l[0]; }, $lines );
	if ( 'subscription_update' === $reason ) {
		foreach ( array_reverse( $texts ) as $t ) {
			if ( preg_match( '/^Remaining time on (.+?) after /', $t, $m ) ) {
				return 'Plan change: ' . $m[1];
			}
		}
		return 'Plan change';
	}
	$t = $texts ? $texts[0] : '';
	$t = preg_replace( '/^\d+\s*(?:×|x)\s*/u', '', $t );   // "1 × "
	$t = preg_replace( '/\s*\(at [^)]*\)$/', '', $t );       // " (at £29.00 / month)"
	if ( count( $texts ) > 1 ) {
		$t .= ' and ' . ( count( $texts ) - 1 ) . ' more';
	}
	return '' !== trim( $t ) ? trim( $t ) : 'Harrow Online Hub';
}

/** "£29", "£290", "£7.99" */
function hub_billing_rule_money( $pence ) {
	return '£' . ( 0 === $pence % 100 ? number_format( $pence / 100 ) : number_format( $pence / 100, 2 ) );
}
