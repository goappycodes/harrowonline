<?php
/**
 * Data sources. URL: /data-sources/
 * The text is the page's own editor (Pages > Data sources and attribution); the title, intro, side card and the
 * Attribution box are its "Page text" box. Data: hub_pages_document( 'sources' ), hub_pages_text( 'sources' ).
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'body' => 'page-legal',
	'description' => 'Where Harrow Online Hub business listings come from: Overture Maps Foundation open data, its sources and licences, how listings were filtered, and how to correct or remove a listing.',
	'nav' => 'hub',
	'title' => 'Data sources and attribution',   // page title and description: Yoast SEO box, else these
) );
get_header();
get_template_part( 'parts/pages/document', null, array( 'page' => 'sources' ) );
get_footer();
