<?php
/**
 * Areas: what pages and the API call.
 *
 *   hub_areas_list()          every area, A to Z
 *   hub_area_get( 'pinner' )  one area by slug
 */
defined( 'ABSPATH' ) || exit;

/**
 * Every area, A to Z.
 *
 * @return array|WP_REST_Response { items: area[], total: int }
 */
function hub_areas_list( $mode = HUB_WEB ) {
	$items = hub_areas_rule_sort( array_map( 'hub_area_shape', hub_areas_db_all() ) );
	return hub_respond( array( 'items' => $items, 'total' => count( $items ) ), $mode );
}

/**
 * One area by slug.
 *
 * @return array|WP_REST_Response|null|WP_Error area, or null (web) / 404 (API) when there is none
 */
function hub_area_get( $slug, $mode = HUB_WEB ) {
	foreach ( hub_areas_db_all() as $term ) {
		if ( $term->slug === $slug ) {
			return hub_respond( hub_area_shape( $term ), $mode );
		}
	}
	return hub_respond_error( 'hub_area_not_found', 'No area with that slug.', $mode );
}
