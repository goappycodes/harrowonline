<?php
/**
 * What's On (events and weekly classes): what pages and the API call. Nothing outside this module calls anything
 * else in it.
 *
 *   hub_events_whats_on( $params )        the What's On list: featured events, then each day of the next 7
 *   hub_events_month( $month )            the month calendar ('Y-m'), with the month's one-off events as an agenda
 *   hub_events_weekend( $limit )          this weekend's events (homepage)
 *   hub_events_list( $params )            one-off events, filtered
 *   hub_event_get( $id_or_slug )          an event page: the event, its organiser, more coming up
 *   hub_classes_list( $params )           weekly classes, filtered
 *   hub_class_get( $id_or_slug )          a class page: the class, who runs it, more classes
 *   hub_events_for_business( $id, $names) a business's upcoming events and its weekly classes (its profile)
 *
 * Each takes $mode last: HUB_WEB (arrays, the default) or HUB_API (WP_REST_Response). See app/bootstrap.php.
 */
defined( 'ABSPATH' ) || exit;

/**
 * The What's On list.
 *
 * @param array $params { from: 'Y-m-d' (default today) }
 * @return array|WP_REST_Response {
 *     today, weekend { from, to }, range { from, to }   dates as 'Y-m-d'
 *     featured:   event[]   featured events from today on, soonest first (not repeated in the days)
 *     days:       [ { date, items: (event | class with date)[] } ]   featured first, then by time
 *     total:      int  items in the days
 *     categories: [ { slug, name, count } ]  upcoming events per category, and the weekly classes
 * }
 */
function hub_events_whats_on( array $params = array(), $mode = HUB_WEB ) {
	$today = hub_events_db_today();
	$from  = _hub_events_date_param( $params, 'from', $today );
	list( $w0, $w1 ) = hub_events_rule_weekend( $today );

	$featured = array_values( array_filter( _hub_events_all(), function ( $e ) use ( $from ) {
		return $e['featured'] && $e['date'] >= $from->format( 'Y-m-d' );
	} ) );
	$days  = array();
	$total = 0;
	for ( $n = 0; $n < HUB_EVENTS_LIST_DAYS; $n++ ) {
		$day   = $from->modify( '+' . $n . ' day' );
		$items = array_values( array_filter( _hub_events_on( $day ), function ( $x ) {
			return ! ( 'event' === $x['kind'] && $x['featured'] );
		} ) );
		$total  += count( $items );
		$days[]  = array( 'date' => $day->format( 'Y-m-d' ), 'items' => $items );
	}

	return hub_respond( array(
		'today'      => $today->format( 'Y-m-d' ),
		'weekend'    => array( 'from' => $w0->format( 'Y-m-d' ), 'to' => $w1->format( 'Y-m-d' ) ),
		'range'      => array( 'from' => $from->format( 'Y-m-d' ), 'to' => $from->modify( '+' . ( HUB_EVENTS_LIST_DAYS - 1 ) . ' day' )->format( 'Y-m-d' ) ),
		'featured'   => $featured,
		'days'       => $days,
		'total'      => $total,
		'categories' => _hub_events_category_counts( $today ),
	), $mode );
}

/**
 * The month calendar.
 *
 * @param string $month 'Y-m' (default: this month).
 * @return array|WP_REST_Response {
 *     month { value 'Y-m', name 'November', label 'November 2026', prev 'Y-m', next 'Y-m' }, today,
 *     cells:      [ { date, in_month, is_today, items (first 3), more (how many not shown) } ]  6 weeks from Monday
 *     agenda:     [ { date, is_today, past, items: event[] } ]  days of the month with one-off events
 *     categories: as hub_events_whats_on()
 * }
 */
function hub_events_month( $month = '', $mode = HUB_WEB ) {
	$today = hub_events_db_today();
	$first = preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month ) ? new DateTimeImmutable( $month . '-01', $today->getTimezone() ) : $today->modify( 'first day of this month' )->setTime( 0, 0 );
	$start = $first->modify( '-' . hub_events_rule_weekday( $first ) . ' day' );
	$t     = $today->format( 'Y-m-d' );

	$cells = array();
	for ( $n = 0; $n < 42; $n++ ) {
		$day     = $start->modify( '+' . $n . ' day' );
		$items   = _hub_events_on( $day );
		$cells[] = array(
			'date'     => $day->format( 'Y-m-d' ),
			'in_month' => $day->format( 'm' ) === $first->format( 'm' ),
			'is_today' => $day->format( 'Y-m-d' ) === $t,
			'items'    => array_slice( $items, 0, HUB_EVENTS_CELL_ITEMS ),
			'more'     => max( 0, count( $items ) - HUB_EVENTS_CELL_ITEMS ),
		);
	}
	$agenda = array();
	for ( $day = $first; $day->format( 'm' ) === $first->format( 'm' ); $day = $day->modify( '+1 day' ) ) {
		$events = array_values( array_filter( _hub_events_on( $day ), function ( $x ) { return 'event' === $x['kind']; } ) );
		if ( $events ) {
			$d        = $day->format( 'Y-m-d' );
			$agenda[] = array( 'date' => $d, 'is_today' => $d === $t, 'past' => $d < $t, 'items' => $events );
		}
	}

	return hub_respond( array(
		'month'      => array(
			'value' => $first->format( 'Y-m' ),
			'name'  => $first->format( 'F' ),
			'label' => $first->format( 'F Y' ),
			'prev'  => $first->modify( '-1 month' )->format( 'Y-m' ),
			'next'  => $first->modify( '+1 month' )->format( 'Y-m' ),
		),
		'today'      => $t,
		'cells'      => $cells,
		'agenda'     => $agenda,
		'categories' => _hub_events_category_counts( $today ),
	), $mode );
}

/**
 * This weekend's one-off events (Friday to Sunday): featured first, then soonest.
 *
 * @return array|WP_REST_Response { from, to, items: event[] }
 */
function hub_events_weekend( $limit = 4, $mode = HUB_WEB ) {
	list( $w0, $w1 ) = hub_events_rule_weekend( hub_events_db_today() );
	$items = array_values( array_filter( _hub_events_all(), function ( $e ) use ( $w0, $w1 ) {
		return $e['date'] >= $w0->format( 'Y-m-d' ) && $e['date'] <= $w1->format( 'Y-m-d' );
	} ) );
	usort( $items, function ( $a, $b ) {
		return array( $a['featured'] ? 0 : 1, $a['start'] ) <=> array( $b['featured'] ? 0 : 1, $b['start'] );
	} );
	return hub_respond( array(
		'from'  => $w0->format( 'Y-m-d' ),
		'to'    => $w1->format( 'Y-m-d' ),
		'items' => array_slice( $items, 0, max( 1, (int) $limit ) ),
	), $mode );
}

/**
 * One-off events, by start time.
 *
 * @param array $params { from, to ('Y-m-d'; default from today on), cat, area (slugs), featured (1), limit, offset }
 * @return array|WP_REST_Response { items: event[], total }
 */
function hub_events_list( array $params = array(), $mode = HUB_WEB ) {
	$today = hub_events_db_today();
	$from  = _hub_events_date_param( $params, 'from', $today )->format( 'Y-m-d' );
	$to    = isset( $params['to'] ) ? _hub_events_date_param( $params, 'to', $today )->format( 'Y-m-d' ) : '';
	$cat   = hub_param_slug( $params, 'cat' );
	$area  = hub_param_slug( $params, 'area' );
	$feat  = hub_param_bool( $params, 'featured' );
	$items = array_values( array_filter( _hub_events_all(), function ( $e ) use ( $from, $to, $cat, $area, $feat ) {
		return $e['date'] >= $from && ( '' === $to || $e['date'] <= $to )
			&& ( '' === $cat || $e['category']['slug'] === $cat ) && ( '' === $area || $e['area']['slug'] === $area )
			&& ( ! $feat || $e['featured'] );
	} ) );
	$limit = hub_param_int( $params, 'limit', 0, 0, 100 );
	return hub_respond( array(
		'items' => array_slice( $items, hub_param_int( $params, 'offset', 0 ), $limit ? $limit : null ),
		'total' => count( $items ),
	), $mode );
}

/**
 * An event page.
 *
 * @return array|WP_REST_Response|null|WP_Error { event, organiser: business | null, more: event[] } or null (web) / 404 (API)
 */
function hub_event_get( $id_or_slug, $mode = HUB_WEB ) {
	$post = hub_events_db_get( 'hub_event', hub_param_id_or_slug( $id_or_slug ) );
	if ( ! $post ) {
		return hub_respond_error( 'hub_event_not_found', 'No event with that ID or slug.', $mode );
	}
	$event = _hub_event_build( $post );
	return hub_respond( array(
		'event'     => $event,
		'organiser' => $event['organiser'] ? hub_business_find_by_name( $event['organiser'] ) : null,   // businesses module
		'more'      => hub_events_rule_more_events( $event, _hub_events_all(), hub_events_db_today() ),
	), $mode );
}

/**
 * Weekly classes, in the Classes and activities page order (including the entries listed there only).
 *
 * @param array $params { type, area (slugs), day ('mon'...'sun'), age (age group slug) }
 * @return array|WP_REST_Response {
 *     items: class[], total,
 *     all_total: int  every class (no filters)
 *     weekly_total: int  classes with a fixed weekly time and their own page (no filters)
 *     types: [ { slug, name, count } ]  every class type, in the page order, with how many classes (no filters)
 *     filters: { type, area, day, age }
 * }
 */
function hub_classes_list( array $params = array(), $mode = HUB_WEB ) {
	$f   = array(
		'type' => hub_param_slug( $params, 'type' ),
		'area' => hub_param_slug( $params, 'area' ),
		'day'  => hub_param_slug( $params, 'day' ),
		'age'  => hub_param_slug( $params, 'age' ),
	);
	$all = _hub_classes_all();
	usort( $all, function ( $a, $b ) {
		return $a['card']['order'] <=> $b['card']['order'];
	} );
	$items = array_values( array_filter( $all, function ( $c ) use ( $f ) {
		return ( '' === $f['type'] || $c['type']['slug'] === $f['type'] ) && ( '' === $f['area'] || $c['area']['slug'] === $f['area'] )
			&& ( '' === $f['day'] || $c['card']['day'] === $f['day'] ) && ( '' === $f['age'] || in_array( $f['age'], $c['age_groups'], true ) );
	} ) );
	$types = array();
	foreach ( HUB_EVENTS_CLASS_TYPES as $name => $slug ) {
		$types[ $slug ] = array( 'slug' => $slug, 'name' => $name, 'count' => 0 );
	}
	foreach ( $all as $c ) {
		$types[ $c['type']['slug'] ]['count']++;
	}
	return hub_respond( array(
		'items'        => $items,
		'total'        => count( $items ),
		'all_total'    => count( $all ),
		'weekly_total' => count( wp_list_filter( $all, array( 'page' => true ) ) ),
		'types'        => array_values( $types ),
		'filters'      => $f,
	), $mode );
}

/**
 * A class page.
 *
 * @return array|WP_REST_Response|null|WP_Error { class, provider: business | null, more: class[] } or null (web) / 404 (API)
 */
function hub_class_get( $id_or_slug, $mode = HUB_WEB ) {
	$post = hub_events_db_get( 'hub_class', hub_param_id_or_slug( $id_or_slug ) );
	if ( ! $post ) {
		return hub_respond_error( 'hub_class_not_found', 'No class with that ID or slug.', $mode );
	}
	$class = current( wp_list_filter( _hub_classes_all(), array( 'id' => $post->ID ) ) );   // with its business's page
	// Who runs it: the business picked in wp-admin, otherwise the business with the provider's (or venue's) name
	if ( $class['business_id'] ) {
		$found    = hub_businesses_summaries( array( $class['business_id'] ) )['items'];   // businesses module
		$provider = isset( $found[ $class['business_id'] ] ) ? $found[ $class['business_id'] ] : null;
	} else {
		$provider = hub_business_find_by_name( '' !== $class['provider'] ? $class['provider'] : $class['venue'] );
	}
	return hub_respond( array(
		'class'    => $class,
		'provider' => $provider,
		'more'     => hub_events_rule_more_classes( $class, _hub_classes_all() ),
	), $mode );
}

/**
 * What a business runs: its one-off events from today on (matched by organiser name) and its weekly classes.
 *
 * @param string[] $names The business's names (current and imported).
 * @return array|WP_REST_Response { events: event[], classes: class[] }
 */
function hub_events_for_business( $business_id, array $names, $mode = HUB_WEB ) {
	$names = array_map( 'hub_plain', $names );
	$today = hub_events_db_today()->format( 'Y-m-d' );
	return hub_respond( array(
		'events'  => array_values( array_filter( _hub_events_all(), function ( $e ) use ( $names, $today ) {
			return $e['organiser'] && in_array( $e['organiser'], $names, true ) && $e['date'] >= $today;
		} ) ),
		'classes' => array_values( array_filter( _hub_classes_all(), function ( $c ) use ( $business_id ) {
			return $c['page'] && $business_id && (int) $c['business_id'] === (int) $business_id;
		} ) ),
	), $mode );
}

/* ------------------------------------------------------------------ events and classes sent from the website (submissions module) */

/**
 * Save a one-off event from the account pages (the submissions module checks who may, and the values).
 *
 * @param array $f { title, description, cat (label), icon, date (Y-m-d), start, end (H:i, end may be ''), time_label,
 *                   venue, area (name), price, age, org (organiser's name), image (URL or ''), link }
 * @return int|WP_Error post ID
 */
function hub_event_save_submission( $id, $status, array $f ) {
	$end    = '' !== $f['end'] ? $f['end'] : gmdate( 'H:i', strtotime( $f['start'] ) + 2 * HOUR_IN_SECONDS );
	$record = array(
		'date'     => $f['date'],
		'time'     => $f['time_label'],
		'title'    => $f['title'],
		'cat'      => $f['cat'],
		'venue'    => $f['venue'],
		'area'     => $f['area'],
		'price'    => $f['price'],
		'age'      => $f['age'],
		'icon'     => $f['icon'],
		'featured' => false,
		'desc'     => $f['description'],
		'org'      => $f['org'],
		'image'    => $f['image'],
		'link'     => $f['link'],
		'day'      => $f['date'],
		'start'    => $f['date'] . 'T' . $f['start'] . ':00',
		'end'      => $f['date'] . 'T' . $end . ':00',
	);
	$meta = array( '_hub_start' => $record['start'], '_hub_day' => $f['date'], '_hub_org' => $f['org'] );
	return hub_events_db_save( 'hub_event', (int) $id, $status, $f['title'], $f['description'], $record, $meta, $f );
}

/**
 * Save a weekly class from the account pages.
 *
 * @param array $f { title, description, type (label), icon, day (0 = Monday), sort (hour), time_label, venue, street,
 *                   postcode, area (name), price, age, provider, image (URL or ''), business_id (0 for organisers), link }
 * @return int|WP_Error post ID
 */
function hub_class_save_submission( $id, $status, array $f ) {
	$order  = $id ? (int) get_post_meta( (int) $id, '_hub_order', true ) : 0;
	$order  = $order ? $order : hub_events_db_next_class_order();
	$record = array(
		'title'    => $f['title'],
		'day'      => (int) $f['day'],
		'time'     => $f['time_label'],
		'sort'     => (float) $f['sort'],
		'venue'    => $f['venue'],
		'area'     => $f['area'],
		'price'    => $f['price'],
		'age'      => $f['age'],
		'icon'     => $f['icon'],
		'featured' => false,
		'type'     => $f['type'],
		'desc'     => $f['description'],
		'provider' => $f['provider'],
		'real'     => array(
			'img'      => $f['image'],
			'alt'      => '' !== $f['image'] ? $f['title'] : '',
			'street'   => $f['street'],
			'postcode' => $f['postcode'],
			'note'     => 'Details from ' . $f['provider'] . ', checked by Harrow Online.',
		),
		'link'     => $f['link'],
		'order'    => $order,
		'page'     => true,
	);
	$meta = array( '_hub_order' => $order, '_hub_day' => (int) $f['day'], 'class_business' => (int) $f['business_id'] );
	return hub_events_db_save( 'hub_class', (int) $id, $status, $f['title'], $f['description'], $record, $meta, $f );
}

/** An event or class sent from the website, any status: { id, status, title, url, form, details (event / class shape) } or null. */
function hub_events_submission( $type, $id ) {
	$post = hub_events_db_get_any( $type, $id );
	if ( ! $post ) {
		return null;
	}
	$form = get_post_meta( $post->ID, '_hub_form', true );
	return array(
		'id'      => $post->ID,
		'status'  => $post->post_status,
		'title'   => hub_plain( $post->post_title ),
		'url'     => hub_url( 'events/' . $post->post_name . '/' ),
		'form'    => is_array( $form ) ? $form : null,
		'details' => 'hub_class' === $type ? _hub_class_build( $post ) : _hub_event_build( $post ),   // in the class / event shape
	);
}

/* ------------------------------------------------------------------ internal */

/** Every event in the event shape, by start then title. Cached for the request. */
function _hub_events_all() {
	static $all = null;
	if ( null === $all ) {
		$all = array_map( '_hub_event_build', hub_events_db_events() );
		usort( $all, function ( $a, $b ) {
			return array( $a['start'], $a['title'] ) <=> array( $b['start'], $b['title'] );
		} );
	}
	return $all;
}

/**
 * Every weekly class in the class shape, in the classes order, with its business's page (provider_url; also the url
 * of classes with no page of their own). Cached for the request.
 */
function _hub_classes_all() {
	static $all = null;
	if ( null === $all ) {
		$all  = array_map( '_hub_class_build', hub_events_db_classes() );
		$biz  = hub_businesses_summaries( array_filter( array_column( $all, 'business_id' ) ) )['items'];   // businesses module
		$all  = array_map( function ( $c ) use ( $biz ) {
			$c['provider_url'] = $c['business_id'] && isset( $biz[ $c['business_id'] ] ) ? $biz[ $c['business_id'] ]['url'] : null;
			if ( ! $c['page'] ) {
				$c['url'] = $c['provider_url'];
			}
			return $c;
		}, $all );
	}
	return $all;
}

function _hub_event_build( WP_Post $post ) {
	return hub_event_shape( $post, hub_events_db_record( $post ), _hub_events_areas(), hub_events_db_today() );
}

function _hub_class_build( WP_Post $post ) {
	return hub_class_shape( $post, hub_events_db_record( $post ), _hub_events_areas(), hub_events_db_class_business( $post->ID ) );
}

/** Everything on one day: its events, then the classes that run on that weekday (with the date), featured first, by time. */
function _hub_events_on( DateTimeImmutable $day ) {
	$d     = $day->format( 'Y-m-d' );
	$wd    = hub_events_rule_weekday( $day );
	$items = array_values( array_filter( _hub_events_all(), function ( $e ) use ( $d ) { return $e['date'] === $d; } ) );
	foreach ( _hub_classes_all() as $c ) {
		if ( $c['weekday'] === $wd ) {
			$items[] = $c + array( 'date' => $d );
		}
	}
	return hub_events_rule_sort_day( $items );
}

/** Upcoming one-off events per category, plus the weekly classes, in the filter order. */
function _hub_events_category_counts( DateTimeImmutable $today ) {
	$cats = array( 'classes' => HUB_EVENTS_CLASSES_CATEGORY + array( 'count' => count( wp_list_filter( _hub_classes_all(), array( 'page' => true ) ) ) ) );
	foreach ( _hub_events_all() as $e ) {
		if ( $e['date'] < $today->format( 'Y-m-d' ) || '' === $e['category']['slug'] ) {
			continue;
		}
		$k = $e['category']['slug'];
		if ( ! isset( $cats[ $k ] ) ) {
			$cats[ $k ] = $e['category'] + array( 'count' => 0 );
		}
		$cats[ $k ]['count']++;
	}
	return hub_events_rule_sort_categories( array_values( $cats ) );
}

/** Area slugs by name and names by slug, from the areas module (once per request). */
function _hub_events_areas() {
	static $areas = null;
	if ( null === $areas ) {
		$items = hub_areas_list()['items'];
		$areas = array( 'by_name' => wp_list_pluck( $items, 'slug', 'name' ), 'by_slug' => wp_list_pluck( $items, 'name', 'slug' ) );
	}
	return $areas;
}

/** A 'Y-m-d' date parameter, or $default. */
function _hub_events_date_param( array $params, $key, DateTimeImmutable $default ) {
	if ( isset( $params[ $key ] ) && is_string( $params[ $key ] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $params[ $key ] ) ) {
		$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $params[ $key ], $default->getTimezone() );
		if ( $d ) {
			return $d;
		}
	}
	return $default;
}
