<?php
/**
 * Businesses (post type hub_business): the only file that reads or writes businesses in WordPress.
 *
 * A business's details are its ACF fields (inc/acf-fields.php), on top of the record imported into its "_hub" meta.
 * Posts saved with ACF have "_hub_acf" = 1. Claimed businesses keep their profile in $record['claimed'].
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
 * The business record: the imported record with the ACF fields on top. Cached per post.
 * $record['claimed'] (array) is set for claimed businesses only.
 */
function hub_businesses_db_record( WP_Post $post ) {
	static $cache = array();
	$key = $post->ID . '@' . hub_businesses_db_cache_gen();
	if ( ! isset( $cache[ $key ] ) ) {
		$x = hub_record( $post );
		if ( hub_businesses_db_uses_acf( $post->ID ) ) {
			$x = _hub_businesses_db_acf( $post, $x );
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

/* ------------------------------------------------------------------ ACF fields */

/** True when this post's details live in ACF fields. */
function hub_businesses_db_uses_acf( $post_id ) {
	return function_exists( 'get_field' ) && '1' === (string) get_post_meta( $post_id, '_hub_acf', true );
}

/** An ACF field value as stored (no formatting), or $default when empty. */
function hub_businesses_db_field( $name, $post_id, $default = '' ) {
	$v = get_field( $name, $post_id, false );
	return ( null === $v || '' === $v || false === $v || array() === $v ) ? $default : $v;
}

/** Attachment ID -> [ url, alt ], or null. */
function hub_businesses_db_attachment( $id, $size = 'large' ) {
	$id  = (int) $id;
	$url = $id ? wp_get_attachment_image_url( $id, $size ) : '';
	return $url ? array( $url, (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : null;
}

/** The record from the ACF fields, on top of the imported record. */
function _hub_businesses_db_acf( WP_Post $post, array $x ) {
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
	$extras = array();
	foreach ( (array) get_field( 'biz_phones_extra', $id ) as $row ) {
		if ( ! empty( $row['biz_phone_extra'] ) ) {
			$extras[] = trim( $row['biz_phone_extra'] );
		}
	}
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
	foreach ( HUB_BUSINESSES_HOUR_KEYS as $k ) {
		$hours[] = trim( (string) get_post_meta( $id, 'biz_hours_biz_hours_' . $k, true ) );
	}
	$c['hours'] = array_filter( $hours, 'strlen' ) ? $hours : array();

	$c['schedule'] = array();
	foreach ( (array) get_field( 'biz_schedule', $id ) as $row ) {
		if ( ! empty( $row['biz_sch_day'] ) || ! empty( $row['biz_sch_text'] ) ) {
			$c['schedule'][] = array( $row['biz_sch_day'], $row['biz_sch_text'] );
		}
	}

	$c['other_locations'] = array();
	foreach ( (array) get_field( 'biz_other_locations', $id ) as $row ) {
		$c['other_locations'][] = array( 'label' => $row['biz_loc_label'], 'name' => $row['biz_loc_name'], 'address' => $row['biz_loc_address'] );
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
	foreach ( (array) $f( 'biz_gallery', $id, array() ) as $aid ) {
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
	foreach ( (array) get_field( 'biz_news', $id ) as $row ) {
		$c['news'][] = array( 'title' => $row['biz_news_title'], 'url' => $row['biz_news_url'], 'cat' => $row['biz_news_cat'], 'date' => $row['biz_news_date'] );
	}

	$x['claimed'] = $c;
	return $x;
}

/* ------------------------------------------------------------------ writing: keep the sort keys in step */

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

add_action( 'acf/save_post', function ( $post_id ) {
	if ( is_numeric( $post_id ) && 'hub_business' === get_post_type( $post_id ) ) {
		update_post_meta( $post_id, '_hub_acf', 1 );
		hub_businesses_db_sync_sort( $post_id );
		hub_businesses_db_cache_gen( true );
		// Saved in wp-admin: the plan may have changed (other modules follow it, e.g. the owner's role)
		do_action( 'hub_business_plan_changed', (int) $post_id, hub_businesses_db_meta( get_post( $post_id ) )['plan'] );
	}
}, 20 );

/**
 * Set a business's plan (billing: a subscription started, changed or ended). Works for businesses whose details are
 * in ACF fields and for ones still on their imported record, then keeps the sort keys in step.
 */
function hub_businesses_db_set_plan( $post_id, $plan ) {
	$post_id = (int) $post_id;
	hub_businesses_db_cache_gen( true );
	if ( hub_businesses_db_uses_acf( $post_id ) && function_exists( 'update_field' ) ) {
		update_field( 'biz_plan', $plan, $post_id );
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
	update_post_meta( $post_id, '_hub_acf', 1 );
	update_field( 'biz_claimed', $claimed ? 1 : 0, $post_id );
	if ( $claimed ) {
		update_field( 'biz_claimed_since', current_time( 'F Y' ), $post_id );   // "November 2026", as on the profile
		$plan = (string) get_field( 'biz_plan', $post_id );
		if ( '' === $plan || 'unclaimed' === $plan ) {
			update_field( 'biz_plan', 'basic', $post_id );
		}
	} else {
		update_field( 'biz_plan', 'unclaimed', $post_id );
	}
	hub_businesses_db_sync_sort( $post_id );
}
