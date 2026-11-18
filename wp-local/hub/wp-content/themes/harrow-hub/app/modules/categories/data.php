<?php
/**
 * Categories (taxonomy hub_category): reading from WordPress.
 */
defined( 'ABSPATH' ) || exit;

/** Every category term (empty ones too), cached for the request. */
function hub_categories_db_all() {
	static $terms = null;
	if ( null === $terms ) {
		$terms = get_terms( array( 'taxonomy' => 'hub_category', 'hide_empty' => false ) );
		$terms = is_wp_error( $terms ) ? array() : $terms;
	}
	return $terms;
}

/** One category's term meta: its icon, intro and directory order. */
function hub_categories_db_meta( WP_Term $term ) {
	return array(
		'icon'  => (string) get_term_meta( $term->term_id, 'hub_icon', true ),
		'intro' => (string) get_term_meta( $term->term_id, 'hub_intro', true ),
		'order' => (int) get_term_meta( $term->term_id, 'hub_order', true ),
	);
}
