<?php
/** Fallback (required by WordPress). The Hub has no blog; anything unmatched shows the 404 page. */
defined( 'ABSPATH' ) || exit;
status_header( 404 );
require get_template_directory() . '/page-templates/404.php';
