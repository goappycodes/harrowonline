<?php
/**
 * Page text: the editable wording of the Hub's listing pages (the What's On page for now; see rules.php to add one).
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
