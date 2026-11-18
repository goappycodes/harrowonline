<?php
/**
 * Offers: the one array shape the templates and the app get. All text is plain (no HTML, no entities); templates
 * escape it when they print it. URLs are full and unescaped.
 *
 * offer:
 *   id, slug, url, title, description, value, terms[], redeem,
 *   subscriber_only, has_code, code (null for subscriber-only offers: their code is never public),
 *   featured, status ('live' | 'ended'),
 *   ends { date ('Y-m-d' | null), days_left (int | null), label, days_left_label, ending_soon },
 *   business { id, slug, name, url, plan, category { slug, name }, area { slug, name }, address,
 *              image { url, alt } | null, icon, sample }
 */
defined( 'ABSPATH' ) || exit;

/** Default how-to-redeem line when the business has not written one. */
const HUB_OFFERS_DEFAULT_REDEEM = 'Show this page when you pay.';

/**
 * @param WP_Post            $post     The offer.
 * @param array              $r        Its details record (hub_offers_db_record()).
 * @param int                $index    Its order within its business (hub_offers_db_index()).
 * @param array              $business Its business (hub_offers_db_business()).
 * @param DateTimeImmutable  $today
 * @param array              $names    Category and area names by slug: { categories: { slug: name }, areas: { slug: name } }.
 * @return array The offer, plus "_sort" (used for ordering; the controller removes it).
 */
function hub_offer_shape( WP_Post $post, array $r, $index, array $business, DateTimeImmutable $today, array $names ) {
	$title = hub_plain( $post->post_title );
	$sub   = ! empty( $r['sub'] );
	$code  = isset( $r['code'] ) ? trim( (string) $r['code'] ) : '';
	$end   = isset( $r['end'] ) ? (string) $r['end'] : '';
	$dates = hub_offers_rule_dates( $end, $today );

	return array(
		'id'              => $post->ID,
		'slug'            => $post->post_name,
		'url'             => hub_url( 'offers/' . $post->post_name . '/' ),
		'title'           => $title,
		'description'     => hub_plain( $post->post_content ),
		'value'           => hub_offers_rule_value( $title, isset( $r['value'] ) ? hub_plain( $r['value'] ) : '' ),
		'terms'           => array_map( 'hub_plain', isset( $r['terms'] ) ? (array) $r['terms'] : array() ),
		'redeem'          => ! empty( $r['redeem'] ) ? hub_plain( $r['redeem'] ) : HUB_OFFERS_DEFAULT_REDEEM,
		'subscriber_only' => $sub,
		'has_code'        => '' !== $code,
		'code'            => ( '' !== $code && ! $sub ) ? $code : null,
		'featured'        => ! empty( $r['featured'] ),
		'status'          => $dates['live'] ? 'live' : 'ended',
		'ends'            => array(
			'date'            => $dates['date'],
			'days_left'       => $dates['days_left'],
			'label'           => hub_offers_rule_ends_label( $dates['date'], $dates['days_left'] ),
			'days_left_label' => hub_offers_rule_days_left_label( $dates['date'], $dates['days_left'] ),
			'ending_soon'     => $dates['live'] && hub_offers_rule_ending_soon( $dates['days_left'] ),
		),
		'business'        => hub_offer_shape_business( $business, $names ),
		'_sort'           => array( $business['_order'], (int) $index ),
	);
}

/** The business, as much as an offer shows of it. $names as for hub_offer_shape(). */
function hub_offer_shape_business( array $x, array $names ) {
	$c      = isset( $x['claimed'] ) && is_array( $x['claimed'] ) ? $x['claimed'] : array();
	$images = isset( $c['images'] ) ? (array) $c['images'] : array();
	$alts   = isset( $c['image_alts'] ) ? (array) $c['image_alts'] : array();
	$cat    = isset( $x['cat'] ) ? $x['cat'] : '';
	$area   = isset( $x['area'] ) ? $x['area'] : '';
	$cat_n  = isset( $names['categories'][ $cat ] ) ? $names['categories'][ $cat ] : $cat;
	$area_n = isset( $names['areas'][ $area ] ) ? $names['areas'][ $area ] : $area;

	if ( ! empty( $c['address'] ) && empty( $c['service_area'] ) ) {
		$address = implode( ', ', array_filter( array( hub_plain( $c['address'] ), $area_n, isset( $x['postcode'] ) ? $x['postcode'] : '' ) ) );
	} else {
		$address = 'Covers ' . ( ! empty( $c['service_area'] ) ? hub_plain( $c['service_area'] ) : 'Harrow' );
	}

	return array(
		'id'       => (int) $x['_id'],
		'slug'     => $x['slug'],
		'name'     => hub_plain( $x['name'] ),
		'url'      => hub_url( 'business/' . $x['slug'] . '/' ),
		'plan'     => $x['_tier'],
		'category' => array( 'slug' => $cat, 'name' => $cat_n ),
		'area'     => array( 'slug' => $area, 'name' => $area_n ),
		'address'  => $address,
		'image'    => $images ? array( 'url' => hub_media_url( $images[0] ), 'alt' => isset( $alts[0] ) ? (string) $alts[0] : '' ) : null,
		'icon'     => ! empty( $c['icon'] ) ? $c['icon'] : 'store',
		'sample'   => hub_is_sample( $x ),
	);
}
