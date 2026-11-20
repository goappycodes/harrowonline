<?php
/**
 * Submissions: offers, events and weekly classes added from the account pages. Each is saved as a draft or as
 * "Pending Review"; staff publish it in wp-admin. Other modules used: account (who is signed in, their plan),
 * offers and events (saving the posts), areas (area names), billing (a Basic business's £15 event).
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * What an add/edit page needs:
 *   { kind, state, message, access ('yes' | 'pay' | 'upgrade' | 'no'), account, business (summary) | null, by (business
 *     or organiser name), venue_home ("Name, street, postcode") | '', item { id, status, status_label, url } | null, form }
 * state: ready | unconfirmed | not_allowed | upgrade | locked | not_found
 *
 * @param string $kind   'offer' | 'event' | 'class'
 * @param array  $params { id: one of their own to edit }
 */
function hub_submission_page( $kind, array $params, $mode = HUB_WEB ) {
	$account = hub_account_current();   // account module
	$biz     = $account['business'];
	$type    = $account['type'];
	$access  = hub_submissions_rule_access( $kind, $type, (bool) $biz, $account['paid'] );
	$profile = hub_account_profile( $account['id'] );
	$out     = array(
		'kind'       => $kind,
		'state'      => 'ready',
		'message'    => '',
		'access'     => $access,
		'account'    => $account,
		'business'   => $biz,
		'by'         => $biz ? html_entity_decode( $biz['name'], ENT_QUOTES, 'UTF-8' ) : ( '' !== $profile['organisation'] ? $profile['organisation'] : $account['name'] ),
		'venue_home' => $biz ? trim( html_entity_decode( $biz['name'], ENT_QUOTES, 'UTF-8' ) . ', ' . trim( $biz['address']['street'] . ', ' . $biz['address']['postcode'], ', ' ), ', ' ) : '',
		'item'       => null,
		'form'       => array(),
	);
	$id = hub_param_int( $params, 'id' );
	if ( $id ) {
		$item = _hub_submission_item( $kind, $id );
		$info = $item ? hub_submissions_db_info( $id ) : null;
		if ( ! $item || ! $info || (int) $info['user'] !== (int) $account['id'] || 'trash' === $item['status'] ) {
			return hub_respond( array( 'state' => 'not_found', 'message' => 'We couldn&rsquo;t find that ' . $kind . ' in your account.' ) + $out, $mode );
		}
		$out['item'] = array( 'id' => $item['id'], 'status' => $item['status'], 'status_label' => HUB_SUBMISSIONS_STATUS_LABELS[ $item['status'] ], 'url' => $item['url'], 'needs_pay' => $info['needs_pay'], 'paid' => $info['paid'] );
		$out['form'] = $item['form'] ? $item['form'] : array();
		if ( 'publish' === $item['status'] ) {
			$out['state']   = 'locked';
			$out['message'] = 'This ' . $kind . ' is live. To change it, email hub@harrowonline.org and we&rsquo;ll update it for you.';
			return hub_respond( $out, $mode );
		}
	}
	if ( ! $account['email_confirmed'] ) {
		$out['state']   = 'unconfirmed';
		$out['message'] = 'Please confirm your email address first: use the link we sent to ' . esc_html( $account['email'] ) . ', or resend it from your account.';
	} elseif ( 'upgrade' === $access ) {
		$out['state']   = 'upgrade';
		$out['message'] = ( 'offer' === $kind ? 'Local Offers come' : 'Weekly classes come' ) . ' with the Featured and Local Partner plans.';
	} elseif ( 'no' === $access ) {
		$out['state']   = 'not_allowed';
		$out['message'] = 'offer' === $kind && 'organiser' === $type
			? 'Local Offers are for businesses. As an organiser you can add events and weekly classes.'
			: 'Claim or add your business first, then you can add ' . ( 'offer' === $kind ? 'offers' : $kind . 'es' ) . '.';
		if ( 'event' === $kind ) {
			$out['message'] = 'Claim or add your business first, then you can add events.';
		}
	}
	return hub_respond( $out, $mode );
}

/**
 * Save an offer, event or class from its page: "Save draft" keeps it a draft, "Send for approval" sends it to
 * Pending Review. A Basic business's event then goes to the £15 payment.
 *
 * @param array $params the form ({ action: draft | submit, id, ...fields }, _hub_nonce on the web)
 * @param array $files  { image } for events and classes (optional)
 * @return array { ok, message, errors, redirect }
 */
function hub_submission_save( $kind, array $params, array $files, $mode = HUB_WEB ) {
	$res = function ( $ok, $msg, $errors = array(), $redirect = '' ) use ( $mode ) {
		return hub_respond( array( 'ok' => $ok, 'message' => $msg, 'errors' => array_filter( $errors ), 'redirect' => $redirect ), $mode, $ok ? 200 : 400 );
	};
	if ( ! isset( HUB_SUBMISSIONS_KINDS[ $kind ] ) ) {
		return $res( false, 'Unknown kind.' );
	}
	if ( HUB_WEB === $mode && ! wp_verify_nonce( isset( $params['_hub_nonce'] ) ? (string) $params['_hub_nonce'] : '', 'hub_submission' ) ) {
		return $res( false, 'This form has expired. Please try again.' );
	}
	$page = hub_submission_page( $kind, $params );
	if ( ! $page['account']['signed_in'] ) {
		return $res( false, 'Sign in first.', array(), hub_url( 'sign-in/' ) );
	}
	if ( 'ready' !== $page['state'] ) {
		return $res( false, $page['message'] );
	}
	$status = isset( $params['action'] ) && 'draft' === $params['action'] ? 'draft' : 'pending';
	$f      = _hub_submission_fields( $kind, $params, $page, $errors );
	$photo  = 'offer' !== $kind && isset( $files['image'] ) && is_array( $files['image'] ) && UPLOAD_ERR_NO_FILE !== (int) $files['image']['error'] ? $files['image'] : null;
	if ( $photo ) {
		$t = UPLOAD_ERR_OK === (int) $photo['error'] ? wp_check_filetype_and_ext( $photo['tmp_name'], $photo['name'], HUB_SUBMISSIONS_PHOTO_TYPES ) : array( 'type' => '' );
		if ( UPLOAD_ERR_OK !== (int) $photo['error'] || ! in_array( $t['type'], HUB_SUBMISSIONS_PHOTO_TYPES, true ) || (int) $photo['size'] > HUB_SUBMISSIONS_PHOTO_MAX ) {
			$errors['image'] = 'Choose a JPG or PNG photo of 5MB or less, or leave it out.';
		}
	}
	if ( $errors ) {
		return $res( false, 'Please check the highlighted fields.', $errors );
	}

	$item     = $page['item'];
	$id       = $item ? (int) $item['id'] : 0;
	$was      = $item ? $item['status'] : '';
	$f['image'] = isset( $page['form']['image'] ) ? (string) $page['form']['image'] : '';   // keep the photo they sent before
	$saved    = _hub_submission_store( $kind, $id, $status, $f, $page );
	if ( is_wp_error( $saved ) ) {
		hub_debug_log( 'submissions: ' . $kind . ' not saved: ' . $saved->get_error_message() );
		return $res( false, 'We couldn&rsquo;t save it just now. Please try again.' );
	}
	if ( $photo ) {
		$url = hub_submissions_db_photo( $saved, 'image' );
		if ( is_wp_error( $url ) ) {
			hub_debug_log( 'submissions: photo not saved for ' . $saved . ': ' . $url->get_error_message() );
		} else {
			$f['image'] = $url;
			_hub_submission_store( $kind, $saved, $status, $f, $page );
		}
	}
	$needs_pay = 'pay' === $page['access'];
	hub_submissions_db_mark( $saved, $page['account']['id'], $needs_pay );

	$edit = add_query_arg( array( 'id' => $saved, 'saved' => $status ), hub_url( HUB_SUBMISSIONS_KINDS[ $kind ][2] . 'edit/' ) );
	if ( 'pending' !== $status ) {
		return $res( true, 'Draft saved.', array(), $edit );
	}
	if ( 'pending' !== $was ) {
		$u = get_userdata( $page['account']['id'] );
		_hub_submission_mail( 'staff', get_option( 'admin_email' ), $u, $kind, $f['title'], $page['by'], admin_url( 'post.php?post=' . $saved . '&action=edit' ), $needs_pay ? 'Basic plan: ' . html_entity_decode( hub_billing_rule_money( hub_billing_items()['event_listing']['amount'] ) ) . ' event listing, not paid yet' : '' );
	}
	// A Basic business pays £15 for its event; with online payments off, staff see it's unpaid and get in touch
	if ( $needs_pay && ! hub_submissions_db_info( $saved )['paid'] && hub_billing_status()['configured'] ) {   // billing module
		return $res( true, 'Sent for checking. Next, pay for the listing.', array(), add_query_arg( array( 'item' => 'event_listing', 'ref' => $saved, 'return' => rawurlencode( $edit ) ), hub_url( 'billing/checkout/' ) ) );
	}
	return $res( true, 'Sent for checking.', array(), $edit );
}

/** The form result for this page view (set when a form was sent and not redirected), or null. */
function hub_submission_result( $set = null ) {
	static $result = null;
	if ( null !== $set ) {
		$result = $set;
	}
	return $result;
}

/** A £15 event listing was paid (billing webhook, hub_billing_item_paid): mark that event paid. */
function hub_submission_paid( $item, $ref, $user_id, $business_id ) {
	$info = 'event_listing' === $item ? hub_submissions_db_info( (int) $ref ) : null;
	if ( $info && (int) $info['user'] === (int) $user_id && 'hub_event' === hub_submissions_db_post_type( (int) $ref ) ) {
		hub_submissions_db_set_paid( (int) $ref );
	}
}

/** Who sent this post, for staff screens: { email, by, needs_pay, paid } or null. */
function hub_submission_info( $post_id ) {
	$i = hub_submissions_db_info( $post_id );
	if ( ! $i ) {
		return null;
	}
	$u = get_userdata( $i['user'] );
	$a = $u ? hub_account_for_user( $u->ID ) : null;
	return $i + array( 'email' => $u ? $u->user_email : '(account deleted)', 'by' => $a && $a['business'] ? html_entity_decode( $a['business']['name'], ENT_QUOTES, 'UTF-8' ) : ( $a ? $a['name'] : '' ) );
}

/** Staff published one (Pending Review -> Published): tell the person, if they want approval emails. */
function hub_submission_published( $post_id ) {
	_hub_submission_decided( $post_id, 'live', 'approved' );
}

/** Staff moved one waiting for review to the Trash: tell the person, if they want "changes needed" emails. */
function hub_submission_declined( $post_id ) {
	_hub_submission_decided( $post_id, 'declined', 'rejected' );
}

/**
 * Staff decline one waiting for review, with a note for the person (wp-admin): it goes to the Trash and the
 * "declined" email carries the note. Returns { ok, message }.
 */
function hub_submission_decline( $post_id, $note, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'delete_post', (int) $post_id ) ) {
		return hub_respond_error( 'forbidden', 'You can&rsquo;t decline this.', $mode, 403 );
	}
	if ( '' === _hub_submission_kind_of( $post_id ) || 'pending' !== get_post_status( $post_id ) ) {
		return hub_respond( array( 'ok' => false, 'message' => 'Only one waiting for review can be declined.' ), $mode );
	}
	hub_submissions_db_set_decline_note( $post_id, sanitize_textarea_field( (string) $note ) );
	$ok = hub_submissions_db_trash( $post_id );   // -> hub_submission_declined() at the end of the request
	return hub_respond( array( 'ok' => $ok, 'message' => $ok ? 'Declined and moved to the Trash. They&rsquo;ll get an email with your note.' : 'Couldn&rsquo;t decline it.' ), $mode );
}

/* ------------------------------------------------------------------ internal */
function _hub_submission_kind_of( $post_id ) {
	foreach ( HUB_SUBMISSIONS_KINDS as $kind => $k ) {
		if ( $k[0] === hub_submissions_db_post_type( $post_id ) ) {
			return $kind;
		}
	}
	return '';
}

function _hub_submission_item( $kind, $id ) {
	return 'offer' === $kind ? hub_offer_submission( $id ) : hub_events_submission( HUB_SUBMISSIONS_KINDS[ $kind ][0], $id );   // offers / events modules
}

function _hub_submission_store( $kind, $id, $status, array $f, array $page ) {
	if ( 'offer' === $kind ) {
		return hub_offer_save_submission( $id, $status, $f, array( 'id' => $page['business']['id'], 'slug' => $page['business']['slug'] ) );
	}
	return 'event' === $kind ? hub_event_save_submission( $id, $status, $f ) : hub_class_save_submission( $id, $status, $f );
}

function _hub_submission_decided( $post_id, $mail, $pref ) {
	$i    = hub_submissions_db_info( $post_id );
	$kind = _hub_submission_kind_of( $post_id );
	$u    = $i ? get_userdata( $i['user'] ) : null;
	if ( ! $u || '' === $kind || ! hub_account_wants_email( $u->ID, $pref ) ) {
		return;
	}
	$post = get_post( $post_id );
	$link = 'live' === $mail ? ( 'offer' === $kind ? hub_url( 'offers/' . $post->post_name . '/' ) : hub_url( 'events/' . $post->post_name . '/' ) ) : hub_url( HUB_SUBMISSIONS_KINDS[ $kind ][2] . 'edit/' );
	_hub_submission_mail( $mail, $u->user_email, $u, $kind, html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ), '', $link, 'declined' === $mail ? hub_submissions_db_decline_note( $post_id ) : '' );
}

function _hub_submission_mail( $kind, $to, WP_User $u, $what, $title, $by, $link, $note = '' ) {
	$mail = hub_submissions_shape_email( $kind, array(
		'first_name'    => (string) $u->first_name,
		'what'          => $what,
		'title'         => $title,
		'by'            => $by,
		'link'          => $link,
		'account_email' => $u->user_email,
		'note'          => $note,
	) );
	if ( ! hub_submissions_db_send_email( $to, $mail['subject'], $mail['body'] ) ) {
		hub_debug_log( 'submissions: could not send the ' . $kind . ' email' );
	}
}

/**
 * Check a form and turn it into the values the offers / events modules save (the raw choices are kept too, so the
 * form can be filled in again). Fills $errors (field => message).
 */
function _hub_submission_fields( $kind, array $p, array $page, &$errors ) {
	$errors = array();
	$get    = function ( $k ) use ( $p ) { return isset( $p[ $k ] ) && is_scalar( $p[ $k ] ) ? trim( wp_unslash( (string) $p[ $k ] ) ) : ''; };
	$today  = hub_today()->format( 'Y-m-d' );
	$f      = array();

	$max         = 'offer' === $kind ? 70 : 80;
	$f['title']  = sanitize_text_field( $get( 'title' ) );
	if ( '' === $f['title'] || mb_strlen( $f['title'] ) > $max ) {
		$errors['title'] = 'offer' === $kind ? 'Write a headline of up to 70 characters.' : 'Give it a name of up to 80 characters.';
	}
	$f['description'] = sanitize_textarea_field( $get( 'description' ) );
	if ( 'offer' === $kind && mb_strlen( $f['description'] ) > 140 ) {
		$errors['description'] = 'Keep it to 140 characters.';
	} elseif ( 'offer' !== $kind && ( '' === $f['description'] || mb_strlen( $f['description'] ) > 600 ) ) {
		$errors['description'] = 'Describe it in up to 600 characters.';
	}

	if ( 'offer' === $kind ) {
		$f['start'] = $get( 'start' );
		$f['end']   = $get( 'end' );
		if ( ! hub_submissions_rule_is_date( $f['start'] ) ) {
			$errors['start'] = 'Choose the first day.';
		}
		if ( ! hub_submissions_rule_is_date( $f['end'] ) || $f['end'] < $today ) {
			$errors['end'] = 'Choose a last day that hasn&rsquo;t passed.';
		} elseif ( hub_submissions_rule_is_date( $f['start'] ) && $f['end'] < $f['start'] ) {
			$errors['end'] = 'The last day can&rsquo;t be before the first.';
		}
		$f['ends_label'] = hub_submissions_rule_is_date( $f['end'] ) ? DateTimeImmutable::createFromFormat( '!Y-m-d', $f['end'] )->format( 'j F Y' ) : '';
		$f['code']       = strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', $get( 'code' ) ) );
		if ( strlen( $f['code'] ) > 20 ) {
			$errors['code'] = 'Keep the code to 20 letters and numbers.';
		}
		$f['redeem'] = sanitize_text_field( $get( 'redeem' ) );
		if ( '' === $f['redeem'] || mb_strlen( $f['redeem'] ) > 100 ) {
			$errors['redeem'] = 'Say how people claim it, in up to 100 characters.';
		}
		$f['terms_text'] = sanitize_textarea_field( $get( 'terms' ) );
		$f['terms']      = hub_submissions_rule_lines( $f['terms_text'] );
		if ( ! $f['terms'] || mb_strlen( $f['terms_text'] ) > 500 ) {
			$errors['terms'] = 'Add the terms, one per line (up to 500 characters).';
		}
		$f['audience'] = 'subscribers' === $get( 'audience' ) ? 'subscribers' : 'everyone';
		return $f;
	}

	// Events and classes: where, who for, price, link
	$biz             = $page['business'];
	$f['at_listing'] = $biz && '0' !== $get( 'venue_is_listing' );
	$f['venue_name'] = sanitize_text_field( $get( 'venue' ) );
	$f['venue_postcode'] = hub_submissions_rule_postcode( $get( 'venue_postcode' ) );
	if ( $f['at_listing'] ) {
		$f['venue']    = html_entity_decode( $biz['name'], ENT_QUOTES, 'UTF-8' );
		$f['street']   = $biz['address']['street'];
		$f['postcode'] = $biz['address']['postcode'];
		$f['area']     = $biz['area']['name'];
	} else {
		if ( '' === $f['venue_name'] ) {
			$errors['venue'] = 'Enter the venue&rsquo;s name.';
		}
		if ( '' === $f['venue_postcode'] ) {
			$errors['venue_postcode'] = 'Enter the venue&rsquo;s postcode, for example HA1 2TA.';
		}
		$f['venue']    = $f['venue_name'];
		$f['street']   = $f['venue_name'];
		$f['postcode'] = $f['venue_postcode'];
		$f['area']     = $biz ? $biz['area']['name'] : _hub_submission_area_name( hub_account_profile( $page['account']['id'] )['area'] );
	}
	$f['age'] = in_array( $get( 'age' ), HUB_SUBMISSIONS_AGES, true ) ? $get( 'age' ) : 'All ages';
	$link     = $get( 'link' );
	$f['link'] = sanitize_text_field( $link );
	if ( 'event' === $kind && '' !== $link && ! filter_var( $link, FILTER_VALIDATE_URL ) ) {
		$errors['link'] = 'Enter the full link, starting https://';
	}
	$f['org'] = $page['by'];

	if ( 'event' === $kind ) {
		$f['category'] = $get( 'category' );
		if ( ! isset( HUB_SUBMISSIONS_EVENT_CATS[ $f['category'] ] ) ) {
			$errors['category'] = 'Choose a category.';
		} else {
			list( $f['cat'], $f['icon'] ) = HUB_SUBMISSIONS_EVENT_CATS[ $f['category'] ];
		}
		$f['date']  = $get( 'start_date' );
		$f['start'] = $get( 'start_time' );
		$f['end']   = $get( 'end_time' );
		if ( ! hub_submissions_rule_is_date( $f['date'] ) || $f['date'] < $today ) {
			$errors['start_date'] = 'Choose a date that hasn&rsquo;t passed.';
		}
		if ( ! hub_submissions_rule_is_time( $f['start'] ) ) {
			$errors['start_time'] = 'Choose a start time.';
		}
		if ( '' !== $f['end'] && ( ! hub_submissions_rule_is_time( $f['end'] ) || $f['end'] <= $f['start'] ) ) {
			$errors['end_time'] = 'The end time must be after the start, or leave it empty.';
		}
		$f['time_label'] = hub_submissions_rule_time_label( $f['start'] );
		$price           = sanitize_text_field( $get( 'price' ) );
		$f['price_text'] = $price;
		$f['price']      = '' === $price ? 'Free' : ( preg_match( '/^\d+(\.\d{1,2})?$/', $price ) ? '£' . $price : $price );
		return $f;
	}

	// Weekly class
	$f['category'] = $get( 'category' );
	if ( ! isset( HUB_SUBMISSIONS_CLASS_TYPES[ $f['category'] ] ) ) {
		$errors['category'] = 'Choose the type of class.';
	} else {
		list( $f['type'], $f['icon'] ) = HUB_SUBMISSIONS_CLASS_TYPES[ $f['category'] ];
	}
	$slot       = isset( $p['slots'][0] ) && is_array( $p['slots'][0] ) ? $p['slots'][0] : array();
	$day        = isset( $slot['day'] ) ? (string) $slot['day'] : '';
	$f['day']   = ctype_digit( $day ) && (int) $day < 7 ? (int) $day : array_search( $day, HUB_SUBMISSIONS_DAYS, true );
	$f['start'] = isset( $slot['start'] ) ? (string) $slot['start'] : '';
	$f['end']   = isset( $slot['end'] ) ? (string) $slot['end'] : '';
	if ( false === $f['day'] ) {
		$errors['day'] = 'Choose the day.';
		$f['day'] = 0;
	}
	if ( ! hub_submissions_rule_is_time( $f['start'] ) || ! hub_submissions_rule_is_time( $f['end'] ) || $f['end'] <= $f['start'] ) {
		$errors['time'] = 'Choose a start time and a later finish time.';
	}
	$f['time_label'] = hub_submissions_rule_time_label( $f['start'] ) . ' to ' . hub_submissions_rule_time_label( $f['end'] );
	$f['sort']       = hub_submissions_rule_is_time( $f['start'] ) ? (int) substr( $f['start'], 0, 2 ) + (int) substr( $f['start'], 3, 2 ) / 60 : 0;
	$f['term']       = 'term' === $get( 'term_type' ) ? 'term' : 'ongoing';   // not "term": WordPress reads a posted "term" as a taxonomy query
	$f['term_start'] = $get( 'term_start' );
	$f['term_end']   = $get( 'term_end' );
	$f['term_weeks'] = min( 52, max( 0, (int) $get( 'term_weeks' ) ) );
	$f['no_half_term'] = hub_param_bool( $p, 'no_half_term' );
	if ( 'term' === $f['term'] && ! ( hub_submissions_rule_is_date( $f['term_start'] ) && ( hub_submissions_rule_is_date( $f['term_end'] ) || $f['term_weeks'] ) ) ) {
		$errors['term'] = 'Give the term&rsquo;s start, and its end or number of weeks.';
	}
	$f['level']      = in_array( $get( 'level' ), HUB_SUBMISSIONS_LEVELS, true ) ? $get( 'level' ) : 'All levels';
	$f['price_text'] = sanitize_text_field( $get( 'price' ) );
	$f['price_per']  = isset( HUB_SUBMISSIONS_PRICE_PER[ $get( 'price_per' ) ] ) ? $get( 'price_per' ) : 'session';
	$f['free_trial'] = hub_param_bool( $p, 'free_trial' );
	$f['price']      = hub_submissions_rule_class_price( $f['price_text'], $f['price_per'], $f['free_trial'] );
	$f['booking']    = 'book' === $get( 'booking' ) ? 'book' : 'turn-up';
	if ( 'book' === $f['booking'] && '' === $f['link'] ) {
		$errors['link'] = 'Add a booking link or phone number.';
	}
	$f['provider']    = $page['by'];
	$f['business_id'] = $biz ? (int) $biz['id'] : 0;
	return $f;
}

function _hub_submission_area_name( $slug ) {
	foreach ( hub_areas_list()['items'] as $a ) {   // areas module
		if ( $a['slug'] === $slug ) {
			return $a['name'];
		}
	}
	return '';
}
