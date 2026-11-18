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
