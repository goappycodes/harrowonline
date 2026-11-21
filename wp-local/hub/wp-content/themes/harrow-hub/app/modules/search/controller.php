<?php
/**
 * Search: the index of everything on the Hub that hub.js searches and lists from (shape.php), built from the other
 * modules (businesses, categories, areas, events, offers), so a business, event, class or offer added or changed in
 * wp-admin shows on the Businesses search, category and area pages straight away. It's built once (a second or two:
 * thousands of businesses) and kept until something it lists changes, or the day does.
 *
 *   hub_search_index()            the index (GET /wp-json/hub/v1/search/index)
 *   hub_search_index_tag()        its version, for the browser's cache (ETag)
 *   hub_search_index_changed()    something it lists changed (app/setup/search.php)
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/** @return array|WP_REST_Response the index (shape.php) */
function hub_search_index( $mode = HUB_WEB ) {
	$key   = hub_search_index_tag();
	$index = hub_search_db_cached( $key );
	if ( null === $index ) {
		$index = _hub_search_build();
		hub_search_db_store( $key, $index );
	}
	return hub_respond( $index, $mode );
}

/** The index's version: changes whenever the index does (content changed, or a new day). */
function hub_search_index_tag() {
	return hub_search_rule_key( hub_search_db_version(), hub_today()->format( 'Y-m-d' ) );
}

/**
 * A post, its fields or a category / area changed: the index is built afresh on the next request, if it's
 * something the index lists ($post_id, or a $taxonomy). Editing locks and the like are ignored.
 */
function hub_search_index_changed( $post_id = 0, $taxonomy = '', $meta_key = '' ) {
	if ( ( $post_id && ! hub_search_db_lists_type( hub_search_db_post_type( $post_id ) ) )
		|| ( '' !== $taxonomy && ! hub_search_db_lists_taxonomy( $taxonomy ) )
		|| ( '' !== $meta_key && ! hub_search_rule_meta_matters( $meta_key ) ) ) {
		return;
	}
	hub_search_db_bump();
}

/* ------------------------------------------------------------------ internal */

/** The index, from the other modules. */
function _hub_search_build() {
	$home  = hub_url( '' );
	$today = hub_today();
	$index = array( 'cats' => array(), 'areas' => array(), 'items' => array() );
	foreach ( hub_categories_list()['items'] as $c ) {   // categories module
		$index['cats'][ $c['slug'] ] = array( 'n' => $c['name'], 'i' => $c['icon'] );
	}
	foreach ( hub_areas_list()['items'] as $a ) {   // areas module
		$index['areas'][ $a['slug'] ] = $a['name'];
	}
	hub_businesses_each( function ( array $batch ) use ( &$index ) {   // businesses module, a batch at a time
		foreach ( $batch as $b ) {
			$index['items'][] = hub_search_shape_business( $b );
		}
	} );
	$weekend          = hub_events_weekend( 1 );   // events module
	$index['today']   = $today->format( 'Y-m-d' );
	$index['weekend'] = array( $weekend['from'], $weekend['to'] );
	$index['events']  = array_map( function ( $e ) use ( $home ) {
		return hub_search_shape_event( $e, $home );
	}, hub_events_list()['items'] );
	$index['classes'] = array();
	foreach ( hub_classes_list()['items'] as $c ) {
		if ( $c['page'] && null !== $c['weekday'] ) {   // a club's teams with no fixed weekly time are on the Classes page only
			$index['classes'][] = hub_search_shape_class( $c, $home );
		}
	}
	$index['offers'] = array_map( function ( $o ) use ( $home ) {
		return hub_search_shape_offer( $o, $home );
	}, hub_offers_list()['items'] );   // offers module: every live offer
	return $index;
}
