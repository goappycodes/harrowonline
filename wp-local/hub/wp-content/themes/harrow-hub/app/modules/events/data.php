<?php
/**
 * What's On (post types hub_event and hub_class): the only file that reads or writes events and weekly classes.
 *
 * Each post's details are its fields (event_* / class_*, below; edited in wp-admin) on top of its imported "_hub"
 * meta record. Events: date, time, start, end (local times), cat, venue,
 * area (name), price, age, icon, image, featured, sample, org (organiser's business name), note. Classes: day
 * (0 = Monday), time, sort, venue, area (name), price, age, icon, featured, type, provider, real (photo, address and
 * note for classes run by real businesses), order. Title and description are the post title and content.
 * A class's business is its post meta "class_business" (set in wp-admin on the class or on the business).
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

/**
 * The fields (plain post meta, edited in wp-admin's "Event details" / "Class details" boxes), one meta key each.
 * Posts with fields have "_hub_fields" = 1; the others still read their imported record.
 *   event: date (Y-m-d), start and end (HH:MM, local), time (as shown, "7pm to 10pm"), image (attachment ID) …
 *   class: day ('' = no fixed day, 0 = Monday), sort (start hour), order, page (own page), sample (made up), image,
 *          image_alt, image_fit (photo | logo), street, postcode, note, and card_* (how it shows on the Classes page)
 */
const HUB_EVENTS_EVENT_FIELDS = array(
	'event_date', 'event_start', 'event_end', 'event_time', 'event_note', 'event_cat', 'event_venue', 'event_area', 'event_price',
	'event_age', 'event_icon', 'event_image', 'event_featured', 'event_sample', 'event_org',
);
const HUB_EVENTS_CLASS_FIELDS = array(
	'class_page', 'class_day', 'class_time', 'class_sort', 'class_order', 'class_type', 'class_venue', 'class_area', 'class_price',
	'class_age', 'class_icon', 'class_featured', 'class_provider', 'class_sample', 'class_image', 'class_image_alt', 'class_image_fit',
	'class_street', 'class_postcode', 'class_note', 'class_card_order', 'class_card_text', 'class_card_when', 'class_card_price',
	'class_card_age', 'class_card_provider',
);

/** The details record of an event or class (array): the imported record with the fields on top. */
function hub_events_db_record( WP_Post $post ) {
	$r = get_post_meta( $post->ID, '_hub', true );
	$r = is_array( $r ) ? $r : array();
	if ( '1' !== (string) get_post_meta( $post->ID, '_hub_fields', true ) ) {
		return $r;
	}
	return 'hub_class' === $post->post_type ? _hub_events_db_class_record( $post->ID, $r ) : _hub_events_db_event_record( $post->ID, $r );
}

/** Every field's stored value (for the wp-admin box). $type 'hub_event' | 'hub_class'. */
function hub_events_db_fields( $type, $post_id ) {
	$out = array();
	foreach ( 'hub_class' === $type ? HUB_EVENTS_CLASS_FIELDS : HUB_EVENTS_EVENT_FIELDS as $k ) {
		$out[ $k ] = get_post_meta( $post_id, $k, true );
	}
	return $out;
}

/** Attachment ID -> [ url, alt ], or null. */
function _hub_events_db_image( $id ) {
	$id  = (int) $id;
	$url = $id ? wp_get_attachment_image_url( $id, 'large' ) : '';
	return $url ? array( $url, (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : null;
}

/** Set $r[ $key ] to the value, or remove it when the value is empty. */
function _hub_events_db_put( array &$r, $key, $value ) {
	if ( '' !== (string) $value ) {
		$r[ $key ] = $value;
	} else {
		unset( $r[ $key ] );
	}
}

function _hub_events_db_event_record( $id, array $r ) {
	$v    = hub_events_db_fields( 'hub_event', $id );
	$date = (string) $v['event_date'];
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		$date = isset( $r['date'] ) ? (string) $r['date'] : hub_today()->format( 'Y-m-d' );   // no date saved yet
	}
	$from = '' !== (string) $v['event_start'] ? $v['event_start'] : '00:00';
	$to   = '' !== (string) $v['event_end'] ? $v['event_end'] : $from;
	$end  = new DateTimeImmutable( $date . 'T' . $to . ':00' );
	if ( $to < $from ) {
		$end = $end->modify( '+1 day' );   // ends after midnight
	}
	$r['date']     = $date;
	$r['day']      = $date;
	$r['start']    = $date . 'T' . $from . ':00';
	$r['end']      = $end->format( 'Y-m-d\TH:i:s' );
	$r['featured'] = '1' === (string) $v['event_featured'];
	$r['sample']   = '1' === (string) $v['event_sample'];
	foreach ( array( 'time', 'note', 'cat', 'venue', 'area', 'price', 'age', 'icon', 'org' ) as $k ) {
		_hub_events_db_put( $r, $k, $v[ 'event_' . $k ] );
	}
	$img = _hub_events_db_image( $v['event_image'] );
	_hub_events_db_put( $r, 'image', $img ? $img[0] : '' );
	return $r;
}

function _hub_events_db_class_record( $id, array $r ) {
	$v = hub_events_db_fields( 'hub_class', $id );
	$r['page']     = '1' === (string) $v['class_page'];
	$r['day']      = '' !== (string) $v['class_day'] ? (int) $v['class_day'] : null;
	$r['sort']     = '' !== (string) $v['class_sort'] ? (float) $v['class_sort'] : 0;
	$r['order']    = (int) $v['class_order'];
	$r['featured'] = '1' === (string) $v['class_featured'];
	foreach ( array( 'time', 'type', 'venue', 'area', 'price', 'age', 'icon', 'provider' ) as $k ) {
		_hub_events_db_put( $r, $k, $v[ 'class_' . $k ] );
	}

	// "real": the photo, address and note of a class run by a real business; null for a made-up sample
	if ( '1' === (string) $v['class_sample'] ) {
		$r['real'] = null;
	} else {
		$real = array();
		$img  = _hub_events_db_image( $v['class_image'] );
		if ( $img ) {
			$real['img'] = $img[0];
			$real['alt'] = '' !== (string) $v['class_image_alt'] ? $v['class_image_alt'] : $img[1];
			if ( 'logo' === $v['class_image_fit'] ) {
				$real['fit'] = 'logo';
			}
		}
		foreach ( array( 'street', 'postcode', 'note' ) as $k ) {
			_hub_events_db_put( $real, $k, $v[ 'class_' . $k ] );
		}
		$r['real'] = $real ? $real : array( 'note' => 'Details from the organiser, checked by Harrow Online.' );
	}

	// "card": how it shows on the Classes and activities page (empty = worked out from the class)
	$card = array();
	foreach ( array( 'order', 'text', 'when', 'price', 'age', 'provider' ) as $k ) {
		_hub_events_db_put( $card, $k, $v[ 'class_card_' . $k ] );
	}
	if ( isset( $card['order'] ) ) {
		$card['order'] = (int) $card['order'];
	}
	$r['card'] = $card;
	return $r;
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

/** Every weekly class for the wp-admin lists, any status: [ { id, title, status, business } ] by title. */
function hub_events_db_admin_classes() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'hub_class', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		$out[] = array( 'id' => $p->ID, 'title' => $p->post_title, 'status' => $p->post_status, 'business' => hub_events_db_class_business( $p->ID ) );
	}
	return $out;
}

/** Save an event's fields (already cleaned by the wp-admin box), then its sort keys. */
function hub_events_db_save_event_fields( $post_id, array $values ) {
	hub_db_save_meta( $post_id, HUB_EVENTS_EVENT_FIELDS, $values );
	$r = _hub_events_db_event_record( $post_id, array() );
	update_post_meta( $post_id, '_hub_start', $r['start'] );
	update_post_meta( $post_id, '_hub_day', $r['date'] );
	update_post_meta( $post_id, '_hub_org', isset( $r['org'] ) ? $r['org'] : '' );
}

/** Save a class's fields (already cleaned by the wp-admin box), then its sort keys. */
function hub_events_db_save_class_fields( $post_id, array $values ) {
	hub_db_save_meta( $post_id, HUB_EVENTS_CLASS_FIELDS, $values );
	$day = get_post_meta( $post_id, 'class_day', true );
	update_post_meta( $post_id, '_hub_order', (int) get_post_meta( $post_id, 'class_order', true ) );
	update_post_meta( $post_id, '_hub_day', '' !== (string) $day ? (int) $day : '' );
}

/** Set (or clear, with 0) the business that runs a class. A class has one business. */
function hub_events_db_set_class_business( $class_id, $business_id ) {
	$business_id = (int) $business_id;
	if ( $business_id ) {
		update_post_meta( $class_id, 'class_business', (string) $business_id );
	} else {
		delete_post_meta( $class_id, 'class_business' );
	}
}

/* ------------------------------------------------------------------ events and classes sent from the website (submissions module) */

/** An event or class ($type 'hub_event' | 'hub_class') in any status, or null. */
function hub_events_db_get_any( $type, $id ) {
	$post = get_post( (int) $id );
	return ( $post && $type === $post->post_type ) ? $post : null;
}

/**
 * Create or update an event or class sent from the website: its record, sort keys, business and fields.
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
		if ( 'class_business' === $k ) {
			hub_events_db_set_class_business( $id, (int) $v );
		} else {
			update_post_meta( $id, $k, $v );
		}
	}
	// The fields (what the pages read and wp-admin edits) follow the record sent from the website
	if ( 'hub_class' === $type ) {
		hub_events_db_save_class_fields( $id, hub_fields_class_from_record( $id, $record ) );
	} else {
		hub_events_db_save_event_fields( $id, hub_fields_event_from_record( $id, $record ) );
	}
	return (int) $id;
}

/** The next position on the Classes page (new classes go last). */
function hub_events_db_next_class_order() {
	$last = get_posts( array( 'post_type' => 'hub_class', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_hub_order', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'fields' => 'ids' ) );
	return $last ? (int) get_post_meta( $last[0], '_hub_order', true ) + 1 : 1;
}
