<?php
/** Weekly class page: /events/{slug}/ */
defined( 'ABSPATH' ) || exit;
list( $hub_meta, $hub_body ) = hub_class_page( hub_class( get_queried_object() ) );
hub_set_page( $hub_meta );
get_header();
echo $hub_body; // phpcs:ignore WordPress.Security.EscapeOutput
get_footer();
