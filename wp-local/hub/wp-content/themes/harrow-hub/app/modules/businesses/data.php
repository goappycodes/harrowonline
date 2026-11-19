<?php
/**
 * Businesses (post type hub_business): the only file that reads or writes businesses in WordPress.
 *
 * A business's details are its fields (plain post meta, below; edited in wp-admin through inc/admin/), on top of the
 * record imported into its "_hub" meta. Posts with fields have "_hub_fields" = 1. Claimed businesses keep their profile in $record['claimed'].
 * Sort keys kept in step on every save: "_hub_tier" (plan), "_hub_status", "_hub_rank" and "_hub_order"
 * (directory order).
 */
defined( 'ABSPATH' ) || exit;

const HUB_BUSINESSES_SOCIAL    = array( 'facebook', 'instagram', 'tiktok', 'linkedin', 'x' );
const HUB_BUSINESSES_HOUR_KEYS = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );

/* ------------------------------------------------------------------ reading */

/** One published business by post ID or slug, or null. */
function hub_businesses_db_get( $id_or_slug ) {
	$post = is_int( $id_or_slug ) ? get_post( $id_or_slug ) : get_page_by_path( (string) $id_or_slug, OBJECT, 'hub_business' );
	return ( $post && 'hub_business' === $post->post_type && 'publish' === $post->post_status ) ? $post : null;
}

/**
 * Published businesses in directory order.
 *
 * @param array $args { cat, area (slugs), exclude (post ID), claimed_only (bool), limit (0 = all), offset }
 * @return array { posts: WP_Post[], total: int }
 */
function hub_businesses_db_query( array $args ) {
	$q = array(
		'post_type'      => 'hub_business',
		'post_status'    => 'publish',
		'posts_per_page' => ! empty( $args['limit'] ) ? (int) $args['limit'] : -1,
		'offset'         => ! empty( $args['offset'] ) ? (int) $args['offset'] : 0,
		'meta_key'       => '_hub_order',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	);
	$tax = array();
	if ( ! empty( $args['cat'] ) ) {
		$tax[] = array( 'taxonomy' => 'hub_category', 'field' => 'slug', 'terms' => $args['cat'] );
	}
	if ( ! empty( $args['area'] ) ) {
		$tax[] = array( 'taxonomy' => 'hub_area', 'field' => 'slug', 'terms' => $args['area'] );
	}
	if ( $tax ) {
		$q['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
	}
	if ( ! empty( $args['exclude'] ) ) {
		$q['post__not_in'] = array( (int) $args['exclude'] );
	}
	if ( ! empty( $args['claimed_only'] ) ) {
		$q['meta_query'] = array( array( 'key' => '_hub_tier', 'value' => 'unclaimed', 'compare' => '!=' ) );
	}
	$query = new WP_Query( $q );
	return array( 'posts' => $query->posts, 'total' => (int) $query->found_posts );
}

/**
 * The business record: the imported record with the fields on top. Cached per post.
 * $record['claimed'] (array) is set for claimed businesses only.
 */
function hub_businesses_db_record( WP_Post $post ) {
	static $cache = array();
	$key = $post->ID . '@' . hub_businesses_db_cache_gen();
	if ( ! isset( $cache[ $key ] ) ) {
		$x = hub_record( $post );
		if ( hub_businesses_db_uses_fields( $post->ID ) ) {
			$x = _hub_businesses_db_fields( $post, $x );
		}
		if ( ! empty( $x['claimed'] ) ) {
			// Description: the post content (editable in wp-admin), one paragraph per <p>
			preg_match_all( '#<p>(.*?)</p>#s', wpautop( $post->post_content ), $m );
			$x['claimed']['description'] = $m[1];
		}
		$cache[ $key ] = $x;
	}
	return $cache[ $key ];
}

/**
 * Per-request caches (records here, shapes in the controller) include this number in their keys. Changing a business
 * moves it on, so the same request reads the change instead of the cached copy (e.g. a billing webhook that sets a
 * plan and then emails the owner).
 */
function hub_businesses_db_cache_gen( $bump = false ) {
	static $gen = 0;
	if ( $bump ) {
		$gen++;
	}
	return $gen;
}

/** The imported record as it was before any wp-admin edits (events still find a renamed business by its old name). */
function hub_businesses_db_imported( WP_Post $post ) {
	return hub_record( $post );
}

/** Plan and directory position from the sort keys. */
function hub_businesses_db_meta( WP_Post $post ) {
	$tier = (string) get_post_meta( $post->ID, '_hub_tier', true );
	return array(
		'plan'  => '' !== $tier ? $tier : 'unclaimed',
		'order' => (int) get_post_meta( $post->ID, '_hub_order', true ),
	);
}

/** Today's date (the option hub_today fixes it for the preview). */
function hub_businesses_db_today() {
	return hub_today();
}

/* ------------------------------------------------------------------ fields (post meta, edited in wp-admin) */

/**
 * The fields a business can have, all plain post meta (edited in the "Business details" box, inc/admin/).
 * Single values are strings; the "list" ones are arrays stored in one meta key each:
 *   biz_phones_extra  [ "020 ..." ]                 biz_hours     seven strings, Monday first
 *   biz_schedule      [ [ day, text ] ]             biz_gallery   [ attachment IDs ]
 *   biz_other_locations [ { label, name, address } ]  biz_news   [ { title, url, cat, date } ]
 * Posts whose details live in these fields have "_hub_fields" = 1.
 */
const HUB_BUSINESSES_FIELDS = array(
	'biz_plan', 'biz_claimed', 'biz_checked', 'biz_sub', 'biz_tagline', 'biz_icon', 'biz_claimed_since', 'biz_botw',
	'biz_verified_note', 'biz_seo_title', 'biz_address', 'biz_postcode', 'biz_service_area', 'biz_phone', 'biz_website',
	'biz_email', 'biz_lat', 'biz_lng', 'biz_hours_note', 'biz_status', 'biz_schedule_title', 'biz_schedule_note',
	'biz_logo', 'biz_cover', 'biz_cover_bg', 'biz_facebook', 'biz_instagram', 'biz_tiktok', 'biz_linkedin', 'biz_x',
	'biz_phones_extra', 'biz_hours', 'biz_schedule', 'biz_other_locations', 'biz_news', 'biz_gallery',
);

/** True when this post's details live in the fields (otherwise the imported record is used as it is). */
function hub_businesses_db_uses_fields( $post_id ) {
	return '1' === (string) get_post_meta( $post_id, '_hub_fields', true );
}

/** A single field's value, or $default when empty. */
function hub_businesses_db_field( $name, $post_id, $default = '' ) {
	$v = get_post_meta( $post_id, $name, true );
	return ( '' === $v || null === $v || false === $v || array() === $v ) ? $default : $v;
}

/** A list field (array), or an empty array. */
function hub_businesses_db_list( $name, $post_id ) {
	$v = get_post_meta( $post_id, $name, true );
	return is_array( $v ) ? array_values( $v ) : array();
}

/** Every field's stored value (for the wp-admin box). */
function hub_businesses_db_fields( $post_id ) {
	$out = array();
	foreach ( HUB_BUSINESSES_FIELDS as $k ) {
		$out[ $k ] = get_post_meta( $post_id, $k, true );
	}
	return $out;
}

/** Attachment ID -> [ url, alt ], or null. */
function hub_businesses_db_attachment( $id, $size = 'large' ) {
	$id  = (int) $id;
	$url = $id ? wp_get_attachment_image_url( $id, $size ) : '';
	return $url ? array( $url, (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : null;
}

/** The record from the fields, on top of the imported record. */
function _hub_businesses_db_fields( WP_Post $post, array $x ) {
	$id   = $post->ID;
	$f    = 'hub_businesses_db_field';
	$cats = get_the_terms( $id, 'hub_category' );
	$ars  = get_the_terms( $id, 'hub_area' );

	$x['id']       = isset( $x['id'] ) ? $x['id'] : 'post-' . $id;
	$x['slug']     = $post->post_name;
	$x['name']     = $post->post_title;
	$x['cat']      = $cats && ! is_wp_error( $cats ) ? $cats[0]->slug : ( isset( $x['cat'] ) ? $x['cat'] : '' );
	$x['area']     = $ars && ! is_wp_error( $ars ) ? $ars[0]->slug : ( isset( $x['area'] ) ? $x['area'] : '' );
	$x['sub']      = $f( 'biz_sub', $id );
	$x['address']  = $f( 'biz_address', $id );
	$x['postcode'] = $f( 'biz_postcode', $id );

	$phone  = trim( $f( 'biz_phone', $id ) );
	$extras = array_values( array_filter( array_map( 'trim', array_map( 'strval', hub_businesses_db_list( 'biz_phones_extra', $id ) ) ) ) );
	$phones         = array_values( array_filter( array_merge( array( $phone ), $extras ) ) );
	$x['phone']     = $phones;
	$x['phone_raw'] = array_map( 'hub_businesses_rule_phone_tel', $phones );
	$web            = $f( 'biz_website', $id );
	$x['web']       = $web ? array( $web ) : array();
	$mail           = $f( 'biz_email', $id );
	$x['email']     = $mail ? array( $mail ) : array();
	$lat            = $f( 'biz_lat', $id, null );
	$lng            = $f( 'biz_lng', $id, null );
	$x['lat']       = null === $lat ? null : (float) $lat;
	$x['lng']       = null === $lng ? null : (float) $lng;
	$x['checked']   = (bool) $f( 'biz_checked', $id, false );

	if ( ! $f( 'biz_claimed', $id, false ) ) {
		unset( $x['tier'], $x['claimed'] );
		return $x;
	}

	$plan      = $f( 'biz_plan', $id, 'basic' );
	$x['tier'] = 'unclaimed' === $plan ? 'basic' : $plan;
	$c         = isset( $x['claimed'] ) && is_array( $x['claimed'] ) ? $x['claimed'] : array();

	$c['name']         = $x['name'];
	$c['address']      = $x['address'];
	$c['phone']        = $phone;
	$c['tagline']      = $f( 'biz_tagline', $id );
	$c['icon']         = $f( 'biz_icon', $id, 'store' );
	$c['claimed']      = $f( 'biz_claimed_since', $id, isset( $c['claimed'] ) ? $c['claimed'] : '' );
	$c['botw']         = (bool) $f( 'biz_botw', $id, false );
	$c['phones_extra'] = $extras;
	foreach ( array( 'verified_note', 'seo_title', 'service_area', 'hours_note', 'status', 'schedule_title', 'schedule_note', 'cover_bg' ) as $k ) {
		$v = $f( 'biz_' . $k, $id );
		if ( '' !== $v ) {
			$c[ $k ] = $v;
		} else {
			unset( $c[ $k ] );
		}
	}

	// Opening hours: seven days, or none (clubs use the timetable)
	$hours = array();
	$saved = hub_businesses_db_list( 'biz_hours', $id );
	foreach ( array_keys( HUB_BUSINESSES_HOUR_KEYS ) as $i ) {
		$hours[] = trim( (string) ( isset( $saved[ $i ] ) ? $saved[ $i ] : '' ) );
	}
	$c['hours'] = array_filter( $hours, 'strlen' ) ? $hours : array();

	$c['schedule'] = array();
	foreach ( hub_businesses_db_list( 'biz_schedule', $id ) as $row ) {
		if ( ! empty( $row[0] ) || ! empty( $row[1] ) ) {
			$c['schedule'][] = array( (string) $row[0], (string) $row[1] );
		}
	}

	$c['other_locations'] = array();
	foreach ( hub_businesses_db_list( 'biz_other_locations', $id ) as $row ) {
		$c['other_locations'][] = array( 'label' => $row['label'], 'name' => $row['name'], 'address' => $row['address'] );
	}

	// Logo, cover and photos (media library)
	$logo = hub_businesses_db_attachment( $f( 'biz_logo', $id, 0 ), 'medium_large' );
	if ( $logo ) {
		$c['logo'] = $logo[0];
	} else {
		unset( $c['logo'] );
	}
	$cover = hub_businesses_db_attachment( $f( 'biz_cover', $id, 0 ), 'full' );
	if ( $cover ) {
		$c['cover']     = $cover[0];
		$c['cover_alt'] = $cover[1];
	} else {
		unset( $c['cover'], $c['cover_alt'] );
	}
	$images = $alts = array();
	foreach ( hub_businesses_db_list( 'biz_gallery', $id ) as $aid ) {
		$img = hub_businesses_db_attachment( $aid );
		if ( $img ) {
			$images[] = $img[0];
			$alts[]   = $img[1];
		}
	}
	// Sample listings keep their made-up photo count (the preview shows "+4 photos" tiles from fewer images)
	$sample_n = hub_is_sample( $x ) && isset( $c['photos'] ) ? (int) $c['photos'] : 0;
	if ( $images ) {
		$c['images']     = $images;
		$c['image_alts'] = $alts;
		$c['photos']     = max( count( $images ), $sample_n );
	} else {
		unset( $c['images'], $c['image_alts'] );
		$c['photos'] = $sample_n;
	}

	// Social links: the ones filled in (samples keep their placeholder icons)
	$urls = array();
	foreach ( HUB_BUSINESSES_SOCIAL as $s ) {
		$u = $f( 'biz_' . $s, $id );
		if ( '' !== $u ) {
			$urls[ $s ] = $u;
		}
	}
	if ( $urls || ! hub_is_sample( $x ) ) {
		$c['social']      = array_keys( $urls );
		$c['social_urls'] = $urls;
	}

	$c['news'] = array();
	foreach ( hub_businesses_db_list( 'biz_news', $id ) as $row ) {
		$c['news'][] = array( 'title' => $row['title'], 'url' => $row['url'], 'cat' => $row['cat'], 'date' => $row['date'] );
	}

	$x['claimed'] = $c;
	return $x;
}

/* ------------------------------------------------------------------ writing */

/**
 * Save a business's fields (already checked and cleaned by the wp-admin box). Empty values are removed rather than
 * stored. Then the sort keys are brought in step.
 */
function hub_businesses_db_save_fields( $post_id, array $values ) {
	hub_db_save_meta( $post_id, HUB_BUSINESSES_FIELDS, $values );
	hub_businesses_db_sync_sort( $post_id );
	hub_businesses_db_cache_gen( true );
}

/** _hub_tier, _hub_status and _hub_rank (directory order: Checked, Verified, imported; then plan) from the fields. */
function hub_businesses_db_sync_sort( $post_id ) {
	$f       = 'hub_businesses_db_field';
	$claimed = (bool) $f( 'biz_claimed', $post_id, false );
	$plan    = $f( 'biz_plan', $post_id, 'unclaimed' );
	$tier    = $claimed ? ( 'unclaimed' === $plan ? 'basic' : $plan ) : 'unclaimed';
	$status  = $f( 'biz_checked', $post_id, false ) ? 1 : ( $claimed ? 0 : 2 );
	update_post_meta( $post_id, '_hub_tier', $tier );
	update_post_meta( $post_id, '_hub_status', $status );
	update_post_meta( $post_id, '_hub_rank', $status * 10 + HUB_BUSINESSES_PLAN_RANK[ $tier ] );
	if ( '' === get_post_meta( $post_id, '_hub_order', true ) ) {
		update_post_meta( $post_id, '_hub_order', 100000 + $post_id );   // new listings go after the imported order
	}
}

/**
 * Before writing single fields on a business still on its imported record: fill its fields from that record first
 * (inc/fields-migrate.php), so marking it as having fields does not hide its other details.
 */
function _hub_businesses_db_ensure_fields( $post_id ) {
	if ( hub_businesses_db_uses_fields( $post_id ) ) {
		return;
	}
	$x = get_post_meta( $post_id, '_hub', true );
	if ( is_array( $x ) && function_exists( 'hub_fields_from_record' ) ) {
		hub_db_save_meta( $post_id, HUB_BUSINESSES_FIELDS, hub_fields_from_record( $post_id, $x ) );
	} else {
		update_post_meta( $post_id, '_hub_fields', 1 );
	}
}

/**
 * Set a business's plan (billing: a subscription started, changed or ended). Works for businesses whose details are
 * in their fields and for ones still on their imported record, then keeps the sort keys in step.
 */
function hub_businesses_db_set_plan( $post_id, $plan ) {
	$post_id = (int) $post_id;
	hub_businesses_db_cache_gen( true );
	if ( hub_businesses_db_uses_fields( $post_id ) ) {
		update_post_meta( $post_id, 'biz_plan', $plan );
		hub_businesses_db_sync_sort( $post_id );
		return;
	}
	$x = get_post_meta( $post_id, '_hub', true );
	if ( is_array( $x ) && ! empty( $x['claimed'] ) ) {
		$x['tier'] = $plan;
		update_post_meta( $post_id, '_hub', $x );
	}
	$status = (int) get_post_meta( $post_id, '_hub_status', true );
	update_post_meta( $post_id, '_hub_tier', $plan );
	update_post_meta( $post_id, '_hub_rank', $status * 10 + HUB_BUSINESSES_PLAN_RANK[ $plan ] );
}

/**
 * Mark a business claimed (a claim was approved: Basic plan, claimed today) or unclaimed (its owner left: back to
 * an unclaimed listing). Details, photos, offers and events are kept, and hidden while it's unclaimed.
 */
function hub_businesses_db_set_claimed( $post_id, $claimed ) {
	$post_id = (int) $post_id;
	hub_businesses_db_cache_gen( true );
	_hub_businesses_db_ensure_fields( $post_id );
	if ( $claimed ) {
		update_post_meta( $post_id, 'biz_claimed', '1' );
		update_post_meta( $post_id, 'biz_claimed_since', current_time( 'F Y' ) );   // "November 2026", as on the profile
		$plan = (string) get_post_meta( $post_id, 'biz_plan', true );
		if ( '' === $plan || 'unclaimed' === $plan ) {
			update_post_meta( $post_id, 'biz_plan', 'basic' );
		}
	} else {
		delete_post_meta( $post_id, 'biz_claimed' );
		update_post_meta( $post_id, 'biz_plan', 'unclaimed' );
	}
	hub_businesses_db_sync_sort( $post_id );
}

/* ------------------------------------------------------------------ businesses added from the website */

/**
 * Create a business waiting for review (post status "pending": not on the Hub until staff publish it).
 *
 * @param array $f { name, cats[], area, sub, tagline, address, postcode, phone, website, email, description,
 *                   hours { mon: "9am to 5pm" | "Closed" | '' ... }, facilities[], added_by (user ID), added_as ('owner' | 'recommend') }
 * @return int|WP_Error
 */
function hub_businesses_db_create_pending( array $f ) {
	$content = implode( "\n\n", array_map( function ( $p ) { return '<p>' . esc_html( $p ) . '</p>'; }, array_filter( array_map( 'trim', preg_split( '/\n\s*\n/', $f['description'] ) ) ) ) );
	$id      = wp_insert_post( array( 'post_type' => 'hub_business', 'post_status' => 'pending', 'post_title' => $f['name'], 'post_content' => $content ), true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	wp_set_object_terms( $id, $f['cats'], 'hub_category' );
	wp_set_object_terms( $id, array( $f['area'] ), 'hub_area' );
	$values = array( 'biz_plan' => 'unclaimed', 'biz_claimed' => '' );
	foreach ( array( 'biz_sub' => 'sub', 'biz_tagline' => 'tagline', 'biz_address' => 'address', 'biz_postcode' => 'postcode', 'biz_phone' => 'phone', 'biz_website' => 'website', 'biz_email' => 'email' ) as $field => $k ) {
		$values[ $field ] = (string) $f[ $k ];
	}
	$hours = array();
	foreach ( HUB_BUSINESSES_HOUR_KEYS as $day ) {   // seven days, Monday first
		$hours[] = isset( $f['hours'][ $day ] ) ? (string) $f['hours'][ $day ] : '';
	}
	$values['biz_hours'] = array_filter( $hours, 'strlen' ) ? $hours : array();
	hub_db_save_meta( $id, HUB_BUSINESSES_FIELDS, $values );
	update_post_meta( $id, '_hub_facilities', array_values( $f['facilities'] ) );   // no field for these yet: kept for the listing editor
	update_post_meta( $id, '_hub_added_by', (int) $f['added_by'] );
	update_post_meta( $id, '_hub_added_as', $f['added_as'] );
	hub_businesses_db_sync_sort( $id );
	return $id;
}

/** Add the uploaded photo ($_FILES key) to the media library and make it the listing's photo. Attachment ID or WP_Error. */
function hub_businesses_db_attach_photo( $post_id, $file_key ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$aid = media_handle_upload( $file_key, (int) $post_id, array(), array( 'test_form' => false, 'mimes' => HUB_BUSINESSES_PHOTO_TYPES ) );
	if ( ! is_wp_error( $aid ) ) {
		_hub_businesses_db_ensure_fields( (int) $post_id );
		update_post_meta( (int) $post_id, 'biz_gallery', array( (int) $aid ) );
	}
	return $aid;
}

/** A live or waiting business with the same name and postcode, or 0. */
function hub_businesses_db_find_same( $name, $postcode ) {
	$key = hub_businesses_rule_name_key( $name );
	$ids = get_posts( array(
		'post_type'      => 'hub_business',
		'post_status'    => array( 'publish', 'pending' ),
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => 'biz_postcode', 'value' => array( $postcode, str_replace( ' ', '', $postcode ) ), 'compare' => 'IN' ) ),
	) );
	foreach ( $ids as $id ) {
		if ( hub_businesses_rule_name_key( get_the_title( $id ) ) === $key ) {
			return (int) $id;
		}
	}
	return 0;
}

/** Businesses this user added that are still waiting for review: post IDs. */
function hub_businesses_db_added_pending( $user_id ) {
	return get_posts( array( 'post_type' => 'hub_business', 'post_status' => 'pending', 'posts_per_page' => 5, 'fields' => 'ids', 'meta_key' => '_hub_added_by', 'meta_value' => (int) $user_id ) );
}

/** Who added a business from the website: { user, as ('owner' | 'recommend'), facilities[] } or null. */
function hub_businesses_db_added_info( $post_id ) {
	$user = (int) get_post_meta( (int) $post_id, '_hub_added_by', true );
	if ( ! $user ) {
		return null;
	}
	return array( 'user' => $user, 'as' => (string) get_post_meta( (int) $post_id, '_hub_added_as', true ), 'facilities' => (array) get_post_meta( (int) $post_id, '_hub_facilities', true ) );
}

function hub_businesses_db_send_email( $to, $subject, $body ) {
	return wp_mail( $to, $subject, $body, array( 'Reply-To: Harrow Online <hub@harrowonline.org>' ) );
}

/** Businesses whose name contains $term, for the business picker on offers and classes: [ { id, name, area } ]. */
function hub_businesses_db_search( $term, $limit = 20 ) {
	$q   = new WP_Query( array(
		'post_type'      => 'hub_business',
		'post_status'    => 'any',
		's'              => $term,
		'search_columns' => array( 'post_title' ),
		'posts_per_page' => $limit,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );
	$out = array();
	foreach ( $q->posts as $p ) {
		$a     = get_the_terms( $p->ID, 'hub_area' );
		$out[] = array( 'id' => $p->ID, 'name' => html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ), 'area' => $a && ! is_wp_error( $a ) ? html_entity_decode( $a[0]->name, ENT_QUOTES, 'UTF-8' ) : '' );
	}
	return $out;
}
