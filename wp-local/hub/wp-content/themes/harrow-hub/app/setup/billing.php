<?php
/**
 * Billing pages: the website's checkout and billing-portal addresses, and the private Payments log in wp-admin.
 *
 *   /billing/checkout/?plan=featured&billing=monthly     a plan (Pricing and account billing buttons)
 *   /billing/checkout/?item=event_listing&ref=123         a one-off item (&return= a Hub page to come back to)
 *   /billing/portal/                                      Stripe's billing portal ("Manage billing")
 *
 * Signed-out visitors go to sign-in and come back. Data and Stripe calls are the billing module's controller.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', function () {
	$path = hub_account_request_path();
	if ( ! in_array( $path, array( 'billing/checkout', 'billing/portal' ), true ) ) {
		return;
	}
	nocache_headers();
	if ( ! is_user_logged_in() ) {
		$back = hub_url( $path . '/' ) . ( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $back ), hub_url( 'sign-in/' ) ) );
		exit;
	}
	$params = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- starting a checkout changes nothing until the person pays on Stripe
	if ( 'billing/portal' === $path ) {
		$r = hub_billing_portal();
	} else {
		$r = isset( $params['item'] ) ? hub_billing_checkout_item( $params ) : hub_billing_checkout_plan( $params );
	}
	// Stripe's own pages (checkout.stripe.com, billing.stripe.com) or back to the Hub
	add_filter( 'allowed_redirect_hosts', function ( $hosts ) {
		return array_merge( $hosts, array( 'checkout.stripe.com', 'billing.stripe.com' ) );
	} );
	wp_safe_redirect( $r['redirect'] ? $r['redirect'] : hub_url( 'account/billing/' ), 303 );
	exit;
}, 4 );

/** Private log of payments and invoices (wp-admin > Payments). Written by the billing webhook only. */
add_action( 'init', function () {
	register_post_type( 'hub_payment', array(
		'labels'          => array( 'name' => 'Payments', 'singular_name' => 'Payment', 'menu_name' => 'Payments', 'all_items' => 'All payments' ),
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-money-alt',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),   // only Stripe adds payments
		'map_meta_cap'    => true,
	) );
} );

add_filter( 'manage_hub_payment_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => 'Payment', 'hub_business' => 'Business', 'hub_user' => 'Account', 'date' => 'Date' );
} );
add_action( 'manage_hub_payment_posts_custom_column', function ( $col, $post_id ) {
	if ( 'hub_business' === $col ) {
		$b = (int) get_post_meta( $post_id, '_hub_business', true );
		echo $b ? '<a href="' . esc_url( get_edit_post_link( $b ) ) . '">' . esc_html( get_the_title( $b ) ) . '</a>' : '&ndash;';
	} elseif ( 'hub_user' === $col ) {
		$u = get_userdata( (int) get_post_meta( $post_id, '_hub_user', true ) );
		echo $u ? '<a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->user_email ) . '</a>' : '&ndash;';
	}
}, 10, 2 );
