<?php
/** Category page: /category/{slug}/ */
defined( 'ABSPATH' ) || exit;
list( $hub_meta, $hub_body ) = hub_archive( 'cat', get_queried_object() );
hub_set_page( $hub_meta );
get_header();
echo $hub_body; // phpcs:ignore WordPress.Security.EscapeOutput
get_footer();
