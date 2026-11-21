<?php
/** Styles, script and the inline icon sprite. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	// Versioned by the file's date, so browsers fetch the new one after every change (deploys included)
	$ver = function ( $file ) {
		$path = get_template_directory() . '/assets/' . $file;
		return file_exists( $path ) ? (string) filemtime( $path ) : HUB_THEME_VERSION;
	};
	wp_enqueue_style( 'ho-hub', hub_asset( 'css/hub.css' ), array(), $ver( 'css/hub.css' ) );
	wp_enqueue_script( 'ho-hub', hub_asset( 'js/hub.js' ), array(), $ver( 'js/hub.js' ), array( 'strategy' => 'defer', 'in_footer' => false ) );
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

/** One inline sprite, so templates use <use href="#i-name"> exactly as the prototype. */
add_action( 'wp_body_open', function () {
	$file = get_template_directory() . '/assets/img/icons.svg';
	if ( is_readable( $file ) ) {
		$svg = file_get_contents( $file );
		$svg = preg_replace( '/<\?xml[^>]*\?>/', '', $svg );
		echo str_replace( '<svg ', '<svg aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden" ', $svg ); // phpcs:ignore WordPress.Security.EscapeOutput -- theme's own file
	}
} );
