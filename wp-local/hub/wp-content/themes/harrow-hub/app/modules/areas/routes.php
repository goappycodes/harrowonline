<?php
/**
 * Areas API:
 *   GET /wp-json/hub/v1/areas
 *   GET /wp-json/hub/v1/areas/{slug}
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/areas', 'GET', function () {
	return hub_areas_list( HUB_API );
} );

hub_route( '/areas/(?P<slug>[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_area_get( $req['slug'], HUB_API );
} );
