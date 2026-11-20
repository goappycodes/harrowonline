<?php
/**
 * Yoast SEO and the theme's titles. Each template's hub_set_page() title and description stay the default; a title
 * or description typed in a page's Yoast SEO box wins. So pages look the same in search results until someone fills
 * in Yoast. Without Yoast, inc/page.php prints the theme's title, description and robots itself.
 */
defined( 'ABSPATH' ) || exit;

/** The theme's full title for this page ("… | Harrow Online Hub"), or ''. */
function hub_seo_title() {
	$t = (string) hub_page( 'title' );
	if ( '' === $t ) {
		return '';
	}
	return false === strpos( $t, 'Harrow Online' ) ? $t . ' | Harrow Online Hub' : $t;
}

/** The theme's description for this page, as plain text, or ''. */
function hub_seo_description() {
	return wp_strip_all_tags( html_entity_decode( (string) hub_page( 'description' ) ) );
}

/** True when the page's Yoast box has no value for $field ("title", "description", "open_graph_title"...). */
function _hub_seo_empty( $presentation, $field ) {
	return is_object( $presentation ) && isset( $presentation->model ) && empty( $presentation->model->{$field} );
}

// Search results: <title> and meta description
add_filter( 'wpseo_title', function ( $title, $presentation = null ) {
	return _hub_seo_empty( $presentation, 'title' ) && hub_seo_title() ? hub_seo_title() : $title;
}, 10, 2 );
add_filter( 'wpseo_metadesc', function ( $desc, $presentation = null ) {
	return _hub_seo_empty( $presentation, 'description' ) && hub_seo_description() ? hub_seo_description() : $desc;
}, 10, 2 );

// Shared links (Facebook, WhatsApp, X): the same title and description, unless Yoast has its own for them
add_filter( 'wpseo_opengraph_title', function ( $title, $presentation = null ) {
	return _hub_seo_empty( $presentation, 'open_graph_title' ) && _hub_seo_empty( $presentation, 'title' ) && hub_seo_title() ? hub_seo_title() : $title;
}, 10, 2 );
add_filter( 'wpseo_opengraph_desc', function ( $desc, $presentation = null ) {
	return _hub_seo_empty( $presentation, 'open_graph_description' ) && _hub_seo_empty( $presentation, 'description' ) && hub_seo_description() ? hub_seo_description() : $desc;
}, 10, 2 );

// Structured data: the page's name and description in Yoast's schema graph
add_filter( 'wpseo_schema_webpage', function ( $data, $context = null ) {
	$own = is_object( $context ) && isset( $context->indexable ) ? $context->indexable : null;
	if ( $own && empty( $own->title ) && hub_seo_title() ) {
		$data['name'] = hub_seo_title();
	}
	if ( $own && empty( $own->description ) && hub_seo_description() ) {
		$data['description'] = hub_seo_description();
	}
	return $data;
}, 10, 2 );

// wp-admin: the Yoast SEO box goes below the Hub's own boxes
add_filter( 'wpseo_metabox_prio', function () {
	return 'low';
} );

/**
 * Admin lists: Yoast's Readability score and Outgoing internal links columns start hidden (staff can still turn them
 * on in Screen Options). Only for people who haven't chosen their own columns on that screen yet.
 */
add_filter( 'default_hidden_columns', function ( $hidden, $screen ) {
	if ( $screen instanceof WP_Screen && 'edit' === $screen->base ) {
		$hidden = array_merge( (array) $hidden, array( 'wpseo-score-readability', 'wpseo-links' ) );
	}
	return $hidden;
}, 10, 2 );
