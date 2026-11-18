<?php
/**
 * Areas: rules.
 */
defined( 'ABSPATH' ) || exit;

/** Lists of areas (filters, menus) are in A to Z order of their names, capitals first
 *  ("Harrow Town Centre" before "Harrow on the Hill"), as the prototype and the database sort them. */
function hub_areas_rule_sort( array $items ) {
	usort( $items, function ( $a, $b ) {
		return strcmp( $a['name'], $b['name'] );
	} );
	return $items;
}
