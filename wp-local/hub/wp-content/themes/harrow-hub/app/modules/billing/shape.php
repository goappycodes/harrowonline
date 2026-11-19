<?php
/**
 * Billing: the one array shape pages and the app get. Plain data; no Stripe IDs or card details.
 *
 * billing:
 *   configured (bool: Stripe keys set up), mode ('test' | 'live' | null),
 *   plan ('none' | 'basic' | 'enhanced' | 'premium' | 'partner'), plan_name ('Basic', 'Featured', 'Local Partner'),
 *   interval ('monthly' | 'annual' | null), price (e.g. "£29") | null, price_per ('month' | 'year' | null),
 *   status ('active' | 'trialing' | 'past_due' | 'canceled' | ... | null), status_label | null,
 *   renews_on ('Y-m-d') | null, cancels_at_period_end (bool), has_subscription (bool), can_manage (bool: has a Stripe customer),
 *   annual_saving (e.g. "£58") | null  (shown when paying monthly)
 */
defined( 'ABSPATH' ) || exit;

function hub_billing_shape( $configured, $mode, $plan, array $sub, $has_customer ) {
	$names    = array( 'basic' => 'Basic', 'enhanced' => 'Featured', 'premium' => 'Featured', 'partner' => 'Local Partner' );
	$key      = isset( $sub['plan_key'] ) ? $sub['plan_key'] : null;
	$interval = isset( $sub['interval'] ) ? $sub['interval'] : null;
	$prices   = ( $key && isset( HUB_BILLING_PLANS[ $key ] ) ) ? HUB_BILLING_PLANS[ $key ]['prices'] : null;
	$status   = isset( $sub['status'] ) ? $sub['status'] : null;
	$labels   = array( 'active' => 'Active', 'trialing' => 'Trial', 'past_due' => 'Payment failed', 'canceled' => 'Cancelled', 'unpaid' => 'Unpaid', 'incomplete' => 'Waiting for payment', 'incomplete_expired' => 'Not completed' );
	return array(
		'configured'            => (bool) $configured,
		'mode'                  => $configured ? $mode : null,
		'plan'                  => $plan,
		'plan_name'             => isset( $names[ $plan ] ) ? $names[ $plan ] : '',
		'interval'              => $interval,
		'price'                 => ( $prices && $interval ) ? hub_billing_rule_money( $prices[ $interval ] ) : null,
		'price_per'             => $interval ? HUB_BILLING_INTERVALS[ $interval ] : null,
		'status'                => $status,
		'status_label'          => $status && isset( $labels[ $status ] ) ? $labels[ $status ] : $status,
		'renews_on'             => ! empty( $sub['period_end'] ) ? gmdate( 'Y-m-d', (int) $sub['period_end'] ) : null,
		'cancels_at_period_end' => ! empty( $sub['cancel_at_period_end'] ),
		'has_subscription'      => ! empty( $sub['id'] ) && in_array( $status, array( 'active', 'trialing', 'past_due' ), true ),
		'can_manage'            => (bool) $has_customer,
		'annual_saving'         => ( $prices && 'monthly' === $interval ) ? hub_billing_rule_money( 12 * $prices['monthly'] - $prices['annual'] ) : null,
	);
}

/**
 * Billing emails, as plain text: { subject, body }.
 *
 * @param string $kind 'payment_failed' | 'plan_ended'
 * @param array  $a    { first_name, business, plan_name, amount (e.g. "£29"), link }
 */
function hub_billing_shape_email( $kind, array $a ) {
	$hi   = 'Hi ' . ( '' !== $a['first_name'] ? $a['first_name'] : 'there' ) . ",\n\n";
	$sign = "\n\nThanks,\nThe Harrow Online team\nhub@harrowonline.org";
	if ( 'payment_failed' === $kind ) {
		return array(
			'subject' => 'We couldn\'t take your Harrow Online payment',
			'body'    => $hi . 'We couldn\'t take the ' . $a['amount'] . ' payment for ' . $a['business'] . '\'s ' . $a['plan_name'] . ' plan on the Harrow Online Hub.'
				. "\n\nPlease update your card here:\n" . $a['link']
				. "\n\nWe'll try again automatically over the next few days. Your listing keeps its " . $a['plan_name'] . ' features while we do. If the last try fails, it moves to the free Basic plan. Nothing is deleted, and everything comes back when you upgrade again.' . $sign,
		);
	}
	return array(
		'subject' => 'Your Harrow Online ' . $a['plan_name'] . ' plan has ended',
		'body'    => $hi . $a['business'] . '\'s ' . $a['plan_name'] . ' plan on the Harrow Online Hub has ended, and the listing is now on the free Basic plan.'
			. "\n\nYour extra photos, social links, offers and events are hidden, not deleted. They come back as soon as you choose a plan again:\n" . $a['link']
			. "\n\nIf this wasn't what you expected, just reply to this email." . $sign,
	);
}
