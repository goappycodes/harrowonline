<?php
/**
 * Areas (taxonomy hub_area): reading from WordPress.
 */
defined( 'ABSPATH' ) || exit;

/** Every area term (empty ones too), cached for the request. */
function hub_areas_db_all() {
	static $terms = null;
	if ( null === $terms ) {
		$terms = get_terms( array( 'taxonomy' => 'hub_area', 'hide_empty' => false ) );
		$terms = is_wp_error( $terms ) ? array() : $terms;
	}
	return $terms;
}

/** One area's term meta: its map point and directory order. */
function hub_areas_db_meta( WP_Term $term ) {
	$lat = get_term_meta( $term->term_id, 'hub_lat', true );
	$lng = get_term_meta( $term->term_id, 'hub_lng', true );
	return array(
		'lat'   => '' === $lat ? null : (float) $lat,
		'lng'   => '' === $lng ? null : (float) $lng,
		'order' => (int) get_term_meta( $term->term_id, 'hub_order', true ),
	);
}
