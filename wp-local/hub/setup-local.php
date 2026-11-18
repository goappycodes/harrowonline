<?php
/**
 * Local setup (CLI), safe to run again: activates the Harrow Online Hub theme, creates a WordPress Page for every
 * prototype page at the same path (inc/page-map.php, e.g. claim/submitted), sets the homepage and permalinks.
 *
 *   C:\php\php.exe wp-local/hub/setup-local.php
 *
 * Run it after `python tools/build_theme.py` adds a page, then commit wp-content/database/hub.sqlite.
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';

switch_theme( 'harrow-hub' );
echo 'Theme: ' . wp_get_theme()->get( 'Name' ) . "\n";

$map     = require get_template_directory() . '/inc/page-map.php';
$created = 0;
$ids     = array();
uksort( $map, function ( $a, $b ) { return substr_count( $a, '/' ) - substr_count( $b, '/' ) ?: strcmp( $a, $b ); } );   // parents first
foreach ( $map as $path => $row ) {
	if ( '' === $path || '404.html' === $path ) {
		continue;   // homepage is its own Page below; 404 is the theme's 404.php
	}
	$existing = get_page_by_path( $path );
	if ( $existing ) {
		$ids[ $path ] = $existing->ID;
		continue;
	}
	$parts  = explode( '/', $path );
	$slug   = array_pop( $parts );
	$parent = $parts ? ( isset( $ids[ implode( '/', $parts ) ] ) ? $ids[ implode( '/', $parts ) ] : 0 ) : 0;
	if ( $parts && ! $parent ) {
		// parent paths with no template of their own (e.g. "advertise" for advertise/newsletter,
		// "account/listings" for account/listings/edit): create each missing level under the one above it
		$above = 0;
		$sofar = array();
		foreach ( $parts as $seg ) {
			$sofar[] = $seg;
			$p       = implode( '/', $sofar );
			$pp      = get_page_by_path( $p );
			$above   = $pp ? $pp->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => ucfirst( $seg ), 'post_name' => $seg, 'post_parent' => $above ) );
			$ids[ $p ] = $above;
		}
		$parent = $above;
	}
	$ids[ $path ] = wp_insert_post( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => wp_specialchars_decode( $row['title'], ENT_QUOTES ),
		'post_name'   => $slug,
		'post_parent' => $parent,
	) );
	$created++;
}

$home = get_page_by_path( 'home' );
if ( ! $home ) {
	$home = get_post( wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home', 'post_name' => 'home' ) ) );
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home->ID );

// The default "Sample Page" and "Hello world!" post are not part of the Hub.
foreach ( array( get_page_by_path( 'sample-page' ), get_page_by_path( 'hello-world', OBJECT, 'post' ) ) as $p ) {
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();
echo "Pages created: {$created}; total Hub pages: " . count( $ids ) . "\n";
