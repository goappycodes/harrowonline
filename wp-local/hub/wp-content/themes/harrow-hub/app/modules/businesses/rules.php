<?php
/**
 * Businesses: the rules. No WordPress calls here; everything works on plain values.
 */
defined( 'ABSPATH' ) || exit;

/** Plans, and their place in the directory order (lower first). */
const HUB_BUSINESSES_PLAN_RANK = array( 'partner' => 0, 'premium' => 0, 'enhanced' => 1, 'basic' => 2, 'unclaimed' => 3 );

/** Paid plans: Local Offers, events, social links and a photo gallery on the profile. */
const HUB_BUSINESSES_PAID = array( 'enhanced', 'premium', 'partner' );

/** Top plans: "Featured" badge, cover photo, homepage features. */
const HUB_BUSINESSES_TOP = array( 'premium', 'partner' );

/** How many businesses a directory page lists before "load more". */
const HUB_BUSINESSES_PAGE_SIZE = 20;

/** Words of description a claimed profile needs before search engines may index it. */
const HUB_BUSINESSES_INDEX_WORDS = 50;

function hub_businesses_rule_is_paid( $plan ) {
	return in_array( $plan, HUB_BUSINESSES_PAID, true );
}

function hub_businesses_rule_is_top( $plan ) {
	return in_array( $plan, HUB_BUSINESSES_TOP, true );
}

/** Phone number for tel: links, in international form (UK "020 8488 9310" -> "+442084889310"). */
function hub_businesses_rule_phone_tel( $phone ) {
	$raw = preg_replace( '/[^\d+]/', '', (string) $phone );
	return preg_match( '/^0\d{9,10}$/', $raw ) ? '+44' . substr( $raw, 1 ) : $raw;
}

/** The mark next to a name: 'checked' (by Harrow Online), 'verified' (claimed by the business) or 'imported' (open data). */
function hub_businesses_rule_status( $claimed, $checked ) {
	if ( $checked ) {
		return 'checked';
	}
	return $claimed ? 'verified' : 'imported';
}

/**
 * Today's line for a claimed business: { state: 'open' | 'closed' | 'info', label }.
 * A status ("Closed for refurbishment") wins; no hours means "See times".
 *
 * @param string[] $hours   Seven days, Monday first ('' when not set).
 * @param string   $status  The business's own status line, or ''.
 * @param int      $weekday Today, Monday = 0.
 */
function hub_businesses_rule_open_today( array $hours, $status, $weekday ) {
	if ( '' !== (string) $status || ! $hours ) {
		return array( 'state' => 'info', 'label' => '' !== (string) $status ? (string) $status : 'See times' );
	}
	$h = isset( $hours[ $weekday ] ) ? (string) $hours[ $weekday ] : '';
	$l = strtolower( $h );
	if ( 'closed' === $l ) {
		return array( 'state' => 'closed', 'label' => 'Closed today' );
	}
	if ( 0 === strpos( $l, 'open' ) || preg_match( '/\d/', $h ) ) {
		return array( 'state' => 'open', 'label' => 'Open today' );
	}
	return array( 'state' => 'info', 'label' => 'Hours ' . $l . ' today' );
}

/**
 * Search engines may index a claimed profile with enough description and either opening times or photos.
 *
 * @param string[] $description Paragraphs (HTML).
 */
function hub_businesses_rule_indexable( $claimed, array $description, array $hours, array $schedule, $photo_count ) {
	if ( ! $claimed ) {
		return false;
	}
	$has_hours = (bool) $schedule;
	foreach ( $hours as $h ) {
		if ( $h && 'closed' !== strtolower( $h ) ) {
			$has_hours = true;
		}
	}
	$words = str_word_count( strip_tags( html_entity_decode( implode( ' ', $description ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	return $words >= HUB_BUSINESSES_INDEX_WORDS && ( $has_hours || $photo_count > 0 );
}

/** "In the news" shows Harrow Online articles only. */
function hub_businesses_rule_news_shown( $url ) {
	return 0 === strpos( (string) $url, 'https://harrowonline.org/' );
}

/**
 * Events and classes on a profile: one-off events by date (as given), then weekly classes soonest day first, then
 * in the classes order.
 */
function hub_businesses_rule_sort_events( array $rows ) {
	$events  = array_values( array_filter( $rows, function ( $r ) { return ! $r['weekly']; } ) );
	$classes = array_values( array_filter( $rows, function ( $r ) { return $r['weekly']; } ) );
	usort( $classes, function ( $a, $b ) {
		return array( $a['date'], $a['order'] ) <=> array( $b['date'], $b['order'] );
	} );
	return array_merge( $events, $classes );
}

/**
 * Homepage "Featured local businesses": real (not sample) businesses on a top plan, those with photos first, then
 * A to Z; one per category.
 */
function hub_businesses_rule_pick_featured( array $businesses, $limit ) {
	$pool = array_values( array_filter( $businesses, function ( $b ) {
		return $b['featured'] && ! $b['sample'];
	} ) );
	usort( $pool, function ( $a, $b ) {
		return array( ( $a['images'] || $a['cover'] ) ? 0 : 1, strtolower( $a['name'] ) )
			<=> array( ( $b['images'] || $b['cover'] ) ? 0 : 1, strtolower( $b['name'] ) );
	} );
	$picks = array();
	$seen  = array();
	foreach ( $pool as $b ) {
		if ( isset( $seen[ $b['category']['slug'] ] ) ) {
			continue;
		}
		$picks[]                         = $b;
		$seen[ $b['category']['slug'] ] = true;
		if ( count( $picks ) === $limit ) {
			break;
		}
	}
	return $picks;
}

/**
 * Homepage "Just joined": newest claimed businesses by the month they claimed ("November 2026"), keeping the
 * directory order within a month.
 */
function hub_businesses_rule_newest( array $businesses, $limit ) {
	$keyed = array();
	foreach ( array_values( $businesses ) as $i => $b ) {
		$t       = $b['claimed_since'] ? strtotime( '1 ' . $b['claimed_since'] ) : false;
		$keyed[] = array( false === $t ? 0 : $t, $i, $b );   // no month: oldest
	}
	usort( $keyed, function ( $a, $b ) {
		return array( -$a[0], $a[1] ) <=> array( -$b[0], $b[1] );
	} );
	return array_slice( array_column( $keyed, 2 ), 0, $limit );
}
