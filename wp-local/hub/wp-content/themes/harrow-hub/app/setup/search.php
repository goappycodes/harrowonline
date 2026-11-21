<?php
/**
 * The search index (app/modules/search) is built again after any change to what it lists: a business, offer, event
 * or class saved, trashed, restored or deleted; its fields (plan changes from billing and claims included) or its
 * category or area changed; a category or area added, edited or deleted. The module ignores everything else.
 */
defined( 'ABSPATH' ) || exit;

foreach ( array( 'save_post', 'deleted_post', 'trashed_post', 'untrashed_post', 'set_object_terms' ) as $hub_hook ) {
	add_action( $hub_hook, function ( $post_id ) {
		hub_search_index_changed( (int) $post_id );
	} );
}
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hub_hook ) {
	add_action( $hub_hook, function ( $meta_id, $post_id, $meta_key ) {
		hub_search_index_changed( (int) $post_id, '', (string) $meta_key );
	}, 10, 3 );
}
foreach ( array( 'created_term', 'edited_term', 'delete_term' ) as $hub_hook ) {
	add_action( $hub_hook, function ( $term_id, $tt_id, $taxonomy ) {
		hub_search_index_changed( 0, (string) $taxonomy );
	}, 10, 3 );
}
foreach ( array( 'added_term_meta', 'updated_term_meta', 'deleted_term_meta' ) as $hub_hook ) {   // a category's icon
	add_action( $hub_hook, function ( $meta_id, $term_id ) {
		$term = get_term( (int) $term_id );
		if ( $term && ! is_wp_error( $term ) ) {
			hub_search_index_changed( 0, $term->taxonomy );
		}
	}, 10, 2 );
}
unset( $hub_hook );
