<?php
/**
 * Areas: the one array shape the templates and the app get.
 *
 *   id, slug, name, url, lat, lng, business_count
 */
defined( 'ABSPATH' ) || exit;

function hub_area_shape( WP_Term $term ) {
	$meta = hub_areas_db_meta( $term );
	return array(
		'id'             => (int) $term->term_id,
		'slug'           => $term->slug,
		'name'           => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
		'url'            => hub_url( 'area/' . $term->slug . '/' ),
		'lat'            => $meta['lat'],
		'lng'            => $meta['lng'],
		'business_count' => (int) $term->count,
	);
}
