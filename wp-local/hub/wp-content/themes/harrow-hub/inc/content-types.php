<?php
/**
 * Hub content: businesses, offers, events and weekly classes (post types), categories and areas (taxonomies).
 * URLs match the prototype (and harrowonline.org/hub/):
 *   /business/{slug}/   /category/{slug}/   /area/{slug}/   /offers/{slug}/   /events/{slug}/ (events and classes)
 *
 * Each post keeps its full record in the "_hub" meta (the prototype's data, imported by import-local.php); the
 * fields used to sort and filter are separate meta keys.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	$common = array(
		'public'          => true,
		'show_ui'         => true,
		'show_in_rest'    => false,   // classic editing (block editor off)
		'supports'        => array( 'title', 'editor' ),   // no "Custom Fields" box: details are edited in the Hub's own boxes (inc/admin/)
		'has_archive'     => false,   // the list pages are WordPress Pages with their own templates
		'capability_type' => 'post',
	);

	register_post_type( 'hub_business', $common + array(
		'labels'    => hub_labels( 'Business', 'Businesses' ),
		'menu_icon' => 'dashicons-store',
		'rewrite'   => array( 'slug' => 'business', 'with_front' => false ),
	) );
	register_post_type( 'hub_offer', $common + array(
		'labels'    => hub_labels( 'Offer', 'Offers' ),
		'menu_icon' => 'dashicons-tag',
		'rewrite'   => array( 'slug' => 'offers', 'with_front' => false ),
	) );
	// Events and classes share /events/{slug}/ (rule below), next to the /events/month/ and /events/classes/ Pages
	register_post_type( 'hub_event', $common + array(
		'labels'    => hub_labels( 'Event', 'Events' ),
		'menu_icon' => 'dashicons-calendar-alt',
		'rewrite'   => false,
	) );
	register_post_type( 'hub_class', $common + array(
		'labels'    => hub_labels( 'Class', 'Classes' ),
		'menu_icon' => 'dashicons-update',
		'rewrite'   => false,
	) );

	register_taxonomy( 'hub_category', array( 'hub_business' ), array(
		'labels'            => hub_labels( 'Category', 'Categories' ),
		'public'            => true,
		'hierarchical'      => false,
		'meta_box_cb'       => 'post_categories_meta_box',   // a tick list, so a typo can't make a new category
		'show_admin_column' => true,
		'show_in_rest'      => false,
		'rewrite'           => array( 'slug' => 'category', 'with_front' => false ),
	) );
	register_taxonomy( 'hub_area', array( 'hub_business' ), array(
		'labels'            => hub_labels( 'Area', 'Areas' ),
		'public'            => true,
		'hierarchical'      => false,
		'meta_box_cb'       => 'post_categories_meta_box',   // a tick list, so a typo can't make a new area
		'show_admin_column' => true,
		'show_in_rest'      => false,
		'rewrite'           => array( 'slug' => 'area', 'with_front' => false ),
	) );

	// /events/{slug}/ is an event or a weekly class; /events/month/ and /events/classes/ stay Pages
	add_rewrite_tag( '%hub_ev%', '([^/]+)' );
	add_rewrite_rule( '^events/(?!month/?$|classes/?$)([^/]+)/?$', 'index.php?hub_ev=$matches[1]', 'top' );
}, 5 );

/** WordPress's own "category" taxonomy (unused: the Hub has no blog) moves out of /category/. */
add_filter( 'pre_option_category_base', function () {
	return 'post-category';
} );

/** Resolve /events/{slug}/ to the event or class with that slug. */
add_filter( 'request', function ( $vars ) {
	if ( empty( $vars['hub_ev'] ) ) {
		return $vars;
	}
	$slug = sanitize_title( $vars['hub_ev'] );
	foreach ( array( 'hub_event', 'hub_class' ) as $type ) {
		$found = get_posts( array( 'post_type' => $type, 'name' => $slug, 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'publish' ) );
		if ( $found ) {
			return array( 'post_type' => $type, 'name' => $slug, $type => $slug );
		}
	}
	return array( 'error' => '404' );
} );

/** Event and class links: /events/{slug}/ */
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( in_array( $post->post_type, array( 'hub_event', 'hub_class' ), true ) ) {
		return hub_url( 'events/' . $post->post_name . '/' );
	}
	return $link;
}, 10, 2 );

function hub_labels( $one, $many ) {
	return array(
		'name'          => $many,
		'singular_name' => $one,
		'add_new_item'  => "Add {$one}",
		'edit_item'     => "Edit {$one}",
		'new_item'      => "New {$one}",
		'view_item'     => "View {$one}",
		'search_items'  => "Search {$many}",
		'all_items'     => "All {$many}",
		'menu_name'     => $many,
	);
}

/** The full imported record of a Hub post (array). */
function hub_record( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$r = get_post_meta( $post->ID, '_hub', true );
	return is_array( $r ) ? $r : array();
}
