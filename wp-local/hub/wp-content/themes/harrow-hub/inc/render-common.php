<?php
/**
 * Shared markup helpers for the Hub's content pages, ported one-to-one from the prototype's Python generators
 * (gen_listings.py, gen_claimed.py, tools/gen_events.py) so the HTML matches exactly.
 * Records are the imported "_hub" arrays (see inc/content-types.php).
 */
defined( 'ABSPATH' ) || exit;

/** Python html.escape(): & < > " ' (as &#x27;). */
function hub_e( $s ) {
	return str_replace( array( '&', '<', '>', '"', "'" ), array( '&amp;', '&lt;', '&gt;', '&quot;', '&#x27;' ), (string) $s );
}

/** Python urllib.parse.quote( s, safe ) (default safe "/"). */
function hub_quote( $s, $safe = '/' ) {
	$out = rawurlencode( (string) $s );
	foreach ( str_split( $safe ) as $ch ) {
		if ( '' !== $ch ) {
			$out = str_replace( rawurlencode( $ch ), $ch, $out );
		}
	}
	return $out;
}

/** Python urllib.parse.quote_plus(). */
function hub_quote_plus( $s ) {
	return str_replace( array( '%20', '%7E' ), array( '+', '~' ), rawurlencode( (string) $s ) );
}

/** Python html.unescape() for text built from HTML strings. */
function hub_unescape( $s ) {
	return html_entity_decode( (string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/** Plain text: no tags, no entities (gen_events.plain). */
function hub_plain( $s ) {
	return hub_unescape( preg_replace( '/<[^>]+>/', '', (string) $s ) );
}

function hub_icon( $name, $cls = 'ho-icon' ) {
	return '<svg class="' . $cls . '"><use href="#i-' . $name . '"/></svg>';
}

function hub_initials( $name ) {
	$clean = preg_replace( '/[^\p{L}\p{N} ]/u', ' ', $name );
	$words = preg_split( '/\s+/', trim( $clean ), -1, PREG_SPLIT_NO_EMPTY );
	$out   = '';
	foreach ( array_slice( $words, 0, 2 ) as $w ) {
		$out .= mb_substr( $w, 0, 1 );
	}
	return '' === $out ? '?' : mb_strtoupper( $out );
}

/** Today (the preview's fixed date while the content is the November 2026 sample; option hub_today). */
function hub_today() {
	$d = get_option( 'hub_today' );
	return new DateTimeImmutable( $d ? $d : 'today', wp_timezone() );
}

/** Weekday index, Monday = 0 (Python date.weekday()). */
function hub_weekday( DateTimeInterface $d ) {
	return ( (int) $d->format( 'N' ) ) - 1;
}

/** Category / area names and icons (taxonomy terms), cached. */
function hub_term_name( $tax, $slug ) {
	static $cache = array();
	if ( ! isset( $cache[ $tax ] ) ) {
		$cache[ $tax ] = array();
		foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) ) as $t ) {
			$cache[ $tax ][ $t->slug ] = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
		}
	}
	return isset( $cache[ $tax ][ $slug ] ) ? $cache[ $tax ][ $slug ] : $slug;
}
function hub_cat_name( $slug ) {
	return hub_term_name( 'hub_category', $slug );
}
function hub_area_name( $slug ) {
	return hub_term_name( 'hub_area', $slug );
}

/** Area slug from its name (the events data stores area names). */
function hub_area_slug_by_name( $name ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( get_terms( array( 'taxonomy' => 'hub_area', 'hide_empty' => false ) ) as $t ) {
			$map[ html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ] = $t->slug;
		}
	}
	return isset( $map[ $name ] ) ? $map[ $name ] : '';
}

/** hub_data.slugify() */
function hub_slugify( $text ) {
	$text = remove_accents( (string) $text );
	$text = preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $text ) );
	$text = trim( preg_replace( '/-{2,}/', '-', trim( $text, '-' ) ), '-' );
	$text = rtrim( substr( $text, 0, 70 ), '-' );
	return '' === $text ? 'business' : $text;
}

/** Root-relative prototype asset path ("/assets/img/x.jpg") -> theme URL. */
function hub_img( $path ) {
	return esc_url( hub_asset( preg_replace( '#^/assets/#', '', (string) $path ) ) );
}

function hub_crumbs( array $items ) {
	$lis  = '';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		list( $label, $href ) = $item;
		$lis .= $i === $last ? '<li><span aria-current="page">' . hub_e( $label ) . '</span></li>'
			: '<li><a href="' . esc_url( $href ) . '">' . hub_e( $label ) . '</a></li>';
	}
	return '<nav class="crumbs ho-container" aria-label="Breadcrumb"><ol>' . $lis . '</ol></nav>';
}

/** ---------------------------------------------------------------- status icons (gen_claimed) */
function hub_name_tick( array $x, $focusable = true ) {
	if ( ! empty( $x['checked'] ) ) {
		list( $kind, $s, $ic ) = array( 'checked', 'Checked by Harrow Online', 'check' );
	} elseif ( ! empty( $x['claimed'] ) ) {
		list( $kind, $s, $ic ) = array( 'verified', 'Verified by the business', 'verified' );
	} else {
		list( $kind, $s, $ic ) = array( 'imported', 'Information imported from public data', 'globe' );
	}
	$tab = $focusable ? ' tabindex="0"' : '';
	return '<span class="card__tick card__tick--' . $kind . '"' . $tab . ' role="img" aria-label="' . $s . '" data-tip="' . $s . '"><svg class="ho-icon" aria-hidden="true"><use href="#i-' . $ic . '"/></svg></span>';
}

function hub_name_with_tick( array $x, $name = null, $linked = true ) {
	$name = $name ? $name : $x['name'];
	$tick = ( $linked && empty( $x['checked'] ) && empty( $x['claimed'] ) ) ? '' : hub_name_tick( $x, ! $linked );
	if ( '' === $tick ) {
		return hub_e( $name );
	}
	$pos  = strrpos( $name, ' ' );
	$head = false === $pos ? '' : substr( $name, 0, $pos );
	$last = false === $pos ? $name : substr( $name, $pos + 1 );
	return ( '' !== $head ? hub_e( $head ) . ' ' : '' ) . '<span class="name-end">' . hub_e( $last ) . $tick . '</span>';
}

function hub_sub_lock( $focusable = true ) {
	$tab = $focusable ? ' tabindex="0"' : '';
	return '<span class="card__tick card__tick--sub"' . $tab . ' role="img" aria-label="Subscribers only" data-tip="Subscribers only">' . hub_icon( 'lock' ) . '</span>';
}

function hub_tier_badge( $tier ) {
	if ( 'partner' === $tier ) {
		return '<span class="badge badge--partner">' . hub_icon( 'star' ) . 'Local Partner</span>';
	}
	if ( 'premium' === $tier ) {
		return '<span class="badge badge--premium">' . hub_icon( 'star' ) . 'Featured</span>';
	}
	return '';
}

function hub_is_sample( array $x ) {
	return 0 === strpos( (string) ( isset( $x['id'] ) ? $x['id'] : '' ), 'sample-' );
}

const HUB_PAID = array( 'enhanced', 'premium', 'partner' );
const HUB_TOP  = array( 'premium', 'partner' );
const HUB_DAYS = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

function hub_has_address( array $c ) {
	return ! empty( $c['address'] ) && empty( $c['service_area'] );
}

function hub_address_line( array $x, $with_area = false ) {
	$c = $x['claimed'];
	if ( ! hub_has_address( $c ) ) {
		return 'Covers ' . ( ! empty( $c['service_area'] ) ? $c['service_area'] : 'Harrow' );
	}
	$parts = array( $c['address'] );
	if ( $with_area ) {
		$parts[] = hub_e( hub_area_name( $x['area'] ) );
	}
	if ( ! empty( $x['postcode'] ) ) {
		$parts[] = hub_e( $x['postcode'] );
	}
	return implode( ', ', $parts );
}

function hub_photo_inner( array $x, $i = 0, $fallback = 'store', $alt = '', $eager = false ) {
	$c    = isset( $x['claimed'] ) ? $x['claimed'] : array();
	$imgs = isset( $c['images'] ) ? $c['images'] : array();
	if ( ! $imgs ) {
		return hub_icon( $fallback );
	}
	$k    = $i % count( $imgs );
	$alts = isset( $c['image_alts'] ) ? $c['image_alts'] : array();
	if ( '' === $alt && $k < count( $alts ) ) {
		$alt = $alts[ $k ];
	}
	$load = $eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
	return '<img src="' . hub_img( $imgs[ $k ] ) . '" alt="' . hub_e( $alt ) . '" width="1200" height="800" ' . $load . '>';
}

function hub_ph_cls( array $x, $base = 'ph' ) {
	return $base . ( ! empty( $x['claimed']['images'] ) ? ' ph--photo' : '' );
}

function hub_logo_slot( array $c, $cls = 'ph ph--logo', $alt = '' ) {
	return '<div class="' . $cls . '"><img src="' . hub_img( $c['logo'] ) . '" alt="' . hub_e( $alt ) . '" width="480" height="480" loading="lazy" decoding="async"></div>';
}

function hub_today_status( array $c ) {
	if ( ! empty( $c['status'] ) || empty( $c['hours'] ) ) {
		return '<span class="open-now open-now--info">' . ( ! empty( $c['status'] ) ? $c['status'] : 'See times' ) . '</span>';
	}
	$h = $c['hours'][ hub_weekday( hub_today() ) ];
	$l = strtolower( $h );
	if ( 'closed' === $l ) {
		return '<span class="open-now open-now--closed">Closed today</span>';
	}
	if ( 0 === strpos( $l, 'open' ) ) {
		return '<span class="open-now">Open today</span>';
	}
	if ( ! preg_match( '/\d/', $h ) ) {
		return '<span class="open-now open-now--info">Hours ' . $l . ' today</span>';
	}
	return '<span class="open-now">Open today</span>';
}

/** Python json.dumps( x, indent=2, ensure_ascii=False ). */
function hub_json( $data ) {
	$j = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return preg_replace_callback( '/^( +)/m', function ( $m ) { return str_repeat( ' ', strlen( $m[1] ) / 2 ); }, $j );
}
