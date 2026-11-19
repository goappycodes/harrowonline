<?php
/**
 * Accounts: REST routes (/wp-json/hub/v1/account/...). Each calls the controller in API mode.
 *
 *   GET  /account/me          the signed-in account (signed-out shape when not signed in)
 *   POST /account/sign-in     { log, pwd, rememberme }
 *   POST /account/register    { account_type, name, email, password, organisation, area, terms, join_newsletter, editions[] }
 *   POST /account/reset       { user_login }
 *   POST /account/sign-out
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/account/me', 'GET', function () {
	return hub_account_current( HUB_API );
} );
hub_route( '/account/sign-in', 'POST', function ( WP_REST_Request $req ) {
	return hub_account_sign_in( $req->get_params(), HUB_API );
} );
hub_route( '/account/register', 'POST', function ( WP_REST_Request $req ) {
	return hub_account_register( $req->get_params(), HUB_API );
} );
hub_route( '/account/reset', 'POST', function ( WP_REST_Request $req ) {
	return hub_account_reset_request( $req->get_params(), HUB_API );
} );
hub_route( '/account/sign-out', 'POST', function () {
	return hub_account_sign_out( HUB_API );
}, 'is_user_logged_in' );
