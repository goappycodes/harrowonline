<?php
/**
 * Submissions API (namespace hub/v1), signed-in accounts:
 *   GET  /submissions/{offer|event|class}?id=   can this account add one, and its saved values when editing
 *   POST /submissions/{offer|event|class}       { action: draft | submit, id, ...the form's fields } (+ image file)
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/submissions/(?P<kind>offer|event|class)', 'GET', function ( WP_REST_Request $req ) {
	return hub_submission_page( (string) $req['kind'], $req->get_params(), HUB_API );
}, 'is_user_logged_in' );
hub_route( '/submissions/(?P<kind>offer|event|class)', 'POST', function ( WP_REST_Request $req ) {
	return hub_submission_save( (string) $req['kind'], $req->get_params(), $req->get_file_params(), HUB_API );
}, 'is_user_logged_in' );
