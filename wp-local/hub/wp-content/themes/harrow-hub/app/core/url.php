<?php
/**
 * URLs in the data. Everything a controller returns uses full, unescaped URLs (the app needs absolute links);
 * templates escape them when they print them.
 */
defined( 'ABSPATH' ) || exit;

/** Image URL from a stored path: a full URL (media library) as it is, a prototype path ("/assets/img/x.jpg") as a theme URL. */
function hub_media_url( $path ) {
	$path = (string) $path;
	if ( '' === $path || preg_match( '#^https?://#', $path ) ) {
		return $path;
	}
	return hub_asset( preg_replace( '#^/assets/#', '', $path ) );
}

/**
 * A link as typed in a wp-admin field -> full URL. "events/classes/" or "/hub/events/" is a Hub page (a query such as
 * "events/?when=today" is kept); a full web address, "mailto:", "tel:" or "#…" is used as it is.
 */
function hub_link( $link ) {
	$link = trim( (string) $link );
	if ( '' === $link || preg_match( '#^(https?:|mailto:|tel:|\#)#', $link ) ) {
		return $link;
	}
	$path = ltrim( $link, '/' );
	$base = trim( (string) wp_parse_url( hub_url(), PHP_URL_PATH ), '/' );   // "hub"
	if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	return hub_url( $path );
}

/** Links typed as Hub paths in HTML (href="/hub/privacy/") as full addresses for this server. */
function hub_html_links( $html ) {
	return preg_replace_callback( '/href="(\/[^"]*)"/', function ( $m ) {
		return 'href="' . esc_url( hub_link( html_entity_decode( $m[1] ) ) ) . '"';
	}, (string) $html );
}
