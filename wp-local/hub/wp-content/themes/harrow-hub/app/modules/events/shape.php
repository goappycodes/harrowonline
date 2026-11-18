<?php
/**
 * What's On: the array shapes the templates and the app get. Text is plain (no HTML, no entities); URLs are full and
 * unescaped; times are local (Europe/London) without an offset.
 *
 * event (one-off):
 *   kind 'event', id, slug, url, title, description, category { slug, name },
 *   date (Y-m-d), start, end (Y-m-d\TH:i:s), time (as written: "7pm to 10pm"), note, sort (hour of the day),
 *   venue, area { slug, name }, price, free, age, age_groups [ slugs ], icon, image (url | null),
 *   featured, sample, organiser (business name | null), past
 *
 * class (weekly):
 *   kind 'class', id, slug, url, title, description, category (always "Classes and activities"),
 *   type { slug, name }, weekday (0 = Monday), day_name, time, start_time ('HH:MM' | null), sort, order,
 *   venue, area { slug, name }, price, free, age, age_groups, icon, featured, sample,
 *   image { url, alt, fit ('photo' | 'logo') } | null, address { street, postcode } | null, note,
 *   provider (name), business_id (int | null)
 *
 * A class on a given day (What's On list, calendar) is the class plus "date" (Y-m-d).
 */
defined( 'ABSPATH' ) || exit;

/**
 * @param array $areas Area slugs by name and names by slug: { by_name: { name: slug }, by_slug: { slug: name } }.
 */
function hub_event_shape( WP_Post $post, array $r, array $areas, DateTimeImmutable $today ) {
	$cat  = hub_plain( isset( $r['cat'] ) ? $r['cat'] : '' );
	$area = hub_plain( isset( $r['area'] ) ? $r['area'] : '' );
	$age  = hub_plain( isset( $r['age'] ) ? $r['age'] : '' );
	$day  = isset( $r['day'] ) ? (string) $r['day'] : substr( (string) $r['start'], 0, 10 );
	return array(
		'kind'        => 'event',
		'id'          => $post->ID,
		'slug'        => $post->post_name,
		'url'         => hub_url( 'events/' . $post->post_name . '/' ),
		'title'       => hub_plain( $post->post_title ),
		'description' => hub_plain( $post->post_content ),
		'category'    => array( 'slug' => hub_events_rule_category_slug( $cat ), 'name' => $cat ),
		'date'        => $day,
		'start'       => (string) $r['start'],
		'end'         => (string) $r['end'],
		'time'        => hub_plain( isset( $r['time'] ) ? $r['time'] : '' ),
		'note'        => ! empty( $r['note'] ) ? hub_plain( $r['note'] ) : null,
		'sort'        => hub_events_rule_sort_hour( $r['start'] ),
		'venue'       => hub_plain( isset( $r['venue'] ) ? $r['venue'] : '' ),
		'area'        => array( 'slug' => isset( $areas['by_name'][ $area ] ) ? $areas['by_name'][ $area ] : '', 'name' => $area ),
		'price'       => hub_plain( isset( $r['price'] ) ? $r['price'] : '' ),
		'free'        => hub_events_rule_is_free( hub_plain( isset( $r['price'] ) ? $r['price'] : '' ) ),
		'age'         => $age,
		'age_groups'  => hub_events_rule_age_groups( $age ),
		'icon'        => ! empty( $r['icon'] ) ? $r['icon'] : 'calendar',
		'image'       => ! empty( $r['image'] ) ? hub_media_url( $r['image'] ) : null,
		'featured'    => ! empty( $r['featured'] ),
		'sample'      => ! empty( $r['sample'] ),
		'organiser'   => ! empty( $r['org'] ) ? hub_plain( $r['org'] ) : null,
		'past'        => $day < $today->format( 'Y-m-d' ),
	);
}

/**
 * @param int $business_id The class's business (its "Business" field), 0 when none.
 */
function hub_class_shape( WP_Post $post, array $r, array $areas, $business_id ) {
	$type = hub_plain( isset( $r['type'] ) ? $r['type'] : '' );
	$area = hub_plain( isset( $r['area'] ) ? $r['area'] : '' );
	$age  = hub_plain( isset( $r['age'] ) ? $r['age'] : '' );
	$real = ! empty( $r['real'] ) && is_array( $r['real'] ) ? $r['real'] : null;
	$day  = (int) $r['day'];
	return array(
		'kind'        => 'class',
		'id'          => $post->ID,
		'slug'        => $post->post_name,
		'url'         => hub_url( 'events/' . $post->post_name . '/' ),
		'title'       => hub_plain( $post->post_title ),
		'description' => hub_plain( $post->post_content ),
		'category'    => HUB_EVENTS_CLASSES_CATEGORY,
		'type'        => array( 'slug' => hub_events_rule_class_type_slug( $type ), 'name' => $type ),
		'weekday'     => $day,
		'day_name'    => HUB_EVENTS_DAY_NAMES[ $day ],
		'time'        => hub_plain( isset( $r['time'] ) ? $r['time'] : '' ),
		'start_time'  => hub_events_rule_start_time( hub_plain( isset( $r['time'] ) ? $r['time'] : '' ) ),
		'sort'        => (float) ( isset( $r['sort'] ) ? $r['sort'] : 0 ),
		'order'       => (int) ( isset( $r['order'] ) ? $r['order'] : 0 ),
		'venue'       => hub_plain( isset( $r['venue'] ) ? $r['venue'] : '' ),
		'area'        => array( 'slug' => isset( $areas['by_name'][ $area ] ) ? $areas['by_name'][ $area ] : '', 'name' => $area ),
		'price'       => hub_plain( isset( $r['price'] ) ? $r['price'] : '' ),
		'free'        => hub_events_rule_is_free( hub_plain( isset( $r['price'] ) ? $r['price'] : '' ) ),
		'age'         => $age,
		'age_groups'  => hub_events_rule_age_groups( $age ),
		'icon'        => ! empty( $r['icon'] ) ? $r['icon'] : 'repeat',
		'featured'    => ! empty( $r['featured'] ),
		'sample'      => null === $real,
		'image'       => $real && ! empty( $real['img'] ) ? array(
			'url' => hub_media_url( $real['img'] ),
			'alt' => hub_plain( isset( $real['alt'] ) ? $real['alt'] : '' ),
			'fit' => isset( $real['fit'] ) && 'logo' === $real['fit'] ? 'logo' : 'photo',
		) : null,
		'address'     => $real && ! empty( $real['street'] ) ? array( 'street' => hub_plain( $real['street'] ), 'postcode' => isset( $real['postcode'] ) ? (string) $real['postcode'] : '' ) : null,
		'note'        => $real ? hub_plain( isset( $real['note'] ) ? $real['note'] : 'Details from the organiser, checked by Harrow Online.' ) : 'Made-up sample for the preview.',
		'provider'    => hub_plain( isset( $r['provider'] ) ? $r['provider'] : '' ),
		'business_id' => $business_id ? (int) $business_id : null,
	);
}
