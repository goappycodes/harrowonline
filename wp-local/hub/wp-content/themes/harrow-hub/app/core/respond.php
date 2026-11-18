<?php
/**
 * The two modes every controller function supports, and the one place that wraps its data for each.
 *
 *   $offers = hub_offers_list( $_GET );                      // HUB_WEB: plain arrays for a template
 *   return hub_offers_list( $req->get_params(), HUB_API );   // HUB_API: WP_REST_Response (JSON)
 */
defined( 'ABSPATH' ) || exit;

const HUB_WEB = 'web';
const HUB_API = 'api';

/**
 * Return a controller's data in the requested mode.
 *
 * @param array  $data    The result: always plain arrays, strings, numbers, booleans and null (JSON-safe).
 * @param string $mode    HUB_WEB or HUB_API.
 * @param int    $status  HTTP status for API mode.
 * @return array|WP_REST_Response
 */
function hub_respond( array $data, $mode = HUB_WEB, $status = 200 ) {
	hub_debug_record( $data, $mode );
	if ( HUB_API !== $mode ) {
		return $data;
	}
	$response = new WP_REST_Response( $data, $status );
	if ( isset( $data['total'] ) ) {
		$response->header( 'X-WP-Total', (string) (int) $data['total'] );
	}
	return $response;
}

/**
 * A "nothing to show" result: null in web mode (the template shows its 404), a WP_Error in API mode (JSON error
 * with that HTTP status).
 *
 * @return null|WP_Error
 */
function hub_respond_error( $code, $message, $mode = HUB_WEB, $status = 404 ) {
	hub_debug_record( array( 'error' => $code, 'message' => $message, 'status' => $status ), $mode );
	hub_debug_log( $code . ': ' . $message );
	if ( HUB_API !== $mode ) {
		return null;
	}
	return new WP_Error( $code, $message, array( 'status' => $status ) );
}
