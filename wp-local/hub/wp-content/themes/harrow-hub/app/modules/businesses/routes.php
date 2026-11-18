<?php
/**
 * Businesses API:
 *   GET /wp-json/hub/v1/businesses?cat=&area=&limit=&offset=   directory
 *   GET /wp-json/hub/v1/businesses/featured?limit=4            homepage features
 *   GET /wp-json/hub/v1/businesses/newest?limit=10             just joined
 *   GET /wp-json/hub/v1/businesses/{id or slug}                a business page
 * (GET /wp-json/hub/v1/businesses/{id}/offers is in the offers module.)
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/businesses', 'GET', function ( WP_REST_Request $req ) {
	return hub_businesses_list( $req->get_params(), HUB_API );
} );

hub_route( '/businesses/featured', 'GET', function ( WP_REST_Request $req ) {
	return hub_businesses_featured( hub_param_int( $req->get_params(), 'limit', 4, 1, 12 ), HUB_API );
} );

hub_route( '/businesses/newest', 'GET', function ( WP_REST_Request $req ) {
	return hub_businesses_newest( hub_param_int( $req->get_params(), 'limit', 10, 1, 50 ), HUB_API );
} );

hub_route( '/businesses/(?P<business>(?!featured$|newest$)[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_business_get( $req['business'], HUB_API );
} );
