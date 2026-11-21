<?php
/**
 * Submissions API (namespace hub/v1), signed-in accounts:
 *   GET  /submissions/{offer|event|class}?id=   can this account add one, and its saved values when editing
 *   POST /submissions/{offer|event|class}       { action: draft | submit, id, ...the form's fields } (+ image file)
 *   GET  /submissions/{offer|event|class}/list?status=   the account's list: every one it sent, and what's on its listing
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/submissions/(?P<kind>offer|event|class)/list', 'GET', function ( WP_REST_Request $req ) {
	return hub_submissions_list( (string) $req['kind'], $req->get_params(), HUB_API );
}, 'is_user_logged_in' );
hub_route( '/submissions/(?P<kind>offer|event|class)', 'GET', function ( WP_REST_Request $req ) {
	return hub_submission_page( (string) $req['kind'], $req->get_params(), HUB_API );
}, 'is_user_logged_in' );
hub_route( '/submissions/(?P<kind>offer|event|class)', 'POST', function ( WP_REST_Request $req ) {
	return hub_submission_save( (string) $req['kind'], $req->get_params(), $req->get_file_params(), HUB_API );
}, 'is_user_logged_in' );
