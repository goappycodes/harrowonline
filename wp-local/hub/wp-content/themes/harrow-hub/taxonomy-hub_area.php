<?php
/** Area page: /area/{slug}/ */
defined( 'ABSPATH' ) || exit;
list( $hub_meta, $hub_body ) = hub_archive( 'area', get_queried_object() );
hub_set_page( $hub_meta );
get_header();
echo $hub_body; // phpcs:ignore WordPress.Security.EscapeOutput
get_footer();
