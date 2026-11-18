<?php
/**
 * Area page: /area/{slug}/
 *
 * Data: hub_area_get(), hub_businesses_list(), hub_categories_list() (app/modules). Markup: parts/businesses/directory.php.
 */
defined( 'ABSPATH' ) || exit;

$hub_term  = hub_area_get( get_queried_object()->slug );
$hub_list  = hub_businesses_list( array( 'area' => $hub_term['slug'] ) );
$hub_title = 'Businesses in ' . $hub_term['name'];
$hub_lead  = number_format( $hub_list['total'] ) . ' local businesses in ' . $hub_term['name'] . ': shops, places to eat, trades and services. Browse by category below.';

hub_set_page( array( 'title' => $hub_title, 'description' => hub_view_excerpt( $hub_lead ), 'hubnav' => 'directory', 'robots' => 'noindex, follow' ) );

get_header();
get_template_part( 'parts/businesses/directory', null, array(
	'kind'    => 'area',
	'term'    => $hub_term,
	'title'   => $hub_title,
	'lead'    => $hub_lead,
	'list'    => $hub_list,
	'options' => hub_categories_list()['items'],
) );
get_footer();
