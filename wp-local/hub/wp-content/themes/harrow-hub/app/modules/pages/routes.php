<?php
/**
 * Page text API:
 *   GET /wp-json/hub/v1/pages/events   the What's On page's text, links and switches (404 for a page without any)
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/pages/(?P<page>[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_pages_text( $req['page'], HUB_API );
} );
