<?php
/**
 * Bring the weekly classes in the local WordPress up to date with data/wp-hub-data.json without a full import (CLI).
 * Safe to run again. Each class gets its "card" (how it shows on the Classes and activities page) and "page" flag;
 * classes that are missing (the Kodak FC entries, which appear on the classes page only) are added and linked to
 * their business. Nothing else is touched, so edits made in wp-admin stay (classes with fields get their card and page
 * fields updated).
 * Then commit wp-content/database/hub.sqlite.
 *
 *   python tools/export_wp_data.py
 *   php wp-local/hub/update-classes-local.php
 */
$_SERVER['HTTP_HOST']   = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';

$data = json_decode( file_get_contents( dirname( __DIR__, 2 ) . '/data/wp-hub-data.json' ), true );
if ( ! $data || empty( $data['classes'] ) ) {
	exit( "data/wp-hub-data.json has no classes: run python tools/export_wp_data.py first.\n" );
}

// Claimed businesses by name (the same match as inc/fields-migrate.php)
$by_name = array();
foreach ( get_posts( array( 'post_type' => 'hub_business', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => '_hub_tier', 'value' => 'unclaimed', 'compare' => '!=' ) ) ) ) as $p ) {
	$x = get_post_meta( $p->ID, '_hub', true );
	foreach ( array( $p->post_title, isset( $x['claimed']['name'] ) ? $x['claimed']['name'] : '' ) as $n ) {
		if ( '' !== $n ) {
			$by_name[ hub_fields_key( $n ) ] = $p->ID;
		}
	}
}

$updated = $added = 0;
foreach ( $data['classes'] as $c ) {
	$found = get_posts( array( 'post_type' => 'hub_class', 'name' => $c['slug'], 'post_status' => 'any', 'posts_per_page' => 1 ) );
	if ( $found ) {
		$id = $found[0]->ID;
		$r  = get_post_meta( $id, '_hub', true );
		$r  = is_array( $r ) ? $r : array();
		$r['card'] = $c['card'];
		$r['page'] = $c['page'];
		update_post_meta( $id, '_hub', $r );
		if ( '1' === (string) get_post_meta( $id, '_hub_fields', true ) ) {
			// Classes with fields (edited in wp-admin) read these, not the record: update the card and page fields only
			$v = array_intersect_key( hub_fields_class_from_record( $id, $r ), array_flip( array( 'class_page', 'class_card_order', 'class_card_text', 'class_card_when', 'class_card_price', 'class_card_age', 'class_card_provider' ) ) );
			hub_events_db_save_class_fields( $id, $v );
		}
		$updated++;
		continue;
	}
	$id = wp_insert_post( array(
		'post_type'      => 'hub_class',
		'post_status'    => 'publish',
		'post_name'      => $c['slug'],
		'post_title'     => $c['title'],
		'post_content'   => $c['desc'],
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'meta_input'     => array( '_hub' => $c, '_hub_order' => (int) $c['order'], '_hub_day' => null === $c['day'] ? '' : (int) $c['day'] ),
	), true );
	if ( is_wp_error( $id ) ) {
		echo 'Could not add ' . $c['slug'] . ': ' . $id->get_error_message() . "\n";
		continue;
	}
	$added++;
	hub_events_db_save_class_fields( $id, hub_fields_class_from_record( $id, $c ) );
	$biz = isset( $by_name[ hub_fields_key( $c['provider'] ) ] ) ? $by_name[ hub_fields_key( $c['provider'] ) ] : 0;
	if ( $biz ) {
		hub_events_db_set_class_business( $id, $biz );
		echo 'Added ' . html_entity_decode( $c['title'] ) . ', linked to ' . get_the_title( $biz ) . "\n";
	} else {
		echo 'Added ' . html_entity_decode( $c['title'] ) . " (no business on the Hub with the provider's name)\n";
	}
}
printf( "Classes: %d updated, %d added.\n", $updated, $added );
