<?php
/**
 * Claims API (namespace hub/v1):
 *   GET  /claims/mine                    the signed-in account's latest claim
 *   GET  /claims/check?listing={slug}    can this account claim it? (state, message)
 *   POST /claims                         { listing, method, email_local | ask_for, call_times[] | doc_type + document (file), role, phone, note, declaration }
 *   POST /claims/{id}/approve            staff
 *   POST /claims/{id}/reject  { note }   staff
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/claims/mine', 'GET', function () {
	return hub_claim_mine( HUB_API );
}, 'is_user_logged_in' );
hub_route( '/claims/check', 'GET', function ( WP_REST_Request $req ) {
	return hub_claim_page( $req->get_params(), HUB_API );
}, 'is_user_logged_in' );
hub_route( '/claims', 'POST', function ( WP_REST_Request $req ) {
	return hub_claim_submit( $req->get_params(), $req->get_file_params(), HUB_API );
}, 'is_user_logged_in' );
hub_route( '/claims/(?P<id>\d+)/approve', 'POST', function ( WP_REST_Request $req ) {
	return hub_claim_approve( (int) $req['id'], HUB_API );
}, function () { return current_user_can( 'edit_users' ); } );
hub_route( '/claims/(?P<id>\d+)/reject', 'POST', function ( WP_REST_Request $req ) {
	return hub_claim_reject( (int) $req['id'], (string) $req->get_param( 'note' ), HUB_API );
}, function () { return current_user_can( 'edit_users' ); } );
