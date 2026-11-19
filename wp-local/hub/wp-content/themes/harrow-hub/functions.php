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
require_once get_template_directory() . '/inc/newsletter.php';       // newsletter band (Hub settings > Newsletter)
require_once get_template_directory() . '/inc/fields-migrate.php';   // fills the business fields from the imported data (CLI)

// wp-admin: native meta boxes and settings page (no plugin): business, offer, event and class details, an offer's or
// class's business, Hub settings > Newsletter. Their script and styles are in admin/.
require_once get_template_directory() . '/inc/admin/fields-ui.php';
require_once get_template_directory() . '/inc/admin/business-box.php';
require_once get_template_directory() . '/inc/admin/link-box.php';
require_once get_template_directory() . '/inc/admin/listing-boxes.php';
require_once get_template_directory() . '/inc/admin/newsletter-page.php';
require_once get_template_directory() . '/inc/admin/home-box.php';       // Pages > Home: the homepage's text, links and sections
require_once get_template_directory() . '/inc/admin/page-text-box.php';  // Pages > What's On (and others): the page's text and panels
