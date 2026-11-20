<?php
/**
 * Page text: the editable wording of the Hub's pages (What's On, Classes and activities, Local Offers, Pricing, Privacy,
 * Terms; see rules.php to add one).
 */
defined( 'ABSPATH' ) || exit;

/**
 * A page's text, for its template and the app.
 *
 * @param string $page A key of HUB_PAGES ("events").
 * @return array|WP_REST_Response|WP_Error|null  every field of the page (shape.php)
 */
function hub_pages_text( $page, $mode = HUB_WEB ) {
	if ( ! isset( HUB_PAGES[ $page ] ) ) {
		return hub_respond_error( 'hub_page_unknown', 'That page has no editable text.', $mode );
	}
	$id = hub_pages_page_id( $page );
	return hub_respond( hub_pages_shape( $page, $id ? hub_pages_db_fields( $id, array_keys( hub_pages_rule_defaults( $page ) ) ) : array() ), $mode );
}

/** The WordPress Page's ID for a page key (0 when that Page does not exist). */
function hub_pages_page_id( $page ) {
	return isset( HUB_PAGES[ $page ] ) ? hub_pages_db_page_id( HUB_PAGES[ $page ] ) : 0;
}

/** Which page key a WordPress Page is ("events"), or '' when it has no editable text. */
function hub_pages_for_post( $post_id ) {
	foreach ( array_keys( HUB_PAGES ) as $page ) {
		if ( $post_id && (int) $post_id === hub_pages_page_id( $page ) ) {
			return $page;
		}
	}
	return '';
}

/** A page's fields for the wp-admin box: what is saved, with the default for anything not saved. */
function hub_pages_fields( $page ) {
	$defaults = hub_pages_rule_defaults( $page );
	$id       = hub_pages_page_id( $page );
	$saved    = $id ? hub_pages_db_fields( $id, array_keys( $defaults ) ) : array();
	$out      = array();
	foreach ( $defaults as $k => $default ) {
		$v         = isset( $saved[ $k ] ) ? $saved[ $k ] : '';
		$out[ $k ] = ( '' === $v || array() === $v ) ? $default : $v;
	}
	return $out;
}

/** Save a page's fields (already cleaned by the wp-admin box). */
function hub_pages_save_fields( $page, array $values ) {
	hub_pages_db_save_fields( hub_pages_page_id( $page ), array_keys( hub_pages_rule_defaults( $page ) ), $values );
}

/**
 * A document page (privacy, terms): its text from the page's editor (or content/{page}.html while it is empty), as the
 * page shows it, and the "On this page" list from its headings.
 * @return array|WP_REST_Response { html, toc: [ [ id, label ] ] }
 */
function hub_pages_document( $page, $mode = HUB_WEB ) {
	$raw = hub_pages_db_body( hub_pages_page_id( $page ) );
	if ( '' === trim( $raw ) ) {
		$raw = hub_pages_rule_default_body( $page );
	}
	list( $html, $toc ) = hub_pages_rule_toc( hub_html_links( apply_filters( 'the_content', $raw ) ) );
	return hub_respond( array( 'html' => $html, 'toc' => $toc ), $mode );
}

/**
 * Pricing: the page's text with the prices filled in from the billing module.
 * @return array|WP_REST_Response { text, plans: { featured|partner: { name, monthly, annual, annual_per_month, note_monthly, note_annual } }, event_price, compare }
 */
function hub_pages_pricing( $mode = HUB_WEB ) {
	$t     = hub_pages_text( 'pricing' );
	$cat   = hub_billing_catalogue();   // billing module
	$event = '';
	foreach ( $cat['items'] as $i ) {
		if ( 'event_listing' === $i['key'] ) {
			$event = $i['price'];
		}
	}
	$vars  = array( '{event_price}' => $event );
	$plans = array();
	foreach ( $cat['plans'] as $p ) {
		$vars[ '{' . $p['key'] . '_monthly}' ] = $p['monthly'];
		$vars[ '{' . $p['key'] . '_annual}' ]  = $p['annual'];
		$plans[ $p['key'] ]                    = $p;
	}
	$t = hub_pages_rule_fill( $t, $vars );
	foreach ( $plans as $key => $p ) {
		$plans[ $key ]['note_monthly'] = strtr( $t['pricing_paid_note_monthly'], array( '{annual}' => $p['annual'], '{per_month}' => $p['annual_per_month'] ) );
		$plans[ $key ]['note_annual']  = strtr( $t['pricing_paid_note_annual'], array( '{annual}' => $p['annual'], '{per_month}' => $p['annual_per_month'] ) );
	}
	return hub_respond( array( 'text' => $t, 'plans' => $plans, 'event_price' => $event, 'compare' => hub_pages_rule_compare( $t['pricing_compare_rows'] ) ), $mode );
}

/** Document pages with an empty editor get their starting text (content/{page}.html). Nothing is overwritten. */
function hub_pages_setup_bodies() {
	foreach ( HUB_PAGES_DOCUMENTS as $page ) {
		$id = hub_pages_page_id( $page );
		if ( $id && '' === trim( hub_pages_db_body( $id ) ) ) {
			hub_pages_db_set_body( $id, hub_pages_rule_default_body( $page ) );
		}
	}
}
