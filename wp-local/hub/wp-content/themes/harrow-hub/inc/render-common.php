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

/** Every icon in the theme's sprite (assets/img/icons.svg), by name: "food", "medical"… */
function hub_icon_names() {
	static $names = null;
	if ( null === $names ) {
		$file  = get_template_directory() . '/assets/img/icons.svg';
		$names = is_readable( $file ) && preg_match_all( '/id="i-([a-z0-9-]+)"/', (string) file_get_contents( $file ), $m ) ? array_values( array_unique( $m[1] ) ) : array();
	}
	return $names;
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

/** Root-relative prototype asset path ("/assets/img/x.jpg") -> theme URL; a full URL (media library) as it is. */
function hub_img( $path ) {
	if ( preg_match( '#^https?://#', (string) $path ) ) {
		return esc_url( $path );
	}
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

function hub_sub_lock( $focusable = true ) {
	$tab = $focusable ? ' tabindex="0"' : '';
	return '<span class="card__tick card__tick--sub"' . $tab . ' role="img" aria-label="Subscribers only" data-tip="Subscribers only">' . hub_icon( 'lock' ) . '</span>';
}

function hub_is_sample( array $x ) {
	return 0 === strpos( (string) ( isset( $x['id'] ) ? $x['id'] : '' ), 'sample-' );
}

const HUB_DAYS = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

/** Python json.dumps( x, indent=2, ensure_ascii=False ). */
function hub_json( $data ) {
	$j = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return preg_replace_callback( '/^( +)/m', function ( $m ) { return str_repeat( ' ', strlen( $m[1] ) / 2 ); }, $j );
}
