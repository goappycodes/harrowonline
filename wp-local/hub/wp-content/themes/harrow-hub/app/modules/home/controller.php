<?php
/**
 * Homepage: everything the front page shows, from its fields (data.php) and the other modules.
 */
defined( 'ABSPATH' ) || exit;

/**
 * @return array|WP_REST_Response {
 *     settings:      the homepage's text, links and switches (shape.php)
 *     weekend:       { label ("Friday 13 to Sunday 15 November"), events: event[] (2) }
 *     weekend_offer: offer | null   the picked offer when it is live, otherwise the first live offer
 *     offers:        offer[]        More Local Offers (6, without the weekend's offer)
 *     stats:         { offers, week_events, classes, businesses, areas }
 *     featured:      business[]     the picked businesses, otherwise the automatic Featured and Local Partner ones
 *     categories:    category[]     the 6 with the most businesses (or the picked ones), most businesses first
 *     areas:         area[] + bar (% of the biggest area)
 *     newest:        business[]     "Just joined"
 * }
 */
function hub_home_page( $mode = HUB_WEB ) {
	$s = hub_home_shape( hub_home_db_fields() );

	// This weekend: two events and an offer
	$weekend = hub_events_weekend( 2 );                                          // events module
	$w0      = date_create_immutable( $weekend['from'] );
	$w1      = date_create_immutable( $weekend['to'] );
	$label   = 'Friday ' . $w0->format( $w0->format( 'm' ) === $w1->format( 'm' ) ? 'j' : 'j F' ) . ' to Sunday ' . $w1->format( 'j F' );

	$offers = hub_offers_list();                                                // offers module: every live offer, ranked
	$pick   = null;
	foreach ( $offers['items'] as $o ) {
		if ( $s['home_weekend_offer'] && (int) $o['id'] === $s['home_weekend_offer'] ) {
			$pick = $o;
		}
	}
	$weekend_offer = $pick ? $pick : ( $offers['items'] ? $offers['items'][0] : null );
	$tiles         = array_values( array_filter( $offers['items'], function ( $o ) use ( $weekend_offer ) {
		return ! $weekend_offer || $o['id'] !== $weekend_offer['id'];
	} ) );

	// Featured local businesses: the picked ones, in the order picked, or the automatic choice
	$featured = array();
	if ( $s['home_featured_pick'] ) {
		$found = hub_businesses_summaries( $s['home_featured_pick'] )['items'];   // businesses module
		foreach ( $s['home_featured_pick'] as $id ) {
			if ( isset( $found[ $id ] ) ) {
				$featured[] = $found[ $id ];
			}
		}
	}
	if ( ! $featured ) {
		$featured = hub_businesses_featured( HUB_HOME_FEATURED )['items'];
	}

	// Popular categories: the 6 with the most businesses, or the picked ones
	$cats = hub_home_rule_categories( hub_categories_list()['items'], $s['home_cats_pick'], HUB_HOME_CATEGORIES );   // categories module

	// Browse by area: every area, with a bar against the biggest
	$areas = hub_areas_list();                                                  // areas module
	$max   = $areas['items'] ? max( array_column( $areas['items'], 'business_count' ) ) : 0;
	$list  = array_map( function ( $a ) use ( $max ) {
		return $a + array( 'bar' => hub_home_rule_bar( $a['business_count'], $max ) );
	}, $areas['items'] );

	$week = hub_events_whats_on();                                              // its range: today and the next 6 days
	return hub_respond( array(
		'settings'      => $s,
		'weekend'       => array( 'label' => $label, 'events' => $weekend['items'] ),
		'weekend_offer' => $weekend_offer,
		'offers'        => array_slice( $tiles, 0, HUB_HOME_OFFERS ),
		'stats'         => array(
			'offers'      => (int) $offers['live_total'],
			'week_events' => (int) hub_events_list( array( 'to' => $week['range']['to'] ) )['total'],   // one-off events, today to 6 days on
			'classes'     => (int) hub_classes_list()['weekly_total'],
			'businesses'  => (int) hub_businesses_list( array( 'limit' => 1 ) )['total'],
			'areas'       => (int) $areas['total'],
		),
		'featured'      => array_slice( $featured, 0, HUB_HOME_FEATURED ),
		'categories'    => $cats,
		'areas'         => $list,
		'newest'        => hub_businesses_newest( 10 )['items'],
	), $mode );
}

/** The homepage's Page ID (0 when the front page is not a Page). */
function hub_home_page_id() {
	return hub_home_db_page_id();
}

/** The homepage's fields for the wp-admin box: what is saved, with the default for anything not saved. */
function hub_home_fields() {
	$saved    = hub_home_db_fields();
	$defaults = hub_home_rule_defaults();
	foreach ( $saved as $k => $v ) {
		if ( ( '' === $v || array() === $v ) && isset( $defaults[ $k ] ) ) {
			$saved[ $k ] = $defaults[ $k ];
		}
	}
	return $saved;
}

/** Choices for the box's pickers: live offers [ id => "title · business" ], categories [ slug => "name (count)" ]. */
function hub_home_choices() {
	$offers = array();
	foreach ( hub_offers_list()['items'] as $o ) {
		$offers[ (string) $o['id'] ] = $o['title'] . ' · ' . $o['business']['name'];
	}
	$cats = array();
	foreach ( hub_categories_list()['items'] as $c ) {
		$cats[ $c['slug'] ] = $c['name'] . ' (' . number_format( $c['business_count'] ) . ')';
	}
	return array( 'offers' => $offers, 'categories' => $cats );
}

/** Save the homepage's fields (already cleaned by the wp-admin box). */
function hub_home_save_fields( array $values ) {
	hub_home_db_save_fields( $values );
}
