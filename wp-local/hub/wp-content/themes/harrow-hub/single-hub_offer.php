<?php
/** Offer page: /offers/{slug}/ (live and ended). */
defined( 'ABSPATH' ) || exit;
$hub_o = hub_offer( get_queried_object() );
list( $hub_meta, $hub_body ) = hub_offer_page( hub_business( $hub_o['_biz'] ), $hub_o );
hub_set_page( $hub_meta );
get_header();
echo $hub_body; // phpcs:ignore WordPress.Security.EscapeOutput
get_footer();
