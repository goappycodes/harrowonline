<?php
/**
 * What's On (post types hub_event and hub_class): the only file that reads or writes events and weekly classes.
 *
 * Each post keeps its details in the "_hub" meta record. Events: date, time, start, end (local times), cat, venue,
 * area (name), price, age, icon, image, featured, sample, org (organiser's business name), note. Classes: day
 * (0 = Monday), time, sort, venue, area (name), price, age, icon, featured, type, provider, real (photo, address and
 * note for classes run by real businesses), order. Title and description are the post title and content.
 * A class's business is its ACF field "class_business".
 */
defined( 'ABSPATH' ) || exit;

/** Every published event, by start time. Cached for the request. */
function hub_events_db_events() {
	static $posts = null;
	if ( null === $posts ) {
		$posts = get_posts( array(
			'post_type'      => 'hub_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_hub_start',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		) );
	}
	return $posts;
}

/** Every published weekly class, in the classes order. Cached for the request. */
function hub_events_db_classes() {
	static $posts = null;
	if ( null === $posts ) {
		$posts = get_posts( array(
			'post_type'      => 'hub_class',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_hub_order',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		) );
	}
	return $posts;
}

/** One published event or class ($type 'hub_event' | 'hub_class') by post ID or slug, or null. */
function hub_events_db_get( $type, $id_or_slug ) {
	$post = is_int( $id_or_slug ) ? get_post( $id_or_slug ) : get_page_by_path( (string) $id_or_slug, OBJECT, $type );
	return ( $post && $type === $post->post_type && 'publish' === $post->post_status ) ? $post : null;
}

/** The details record of an event or class (array). */
function hub_events_db_record( WP_Post $post ) {
	$r = get_post_meta( $post->ID, '_hub', true );
	return is_array( $r ) ? $r : array();
}

/** Post ID of the business that runs a class (its "Business" field), or 0. */
function hub_events_db_class_business( $class_id ) {
	return (int) get_post_meta( $class_id, 'class_business', true );
}

/** Today's date (the option hub_today fixes it for the preview). */
function hub_events_db_today() {
	return hub_today();
}

/* ------------------------------------------------------------------ writing */

/* A class has one business. When a class is added on a business, ACF puts both businesses in the class's "Business"
 * field ([old, new]): keep the new one and take the class off the old business's list. (ACF is already inside its
 * own two-way update here, so this does not loop.) */
add_filter( 'acf/update_value/key=field_hub_class_business', function ( $value, $post_id ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	$ids = array_values( array_filter( array_map( 'intval', $value ) ) );
	$new = $ids ? end( $ids ) : 0;
	foreach ( array_diff( $ids, array( $new ) ) as $old ) {
		$list = array_map( 'intval', acf_get_array( get_field( 'biz_classes', $old, false ) ) );
		update_field( 'field_hub_biz_classes', array_values( array_diff( $list, array( (int) $post_id ) ) ), $old );
	}
	return $new ? (string) $new : '';
}, 20, 2 );
