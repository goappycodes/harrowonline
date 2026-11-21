<?php
/**
 * Search: the rules (plain PHP, no WordPress).
 */
defined( 'ABSPATH' ) || exit;

/** Post meta that changes without changing anything the index lists (editing locks): no rebuild for these. */
const HUB_SEARCH_IGNORED_META = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_encloseme', '_pingme' );

/** The stored index's key: it's built again when the content changes (version) or the day does (events, offers). */
function hub_search_rule_key( $version, $today ) {
	return 'v' . (int) $version . '-' . $today;
}

/** A row without its empty values ('', 0, false, null), to keep the index small. $keep: keys kept even when 0. */
function hub_search_rule_slim( array $row, array $keep = array() ) {
	return array_filter( $row, function ( $v, $k ) use ( $keep ) {
		return in_array( $k, $keep, true ) || ! ( '' === $v || 0 === $v || false === $v || null === $v );
	}, ARRAY_FILTER_USE_BOTH );
}

/** "http://site/hub/events/x/" -> "/events/x/": hub.js puts the Hub's own base path in front. */
function hub_search_rule_path( $url, $home ) {
	$home = rtrim( (string) $home, '/' );
	return 0 === strpos( (string) $url, $home ) ? substr( (string) $url, strlen( $home ) ) : (string) $url;
}

/** Whether this meta key's change could change the index. */
function hub_search_rule_meta_matters( $meta_key ) {
	return ! in_array( (string) $meta_key, HUB_SEARCH_IGNORED_META, true );
}
