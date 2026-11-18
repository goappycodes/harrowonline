<?php
/**
 * Small markup helpers for parts/, working on the controllers' shapes (app/modules/{module}/shape.php).
 * They only print data they are given: no queries. Everything returned is escaped HTML.
 */
defined( 'ABSPATH' ) || exit;

/** The status mark next to a business name: Checked by Harrow Online, Verified by the business, or imported. */
function hub_view_tick( array $b, $focusable = true ) {
	$marks = array(
		'checked'  => array( 'Checked by Harrow Online', 'check' ),
		'verified' => array( 'Verified by the business', 'verified' ),
		'imported' => array( 'Information imported from public data', 'globe' ),
	);
	list( $label, $icon ) = $marks[ $b['status'] ];
	return '<span class="card__tick card__tick--' . esc_attr( $b['status'] ) . '"' . ( $focusable ? ' tabindex="0"' : '' ) . ' role="img" aria-label="' . esc_attr( $label ) . '" data-tip="' . esc_attr( $label ) . '"><svg class="ho-icon" aria-hidden="true"><use href="#i-' . $icon . '"/></svg></span>';
}

/**
 * The business name with its mark after the last word (kept together on one line). In links ($linked), imported
 * businesses show no mark and the mark is not focusable (the link is).
 */
function hub_view_name_with_tick( array $b, $linked = true ) {
	$name = $b['name'];
	if ( $linked && 'imported' === $b['status'] ) {
		return esc_html( $name );
	}
	$pos  = strrpos( $name, ' ' );
	$head = false === $pos ? '' : substr( $name, 0, $pos );
	$last = false === $pos ? $name : substr( $name, $pos + 1 );
	return ( '' !== $head ? esc_html( $head ) . ' ' : '' ) . '<span class="name-end">' . esc_html( $last ) . hub_view_tick( $b, ! $linked ) . '</span>';
}

/** "Open today" / "Closed today" / the business's own status line. */
function hub_view_open_today( array $b ) {
	if ( ! $b['open_today'] ) {
		return '';
	}
	$mod = array( 'open' => '', 'closed' => ' open-now--closed', 'info' => ' open-now--info' );
	return '<span class="open-now' . $mod[ $b['open_today']['state'] ] . '">' . esc_html( $b['open_today']['label'] ) . '</span>';
}

/** Photo $i of a business (wrapping round), or its icon when it has no photos. */
function hub_view_photo( array $b, $i = 0, $fallback = null, $alt = '', $eager = false ) {
	if ( ! $b['images'] ) {
		return hub_icon( $fallback ? $fallback : $b['icon'] );
	}
	$img  = $b['images'][ $i % count( $b['images'] ) ];
	$alt  = '' !== $alt ? $alt : $img['alt'];
	$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
	return '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $alt ) . '" width="1200" height="800" ' . $load . '>';
}

/** Class of a photo box: "ph ph--photo" when the business has photos. */
function hub_view_ph_cls( array $b, $base = 'ph' ) {
	return $base . ( $b['images'] ? ' ph--photo' : '' );
}

/** The business's logo in a box. */
function hub_view_logo( array $b, $cls = 'ph ph--logo', $alt = '' ) {
	return '<div class="' . esc_attr( $cls ) . '"><img src="' . esc_url( $b['logo'] ) . '" alt="' . esc_attr( $alt ) . '" width="480" height="480" loading="lazy" decoding="async"></div>';
}

/** Where a business is: "12 High St, Pinner, HA5 1AA" ($with_area) or "12 High St, HA5 1AA"; "Covers Harrow" for mobile businesses. */
function hub_view_address( array $b, $with_area = false ) {
	$a = $b['address'];
	if ( ! $a['has_premises'] ) {
		return 'Covers ' . ( $a['service_area'] ? $a['service_area'] : 'Harrow' );
	}
	return implode( ', ', array_filter( array( $a['street'], $with_area ? $b['area']['name'] : '', $a['postcode'] ) ) );
}

/** Google Maps search link. */
function hub_view_maps_url( $query ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

/** A website address without the scheme and trailing slash ("example.co.uk"). */
function hub_view_short_url( $url ) {
	$parts = explode( '//', (string) $url, 2 );
	return rtrim( end( $parts ), '/' );
}

/** Text cut to about $n characters at a word, with "…" (meta descriptions). */
function hub_view_excerpt( $text, $n = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	if ( mb_strlen( $text ) <= $n ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $n );
	$cut = mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) );
	return rtrim( $cut, ',;:' ) . '…';
}

/* ------------------------------------------------------------------ events and classes */

/** Date tile: "Nov / 14 / Sat". */
function hub_view_date_tile( $date ) {
	$d = date_create_immutable( $date );
	return '<time class="date-tile" datetime="' . esc_attr( $d->format( 'Y-m-d' ) ) . '"><span class="date-tile__m">' . $d->format( 'M' ) . '</span><span class="date-tile__d">' . $d->format( 'j' ) . '</span><span class="date-tile__w">' . $d->format( 'D' ) . '</span></time>';
}

/** An event's photo, or its icon, in a box ($extra_cls added to the box's class). */
function hub_view_event_media( array $e, $extra_cls = '', $eager = false ) {
	if ( $e['image'] ) {
		$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
		return '<div class="ph ph--photo' . esc_attr( $extra_cls ) . '"><img src="' . esc_url( $e['image'] ) . '" alt="" width="1200" height="800" ' . $load . '></div>';
	}
	return '<div class="ph' . esc_attr( $extra_cls ) . '">' . hub_icon( $e['icon'] ) . '</div>';
}

/** A class's photo or logo (classes run by real businesses), or its icon, in a box of class $cls. */
function hub_view_class_media( array $c, $cls = 'ph', $eager = false ) {
	if ( ! $c['image'] ) {
		return '<div class="' . esc_attr( $cls ) . '">' . hub_icon( $c['icon'] ) . '</div>';
	}
	$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
	$logo = 'logo' === $c['image']['fit'];
	return '<div class="' . esc_attr( $cls ) . ( $logo ? ' ph--logo' : ' ph--photo' ) . '"><img src="' . esc_url( $c['image']['url'] ) . '" alt="' . esc_attr( $c['image']['alt'] ) . '" width="' . ( $logo ? 480 : 1200 ) . '" height="' . ( $logo ? 480 : 800 ) . '" ' . $load . '></div>';
}

/** data-* attributes the What's On filters read (hub.js 6b): category, area, age groups, free. */
function hub_view_event_filter_attrs( array $x ) {
	return ' data-cat="' . esc_attr( $x['category']['slug'] ) . '" data-area="' . esc_attr( $x['area']['slug'] ) . '" data-ages="' . esc_attr( implode( ' ', $x['age_groups'] ) ) . '" data-free="' . ( $x['free'] ? 1 : 0 ) . '"';
}

/** True when a UK local time ('Y-m-d\TH:i:s') is in British Summer Time (2026 dates, as the prototype). */
function hub_view_is_bst( $local ) {
	$naive = str_replace( 'T', ' ', $local );
	return $naive >= '2026-03-29 00:00:00' && $naive < '2026-10-25 01:00:00';
}

/** UTC offset of a UK local time: "+01:00" in summer, "+00:00" otherwise. */
function hub_view_uk_offset( $local ) {
	return hub_view_is_bst( $local ) ? '+01:00' : '+00:00';
}

/** A UK local time in UTC, in the format given. */
function hub_view_utc( $local, $format ) {
	$t = new DateTimeImmutable( $local, new DateTimeZone( 'UTC' ) );
	return ( hub_view_is_bst( $local ) ? $t->modify( '-1 hour' ) : $t )->format( $format );
}

/** "Add to calendar" links for an event: [ google, outlook, ics data URL ]. $url is the event's public address. */
function hub_view_calendar_links( array $e, $url ) {
	$place  = $e['venue'] . ', ' . $e['area']['name'] . ', Harrow';
	$s      = hub_view_utc( $e['start'], 'Ymd\THis\Z' );
	$f      = hub_view_utc( $e['end'], 'Ymd\THis\Z' );
	$google = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . hub_quote_plus( $e['title'] ) . "&dates={$s}/{$f}"
		. '&location=' . hub_quote_plus( $place ) . '&details=' . hub_quote_plus( $url );
	$outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&subject=' . hub_quote_plus( $e['title'] )
		. '&startdt=' . hub_quote( hub_view_utc( $e['start'], 'Y-m-d\TH:i:s\Z' ), '' ) . '&enddt=' . hub_quote( hub_view_utc( $e['end'], 'Y-m-d\TH:i:s\Z' ), '' )
		. '&location=' . hub_quote_plus( $place );
	$ics = implode( "\r\n", array(
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Harrow Online Hub//EN', 'BEGIN:VEVENT',
		'UID:' . $e['slug'] . '-' . substr( $e['date'], 0, 4 ) . '@harrowonline.org', 'DTSTAMP:20261111T090000Z', "DTSTART:{$s}", "DTEND:{$f}",
		'SUMMARY:' . str_replace( ',', '\,', $e['title'] ), 'LOCATION:' . str_replace( ',', '\,', $place ), "URL:{$url}", 'END:VEVENT', 'END:VCALENDAR',
	) );
	return array( $google, $outlook, 'data:text/calendar;charset=utf-8,' . hub_quote( $ics, ':/@.-_ ' ) );
}

/** "Hatch End Bookshop" -> "HB" (organiser and provider boxes). */
function hub_view_two_initials( $name ) {
	$words = preg_split( '/\s+/', trim( (string) $name ) );
	return strtoupper( implode( '', array_map( function ( $w ) { return mb_substr( $w, 0, 1 ); }, array_slice( $words, 0, 2 ) ) ) );
}
