<?php
/**
 * Categories: what pages and the API call.
 *
 *   hub_categories_list()            every category, A to Z
 *   hub_category_get( 'restaurants' ) one category by slug
 */
defined( 'ABSPATH' ) || exit;

/**
 * Every category, A to Z.
 *
 * @return array|WP_REST_Response { items: category[], total: int }
 */
function hub_categories_list( $mode = HUB_WEB ) {
	$items = hub_categories_rule_sort( array_map( 'hub_category_shape', hub_categories_db_all() ) );
	return hub_respond( array( 'items' => $items, 'total' => count( $items ) ), $mode );
}

/**
 * One category by slug.
 *
 * @return array|WP_REST_Response|null|WP_Error category, or null (web) / 404 (API) when there is none
 */
function hub_category_get( $slug, $mode = HUB_WEB ) {
	foreach ( hub_categories_db_all() as $term ) {
		if ( $term->slug === $slug ) {
			return hub_respond( hub_category_shape( $term ), $mode );
		}
	}
	return hub_respond_error( 'hub_category_not_found', 'No category with that slug.', $mode );
}
