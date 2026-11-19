<?php
/**
 * Page text: the shape a template (and the app) gets. Every field of the page, with the default for anything not saved.
 * Text is plain (templates escape it); "…_link" fields and the "link" of list rows are full URLs; "…_hide" switches
 * are booleans; lists are rows of plain values with the columns of their default rows.
 */
defined( 'ABSPATH' ) || exit;

function hub_pages_shape( $page, array $saved ) {
	$out = array();
	foreach ( hub_pages_rule_defaults( $page ) as $k => $default ) {
		$v = isset( $saved[ $k ] ) ? $saved[ $k ] : '';
		if ( '_hide' === substr( $k, -5 ) ) {
			$out[ $k ] = '1' === (string) $v;
		} elseif ( is_array( $default ) ) {
			$cols      = array_keys( $default[0] );
			$rows      = is_array( $v ) && $v ? $v : $default;
			$out[ $k ] = array_map( function ( $row ) use ( $cols ) {
				$r = array();
				foreach ( $cols as $col ) {
					$r[ $col ] = is_array( $row ) && isset( $row[ $col ] ) ? (string) $row[ $col ] : '';
				}
				if ( isset( $r['link'] ) ) {
					$r['link'] = hub_link( $r['link'] );
				}
				return $r;
			}, array_values( $rows ) );
		} else {
			$text      = '' !== (string) $v ? (string) $v : (string) $default;
			$out[ $k ] = '_link' === substr( $k, -5 ) ? hub_link( $text ) : $text;
		}
	}
	return $out;
}
