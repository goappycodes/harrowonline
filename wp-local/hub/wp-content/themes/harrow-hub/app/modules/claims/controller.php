<?php
/**
 * Claims: what pages, wp-admin and the API call. Other modules used: businesses (the listing, marking it claimed),
 * account (who is signed in, linking them to the business, attempt limits).
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Everything the claim page needs for ?listing={slug}, and whether this account can claim it.
 *   { state, message, business (summary) | null, domain, call_number, account, claim (this account's pending claim) | null }
 * state: ready | unconfirmed | not_found | mine | has_business | claimed | pending_other
 */
function hub_claim_page( array $params, $mode = HUB_WEB ) {
	$account = hub_account_current();
	$slug    = hub_param_slug( $params, 'listing' );
	$found   = '' !== $slug ? hub_business_get( $slug ) : null;
	$biz     = $found ? $found['business'] : null;
	$pending = $account['signed_in'] ? _hub_claims_pending( $account['id'] ) : null;
	$out     = array(
		'state'       => 'ready',
		'message'     => '',
		'business'    => $biz,
		'domain'      => $biz ? hub_claims_rule_domain( $biz['website'] ) : '',
		'call_number' => $biz && $biz['phones'] ? $biz['phones'][0]['display'] : '',
		'account'     => $account,
		'claim'       => null,
	);
	if ( ! $biz ) {
		$out['state']   = 'not_found';
		$out['message'] = 'We couldn&rsquo;t find that business. Search for it, then press Claim on its page.';
	} elseif ( $account['business'] && (int) $account['business']['id'] === (int) $biz['id'] ) {
		$out['state']   = 'mine';
		$out['message'] = 'You already manage this business.';
	} elseif ( $account['business'] ) {
		$out['state']   = 'has_business';
		$out['message'] = 'Your account already manages ' . esc_html( $account['business']['name'] ) . '. Each account manages one business; email hub@harrowonline.org if you run more than one.';
	} elseif ( $biz['claimed'] || hub_account_owner_id( $biz['id'] ) ) {
		$out['state']   = 'claimed';
		$out['message'] = 'Someone already manages this listing. If you think that&rsquo;s wrong, email hub@harrowonline.org with the business name.';
	} elseif ( $pending && (int) $pending['business'] !== (int) $biz['id'] ) {
		$other          = _hub_claims_business( $pending['business'] );
		$out['state']   = 'pending_other';
		$out['message'] = 'You already have a claim waiting for ' . esc_html( $other ? $other['name'] : 'another business' ) . '. We&rsquo;ll email you when it&rsquo;s checked.';
	} elseif ( $account['signed_in'] && ! $account['email_confirmed'] ) {
		$out['state']   = 'unconfirmed';
		$out['message'] = 'Please confirm your email address first: use the link we sent to ' . esc_html( $account['email'] ) . ', or resend it from your account.';
	}
	if ( $pending && $biz && (int) $pending['business'] === (int) $biz['id'] ) {
		$out['claim'] = hub_claim_shape( $pending, $biz, null, false );
	}
	return hub_respond( $out, $mode );
}

/**
 * Submit (or update) a claim from the claim page.
 *
 * @param array $params { listing, method, email_local, ask_for, call_times[], doc_type, role, phone, note, declaration, _hub_nonce (web) }
 * @param array $files  { document: an entry of $_FILES } for the document method
 */
function hub_claim_submit( array $params, array $files, $mode = HUB_WEB ) {
	if ( HUB_WEB === $mode && ! wp_verify_nonce( isset( $params['_hub_nonce'] ) ? (string) $params['_hub_nonce'] : '', 'hub_claim' ) ) {
		return _hub_claim_result( false, 'This form has expired. Please try again.', array(), '', $mode );
	}
	$page = hub_claim_page( $params );
	if ( 'ready' !== $page['state'] ) {
		return _hub_claim_result( false, '' !== $page['message'] ? $page['message'] : 'Sign in to claim a business.', array(), '', $mode );
	}
	$account = $page['account'];
	$biz     = $page['business'];
	if ( hub_account_limited( 'claim', array( $account['id'] ) ) ) {
		return _hub_claim_result( false, 'You&rsquo;ve sent several claims today. Please try again tomorrow, or email hub@harrowonline.org.', array(), '', $mode );
	}
	$get    = function ( $k ) use ( $params ) { return isset( $params[ $k ] ) && is_scalar( $params[ $k ] ) ? trim( wp_unslash( (string) $params[ $k ] ) ) : ''; };
	$method = $get( 'method' );
	$errors = array();
	$d      = array();
	if ( ! isset( HUB_CLAIMS_METHODS[ $method ] ) ) {
		$errors['method'] = 'Choose how we can check it&rsquo;s yours.';
	} elseif ( 'email' === $method ) {
		if ( '' === $page['domain'] ) {
			$errors['method'] = 'This listing has no website of its own to check an email against. Choose another way.';
		} elseif ( ! hub_claims_rule_email_local_ok( $get( 'email_local' ) ) ) {
			$errors['email_local'] = 'Enter the part before the @, for example yourname.';
		} else {
			$d['email'] = strtolower( $get( 'email_local' ) ) . '@' . $page['domain'];
		}
	} elseif ( 'phone' === $method ) {
		if ( '' === $page['call_number'] ) {
			$errors['method'] = 'This listing has no phone number for us to call. Choose another way.';
		} elseif ( '' === $get( 'ask_for' ) ) {
			$errors['ask_for'] = 'Tell us who to ask for.';
		}
		$d['ask_for']     = sanitize_text_field( $get( 'ask_for' ) );
		$d['call_times']  = array_values( array_intersect( isset( $params['call_times'] ) ? array_map( 'strval', (array) $params['call_times'] ) : array(), array_keys( HUB_CLAIMS_CALL_TIMES ) ) );
		$d['call_number'] = $page['call_number'];
	} else {
		$file = isset( $files['document'] ) && is_array( $files['document'] ) ? $files['document'] : null;
		$ext  = '';
		if ( ! $file || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			$errors['document'] = 'Choose the document to upload.';
		} elseif ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$errors['document'] = in_array( (int) $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? 'That file is too big. The limit is 10MB.' : 'The upload didn&rsquo;t work. Please try again.';
		} else {
			$type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], HUB_CLAIMS_FILE_TYPES );
			$ext  = hub_claims_rule_file_ok( $file['name'], (string) $type['type'], (int) $file['size'] );
			if ( '' === $ext ) {
				$errors['document'] = 'Upload a PDF, JPG or PNG of 10MB or less.';
			}
		}
		$d['doc_type'] = isset( HUB_CLAIMS_DOC_TYPES[ $get( 'doc_type' ) ] ) ? $get( 'doc_type' ) : 'utility';
	}
	$role = $get( 'role' );
	if ( ! isset( HUB_CLAIMS_ROLES[ $role ] ) ) {
		$errors['role'] = 'Choose your role.';
	}
	if ( ! hub_param_bool( $params, 'declaration' ) ) {
		$errors['declaration'] = 'Please confirm you own or manage this business.';
	}
	if ( $errors ) {
		return _hub_claim_result( false, 'Please check the highlighted fields.', $errors, '', $mode );
	}

	$claim = array(
		'business'       => (int) $biz['id'],
		'user'           => (int) $account['id'],
		'method'         => $method,
		'details'        => $d,
		'role'           => $role,
		'phone'          => sanitize_text_field( $get( 'phone' ) ),
		'note'           => sanitize_textarea_field( mb_substr( $get( 'note' ), 0, 500 ) ),
		'status'         => 'pending',
		'email_verified' => false,
	);
	$id = $page['claim'] ? (int) $page['claim']['id'] : 0;   // claiming the same business again updates the waiting claim
	if ( $id ) {
		hub_claims_db_save( $id, $claim );
	} else {
		$id = hub_claims_db_create( $claim, $biz['name'] . ' (' . $account['email'] . ')' );
		if ( is_wp_error( $id ) ) {
			return _hub_claim_result( false, 'We couldn&rsquo;t save your claim just now. Please try again.', array(), '', $mode );
		}
	}
	if ( 'document' === $method ) {
		$stored = hub_claims_db_store_document( $id, $file, $ext );
		if ( is_wp_error( $stored ) ) {
			hub_debug_log( 'claims: document not saved for claim ' . $id . ': ' . $stored->get_error_message() );
			return _hub_claim_result( false, 'We couldn&rsquo;t save your document. Please try again, or choose another way.', array( 'document' => 'Please try again.' ), '', $mode );
		}
		$claim['details']['document'] = $stored;
		hub_claims_db_save( $id, $claim );
	}
	hub_account_count_attempt( 'claim', array( $account['id'] ) );
	$u = get_userdata( $account['id'] );
	if ( 'email' === $method ) {
		$link = add_query_arg( array( 'c' => $id, 'key' => hub_claims_db_new_verify_key( $id ) ), hub_url( 'claim/verify/' ) );
		_hub_claims_mail( 'verify', $d['email'], $u, $biz['name'], $link );
	}
	_hub_claims_mail( 'staff', get_option( 'admin_email' ), $u, $biz['name'], admin_url( 'post.php?post=' . $id . '&action=edit' ), '', HUB_CLAIMS_METHODS[ $method ] );
	return _hub_claim_result( true, 'Claim sent.', array(), hub_url( 'claim/submitted/' ), $mode );
}

/** The link in the business-email check: /claim/verify/?c=ID&key=... -> claim/submitted/?verified=1 (or 0). */
function hub_claim_verify_email( array $params, $mode = HUB_WEB ) {
	$id  = hub_param_int( $params, 'c' );
	$key = isset( $params['key'] ) && is_scalar( $params['key'] ) ? (string) wp_unslash( $params['key'] ) : '';
	$c   = $id ? hub_claims_db_get( $id ) : null;
	$ok  = $c && 'email' === $c['method'] && 'pending' === $c['status'] && hub_claims_db_check_verify_key( $id, $key, HUB_CLAIMS_VERIFY_DAYS * DAY_IN_SECONDS );
	if ( $ok ) {
		$c['email_verified'] = true;
		$c['verified_at']    = time();
		hub_claims_db_save( $id, array_diff_key( $c, array( 'id' => 1, 'submitted' => 1 ) ) );
		delete_post_meta( $id, '_hub_claim_verify' );
	}
	return _hub_claim_result( $ok, $ok ? 'Email confirmed.' : 'That link isn&rsquo;t valid or has expired.', array(), hub_url( 'claim/submitted/?verified=' . ( $ok ? '1' : '0' ) ), $mode );
}

/**
 * The signed-in account's latest claim (claim shape), or null when there's none: for the claim-received page and the
 * dashboard. The API answers { claim: ... | null }.
 */
function hub_claim_mine( $mode = HUB_WEB ) {
	$account = hub_account_current();
	$ids     = $account['signed_in'] ? hub_claims_db_find( array( 'user' => $account['id'] ), 1 ) : array();
	$c       = $ids ? hub_claims_db_get( $ids[0] ) : null;
	$claim   = $c ? hub_claim_shape( $c, _hub_claims_business( $c['business'] ), get_userdata( $c['user'] ), false ) : null;
	if ( HUB_API === $mode ) {
		return hub_respond( array( 'claim' => $claim ), $mode );
	}
	return $claim ? hub_respond( $claim, $mode ) : null;
}

/** A claim with its evidence, for staff (wp-admin). */
function hub_claim_for_staff( $id, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return hub_respond_error( 'forbidden', 'Staff only.', $mode, 403 );
	}
	$c = hub_claims_db_get( $id );
	return $c ? hub_respond( hub_claim_shape( $c, _hub_claims_business( $c['business'] ), get_userdata( $c['user'] ), true ), $mode ) : hub_respond_error( 'not_found', 'No such claim.', $mode );
}

/**
 * Approve a claim (staff): the business becomes claimed (Basic plan), the account manages it, and they get an email.
 * Returns { ok, message }.
 */
function hub_claim_approve( $id, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return hub_respond_error( 'forbidden', 'Staff only.', $mode, 403 );
	}
	$c = hub_claims_db_get( $id );
	if ( ! $c || 'pending' !== $c['status'] ) {
		return _hub_claim_result( false, 'Only a claim that&rsquo;s waiting can be approved.', array(), '', $mode );
	}
	$owner = hub_account_owner_id( $c['business'] );
	if ( $owner && (int) $owner !== (int) $c['user'] ) {
		return _hub_claim_result( false, 'Another account already manages this business. Unlink it in Users first.', array(), '', $mode );
	}
	$has = hub_account_business_id( $c['user'] );
	if ( $has && (int) $has !== (int) $c['business'] ) {
		return _hub_claim_result( false, 'This account already manages another business (one per account).', array(), '', $mode );
	}
	hub_business_set_claimed( $c['business'], true );       // businesses module
	hub_account_link_business( $c['user'], $c['business'] );   // account module: also sets the account's plan
	_hub_claims_decide( $c, 'approved', '' );
	$biz = _hub_claims_business( $c['business'] );
	_hub_claims_mail( 'approved', null, get_userdata( $c['user'] ), $biz ? $biz['name'] : '', hub_url( 'account/listings/edit/' ) );
	return _hub_claim_result( true, 'Claim approved: the account now manages the business.', array(), '', $mode );
}

/** Reject a claim (staff), with a note the person sees in the email. Returns { ok, message }. */
function hub_claim_reject( $id, $note, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return hub_respond_error( 'forbidden', 'Staff only.', $mode, 403 );
	}
	$c = hub_claims_db_get( $id );
	if ( ! $c || 'pending' !== $c['status'] ) {
		return _hub_claim_result( false, 'Only a claim that&rsquo;s waiting can be rejected.', array(), '', $mode );
	}
	$note = sanitize_textarea_field( (string) $note );
	_hub_claims_decide( $c, 'rejected', $note );
	$biz = _hub_claims_business( $c['business'] );
	_hub_claims_mail( 'rejected', null, get_userdata( $c['user'] ), $biz ? $biz['name'] : '', $biz ? hub_url( 'claim/?listing=' . get_post_field( 'post_name', $biz['id'] ) ) : hub_url( 'search/' ), $note );
	return _hub_claim_result( true, 'Claim rejected and the person emailed.', array(), '', $mode );
}

/** A claim's uploaded document for staff to download: { path, name, type } or null. */
function hub_claim_document( $id ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return null;
	}
	$c    = hub_claims_db_get( $id );
	$name = $c && ! empty( $c['details']['document'] ) ? $c['details']['document'] : '';
	$path = hub_claims_db_document_path( $name );
	if ( '' === $path ) {
		return null;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	return array( 'path' => $path, 'name' => 'claim-' . (int) $id . '.' . $ext, 'type' => HUB_CLAIMS_FILE_TYPES[ $ext ] );
}

/* ------------------------------------------------------------------ internal */
function _hub_claims_pending( $user_id ) {
	$ids = hub_claims_db_find( array( 'user' => (int) $user_id, 'status' => 'pending' ), 1 );
	return $ids ? hub_claims_db_get( $ids[0] ) : null;
}

function _hub_claims_business( $id ) {
	$found = hub_businesses_summaries( array( (int) $id ) );
	return isset( $found['items'][ (int) $id ] ) ? $found['items'][ (int) $id ] : null;
}

function _hub_claims_decide( array $c, $status, $note ) {
	$id                   = $c['id'];
	$c['status']          = $status;
	$c['decision_note']   = $note;
	$c['decided_by']      = get_current_user_id();
	$c['decided_at']      = time();
	hub_claims_db_save( $id, array_diff_key( $c, array( 'id' => 1, 'submitted' => 1 ) ) );
}

function _hub_claims_mail( $kind, $to, $user, $business, $link, $note = '', $method_label = '' ) {
	if ( ! $user ) {
		return false;
	}
	$mail = hub_claims_shape_email( $kind, array(
		'first_name'    => (string) $user->first_name,
		'business'      => html_entity_decode( $business, ENT_QUOTES, 'UTF-8' ),
		'link'          => $link,
		'note'          => $note,
		'method_label'  => $method_label,
		'account_email' => $user->user_email,
	) );
	$sent = hub_claims_db_send_email( $to ? $to : $user->user_email, $mail['subject'], $mail['body'] );
	if ( ! $sent ) {
		hub_debug_log( 'claims: could not send the ' . $kind . ' email' );
	}
	return $sent;
}

function _hub_claim_result( $ok, $message, array $errors, $redirect, $mode ) {
	return hub_respond( array( 'ok' => (bool) $ok, 'message' => $message, 'errors' => array_filter( $errors ), 'redirect' => $redirect ), $mode, $ok ? 200 : 400 );
}
