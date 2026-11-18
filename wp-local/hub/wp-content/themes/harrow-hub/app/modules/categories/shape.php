<?php
/**
 * Categories: the one array shape the templates and the app get.
 *
 *   id, slug, name, url, icon, intro, business_count
 */
defined( 'ABSPATH' ) || exit;

function hub_category_shape( WP_Term $term ) {
	$meta = hub_categories_db_meta( $term );
	return array(
		'id'             => (int) $term->term_id,
		'slug'           => $term->slug,
		'name'           => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
		'url'            => hub_url( 'category/' . $term->slug . '/' ),
		'icon'           => $meta['icon'],
		'intro'          => $meta['intro'],
		'business_count' => (int) $term->count,
	);
}
