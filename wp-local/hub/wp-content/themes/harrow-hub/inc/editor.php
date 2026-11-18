<?php
/**
 * Classic editing: the block editor (Gutenberg) is turned off for every post type and for widgets.
 * Hub pages are built by the theme's templates, so the block editor and its front-end styles aren't needed.
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'use_block_editor_for_post', '__return_false', 100 );
add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
add_filter( 'use_widgets_block_editor', '__return_false' );
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );

/** No block styles or global styles on the front end. */
add_action( 'wp_enqueue_scripts', function () {
	foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'core-block-supports' ) as $h ) {
		wp_dequeue_style( $h );
		wp_deregister_style( $h );
	}
}, 100 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
