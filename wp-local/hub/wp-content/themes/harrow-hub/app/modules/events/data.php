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

/* ------------------------------------------------------------------ events and classes sent from the website (submissions module) */

/** An event or class ($type 'hub_event' | 'hub_class') in any status, or null. */
function hub_events_db_get_any( $type, $id ) {
	$post = get_post( (int) $id );
	return ( $post && $type === $post->post_type ) ? $post : null;
}

/**
 * Create or update an event or class sent from the website.
 *
 * @param string $type   'hub_event' | 'hub_class'
 * @param int    $id     0 for a new one
 * @param string $status 'draft' | 'pending'
 * @param array  $record the "_hub" record
 * @param array  $meta   sort keys and links: _hub_start, _hub_day, _hub_org (events); _hub_order, class_business (classes)
 * @param array  $form   the form's values, for editing it again ("_hub_form")
 * @return int|WP_Error
 */
function hub_events_db_save( $type, $id, $status, $title, $content, array $record, array $meta, array $form ) {
	$args = array( 'post_type' => $type, 'post_status' => $status, 'post_title' => $title, 'post_content' => $content );
	if ( $id ) {
		$args['ID'] = (int) $id;
	}
	$id = $id ? wp_update_post( $args, true ) : wp_insert_post( $args, true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	// WordPress leaves the slug empty on drafts, and clears it on "pending" posts saved by accounts that can't publish
	// (every Hub account): set it here, so the record and the page link work at every status
	if ( '' === (string) get_post_field( 'post_name', $id ) ) {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_name' => wp_unique_post_slug( sanitize_title( $title ), $id, 'publish', $type, 0 ) ), array( 'ID' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( $id );
	}
	$record['slug'] = get_post_field( 'post_name', $id );
	update_post_meta( $id, '_hub', $record );
	update_post_meta( $id, '_hub_form', $form );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	if ( 'hub_class' === $type && ! empty( $meta['class_business'] ) ) {
		$biz = (int) $meta['class_business'];
		update_post_meta( $id, '_class_business', 'field_hub_class_business' );
		$list = array_map( 'intval', (array) get_field( 'biz_classes', $biz, false ) );
		if ( ! in_array( (int) $id, $list, true ) ) {
			update_post_meta( $biz, 'biz_classes', array_map( 'strval', array_merge( $list, array( (int) $id ) ) ) );
		}
	}
	return (int) $id;
}

/** The next position on the Classes page (new classes go last). */
function hub_events_db_next_class_order() {
	$last = get_posts( array( 'post_type' => 'hub_class', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_hub_order', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'fields' => 'ids' ) );
	return $last ? (int) get_post_meta( $last[0], '_hub_order', true ) + 1 : 1;
}
