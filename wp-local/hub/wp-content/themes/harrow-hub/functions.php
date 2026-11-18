<?php
/**
 * Harrow Online Hub theme. The Hub runs on its own WordPress at harrowonline.org/hub/, so home_url() is the Hub
 * homepage and every link is built from it (hub_url). Markup comes from the static prototype (tools/build_theme.py).
 */
defined( 'ABSPATH' ) || exit;

define( 'HUB_THEME_VERSION', '0.1.0' );

require_once get_template_directory() . '/inc/page.php';
require_once get_template_directory() . '/inc/assets.php';
require_once get_template_directory() . '/inc/editor.php';
require_once get_template_directory() . '/inc/content-types.php';
require_once get_template_directory() . '/inc/no-comments.php';
require_once get_template_directory() . '/inc/render-common.php';
require_once get_template_directory() . '/inc/render-business.php';
require_once get_template_directory() . '/inc/render-profile.php';
require_once get_template_directory() . '/inc/render-events.php';
require_once get_template_directory() . '/inc/render-offers.php';
