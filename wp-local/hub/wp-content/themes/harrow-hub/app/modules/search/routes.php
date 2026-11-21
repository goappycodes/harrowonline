<?php
/**
 * Search API (namespace hub/v1), public:
 *   GET /search/index   the index hub.js searches and lists from (shape.php). It sends an ETag: a browser that has
 *                       this version already gets "304 Not Modified" and no body.
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/search/index', 'GET', function ( WP_REST_Request $req ) {
	$tag = '"' . hub_search_index_tag() . '"';
	$res = trim( (string) $req->get_header( 'if_none_match' ) ) === $tag ? new WP_REST_Response( null, 304 ) : hub_search_index( HUB_API );
	$res->header( 'ETag', $tag );
	$res->header( 'Cache-Control', 'no-cache' );   // ask each time; unchanged costs only the 304
	return $res;
} );
