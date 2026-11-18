<?php
/**
 * Categories API:
 *   GET /wp-json/hub/v1/categories
 *   GET /wp-json/hub/v1/categories/{slug}
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/categories', 'GET', function () {
	return hub_categories_list( HUB_API );
} );

hub_route( '/categories/(?P<slug>[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_category_get( $req['slug'], HUB_API );
} );
