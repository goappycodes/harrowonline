<?php
/**
 * Page text API:
 *   GET /wp-json/hub/v1/pages/events    the What's On page's text, links and switches
 *   GET /wp-json/hub/v1/pages/classes   the Classes and activities page's
 *   GET /wp-json/hub/v1/pages/offers    the Local Offers page's
 *   GET /wp-json/hub/v1/pages/pricing   Pricing: its text, and the prices from billing
 *   GET /wp-json/hub/v1/pages/privacy   (and terms) the page's text, and its document with "On this page"
 *   (404 for a page without any)
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/pages/(?P<page>[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	if ( 'pricing' === $req['page'] ) {
		return hub_pages_pricing( HUB_API );
	}
	if ( in_array( $req['page'], HUB_PAGES_DOCUMENTS, true ) ) {
		return hub_respond( array( 'text' => hub_pages_text( $req['page'] ), 'document' => hub_pages_document( $req['page'] ) ), HUB_API );
	}
	return hub_pages_text( $req['page'], HUB_API );
} );
