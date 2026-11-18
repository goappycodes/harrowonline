<?php
/**
 * What's On API:
 *   GET /wp-json/hub/v1/events?from=&to=&cat=&area=&featured=1&limit=&offset=   one-off events
 *   GET /wp-json/hub/v1/events/whats-on?from=                                  the What's On list (7 days)
 *   GET /wp-json/hub/v1/events/month?month=2026-11                             the month calendar
 *   GET /wp-json/hub/v1/events/weekend?limit=4                                 this weekend's events
 *   GET /wp-json/hub/v1/events/{id or slug}                                    an event page
 *   GET /wp-json/hub/v1/classes?type=&area=&day=&age=                          weekly classes
 *   GET /wp-json/hub/v1/classes/{id or slug}                                   a class page
 */
defined( 'ABSPATH' ) || exit;

hub_route( '/events', 'GET', function ( WP_REST_Request $req ) {
	return hub_events_list( $req->get_params(), HUB_API );
} );

hub_route( '/events/whats-on', 'GET', function ( WP_REST_Request $req ) {
	return hub_events_whats_on( $req->get_params(), HUB_API );
} );

hub_route( '/events/month', 'GET', function ( WP_REST_Request $req ) {
	return hub_events_month( (string) $req->get_param( 'month' ), HUB_API );
} );

hub_route( '/events/weekend', 'GET', function ( WP_REST_Request $req ) {
	return hub_events_weekend( hub_param_int( $req->get_params(), 'limit', 4, 1, 50 ), HUB_API );
} );

hub_route( '/events/(?P<event>(?!whats-on$|month$|weekend$)[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_event_get( $req['event'], HUB_API );
} );

hub_route( '/classes', 'GET', function ( WP_REST_Request $req ) {
	return hub_classes_list( $req->get_params(), HUB_API );
} );

hub_route( '/classes/(?P<class>[a-z0-9-]+)', 'GET', function ( WP_REST_Request $req ) {
	return hub_class_get( $req['class'], HUB_API );
} );
