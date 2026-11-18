<?php
/**
 * Businesses: directory rows, profiles (imported and claimed), offer cards and category / area archives.
 * Ported from gen_listings.py and gen_claimed.py; same markup.
 */
defined( 'ABSPATH' ) || exit;

/** Business record by post (cached), with its offers (offers shape, app/modules/offers) and profile events attached. */
function hub_business( $post ) {
	static $cache = array();
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	if ( ! isset( $cache[ $post->ID ] ) ) {
		$x = hub_record( $post );
		if ( hub_uses_acf( $post->ID ) ) {
			$x = hub_business_from_acf( $post, $x );   // details edited in wp-admin (inc/business-acf.php)
		}
		if ( ! empty( $x['claimed'] ) ) {
			// description from the post content (editable in wp-admin), one paragraph per <p>
			preg_match_all( '#<p>(.*?)</p>#s', wpautop( $post->post_content ), $m );
			$x['claimed']['description'] = $m[1];
			$offers                       = hub_offers_for_business( $post->ID );   // app/modules/offers
			$x['claimed']['offers']       = $offers['live'];
			$x['claimed']['ended_offers'] = $offers['ended'];
			$x['claimed']['events']       = hub_business_events( $x, $post->ID );
		}
		$x['_id']           = $post->ID;
		$cache[ $post->ID ] = $x;
	}
	return $cache[ $post->ID ];
}

function hub_business_by_slug( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'hub_business' );
	return $p ? hub_business( $p ) : null;
}

/** One-off events by this business (from the events, by organiser name), then its weekly classes (linked in
 *  wp-admin; listings not yet saved with ACF use the imported list). */
function hub_business_events( array $x, $business_id = 0 ) {
	$out   = array();
	$today = hub_today()->format( 'Y-m-d' );
	$evs   = get_posts( array(
		'post_type'      => 'hub_event',
		'posts_per_page' => -1,
		'meta_key'       => '_hub_start',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => '_hub_org', 'value' => $x['claimed']['name'] ),
			array( 'key' => '_hub_day', 'value' => $today, 'compare' => '>=' ),
		),
	) );
	foreach ( $evs as $p ) {
		$ev    = hub_event( $p );
		$d     = $ev['day_date'];
		$out[] = array(
			'title' => $ev['title'], 'when' => $d->format( 'D' ) . ' ' . $d->format( 'j' ) . ' ' . $d->format( 'M' ) . ', ' . $ev['time'],
			'd' => $d->format( 'j' ), 'm' => $d->format( 'M' ), 'w' => $d->format( 'D' ), 'price' => $ev['price'],
			'href' => $ev['href'], 'featured' => ! empty( $ev['featured'] ),
		);
	}
	if ( $business_id && hub_uses_acf( $business_id ) ) {
		return array_merge( $out, hub_class_profile_rows( hub_business_classes( $business_id ) ) );
	}
	foreach ( isset( $x['claimed']['class_events'] ) ? $x['claimed']['class_events'] : array() as $c ) {
		$c['href'] = hub_url( 'events/' . hub_slugify( hub_unescape( $c['title'] ) ) . '/' );
		$out[]     = $c;
	}
	return $out;
}

/** Listings in the directory order (Verified, Checked, imported; then plan; then name). */
function hub_business_query( array $tax = array(), $limit = -1, $exclude = 0 ) {
	$q = array(
		'post_type'      => 'hub_business',
		'posts_per_page' => $limit,
		'meta_key'       => '_hub_order',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( $tax ) {
		$q['tax_query'] = array( 'relation' => 'AND' );
		foreach ( $tax as $t => $slug ) {
			$q['tax_query'][] = array( 'taxonomy' => $t, 'field' => 'slug', 'terms' => $slug );
		}
	}
	if ( $exclude ) {
		$q['post__not_in'] = array( $exclude );
	}
	return get_posts( $q );
}

/** ---------------------------------------------------------------- rows */
function hub_biz_row( array $x ) {
	if ( ! empty( $x['tier'] ) ) {
		return hub_claimed_row( $x );
	}
	$phone = $x['phone_raw'] ? $x['phone_raw'][0] : '';
	$sub   = $x['sub'] ? ' · <span class="muted">' . hub_e( $x['sub'] ) . '</span>' : '';
	$call  = $phone ? '<a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:' . hub_e( $phone ) . '">' . hub_icon( 'phone' ) . 'Call</a>' : '';
	$addr  = $x['address'] ? hub_e( $x['address'] ) . ', ' : '';
	return '<article class="biz-row biz-row--mono biz-row--unclaimed">
        <span class="mono mono--soft" aria-hidden="true">' . hub_e( hub_initials( $x['name'] ) ) . '</span>
        <div class="biz-row__main">
          <span class="biz-card__cat">' . hub_e( hub_cat_name( $x['cat'] ) ) . $sub . '</span>
          <h3 class="biz-row__title"><a href="' . hub_url( 'business/' . $x['slug'] . '/' ) . '">' . hub_name_with_tick( $x ) . '</a></h3>
          <p class="card__meta"><span>' . hub_icon( 'pin' ) . $addr . hub_e( $x['postcode'] ) . '</span><span>' . hub_e( hub_area_name( $x['area'] ) ) . '</span></p>
        </div>
        <div class="biz-row__side">' . $call . '</div>
      </article>';
}

function hub_claimed_row( array $x ) {
	$c        = $x['claimed'];
	$tier     = $x['tier'];
	$promoted = in_array( $tier, HUB_TOP, true );
	$media    = ! empty( $c['logo'] ) || ! empty( $c['images'] );
	$cls      = 'biz-row' . ( $promoted ? ' biz-row--featured' : '' ) . ( $media ? ' biz-row--media' : '' );
	$badges   = $promoted ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : '';
	$pic      = ! empty( $c['logo'] ) ? hub_logo_slot( $c ) : '<div class="' . hub_ph_cls( $x ) . '">' . hub_photo_inner( $x, 0, isset( $c['icon'] ) ? $c['icon'] : 'store' ) . '</div>';
	$n_off    = count( $c['offers'] );
	$offers   = ( $c['offers'] && in_array( $tier, HUB_PAID, true ) ) ? '<span>' . hub_icon( 'tag' ) . $n_off . ' offer' . ( 1 !== $n_off ? 's' : '' ) . '</span>' : '';
	$events   = $c['events'] ? '<span>' . hub_icon( 'calendar' ) . count( $c['events'] ) . ' upcoming</span>' : '';
	return '<article class="' . $cls . '">
        ' . $pic . '
        <div class="biz-row__main">
          <span class="biz-card__cat">' . hub_e( hub_cat_name( $x['cat'] ) ) . ' · <span class="muted">' . hub_e( $x['sub'] ) . '</span></span>
          <h3 class="biz-row__title"><a href="' . hub_url( 'business/' . $x['slug'] . '/' ) . '">' . hub_name_with_tick( $x ) . '</a></h3>
          <p class="card__meta"><span>' . hub_icon( 'pin' ) . hub_address_line( $x ) . '</span><span>' . hub_e( hub_area_name( $x['area'] ) ) . '</span></p>
          <p class="card__text">' . $c['tagline'] . '</p>
          <p class="card__meta">' . hub_today_status( $c ) . $offers . $events . '</p>
        </div>
        <div class="biz-row__side"><div class="cluster">' . $badges . '</div><a class="ho-btn ho-btn--sm ho-btn--outline" href="tel:' . $x['phone_raw'][0] . '">' . hub_icon( 'phone' ) . 'Call</a></div>
      </article>';
}
