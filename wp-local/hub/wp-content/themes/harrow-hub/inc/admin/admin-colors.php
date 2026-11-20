<?php
/**
 * wp-admin in the Hub's colours: the "Harrow Hub" admin colour scheme (Users > Profile > Admin Color Scheme). It is
 * WordPress's Default scheme with the Hub red instead of blue (admin/hub-admin-colors.css, made by
 * tools/build_admin_colors.py). New accounts start on it; accounts still on WordPress's Default move to it once.
 * Anyone can pick another scheme in their profile.
 */
defined( 'ABSPATH' ) || exit;

const HUB_ADMIN_COLOR = 'hub';

add_action( 'admin_init', function () {
	$file = get_theme_file_path( 'admin/hub-admin-colors.css' );
	wp_admin_css_color(
		HUB_ADMIN_COLOR,
		'Harrow Hub',
		add_query_arg( 'v', is_readable( $file ) ? filemtime( $file ) : HUB_THEME_VERSION, get_theme_file_uri( 'admin/hub-admin-colors.css' ) ),
		array( '#1e1e1e', '#df2028', '#fa5e65' ),
		array( 'base' => '#f3f1f1', 'focus' => '#fff', 'current' => '#fff' )
	);

	// Once: accounts on WordPress's own default scheme move to the Hub's
	if ( ! get_option( 'hub_admin_colors' ) ) {
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $id ) {
			if ( in_array( (string) get_user_meta( $id, 'admin_color', true ), array( '', 'fresh', 'modern' ), true ) ) {
				update_user_meta( $id, 'admin_color', HUB_ADMIN_COLOR );
			}
		}
		update_option( 'hub_admin_colors', 1, false );
	}
} );

/** New accounts start on the Hub's scheme. */
add_filter( 'insert_user_meta', function ( $meta, $user, $update ) {
	if ( ! $update && in_array( isset( $meta['admin_color'] ) ? (string) $meta['admin_color'] : '', array( '', 'fresh', 'modern' ), true ) ) {
		$meta['admin_color'] = HUB_ADMIN_COLOR;
	}
	return $meta;
}, 10, 3 );
