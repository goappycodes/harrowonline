<?php
/**
 * Businesses: the array shapes the templates and the app get. Text is plain (no HTML, no entities) except
 * "description", which is a list of HTML paragraphs (links allowed). URLs are full and unescaped.
 *
 * business (everywhere: directory rows, cards, offers, class pages):
 *   id, slug, url, name, status ('checked' | 'verified' | 'imported'), claimed, checked,
 *   plan ('unclaimed' | 'basic' | 'enhanced' | 'premium' | 'partner'), paid, featured, sample,
 *   category { slug, name }, subcategory, area { slug, name },
 *   address { street, postcode, service_area (string | null), has_premises },
 *   phones [ { display, tel } ], website, email, location { lat, lng } | null,
 *   tagline, icon, logo (url | null), cover { url, alt, background } | null, images [ { url, alt } ], photo_count,
 *   open_today { state ('open' | 'closed' | 'info'), label } | null (claimed only), claimed_since, directory_order
 *
 * profile (the business page) = business +
 *   description [ html paragraphs ], seo_title, verified_note, business_of_the_week, indexable,
 *   hours [ 7 strings, Monday first ] | [], hours_note, schedule [ { day, text } ], schedule_title, schedule_note,
 *   today_index (Monday = 0), other_locations [ { label, name, address } ],
 *   social [ { network, label, url (null for a sample's placeholder) } ], news [ { title, url, category, date } ]
 *
 * event row (a business's events and classes):
 *   title, url, date (Y-m-d), when, price, featured, weekly
 */
defined( 'ABSPATH' ) || exit;

const HUB_BUSINESSES_SOCIAL_LABELS = array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn', 'x' => 'X' );

/**
 * @param WP_Post $post
 * @param array   $x       The record (hub_businesses_db_record()).
 * @param array   $meta    Plan and order (hub_businesses_db_meta()).
 * @param array   $names   Category and area names by slug: { categories: { slug: name }, areas: { slug: name } }.
 * @param int     $weekday Today, Monday = 0.
 */
function hub_business_shape( WP_Post $post, array $x, array $meta, array $names, $weekday ) {
	$c       = ! empty( $x['claimed'] ) && is_array( $x['claimed'] ) ? $x['claimed'] : null;
	$claimed = null !== $c;
	$plan    = $claimed ? ( ! empty( $x['tier'] ) ? $x['tier'] : $meta['plan'] ) : 'unclaimed';
	$cat     = isset( $x['cat'] ) ? $x['cat'] : '';
	$area    = isset( $x['area'] ) ? $x['area'] : '';
	$checked = ! empty( $x['checked'] );

	$phones = array();
	foreach ( isset( $x['phone'] ) ? (array) $x['phone'] : array() as $i => $display ) {
		$phones[] = array(
			'display' => hub_plain( $display ),
			'tel'     => isset( $x['phone_raw'][ $i ] ) ? $x['phone_raw'][ $i ] : hub_businesses_rule_phone_tel( $display ),
		);
	}
	$images = array();
	if ( $claimed ) {
		foreach ( isset( $c['images'] ) ? (array) $c['images'] : array() as $i => $url ) {
			$images[] = array( 'url' => hub_media_url( $url ), 'alt' => isset( $c['image_alts'][ $i ] ) ? hub_plain( $c['image_alts'][ $i ] ) : '' );
		}
	}
	$service_area = $claimed && ! empty( $c['service_area'] ) ? hub_plain( $c['service_area'] ) : null;
	$street       = hub_plain( isset( $x['address'] ) ? $x['address'] : '' );

	return array(
		'id'              => $post->ID,
		'slug'            => $post->post_name,
		'url'             => hub_url( 'business/' . $post->post_name . '/' ),
		'name'            => hub_plain( $x['name'] ),
		'status'          => hub_businesses_rule_status( $claimed, $checked ),
		'claimed'         => $claimed,
		'checked'         => $checked,
		'plan'            => $plan,
		'paid'            => hub_businesses_rule_is_paid( $plan ),
		'featured'        => hub_businesses_rule_is_top( $plan ),
		'sample'          => hub_is_sample( $x ),
		'category'        => array( 'slug' => $cat, 'name' => isset( $names['categories'][ $cat ] ) ? $names['categories'][ $cat ] : $cat ),
		'subcategory'     => hub_plain( isset( $x['sub'] ) ? $x['sub'] : '' ),
		'area'            => array( 'slug' => $area, 'name' => isset( $names['areas'][ $area ] ) ? $names['areas'][ $area ] : $area ),
		'address'         => array(
			'street'       => $street,
			'postcode'     => isset( $x['postcode'] ) ? (string) $x['postcode'] : '',
			'service_area' => $service_area,
			'has_premises' => '' !== $street && null === $service_area,
		),
		'phones'          => $phones,
		'website'         => ! empty( $x['web'][0] ) ? $x['web'][0] : null,
		'email'           => ! empty( $x['email'][0] ) ? $x['email'][0] : null,
		'location'        => ( isset( $x['lat'], $x['lng'] ) && null !== $x['lat'] && null !== $x['lng'] ) ? array( 'lat' => (float) $x['lat'], 'lng' => (float) $x['lng'] ) : null,
		'tagline'         => $claimed && ! empty( $c['tagline'] ) ? hub_plain( $c['tagline'] ) : '',
		'icon'            => $claimed && ! empty( $c['icon'] ) ? $c['icon'] : 'store',
		'logo'            => $claimed && ! empty( $c['logo'] ) ? hub_media_url( $c['logo'] ) : null,
		'cover'           => $claimed && ! empty( $c['cover'] ) ? array(
			'url'        => hub_media_url( $c['cover'] ),
			'alt'        => hub_plain( isset( $c['cover_alt'] ) ? $c['cover_alt'] : $x['name'] ),
			'background' => ! empty( $c['cover_bg'] ) ? $c['cover_bg'] : null,
		) : null,
		'images'          => $images,
		'photo_count'     => $claimed ? (int) ( isset( $c['photos'] ) ? $c['photos'] : count( $images ) ) : 0,
		'open_today'      => $claimed ? hub_businesses_rule_open_today( isset( $c['hours'] ) ? (array) $c['hours'] : array(), isset( $c['status'] ) ? hub_plain( $c['status'] ) : '', $weekday ) : null,
		'claimed_since'   => $claimed && ! empty( $c['claimed'] ) ? (string) $c['claimed'] : null,
		'directory_order' => $meta['order'],
	);
}

/** The business page: the business plus its description, hours, contacts and links. */
function hub_business_shape_profile( WP_Post $post, array $x, array $meta, array $names, $weekday ) {
	$b = hub_business_shape( $post, $x, $meta, $names, $weekday );
	$c = $b['claimed'] ? $x['claimed'] : array();

	$description = array_map( function ( $p ) {
		return wp_kses( $p, array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ), 'strong' => array(), 'em' => array(), 'br' => array() ) );
	}, isset( $c['description'] ) ? (array) $c['description'] : array() );
	$hours    = array_map( 'hub_plain', isset( $c['hours'] ) ? (array) $c['hours'] : array() );
	$schedule = array();
	foreach ( isset( $c['schedule'] ) ? (array) $c['schedule'] : array() as $row ) {
		$schedule[] = array( 'day' => hub_plain( $row[0] ), 'text' => hub_plain( $row[1] ) );
	}
	$social = array();
	$urls   = isset( $c['social_urls'] ) ? (array) $c['social_urls'] : array();
	foreach ( isset( $c['social'] ) ? (array) $c['social'] : array() as $s ) {
		if ( isset( HUB_BUSINESSES_SOCIAL_LABELS[ $s ] ) ) {
			$social[] = array( 'network' => $s, 'label' => HUB_BUSINESSES_SOCIAL_LABELS[ $s ], 'url' => isset( $urls[ $s ] ) ? $urls[ $s ] : null );
		}
	}
	$news = array();
	foreach ( isset( $c['news'] ) ? (array) $c['news'] : array() as $n ) {
		if ( hub_businesses_rule_news_shown( isset( $n['url'] ) ? $n['url'] : '' ) ) {
			$news[] = array( 'title' => hub_plain( $n['title'] ), 'url' => $n['url'], 'category' => hub_plain( $n['cat'] ), 'date' => hub_plain( $n['date'] ) );
		}
	}
	$locations = array();
	foreach ( isset( $c['other_locations'] ) ? (array) $c['other_locations'] : array() as $l ) {
		$locations[] = array( 'label' => hub_plain( $l['label'] ), 'name' => hub_plain( $l['name'] ), 'address' => hub_plain( $l['address'] ) );
	}
	$opt = function ( $k ) use ( $c ) {
		return ! empty( $c[ $k ] ) ? hub_plain( $c[ $k ] ) : null;
	};

	return $b + array(
		'description'          => $description,
		'seo_title'            => $opt( 'seo_title' ),
		'verified_note'        => $b['claimed'] ? ( $opt( 'verified_note' ) ? $opt( 'verified_note' ) : 'Verified by the business since ' . $b['claimed_since'] . '.' ) : null,
		'business_of_the_week' => ! empty( $c['botw'] ),
		'indexable'            => hub_businesses_rule_indexable( $b['claimed'], $description, $hours, $schedule, $b['photo_count'] ),
		'hours'                => $hours,
		'hours_note'           => $opt( 'hours_note' ),
		'schedule'             => $schedule,
		'schedule_title'       => $opt( 'schedule_title' ),
		'schedule_note'        => $opt( 'schedule_note' ),
		'today_index'          => (int) $weekday,
		'other_locations'      => $locations,
		'social'               => $social,
		'news'                 => $news,
	);
}

/** One row of a business's events and classes. */
function hub_business_shape_event( array $row ) {
	return array(
		'title'    => hub_plain( $row['title'] ),
		'url'      => $row['url'],
		'date'     => $row['date'],
		'when'     => hub_plain( $row['when'] ),
		'price'    => hub_plain( $row['price'] ),
		'featured' => (bool) $row['featured'],
		'weekly'   => (bool) $row['weekly'],
	);
}
