<?php
/**
 * Billing: REST routes (/wp-json/hub/v1/billing/...). Each calls the controller in API mode.
 *
 *   GET  /billing/catalogue   plans and one-off items with prices (public)
 *   GET  /billing/status      the signed-in business's plan and subscription
 *   POST /billing/checkout    { plan, billing } or { item, ref, return }: answers { redirect } to Stripe Checkout
 *   POST /billing/portal      answers { redirect } to Stripe's billing portal
 *   POST /billing/webhook     Stripe's events (public; checked by the Stripe-Signature header)
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/billing/catalogue', 'GET', function () {
	return hub_billing_catalogue( HUB_API );
} );
hub_route( '/billing/status', 'GET', function () {
	return hub_billing_status( HUB_API );
}, 'is_user_logged_in' );
hub_route( '/billing/checkout', 'POST', function ( WP_REST_Request $req ) {
	$p = $req->get_params();
	return isset( $p['item'] ) ? hub_billing_checkout_item( $p, HUB_API ) : hub_billing_checkout_plan( $p, HUB_API );
}, 'is_user_logged_in' );
hub_route( '/billing/portal', 'POST', function () {
	return hub_billing_portal( HUB_API );
}, 'is_user_logged_in' );
hub_route( '/billing/webhook', 'POST', function ( WP_REST_Request $req ) {
	return hub_billing_webhook( $req->get_body(), (string) $req->get_header( 'stripe_signature' ), HUB_API );
} );
