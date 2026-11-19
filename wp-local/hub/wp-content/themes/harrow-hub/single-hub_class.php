<?php
/**
 * Weekly class page: /events/{slug}/ (classes not run by a real business are noindexed).
 *
 * Data: hub_class_get() (app/modules/events). Markup: parts/events/class-single.php.
 */
defined( 'ABSPATH' ) || exit;

$hub_data = hub_class_get( get_queried_object_id() );
if ( ! $hub_data ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	return;
}
$hub_c = $hub_data['class'];
if ( ! $hub_c['page'] ) {
	// Listed on the classes page only (a club's teams with no fixed weekly time): its business's page has the details
	wp_safe_redirect( $hub_c['url'] ? $hub_c['url'] : hub_url( 'events/classes/' ), 301 );
	exit;
}

// Page title, description and robots
$hub_meta = array(
	'title'       => $hub_c['sample'] ? $hub_c['title'] . ' in ' . $hub_c['area']['name'] . ', every ' . $hub_c['day_name'] : $hub_c['title'],
	'description' => $hub_c['title'] . ': every ' . $hub_c['day_name'] . ', ' . $hub_c['time'] . ' at ' . $hub_c['venue'] . ', ' . $hub_c['area']['name'] . '. '
		. $hub_c['price'] . ', ' . $hub_c['age'] . '. ' . $hub_c['description'],
	'nav'         => 'classes',
	'hubnav'      => 'events',
	'body'        => 'page-event',
);
if ( $hub_c['sample'] ) {
	$hub_meta['robots'] = 'noindex, follow';
}
hub_set_page( $hub_meta );

// Structured data (schema.org Event with a weekly schedule), printed in <head>
$hub_url      = HUB_ORIGIN . '/events/' . $hub_c['slug'] . '/';
$hub_schedule = array( '@type' => 'Schedule', 'repeatFrequency' => 'P1W', 'byDay' => 'https://schema.org/' . $hub_c['day_name'] );
if ( $hub_c['start_time'] ) {
	$hub_schedule['startTime'] = $hub_c['start_time'] . ':00';
}
$hub_schedule['scheduleTimezone'] = 'Europe/London';
$hub_address = array( '@type' => 'PostalAddress', 'addressLocality' => 'Harrow', 'addressCountry' => 'GB' );
if ( $hub_c['address'] ) {
	$hub_address['streetAddress'] = $hub_c['address']['street'];
	$hub_address['postalCode']    = $hub_c['address']['postcode'];
}
$hub_ld = array(
	'@type'               => 'Event',
	'@id'                 => $hub_url . '#event',
	'name'                => $hub_c['title'],
	'description'         => '' !== $hub_c['description'] ? $hub_c['description'] : $hub_c['title'],
	'url'                 => $hub_url,
	'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
	'eventStatus'         => 'https://schema.org/EventScheduled',
	'startDate'           => '2026-11-01',
	'eventSchedule'       => $hub_schedule,
	'location'            => array( '@type' => 'Place', 'name' => $hub_c['venue'], 'address' => $hub_address ),
	'isAccessibleForFree' => $hub_c['free'],
	'publisher'           => array( '@id' => 'https://harrowonline.org/#organization' ),
);
hub_add_ld( hub_json( array( '@context' => 'https://schema.org', '@graph' => array( $hub_ld ) ) ) );

get_header();
get_template_part( 'parts/events/class-single', null, $hub_data );
get_footer();
