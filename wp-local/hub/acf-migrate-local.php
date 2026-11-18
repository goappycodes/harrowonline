<?php
/**
 * Turn on ACF Pro (if it is off) and copy the imported Hub data into the ACF fields (CLI). Safe to run again.
 * Then commit wp-content/database/hub.sqlite and wp-content/uploads/.
 *
 *   php wp-local/hub/acf-migrate-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$hub_acf_plugin = 'advanced-custom-fields-pro/acf.php';
if ( ! is_plugin_active( $hub_acf_plugin ) ) {
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $hub_acf_plugin ) ) {
		exit( "ACF Pro is not in wp-content/plugins/advanced-custom-fields-pro.\n" );
	}
	$r = activate_plugin( $hub_acf_plugin );
	if ( is_wp_error( $r ) ) {
		exit( 'Could not turn on ACF Pro: ' . $r->get_error_message() . "\n" );
	}
	echo "Turned on ACF Pro.\n";
	// ACF loads with the plugins, so run again in a fresh process to have it
	passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ), $code );
	exit( $code );
}

hub_acf_migrate();
