<?php
/**
 * Offer page: /offers/{slug}/ (live and ended; ended and sample offers are noindexed).
 *
 * Data: hub_offer_get() (app/modules/offers). Markup: parts/offers/single.php.
 */
defined( 'ABSPATH' ) || exit;

$hub_data = hub_offer_get( get_queried_object_id() );
if ( ! $hub_data ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	return;
}
$hub_o     = $hub_data['offer'];
$hub_b     = $hub_o['business'];
$hub_ended = 'ended' === $hub_o['status'];
$hub_end   = $hub_o['ends']['date'] ? date_create_immutable( $hub_o['ends']['date'] ) : null;

// Page title, description and robots
$hub_full = $hub_o['title'] . ' at ' . $hub_b['name'];
$hub_when = $hub_end ? ( $hub_ended ? 'Ended' : 'Ends' ) . ' ' . $hub_end->format( 'j F Y' ) . '.' : 'No end date.';
$hub_meta = array(
	'title'       => mb_strlen( $hub_full ) <= 50 ? $hub_full : $hub_o['title'],
	'description' => trim( preg_replace( '/\s+/u', ' ', $hub_full . ', ' . $hub_b['area']['name'] . '. ' . $hub_o['description'] . ' ' . $hub_when ) ),
	'nav'         => 'offers',
	'hubnav'      => 'offers',
	'body'        => 'page-offer',
);
if ( $hub_ended || $hub_b['sample'] ) {
	$hub_meta['robots'] = 'noindex, follow';
}
hub_set_page( $hub_meta );

// Structured data (schema.org Offer), printed in <head>
$hub_url = HUB_ORIGIN . '/offers/' . $hub_o['slug'] . '/';
$hub_ld  = array( '@type' => 'Offer', '@id' => $hub_url . '#offer', 'name' => $hub_o['title'], 'description' => $hub_o['description'], 'url' => $hub_url );
if ( $hub_end ) {
	$hub_ld['validThrough'] = $hub_end->format( 'Y-m-d' ) . 'T23:59:00';
}
$hub_ld['availability'] = 'https://schema.org/' . ( $hub_ended ? 'Discontinued' : 'InStock' );
$hub_ld['offeredBy']    = array( '@type' => 'LocalBusiness', 'name' => $hub_b['name'], 'url' => HUB_ORIGIN . '/business/' . $hub_b['slug'] . '/' );
hub_add_ld( hub_json( array( '@context' => 'https://schema.org', '@graph' => array( $hub_ld ) ) ) );

get_header();
get_template_part( 'parts/offers/single', null, $hub_data );
get_footer();
