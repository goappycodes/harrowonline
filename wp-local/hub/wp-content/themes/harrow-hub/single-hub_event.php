<?php
/**
 * Event page: /events/{slug}/ (past and sample events are noindexed).
 *
 * Data: hub_event_get() (app/modules/events). Markup: parts/events/single.php.
 */
defined( 'ABSPATH' ) || exit;

$hub_data = hub_event_get( get_queried_object_id() );
if ( ! $hub_data ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	return;
}
$hub_e = $hub_data['event'];
$hub_d = date_create_immutable( $hub_e['date'] );

// Page title, description and robots
$hub_meta = array(
	'title'       => $hub_e['title'] . ', ' . $hub_d->format( 'j M Y' ),
	'description' => $hub_e['title'] . ' at ' . $hub_e['venue'] . ', ' . $hub_e['area']['name'] . ', ' . hub_events_rule_long_date( $hub_d ) . ' ' . $hub_d->format( 'Y' )
		. ', ' . $hub_e['time'] . '. ' . $hub_e['price'] . ', ' . $hub_e['age'] . '. ' . $hub_e['description'],
	'nav'         => 'events',
	'hubnav'      => 'events',
	'body'        => 'page-event',
);
if ( $hub_e['past'] || $hub_e['sample'] ) {
	$hub_meta['robots'] = 'noindex, follow';
}
hub_set_page( $hub_meta );

// Structured data (schema.org Event), printed in <head>
$hub_url = HUB_ORIGIN . '/events/' . $hub_e['slug'] . '/';
$hub_ld  = array(
	'@type'               => 'Event',
	'@id'                 => $hub_url . '#event',
	'name'                => $hub_e['title'],
	'description'         => '' !== $hub_e['description'] ? $hub_e['description'] : $hub_e['title'],
	'url'                 => $hub_url,
	'image'               => array( HUB_ORIGIN . '/assets/img/og-default.png' ),
	'startDate'           => $hub_e['start'] . hub_view_uk_offset( $hub_e['start'] ),
	'endDate'             => $hub_e['end'] . hub_view_uk_offset( $hub_e['end'] ),
	'eventStatus'         => 'https://schema.org/EventScheduled',
	'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
	'isAccessibleForFree' => $hub_e['free'],
	'location'            => array(
		'@type'   => 'Place',
		'name'    => $hub_e['venue'],
		'address' => array( '@type' => 'PostalAddress', 'streetAddress' => $hub_e['venue'], 'addressLocality' => 'Harrow', 'addressRegion' => 'London', 'addressCountry' => 'GB' ),
	),
	'publisher'           => array( '@id' => 'https://harrowonline.org/#organization' ),
);
if ( $hub_data['organiser'] ) {
	$hub_ld['organizer'] = array( '@type' => 'Organization', 'name' => $hub_data['organiser']['name'], 'url' => HUB_ORIGIN . '/business/' . $hub_data['organiser']['slug'] . '/' );
}
hub_add_ld( hub_json( array( '@context' => 'https://schema.org', '@graph' => array( $hub_ld ) ) ) );

get_header();
get_template_part( 'parts/events/single', null, $hub_data );
get_footer();
