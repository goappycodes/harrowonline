<?php
/**
 * Businesses: what pages and the API call. Nothing outside this module calls anything else in it.
 *
 *   hub_businesses_list( $params )            directory: businesses in a category and/or area, in directory order
 *   hub_business_get( $id_or_slug )           a business page: profile, offers, events, related businesses
 *   hub_businesses_summaries( $ids )          several businesses by ID (offers, classes and events use this)
 *   hub_business_find_by_name( $name )        the claimed business with this name (event organisers, class providers)
 *   hub_businesses_featured( $limit )         homepage "Featured local businesses"
 *   hub_businesses_newest( $limit )           homepage "Just joined"
 *   hub_businesses_each( $fn )                every business, a batch at a time (the search index)
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Businesses in directory order: Verified by the business, Checked by Harrow Online, then imported from public data;
 * the plan within each (Featured and Local Partner first); then the imported order, new listings after it.
 *
 * @param array $params {
 *     cat     category slug
 *     area    area slug
 *     exclude business ID to leave out
 *     limit   how many (default 20, at most 100)
 *     offset  how many to skip
 * }
 * @return array|WP_REST_Response {
 *     items:           business[] each with counts { offers, upcoming } (claimed businesses only; 0 otherwise)
 *     total:           int  businesses matching (before limit and offset)
 *     indexable_total: int  how many of those have a profile search engines may index
 *     filters:         { cat, area }
 * }
 */
function hub_businesses_list( array $params = array(), $mode = HUB_WEB ) {
	$filters = array( 'cat' => hub_param_slug( $params, 'cat' ), 'area' => hub_param_slug( $params, 'area' ) );
	$found   = hub_businesses_db_query( $filters + array(
		'exclude' => hub_param_int( $params, 'exclude', 0 ),
		'limit'   => hub_param_int( $params, 'limit', HUB_BUSINESSES_PAGE_SIZE, 1, 100 ),
		'offset'  => hub_param_int( $params, 'offset', 0 ),
		'by_rank' => true,
	) );

	$indexable = 0;
	foreach ( hub_businesses_db_query( $filters + array( 'claimed_only' => true ) )['posts'] as $post ) {
		if ( _hub_business_build( $post, true )['indexable'] ) {
			$indexable++;
		}
	}

	return hub_respond( array(
		'items'           => array_map( '_hub_businesses_with_counts', $found['posts'] ),
		'total'           => $found['total'],
		'indexable_total' => $indexable,
		'filters'         => $filters,
	), $mode );
}

/**
 * Every published business in the business shape, in directory order (as hub_businesses_list()), handed to $each a
 * batch at a time, so the whole directory (thousands) never sits in memory at once. For the search index.
 *
 * @param callable $each Called with each batch: business[].
 * @return int How many businesses there were.
 */
function hub_businesses_each( callable $each, $batch = 250 ) {
	$ids = hub_businesses_db_query( array( 'by_rank' => true, 'ids_only' => true ) )['posts'];
	foreach ( array_chunk( $ids, max( 1, (int) $batch ) ) as $chunk ) {
		$each( array_map( '_hub_business_build', hub_businesses_db_posts( $chunk ) ) );
		hub_businesses_db_forget( $chunk );
	}
	return count( $ids );
}

/**
 * A business page.
 *
 * @param int|string $id_or_slug Post ID or slug.
 * @return array|WP_REST_Response|null|WP_Error {
 *     business: profile (see shape.php)
 *     offers:   offer[]  its live offers, when its plan shows offers (offers module)
 *     events:   event row[]  its upcoming events, then its weekly classes
 *     related:  business[] (with counts)  up to 4 more in the same category and area
 * } or null (web) / 404 (API)
 */
function hub_business_get( $id_or_slug, $mode = HUB_WEB ) {
	$post = hub_businesses_db_get( hub_param_id_or_slug( $id_or_slug ) );
	if ( ! $post ) {
		return hub_respond_error( 'hub_business_not_found', 'No business with that ID or slug.', $mode );
	}
	$b       = _hub_business_build( $post, true );
	$related = hub_businesses_db_query( array( 'cat' => $b['category']['slug'], 'area' => $b['area']['slug'], 'exclude' => $post->ID, 'limit' => 4 ) );
	return hub_respond( array(
		'business' => $b,
		'offers'   => $b['paid'] ? hub_offers_for_business( $post->ID )['live'] : array(),
		'events'   => _hub_businesses_events( $post ),
		'related'  => array_map( '_hub_businesses_with_counts', $related['posts'] ),
	), $mode );
}

/**
 * Several businesses by post ID (unknown IDs are left out).
 *
 * @param int[] $ids
 * @return array|WP_REST_Response { items: { id: business } }
 */
function hub_businesses_summaries( array $ids, $mode = HUB_WEB ) {
	$items = array();
	foreach ( array_unique( array_map( 'intval', $ids ) ) as $id ) {
		$post = $id ? hub_businesses_db_get( $id ) : null;
		if ( $post ) {
			$items[ $id ] = _hub_business_build( $post );
		}
	}
	return hub_respond( array( 'items' => $items ), $mode );
}

/**
 * The claimed business with this name (its current name or the name it was imported with), or null (web) / 404 (API).
 *
 * @return array|WP_REST_Response|null|WP_Error business
 */
function hub_business_find_by_name( $name, $mode = HUB_WEB ) {
	$by   = _hub_businesses_claimed_by_name();
	$name = hub_plain( $name );
	if ( '' === $name || ! isset( $by[ $name ] ) ) {
		return hub_respond_error( 'hub_business_not_found', 'No claimed business with that name.', $mode );
	}
	return hub_respond( _hub_business_build( $by[ $name ] ), $mode );
}

/**
 * Homepage "Featured local businesses": real businesses on a top plan, one per category.
 *
 * @return array|WP_REST_Response { items: business[] }
 */
function hub_businesses_featured( $limit = 4, $mode = HUB_WEB ) {
	$all = array_map( '_hub_business_build', hub_businesses_db_query( array( 'claimed_only' => true ) )['posts'] );
	return hub_respond( array( 'items' => hub_businesses_rule_pick_featured( $all, max( 1, (int) $limit ) ) ), $mode );
}

/**
 * Homepage "Just joined": the newest claimed businesses.
 *
 * @return array|WP_REST_Response { items: business[] }
 */
function hub_businesses_newest( $limit = 10, $mode = HUB_WEB ) {
	$all = array_map( '_hub_business_build', hub_businesses_db_query( array( 'claimed_only' => true ) )['posts'] );
	return hub_respond( array( 'items' => hub_businesses_rule_newest( $all, max( 1, (int) $limit ) ) ), $mode );
}

/**
 * A claim was approved (claims module) or the owner closed their account: mark the business claimed or unclaimed.
 * Fires hub_business_plan_changed, as the plan may change with it.
 */
function hub_business_set_claimed( $id, $claimed, $mode = HUB_WEB ) {
	$post = hub_businesses_db_get( (int) $id );
	if ( ! $post ) {
		return hub_respond_error( 'not_found', 'No business ' . (int) $id, $mode );
	}
	hub_businesses_db_set_claimed( $post->ID, (bool) $claimed );
	$b = _hub_business_build( hub_businesses_db_get( $post->ID ) );
	do_action( 'hub_business_plan_changed', (int) $post->ID, $b['plan'] );
	return hub_respond( $b, $mode );
}

/* ------------------------------------------------------------------ adding a business (/add-business/) */

/**
 * What the add-business page needs: { recommend, state, message, account, categories[], areas[] }
 * state: ready | unconfirmed | has_business | waiting   (owners only: one business per account)
 *
 * @param array $params { recommend: 1 for a reader suggesting a business they don't own }
 */
function hub_business_add_page( array $params, $mode = HUB_WEB ) {
	$account   = hub_account_current();   // account module
	$recommend = hub_param_bool( $params, 'recommend' );
	$out       = array(
		'recommend'  => $recommend,
		'state'      => 'ready',
		'message'    => '',
		'account'    => $account,
		'categories' => hub_categories_list()['items'],   // categories module
		'areas'      => hub_areas_list()['items'],        // areas module
	);
	if ( $account['signed_in'] && ! $account['email_confirmed'] ) {
		$out['state']   = 'unconfirmed';
		$out['message'] = 'Please confirm your email address first: use the link we sent to ' . esc_html( $account['email'] ) . ', or resend it from your account.';
	} elseif ( ! $recommend && $account['business'] ) {
		$out['state']   = 'has_business';
		$out['message'] = 'Your account already manages ' . esc_html( $account['business']['name'] ) . '. Each account manages one business; email hub@harrowonline.org if you run more than one, or recommend this one instead.';
	} elseif ( ! $recommend && $account['signed_in'] ) {
		$claim = hub_claim_mine();   // claims module
		$added = hub_businesses_added_pending( $account['id'] );
		if ( $claim && 'pending' === $claim['status'] ) {
			$out['state']   = 'waiting';
			$out['message'] = 'You already have a claim waiting for ' . esc_html( $claim['business']['name'] ) . '. We&rsquo;ll email you when it&rsquo;s checked.';
		} elseif ( $added ) {
			$out['state']   = 'waiting';
			$out['message'] = 'We&rsquo;re still checking ' . esc_html( $added[0]['name'] ) . ', which you added. We&rsquo;ll email you when it&rsquo;s live.';
		}
	}
	return hub_respond( $out, $mode );
}

/**
 * Add a business from the website: it waits as "Pending Review" until staff publish it.
 *
 * @param array $params { recommend, business_name, cat, cat_extra[], business_type, summary, address, postcode, area, phone,
 *                        website, email, description, facilities[], hours { mon: { open, close, closed } ... },
 *                        declaration, _hub_nonce (web) }
 * @param array $files  { photo: an entry of $_FILES } (optional, owners only)
 * @return array { ok, message, errors, redirect, duplicate { name, url, claim } | null }
 */
function hub_business_add( array $params, array $files, $mode = HUB_WEB ) {
	$res = function ( $ok, $msg, $errors = array(), $redirect = '', $dupe = null ) use ( $mode ) {
		return hub_respond( array( 'ok' => $ok, 'message' => $msg, 'errors' => array_filter( $errors ), 'redirect' => $redirect, 'duplicate' => $dupe ), $mode, $ok ? 200 : 400 );
	};
	if ( HUB_WEB === $mode && ! wp_verify_nonce( isset( $params['_hub_nonce'] ) ? (string) $params['_hub_nonce'] : '', 'hub_add_business' ) ) {
		return $res( false, 'This form has expired. Please try again.' );
	}
	$page = hub_business_add_page( $params );
	if ( ! $page['account']['signed_in'] ) {
		return $res( false, 'Sign in to add a business.', array(), hub_url( 'sign-in/' ) );
	}
	if ( 'ready' !== $page['state'] ) {
		return $res( false, $page['message'] );
	}
	$account = $page['account'];
	if ( hub_account_limited( 'add_business', array( $account['id'] ) ) ) {   // account module
		return $res( false, 'You&rsquo;ve added several businesses today. Please try again tomorrow, or email hub@harrowonline.org.' );
	}
	$owner = ! $page['recommend'];
	$get   = function ( $k ) use ( $params ) { return isset( $params[ $k ] ) && is_scalar( $params[ $k ] ) ? trim( wp_unslash( (string) $params[ $k ] ) ) : ''; };
	$cats  = wp_list_pluck( $page['categories'], 'slug' );
	$areas = wp_list_pluck( $page['areas'], 'slug' );
	$errors = array();

	$name = sanitize_text_field( $get( 'business_name' ) );   // not "name": WordPress reads a posted "name" as a page slug and 404s
	if ( '' === $name || mb_strlen( $name ) > 100 ) {
		$errors['business_name'] = 'Enter the business name.';
	}
	$cat = $get( 'cat' );
	if ( ! in_array( $cat, $cats, true ) ) {
		$errors['cat'] = 'Choose a category.';
	}
	$extra = isset( $params['cat_extra'] ) ? array_slice( array_intersect( array_map( 'strval', (array) $params['cat_extra'] ), $cats ), 0, 2 ) : array();
	$sub   = sanitize_text_field( $get( 'business_type' ) );
	if ( '' === $sub ) {
		$errors['business_type'] = 'Say what kind of business it is.';
	}
	$tagline = sanitize_text_field( $get( 'summary' ) );
	if ( '' === $tagline || mb_strlen( $tagline ) > 120 ) {
		$errors['summary'] = 'Write a one-line summary of up to 120 characters.';
	}
	$address = sanitize_text_field( $get( 'address' ) );
	if ( '' === $address ) {
		$errors['address'] = 'Enter the street address.';
	}
	$postcode = hub_businesses_rule_postcode( $get( 'postcode' ) );
	if ( '' === $postcode ) {
		$errors['postcode'] = 'Enter a UK postcode, for example HA1 2TA.';
	}
	$area = $get( 'area' );
	if ( ! in_array( $area, $areas, true ) ) {
		$errors['area'] = 'Choose an area.';
	}
	$phone = sanitize_text_field( $get( 'phone' ) );
	if ( strlen( preg_replace( '/\D/', '', $phone ) ) < 10 ) {
		$errors['phone'] = 'Enter a phone number, for example 020 8123 4567.';
	}
	$website = esc_url_raw( $get( 'website' ) );
	// Shape only: wp_http_validate_url() also looks the host up in DNS, which fails for sites that aren't live yet
	if ( '' !== $get( 'website' ) && ( '' === $website || ! filter_var( $website, FILTER_VALIDATE_URL ) || ! preg_match( '#^https?://[^/]+\.[a-z]{2,}#i', $website ) ) ) {
		$errors['website'] = 'Enter the full website address, starting https://';
	}
	$email = $owner ? sanitize_email( $get( 'email' ) ) : '';
	if ( $owner && '' !== $get( 'email' ) && ! is_email( $email ) ) {
		$errors['email'] = 'Enter a valid email address, or leave it empty.';
	}
	$description = sanitize_textarea_field( $get( 'description' ) );
	if ( '' === $description || mb_strlen( $description ) > 1500 ) {
		$errors['description'] = 'Describe the business in up to 1,500 characters.';
	}
	$hours = array();
	foreach ( HUB_BUSINESSES_DAYS as $day => $label ) {
		$h            = $owner && isset( $params['hours'][ $day ] ) && is_array( $params['hours'][ $day ] ) ? $params['hours'][ $day ] : array();
		$hours[ $day ] = hub_businesses_rule_hours_text( isset( $h['open'] ) ? $h['open'] : '', isset( $h['close'] ) ? $h['close'] : '', ! empty( $h['closed'] ) );
	}
	$facilities = $owner && isset( $params['facilities'] ) ? array_values( array_intersect( array_map( 'strval', (array) $params['facilities'] ), array_keys( HUB_BUSINESSES_FACILITIES ) ) ) : array();
	$photo      = $owner && isset( $files['photo'] ) && is_array( $files['photo'] ) && UPLOAD_ERR_NO_FILE !== (int) $files['photo']['error'] ? $files['photo'] : null;
	if ( $photo ) {
		$type = UPLOAD_ERR_OK === (int) $photo['error'] ? wp_check_filetype_and_ext( $photo['tmp_name'], $photo['name'], HUB_BUSINESSES_PHOTO_TYPES ) : array( 'type' => '' );
		if ( UPLOAD_ERR_OK !== (int) $photo['error'] || ! in_array( $type['type'], HUB_BUSINESSES_PHOTO_TYPES, true ) || (int) $photo['size'] > HUB_BUSINESSES_PHOTO_MAX ) {
			$errors['photo'] = 'Choose a JPG or PNG photo of 8MB or less, or leave it out.';
		}
	}
	if ( ! hub_param_bool( $params, 'declaration' ) ) {
		$errors['declaration'] = $owner ? 'Please confirm you own or manage this business.' : 'Please confirm the details are correct as far as you know.';
	}
	if ( $errors ) {
		return $res( false, 'Please check the highlighted fields.', $errors );
	}
	$same = hub_businesses_db_find_same( $name, $postcode );
	if ( $same ) {
		$live = 'publish' === get_post_status( $same );
		$slug = get_post_field( 'post_name', $same );
		return $res( false, $live ? 'This business is already on the Hub.' : 'Someone has already added this business, and we&rsquo;re checking it.', array(), '', $live ? array(
			'name'  => get_the_title( $same ),
			'url'   => hub_url( 'business/' . $slug . '/' ),
			'claim' => hub_url( 'claim/?listing=' . $slug ),
		) : null );
	}

	$id = hub_businesses_db_create_pending( array(
		'name' => $name, 'cats' => array_values( array_unique( array_merge( array( $cat ), $extra ) ) ), 'area' => $area, 'sub' => $sub,
		'tagline' => $tagline, 'address' => $address, 'postcode' => $postcode, 'phone' => $phone, 'website' => $website,
		'email' => $email, 'description' => $description, 'hours' => $hours, 'facilities' => $facilities,
		'added_by' => $account['id'], 'added_as' => $owner ? 'owner' : 'recommend',
	) );
	if ( is_wp_error( $id ) ) {
		hub_debug_log( 'add business: ' . $id->get_error_message() );
		return $res( false, 'We couldn&rsquo;t save it just now. Please try again.' );
	}
	if ( $photo ) {
		$aid = hub_businesses_db_attach_photo( $id, 'photo' );
		if ( is_wp_error( $aid ) ) {
			hub_debug_log( 'add business: photo not saved for ' . $id . ': ' . $aid->get_error_message() );   // the listing is saved; staff can add the photo
		}
	}
	hub_account_count_attempt( 'add_business', array( $account['id'] ) );
	$u = get_userdata( $account['id'] );
	_hub_businesses_added_mail( 'received', $u->user_email, $u, $name, '' );
	_hub_businesses_added_mail( 'staff', get_option( 'admin_email' ), $u, $name, admin_url( 'post.php?post=' . $id . '&action=edit' ), $owner ? 'as the owner' : 'as a recommendation' );
	return $res( true, 'Sent for checking.', array(), add_query_arg( array( 'sent' => $owner ? 'owner' : 'recommend', 'added' => rawurlencode( $name ) ), hub_url( 'add-business/' ) ) );
}

/** Businesses a user added that are still waiting for review: [ { id, name } ]. */
function hub_businesses_added_pending( $user_id, $mode = HUB_WEB ) {
	$out = array();
	foreach ( hub_businesses_db_added_pending( $user_id ) as $id ) {
		$out[] = array( 'id' => (int) $id, 'name' => esc_html( get_the_title( $id ) ) );
	}
	return hub_respond( $out, $mode );
}

/** Who added this business from the website, for staff screens: { user, email, as, facilities[] } or null. */
function hub_business_added_info( $post_id ) {
	$i = hub_businesses_db_added_info( $post_id );
	if ( ! $i ) {
		return null;
	}
	$u = get_userdata( $i['user'] );
	return $i + array( 'email' => $u ? $u->user_email : '(account deleted)', 'facility_labels' => array_values( array_intersect_key( HUB_BUSINESSES_FACILITIES, array_flip( $i['facilities'] ) ) ) );
}

/**
 * Staff published a business added from the website (Pending Review -> Published). An owner gets it: claimed on
 * Basic and linked to their account, unless they've got a business meanwhile. Everyone is emailed.
 */
function hub_business_added_published( $post_id ) {
	$i = hub_businesses_db_added_info( $post_id );
	$u = $i ? get_userdata( $i['user'] ) : null;
	if ( ! $u ) {
		return;
	}
	$name = get_the_title( $post_id );
	if ( 'owner' === $i['as'] && ! hub_account_business_id( $u->ID ) && ! hub_account_owner_id( $post_id ) && current_user_can( 'edit_users' ) ) {
		hub_business_set_claimed( $post_id, true );
		hub_account_link_business( $u->ID, $post_id );   // account module: also sets their plan
		_hub_businesses_added_mail( 'live', $u->user_email, $u, $name, hub_url( 'account/' ) );
		return;
	}
	_hub_businesses_added_mail( 'live_recommend', $u->user_email, $u, $name, hub_url( 'business/' . get_post_field( 'post_name', $post_id ) . '/' ) );
}

/** Staff moved a waiting business to the Trash: tell the person who added it. */
function hub_business_added_declined( $post_id ) {
	$i = hub_businesses_db_added_info( $post_id );
	$u = $i ? get_userdata( $i['user'] ) : null;
	if ( $u ) {
		_hub_businesses_added_mail( 'declined', $u->user_email, $u, get_the_title( $post_id ), '', '', hub_businesses_db_decline_note( $post_id ) );
	}
}

/** Staff decline a business added from the website, with a note for the person (wp-admin). Returns { ok, message }. */
function hub_business_added_decline( $post_id, $note, $mode = HUB_WEB ) {
	if ( ! current_user_can( 'delete_post', (int) $post_id ) ) {
		return hub_respond_error( 'forbidden', 'You can&rsquo;t decline this.', $mode, 403 );
	}
	if ( ! hub_businesses_db_added_info( $post_id ) || 'pending' !== get_post_status( $post_id ) ) {
		return hub_respond( array( 'ok' => false, 'message' => 'Only a business waiting for review can be declined.' ), $mode );
	}
	hub_businesses_db_set_decline_note( $post_id, sanitize_textarea_field( (string) $note ) );
	$ok = hub_businesses_db_trash( $post_id );   // -> hub_business_added_declined() at the end of the request
	return hub_respond( array( 'ok' => $ok, 'message' => $ok ? 'Declined and moved to the Trash. They&rsquo;ll get an email with your note.' : 'Couldn&rsquo;t decline it.' ), $mode );
}

/* ------------------------------------------------------------------ internal */

/** A business (or its profile) in the shape.php shape. Cached per post. */
function _hub_business_build( WP_Post $post, $profile = false ) {
	static $cache = array();
	$key = $post->ID . ( $profile ? 'p' : 's' ) . '@' . hub_businesses_db_cache_gen();
	if ( ! isset( $cache[ $key ] ) ) {
		$fn            = $profile ? 'hub_business_shape_profile' : 'hub_business_shape';
		$cache[ $key ] = $fn( $post, hub_businesses_db_record( $post ), hub_businesses_db_meta( $post ), _hub_businesses_names(), hub_weekday( hub_businesses_db_today() ) );
	}
	return $cache[ $key ];
}

/** A directory row: the business plus how many live offers and upcoming events it has. */
function _hub_businesses_with_counts( WP_Post $post ) {
	$b      = _hub_business_build( $post );
	$offers = ( $b['claimed'] && $b['paid'] ) ? count( hub_offers_for_business( $post->ID )['live'] ) : 0;
	return $b + array( 'counts' => array( 'offers' => $offers, 'upcoming' => $b['claimed'] ? count( _hub_businesses_events( $post ) ) : 0 ) );
}

/**
 * A claimed business's upcoming events and weekly classes, in profile order (events module). Businesses never saved
 * in wp-admin keep the classes of their imported record. Cached per post.
 */
function _hub_businesses_events( WP_Post $post ) {
	static $cache = array();
	if ( ! isset( $cache[ $post->ID ] ) ) {
		$x    = hub_businesses_db_record( $post );
		$rows = array();
		if ( ! empty( $x['claimed'] ) ) {
			$found = hub_events_for_business( $post->ID, array( $x['claimed']['name'] ) );   // events module
			$rows  = array_map( 'hub_business_shape_event_row', $found['events'] );
			if ( hub_businesses_db_uses_fields( $post->ID ) ) {
				$today = hub_businesses_db_today();
				foreach ( $found['classes'] as $c ) {
					$rows[] = hub_business_shape_class_row( $c, $today );
				}
			} else {
				foreach ( isset( $x['claimed']['class_events'] ) ? $x['claimed']['class_events'] : array() as $c ) {
					$rows[] = hub_business_shape_imported_class_row( $c );
				}
			}
			$rows = array_map( function ( $r ) {
				unset( $r['order'] );
				return $r;
			}, hub_businesses_rule_sort_events( $rows ) );
		}
		$cache[ $post->ID ] = $rows;
	}
	return $cache[ $post->ID ];
}

/** Claimed businesses by current and imported name (plain text). */
function _hub_businesses_claimed_by_name() {
	static $by = null;
	if ( null === $by ) {
		$by = array();
		foreach ( hub_businesses_db_query( array( 'claimed_only' => true ) )['posts'] as $post ) {
			$x = hub_businesses_db_record( $post );
			if ( empty( $x['claimed'] ) ) {
				continue;
			}
			$by[ hub_plain( $x['claimed']['name'] ) ] = $post;
			$imported                                 = hub_businesses_db_imported( $post );
			if ( ! empty( $imported['claimed']['name'] ) ) {
				$by[ hub_plain( $imported['claimed']['name'] ) ] = $post;
			}
		}
	}
	return $by;
}

/** Category and area names by slug, from the categories and areas modules (once per request). */
function _hub_businesses_names() {
	static $names = null;
	if ( null === $names ) {
		$names = array(
			'categories' => wp_list_pluck( hub_categories_list()['items'], 'name', 'slug' ),
			'areas'      => wp_list_pluck( hub_areas_list()['items'], 'name', 'slug' ),
		);
	}
	return $names;
}

/**
 * Change a claimed business's plan (called by billing when a subscription starts, changes or ends).
 *
 * @param int    $id
 * @param string $plan 'basic' | 'enhanced' | 'premium' | 'partner'
 * @return array|WP_REST_Response|null|WP_Error business
 */
function hub_business_set_plan( $id, $plan, $mode = HUB_WEB ) {
	if ( ! in_array( $plan, array( 'basic', 'enhanced', 'premium', 'partner' ), true ) ) {
		return hub_respond_error( 'bad_plan', 'Unknown plan: ' . $plan, $mode, 400 );
	}
	$post = hub_businesses_db_get( (int) $id );
	if ( ! $post ) {
		return hub_respond_error( 'not_found', 'No business ' . (int) $id, $mode );
	}
	hub_businesses_db_set_plan( $post->ID, $plan );
	do_action( 'hub_business_plan_changed', (int) $post->ID, $plan );   // e.g. the owner's role (app/setup/accounts.php)
	return hub_respond( _hub_business_build( hub_businesses_db_get( $post->ID ) ), $mode );
}

function _hub_businesses_added_mail( $kind, $to, WP_User $u, $business, $link, $as_label = '', $note = '' ) {
	$mail = hub_businesses_shape_added_email( $kind, array(
		'first_name'    => (string) $u->first_name,
		'business'      => html_entity_decode( $business, ENT_QUOTES, 'UTF-8' ),
		'link'          => $link,
		'account_email' => $u->user_email,
		'as_label'      => $as_label,
		'note'          => $note,
	) );
	if ( ! hub_businesses_db_send_email( $to, $mail['subject'], $mail['body'] ) ) {
		hub_debug_log( 'add business: could not send the ' . $kind . ' email' );
	}
}
