<?php
/**
 * Import the Hub's businesses, offers, events, weekly classes, categories and areas into the local WordPress
 * (CLI). Reads data/wp-hub-data.json (python tools/export_wp_data.py). Safe to run again: it replaces all Hub
 * content each time. Then commit wp-content/database/hub.sqlite.
 *
 *   C:\php\php.exe wp-local/hub/import-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';
set_time_limit( 0 );

$file = dirname( __DIR__, 2 ) . '/data/wp-hub-data.json';
$data = json_decode( file_get_contents( $file ), true );
if ( ! $data ) {
	exit( "Could not read {$file}\n" );
}

// Store titles and text exactly as in the data (they contain entities like &pound; and &rsquo;)
kses_remove_filters();
wp_defer_term_counting( true );
wp_suspend_cache_invalidation( true );
global $wpdb;
$t0 = microtime( true );

// ---------------------------------------------------------------- clear previous Hub content
$types = "'hub_business','hub_offer','hub_event','hub_class'";
$ids   = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$types})" );
if ( $ids ) {
	$in = implode( ',', array_map( 'intval', $ids ) );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$in})" );
	$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$in})" );
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$in})" );
}
foreach ( array( 'hub_category', 'hub_area' ) as $tax ) {
	foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids' ) ) as $tid ) {
		wp_delete_term( $tid, $tax );
	}
}
echo 'Cleared ' . count( $ids ) . " old posts\n";

$wpdb->query( 'BEGIN' );

// ---------------------------------------------------------------- categories and areas
foreach ( $data['categories'] as $i => $c ) {
	$t = wp_insert_term( $c['name'], 'hub_category', array( 'slug' => $c['slug'] ) );
	update_term_meta( $t['term_id'], 'hub_icon', $c['icon'] );
	update_term_meta( $t['term_id'], 'hub_intro', $c['intro'] );
	update_term_meta( $t['term_id'], 'hub_order', $i );
}
foreach ( $data['areas'] as $i => $a ) {
	$t = wp_insert_term( $a['name'], 'hub_area', array( 'slug' => $a['slug'] ) );
	update_term_meta( $t['term_id'], 'hub_lat', $a['lat'] );
	update_term_meta( $t['term_id'], 'hub_lng', $a['lng'] );
	update_term_meta( $t['term_id'], 'hub_order', $i );
}
$cat_id  = wp_list_pluck( get_terms( array( 'taxonomy' => 'hub_category', 'hide_empty' => false ) ), 'term_id', 'slug' );
$area_id = wp_list_pluck( get_terms( array( 'taxonomy' => 'hub_area', 'hide_empty' => false ) ), 'term_id', 'slug' );

/** Insert a post fast (no revisions, no pings), returning its ID. */
function hub_import_post( $type, $slug, $title, $content, array $meta ) {
	return wp_insert_post( array(
		'post_type'      => $type,
		'post_status'    => 'publish',
		'post_name'      => $slug,
		'post_title'     => $title,
		'post_content'   => $content,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'meta_input'     => $meta,
	), true );
}

// ---------------------------------------------------------------- businesses
$tier_rank = array( 'partner' => 0, 'premium' => 0, 'enhanced' => 1, 'basic' => 2, 'unclaimed' => 3 );
$biz_id    = array();
foreach ( $data['businesses'] as $n => $x ) {
	$c       = isset( $x['claimed'] ) ? $x['claimed'] : null;
	$content = $c ? implode( '', array_map( function ( $p ) { return "<p>{$p}</p>"; }, $c['description'] ) ) : '';
	$status  = ! empty( $x['checked'] ) ? 1 : ( $c ? 0 : 2 );   // Verified, Checked, imported
	$id      = hub_import_post( 'hub_business', $x['slug'], $x['name'], $content, array(
		'_hub'        => $x,
		'_hub_order'  => (int) $x['order'],
		'_hub_tier'   => isset( $x['tier'] ) ? $x['tier'] : 'unclaimed',
		'_hub_status' => $status,
		'_hub_rank'   => $status * 10 + $tier_rank[ isset( $x['tier'] ) ? $x['tier'] : 'unclaimed' ],
	) );
	if ( is_wp_error( $id ) ) {
		exit( 'Business ' . $x['slug'] . ': ' . $id->get_error_message() . "\n" );
	}
	wp_set_object_terms( $id, (int) $cat_id[ $x['cat'] ], 'hub_category' );
	wp_set_object_terms( $id, (int) $area_id[ $x['area'] ], 'hub_area' );
	$biz_id[ $x['slug'] ] = $id;
	if ( 0 === ( $n + 1 ) % 500 ) {
		echo '  businesses: ' . ( $n + 1 ) . "\n";
	}
}

// ---------------------------------------------------------------- offers, events, classes
foreach ( $data['offers'] as $o ) {
	hub_import_post( 'hub_offer', $o['slug'], $o['title'], $o['text'], array(
		'_hub'          => $o,
		'_hub_business' => $biz_id[ $o['business'] ],
		'_hub_end'      => $o['end'],
		'_hub_index'    => (int) $o['index'],
		'_hub_featured' => empty( $o['featured'] ) ? 0 : 1,
	) );
}
foreach ( $data['events'] as $ev ) {
	hub_import_post( 'hub_event', $ev['slug'], $ev['title'], $ev['desc'], array(
		'_hub'       => $ev,
		'_hub_start' => $ev['start'],
		'_hub_day'   => $ev['day'],
		'_hub_org'   => isset( $ev['org'] ) ? $ev['org'] : '',
	) );
}
foreach ( $data['classes'] as $c ) {
	hub_import_post( 'hub_class', $c['slug'], $c['title'], $c['desc'], array(
		'_hub'       => $c,
		'_hub_order' => (int) $c['order'],
		'_hub_day'   => null === $c['day'] ? '' : (int) $c['day'],   // none: on the classes page only (Kodak FC's teams)
	) );
}

$wpdb->query( 'COMMIT' );
wp_suspend_cache_invalidation( false );
wp_defer_term_counting( false );
update_option( 'hub_today', $data['today'] );   // the preview's fixed "today"; empty = the real date
flush_rewrite_rules();

// Fill the business fields the pages read and wp-admin edits (theme inc/fields-migrate.php)
hub_fields_migrate();

printf( "Imported %d businesses, %d offers, %d events, %d classes, %d categories, %d areas in %.0fs\n",
	count( $data['businesses'] ), count( $data['offers'] ), count( $data['events'] ), count( $data['classes'] ),
	count( $data['categories'] ), count( $data['areas'] ), microtime( true ) - $t0 );
