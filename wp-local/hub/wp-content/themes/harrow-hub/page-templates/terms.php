<?php
/**
 * Terms of use. URL: /terms/
 * The text is the page's own editor (Pages > Terms of use); the title, intro, side card and draft note are its
 * "Page text" box. Data: hub_pages_document( 'terms' ), hub_pages_text( 'terms' ) (app/modules/pages).
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-legal',
	'description' => 'Terms for using Harrow Online Hub as a reader or business member: listings, claims, offers and events, plans and billing, cancellations and liability.',
	'nav' => 'hub',
	'title' => 'Terms of use',   // page title and description: Yoast SEO box, else these
) );
get_header();
get_template_part( 'parts/pages/document', null, array( 'page' => 'terms' ) );
get_footer();
