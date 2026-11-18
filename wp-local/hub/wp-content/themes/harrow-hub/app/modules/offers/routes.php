<?php
/**
 * Offers API:
 *   GET /wp-json/hub/v1/offers?cat=&area=&sub=1&limit=&offset=   Local Offers (live)
 *   GET /wp-json/hub/v1/offers/{id or slug}                       one offer and the offers to suggest beside it
 *   GET /wp-json/hub/v1/businesses/{id}/offers                    a business's live and ended offers
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/offers', 'GET', function ( WP_REST_Request $req ) {
	return hub_offers_list( $req->get_params(), HUB_API );
} );

hub_route( '/offers/(?P<offer>[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_offer_get( $req['offer'], HUB_API );
} );

hub_route( '/businesses/(?P<id>\d+)/offers', 'GET', function ( WP_REST_Request $req ) {
	return hub_offers_for_business( (int) $req['id'], HUB_API );
} );
