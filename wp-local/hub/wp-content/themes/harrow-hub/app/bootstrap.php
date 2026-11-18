<?php
/**
 * Hub data layer: loads the core helpers, the setup and every module, in this order.
 *
 * Every page and every API route gets its data from a module's controller.php functions. Each controller function
 * takes a $mode as its last argument:
 *   HUB_WEB (default) returns plain PHP arrays for the theme's templates and parts
 *   HUB_API           returns a WP_REST_Response (JSON) for the app, at /wp-json/hub/v1/...
 * The data is the same in both modes. Only the wrapping differs (app/core/respond.php).
 *
 * Each module has the same five files:
 *   data.php        the only place that reads or writes WordPress (WP_Query, post meta, ACF, terms)
 *   rules.php       the business rules (what is live, what is listed, the order)
 *   shape.php       turns a post into the module's one fixed array shape (what the app and the templates get)
 *   controller.php  the functions pages and routes call; nothing outside the module calls anything else in it
 *   routes.php      the module's REST routes, which call its controller in API mode
 * Functions starting with _hub_ are internal to their module.
 */
defined( 'ABSPATH' ) || exit;

$hub_app = __DIR__;

// Core: debugging, modes and responses, request params, URLs
require_once $hub_app . '/core/debug.php';
require_once $hub_app . '/core/respond.php';
require_once $hub_app . '/core/request.php';
require_once $hub_app . '/core/url.php';

// Setup: REST namespace and route helper
require_once $hub_app . '/setup/rest-routes.php';

// Modules
foreach ( array( 'categories', 'areas', 'offers' ) as $hub_module ) {
	foreach ( array( 'data', 'rules', 'shape', 'controller', 'routes' ) as $hub_file ) {
		require_once $hub_app . '/modules/' . $hub_module . '/' . $hub_file . '.php';
	}
}
unset( $hub_app, $hub_module, $hub_file );
