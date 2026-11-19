<?php
/**
 * Accounts: what pages and the API call. Nothing outside this module calls anything else in it.
 *
 *   hub_account_current()                  the signed-in account (or the signed-out shape)
 *   hub_account_sign_in( $params )         sign in with email and password
 *   hub_account_register( $params )        create a business or organiser account and sign it in
 *   hub_account_reset_request( $params )   email a password-reset link (same answer whether or not the email exists)
 *   hub_account_sign_out()
 *   hub_account_business_id( $user_id )    the business a user manages (0 = none), for other modules (billing)
 *   hub_account_link_business( $user_id, $business_id )   admin: link a user to their business
 *
 * Form actions return { ok, message, errors: { field: message }, redirect, account }.
 * Web forms post a nonce (field "_hub_nonce", action "hub_account"); the API uses WordPress's REST authentication.
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/** The signed-in account. */
function hub_account_current( $mode = HUB_WEB ) {
	$u = hub_account_db_current_user();
	if ( ! $u ) {
		return hub_respond( hub_account_shape_guest(), $mode );
	}
	return hub_respond( _hub_account_for_user( $u ), $mode );
}

/** The business post ID a user manages, or 0. */
function hub_account_business_id( $user_id ) {
	return hub_account_db_business_id( $user_id );
}

/**
 * @param array $params { log (email), pwd, rememberme, redirect_to, _hub_nonce (web) }
 */
function hub_account_sign_in( array $params, $mode = HUB_WEB ) {
	if ( $bad = _hub_account_bad_nonce( $params, $mode ) ) {
		return $bad;
	}
	$email = isset( $params['log'] ) ? sanitize_email( wp_unslash( (string) $params['log'] ) ) : '';
	$pass  = isset( $params['pwd'] ) ? (string) $params['pwd'] : '';
	if ( '' === $email || '' === $pass ) {
		return _hub_account_result( false, 'Enter your email address and password.', array( 'log' => '' === $email ? 'Enter your email address.' : '', 'pwd' => '' === $pass ? 'Enter your password.' : '' ), '', $mode );
	}
	$u = hub_account_db_sign_in( $email, $pass, hub_param_bool( $params, 'rememberme' ) || 'forever' === ( isset( $params['rememberme'] ) ? $params['rememberme'] : '' ) );
	if ( is_wp_error( $u ) ) {
		// One message for a wrong email or a wrong password, so the form doesn't reveal who has an account
		return _hub_account_result( false, 'That email address and password don&rsquo;t match an account. Check them, or reset your password.', array(), '', $mode );
	}
	wp_set_current_user( $u->ID );
	$return = hub_account_rule_safe_return( isset( $params['redirect_to'] ) ? wp_unslash( (string) $params['redirect_to'] ) : '', hub_url() );
	return _hub_account_result( true, 'Signed in.', array(), $return, $mode );
}

/**
 * @param array $params {
 *     account_type ('business' | 'organiser'), full_name (or name: WordPress keeps "name" for itself on web forms), email, password, organisation (organisers), area,
 *     terms (must be 1), join_newsletter, editions[], website_url (spam trap: must be empty), _hub_nonce (web)
 * }
 */
function hub_account_register( array $params, $mode = HUB_WEB ) {
	if ( $bad = _hub_account_bad_nonce( $params, $mode ) ) {
		return $bad;
	}
	$get    = function ( $k ) use ( $params ) { return isset( $params[ $k ] ) && is_scalar( $params[ $k ] ) ? trim( wp_unslash( (string) $params[ $k ] ) ) : ''; };
	$type   = hub_account_rule_type( $get( 'account_type' ) );
	$name   = sanitize_text_field( '' !== $get( 'full_name' ) ? $get( 'full_name' ) : $get( 'name' ) );
	$email  = sanitize_email( $get( 'email' ) );
	$pass   = isset( $params['password'] ) ? (string) $params['password'] : '';
	$org    = sanitize_text_field( $get( 'organisation' ) );
	$errors = array();
	if ( '' !== $get( 'website_url' ) ) {
		// Spam trap filled in: answer as if it worked, create nothing
		return _hub_account_result( true, 'Account created.', array(), hub_url( 'account/' ), $mode );
	}
	if ( '' === $name ) {
		$errors['full_name'] = 'Enter your name.';
	}
	if ( ! is_email( $email ) ) {
		$errors['email'] = 'Enter a valid email address.';
	} elseif ( hub_account_db_email_exists( $email ) ) {
		$errors['email'] = 'There&rsquo;s already an account with this email address. Sign in, or reset your password.';
	}
	if ( ! hub_account_rule_password_ok( $pass ) ) {
		$errors['password'] = 'Use at least ' . HUB_ACCOUNT_MIN_PASSWORD . ' characters.';
	}
	if ( 'organiser' === $type && '' === $org ) {
		$errors['organisation'] = 'Enter the name of your school, club, charity or group.';
	}
	if ( ! hub_param_bool( $params, 'terms' ) ) {
		$errors['terms'] = 'Please agree to the terms of use.';
	}
	if ( $errors ) {
		return _hub_account_result( false, 'Please check the highlighted fields.', $errors, '', $mode );
	}
	$editions = array();
	if ( hub_param_bool( $params, 'join_newsletter' ) && isset( $params['editions'] ) && is_array( $params['editions'] ) ) {
		$editions = array_values( array_intersect( array_map( 'strval', $params['editions'] ), array_keys( HUB_ACCOUNT_EDITIONS ) ) );
	}
	$id = hub_account_db_create_user( $email, $pass, $name, HUB_ACCOUNT_TYPES[ $type ], array(
		'hub_organisation'        => $org,
		'hub_area'                => hub_param_slug( $params, 'area' ),
		'hub_newsletter_editions' => $editions,   // for the newsletter module (Beehiiv sign-up)
		'hub_signup_source'       => 'hub-registration',
	) );
	if ( is_wp_error( $id ) ) {
		return _hub_account_result( false, 'We couldn&rsquo;t create your account just now. Please try again.', array(), '', $mode );
	}
	hub_account_db_set_signed_in( $id );
	return _hub_account_result( true, 'Account created.', array(), hub_url( 'account/?welcome=1' ), $mode );
}

/** @param array $params { user_login (email), _hub_nonce (web) } */
function hub_account_reset_request( array $params, $mode = HUB_WEB ) {
	if ( $bad = _hub_account_bad_nonce( $params, $mode ) ) {
		return $bad;
	}
	$email = isset( $params['user_login'] ) ? sanitize_email( wp_unslash( (string) $params['user_login'] ) ) : '';
	if ( ! is_email( $email ) ) {
		return _hub_account_result( false, 'Enter the email address you use for the Hub.', array( 'user_login' => 'Enter a valid email address.' ), '', $mode );
	}
	if ( hub_account_db_email_exists( $email ) ) {
		$sent = hub_account_db_send_reset( $email );
		if ( is_wp_error( $sent ) ) {
			hub_debug_log( 'reset email: ' . $sent->get_error_message() );
		}
	}
	// Always the same answer, so the form doesn't reveal who has an account
	return _hub_account_result( true, 'If that email has a Hub account, a reset link is on its way.', array(), '', $mode );
}

function hub_account_sign_out( $mode = HUB_WEB ) {
	hub_account_db_sign_out();
	return hub_respond( hub_account_shape_guest(), $mode );
}

/** Admin: link a user to the business they manage (0 = unlink). */
function hub_account_link_business( $user_id, $business_id, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return hub_respond_error( 'forbidden', 'Only admins can link accounts.', $mode, 403 );
	}
	hub_account_db_link( $user_id, $business_id );
	$u = get_userdata( (int) $user_id );
	return $u ? hub_respond( _hub_account_for_user( $u ), $mode ) : hub_respond_error( 'not_found', 'No such user.', $mode );
}

/** ---------------------------------------------------------------- internal */
function _hub_account_for_user( WP_User $u ) {
	$biz_id   = hub_account_db_business_id( $u->ID );
	$business = null;
	if ( $biz_id ) {
		$found    = hub_businesses_summaries( array( $biz_id ) );
		$business = isset( $found['items'][ $biz_id ] ) ? $found['items'][ $biz_id ] : null;
	}
	return hub_account_shape( $u, hub_account_db_type( $u ), $business );
}

function _hub_account_result( $ok, $message, array $errors, $redirect, $mode ) {
	$data = array(
		'ok'       => (bool) $ok,
		'message'  => $message,
		'errors'   => array_filter( $errors ),
		'redirect' => $redirect,
		'account'  => $ok ? hub_account_current() : hub_account_shape_guest(),
	);
	return hub_respond( $data, $mode, $ok ? 200 : 400 );
}

/** Web forms must carry the nonce; API requests are checked by WordPress's REST authentication instead. */
function _hub_account_bad_nonce( array $params, $mode ) {
	if ( HUB_API === $mode ) {
		return null;
	}
	$nonce = isset( $params['_hub_nonce'] ) ? (string) $params['_hub_nonce'] : '';
	if ( wp_verify_nonce( $nonce, 'hub_account' ) ) {
		return null;
	}
	return _hub_account_result( false, 'This form has expired. Please try again.', array(), '', $mode );
}
