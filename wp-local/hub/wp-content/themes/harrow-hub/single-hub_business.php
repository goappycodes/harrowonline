<?php
/** Business profile: /business/{slug}/ (imported listings and claimed profiles). */
defined( 'ABSPATH' ) || exit;
$hub_x       = hub_business( get_queried_object() );
$hub_related = array();
foreach ( hub_business_query( array( 'hub_category' => $hub_x['cat'], 'hub_area' => $hub_x['area'] ), 4, $hub_x['_id'] ) as $p ) {
	$hub_related[] = hub_business( $p );
}
list( $hub_meta, $hub_body ) = hub_profile( $hub_x, $hub_related );
hub_set_page( $hub_meta );
get_header();
echo $hub_body; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped by inc/render-*.php
get_footer();
