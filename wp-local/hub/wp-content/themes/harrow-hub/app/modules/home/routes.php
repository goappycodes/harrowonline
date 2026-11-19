<?php
/**
 * Homepage API:
 *   GET /wp-json/hub/v1/home   everything the homepage shows (its text and links, and the events, offers, businesses,
 *                              categories and areas on it)
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/home', 'GET', function () {
	return hub_home_page( HUB_API );
} );
