<?php
/**
 * Accounts: the only place that reads or writes WordPress users, roles and the user <-> business link.
 *
 * The link (one listing per account): user meta "hub_business_id" on the user, post meta "_hub_owner" on the business.
 * Every account is a Subscriber; user meta "hub_account_type" (business | organiser) and "hub_plan" (basic | featured |
 * partner, business owners only) say what it is (rules.php).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Move users of the Hub's old roles to Subscriber, keeping what the role said in the account type field, then remove
 * the roles. Runs on every load; does nothing once they're gone.
 */
function hub_account_db_migrate_roles() {
	foreach ( HUB_ACCOUNT_OLD_ROLES as $old ) {
		if ( ! get_role( $old ) ) {
			continue;
		}
		foreach ( get_users( array( 'role' => $old, 'fields' => 'ID' ) ) as $id ) {
			$u = new WP_User( $id );
			$u->set_role( HUB_ACCOUNT_ROLE );
			update_user_meta( $id, 'hub_account_type', 'hub_organiser' === $old ? 'organiser' : 'business' );
			do_action( 'hub_account_role_migrated', (int) $id );   // then the plan field is filled in from their business
		}
		remove_role( $old );
	}
}

/** The account type field: 'business', 'organiser', or '' (not a Hub account, e.g. staff). */
function hub_account_db_account_type( $user_id ) {
	$t = (string) get_user_meta( (int) $user_id, 'hub_account_type', true );
	return isset( HUB_ACCOUNT_TYPES[ $t ] ) ? $t : '';
}

/** The plan field: 'basic', 'featured', 'partner', or '' (organisers and staff). */
function hub_account_db_plan( $user_id ) {
	$p = (string) get_user_meta( (int) $user_id, 'hub_plan', true );
	return isset( HUB_ACCOUNT_PLANS[ $p ] ) ? $p : '';
}

function hub_account_db_save_plan( $user_id, $plan ) {
	update_user_meta( (int) $user_id, 'hub_plan', $plan );
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

/** The user a reset link belongs to, or WP_Error (invalid_key, expired_key) if the link is wrong, used or expired. */
function hub_account_db_check_reset_key( $key, $login ) {
	return check_password_reset_key( $key, $login );
}

/** Set a new password (WordPress also invalidates the reset link and signs out the user's other sessions). */
function hub_account_db_reset_password( WP_User $u, $password ) {
	reset_password( $u, $password );
}

/** WordPress's "New user registration" email to the site admin. */
function hub_account_db_notify_admin( $user_id ) {
	wp_new_user_notification( (int) $user_id, null, 'admin' );
}

/** The user's account type from their role: 'business', 'organiser' or 'admin'. */
function hub_account_db_type( WP_User $u ) {
	$type = hub_account_db_account_type( $u->ID );
	if ( '' !== $type ) {
		return $type;
	}
	return user_can( $u, 'manage_options' ) ? 'admin' : 'business';
}

/* ------------------------------------------------------------------ attempt limits (transients) */

/** The visitor's IP address (Forge's nginx talks to PHP directly; behind a proxy such as Cloudflare this needs its header). */
function hub_account_db_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

function hub_account_db_attempts( $key ) {
	return (int) get_transient( 'hub_lim_' . $key );
}

/** Count one more try; the bucket empties $seconds after this one. */
function hub_account_db_add_attempt( $key, $seconds ) {
	$n = hub_account_db_attempts( $key ) + 1;
	set_transient( 'hub_lim_' . $key, $n, $seconds );
	return $n;
}

function hub_account_db_clear_attempts( $key ) {
	delete_transient( 'hub_lim_' . $key );
}

/* ------------------------------------------------------------------ email confirmation (user meta) */

/**
 * Has the user confirmed their email address? Accounts made before confirmation existed (no "hub_email_confirmed"
 * meta) count as confirmed; new ones start at "0".
 */
function hub_account_db_email_confirmed( $user_id ) {
	return '0' !== (string) get_user_meta( (int) $user_id, 'hub_email_confirmed', true );
}

function hub_account_db_set_email_confirmed( $user_id, $confirmed ) {
	update_user_meta( (int) $user_id, 'hub_email_confirmed', $confirmed ? '1' : '0' );
	if ( $confirmed ) {
		delete_user_meta( (int) $user_id, 'hub_confirm' );
	}
}

/** A new confirmation key for the link (only its hash is stored; a new key replaces the old one). */
function hub_account_db_new_confirm_key( $user_id ) {
	$key = wp_generate_password( 32, false );
	update_user_meta( (int) $user_id, 'hub_confirm', array( 'hash' => hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ), 'at' => time() ) );
	return $key;
}

/** True when $key is the user's current confirmation key and was made within $max_age seconds. */
function hub_account_db_check_confirm_key( $user_id, $key, $max_age ) {
	$c = get_user_meta( (int) $user_id, 'hub_confirm', true );
	return is_array( $c ) && ! empty( $c['hash'] ) && time() - (int) $c['at'] <= $max_age
		&& hash_equals( $c['hash'], hash_hmac( 'sha256', (string) $key, wp_salt( 'auth' ) ) );
}

function hub_account_db_send_email( $to, $subject, $body ) {
	return wp_mail( $to, $subject, $body, array( 'Reply-To: Harrow Online <hub@harrowonline.org>' ) );
}

/* ------------------------------------------------------------------ settings */

function hub_account_db_update_name( $user_id, $name ) {
	$parts = preg_split( '/\s+/', trim( $name ), 2 );
	return wp_update_user( array( 'ID' => (int) $user_id, 'display_name' => $name, 'first_name' => $parts[0], 'last_name' => isset( $parts[1] ) ? $parts[1] : '' ) );
}

function hub_account_db_check_password( WP_User $u, $password ) {
	return wp_check_password( (string) $password, $u->user_pass, $u->ID );
}

/** New password; WordPress emails the user that it changed. The caller signs them back in on this device. */
function hub_account_db_change_password( $user_id, $password ) {
	return wp_update_user( array( 'ID' => (int) $user_id, 'user_pass' => $password ) );
}

/** A requested email change, waiting for the link sent to the new address: { email, hash, at } or null. */
function hub_account_db_pending_email( $user_id ) {
	$p = get_user_meta( (int) $user_id, 'hub_email_change', true );
	return is_array( $p ) && ! empty( $p['email'] ) ? $p : null;
}

function hub_account_db_request_email_change( $user_id, $email ) {
	$key = wp_generate_password( 32, false );
	update_user_meta( (int) $user_id, 'hub_email_change', array( 'email' => $email, 'hash' => hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ), 'at' => time() ) );
	return $key;
}

function hub_account_db_check_email_change( $user_id, $key, $max_age ) {
	$p = hub_account_db_pending_email( $user_id );
	return $p && time() - (int) $p['at'] <= $max_age && hash_equals( $p['hash'], hash_hmac( 'sha256', (string) $key, wp_salt( 'auth' ) ) );
}

function hub_account_db_cancel_email_change( $user_id ) {
	delete_user_meta( (int) $user_id, 'hub_email_change' );
}

/**
 * Switch to the confirmed new email. Hub accounts sign in with their email, so the login name changes too (else the
 * old address couldn't be used for a new account). False if the address was taken in the meantime.
 */
function hub_account_db_apply_email_change( $user_id, $email ) {
	global $wpdb;
	$taken = get_user_by( 'email', $email );
	if ( ( $taken && (int) $taken->ID !== (int) $user_id ) || ( username_exists( $email ) && (int) username_exists( $email ) !== (int) $user_id ) ) {
		return false;
	}
	$u        = get_userdata( (int) $user_id );
	$is_login = $u && strtolower( $u->user_login ) === strtolower( $u->user_email );
	wp_update_user( array( 'ID' => (int) $user_id, 'user_email' => $email ) );
	if ( $is_login ) {
		$wpdb->update( $wpdb->users, array( 'user_login' => $email, 'user_nicename' => sanitize_title( $email ) ), array( 'ID' => (int) $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_user_cache( (int) $user_id );
	}
	delete_user_meta( (int) $user_id, 'hub_email_change' );
	return true;
}

function hub_account_db_notify( $user_id ) {
	$n = get_user_meta( (int) $user_id, 'hub_notify', true );
	$n = is_array( $n ) ? $n : array();
	$out = array();
	foreach ( array_keys( HUB_ACCOUNT_NOTIFY ) as $k ) {
		$out[ $k ] = isset( $n[ $k ] ) ? (bool) $n[ $k ] : true;   // on unless turned off
	}
	return $out;
}

function hub_account_db_save_notify( $user_id, array $n ) {
	update_user_meta( (int) $user_id, 'hub_notify', $n );
}

function hub_account_db_editions( $user_id ) {
	$e = get_user_meta( (int) $user_id, 'hub_newsletter_editions', true );
	return is_array( $e ) ? array_values( array_intersect( $e, array_keys( HUB_ACCOUNT_EDITIONS ) ) ) : array();
}

function hub_account_db_save_editions( $user_id, array $editions ) {
	update_user_meta( (int) $user_id, 'hub_newsletter_editions', array_values( $editions ) );
	update_user_meta( (int) $user_id, 'hub_newsletter_changed', time() );   // for the newsletter module's Beehiiv sync
}

/** Delete the user (their Hub posts, if any, go with them). */
function hub_account_db_delete_user( $user_id ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	return wp_delete_user( (int) $user_id );
}
