<?php
/**
 * The Hub API: every module's routes live under /wp-json/hub/v1/ and are registered through hub_route().
 */
defined( 'ABSPATH' ) || exit;

const HUB_API_NAMESPACE = 'hub/v1';

/**
 * Register a route in the Hub namespace. Call it from a module's routes.php.
 *
 *   hub_route( '/offers', 'GET', function ( WP_REST_Request $req ) { return hub_offers_list( $req->get_params(), HUB_API ); } );
 *
 * @param string          $path       Route path, e.g. '/offers/(?P<id>[\w-]+)'.
 * @param string          $methods    'GET', 'POST', ...
 * @param callable        $callback   Receives the WP_REST_Request; returns the controller's API-mode result.
 * @param callable|string $permission Permission callback; public by default (read-only routes).
 * @param array           $args       Argument definitions for the route.
 */
function hub_route( $path, $methods, $callback, $permission = '__return_true', array $args = array() ) {
	add_action( 'rest_api_init', function () use ( $path, $methods, $callback, $permission, $args ) {
		register_rest_route( HUB_API_NAMESPACE, $path, array(
			'methods'             => $methods,
			'callback'            => $callback,
			'permission_callback' => $permission,
			'args'                => $args,
		) );
	} );
}
