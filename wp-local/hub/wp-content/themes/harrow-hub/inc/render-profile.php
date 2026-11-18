<?php
/**
 * Business profiles and category / area archives (gen_listings.profile, gen_claimed.profile_body,
 * gen_listings.archive). Each returns [ page settings, main HTML ].
 */
defined( 'ABSPATH' ) || exit;

function hub_short( $text, $n = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	if ( mb_strlen( $text ) <= $n ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $n );
	$cut = mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) );
	return rtrim( $cut, ',;:' ) . '…';
}

/** seo.listing_indexable: claimed, 50+ words of description and hours (or a schedule) or photos. */
function hub_listing_indexable( array $x ) {
	$c = isset( $x['claimed'] ) ? $x['claimed'] : null;
	if ( ! $c ) {
		return false;
	}
	$has_hours = ! empty( $c['schedule'] );
	foreach ( isset( $c['hours'] ) ? $c['hours'] : array() as $h ) {
		if ( $h && 'closed' !== strtolower( $h ) ) {
			$has_hours = true;
		}
	}
	$words = str_word_count( wp_strip_all_tags( hub_unescape( implode( ' ', $c['description'] ) ) ) );
	return $words >= 50 && ( $has_hours || ( isset( $c['photos'] ) ? $c['photos'] : 0 ) > 0 );
}

function hub_profile( array $x, array $related ) {
	$cat   = $x['cat'];
	$area  = $x['area'];
	$crumb = hub_crumbs( array( array( 'Hub', hub_url() ), array( hub_cat_name( $cat ), hub_url( "category/{$cat}/" ) ), array( hub_area_name( $area ), hub_url( "area/{$area}/" ) ), array( $x['name'], '' ) ) );
	$rel   = implode( '', array_map( 'hub_biz_row', $related ) );
	$rel   = '' !== $rel ? $rel : '<p class="muted">No other listings in this area yet.</p>';
	if ( ! empty( $x['tier'] ) ) {
		$meta = array(
			'title'       => ! empty( $x['claimed']['seo_title'] ) ? $x['claimed']['seo_title'] : $x['name'] . ' in ' . hub_area_name( $area ),
			'description' => $x['name'] . ' (' . $x['sub'] . ') in ' . hub_area_name( $area ) . ', Harrow: opening hours, photos, Local Offers, events and contact details on Harrow Online Hub.',
			'hubnav'      => 'directory',
		);
		if ( ! hub_is_sample( $x ) ) {
			$meta['description'] = hub_short( hub_unescape( $x['claimed']['tagline'] ) );
		}
		if ( ! hub_listing_indexable( $x ) ) {
			$meta['robots'] = 'noindex, follow';
		}
		return array( $meta, hub_profile_body( $x, $rel, $crumb ) );
	}

	$maps = 'https://www.google.com/maps/search/?api=1&query=' . hub_quote( $x['name'] . ' ' . $x['postcode'] );
	$addr = implode( ', ', array_filter( array( $x['address'], hub_area_name( $area ), $x['postcode'] ) ) );
	$info = '<li>' . hub_icon( 'pin' ) . '<div><address>' . hub_e( $addr ) . '</address><a class="small" href="' . $maps . '" target="_blank" rel="noopener">Get directions</a></div></li>';
	foreach ( $x['phone'] as $i => $disp ) {
		$info .= '<li>' . hub_icon( 'phone' ) . '<a href="tel:' . hub_e( $x['phone_raw'][ $i ] ) . '">' . hub_e( $disp ) . '</a></li>';
	}
	foreach ( array_slice( $x['web'], 0, 1 ) as $w ) {
		$parts = explode( '//', $w, 2 );
		$shown = rtrim( end( $parts ), '/' );
		$info .= '<li>' . hub_icon( 'globe' ) . '<a href="' . hub_e( $w ) . '" target="_blank" rel="noopener nofollow">' . hub_e( $shown ) . '</a></li>';
	}
	foreach ( array_slice( $x['email'], 0, 1 ) as $m ) {
		$info .= '<li>' . hub_icon( 'mail' ) . '<a href="mailto:' . hub_e( $m ) . '">' . hub_e( $m ) . '</a></li>';
	}
	$call = $x['phone_raw'] ? '<a class="ho-btn ho-btn--primary" href="tel:' . hub_e( $x['phone_raw'][0] ) . '">' . hub_icon( 'phone' ) . 'Call</a>' : '';
	$web  = $x['web'] ? '<a class="ho-btn ho-btn--outline" href="' . hub_e( $x['web'][0] ) . '" target="_blank" rel="noopener nofollow">' . hub_icon( 'globe' ) . 'Website</a>' : '';
	$sub  = $x['sub'] ? '<span>' . hub_e( $x['sub'] ) . '</span>' : '';
	$catn = hub_cat_name( $cat );
	$arn  = hub_area_name( $area );
	$claim = hub_url( 'claim/?listing=' . $x['slug'] );
	$body = $crumb . '

<section class="profile-head ho-container">
  <div class="profile-head__row">
    <span class="mono mono--lg mono--soft" aria-hidden="true">' . hub_e( hub_initials( $x['name'] ) ) . '</span>
    <div class="profile-head__main">
      <h1 class="profile-head__title">' . hub_name_with_tick( $x, null, false ) . '</h1>
      <p class="profile-head__meta">
        <a href="' . hub_url( "category/{$cat}/" ) . '">' . hub_icon( 'tag' ) . hub_e( $catn ) . '</a>
        ' . $sub . '
        <a href="' . hub_url( "area/{$area}/" ) . '">' . hub_icon( 'pin' ) . hub_e( $arn ) . '</a>
      </p>
      <div class="profile-head__actions">
        ' . $call . '
        ' . $web . '
        <a class="ho-btn ho-btn--outline" href="' . $maps . '" target="_blank" rel="noopener">' . hub_icon( 'map' ) . 'Directions</a>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside">
  <div>
    <section class="profile-section">
      <h2>About ' . hub_e( $x['name'] ) . '</h2>
      <div class="limited-note">' . hub_icon( 'info' ) . '<p class="mb-0">This business hasn&rsquo;t added a description, opening hours or photos yet. The details below come from open map data and may be out of date. <a href="' . $claim . '">Is this your business? Claim it free</a> to update it.</p></div>
    </section>

    <section class="profile-section">
      <h2>Contact and location</h2>
      <div class="grid grid-2">
        <ul class="info-list">' . $info . '</ul>
        <a class="ph ph--map" href="' . $maps . '" target="_blank" rel="noopener" aria-label="Open ' . hub_e( $x['name'] ) . ' in Google Maps">' . hub_icon( 'pin' ) . '</a>
      </div>
    </section>

    <section class="profile-section">
      <h2>Opening hours</h2>
      <p class="muted mb-0">Not added yet. Owners can add opening hours after claiming this listing.</p>
    </section>

    <section class="profile-section">
      <div class="sec-head"><div><h2 class="mb-0">More ' . hub_e( strtolower( $catn ) ) . ' in ' . hub_e( $arn ) . '</h2></div><a class="link-arrow" href="' . hub_url( "category/{$cat}/" ) . '">All ' . hub_e( strtolower( $catn ) ) . ' in Harrow ' . hub_icon( 'arrow' ) . '</a></div>
      ' . $rel . '
    </section>

    <p class="mt-4"><a class="report-link" href="' . hub_url( 'report/?listing=' . $x['slug'] ) . '">' . hub_icon( 'flag' ) . 'Report a mistake or ask for this listing to be removed</a></p>
  </div>

  <aside class="sticky">
    <div class="claim-box">
      <h2>Is this your business?</h2>
      <p>Claim this listing for free to add photos, opening hours and a description, and to get a verified badge.</p>
      <ul class="ticks"><li>Free forever on the Basic plan</li><li>Ranked above unclaimed listings</li><li>Offers, events and classes on Featured or Local Partner</li></ul>
      <a class="ho-btn ho-btn--primary ho-btn--block" href="' . $claim . '">Claim this business</a>
      <p class="xsmall mt-3 mb-0 muted">Takes about 2 minutes. We check every claim before it goes live.</p>
    </div>
    <div class="panel mt-4">
      <h3 class="panel__title">Get Harrow&rsquo;s best offers</h3>
      <p class="small muted">Local Offers and what&rsquo;s on, every morning in the Harrow Online Newsletter.</p>
      <a class="link-arrow" href="https://harrowonline.org/newsletter/">Sign up free ' . hub_icon( 'arrow' ) . '</a>
    </div>
  </aside>
</div>';
	$meta = array(
		'title'       => $x['name'] . ' in ' . $arn,
		'description' => $x['name'] . ', ' . strtolower( $catn ) . ' in ' . $arn . ', Harrow. Address, phone and directions on Harrow Online Hub.',
		'robots'      => 'noindex, follow',
		'hubnav'      => 'directory',
	);
	return array( $meta, $body );
}

function hub_profile_event_row( array $ev ) {
	$rep   = ! empty( $ev['class'] ) ? '<span class="badge">' . hub_icon( 'repeat' ) . 'Weekly class</span>' : '';
	$feat  = ! empty( $ev['featured'] ) ? '<span class="badge badge--featured">Featured</span>' : '';
	$month = array( 'Oct' => 10, 'Nov' => 11, 'Dec' => 12 );
	$mn    = isset( $month[ $ev['m'] ] ) ? $month[ $ev['m'] ] : 11;
	return '<article class="event-row' . ( ! empty( $ev['featured'] ) ? ' event-row--featured' : '' ) . '">
          <time class="date-tile" datetime="2026-' . sprintf( '%02d', $mn ) . '-' . sprintf( '%02d', (int) $ev['d'] ) . '"><span class="date-tile__m">' . $ev['m'] . '</span><span class="date-tile__d">' . $ev['d'] . '</span><span class="date-tile__w">' . $ev['w'] . '</span></time>
          <div class="ph">' . hub_icon( ! empty( $ev['class'] ) ? 'repeat' : 'calendar' ) . '</div>
          <div>
            <div class="cluster mb-2">' . $feat . $rep . '</div>
            <h3 class="event-row__title"><a href="' . $ev['href'] . '">' . $ev['title'] . '</a></h3>
            <p class="card__meta"><span>' . hub_icon( 'clock' ) . $ev['when'] . '</span></p>
          </div>
          <div class="event-row__side"><strong>' . $ev['price'] . '</strong></div>
        </article>';
}

function hub_profile_body( array $x, $related_html, $crumbs_html ) {
	$c      = $x['claimed'];
	$tier   = $x['tier'];
	$name   = $x['name'];
	$area   = $x['area'];
	$cat    = $x['cat'];
	$top    = in_array( $tier, HUB_TOP, true );
	$paid   = in_array( $tier, HUB_PAID, true );
	$ic     = isset( $c['icon'] ) ? $c['icon'] : 'store';
	$maps   = hub_has_address( $c ) ? 'https://www.google.com/maps/search/?api=1&query=' . hub_quote( hub_unescape( $name ) . ' ' . $x['postcode'] ) : '';
	$sample = hub_is_sample( $x );
	$badges = hub_tier_badge( $tier );
	if ( ! empty( $c['botw'] ) ) {
		$badges = '<span class="botw">' . hub_icon( 'star' ) . 'Business of the Week</span>' . $badges;
	}

	if ( $top && ! empty( $c['cover'] ) ) {
		$fit   = isset( $c['cover_bg'] ) ? $c['cover_bg'] : '';
		$style = $fit ? ' style="background:' . $fit . '"' : '';
		$cover = '<div class="ho-container profile-cover"><div class="ph ph--banner ph--photo' . ( $fit ? ' ph--contain' : '' ) . '"' . $style . '>'
			. '<img src="' . hub_img( $c['cover'] ) . '" alt="' . hub_e( isset( $c['cover_alt'] ) ? $c['cover_alt'] : $name ) . '" width="1600" height="600" fetchpriority="high"></div></div>';
	} elseif ( $top && ( $sample || ! empty( $c['images'] ) ) ) {
		$cover = '<div class="ho-container profile-cover"><div class="' . hub_ph_cls( $x, 'ph ph--banner ph--dark' ) . '">' . hub_photo_inner( $x, 0, $ic, $name ) . '</div></div>';
	} else {
		$cover = '';
	}

	$n = $paid ? ( isset( $c['photos'] ) ? $c['photos'] : 1 ) : 1;
	if ( ! $sample && ! empty( $c['images'] ) && $paid ) {
		$imgs  = array_slice( $c['images'], 0, 5 );
		$tiles = '';
		foreach ( array_keys( $imgs ) as $k ) {
			$tiles .= '<div class="' . hub_ph_cls( $x ) . '">' . hub_photo_inner( $x, $k ) . '</div>';
		}
		$gallery = '<div class="gallery gallery--' . count( $imgs ) . ' mb-6" aria-label="Photos">' . $tiles . '</div>';
	} elseif ( $n >= 3 ) {
		$tiles = array();
		for ( $k = 0; $k < min( $n, 5 ); $k++ ) {
			$tiles[] = '<div class="' . hub_ph_cls( $x ) . '">' . hub_photo_inner( $x, $k + 1, $ic ) . '</div>';
		}
		if ( $n > 5 ) {
			$tiles[ count( $tiles ) - 1 ] = '<div class="gallery__more"><div class="' . hub_ph_cls( $x ) . '">' . hub_photo_inner( $x, 2, $ic ) . '</div><span>+' . ( $n - 4 ) . ' photos</span></div>';
		}
		$gallery = '<div class="gallery mb-6" aria-label="Photos">' . implode( '', $tiles ) . '</div>';
	} elseif ( ! $sample ) {
		$gallery = '';
	} else {
		$gallery = '<div class="ph ph--wide mb-6">' . hub_icon( $ic ) . '</div>';
	}

	$desc     = implode( '', array_map( function ( $p ) { return "<p>{$p}</p>"; }, $c['description'] ) );
	$sections = array( '<section class="profile-section"><h2>About ' . hub_e( $name ) . '</h2><div class="profile-desc">' . $desc . '</div></section>' );

	$offers = $paid ? $c['offers'] : array();
	if ( $offers ) {
		$cards      = implode( '', array_map( function ( $o ) { return hub_part( 'offers/card-full', array( 'offer' => $o, 'show_business' => false ) ); }, $offers ) );
		$sections[] = '<section class="profile-section"><div class="sec-head"><h2 class="mb-0">Local Offers</h2><a class="link-arrow" href="' . hub_url( 'offers/' ) . '">All offers ' . hub_icon( 'arrow' ) . '</a></div><div class="stack">' . $cards . '</div></section>';
	}
	if ( $c['events'] ) {
		$rows       = implode( '', array_map( 'hub_profile_event_row', $c['events'] ) );
		$sections[] = '<section class="profile-section"><div class="sec-head"><h2 class="mb-0">Upcoming events and classes</h2><a class="link-arrow" href="' . hub_url( 'events/' ) . '">What&rsquo;s On ' . hub_icon( 'arrow' ) . '</a></div><div>' . $rows . '</div></section>';
	}

	if ( hub_has_address( $c ) ) {
		$where = '<li>' . hub_icon( 'pin' ) . '<div><address>' . hub_address_line( $x, true ) . '</address><a class="small" href="' . $maps . '" target="_blank" rel="noopener">Get directions</a></div></li>';
	} else {
		$where = '<li>' . hub_icon( 'pin' ) . '<div><span>' . hub_address_line( $x ) . '</span><p class="small muted mb-0">Works at customers&rsquo; homes and premises. No shop or office to visit.</p></div></li>';
	}
	$info = $where . '<li>' . hub_icon( 'phone' ) . '<a href="tel:' . $x['phone_raw'][0] . '">' . hub_e( $c['phone'] ) . '</a></li>';
	foreach ( isset( $c['phones_extra'] ) ? $c['phones_extra'] : array() as $ph ) {
		$info .= '<li>' . hub_icon( 'phone' ) . '<a href="tel:' . preg_replace( '/[^\d+]/', '', $ph ) . '">' . hub_e( $ph ) . '</a></li>';
	}
	foreach ( array_slice( isset( $x['web'] ) ? $x['web'] : array(), 0, 1 ) as $w ) {
		$parts = explode( '//', $w, 2 );
		$info .= '<li>' . hub_icon( 'globe' ) . '<a href="' . hub_e( $w ) . '" target="_blank" rel="noopener">' . hub_e( rtrim( end( $parts ), '/' ) ) . '</a></li>';
	}
	foreach ( array_slice( isset( $x['email'] ) ? $x['email'] : array(), 0, 1 ) as $m ) {
		$info .= '<li>' . hub_icon( 'mail' ) . '<a href="mailto:' . hub_e( $m ) . '">' . hub_e( $m ) . '</a></li>';
	}
	foreach ( isset( $c['other_locations'] ) ? $c['other_locations'] : array() as $loc ) {
		$lmaps = 'https://www.google.com/maps/search/?api=1&query=' . hub_quote( $loc['name'] . ' ' . $loc['address'] );
		$info .= '<li>' . hub_icon( 'pin' ) . '<div><span class="small muted">' . $loc['label'] . '</span><address>' . $loc['name'] . ', ' . $loc['address'] . '</address><a class="small" href="' . $lmaps . '" target="_blank" rel="noopener">Get directions</a></div></li>';
	}
	$mapbox     = $maps ? '<a class="ph ph--map" href="' . $maps . '" target="_blank" rel="noopener" aria-label="Open ' . hub_e( $name ) . ' in Google Maps">' . hub_icon( 'pin' ) . '</a>' : '';
	$sections[] = '<section class="profile-section"><h2>Contact and location</h2>
      <div class="grid grid-2"><ul class="info-list">' . $info . '</ul>
      ' . $mapbox . '</div></section>';

	$news = array_filter( isset( $c['news'] ) ? $c['news'] : array(), function ( $n ) { return 0 === strpos( (string) ( isset( $n['url'] ) ? $n['url'] : '' ), 'https://harrowonline.org/' ); } );
	if ( $news ) {
		$items = '';
		foreach ( $news as $n_ ) {
			$items .= '<article class="news-link"><div class="ph">' . hub_icon( 'news' ) . '</div><div><span class="news-link__cat">' . $n_['cat'] . '</span>
          <h3 class="news-link__title"><a href="' . $n_['url'] . '">' . $n_['title'] . '</a></h3><p class="news-link__date">' . $n_['date'] . '</p></div></article>';
		}
		$sections[] = '<section class="profile-section"><h2>In the news</h2>' . $items . '</section>';
	}

	if ( ! $top ) {
		$catn       = hub_cat_name( $cat );
		$sections[] = '<section class="profile-section"><div class="sec-head"><h2 class="mb-0">More ' . hub_e( strtolower( $catn ) ) . ' in ' . hub_e( hub_area_name( $area ) ) . '</h2>
          <a class="link-arrow" href="' . hub_url( "category/{$cat}/" ) . '">All ' . hub_e( strtolower( $catn ) ) . ' in Harrow ' . hub_icon( 'arrow' ) . '</a></div>' . $related_html . '</section>';
	}

	$today_i = hub_weekday( hub_today() );
	if ( ! empty( $c['schedule'] ) ) {
		$hours_title = isset( $c['schedule_title'] ) ? $c['schedule_title'] : 'When we meet';
		$hours       = '';
		foreach ( $c['schedule'] as $row ) {
			$hours .= '<tr><th scope="row">' . $row[0] . '</th><td>' . $row[1] . '</td></tr>';
		}
		$hours_note = ! empty( $c['schedule_note'] ) ? '<p class="small muted mt-3 mb-0">' . $c['schedule_note'] . '</p>' : '';
	} else {
		$hours_title = 'Opening hours';
		$hours_note  = ! empty( $c['hours_note'] ) ? '<p class="small muted mt-3 mb-0">' . $c['hours_note'] . '</p>' : '';
		$hours       = '';
		foreach ( HUB_DAYS as $i => $d ) {
			if ( ! isset( $c['hours'][ $i ] ) ) {
				break;
			}
			$hours .= '<tr' . ( $i === $today_i ? ' class="is-today"' : '' ) . '><th scope="row">' . $d . '</th><td>' . $c['hours'][ $i ] . '</td></tr>';
		}
	}
	$social = '';
	if ( $paid && ! empty( $c['social'] ) ) {
		$labels = array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn', 'x' => 'X' );
		$urls   = isset( $c['social_urls'] ) ? $c['social_urls'] : array();
		$links  = '';
		foreach ( $c['social'] as $s ) {
			$links .= isset( $urls[ $s ] ) ? '<a href="' . hub_e( $urls[ $s ] ) . '" target="_blank" rel="noopener" aria-label="' . $labels[ $s ] . '">' . hub_icon( $s ) . '</a>'
				: '<a href="#" aria-label="' . $labels[ $s ] . '">' . hub_icon( $s ) . '</a>';
		}
		$social = '<div class="profile-card__follow"><h3 class="profile-card__h">Follow</h3><div class="social-row">' . $links . '</div></div>';
	}
	if ( $sample ) {
		$trust = '<p class="profile-card__trust">' . hub_icon( 'alert', 'ho-icon ho-icon--sm' ) . '<span><strong>Sample listing.</strong> Made up for the preview; removed before launch.</span></p>';
	} else {
		$note  = ! empty( $c['verified_note'] ) ? $c['verified_note'] : 'Verified by the business since ' . $c['claimed'] . '.';
		$trust = '<p class="profile-card__trust">' . hub_icon( ! empty( $x['checked'] ) ? 'check' : 'verified', 'ho-icon ho-icon--sm' ) . '<span>' . $note . '</span></p>';
	}
	$aside = '<aside class="sticky profile-aside">
    <div class="panel profile-card">
      <h2 class="panel__title">' . $hours_title . '</h2>
      <p class="mb-3">' . hub_today_status( $c ) . '</p>
      <table class="hours"><caption class="ho-sr">' . $hours_title . '</caption><tbody>' . $hours . '</tbody></table>' . $hours_note . '
      <a class="ho-btn ho-btn--primary ho-btn--block mt-4" href="tel:' . $x['phone_raw'][0] . '">' . hub_icon( 'phone' ) . 'Call ' . hub_e( $c['phone'] ) . '</a>
      ' . $social . '
      ' . $trust . '
    </div>
  </aside>';

	$mono    = ! empty( $c['logo'] ) ? '<span class="mono mono--lg mono--logo"><img src="' . hub_img( $c['logo'] ) . '" alt="' . hub_e( $name ) . ' badge" width="480" height="480"></span>'
		: '<span class="mono mono--lg' . ( $top ? ' mono--red' : '' ) . '" aria-hidden="true">' . hub_e( hub_initials( $name ) ) . '</span>';
	$summary = ! empty( $c['tagline'] ) ? '<p class="profile-head__summary">' . $c['tagline'] . '</p>' : '';
	$web     = ! empty( $x['web'] ) ? '<a class="ho-btn ho-btn--outline" href="' . hub_e( $x['web'][0] ) . '" target="_blank" rel="noopener">' . hub_icon( 'globe' ) . 'Website</a>' : '';
	$dir     = $maps ? '<a class="ho-btn ho-btn--outline" href="' . $maps . '" target="_blank" rel="noopener">' . hub_icon( 'map' ) . 'Directions</a>' : '';

	return $crumbs_html . '
' . $cover . '
<section class="profile-head profile-head--card ho-container' . ( $top ? ' profile-head--premium' : '' ) . '">
  <div class="profile-head__row">
    ' . $mono . '
    <div class="profile-head__main">
      <div class="cluster">' . $badges . '</div>
      <h1 class="profile-head__title">' . hub_name_with_tick( $x, $name, false ) . '</h1>
      ' . $summary . '
      <p class="profile-head__meta">
        <a href="' . hub_url( "category/{$cat}/" ) . '">' . hub_icon( 'tag' ) . hub_e( hub_cat_name( $cat ) ) . '</a>
        <span>' . hub_e( $x['sub'] ) . '</span>
        <a href="' . hub_url( "area/{$area}/" ) . '">' . hub_icon( 'pin' ) . hub_e( hub_area_name( $area ) ) . '</a>
        ' . hub_today_status( $c ) . '
      </p>
      <div class="profile-head__actions">
        <a class="ho-btn ho-btn--primary" href="tel:' . $x['phone_raw'][0] . '">' . hub_icon( 'phone' ) . 'Call</a>
        ' . $web . '
        ' . $dir . '
        <button class="ho-btn ho-btn--outline" type="button" data-copy>' . hub_icon( 'external' ) . 'Share</button>
      </div>
    </div>
  </div>
</section>

<div class="ho-container layout-aside profile-layout">
  <div class="profile-main">
    ' . $gallery . '
    ' . implode( '', $sections ) . '
    <p class="mt-4"><a class="report-link" href="' . hub_url( 'report/?listing=' . $x['slug'] ) . '">' . hub_icon( 'flag' ) . 'Report a mistake on this listing</a></p>
  </div>
  ' . $aside . '
</div>';
}

/** ---------------------------------------------------------------- category and area archives */
function hub_options( $tax ) {
	$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
	$rows  = array();
	foreach ( $terms as $t ) {
		$rows[ html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ] = $t->slug;
	}
	ksort( $rows, SORT_STRING );
	$out = '';
	foreach ( $rows as $n => $s ) {
		$out .= '<option value="' . $s . '">' . hub_e( $n ) . '</option>';
	}
	return $out;
}

function hub_archive( $kind, WP_Term $term ) {
	$slug  = $term->slug;
	$items = hub_business_query( array( 'cat' === $kind ? 'hub_category' : 'hub_area' => $slug ) );
	$count = count( $items );
	if ( 'cat' === $kind ) {
		$name   = hub_cat_name( $slug );
		$title  = "{$name} in Harrow";
		$intro  = (string) get_term_meta( $term->term_id, 'hub_intro', true );
		$lead   = '' !== $intro ? $intro : number_format( $count ) . ' ' . strtolower( $name ) . ' listed across Harrow. Filter by area, or claim your listing free.';
		$crumb  = hub_crumbs( array( array( 'Hub', hub_url() ), array( $name, '' ) ) );
		$data   = 'data-cat="' . $slug . '"';
		$other  = '<label class="fsel"><span class="ho-sr">Area</span><select id="f-area" name="area"><option value="">All areas</option>' . hub_options( 'hub_area' ) . '</select></label>';
		$hidden = '<input type="hidden" name="cat" value="' . $slug . '">';
	} else {
		$name   = hub_area_name( $slug );
		$title  = "Businesses in {$name}";
		$intro  = '';
		$lead   = number_format( $count ) . " local businesses in {$name}: shops, places to eat, trades and services. Browse by category below.";
		$crumb  = hub_crumbs( array( array( 'Hub', hub_url() ), array( 'Areas', hub_url() . '#areas' ), array( $name, '' ) ) );
		$data   = 'data-area="' . $slug . '"';
		$other  = '<label class="fsel"><span class="ho-sr">Category</span><select id="f-cat" name="cat"><option value="">All categories</option>' . hub_options( 'hub_category' ) . '</select></label>';
		$hidden = '<input type="hidden" name="area" value="' . $slug . '">';
	}
	$first = '';
	foreach ( array_slice( $items, 0, 20 ) as $p ) {
		$first .= hub_biz_row( hub_business( $p ) );
	}
	$body = $crumb . '
<header class="page-head ho-container">
  <h1 class="page-head__title">' . hub_e( $title ) . '</h1>
  <p class="page-head__lead">' . hub_e( $lead ) . '</p>
</header>

<div class="ho-container">
  <!-- Clean layout (Nov 2026): no filter sidebar. Search and small filters on one line, quick links as one chip row. -->
  <form class="dline" id="filters" data-directory-form action="" method="get" role="search" aria-label="Search and filter">
    ' . $hidden . '
    <label class="fsearch">' . hub_icon( 'search' ) . '<span class="ho-sr">Search ' . hub_e( $title ) . '</span><input id="f-q" type="search" name="q" placeholder="Search by name or service" autocomplete="off"></label>
    ' . $other . '
    <label class="fcheck"><input type="checkbox" name="web" value="1"> Has a website</label>
    <label class="fcheck"><input type="checkbox" name="phone" value="1"> Has a phone</label>
  </form>

  <div data-directory ' . $data . ' data-page-size="20">
    <h2 class="ho-sr">Listings</h2>
    <div class="toolbar dtoolbar">
      <div class="dtoolbar__lead"><p class="toolbar__count" data-count><strong>' . number_format( $count ) . '</strong> businesses</p><a class="dtoolbar__key" href="' . hub_url( 'pricing/' ) . '#fb-labels"><svg class="ho-icon" aria-hidden="true"><use href="#i-info"/></svg>What the icons mean</a></div>
      <label class="fsel fsel--sort"><span class="dtoolbar__label">Sort</span><select id="f-sort" name="sort" form="filters"><option value="relevance">Best match</option><option value="name">Name (A to Z)</option></select></label>
    </div>
    <div class="active-filters" data-active hidden></div>
    <div data-results>' . $first . '</div>
    <nav class="pager" data-pager aria-label="Pages"></nav>
  </div>

  <div class="cta-band mt-6">
    <div><h2>Run a business in Harrow?</h2><p>Claim your free listing or add a missing business. It takes two minutes.</p></div>
    <div class="cta-band__actions"><a class="ho-btn ho-btn--primary" href="' . hub_url( 'add-business/' ) . '">List your business</a><a class="ho-btn ho-btn--outline" href="' . hub_url( 'pricing/' ) . '">See plans</a></div>
  </div>
</div>';
	$indexable = false;
	if ( 'cat' === $kind && '' !== $intro ) {
		$n = 0;
		foreach ( $items as $p ) {
			if ( hub_listing_indexable( hub_business( $p ) ) ) {
				$n++;
			}
		}
		$indexable = $n >= 5;
	}
	$meta = array( 'title' => $title, 'description' => hub_short( $lead ), 'hubnav' => 'directory' );
	if ( ! $indexable ) {
		$meta['robots'] = 'noindex, follow';
	}
	return array( $meta, $body );
}
