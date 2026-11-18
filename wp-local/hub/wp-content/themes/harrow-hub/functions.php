<?php
/**
 * Harrow Online Hub theme. The Hub runs on its own WordPress at harrowonline.org/hub/, so home_url() is the Hub
 * homepage and every link is built from it (hub_url). Markup comes from the static prototype (tools/build_theme.py).
 */
defined( 'ABSPATH' ) || exit;

define( 'HUB_THEME_VERSION', '0.1.0' );

// Data layer: every page and API route gets its data from the modules' controllers (app/bootstrap.php explains)
require_once get_template_directory() . '/app/bootstrap.php';

// Theme setup and legacy page code (moving into app/modules/ and parts/ one module at a time)
require_once get_template_directory() . '/inc/page.php';
require_once get_template_directory() . '/inc/assets.php';
require_once get_template_directory() . '/inc/editor.php';
require_once get_template_directory() . '/inc/content-types.php';
require_once get_template_directory() . '/inc/no-comments.php';
require_once get_template_directory() . '/inc/render-common.php';
require_once get_template_directory() . '/inc/view.php';         // markup helpers for parts/ (work on the controllers' shapes)
require_once get_template_directory() . '/inc/acf-fields.php';     // ACF field groups and the Hub settings options page
require_once get_template_directory() . '/inc/business-acf.php';   // newsletter band (Hub settings > Newsletter)
require_once get_template_directory() . '/inc/acf-migrate.php';    // copies the imported data into the fields (CLI)
