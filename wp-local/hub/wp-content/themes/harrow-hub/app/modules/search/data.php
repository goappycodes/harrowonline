<?php
/**
 * Search: the only file that reads or writes WordPress for the search index. The index is built from the other
 * modules (search/controller.php) and kept, already built, in one transient until something it lists changes:
 *   option    hub_search_version   goes up by one on every change (app/setup/search.php)
 *   transient hub_search_index     { key: "v{version}-{today}", index }
 */
defined( 'ABSPATH' ) || exit;

function hub_search_db_version() {
	return (int) get_option( 'hub_search_version', 1 );
}

/** Something the index lists changed: the next request builds it afresh. Once per request, however many changes. */
function hub_search_db_bump() {
	static $done = false;
	if ( ! $done ) {
		$done = true;
		update_option( 'hub_search_version', hub_search_db_version() + 1, true );
	}
}

/** The stored index if it was built for this key, else null. */
function hub_search_db_cached( $key ) {
	$c = get_transient( 'hub_search_index' );
	return is_array( $c ) && isset( $c['key'], $c['index'] ) && $key === $c['key'] ? $c['index'] : null;
}

function hub_search_db_store( $key, array $index ) {
	set_transient( 'hub_search_index', array( 'key' => $key, 'index' => $index ), DAY_IN_SECONDS );
}

/** The post types and taxonomies the index lists. */
function hub_search_db_lists_type( $post_type ) {
	return in_array( $post_type, array( 'hub_business', 'hub_offer', 'hub_event', 'hub_class' ), true );
}

function hub_search_db_lists_taxonomy( $taxonomy ) {
	return in_array( $taxonomy, array( 'hub_category', 'hub_area' ), true );
}

function hub_search_db_post_type( $post_id ) {
	return (string) get_post_type( (int) $post_id );
}
