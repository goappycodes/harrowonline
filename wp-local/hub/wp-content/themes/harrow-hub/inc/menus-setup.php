<?php
/**
 * Creates today's menus (Appearance > Menus), footer widgets (Appearance > Widgets) and the Privacy and Terms text
 * (each page's editor) once: the menus that don't exist
 * yet (by name), the two menu locations if empty, and the widgets of any footer area that has none. Runs when the theme
 * is switched on; locally also from the command line (wp-local/README.md). Nothing is overwritten.
 */
defined( 'ABSPATH' ) || exit;

function hub_setup_menus_widgets() {
	// Menus
	$ids = array();
	foreach ( hub_menu_defaults() as $key => $menu ) {
		$found = wp_get_nav_menu_object( $menu[0] );
		if ( $found ) {
			$ids[ $key ] = (int) $found->term_id;
			continue;
		}
		$menu_id = wp_create_nav_menu( $menu[0] );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}
		$ids[ $key ] = (int) $menu_id;
		$position    = 0;
		$add         = function ( array $rows, $parent ) use ( &$add, $menu_id, &$position ) {
			foreach ( $rows as $r ) {
				$item = array(
					'menu-item-title'     => $r[0],
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $parent,
					'menu-item-position'  => ++$position,
					'menu-item-classes'   => isset( $r[2] ) ? $r[2] : '',
				);
				$page = 0 === strpos( $r[1], 'page:' ) ? substr( $r[1], 5 ) : null;
				$post = null === $page ? null : ( '' === $page ? get_post( (int) get_option( 'page_on_front' ) ) : get_page_by_path( $page ) );
				if ( $post ) {
					$item += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $post->ID );
				} else {
					$item += array( 'menu-item-type' => 'custom', 'menu-item-url' => null === $page ? $r[1] : '/hub/' . ( '' === $page ? '' : $page . '/' ) );
				}
				$item_id = wp_update_nav_menu_item( $menu_id, 0, $item );
				if ( ! is_wp_error( $item_id ) && ! empty( $r[3] ) ) {
					$add( $r[3], $item_id );
				}
			}
		};
		$add( $menu[1], 0 );
	}
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	foreach ( array( 'primary', 'social' ) as $location ) {
		if ( empty( $locations[ $location ] ) && ! empty( $ids[ $location ] ) ) {
			$locations[ $location ] = $ids[ $location ];
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );

	// Footer widgets
	$d      = hub_footer_defaults();
	$widget = function ( $base, array $instance ) {
		$all = get_option( 'widget_' . $base, array() );
		$all = is_array( $all ) ? $all : array();
		$n   = max( array_merge( array( 1 ), array_filter( array_keys( $all ), 'is_int' ) ) ) + 1;
		$all[ $n ]         = $instance;
		$all['_multiwidget'] = 1;
		update_option( 'widget_' . $base, $all );
		return $base . '-' . $n;
	};
	$text  = function ( $html ) {
		return array( 'title' => '', 'text' => $html, 'filter' => true, 'visual' => true );
	};
	$menu  = function ( $title, $key ) use ( $ids ) {
		return array( 'title' => $title, 'nav_menu' => isset( $ids[ $key ] ) ? $ids[ $key ] : 0 );
	};
	$fill  = array(
		'footer-brand'      => array( array( 'text', $text( $d['tagline'] ) ) ),
		'footer-columns'    => array_map( function ( $title, $key ) use ( $menu ) {
			return array( 'nav_menu', $menu( $title, $key ) );
		}, array_keys( $d['columns'] ), $d['columns'] ),
		'footer-newsletter' => array( array( 'hub_newsletter', $d['newsletter'] ) ),
		'footer-topics'     => array( array( 'nav_menu', $menu( $d['topics'][0], $d['topics'][1] ) ) ),
		'footer-badges'     => array( array( 'hub_badges', array( 'badges' => $d['badges'] ) ) ),
		'footer-legal'      => array( array( 'text', $text( $d['legal'] ) ) ),
	);
	$sidebars = wp_get_sidebars_widgets();
	foreach ( $fill as $area => $widgets ) {
		if ( ! empty( $sidebars[ $area ] ) ) {
			continue;
		}
		$sidebars[ $area ] = array();
		foreach ( $widgets as $w ) {
			$sidebars[ $area ][] = $widget( $w[0], $w[1] );
		}
	}
	wp_set_sidebars_widgets( $sidebars );
	hub_pages_setup_bodies();   // Privacy and Terms: their text into the page's editor, if it is empty
	return array( 'menus' => $ids, 'areas' => array_keys( $fill ) );
}

add_action( 'after_switch_theme', 'hub_setup_menus_widgets' );
