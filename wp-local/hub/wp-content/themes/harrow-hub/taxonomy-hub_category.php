<?php
/**
 * Category page: /category/{slug}/
 *
 * Data: hub_category_get(), hub_businesses_list(), hub_areas_list() (app/modules). Markup: parts/businesses/directory.php.
 */
defined( 'ABSPATH' ) || exit;

$hub_term  = hub_category_get( get_queried_object()->slug );
$hub_list  = hub_businesses_list( array( 'cat' => $hub_term['slug'] ) );
$hub_title = $hub_term['name'] . ' in Harrow';
$hub_lead  = '' !== $hub_term['intro'] ? $hub_term['intro']
	: number_format( $hub_list['total'] ) . ' ' . strtolower( $hub_term['name'] ) . ' listed across Harrow. Filter by area, or claim your listing free.';

// Indexed only with its own intro and at least five profiles worth indexing
$hub_meta = array( 'title' => $hub_title, 'description' => hub_view_excerpt( $hub_lead ), 'hubnav' => 'directory' );
if ( '' === $hub_term['intro'] || $hub_list['indexable_total'] < 5 ) {
	$hub_meta['robots'] = 'noindex, follow';
}
hub_set_page( $hub_meta );

get_header();
get_template_part( 'parts/businesses/directory', null, array(
	'kind'    => 'cat',
	'term'    => $hub_term,
	'title'   => $hub_title,
	'lead'    => $hub_lead,
	'list'    => $hub_list,
	'options' => hub_areas_list()['items'],
) );
get_footer();
