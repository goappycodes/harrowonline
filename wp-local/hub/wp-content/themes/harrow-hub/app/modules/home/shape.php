<?php
/**
 * Homepage: the settings shape the template and the app get. Every key of HUB_HOME_FIELDS, with the default for
 * anything not saved. Text is plain (templates escape it); links are full URLs; "…_hide" switches are booleans; lists
 * are rows of plain values (links resolved). home_weekend_offer is an offer ID or 0; home_featured_pick business IDs;
 * home_cats_pick category slugs.
 */
defined( 'ABSPATH' ) || exit;

/** Row keys for each list. */
const HUB_HOME_LISTS = array(
	'home_popular'     => array( 'label', 'link' ),
	'home_tiles'       => array( 'icon', 'title', 'text', 'link' ),
	'home_add_buttons' => array( 'label', 'link' ),
	'home_nl_editions' => array( 'value', 'label', 'note', 'checked' ),
	'home_biz_buttons' => array( 'label', 'link' ),
	'home_biz_steps'   => array( 'title', 'text' ),
);

function hub_home_shape( array $saved ) {
	$defaults = hub_home_rule_defaults();
	$out      = array();
	foreach ( HUB_HOME_FIELDS as $k ) {
		$v = isset( $saved[ $k ] ) ? $saved[ $k ] : '';
		if ( '_hide' === substr( $k, -5 ) ) {
			$out[ $k ] = '1' === (string) $v;
		} elseif ( isset( HUB_HOME_LISTS[ $k ] ) ) {
			$rows      = is_array( $v ) && $v ? $v : $defaults[ $k ];
			$out[ $k ] = array_map( function ( $row ) use ( $k ) {
				$r = array();
				foreach ( HUB_HOME_LISTS[ $k ] as $col ) {
					$r[ $col ] = isset( $row[ $col ] ) ? (string) $row[ $col ] : '';
				}
				if ( isset( $r['link'] ) ) {
					$r['link'] = hub_home_rule_link( $r['link'] );
				}
				if ( isset( $r['checked'] ) ) {
					$r['checked'] = '1' === $r['checked'];
				}
				return $r;
			}, array_values( $rows ) );
		} elseif ( 'home_weekend_offer' === $k ) {
			$out[ $k ] = (int) $v;
		} elseif ( 'home_featured_pick' === $k ) {
			$out[ $k ] = array_values( array_filter( array_map( 'intval', is_array( $v ) ? $v : array() ) ) );
		} elseif ( 'home_cats_pick' === $k ) {
			$out[ $k ] = array_values( array_filter( array_map( 'strval', is_array( $v ) ? $v : array() ) ) );
		} else {
			$text      = '' !== (string) $v ? (string) $v : ( isset( $defaults[ $k ] ) ? $defaults[ $k ] : '' );
			$out[ $k ] = '_link' === substr( $k, -5 ) ? hub_home_rule_link( $text ) : $text;
		}
	}
	return $out;
}
