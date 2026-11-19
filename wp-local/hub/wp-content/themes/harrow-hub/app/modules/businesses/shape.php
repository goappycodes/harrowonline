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
 * event row (a business's events and classes, from the events module):
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

/** A profile row from one of its one-off events (events module shape). */
function hub_business_shape_event_row( array $e ) {
	$d = date_create_immutable( $e['date'] );
	return array(
		'title' => $e['title'], 'url' => $e['url'], 'date' => $e['date'],
		'when'  => $d->format( 'D j M' ) . ', ' . $e['time'],
		'price' => $e['price'], 'featured' => $e['featured'], 'weekly' => false, 'order' => 0,
	);
}

/** A profile row from one of its weekly classes (events module shape): dated the next time it runs. */
function hub_business_shape_class_row( array $c, DateTimeImmutable $today ) {
	$ahead = ( $c['weekday'] - hub_weekday( $today ) + 7 ) % 7;
	return array(
		'title' => $c['title'], 'url' => $c['url'], 'date' => $today->modify( '+' . $ahead . ' day' )->format( 'Y-m-d' ),
		'when'  => 'Every ' . $c['day_name'] . ', ' . $c['time'],
		'price' => $c['price'], 'featured' => $c['featured'], 'weekly' => true, 'order' => $c['order'],
	);
}

/** A profile row from a class kept in the imported record (businesses never saved in wp-admin). */
function hub_business_shape_imported_class_row( array $c ) {
	$months = array( 'Oct' => 10, 'Nov' => 11, 'Dec' => 12 );
	return array(
		'title' => hub_plain( $c['title'] ), 'url' => hub_url( 'events/' . hub_slugify( hub_unescape( $c['title'] ) ) . '/' ),
		'date'  => sprintf( '2026-%02d-%02d', isset( $months[ $c['m'] ] ) ? $months[ $c['m'] ] : 11, (int) $c['d'] ),
		'when'  => hub_plain( $c['when'] ), 'price' => hub_plain( $c['price'] ),
		'featured' => ! empty( $c['featured'] ), 'weekly' => ! empty( $c['class'] ), 'order' => 0,
	);
}

/**
 * Emails about a business added from the website, plain text: { subject, body }.
 *
 * @param string $kind 'received' | 'staff' | 'live' (owner) | 'live_recommend' | 'declined'
 * @param array  $a    { first_name, business, link, account_email, as_label }
 */
function hub_businesses_shape_added_email( $kind, array $a ) {
	$hi   = 'Hi ' . ( '' !== $a['first_name'] ? $a['first_name'] : 'there' ) . ",\n\n";
	$sign = "\n\nThanks,\nThe Harrow Online team\nhub@harrowonline.org";
	switch ( $kind ) {
		case 'received':
			return array(
				'subject' => 'We\'ve received ' . $a['business'],
				'body'    => $hi . 'Thanks for adding ' . $a['business'] . ' to the Harrow Online Hub. A member of our team will check it, usually within 2 working days, and email you either way.' . $sign,
			);
		case 'live':
			return array(
				'subject' => $a['business'] . ' is live on Harrow Online Hub',
				'body'    => $hi . $a['business'] . ' is now on the Harrow Online Hub, on the free Basic plan with a verified badge, and you manage it from your account:'
					. "\n" . $a['link']
					. "\n\nWant more photos, Local Offers, events and a place at the top of your category? You can upgrade any time." . $sign,
			);
		case 'live_recommend':
			return array(
				'subject' => 'Thanks: ' . $a['business'] . ' is on Harrow Online Hub',
				'body'    => $hi . 'Thanks for recommending ' . $a['business'] . '. It\'s now on the Harrow Online Hub:' . "\n" . $a['link']
					. "\n\nIf you know the owner, let them know they can claim it free to keep it up to date." . $sign,
			);
		case 'declined':
			return array(
				'subject' => 'About ' . $a['business'] . ' on Harrow Online Hub',
				'body'    => $hi . 'We couldn\'t add ' . $a['business'] . ' to the Harrow Online Hub this time. It may already be listed, be outside Harrow, or we couldn\'t check the details.'
					. "\n\nReply to this email and we'll explain or help." . $sign,
			);
	}
	return array(
		'subject' => 'New business to check: ' . $a['business'],
		'body'    => $a['account_email'] . ' added ' . $a['business'] . ' (' . $a['as_label'] . ").\n\nCheck it, then Publish or move it to the Trash:\n" . $a['link'],
	);
}
