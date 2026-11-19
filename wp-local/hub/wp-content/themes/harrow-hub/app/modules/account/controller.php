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

/** Any account by user ID (for webhooks and emails, where nobody is signed in). Guest shape if there's no such user. */
function hub_account_for_user( $user_id, $mode = HUB_WEB ) {
	$u = get_userdata( (int) $user_id );
	return hub_respond( $u ? _hub_account_for_user( $u ) : hub_account_shape_guest(), $mode );
}

/** The user who manages a business, or 0. */
function hub_account_owner_id( $business_id ) {
	return hub_account_db_owner_id( $business_id );
}

/** The sign-out link, with a token so other sites can't sign people out by linking to it. */
function hub_account_sign_out_url() {
	return add_query_arg( '_wpnonce', wp_create_nonce( 'hub_sign_out' ), hub_url( 'sign-out/' ) );
}

/** Is this sign-out request genuine (the token from hub_account_sign_out_url)? */
function hub_account_sign_out_ok( $token ) {
	return (bool) wp_verify_nonce( (string) $token, 'hub_sign_out' );
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
	if ( is_wp_error( $u ) && 'hub_too_many' === $u->get_error_code() ) {
		return _hub_account_result( false, $u->get_error_message(), array(), '', $mode );
	}
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
	if ( hub_account_limited( 'register_ip', array( hub_account_db_ip() ) ) ) {
		return _hub_account_result( false, 'Too many new accounts from your connection. Please try again in ' . hub_account_rule_wait( 'register_ip' ) . '.', array(), '', $mode );
	}
	$editions = array();
	if ( hub_param_bool( $params, 'join_newsletter' ) && isset( $params['editions'] ) && is_array( $params['editions'] ) ) {
		$editions = array_values( array_intersect( array_map( 'strval', $params['editions'] ), array_keys( HUB_ACCOUNT_EDITIONS ) ) );
	}
	$id = hub_account_db_create_user( $email, $pass, $name, HUB_ACCOUNT_ROLE, array(
		'hub_account_type'        => $type,
		'hub_plan'                => 'business' === $type ? 'basic' : '',
		'hub_email_confirmed'     => '0',
		'hub_organisation'        => $org,
		'hub_area'                => hub_param_slug( $params, 'area' ),
		'hub_newsletter_editions' => $editions,   // for the newsletter module (Beehiiv sign-up)
		'hub_signup_source'       => 'hub-registration',
	) );
	if ( is_wp_error( $id ) ) {
		return _hub_account_result( false, 'We couldn&rsquo;t create your account just now. Please try again.', array(), '', $mode );
	}
	hub_account_count_attempt( 'register_ip', array( hub_account_db_ip() ) );
	hub_account_db_notify_admin( $id );
	_hub_account_send_confirmation( get_userdata( $id ) );
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
	$ip = hub_account_db_ip();
	if ( hub_account_limited( 'reset', array( $email ) ) || hub_account_limited( 'reset_ip', array( $ip ) ) ) {
		return _hub_account_result( false, 'Too many reset requests. Please wait ' . hub_account_rule_wait( 'reset' ) . ' and try again, and check your spam folder for the email we already sent.', array(), '', $mode );
	}
	hub_account_count_attempt( 'reset', array( $email ) );
	hub_account_count_attempt( 'reset_ip', array( $ip ) );
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
	$was_owner = $business_id ? hub_account_db_owner_id( $business_id ) : 0;
	hub_account_db_link( $user_id, $business_id );
	hub_account_sync_plan( $user_id );
	if ( $was_owner && (int) $was_owner !== (int) $user_id ) {
		hub_account_sync_plan( $was_owner );   // the business's previous owner no longer has it: back to Basic
	}
	$u = get_userdata( (int) $user_id );
	return $u ? hub_respond( _hub_account_for_user( $u ), $mode ) : hub_respond_error( 'not_found', 'No such user.', $mode );
}

/**
 * Is this reset link (from the reset email) still good? { ok, message }
 *
 * @param array $params { key, login }
 */
function hub_account_reset_check( array $params, $mode = HUB_WEB ) {
	$u = _hub_account_reset_user( $params );
	return _hub_account_result( ! is_wp_error( $u ), is_wp_error( $u ) ? _hub_account_reset_link_message( $u ) : '', array(), '', $mode );
}

/**
 * Choose a new password from a reset link, then sign in.
 *
 * @param array $params { key, login, password, _hub_nonce (web) }
 */
function hub_account_reset_password( array $params, $mode = HUB_WEB ) {
	if ( $bad = _hub_account_bad_nonce( $params, $mode ) ) {
		return $bad;
	}
	$u = _hub_account_reset_user( $params );
	if ( is_wp_error( $u ) ) {
		return _hub_account_result( false, _hub_account_reset_link_message( $u ), array(), '', $mode );
	}
	$pass = isset( $params['password'] ) ? (string) $params['password'] : '';
	if ( ! hub_account_rule_password_ok( $pass ) ) {
		return _hub_account_result( false, 'Please check the highlighted field.', array( 'password' => 'Use at least ' . HUB_ACCOUNT_MIN_PASSWORD . ' characters.' ), '', $mode );
	}
	hub_account_db_reset_password( $u, $pass );
	hub_account_db_set_email_confirmed( $u->ID, true );   // the reset link reached their inbox
	hub_account_db_set_signed_in( $u->ID );
	return _hub_account_result( true, 'Password changed.', array(), hub_url( 'account/' ), $mode );
}

/** The reset email for a user and the key WordPress made for them: { subject, body } (used by app/setup/accounts.php). */
function hub_account_reset_email( WP_User $u, $key ) {
	$link = add_query_arg( array( 'key' => $key, 'login' => rawurlencode( $u->user_login ) ), hub_url( 'forgot-password/' ) );
	return hub_account_shape_reset_email( (string) $u->first_name, $link );
}

/**
 * Fill in a business owner's plan field from their business's plan (Basic with no business). Organisers and staff
 * are left alone. True when it changed.
 */
function hub_account_sync_plan( $user_id ) {
	$u = get_userdata( (int) $user_id );
	if ( ! $u || 'business' !== hub_account_db_account_type( $u->ID ) ) {
		return false;
	}
	$plan = hub_account_rule_plan_for_business( _hub_account_for_user( $u )['plan'] );
	if ( hub_account_db_plan( $u->ID ) === $plan ) {
		return false;
	}
	hub_account_db_save_plan( $u->ID, $plan );
	return true;
}

/**
 * Set a business owner's plan from wp-admin (staff only). With a linked business, the business's plan changes too
 * (its listing, offers and events follow the plan). A business paying through Stripe gets Stripe's plan back on its
 * next billing event, so change those in Stripe instead.
 */
function hub_account_set_plan( $user_id, $plan, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return hub_respond_error( 'forbidden', 'Only admins can change plans.', $mode, 403 );
	}
	if ( ! isset( HUB_ACCOUNT_PLANS[ $plan ] ) ) {
		return hub_respond_error( 'bad_plan', 'Unknown plan: ' . $plan, $mode, 400 );
	}
	$biz = hub_account_db_business_id( $user_id );
	if ( $biz ) {
		hub_business_set_plan( $biz, hub_account_rule_business_plan( $plan ) );   // businesses module; fires hub_business_plan_changed
	}
	hub_account_db_save_plan( $user_id, $plan );
	return hub_respond( _hub_account_for_user( get_userdata( (int) $user_id ) ), $mode );
}

/** Is this one of the Hub's accounts (a business owner or organiser), rather than staff? */
function hub_account_is_member( $user_id ) {
	return '' !== hub_account_db_account_type( $user_id );
}

/** The same for whoever owns a business (after its plan changes). */
function hub_account_sync_business_owner( $business_id ) {
	$owner = hub_account_db_owner_id( $business_id );
	return $owner ? hub_account_sync_plan( $owner ) : false;
}

/* ---------------------------------------------------------------- settings page */

/**
 * The signed-in account's settings:
 *   { name, email, pending_email, notify { approved, rejected, ended }, editions[], plan_label,
 *     business (name) | null, can_close, close_blocked (why not, or '') }
 */
function hub_account_settings( $mode = HUB_WEB ) {
	$u = hub_account_db_current_user();
	if ( ! $u ) {
		return hub_respond_error( 'signed_out', 'Sign in first.', $mode, 401 );
	}
	$a       = _hub_account_for_user( $u );
	$billing = hub_billing_status();   // billing module
	$paid    = $billing && $billing['has_subscription'];
	$pending = hub_account_db_pending_email( $u->ID );
	return hub_respond( array(
		'name'          => $u->display_name,
		'email'         => $u->user_email,
		'pending_email' => $pending ? $pending['email'] : null,
		'notify'        => hub_account_db_notify( $u->ID ),
		'editions'      => hub_account_db_editions( $u->ID ),
		'plan_label'    => $a['plan_label'],
		'business'      => $a['business'] ? $a['business']['name'] : null,
		'can_close'     => ! $paid && ! user_can( $u, 'edit_posts' ),
		'close_blocked' => $paid ? 'You have an active ' . $billing['plan_name'] . ' plan. Cancel it in Manage billing before closing your account.' : ( user_can( $u, 'edit_posts' ) ? 'Staff accounts are closed by an administrator in wp-admin.' : '' ),
	), $mode );
}

/**
 * Save one settings form. { ok, message, errors, redirect, form }
 *
 * @param array $params { form: details | password | notify | newsletter | close, ...that form's fields, _hub_nonce (web) }
 */
function hub_account_settings_save( array $params, $mode = HUB_WEB ) {
	$form = isset( $params['form'] ) ? sanitize_key( (string) $params['form'] ) : '';
	$res  = function ( $ok, $msg, $errors = array(), $redirect = '' ) use ( $form, $mode ) {
		$r = _hub_account_result( $ok, $msg, $errors, $redirect, HUB_WEB );
		return hub_respond( $r + array( 'form' => $form ), $mode, $ok ? 200 : 400 );
	};
	if ( $bad = _hub_account_bad_nonce( $params, $mode ) ) {
		return $bad;
	}
	$u = hub_account_db_current_user();
	if ( ! $u ) {
		return $res( false, 'Sign in first.', array(), hub_url( 'sign-in/' ) );
	}
	$get  = function ( $k ) use ( $params ) { return isset( $params[ $k ] ) && is_scalar( $params[ $k ] ) ? trim( wp_unslash( (string) $params[ $k ] ) ) : ''; };
	$back = hub_url( 'account/settings/?saved=' . $form );

	if ( 'details' === $form ) {
		$name   = sanitize_text_field( $get( 'name' ) );
		$email  = sanitize_email( $get( 'email' ) );
		$errors = array();
		if ( '' === $name ) {
			$errors['name'] = 'Enter your name.';
		}
		if ( ! is_email( $email ) ) {
			$errors['email'] = 'Enter a valid email address.';
		} elseif ( strtolower( $email ) !== strtolower( $u->user_email ) && hub_account_db_email_exists( $email ) ) {
			$errors['email'] = 'Another account uses that email address.';
		}
		if ( $errors ) {
			return $res( false, 'Please check the highlighted fields.', $errors );
		}
		if ( $name !== $u->display_name ) {
			hub_account_db_update_name( $u->ID, $name );
		}
		if ( strtolower( $email ) === strtolower( $u->user_email ) ) {
			hub_account_db_cancel_email_change( $u->ID );
			return $res( true, 'Details saved.', array(), $back );
		}
		$link = add_query_arg( array( 'u' => $u->ID, 'change' => hub_account_db_request_email_change( $u->ID, $email ) ), hub_url( 'confirm-email/' ) );
		$mail = hub_account_shape_email_change( (string) $u->first_name, $link );
		hub_account_db_send_email( $email, $mail['subject'], $mail['body'] );
		return $res( true, 'Details saved. We&rsquo;ve sent a link to ' . $email . ': your email changes when you click it.', array(), hub_url( 'account/settings/?saved=email' ) );
	}

	if ( 'password' === $form ) {
		$cur    = isset( $params['current_password'] ) ? (string) $params['current_password'] : '';
		$new    = isset( $params['new_password'] ) ? (string) $params['new_password'] : '';
		$new2   = isset( $params['new_password_2'] ) ? (string) $params['new_password_2'] : '';
		$errors = array();
		if ( hub_account_limited( 'sign_in', array( $u->user_login, hub_account_db_ip() ) ) ) {
			return $res( false, 'Too many wrong passwords. Please wait ' . hub_account_rule_wait( 'sign_in' ) . '.' );
		}
		if ( ! hub_account_db_check_password( $u, $cur ) ) {
			hub_account_sign_in_failed( $u->user_login );   // counts towards the sign-in limit, so it can't be used to guess
			$errors['current_password'] = 'That isn&rsquo;t your current password.';
		}
		if ( ! hub_account_rule_password_ok( $new ) ) {
			$errors['new_password'] = 'Use at least ' . HUB_ACCOUNT_MIN_PASSWORD . ' characters.';
		} elseif ( $new !== $new2 ) {
			$errors['new_password_2'] = 'The two new passwords don&rsquo;t match.';
		}
		if ( $errors ) {
			return $res( false, 'Please check the highlighted fields.', $errors );
		}
		hub_account_db_change_password( $u->ID, $new );
		hub_account_db_set_signed_in( $u->ID );   // other devices are signed out; this one stays in
		return $res( true, 'Password changed.', array(), $back );
	}

	if ( 'notify' === $form ) {
		$on = isset( $params['notify'] ) && is_array( $params['notify'] ) ? $params['notify'] : array();
		$n  = array();
		foreach ( array_keys( HUB_ACCOUNT_NOTIFY ) as $k ) {
			$n[ $k ] = ! empty( $on[ $k ] );
		}
		hub_account_db_save_notify( $u->ID, $n );
		return $res( true, 'Email choices saved.', array(), $back );
	}

	if ( 'newsletter' === $form ) {
		$e = isset( $params['editions'] ) && is_array( $params['editions'] ) ? array_intersect( array_map( 'strval', $params['editions'] ), array_keys( HUB_ACCOUNT_EDITIONS ) ) : array();
		hub_account_db_save_editions( $u->ID, $e );
		return $res( true, $e ? 'Newsletter choices saved.' : 'You&rsquo;re unsubscribed from the newsletter.', array(), $back );
	}

	if ( 'close' === $form ) {
		$s = hub_account_settings();
		if ( ! $s['can_close'] ) {
			return $res( false, $s['close_blocked'] );
		}
		$errors = array();
		if ( ! hub_account_db_check_password( $u, isset( $params['close_password'] ) ? (string) $params['close_password'] : '' ) ) {
			hub_account_sign_in_failed( $u->user_login );
			$errors['close_password'] = 'That isn&rsquo;t your password.';
		}
		if ( ! hub_param_bool( $params, 'close_confirm' ) ) {
			$errors['close_confirm'] = 'Tick to confirm you want to close your account.';
		}
		if ( $errors ) {
			return $res( false, 'Your account is still open. Please check the highlighted fields.', $errors );
		}
		$biz = hub_account_db_business_id( $u->ID );
		if ( $biz ) {
			hub_account_db_link( $u->ID, 0 );
			hub_business_set_claimed( $biz, false );   // businesses module: back to an unclaimed listing, content hidden
		}
		hub_account_db_sign_out();
		hub_account_db_delete_user( $u->ID );
		return $res( true, 'Your account is closed.', array(), hub_url( '?account=closed' ) );
	}
	return $res( false, 'Unknown form.' );
}

/** The settings form result for this page view (set when a form was sent and not redirected), or null. */
function hub_account_settings_result( $set = null ) {
	static $result = null;
	if ( null !== $set ) {
		$result = $set;
	}
	return $result;
}

/** The link in the change-email message: /confirm-email/?u=ID&change=KEY. { ok, message, redirect } */
function hub_account_confirm_email_change( array $params, $mode = HUB_WEB ) {
	$id  = hub_param_int( $params, 'u' );
	$key = isset( $params['change'] ) && is_scalar( $params['change'] ) ? (string) wp_unslash( $params['change'] ) : '';
	$p   = $id ? hub_account_db_pending_email( $id ) : null;
	$to  = is_user_logged_in() ? 'account/settings/' : 'sign-in/';
	if ( ! $p || ! hub_account_db_check_email_change( $id, $key, HUB_ACCOUNT_CONFIRM_DAYS * DAY_IN_SECONDS ) ) {
		return _hub_account_result( false, 'That link isn&rsquo;t valid or has expired.', array(), hub_url( $to . '?email=link-invalid' ), $mode );
	}
	$was_signed_in = get_current_user_id() === $id;
	if ( ! hub_account_db_apply_email_change( $id, $p['email'] ) ) {
		hub_account_db_cancel_email_change( $id );
		return _hub_account_result( false, 'Another account uses that email address now.', array(), hub_url( $to . '?email=taken' ), $mode );
	}
	hub_account_db_set_email_confirmed( $id, true );
	if ( $was_signed_in ) {
		hub_account_db_set_signed_in( $id );   // the sign-in cookie holds the login name, which just changed
	}
	return _hub_account_result( true, 'Email address changed.', array(), hub_url( $to . '?email=changed' ), $mode );
}

/* ---------------------------------------------------------------- attempt limits */

/** Has this bucket (rules.php HUB_ACCOUNT_LIMITS) reached its limit for these values (email, IP)? */
function hub_account_limited( $bucket, array $parts ) {
	return hub_account_db_attempts( hub_account_rule_limit_key( $bucket, $parts ) ) >= HUB_ACCOUNT_LIMITS[ $bucket ][0];
}

function hub_account_count_attempt( $bucket, array $parts ) {
	return hub_account_db_add_attempt( hub_account_rule_limit_key( $bucket, $parts ), HUB_ACCOUNT_LIMITS[ $bucket ][1] );
}

function hub_account_clear_attempts( $bucket, array $parts ) {
	hub_account_db_clear_attempts( hub_account_rule_limit_key( $bucket, $parts ) );
}

/**
 * For WordPress's authenticate filter (every sign-in: the Hub's form, wp-login.php, app passwords): a WP_Error when
 * this email or this IP has had too many wrong passwords, else null.
 */
function hub_account_sign_in_blocked( $login ) {
	$ip = hub_account_db_ip();
	if ( hub_account_limited( 'sign_in', array( $login, $ip ) ) || hub_account_limited( 'sign_in_ip', array( $ip ) ) ) {
		return new WP_Error( 'hub_too_many', 'Too many wrong passwords. Please wait ' . hub_account_rule_wait( 'sign_in' ) . ', or reset your password.' );
	}
	return null;
}

/** A wrong password (wp_login_failed): count it for the email and the IP. */
function hub_account_sign_in_failed( $login ) {
	$ip = hub_account_db_ip();
	hub_account_count_attempt( 'sign_in', array( $login, $ip ) );
	hub_account_count_attempt( 'sign_in_ip', array( $ip ) );
}

/** A successful sign-in (wp_login): forget this email's wrong passwords from this IP. */
function hub_account_sign_in_succeeded( $login ) {
	hub_account_clear_attempts( 'sign_in', array( $login, hub_account_db_ip() ) );
}

/* ---------------------------------------------------------------- email confirmation */

/** Has this account confirmed its email address? (Accounts from before confirmation existed count as confirmed.) */
function hub_account_email_confirmed( $user_id ) {
	return hub_account_db_email_confirmed( $user_id );
}

/**
 * The link from the confirmation email: /confirm-email/?u=ID&key=...  { ok, message, redirect }
 *
 * @param array $params { u, key }
 */
function hub_account_confirm_email( array $params, $mode = HUB_WEB ) {
	$id  = hub_param_int( $params, 'u' );
	$key = isset( $params['key'] ) && is_scalar( $params['key'] ) ? (string) wp_unslash( $params['key'] ) : '';
	$signed_in = is_user_logged_in();
	$where     = $signed_in ? 'account/' : 'sign-in/';
	if ( $id && ! hub_account_db_email_confirmed( $id ) && hub_account_db_check_confirm_key( $id, $key, HUB_ACCOUNT_CONFIRM_DAYS * DAY_IN_SECONDS ) ) {
		hub_account_db_set_email_confirmed( $id, true );
		return _hub_account_result( true, 'Email address confirmed.', array(), hub_url( $where . '?email=confirmed' ), $mode );
	}
	if ( $id && get_userdata( $id ) && hub_account_db_email_confirmed( $id ) ) {
		return _hub_account_result( true, 'Your email address is already confirmed.', array(), hub_url( $where . '?email=confirmed' ), $mode );
	}
	return _hub_account_result( false, 'That confirmation link isn&rsquo;t valid or has expired.', array(), hub_url( $where . '?email=link-invalid' ), $mode );
}

/** Send the signed-in account a new confirmation email (limited to a few an hour). */
function hub_account_resend_confirmation( array $params, $mode = HUB_WEB ) {
	if ( $bad = _hub_account_bad_nonce( $params, $mode ) ) {
		return $bad;
	}
	$u = hub_account_db_current_user();
	if ( ! $u ) {
		return _hub_account_result( false, 'Sign in first.', array(), hub_url( 'sign-in/' ), $mode );
	}
	if ( hub_account_db_email_confirmed( $u->ID ) ) {
		return _hub_account_result( true, 'Your email address is already confirmed.', array(), hub_url( 'account/?email=confirmed' ), $mode );
	}
	if ( hub_account_limited( 'confirm', array( $u->ID ) ) ) {
		return _hub_account_result( false, 'We&rsquo;ve sent several links already. Please check your spam folder, or try again in ' . hub_account_rule_wait( 'confirm' ) . '.', array(), hub_url( 'account/?email=wait' ), $mode );
	}
	hub_account_count_attempt( 'confirm', array( $u->ID ) );
	_hub_account_send_confirmation( $u );
	return _hub_account_result( true, 'We&rsquo;ve sent a new link to ' . $u->user_email . '.', array(), hub_url( 'account/?email=sent' ), $mode );
}

/** ---------------------------------------------------------------- internal */
function _hub_account_send_confirmation( WP_User $u ) {
	$link = add_query_arg( array( 'u' => $u->ID, 'key' => hub_account_db_new_confirm_key( $u->ID ) ), hub_url( 'confirm-email/' ) );
	$mail = hub_account_shape_confirm_email( (string) $u->first_name, $link );
	$sent = hub_account_db_send_email( $u->user_email, $mail['subject'], $mail['body'] );
	if ( ! $sent ) {
		hub_debug_log( 'account: could not send the confirmation email to user ' . $u->ID );
	}
	return $sent;
}

function _hub_account_reset_user( array $params ) {
	$key   = isset( $params['key'] ) && is_scalar( $params['key'] ) ? (string) wp_unslash( $params['key'] ) : '';
	$login = isset( $params['login'] ) && is_scalar( $params['login'] ) ? (string) wp_unslash( $params['login'] ) : '';
	if ( '' === $key || '' === $login ) {
		return new WP_Error( 'invalid_key' );
	}
	return hub_account_db_check_reset_key( $key, $login );
}

function _hub_account_reset_link_message( WP_Error $e ) {
	return 'expired_key' === $e->get_error_code()
		? 'This reset link has expired. Links last an hour; ask for a new one below.'
		: 'This reset link isn&rsquo;t valid. It may have been used already. Ask for a new one below.';
}
function _hub_account_for_user( WP_User $u ) {
	$biz_id   = hub_account_db_business_id( $u->ID );
	$business = null;
	if ( $biz_id ) {
		$found    = hub_businesses_summaries( array( $biz_id ) );
		$business = isset( $found['items'][ $biz_id ] ) ? $found['items'][ $biz_id ] : null;
	}
	return hub_account_shape( $u, hub_account_db_type( $u ), $business, hub_account_db_email_confirmed( $u->ID ) );
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
