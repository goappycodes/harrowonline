<?php
/**
 * The Hub doesn't use Posts (news is on harrowonline.org): the Posts menu, "New > Post" in the admin bar and the
 * Dashboard's Quick Draft are removed. The Posts screens still open by their address, with a note pointing to the
 * Hub's own content (inc/admin/list-screens.php).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	remove_menu_page( 'edit.php' );
} );

add_action( 'admin_bar_menu', function ( WP_Admin_Bar $bar ) {
	$bar->remove_node( 'new-post' );
}, 999 );

add_action( 'wp_dashboard_setup', function () {
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
} );
