<?php
/**
 * What's On: the rules. No WordPress calls here; everything works on plain values.
 */
defined( 'ABSPATH' ) || exit;

/** Days the What's On list shows, from today. */
const HUB_EVENTS_LIST_DAYS = 7;

/** Items shown in a month-calendar day before "+N more". */
const HUB_EVENTS_CELL_ITEMS = 3;

/** Events or classes suggested on an event or class page. */
const HUB_EVENTS_MORE = 3;

/** Category of every weekly class in What's On. */
const HUB_EVENTS_CLASSES_CATEGORY = array( 'slug' => 'classes', 'name' => 'Classes and activities' );

/** Order of the What's On category filters; categories not listed here follow, A to Z. */
const HUB_EVENTS_CATEGORY_ORDER = array( 'classes', 'family', 'music', 'food-and-drink', 'nightlife', 'community', 'arts' );

/** Class types and their filter slugs. */
const HUB_EVENTS_CLASS_TYPES = array(
	'Children’s activities' => 'childrens-activities',
	'Fitness'               => 'fitness',
	'Dance schools'         => 'dance-schools',
	'Workshops'             => 'workshops',
	'Other regular classes' => 'other',
);

/** Age groups used by the filters: [ slug, from, to ]. */
const HUB_EVENTS_AGE_GROUPS = array( array( 'under-5', 0, 4 ), array( '5-11', 5, 11 ), array( '12-17', 12, 17 ), array( 'adults', 18, 59 ), array( '60-plus', 60, 120 ) );

const HUB_EVENTS_DAY_NAMES = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

/** Category slug from its name: "Food and drink" -> "food-and-drink". */
function hub_events_rule_category_slug( $name ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', str_replace( '&', 'and', strtolower( (string) $name ) ) ), '-' );
}

/** Class type slug: "Dance schools" -> "dance-schools" ("other" when unknown). */
function hub_events_rule_class_type_slug( $type ) {
	return isset( HUB_EVENTS_CLASS_TYPES[ $type ] ) ? HUB_EVENTS_CLASS_TYPES[ $type ] : 'other';
}

/** Free when the price starts with "Free". */
function hub_events_rule_is_free( $price ) {
	return 0 === strpos( strtolower( trim( (string) $price ) ), 'free' );
}

/** The age groups an age line suits ("Ages 5 to 7" -> [ "5-11" ]; "All ages" -> all of them). */
function hub_events_rule_age_groups( $age ) {
	$all = array_column( HUB_EVENTS_AGE_GROUPS, 0 );
	$a   = strtolower( (string) $age );
	if ( '' === trim( $a ) || false !== strpos( $a, 'all ages' ) || false !== strpos( $a, 'everyone' ) ) {
		return $all;
	}
	if ( false !== strpos( $a, '60' ) || false !== strpos( $a, 'older' ) ) {
		return array( '60-plus', 'adults' );
	}
	if ( false !== strpos( $a, 'adult' ) && ! preg_match( '/\d/', $a ) ) {
		return array( 'adults', '60-plus' );
	}
	preg_match_all( '/\d+/', $a, $m );
	$nums = array_map( 'intval', $m[0] );
	if ( false !== strpos( $a, 'under' ) && $nums ) {
		list( $lo, $hi ) = array( 0, $nums[0] - 1 );
	} elseif ( false !== strpos( $a, '+' ) && $nums ) {
		list( $lo, $hi ) = array( $nums[0], 120 );
	} elseif ( count( $nums ) >= 2 ) {
		list( $lo, $hi ) = array( $nums[0], $nums[1] );
	} elseif ( $nums ) {
		list( $lo, $hi ) = array( $nums[0], $nums[0] );
	} else {
		return $all;
	}
	$out = array();
	foreach ( HUB_EVENTS_AGE_GROUPS as $g ) {
		if ( $lo <= $g[2] && $hi >= $g[1] ) {
			$out[] = $g[0];
		}
	}
	return $out;
}

/** "This weekend": Friday to Sunday. From Monday to Friday the coming one; on Saturday and Sunday the current one. */
function hub_events_rule_weekend( DateTimeImmutable $today ) {
	$dow    = (int) $today->format( 'N' );   // 1 = Monday
	$friday = $dow >= 5 ? $today->modify( '-' . ( $dow - 5 ) . ' day' ) : $today->modify( '+' . ( 5 - $dow ) . ' day' );
	return array( $friday, $friday->modify( '+2 day' ) );
}

/** Start time from a time line: "7.30pm to 9pm" -> "19:30"; null when it has no clock time. */
function hub_events_rule_start_time( $time ) {
	if ( ! preg_match( '/(\d{1,2})(?:\.(\d{2}))?\s*(am|pm)/', (string) $time, $m ) ) {
		return null;
	}
	return sprintf( '%02d:%02d', ( (int) $m[1] ) % 12 + ( 'pm' === $m[3] ? 12 : 0 ), isset( $m[2] ) && '' !== $m[2] ? (int) $m[2] : 0 );
}

/** Weekday index, Monday = 0. */
function hub_events_rule_weekday( DateTimeInterface $d ) {
	return (int) $d->format( 'N' ) - 1;
}

/** "Wednesday 11 November". */
function hub_events_rule_long_date( DateTimeInterface $d ) {
	return HUB_EVENTS_DAY_NAMES[ hub_events_rule_weekday( $d ) ] . ' ' . $d->format( 'j F' );
}

/** Start time as hours for sorting a day ("2026-11-14T19:30:00" -> 19.5). */
function hub_events_rule_sort_hour( $start ) {
	return preg_match( '/T(\d{2}):(\d{2})/', (string) $start, $m ) ? (int) $m[1] + (int) $m[2] / 60 : 0.0;
}

/** Everything on one day: featured first, then by time. $items keep their given order when equal (events, then classes). */
function hub_events_rule_sort_day( array $items ) {
	$keyed = array();
	foreach ( array_values( $items ) as $i => $x ) {
		$keyed[] = array( $x['featured'] ? 0 : 1, $x['sort'], $i, $x );
	}
	usort( $keyed, function ( $a, $b ) {
		return array( $a[0], $a[1], $a[2] ) <=> array( $b[0], $b[1], $b[2] );
	} );
	return array_column( $keyed, 3 );
}

/** Events still to come (or on now): starting at or after the later of the event's own start and the start of today. */
function hub_events_rule_more_events( array $event, array $events, DateTimeImmutable $today, $limit = HUB_EVENTS_MORE ) {
	$after = max( $event['start'], $today->format( 'Y-m-d' ) . 'T00:00:00' );
	$out   = array();
	foreach ( $events as $e ) {
		if ( $e['id'] !== $event['id'] && $e['start'] >= $after ) {
			$out[] = $e;
			if ( count( $out ) === $limit ) {
				break;
			}
		}
	}
	return $out;
}

/** Classes to suggest on a class page: same provider first, then same type, then same area; then by day and order. */
function hub_events_rule_more_classes( array $class, array $classes, $limit = HUB_EVENTS_MORE ) {
	$others = array_values( array_filter( $classes, function ( $c ) use ( $class ) {
		return $c['title'] !== $class['title'];
	} ) );
	usort( $others, function ( $a, $b ) use ( $class ) {
		$k = function ( $x ) use ( $class ) {
			return array(
				( $x['provider'] !== $class['provider'] || '' === $class['provider'] ) ? 1 : 0,
				$x['type']['name'] !== $class['type']['name'] ? 1 : 0,
				$x['area']['name'] !== $class['area']['name'] ? 1 : 0,
				$x['weekday'],
				$x['order'],
			);
		};
		return $k( $a ) <=> $k( $b );
	} );
	return array_slice( $others, 0, $limit );
}

/** Sort category counts by HUB_EVENTS_CATEGORY_ORDER, then A to Z. */
function hub_events_rule_sort_categories( array $cats ) {
	$rank = array_flip( HUB_EVENTS_CATEGORY_ORDER );
	usort( $cats, function ( $a, $b ) use ( $rank ) {
		return array( isset( $rank[ $a['slug'] ] ) ? $rank[ $a['slug'] ] : 99, $a['name'] ) <=> array( isset( $rank[ $b['slug'] ] ) ? $rank[ $b['slug'] ] : 99, $b['name'] );
	} );
	return $cats;
}
