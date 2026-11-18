<?php
/**
 * The Hub has no comments: comments and pingbacks are off everywhere, and the Comments menu, admin-bar item,
 * dashboard box and comment feeds are removed.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	foreach ( get_post_types() as $type ) {
		if ( post_type_supports( $type, 'comments' ) ) {
			remove_post_type_support( $type, 'comments' );
			remove_post_type_support( $type, 'trackbacks' );
		}
	}
}, 100 );

add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 10 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );
add_filter( 'xmlrpc_methods', function ( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	return $methods;
} );

add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
} );

add_action( 'admin_init', function () {
	global $pagenow;
	if ( in_array( $pagenow, array( 'edit-comments.php', 'comment.php', 'options-discussion.php' ), true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
} );

add_action( 'wp_before_admin_bar_render', function () {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'comments' );
} );
