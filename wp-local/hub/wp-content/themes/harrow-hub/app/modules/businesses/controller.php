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
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Businesses in directory order (Checked, Verified, then imported; then plan; then name).
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

/* ------------------------------------------------------------------ internal */

/** A business (or its profile) in the shape.php shape. Cached per post. */
function _hub_business_build( WP_Post $post, $profile = false ) {
	static $cache = array();
	$key = $post->ID . ( $profile ? 'p' : 's' );
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
			if ( hub_businesses_db_uses_acf( $post->ID ) ) {
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
