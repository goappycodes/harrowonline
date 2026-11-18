<?php
/**
 * Offers: the rules. No WordPress calls here; everything works on plain values.
 */
defined( 'ABSPATH' ) || exit;

/** An offer counts as "ending soon" from this many days before its end date. */
const HUB_OFFERS_SOON_DAYS = 7;

/** How many other offers the offer page suggests. */
const HUB_OFFERS_MORE = 4;

/** Plans whose offers are shown (Local Offers page, profiles, homepage), and their order on the Local Offers page. */
const HUB_OFFERS_PLAN_RANK = array( 'partner' => 0, 'premium' => 0, 'enhanced' => 1 );

/**
 * End date, days left and whether the offer is live, worked out from today. An offer with no end date runs until
 * the business ends it. An offer is live up to and including its end date.
 *
 * @param string $end 'Y-m-d' or ''.
 * @return array { date: 'Y-m-d'|null, days_left: int|null, live: bool }
 */
function hub_offers_rule_dates( $end, DateTimeImmutable $today ) {
	if ( '' === (string) $end ) {
		return array( 'date' => null, 'days_left' => null, 'live' => true );
	}
	$date = new DateTimeImmutable( $end, $today->getTimezone() );
	$days = (int) $today->diff( $date )->format( '%r%a' );
	return array( 'date' => $date->format( 'Y-m-d' ), 'days_left' => $days, 'live' => $days >= 0 );
}

/** True when a live offer ends within HUB_OFFERS_SOON_DAYS. */
function hub_offers_rule_ending_soon( $days_left ) {
	return null !== $days_left && $days_left >= 0 && $days_left <= HUB_OFFERS_SOON_DAYS;
}

/** Short end label for cards: "No end date", "Ends today", "Ends tomorrow", "Ends in 5 days", "Ends 31 Dec", "Ended 4 Nov". */
function hub_offers_rule_ends_label( $date, $days_left ) {
	if ( null === $date ) {
		return 'No end date';
	}
	$short = gmdate( 'j M', strtotime( $date ) );
	if ( $days_left < 0 ) {
		return 'Ended ' . $short;
	}
	if ( 0 === $days_left ) {
		return 'Ends today';
	}
	if ( 1 === $days_left ) {
		return 'Ends tomorrow';
	}
	return $days_left <= HUB_OFFERS_SOON_DAYS ? "Ends in {$days_left} days" : 'Ends ' . $short;
}

/** Days-left line for the offer page: "Last day today", "6 days left", "This offer has ended", or '' with no end date. */
function hub_offers_rule_days_left_label( $date, $days_left ) {
	if ( null === $date ) {
		return '';
	}
	if ( $days_left < 0 ) {
		return 'This offer has ended';
	}
	if ( 0 === $days_left ) {
		return 'Last day today';
	}
	return $days_left . ' day' . ( 1 !== $days_left ? 's' : '' ) . ' left';
}

/**
 * The short headline value ("20% OFF", "2-FOR-1", "FREE COFFEE"): the offer's own value if it has one, otherwise
 * worked out from the title (plain text).
 */
function hub_offers_rule_value( $title, $value = '' ) {
	if ( '' !== (string) $value ) {
		return (string) $value;
	}
	$t = (string) $title;
	foreach ( array(
		array( '/(\d+)%\s*off/i', '%s% OFF' ), array( '/£(\d+)\s*off/iu', '£%s OFF' ), array( '/(\d)-for-(\d)/i', null ),
		array( '/£(\d+)\s*cuts/iu', '£%s CUTS' ), array( '/for\s*£(\d+)/iu', '£%s' ),
	) as $rule ) {
		if ( preg_match( $rule[0], $t, $m ) ) {
			return null === $rule[1] ? $m[1] . '-FOR-' . $m[2] : str_replace( '%s', $m[1], $rule[1] );
		}
	}
	if ( preg_match( '/no joining fee/i', $t ) ) {
		return 'NO JOINING FEE';
	}
	if ( preg_match( '/^(?:a\s+)?free\s+(\w+)/i', $t, $m ) ) {
		return 'FREE ' . mb_strtoupper( $m[1] );
	}
	if ( preg_match( '/^(.{0,24}?\bfree)\b/iu', $t, $m ) ) {
		return mb_strtoupper( $m[1] );
	}
	$first = explode( ',', $t );
	return mb_strtoupper( $first[0] );
}

/** True when offers from a business on this plan are shown. */
function hub_offers_rule_plan_shows_offers( $plan ) {
	return isset( HUB_OFFERS_PLAN_RANK[ (string) $plan ] );
}

/**
 * Order of the Local Offers page: featured first, then Partner and Premium, then Enhanced, then the directory
 * order of the business, then the offer's order within its business.
 */
function hub_offers_rule_compare( array $a, array $b ) {
	return array( $a['featured'] ? 0 : 1, HUB_OFFERS_PLAN_RANK[ $a['business']['plan'] ], $a['_sort'][0], $a['_sort'][1] )
		<=> array( $b['featured'] ? 0 : 1, HUB_OFFERS_PLAN_RANK[ $b['business']['plan'] ], $b['_sort'][0], $b['_sort'][1] );
}

/**
 * The offers to suggest on an offer's page: same business first, then same category, then featured, then the
 * Local Offers order. $ranked is the live list in Local Offers order.
 */
function hub_offers_rule_more( array $offer, array $ranked, $limit = HUB_OFFERS_MORE ) {
	$more = array();
	foreach ( $ranked as $i => $o ) {
		if ( $o['id'] !== $offer['id'] ) {
			$more[] = array( $o, $i );
		}
	}
	usort( $more, function ( $a, $b ) use ( $offer ) {
		$key = function ( $p ) use ( $offer ) {
			return array(
				$p[0]['business']['id'] !== $offer['business']['id'] ? 1 : 0,
				$p[0]['business']['category']['slug'] !== $offer['business']['category']['slug'] ? 1 : 0,
				$p[0]['featured'] ? 0 : 1,
				$p[1],
			);
		};
		return $key( $a ) <=> $key( $b );
	} );
	return array_map( function ( $p ) { return $p[0]; }, array_slice( $more, 0, $limit ) );
}
