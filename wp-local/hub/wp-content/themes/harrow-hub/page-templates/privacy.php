<?php
/**
 * Privacy notice. URL: /privacy/
 * The text is the page's own editor (Pages > Privacy notice); the title, intro, side card and draft note are its
 * "Page text" box. Data: hub_pages_document( 'privacy' ), hub_pages_text( 'privacy' ) (app/modules/pages).
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-legal',
	'description' => 'How Harrow Online Hub collects and uses personal information: accounts, business claims, newsletter sign-ups, payments and cookies, and your rights.',
	'nav' => 'hub',
	'title' => 'Privacy notice',   // page title and description: Yoast SEO box, else these
) );
get_header();
get_template_part( 'parts/pages/document', null, array( 'page' => 'privacy' ) );
get_footer();
