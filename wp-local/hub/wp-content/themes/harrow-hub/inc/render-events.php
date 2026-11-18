<?php
/**
 * Events and weekly classes: /events/{slug}/ pages and the cards they use. Ported from tools/gen_events.py.
 */
defined( 'ABSPATH' ) || exit;

const HUB_ORIGIN = 'https://harrowonline.org/hub';

/** Event record with its dates (day, start, end) as objects. */
function hub_event( $post ) {
	$post           = get_post( $post );
	$ev             = hub_record( $post );
	$ev['title']    = $post->post_title;
	$ev['desc']     = $post->post_content;
	$ev['slug']     = $post->post_name;
	$ev['href']     = hub_url( 'events/' . $post->post_name . '/' );
	$tz             = wp_timezone();
	$ev['day_date'] = new DateTimeImmutable( $ev['day'], $tz );
	$ev['start_dt'] = new DateTimeImmutable( $ev['start'], $tz );
	$ev['end_dt']   = new DateTimeImmutable( $ev['end'], $tz );
	return $ev;
}

/** Every event, by start time then title (hub_data.load_events order). */
function hub_events() {
	static $all = null;
	if ( null === $all ) {
		$all = array_map( 'hub_event', get_posts( array( 'post_type' => 'hub_event', 'posts_per_page' => -1, 'meta_key' => '_hub_start', 'orderby' => 'meta_value', 'order' => 'ASC' ) ) );
		usort( $all, function ( $a, $b ) { return array( $a['start'], $a['title'] ) <=> array( $b['start'], $b['title'] ); } );
	}
	return $all;
}

function hub_class( $post ) {
	$post        = get_post( $post );
	$c           = hub_record( $post );
	$c['title']  = $post->post_title;
	$c['desc']   = $post->post_content;
	$c['slug']   = $post->post_name;
	$c['href']   = hub_url( 'events/' . $post->post_name . '/' );
	return $c;
}

function hub_classes() {
	static $all = null;
	if ( null === $all ) {
		$all = array_map( 'hub_class', get_posts( array( 'post_type' => 'hub_class', 'posts_per_page' => -1, 'meta_key' => '_hub_order', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) ) );
	}
	return $all;
}

function hub_long_date( DateTimeInterface $d ) {
	return HUB_DAYS[ hub_weekday( $d ) ] . ' ' . $d->format( 'j' ) . ' ' . $d->format( 'F' );
}

function hub_event_badges( array $x, $extra = '' ) {
	return ( ! empty( $x['featured'] ) ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : '' ) . $extra;
}

function hub_ev_media( array $ev, $extra_cls = '', $eager = false ) {
	if ( ! empty( $ev['image'] ) ) {
		$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
		return '<div class="ph ph--photo' . $extra_cls . '"><img src="' . hub_img( $ev['image'] ) . '" alt="" width="1200" height="800" ' . $load . '></div>';
	}
	return '<div class="ph' . $extra_cls . '">' . hub_icon( $ev['icon'] ) . '</div>';
}

function hub_event_card( array $ev ) {
	$feat   = ! empty( $ev['featured'] );
	$badges = hub_event_badges( $ev );
	$top    = $badges ? '<div class="card__badges">' . $badges . "</div>\n        " : '';
	$d      = $ev['day_date'];
	return '<article class="card event-card' . ( $feat ? ' card--featured' : '' ) . '">
        ' . $top . '<time class="date-tile" datetime="' . $d->format( 'Y-m-d' ) . '"><span class="date-tile__m">' . $d->format( 'M' ) . '</span><span class="date-tile__d">' . $d->format( 'j' ) . '</span><span class="date-tile__w">' . $d->format( 'D' ) . '</span></time>
        ' . hub_ev_media( $ev, $feat ? ' ph--red' : '' ) . '
        <div class="card__body">
          <span class="biz-card__cat">' . $ev['cat'] . '</span>
          <h3 class="card__title"><a href="' . $ev['href'] . '">' . $ev['title'] . '</a></h3>
          <p class="card__meta"><span>' . hub_icon( 'clock' ) . $ev['time'] . '</span><span>' . hub_icon( 'pin' ) . $ev['area'] . '</span></p>
          <p class="card__meta"><span>' . hub_icon( 'ticket' ) . $ev['price'] . '</span></p>
        </div>
      </article>';
}

/** UK local time -> UTC stamp for calendar links (GMT outside 29 Mar to 25 Oct 2026, as the prototype). */
function hub_is_bst( DateTimeImmutable $t ) {
	$naive = $t->format( 'Y-m-d H:i:s' );
	return $naive >= '2026-03-29 00:00:00' && $naive < '2026-10-25 01:00:00';
}
function hub_utc( DateTimeImmutable $t ) {
	return ( hub_is_bst( $t ) ? $t->modify( '-1 hour' ) : $t )->format( 'Ymd\THis\Z' );
}
function hub_offset( DateTimeImmutable $t ) {
	return hub_is_bst( $t ) ? '+01:00' : '+00:00';
}

function hub_calendar_links( array $ev, $url ) {
	$title = hub_plain( $ev['title'] );
	$place = hub_plain( $ev['venue'] . ', ' . $ev['area'] . ', Harrow' );
	$s     = hub_utc( $ev['start_dt'] );
	$e     = hub_utc( $ev['end_dt'] );
	$google = 'https://calendar.google.com/calendar/render?action=TEMPLATE&amp;text=' . hub_quote_plus( $title ) . "&amp;dates={$s}/{$e}"
		. '&amp;location=' . hub_quote_plus( $place ) . '&amp;details=' . hub_quote_plus( $url );
	$iso    = function ( DateTimeImmutable $t ) {
		return ( '+01:00' === hub_offset( $t ) ? $t->modify( '-1 hour' ) : $t )->format( 'Y-m-d\TH:i:s\Z' );
	};
	$outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&amp;rru=addevent&amp;subject='
		. hub_quote_plus( $title ) . '&amp;startdt=' . hub_quote( $iso( $ev['start_dt'] ), '' ) . '&amp;enddt=' . hub_quote( $iso( $ev['end_dt'] ), '' )
		. '&amp;location=' . hub_quote_plus( $place );
	$ics = implode( "\r\n", array(
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Harrow Online Hub//EN', 'BEGIN:VEVENT',
		'UID:' . $ev['slug'] . '-' . $ev['day_date']->format( 'Y' ) . '@harrowonline.org', 'DTSTAMP:20261111T090000Z', "DTSTART:{$s}", "DTEND:{$e}",
		'SUMMARY:' . str_replace( ',', '\\,', $title ), 'LOCATION:' . str_replace( ',', '\\,', $place ), "URL:{$url}", 'END:VEVENT', 'END:VCALENDAR',
	) );
	return array( $google, $outlook, 'data:text/calendar;charset=utf-8,' . hub_quote( $ics, ':/@.-_ ' ) );
}

/** The Hub business with this name (an event's organiser or a class's provider), or null. */
function hub_business_by_name( $name ) {
	static $by = null;
	if ( null === $by ) {
		$by = array();
		foreach ( get_posts( array( 'post_type' => 'hub_business', 'posts_per_page' => -1, 'meta_query' => array( array( 'key' => '_hub_tier', 'value' => 'unclaimed', 'compare' => '!=' ) ) ) ) as $p ) {
			$x = hub_record( $p );
			if ( ! empty( $x['claimed'] ) ) {
				$by[ $x['claimed']['name'] ] = $x;
			}
		}
	}
	return isset( $by[ $name ] ) ? $by[ $name ] : null;
}

/** [ page settings, main HTML ] for /events/{slug}/ (gen_events.event_page). */
function hub_event_page( array $ev ) {
	$url     = HUB_ORIGIN . '/events/' . $ev['slug'] . '/';
	$title   = $ev['title'];
	$d       = $ev['day_date'];
	$past    = $d < hub_today();
	$orgx    = ! empty( $ev['org'] ) ? hub_business_by_name( $ev['org'] ) : null;
	list( $google, $outlook, $ics ) = hub_calendar_links( $ev, $url );
	$maps     = 'https://www.google.com/maps/search/?api=1&amp;query=' . hub_quote_plus( hub_plain( $ev['venue'] . ' ' . $ev['area'] . ' Harrow' ) );
	$when_sub = $ev['time'] . ( ! empty( $ev['note'] ) ? '. ' . $ev['note'] . '.' : '' );
	$cat_slug = hub_slugify( hub_plain( $ev['cat'] ) );
	$free     = 0 === strpos( strtolower( hub_plain( $ev['price'] ) ), 'free' );
	$events   = hub_url( 'events/' );

	$ended = $past ? '
<div class="ho-container mt-4">
  <div class="notice notice--warn">' . hub_icon( 'alert' ) . '
    <div><p><strong>This event has ended.</strong> It took place on ' . hub_long_date( $d ) . ' ' . $d->format( 'Y' ) . '.</p>
    <p><a href="' . $events . '">See what&rsquo;s on in Harrow this week</a></p></div>
  </div>
</div>' : '';
	$tags       = hub_event_badges( $ev ) . '<a class="biz-card__cat" href="' . $events . '?cat=' . $cat_slug . '">' . $ev['cat'] . '</a>';
	$when_line  = hub_long_date( $d ) . ' ' . $d->format( 'Y' ) . ' &middot; ' . $when_sub;
	$price_line = $ev['price'] . ( empty( $ev['age'] ) ? '' : ' &middot; ' . $ev['age'] );
	$about      = '' !== $ev['desc'] ? '<p>' . $ev['desc'] . '</p>' : '<p>Full details from the organiser coming soon.</p>';

	$after = max( $ev['start'], hub_today()->format( 'Y-m-d' ) . 'T00:00:00' );
	$more  = array();
	foreach ( hub_events() as $e ) {
		if ( $e['slug'] !== $ev['slug'] && $e['start'] >= $after && count( $more ) < 3 ) {
			$more[] = $e;
		}
	}
	$more_html = '';
	if ( $more ) {
		$more_html = '
    <section class="event-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More coming up</h2>
        <a class="link-arrow" href="' . $events . '">All events ' . hub_icon( 'arrow' ) . '</a>
      </div>
      <div class="grid grid-3">
        ' . implode( '', array_map( 'hub_event_card', $more ) ) . '
      </div>
    </section>';
	}
	$org_row = '';
	$org     = null;
	if ( $orgx ) {
		$org      = array( 'name' => $orgx['claimed']['name'], 'slug' => $orgx['slug'], 'area' => hub_area_name( $orgx['area'] ) );
		$words    = preg_split( '/\s+/', trim( hub_plain( $org['name'] ) ) );
		$initials = strtoupper( implode( '', array_map( function ( $w ) { return mb_substr( $w, 0, 1 ); }, array_slice( $words, 0, 2 ) ) ) );
		$org_row  = '
    <a class="offer-biz" href="' . hub_url( 'business/' . $org['slug'] . '/' ) . '">
      <span class="mono" aria-hidden="true">' . $initials . '</span>
      <span class="offer-biz__main"><span class="eyebrow mb-0">Organiser</span><strong>' . $org['name'] . '</strong><span>' . $org['area'] . '</span></span>
      <span class="offer-biz__go">View profile ' . hub_icon( 'arrow', 'ho-icon ho-icon--sm' ) . '</span>
    </a>';
	}
	$share   = hub_quote( hub_plain( $title ) . ', ' . $d->format( 'D' ) . ' ' . $d->format( 'j' ) . ' ' . $d->format( 'M' ) . ' ' . $url, '' );
	$actions = $past ? '' : '
      <div class="event-actions" id="add-to-calendar">
        <span>Add to calendar</span>
        <a href="' . $google . '" rel="noopener" target="_blank">Google</a>
        <a href="' . $outlook . '" rel="noopener" target="_blank">Outlook</a>
        <a download="' . $ev['slug'] . '.ics" href="' . $ics . '">Apple (.ics)</a>
      </div>
      <div class="share-row" aria-label="Share this event">
        <span>Share</span>
        <a href="https://wa.me/?text=' . $share . '" rel="noopener" target="_blank" aria-label="Share on WhatsApp">' . hub_icon( 'whatsapp' ) . '</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=' . hub_quote( $url, '' ) . '" rel="noopener" target="_blank" aria-label="Share on Facebook">' . hub_icon( 'facebook' ) . '</a>
        <a href="mailto:?subject=' . hub_quote( hub_plain( $title ) ) . '&amp;body=' . hub_quote( $url, '' ) . '" aria-label="Share by email">' . hub_icon( 'mail' ) . '</a>
        <button type="button" data-copy="' . $url . '" data-copied="Link copied">Copy link</button>
      </div>';
	$pub = ! empty( $ev['sample'] ) ? 'Made-up sample for the preview.' : 'Checked by Harrow Online.';

	$body = '<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="' . hub_url() . '">Hub</a></li>
    <li><a href="' . $events . '">What&rsquo;s On</a></li>
    <li><a href="' . $events . '?cat=' . $cat_slug . '">' . $ev['cat'] . '</a></li>
    <li><span aria-current="page">' . $title . '</span></li>
  </ol>
</nav>
' . $ended . '
<article class="event-page--simple">
  <div class="ho-container offer-simple event-simple">
    ' . hub_ev_media( $ev, ' offer-simple__media' . ( ! empty( $ev['featured'] ) ? ' ph--red' : '' ), true ) . '
    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags">' . $tags . '</div>
      <h1 class="offer-simple__title event-simple__title">' . $title . '</h1>
      <ul class="event-lines">
        <li>' . hub_icon( 'calendar' ) . '<span>' . $when_line . '</span></li>
        <li>' . hub_icon( 'pin' ) . '<span>' . $ev['venue'] . ', ' . $ev['area'] . ' &middot; <a href="' . $maps . '" rel="noopener" target="_blank">Directions</a></span></li>
        <li>' . hub_icon( 'ticket' ) . '<span>' . $price_line . '</span></li>
      </ul>' . $actions . '
    </div>
  </div>

  <div class="ho-container offer-simple__below">
    <section class="event-about" aria-labelledby="about-title">
      <h2 id="about-title">About this event</h2>
      <div class="profile-desc">' . $about . '</div>
      <p class="small muted mb-0">' . $pub . ' Details can change, so check with the organiser before you travel. <a class="report-link" href="' . hub_url( 'report/' ) . '">' . hub_icon( 'flag' ) . 'Report a problem</a></p>
    </section>
' . $org_row . '
' . $more_html . '
  </div>
</article>
';
	$node = array(
		'@type' => 'Event', '@id' => $url . '#event', 'name' => hub_plain( $title ), 'description' => hub_plain( $ev['desc'] ) ? hub_plain( $ev['desc'] ) : hub_plain( $title ), 'url' => $url,
		'image' => array( HUB_ORIGIN . '/assets/img/og-default.png' ),
		'startDate' => $ev['start_dt']->format( 'Y-m-d\TH:i:s' ) . hub_offset( $ev['start_dt'] ),
		'endDate' => $ev['end_dt']->format( 'Y-m-d\TH:i:s' ) . hub_offset( $ev['end_dt'] ),
		'eventStatus' => 'https://schema.org/EventScheduled', 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'isAccessibleForFree' => $free,
		'location' => array( '@type' => 'Place', 'name' => hub_plain( $ev['venue'] ),
			'address' => array( '@type' => 'PostalAddress', 'streetAddress' => hub_plain( $ev['venue'] ), 'addressLocality' => 'Harrow', 'addressRegion' => 'London', 'addressCountry' => 'GB' ) ),
		'publisher' => array( '@id' => 'https://harrowonline.org/#organization' ),
	);
	if ( $org ) {
		$node['organizer'] = array( '@type' => 'Organization', 'name' => hub_plain( $org['name'] ), 'url' => HUB_ORIGIN . '/business/' . $org['slug'] . '/' );
	}
	hub_add_ld( hub_json( array( '@context' => 'https://schema.org', '@graph' => array( $node ) ) ) );   // printed in <head>, as in the prototype

	$meta = array(
		'title'       => hub_plain( $title . ', ' . $d->format( 'j' ) . ' ' . $d->format( 'M' ) . ' ' . $d->format( 'Y' ) ),
		'description' => hub_plain( $title . ' at ' . $ev['venue'] . ', ' . $ev['area'] . ', ' . hub_long_date( $d ) . ' ' . $d->format( 'Y' ) . ', ' . $ev['time'] . '. ' . $ev['price'] . ', ' . $ev['age'] . '. ' . $ev['desc'] ),
		'nav' => 'events', 'hubnav' => 'events', 'body' => 'page-event',
	);
	if ( $past || ! empty( $ev['sample'] ) ) {
		$meta['robots'] = 'noindex, follow';
	}
	return array( $meta, $body );
}

/** ---------------------------------------------------------------- weekly classes */
function hub_class_media( array $c, $cls = 'ph', $eager = false ) {
	$r = $c['real'];
	if ( ! $r ) {
		return '<div class="' . $cls . '">' . hub_icon( $c['icon'] ) . '</div>';
	}
	$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
	if ( isset( $r['fit'] ) && 'logo' === $r['fit'] ) {
		return '<div class="' . $cls . ' ph--logo"><img src="' . hub_img( $r['img'] ) . '" alt="' . $r['alt'] . '" width="480" height="480" ' . $load . '></div>';
	}
	return '<div class="' . $cls . ' ph--photo"><img src="' . hub_img( $r['img'] ) . '" alt="' . $r['alt'] . '" width="1200" height="800" ' . $load . '></div>';
}

function hub_class_card( array $c ) {
	$badges = ! empty( $c['featured'] ) ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : '';
	return '<article class="card event-card">
        <div class="card__badges">' . $badges . '</div>
        ' . hub_class_media( $c ) . '
        <div class="card__body">
          <span class="biz-card__cat">' . $c['type'] . '</span>
          <h3 class="card__title"><a href="' . $c['href'] . '">' . $c['title'] . '</a></h3>
          <p class="card__meta"><span>' . hub_icon( 'repeat' ) . 'Every ' . HUB_DAYS[ $c['day'] ] . ', ' . $c['time'] . '</span><span>' . hub_icon( 'pin' ) . $c['area'] . '</span></p>
          <p class="card__meta"><span>' . hub_icon( 'ticket' ) . $c['price'] . '</span><span>' . hub_icon( 'people' ) . $c['age'] . '</span></p>
        </div>
      </article>';
}

/** hub_data._clock(): "7pm" -> [19, 0] */
function hub_clock( $s ) {
	if ( ! preg_match( '/(\d{1,2})(?:\.(\d{2}))?\s*(am|pm)/', $s, $m ) ) {
		return null;
	}
	return array( ( (int) $m[1] ) % 12 + ( 'pm' === $m[3] ? 12 : 0 ), isset( $m[2] ) && '' !== $m[2] ? (int) $m[2] : 0 );
}

/** [ page settings, main HTML ] for a weekly class (gen_events.class_page). */
function hub_class_page( array $c ) {
	$title = $c['title'];
	$day   = (int) $c['day'];
	$url   = HUB_ORIGIN . '/events/' . $c['slug'] . '/';
	$desc  = $c['desc'];
	$maps  = 'https://www.google.com/maps/search/?api=1&amp;query=' . hub_quote_plus( hub_plain( $c['venue'] . ' ' . $c['area'] . ' Harrow' ) );
	$prov  = hub_business_by_name( '' !== $c['provider'] ? $c['provider'] : hub_plain( $c['venue'] ) );
	$org_row = '';
	if ( $prov ) {
		$pc       = $prov['claimed'];
		$words    = preg_split( '/\s+/', trim( hub_plain( $pc['name'] ) ) );
		$initials = strtoupper( implode( '', array_map( function ( $w ) { return mb_substr( $w, 0, 1 ); }, array_slice( $words, 0, 2 ) ) ) );
		$mono     = ! empty( $pc['logo'] ) ? '<span class="mono mono--logo" aria-hidden="true"><img src="' . hub_img( $pc['logo'] ) . '" alt="" width="480" height="480" loading="lazy"></span>' : '<span class="mono" aria-hidden="true">' . $initials . '</span>';
		$org_row  = '
    <a class="offer-biz" href="' . hub_url( 'business/' . $prov['slug'] . '/' ) . '">
      ' . $mono . '
      <span class="offer-biz__main"><span class="eyebrow mb-0">Run by</span><strong>' . $pc['name'] . '</strong><span>' . hub_area_name( $pc['area'] ) . '</span></span>
      <span class="offer-biz__go">View profile ' . hub_icon( 'arrow', 'ho-icon ho-icon--sm' ) . '</span>
    </a>';
	}
	$others = array_values( array_filter( hub_classes(), function ( $x ) use ( $title ) { return $x['title'] !== $title; } ) );
	$mine   = $c['provider'];
	usort( $others, function ( $a, $b ) use ( $mine, $c ) {
		$k = function ( $x ) use ( $mine, $c ) {
			return array( ( $x['provider'] !== $mine || '' === $mine ) ? 1 : 0, $x['type'] !== $c['type'] ? 1 : 0, $x['area'] !== $c['area'] ? 1 : 0, (int) $x['day'], (int) $x['order'] );
		};
		return $k( $a ) <=> $k( $b );
	} );
	$more  = implode( '', array_map( 'hub_class_card', array_slice( $others, 0, 3 ) ) );
	$share = hub_quote( hub_plain( $title ) . ', every ' . HUB_DAYS[ $day ] . ' ' . $url, '' );
	$real  = $c['real'];
	$tags  = ( ! empty( $c['featured'] ) ? '<span class="badge badge--featured">' . hub_icon( 'star' ) . 'Featured</span>' : '' )
		. '<span class="badge badge--outline">' . hub_icon( 'repeat' ) . 'Weekly class</span><a class="biz-card__cat" href="' . hub_url( 'events/classes/' ) . '">' . $c['type'] . '</a>';
	$note  = $real ? ( isset( $real['note'] ) ? $real['note'] : 'Details from the organiser, checked by Harrow Online.' ) : 'Made-up sample for the preview.';
	$classes = hub_url( 'events/classes/' );
	$body  = '<nav class="crumbs ho-container" aria-label="Breadcrumb">
  <ol>
    <li><a href="' . hub_url() . '">Hub</a></li>
    <li><a href="' . hub_url( 'events/' ) . '">What&rsquo;s On</a></li>
    <li><a href="' . $classes . '">Classes and activities</a></li>
    <li><span aria-current="page">' . $title . '</span></li>
  </ol>
</nav>

<article class="event-page--simple">
  <div class="ho-container offer-simple event-simple">
    ' . hub_class_media( $c, 'ph offer-simple__media', true ) . '
    <div class="offer-simple__main">
      <div class="cluster offer-simple__tags">' . $tags . '</div>
      <h1 class="offer-simple__title event-simple__title">' . $title . '</h1>
      <ul class="event-lines">
        <li>' . hub_icon( 'repeat' ) . '<span>Every ' . HUB_DAYS[ $day ] . ', ' . $c['time'] . '</span></li>
        <li>' . hub_icon( 'pin' ) . '<span>' . $c['venue'] . ', ' . $c['area'] . ' &middot; <a href="' . $maps . '" rel="noopener" target="_blank">Directions</a></span></li>
        <li>' . hub_icon( 'ticket' ) . '<span>' . $c['price'] . ' &middot; ' . $c['age'] . '</span></li>
      </ul>
      <div class="share-row" aria-label="Share this class">
        <span>Share</span>
        <a href="https://wa.me/?text=' . $share . '" rel="noopener" target="_blank" aria-label="Share on WhatsApp">' . hub_icon( 'whatsapp' ) . '</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=' . hub_quote( $url, '' ) . '" rel="noopener" target="_blank" aria-label="Share on Facebook">' . hub_icon( 'facebook' ) . '</a>
        <a href="mailto:?subject=' . hub_quote( hub_plain( $title ) ) . '&amp;body=' . hub_quote( $url, '' ) . '" aria-label="Share by email">' . hub_icon( 'mail' ) . '</a>
        <button type="button" data-copy="' . $url . '" data-copied="Link copied">Copy link</button>
      </div>
    </div>
  </div>

  <div class="ho-container offer-simple__below">
    <section class="event-about" aria-labelledby="about-title">
      <h2 id="about-title">About this class</h2>
      <div class="profile-desc"><p>' . ( '' !== $desc ? $desc : 'Full details from the organiser coming soon.' ) . '</p></div>
      <p class="small muted mb-0">' . $note . ' Check times with the organiser before you go. <a class="report-link" href="' . hub_url( 'report/' ) . '">' . hub_icon( 'flag' ) . 'Report a problem</a></p>
    </section>
' . $org_row . '
    <section class="event-more" aria-labelledby="more-title">
      <div class="sec-head">
        <h2 class="sec-title" id="more-title">More classes</h2>
        <a class="link-arrow" href="' . $classes . '">All classes ' . hub_icon( 'arrow' ) . '</a>
      </div>
      <div class="grid grid-3">' . $more . '</div>
    </section>
  </div>
</article>
';
	$clock    = hub_clock( $c['time'] );
	$schedule = array( '@type' => 'Schedule', 'repeatFrequency' => 'P1W', 'byDay' => 'https://schema.org/' . HUB_DAYS[ $day ] );
	if ( $clock ) {
		$schedule['startTime'] = sprintf( '%02d:%02d:00', $clock[0], $clock[1] );
	}
	$schedule['scheduleTimezone'] = 'Europe/London';
	$address = array( '@type' => 'PostalAddress', 'addressLocality' => 'Harrow', 'addressCountry' => 'GB' );
	if ( $real ) {
		$address['streetAddress'] = $real['street'];
		$address['postalCode']    = $real['postcode'];
	}
	$node = array(
		'@type' => 'Event', '@id' => $url . '#event', 'name' => hub_plain( $title ), 'description' => hub_plain( $desc ) ? hub_plain( $desc ) : hub_plain( $title ), 'url' => $url,
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode', 'eventStatus' => 'https://schema.org/EventScheduled',
		'startDate' => '2026-11-01', 'eventSchedule' => $schedule,
		'location' => array( '@type' => 'Place', 'name' => hub_plain( $c['venue'] ), 'address' => $address ),
		'isAccessibleForFree' => 0 === strpos( strtolower( hub_plain( $c['price'] ) ), 'free' ), 'publisher' => array( '@id' => 'https://harrowonline.org/#organization' ),
	);
	hub_add_ld( hub_json( array( '@context' => 'https://schema.org', '@graph' => array( $node ) ) ) );   // printed in <head>, as in the prototype
	$meta = array(
		'title'       => hub_plain( $title . ' in ' . $c['area'] . ', every ' . HUB_DAYS[ $day ] ),
		'description' => hub_plain( $title . ': every ' . HUB_DAYS[ $day ] . ', ' . $c['time'] . ' at ' . $c['venue'] . ', ' . $c['area'] . '. ' . $c['price'] . ', ' . $c['age'] . '. ' . $desc ),
		'nav' => 'classes', 'hubnav' => 'events', 'body' => 'page-event', 'robots' => 'noindex, follow',
	);
	if ( $real ) {
		unset( $meta['robots'] );
		$meta['title'] = hub_plain( $title );
	}
	return array( $meta, $body );
}
