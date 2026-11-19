<?php
/**
 * Offers: what pages and the API call. Nothing outside this module calls anything else in it.
 *
 *   hub_offers_list( $params )          Local Offers: live offers, filtered, in Local Offers order, with category counts
 *   hub_offer_get( $id_or_slug )        one offer (live or ended) and the offers to suggest beside it
 *   hub_offers_for_business( $id )      a business's live and ended offers (its profile)
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Live offers from businesses on a plan that shows offers.
 *
 * @param array $params {
 *     cat    category slug
 *     area   area slug
 *     sub    1 = subscriber-only offers only
 *     limit  how many to return (0 = all)
 *     offset how many to skip
 * }
 * @return array|WP_REST_Response {
 *     items:      offer[]  (see shape.php)
 *     total:      int      offers matching the filters (before limit and offset)
 *     live_total: int      all live offers (no filters)
 *     filters:    { cat, area, sub }  the filters applied, cleaned
 *     facets:     { categories: [ { slug, name, count } ] }  live offers per category (no filters), most first
 * }
 */
function hub_offers_list( array $params = array(), $mode = HUB_WEB ) {
	$filters = array(
		'cat'  => hub_param_slug( $params, 'cat' ),
		'area' => hub_param_slug( $params, 'area' ),
		'sub'  => hub_param_bool( $params, 'sub' ),
	);
	$limit  = hub_param_int( $params, 'limit', 0, 0, 100 );
	$offset = hub_param_int( $params, 'offset', 0 );

	$live  = _hub_offers_ranked();
	$items = array_values( array_filter( $live, function ( $o ) use ( $filters ) {
		return ( '' === $filters['cat'] || $o['business']['category']['slug'] === $filters['cat'] )
			&& ( '' === $filters['area'] || $o['business']['area']['slug'] === $filters['area'] )
			&& ( ! $filters['sub'] || $o['subscriber_only'] );
	} ) );
	$total = count( $items );
	$items = array_slice( $items, $offset, $limit ? $limit : null );

	return hub_respond( array(
		'items'      => array_map( '_hub_offers_public', $items ),
		'total'      => $total,
		'live_total' => count( $live ),
		'filters'    => $filters,
		'facets'     => array( 'categories' => _hub_offers_category_counts( $live ) ),
	), $mode );
}

/**
 * One offer, live or ended (ended offers keep their page), and the live offers to suggest beside it.
 *
 * @param int|string $id_or_slug Post ID or slug.
 * @return array|WP_REST_Response|null|WP_Error { offer: offer, more: offer[] }, or null (web) / 404 (API)
 */
function hub_offer_get( $id_or_slug, $mode = HUB_WEB ) {
	$post     = hub_offers_db_get( hub_param_id_or_slug( $id_or_slug ) );
	$business = $post ? _hub_offers_business( hub_offers_db_business_id( $post->ID ) ) : null;
	if ( ! $post || ! $business ) {
		return hub_respond_error( 'hub_offer_not_found', 'No offer with that ID or slug.', $mode );
	}
	$offer = _hub_offer_build( $post, $business );
	return hub_respond( array(
		'offer' => _hub_offers_public( $offer ),
		'more'  => array_map( '_hub_offers_public', hub_offers_rule_more( $offer, _hub_offers_ranked() ) ),
	), $mode );
}

/**
 * A business's offers, in its own order. Its profile shows the live ones only when its plan shows offers
 * ("shows_offers").
 *
 * @return array|WP_REST_Response { live: offer[], ended: offer[], shows_offers: bool }
 */
function hub_offers_for_business( $business_id, $mode = HUB_WEB ) {
	$business = _hub_offers_business( $business_id );
	$out      = array( 'live' => array(), 'ended' => array(), 'shows_offers' => false );
	if ( $business ) {
		$out['shows_offers'] = hub_offers_rule_plan_shows_offers( $business['plan'] );
		foreach ( hub_offers_db_for_business( $business['id'] ) as $post ) {
			$o = _hub_offers_public( _hub_offer_build( $post, $business ) );
			$out[ 'live' === $o['status'] ? 'live' : 'ended' ][] = $o;
		}
	}
	return hub_respond( $out, $mode );
}

/* ------------------------------------------------------------------ offers sent from the website (submissions module) */

/**
 * Save an offer from the account pages (the submissions module checks who may, and the values).
 *
 * @param array $f { title, description, start, end (Y-m-d), ends_label ("31 December 2026"), code, redeem, terms[], audience }
 * @param array $business { id, slug } of the owner's business
 * @return int|WP_Error post ID
 */
function hub_offer_save_submission( $id, $status, array $f, array $business ) {
	$record = array(
		'title'    => $f['title'],
		'text'     => $f['description'],
		'start'    => $f['start'],
		'end'      => $f['end'],
		'ends'     => $f['ends_label'],
		'code'     => $f['code'],
		'redeem'   => $f['redeem'],
		'terms'    => $f['terms'],
		'sub'      => 'subscribers' === $f['audience'],
		'business' => $business['slug'],
	);
	return hub_offers_db_save( (int) $id, $status, $f['title'], $f['description'], $record, (int) $business['id'], $f );
}

/** An offer sent from the website, any status: { id, status, title, url, form } or null. */
function hub_offer_submission( $id ) {
	$post = hub_offers_db_get_any( $id );
	if ( ! $post ) {
		return null;
	}
	$form = get_post_meta( $post->ID, '_hub_form', true );
	return array( 'id' => $post->ID, 'status' => $post->post_status, 'title' => hub_plain( $post->post_title ), 'url' => hub_url( 'offers/' . $post->post_name . '/' ), 'form' => is_array( $form ) ? $form : null );
}

/* ------------------------------------------------------------------ internal */

/** One offer in the shape.php shape (with "_sort"). */
function _hub_offer_build( WP_Post $post, array $business ) {
	return hub_offer_shape( $post, hub_offers_db_record( $post ), hub_offers_db_index( $post ), $business, hub_offers_db_today() );
}

/**
 * Businesses of offers, from the businesses module (one call for many). Cached for the request.
 *
 * @param int[] $ids Business post IDs to load if not loaded yet.
 */
function _hub_offers_businesses( array $ids = array() ) {
	static $loaded = array();
	$missing = array_diff( array_map( 'intval', $ids ), array_keys( $loaded ) );
	if ( $missing ) {
		$found = hub_businesses_summaries( $missing )['items'];
		foreach ( $missing as $id ) {
			$loaded[ $id ] = isset( $found[ $id ] ) ? $found[ $id ] : null;
		}
	}
	return $loaded;
}

/** One offer's business (businesses module shape), or null. */
function _hub_offers_business( $business_id ) {
	$business_id = (int) $business_id;
	$all         = _hub_offers_businesses( array( $business_id ) );
	return isset( $all[ $business_id ] ) ? $all[ $business_id ] : null;
}

/** Every live offer from a business whose plan shows offers, in Local Offers order. Cached for the request. */
function _hub_offers_ranked() {
	static $ranked = null;
	if ( null === $ranked ) {
		$ranked = array();
		$posts  = hub_offers_db_all();
		_hub_offers_businesses( array_map( function ( $p ) { return hub_offers_db_business_id( $p->ID ); }, $posts ) );
		foreach ( $posts as $post ) {
			$business = _hub_offers_business( hub_offers_db_business_id( $post->ID ) );
			if ( ! $business || ! hub_offers_rule_plan_shows_offers( $business['plan'] ) ) {
				continue;
			}
			$o = _hub_offer_build( $post, $business );
			if ( 'live' === $o['status'] ) {
				$ranked[] = $o;
			}
		}
		usort( $ranked, 'hub_offers_rule_compare' );
	}
	return $ranked;
}

/** Live offers per category, most first, then A to Z. */
function _hub_offers_category_counts( array $offers ) {
	$counts = array();
	foreach ( $offers as $o ) {
		$cat = $o['business']['category'];
		if ( ! isset( $counts[ $cat['slug'] ] ) ) {
			$counts[ $cat['slug'] ] = array( 'slug' => $cat['slug'], 'name' => $cat['name'], 'count' => 0 );
		}
		$counts[ $cat['slug'] ]['count']++;
	}
	$counts = array_values( $counts );
	usort( $counts, function ( $a, $b ) {
		return array( -$a['count'], strtolower( $a['name'] ) ) <=> array( -$b['count'], strtolower( $b['name'] ) );
	} );
	return $counts;
}

/** The offer without its internal keys (the ones starting with "_"). */
function _hub_offers_public( array $offer ) {
	return array_filter( $offer, function ( $key ) {
		return '_' !== $key[0];
	}, ARRAY_FILTER_USE_KEY );
}
