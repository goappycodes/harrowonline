<?php
/**
 * Accounts: the only place that reads or writes WordPress users, roles and the user <-> business link.
 *
 * The link (one listing per account): user meta "hub_business_id" on the user, post meta "_hub_owner" on the business.
 * Roles: hub_business (a business owner) and hub_organiser (school, club, charity: events and classes only).
 */
defined( 'ABSPATH' ) || exit;

/** Add the Hub's roles if they're missing (run on every load: cheap once they exist). */
function hub_account_db_ensure_roles() {
	foreach ( HUB_ACCOUNT_ROLES as $role => $label ) {
		if ( ! get_role( $role ) ) {
			add_role( $role, $label, array( 'read' => true ) );
		}
	}
}

/** The signed-in user, or null. */
function hub_account_db_current_user() {
	$u = wp_get_current_user();
	return $u && $u->exists() ? $u : null;
}

/** The business post ID linked to a user, or 0. */
function hub_account_db_business_id( $user_id ) {
	$id = (int) get_user_meta( (int) $user_id, 'hub_business_id', true );
	return ( $id && 'hub_business' === get_post_type( $id ) ) ? $id : 0;
}

/** The user linked to a business, or 0. */
function hub_account_db_owner_id( $business_id ) {
	return (int) get_post_meta( (int) $business_id, '_hub_owner', true );
}

/**
 * Link a user to a business (0 = unlink). One business per user and one owner per business: any previous link on
 * either side is removed first.
 */
function hub_account_db_link( $user_id, $business_id ) {
	$user_id     = (int) $user_id;
	$business_id = (int) $business_id;
	$old_biz     = hub_account_db_business_id( $user_id );
	if ( $old_biz ) {
		delete_post_meta( $old_biz, '_hub_owner' );
	}
	if ( ! $business_id ) {
		delete_user_meta( $user_id, 'hub_business_id' );
		return;
	}
	$old_owner = hub_account_db_owner_id( $business_id );
	if ( $old_owner && $old_owner !== $user_id ) {
		delete_user_meta( $old_owner, 'hub_business_id' );
	}
	update_user_meta( $user_id, 'hub_business_id', $business_id );
	update_post_meta( $business_id, '_hub_owner', $user_id );
}

/**
 * Create a user. The email is also the login name.
 *
 * @return int|WP_Error User ID.
 */
function hub_account_db_create_user( $email, $password, $name, $role, array $meta = array() ) {
	$parts = preg_split( '/\s+/', trim( $name ), 2 );
	$id    = wp_insert_user( array(
		'user_login'   => $email,
		'user_email'   => $email,
		'user_pass'    => $password,
		'display_name' => $name,
		'first_name'   => $parts[0],
		'last_name'    => isset( $parts[1] ) ? $parts[1] : '',
		'role'         => $role,
	) );
	if ( ! is_wp_error( $id ) ) {
		foreach ( $meta as $k => $v ) {
			update_user_meta( $id, $k, $v );
		}
	}
	return $id;
}

function hub_account_db_email_exists( $email ) {
	return (bool) email_exists( $email );
}

/**
 * Check a login and password and set the sign-in cookie.
 *
 * @return WP_User|WP_Error
 */
function hub_account_db_sign_in( $login, $password, $remember ) {
	return wp_signon( array( 'user_login' => $login, 'user_password' => $password, 'remember' => $remember ), is_ssl() );
}

/** Sign a just-created user in (after registering). */
function hub_account_db_set_signed_in( $user_id ) {
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );
}

function hub_account_db_sign_out() {
	wp_logout();
}

/** Send WordPress's password-reset email. Errors (unknown email) are swallowed by the controller on purpose. */
function hub_account_db_send_reset( $login ) {
	return retrieve_password( $login );
}

/** The user's account type from their role: 'business', 'organiser' or 'admin'. */
function hub_account_db_type( WP_User $u ) {
	if ( in_array( 'hub_organiser', (array) $u->roles, true ) ) {
		return 'organiser';
	}
	if ( in_array( 'hub_business', (array) $u->roles, true ) ) {
		return 'business';
	}
	return user_can( $u, 'manage_options' ) ? 'admin' : 'business';
}
