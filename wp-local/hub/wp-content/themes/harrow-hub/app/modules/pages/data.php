<?php
/**
 * Page text (the What's On page, and any page added to HUB_PAGES): the only file that reads or writes it.
 *
 * Every field is plain post meta on the page's WordPress Page ({page}_*), edited in wp-admin's "Page text" box
 * (inc/admin/page-text-box.php). Lists are arrays in one meta key each. An empty field means "use the default"
 * (rules.php); the "…_hide" switches hide a section.
 */
defined( 'ABSPATH' ) || exit;

/** A WordPress Page's ID from its path ("events"), or 0. */
function hub_pages_db_page_id( $path ) {
	$page = get_page_by_path( $path, OBJECT, 'page' );
	return $page ? (int) $page->ID : 0;
}

/** The stored value of each key ('' when not set). */
function hub_pages_db_fields( $page_id, array $keys ) {
	$out = array();
	foreach ( $keys as $k ) {
		$out[ $k ] = $page_id ? get_post_meta( $page_id, $k, true ) : '';
	}
	return $out;
}

/** Save the fields (already cleaned by the wp-admin box). Empty values are removed rather than stored. */
function hub_pages_db_save_fields( $page_id, array $keys, array $values ) {
	if ( $page_id ) {
		hub_db_save_meta( $page_id, $keys, $values );
	}
}
