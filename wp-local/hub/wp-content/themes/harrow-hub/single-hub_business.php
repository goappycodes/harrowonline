<?php
/**
 * Business page: /business/{slug}/ (claimed profiles and imported listings).
 *
 * Data: hub_business_get() (app/modules/businesses). Markup: parts/businesses/profile.php (claimed) and
 * parts/businesses/profile-imported.php.
 */
defined( 'ABSPATH' ) || exit;

$hub_data = hub_business_get( get_queried_object_id() );
if ( ! $hub_data ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	return;
}
$hub_b = $hub_data['business'];

// Page title, description and robots
if ( $hub_b['claimed'] ) {
	$hub_meta = array(
		'title'       => $hub_b['seo_title'] ? $hub_b['seo_title'] : $hub_b['name'] . ' in ' . $hub_b['area']['name'],
		'description' => $hub_b['sample']
			? $hub_b['name'] . ' (' . $hub_b['subcategory'] . ') in ' . $hub_b['area']['name'] . ', Harrow: opening hours, photos, Local Offers, events and contact details on Harrow Online Hub.'
			: hub_view_excerpt( $hub_b['tagline'] ),
		'hubnav'      => 'directory',
	);
	if ( ! $hub_b['indexable'] ) {
		$hub_meta['robots'] = 'noindex, follow';
	}
} else {
	$hub_meta = array(
		'title'       => $hub_b['name'] . ' in ' . $hub_b['area']['name'],
		'description' => $hub_b['name'] . ', ' . strtolower( $hub_b['category']['name'] ) . ' in ' . $hub_b['area']['name'] . ', Harrow. Address, phone and directions on Harrow Online Hub.',
		'robots'      => 'noindex, follow',
		'hubnav'      => 'directory',
	);
}
hub_set_page( $hub_meta );

get_header();
get_template_part( $hub_b['claimed'] ? 'parts/businesses/profile' : 'parts/businesses/profile-imported', null, $hub_data );
get_footer();
