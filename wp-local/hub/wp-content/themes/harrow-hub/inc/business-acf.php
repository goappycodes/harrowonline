<?php
/**
 * Business pages read their details from the ACF fields (inc/acf-fields.php).
 *
 * hub_business() builds the same record the templates always used ($x, with $x['claimed'] for claimed listings), so
 * every page that shows a business (profile, listing rows, category and area pages, offer pages, class pages) picks
 * up what is saved in wp-admin. Posts copied to ACF have the meta "_hub_acf" = 1 (set by hub_acf_migrate() and on
 * every save); posts without it still read the imported record in "_hub".
 *
 * Offers and classes belong to a business through their "Business" field (offer_business / class_business), kept in
 * step with the business's "Offers" and "Weekly classes" fields by ACF.
 */
defined( 'ABSPATH' ) || exit;

const HUB_SOCIAL = array( 'facebook', 'instagram', 'tiktok', 'linkedin', 'x' );
const HUB_HOUR_KEYS = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );

/** True when this post's details live in ACF fields. */
function hub_uses_acf( $post_id ) {
	return function_exists( 'get_field' ) && '1' === (string) get_post_meta( $post_id, '_hub_acf', true );
}

/** An ACF field value as stored (no formatting), or $default when empty. */
function hub_field( $name, $post_id, $default = '' ) {
	$v = get_field( $name, $post_id, false );
	return ( null === $v || '' === $v || false === $v || array() === $v ) ? $default : $v;
}

/** Attachment ID -> [ url, alt ] for the templates. */
function hub_attachment( $id, $size = 'large' ) {
	$id  = (int) $id;
	$url = $id ? wp_get_attachment_image_url( $id, $size ) : '';
	return $url ? array( $url, (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : null;
}

/** Phone number for tel: links, in international form (UK "020 8488 9310" -> "+442084889310"). */
function hub_phone_raw( $phone ) {
	$raw = preg_replace( '/[^\d+]/', '', (string) $phone );
	return preg_match( '/^0\d{9,10}$/', $raw ) ? '+44' . substr( $raw, 1 ) : $raw;
}

/** The business record ($x) from the ACF fields, on top of the imported record. */
function hub_business_from_acf( WP_Post $post, array $x ) {
	$id   = $post->ID;
	$cats = get_the_terms( $id, 'hub_category' );
	$ars  = get_the_terms( $id, 'hub_area' );

	$x['id']       = isset( $x['id'] ) ? $x['id'] : 'post-' . $id;
	$x['slug']     = $post->post_name;
	$x['name']     = $post->post_title;
	$x['cat']      = $cats && ! is_wp_error( $cats ) ? $cats[0]->slug : ( isset( $x['cat'] ) ? $x['cat'] : '' );
	$x['area']     = $ars && ! is_wp_error( $ars ) ? $ars[0]->slug : ( isset( $x['area'] ) ? $x['area'] : '' );
	$x['sub']      = hub_field( 'biz_sub', $id );
	$x['address']  = hub_field( 'biz_address', $id );
	$x['postcode'] = hub_field( 'biz_postcode', $id );

	$phone  = trim( hub_field( 'biz_phone', $id ) );
	$extras = array();
	foreach ( (array) get_field( 'biz_phones_extra', $id ) as $row ) {
		if ( ! empty( $row['biz_phone_extra'] ) ) {
			$extras[] = trim( $row['biz_phone_extra'] );
		}
	}
	$phones          = array_values( array_filter( array_merge( array( $phone ), $extras ) ) );
	$x['phone']      = $phones;
	$x['phone_raw']  = array_map( 'hub_phone_raw', $phones );
	$web             = hub_field( 'biz_website', $id );
	$x['web']        = $web ? array( $web ) : array();
	$mail            = hub_field( 'biz_email', $id );
	$x['email']      = $mail ? array( $mail ) : array();
	$lat             = hub_field( 'biz_lat', $id, null );
	$lng             = hub_field( 'biz_lng', $id, null );
	$x['lat']        = null === $lat ? null : (float) $lat;
	$x['lng']        = null === $lng ? null : (float) $lng;
	$x['checked']    = (bool) hub_field( 'biz_checked', $id, false );

	if ( ! hub_field( 'biz_claimed', $id, false ) ) {
		unset( $x['tier'], $x['claimed'] );
		return $x;
	}

	$plan      = hub_field( 'biz_plan', $id, 'basic' );
	$x['tier'] = 'unclaimed' === $plan ? 'basic' : $plan;
	$c         = isset( $x['claimed'] ) && is_array( $x['claimed'] ) ? $x['claimed'] : array();

	$c['name']     = $x['name'];
	$c['slug']     = $x['slug'];
	$c['cat']      = $x['cat'];
	$c['area']     = $x['area'];
	$c['sub']      = $x['sub'];
	$c['address']  = $x['address'];
	$c['postcode'] = $x['postcode'];
	$c['phone']    = $phone;
	$c['tier']     = $x['tier'];
	$c['website']  = $web;
	$c['tagline']  = hub_field( 'biz_tagline', $id );
	$c['icon']     = hub_field( 'biz_icon', $id, 'store' );
	$c['claimed']  = hub_field( 'biz_claimed_since', $id, isset( $c['claimed'] ) ? $c['claimed'] : '' );
	$c['botw']     = (bool) hub_field( 'biz_botw', $id, false );
	$c['phones_extra'] = $extras;
	foreach ( array( 'verified_note', 'seo_title', 'service_area', 'hours_note', 'status', 'schedule_title', 'schedule_note', 'cover_bg' ) as $k ) {
		$v = hub_field( 'biz_' . $k, $id );
		if ( '' !== $v ) {
			$c[ $k ] = $v;
		} else {
			unset( $c[ $k ] );
		}
	}

	// Opening hours: seven days, or none (clubs use the timetable)
	$hours = array();
	foreach ( HUB_HOUR_KEYS as $k ) {
		$hours[] = trim( (string) get_post_meta( $id, 'biz_hours_biz_hours_' . $k, true ) );
	}
	$c['hours'] = array_filter( $hours, 'strlen' ) ? $hours : array();

	$c['schedule'] = array();
	foreach ( (array) get_field( 'biz_schedule', $id ) as $row ) {
		if ( ! empty( $row['biz_sch_day'] ) || ! empty( $row['biz_sch_text'] ) ) {
			$c['schedule'][] = array( $row['biz_sch_day'], $row['biz_sch_text'] );
		}
	}
	if ( ! $c['schedule'] ) {
		unset( $c['schedule'] );
	}

	$c['other_locations'] = array();
	foreach ( (array) get_field( 'biz_other_locations', $id ) as $row ) {
		$c['other_locations'][] = array( 'label' => $row['biz_loc_label'], 'name' => $row['biz_loc_name'], 'address' => $row['biz_loc_address'] );
	}

	// Logo, cover and photos (media library)
	$logo = hub_attachment( hub_field( 'biz_logo', $id, 0 ), 'medium_large' );
	if ( $logo ) {
		$c['logo'] = $logo[0];
	} else {
		unset( $c['logo'] );
	}
	$cover = hub_attachment( hub_field( 'biz_cover', $id, 0 ), 'full' );
	if ( $cover ) {
		$c['cover']     = $cover[0];
		$c['cover_alt'] = $cover[1];
	} else {
		unset( $c['cover'], $c['cover_alt'] );
	}
	$images = $alts = array();
	foreach ( (array) hub_field( 'biz_gallery', $id, array() ) as $aid ) {
		$img = hub_attachment( $aid );
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
	foreach ( HUB_SOCIAL as $s ) {
		$u = hub_field( 'biz_' . $s, $id );
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

/** Weekly classes run by a business (its "Weekly classes" / the class's "Business" field), in the classes order. */
function hub_business_classes( $business_id ) {
	return get_posts( array(
		'post_type'      => 'hub_class',
		'posts_per_page' => -1,
		'meta_key'       => '_hub_order',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => array( array( 'key' => 'class_business', 'value' => (string) $business_id ) ),
	) );
}

/** A business's classes as profile rows: the next date each one runs, soonest first (like the events). */
function hub_class_profile_rows( array $posts ) {
	$today = hub_today();
	$rows  = array();
	foreach ( $posts as $p ) {
		$c     = hub_class( $p );
		$day   = (int) $c['day'];
		$ahead = ( $day - hub_weekday( $today ) + 7 ) % 7;
		$d     = $today->modify( '+' . $ahead . ' day' );
		$rows[] = array(
			array( $ahead, (int) $c['order'] ),   // soonest day first; same day in the classes order
			array(
				'title' => $c['title'], 'when' => 'Every ' . HUB_DAYS[ $day ] . ', ' . $c['time'],
				'd' => $d->format( 'j' ), 'm' => $d->format( 'M' ), 'w' => $d->format( 'D' ),
				'price' => $c['price'], 'href' => $c['href'], 'class' => true, 'featured' => ! empty( $c['featured'] ),
			),
		);
	}
	usort( $rows, function ( $a, $b ) { return $a[0] <=> $b[0]; } );
	return array_column( $rows, 1 );
}

/* ------------------------------------------------------------ one business per offer / class
 * When an offer or class is added on a business, ACF adds that business to the offer's or class's "Business" field
 * as a list ([old, new]). An offer or class has one business, so keep the new one and take the offer or class off
 * the old business's list. (ACF is already inside its own two-way update here, so this does not loop.) */
foreach ( array( 'offer' => 'biz_offers', 'class' => 'biz_classes' ) as $hub_k => $hub_list ) {
	add_filter( "acf/update_value/key=field_hub_{$hub_k}_business", function ( $value, $post_id ) use ( $hub_list ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$ids = array_values( array_filter( array_map( 'intval', $value ) ) );
		$new = $ids ? end( $ids ) : 0;
		foreach ( array_diff( $ids, array( $new ) ) as $old ) {
			$list = array_map( 'intval', acf_get_array( get_field( $hub_list, $old, false ) ) );
			update_field( 'field_hub_' . $hub_list, array_values( array_diff( $list, array( (int) $post_id ) ) ), $old );
		}
		return $new ? (string) $new : '';
	}, 20, 2 );
}

/* ------------------------------------------------------------ keep the sort fields in step when a business is saved */
add_action( 'acf/save_post', function ( $post_id ) {
	if ( ! is_numeric( $post_id ) ) {
		return;
	}
	$type = get_post_type( $post_id );
	if ( 'hub_business' === $type ) {
		update_post_meta( $post_id, '_hub_acf', 1 );
		hub_business_sync_sort( $post_id );
	} elseif ( 'hub_offer' === $type ) {
		update_post_meta( $post_id, '_hub_business', (int) get_post_meta( $post_id, 'offer_business', true ) );
		if ( '' === get_post_meta( $post_id, '_hub_index', true ) ) {
			update_post_meta( $post_id, '_hub_index', 99 );
		}
	}
}, 20 );

/** _hub_tier, _hub_status and _hub_rank (directory order: Checked, Verified, imported; then plan) from the fields. */
function hub_business_sync_sort( $post_id ) {
	$rank    = array( 'partner' => 0, 'premium' => 0, 'enhanced' => 1, 'basic' => 2, 'unclaimed' => 3 );
	$claimed = (bool) hub_field( 'biz_claimed', $post_id, false );
	$plan    = hub_field( 'biz_plan', $post_id, 'unclaimed' );
	$tier    = $claimed ? ( 'unclaimed' === $plan ? 'basic' : $plan ) : 'unclaimed';
	$status  = hub_field( 'biz_checked', $post_id, false ) ? 1 : ( $claimed ? 0 : 2 );
	update_post_meta( $post_id, '_hub_tier', $tier );
	update_post_meta( $post_id, '_hub_status', $status );
	update_post_meta( $post_id, '_hub_rank', $status * 10 + $rank[ $tier ] );
	if ( '' === get_post_meta( $post_id, '_hub_order', true ) ) {
		update_post_meta( $post_id, '_hub_order', 100000 + $post_id );   // new listings go after the imported order
	}
}

/* ------------------------------------------------------------ newsletter band (Hub settings > Newsletter) */
function hub_newsletter_settings() {
	static $s = null;
	if ( null !== $s ) {
		return $s;
	}
	$opt = function ( $name, $default ) {
		$v = function_exists( 'get_field' ) ? get_field( $name, 'option', false ) : null;
		return ( null === $v || '' === $v ) ? $default : $v;
	};
	$editions = function_exists( 'get_field' ) ? get_field( 'nl_editions', 'option' ) : null;
	$list     = array();
	foreach ( is_array( $editions ) ? $editions : array() as $row ) {
		if ( ! empty( $row['nl_ed_value'] ) && ! empty( $row['nl_ed_label'] ) ) {
			$list[] = array( $row['nl_ed_value'], $row['nl_ed_label'], (string) $row['nl_ed_desc'], ! empty( $row['nl_ed_checked'] ) );
		}
	}
	if ( ! $list ) {
		$list = hub_newsletter_default_editions();
	}
	$s = array(
		'show'        => (bool) $opt( 'nl_show', true ),
		'title'       => $opt( 'nl_title', 'Harrow in your inbox, every morning' ),
		'text'        => $opt( 'nl_text', 'The day’s local news, what’s on and the best Local Offers. Free, from Harrow Online Newsletter.' ),
		'editions'    => $list,
		'placeholder' => $opt( 'nl_placeholder', 'Your email address' ),
		'button'      => $opt( 'nl_button', 'Sign up free' ),
		'action'      => $opt( 'nl_action', 'https://harrowonline.org/wp-json/ho/v1/subscribe' ),
		'source'      => $opt( 'nl_source', 'hub-strip' ),
	);
	return $s;
}

/** [ value, name, description, ticked ] */
function hub_newsletter_default_editions() {
	return array(
		array( 'daily', 'Daily News', 'Every morning: Harrow’s news', true ),
		array( 'weekend', 'The Weekend', 'Thursdays: what’s on and offers', false ),
		array( 'business', 'Harrow Business', 'Mondays: local business news', false ),
	);
}

/** The sign-up band above the footer (same markup as the prototype's nl-strip partial). */
function hub_newsletter_strip() {
	$s = hub_newsletter_settings();
	if ( ! $s['show'] ) {
		return;
	}
	$picks = '';
	foreach ( $s['editions'] as $e ) {
		$picks .= "\n          " . '<label class="nl-pick"><input type="checkbox" name="editions[]" value="' . esc_attr( $e[0] ) . '"' . ( $e[3] ? ' checked' : '' ) . '><span><strong>' . esc_html( $e[1] ) . '</strong>' . ( '' !== $e[2] ? '<small>' . esc_html( $e[2] ) . '</small>' : '' ) . '</span></label>';
	}
	?>
<!-- Newsletter sign-up above the footer (Hub settings > Newsletter): the reader picks each edition separately
     (hub.js 5i shows known subscribers a short "you're signed up" line instead). -->
<section class="nl-band nl-band--strip" aria-label="Newsletter sign-up">
  <div class="ho-container">
    <div class="nl-band__box">
      <div class="nl-band__text">
        <p class="nl-band__title"><?php echo esc_html( $s['title'] ); ?></p>
        <p><?php echo esc_html( $s['text'] ); ?></p>
      </div>
      <form class="nl-band__form" action="<?php echo esc_url( $s['action'] ); ?>" method="post" data-nl-form>
        <input type="hidden" name="signup_source" value="<?php echo esc_attr( $s['source'] ); ?>">
        <fieldset class="nl-band__editions">
          <legend class="ho-sr">Choose your newsletters</legend><?php echo $picks; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>

        </fieldset>
        <div class="nl-band__row">
          <label class="ho-sr" for="nl-email">Email address</label>
          <input class="input" id="nl-email" type="email" name="email" placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>" autocomplete="email" required>
          <button class="ho-btn ho-btn--primary" type="submit"><?php echo esc_html( $s['button'] ); ?></button>
        </div>
        <p class="ho-nl__msg nl-band__msg" role="status" aria-live="polite"></p>
      </form>
    </div>
  </div>
</section>
	<?php
}
