<?php
/**
 * Debugging the data layer.
 *
 * 1. What does a page get? The same data is at the API: /wp-json/hub/v1/offers?cat=restaurants returns what
 *    page-templates/offers.php gets from hub_offers_list( array( 'cat' => 'restaurants' ) ).
 *
 * 2. What did this page ask for? Add ?hub_debug=1 to any page (admins, or anyone while WP_DEBUG is on). The page
 *    then lists every controller call in the browser console and in <script id="hub-debug" type="application/json">
 *    at the end of the page: the function, its mode, how long the request had run, memory, and the full result.
 *
 * 3. Errors: controllers that find nothing log why with hub_debug_log() (wp-content/debug.log while
 *    WP_DEBUG_LOG is on).
 */
defined( 'ABSPATH' ) || exit;

/** True when this request should collect the controller log. */
function hub_debug_enabled() {
	static $on = null;
	if ( null === $on ) {
		$on = isset( $_GET['hub_debug'] ) // phpcs:ignore WordPress.Security.NonceVerification -- read-only switch
			&& ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) );
	}
	return $on;
}

/** Record one controller result (called by hub_respond() and hub_respond_error()). */
function hub_debug_record( $result, $mode ) {
	if ( ! hub_debug_enabled() ) {
		return;
	}
	$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );   // [0] here, [1] hub_respond*, [2] the controller
	$GLOBALS['hub_debug_calls'][] = array(
		'controller' => isset( $trace[2]['function'] ) ? $trace[2]['function'] : '?',
		'mode'       => $mode,
		'at_ms'      => round( ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000 ),
		'memory_mb'  => round( memory_get_peak_usage() / 1048576, 1 ),
		'result'     => $result,
	);
}

/** Write a line to the PHP error log (wp-content/debug.log) while WP_DEBUG is on. */
function hub_debug_log( $message, array $context = array() ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[hub] ' . $message . ( $context ? ' ' . wp_json_encode( $context ) : '' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}

add_action( 'wp_footer', function () {
	if ( ! hub_debug_enabled() ) {
		return;
	}
	$calls = isset( $GLOBALS['hub_debug_calls'] ) ? $GLOBALS['hub_debug_calls'] : array();
	$json  = wp_json_encode( $calls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	echo "\n<script id=\"hub-debug\" type=\"application/json\">" . str_replace( '</', '<\/', $json ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	echo "<script>console.groupCollapsed('Hub controllers: " . count( $calls ) . " call(s)');console.log(JSON.parse(document.getElementById('hub-debug').textContent));console.groupEnd();</script>\n";
}, 999 );
