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
