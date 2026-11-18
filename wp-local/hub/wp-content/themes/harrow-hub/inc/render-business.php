<?php
/**
 * Businesses: directory rows, profiles (imported and claimed), offer cards and category / area archives.
 * Ported from gen_listings.py and gen_claimed.py; same markup.
 */
defined( 'ABSPATH' ) || exit;

/** Business record by post (cached), with its live offers and profile events attached. */
function hub_business( $post ) {
	static $cache = array();
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	if ( ! isset( $cache[ $post->ID ] ) ) {
		$x = hub_record( $post );
		if ( ! empty( $x['claimed'] ) ) {
			// description from the post content (editable in wp-admin), one paragraph per <p>
			preg_match_all( '#<p>(.*?)</p>#s', $post->post_content, $m );
			$x['claimed']['description'] = $m[1];
			list( $live, $ended )         = hub_business_offers( $post->ID );
			$x['claimed']['offers']       = $live;
			$x['claimed']['ended_offers'] = $ended;
			$x['claimed']['events']       = hub_business_events( $x );
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

/** Live and ended offers of a business, in their original order. */
function hub_business_offers( $business_id ) {
	$posts = get_posts( array(
		'post_type'      => 'hub_offer',
		'posts_per_page' => -1,
		'meta_key'       => '_hub_index',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => array( array( 'key' => '_hub_business', 'value' => $business_id ) ),
	) );
	$live = $ended = array();
	foreach ( $posts as $p ) {
		$o = hub_offer( $p );
		if ( $o['live'] ) {
			$live[] = $o;
		} else {
			$ended[] = $o;
		}
	}
	return array( $live, $ended );
}

/** Offer record with dates worked out from today (hub_data.offer_dates). */
function hub_offer( $post ) {
	$post        = get_post( $post );
	$o           = hub_record( $post );
	$o['title']  = $post->post_title;
	$o['text']   = $post->post_content;
	$o['slug']   = $post->post_name;
	$o['href']   = hub_url( 'offers/' . $post->post_name . '/' );
	$o['_id']    = $post->ID;
	$o['_biz']   = (int) get_post_meta( $post->ID, '_hub_business', true );
	if ( empty( $o['end'] ) ) {
		$o['end_date']   = null;
		$o['days_left']  = 9999;
		$o['live']       = true;
		$o['ends_short'] = '';
	} else {
		$end             = new DateTimeImmutable( $o['end'], wp_timezone() );
		$days            = (int) hub_today()->diff( $end )->format( '%r%a' );
		$o['end_date']   = $end;
		$o['days_left']  = $days;
		$o['live']       = $days >= 0;
		$o['ends_short'] = $end->format( 'j M' );
	}
	return $o;
}

/** One-off events by this business (from the events, by organiser name), then its weekly classes. */
function hub_business_events( array $x ) {
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

/** ---------------------------------------------------------------- offers (cards on profiles, tiles) */
function hub_ends_label( array $o ) {
	if ( null === $o['end_date'] ) {
		return 'No end date';
	}
	$d = $o['days_left'];
	if ( $d < 0 ) {
		return 'Ended ' . $o['ends_short'];
	}
	if ( 0 === $d ) {
		return 'Ends today';
	}
	if ( 1 === $d ) {
		return 'Ends tomorrow';
	}
	return $d <= 7 ? "Ends in {$d} days" : 'Ends ' . $o['ends_short'];
}

function hub_unlock_box( array $x, array $o, $note = true ) {
	static $uid = 0;
	$uid++;
	$id   = 'unlock-' . substr( $x['slug'], 0, 20 ) . '-' . $uid;
	$note = $note ? '<span class="offer__lock-note">We&rsquo;ll email you a link to confirm. Already a subscriber? Use the email you signed up with.</span>' : '';
	return '
          <div class="offer__lock">
            <p>' . hub_icon( 'lock', 'ho-icon ho-icon--sm' ) . 'Subscriber-only. Enter your email to unlock.</p>
            <form data-unlock action="' . hub_url( 'wp-json/ho/v1/unlock' ) . '" method="post">
              <input type="hidden" name="signup_source" value="subscriber-offer">
              <input type="hidden" name="offer" value="' . $o['slug'] . '">
              <label class="ho-sr" for="' . $id . '">Email address</label>
              <input id="' . $id . '" type="email" name="email" placeholder="Your email" autocomplete="email" required>
              <button class="ho-btn ho-btn--sm ho-btn--primary" type="submit">Unlock</button>
            </form>' . $note . '
          </div>';
}

function hub_offer_card( array $x, array $o, $show_biz = true, $heading = 'h3' ) {
	$featured = ! empty( $o['featured'] );
	$soon     = $o['days_left'] >= 0 && $o['days_left'] <= 7;
	$cls      = 'offer' . ( $featured ? ' offer--featured' : '' ) . ( ! empty( $o['sub'] ) ? ' offer--locked' : '' );
	$badges   = $featured ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : '';
	if ( ! empty( $o['sub'] ) ) {
		$badges .= hub_sub_lock();
	}
	if ( $soon ) {
		$badges .= '<span class="badge badge--soon">' . hub_icon( 'clock' ) . 'Ends soon</span>';
	}
	$badges .= '<span class="biz-card__cat">' . hub_e( hub_cat_name( $x['cat'] ) ) . '</span>';
	if ( ! empty( $o['sub'] ) ) {
		$code = ! empty( $o['code'] ) ? '<span class="offer__code offer__code--pending" data-sub-code data-offer="' . $o['slug'] . '">Code shown after you confirm your email</span>' : '<span>No code needed</span>';
	} else {
		$code = ! empty( $o['code'] ) ? '<span class="offer__code">' . $o['code'] . '</span>' : '<span>No code needed</span>';
	}
	$lock = ! empty( $o['sub'] ) ? hub_unlock_box( $x, $o ) : '';
	$biz  = $show_biz ? '<a class="offer__biz" href="' . hub_url( 'business/' . $x['slug'] . '/' ) . '">' . hub_e( $x['name'] ) . ' &middot; ' . hub_e( hub_area_name( $x['area'] ) ) . '</a>' : '';
	return '<article class="' . $cls . '" data-cat="' . $x['cat'] . '" data-area="' . $x['area'] . '" data-sub="' . ( ! empty( $o['sub'] ) ? 1 : 0 ) . '">
        <div class="offer__body">
          <div class="cluster">' . $badges . '</div>
          <' . $heading . ' class="offer__title"><a href="' . $o['href'] . '">' . $o['title'] . '</a></' . $heading . '>
          ' . $biz . '
          <p class="offer__text">' . $o['text'] . '</p>
          <div class="offer__foot"><span class="offer__ends' . ( $soon ? ' offer__ends--soon' : '' ) . '">' . hub_icon( 'calendar', 'ho-icon ho-icon--sm' ) . ' ' . hub_ends_label( $o ) . '</span>' . $code . '</div>
          <a class="offer__more" href="' . $o['href'] . '">Terms and how to redeem ' . hub_icon( 'arrow', 'ho-icon ho-icon--sm' ) . '</a>' . $lock . '
        </div>
      </article>';
}

/** gen_claimed.offer_value(): short headline value ("20% OFF", "2-FOR-1", "FREE COFFEE"). */
function hub_offer_value( array $o ) {
	if ( ! empty( $o['value'] ) ) {
		return $o['value'];
	}
	$t = str_replace( '&pound;', '£', $o['title'] );
	foreach ( array(
		array( '/(\d+)%\s*off/i', '%s% OFF' ), array( '/£(\d+)\s*off/i', '£%s OFF' ), array( '/(\d)-for-(\d)/i', null ),
		array( '/£(\d+)\s*cuts/i', '£%s CUTS' ), array( '/for\s*£(\d+)/i', '£%s' ),
	) as $rule ) {
		if ( preg_match( $rule[0], $t, $m ) ) {
			return null === $rule[1] ? $m[1] . '-FOR-' . $m[2] : str_replace( '%s', $m[1], $rule[1] );
		}
	}
	if ( preg_match( '/no joining fee/i', $t ) ) {
		return 'NO JOINING FEE';
	}
	if ( preg_match( '/^(?:a\s+)?free\s+(\w+)/i', $t, $m ) ) {
		return 'FREE ' . strtoupper( $m[1] );
	}
	if ( preg_match( '/^(.{0,24}?\bfree)\b/i', $t, $m ) ) {
		return strtoupper( $m[1] );
	}
	$first = explode( ',', $t );
	return strtoupper( $first[0] );
}

function hub_offer_tile( array $x, array $o ) {
	$lock = ! empty( $o['sub'] ) ? '<span class="offer-tile__lock" title="Subscribers only">' . hub_icon( 'lock', 'ho-icon ho-icon--sm' ) . '<span class="offer-tile__lock-text">Subscribers</span></span>' : '';
	return '<a class="offer-tile" href="' . $o['href'] . '">
        ' . $lock . '
        <span class="offer-tile__biz">' . hub_e( $x['name'] ) . ', ' . hub_e( hub_area_name( $x['area'] ) ) . '</span>
        <strong class="offer-tile__value">' . hub_offer_value( $o ) . '</strong>
        <span class="offer-tile__title">' . $o['title'] . '</span>
      </a>';
}

/** Every live offer from a business on a paid plan: featured first, then by plan (gen_claimed.all_offers). */
function hub_all_offers() {
	static $all = null;
	if ( null !== $all ) {
		return $all;
	}
	$rank = array( 'partner' => 0, 'premium' => 0, 'enhanced' => 1, 'basic' => 2, 'unclaimed' => 3 );
	$all  = array();
	foreach ( hub_business_query() as $p ) {
		$tier = get_post_meta( $p->ID, '_hub_tier', true );
		if ( ! in_array( $tier, HUB_PAID, true ) ) {
			continue;
		}
		$x = hub_business( $p );
		foreach ( $x['claimed']['offers'] as $o ) {
			$all[] = array( $x, $o );
		}
	}
	$i = 0;
	foreach ( $all as $k => $pair ) {
		$all[ $k ][2] = $i++;
	}
	usort( $all, function ( $a, $b ) use ( $rank ) {
		$ka = array( empty( $a[1]['featured'] ) ? 1 : 0, $rank[ $a[0]['tier'] ], $a[2] );
		$kb = array( empty( $b[1]['featured'] ) ? 1 : 0, $rank[ $b[0]['tier'] ], $b[2] );
		return $ka <=> $kb;
	} );
	return $all;
}
