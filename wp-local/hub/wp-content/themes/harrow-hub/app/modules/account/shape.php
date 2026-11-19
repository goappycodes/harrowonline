<?php
/**
 * Accounts: the one array shape pages and the app get.
 *
 * account:
 *   signed_in (bool), id, name, first_name, email, type ('business' | 'organiser' | 'admin'),
 *   business: business summary (businesses module shape) | null,
 *   plan ('none' | 'basic' | 'enhanced' | 'premium' | 'partner'), plan_label ('Basic', 'Featured', 'Local Partner'),
 *   paid (bool: on Featured or Local Partner)
 *
 * Never holds passwords, Stripe IDs or anything private beyond the user's own name and email.
 */
defined( 'ABSPATH' ) || exit;

/** The signed-out shape. */
function hub_account_shape_guest() {
	return array(
		'signed_in'  => false,
		'id'         => 0,
		'name'       => '',
		'first_name' => '',
		'email'      => '',
		'type'       => '',
		'business'   => null,
		'plan'       => 'none',
		'plan_label' => '',
		'paid'       => false,
	);
}

/**
 * @param WP_User    $u
 * @param string     $type      hub_account_db_type()
 * @param array|null $business  Business summary, or null.
 */
function hub_account_shape( WP_User $u, $type, $business ) {
	$plan   = $business ? ( 'unclaimed' === $business['plan'] ? 'basic' : $business['plan'] ) : 'none';
	$labels = array( 'basic' => 'Basic', 'enhanced' => 'Featured', 'premium' => 'Featured', 'partner' => 'Local Partner' );
	return array(
		'signed_in'  => true,
		'id'         => (int) $u->ID,
		'name'       => $u->display_name,
		'first_name' => '' !== $u->first_name ? $u->first_name : $u->display_name,
		'email'      => $u->user_email,
		'type'       => $type,
		'business'   => $business,
		'plan'       => $plan,
		'plan_label' => isset( $labels[ $plan ] ) ? $labels[ $plan ] : '',
		'paid'       => in_array( $plan, array( 'enhanced', 'premium', 'partner' ), true ),
	);
}
