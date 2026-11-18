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
 *              image { url, alt } | null, icon, sample }   (from the businesses module's business shape)
 */
defined( 'ABSPATH' ) || exit;

/** Default how-to-redeem line when the business has not written one. */
const HUB_OFFERS_DEFAULT_REDEEM = 'Show this page when you pay.';

/**
 * @param WP_Post            $post     The offer.
 * @param array              $r        Its details record (hub_offers_db_record()).
 * @param int                $index    Its order within its business (hub_offers_db_index()).
 * @param array              $business Its business (businesses module, hub_businesses_summaries()).
 * @param DateTimeImmutable  $today
 * @return array The offer, plus "_sort" (used for ordering; the controller removes it).
 */
function hub_offer_shape( WP_Post $post, array $r, $index, array $business, DateTimeImmutable $today ) {
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
		'business'        => hub_offer_shape_business( $business ),
		'_sort'           => array( $business['directory_order'], (int) $index ),
	);
}

/** The business, as much as an offer shows of it (from the businesses module's business shape). */
function hub_offer_shape_business( array $b ) {
	$a = $b['address'];
	return array(
		'id'       => $b['id'],
		'slug'     => $b['slug'],
		'name'     => $b['name'],
		'url'      => $b['url'],
		'plan'     => $b['plan'],
		'category' => $b['category'],
		'area'     => $b['area'],
		'address'  => $a['has_premises'] ? implode( ', ', array_filter( array( $a['street'], $b['area']['name'], $a['postcode'] ) ) )
			: 'Covers ' . ( $a['service_area'] ? $a['service_area'] : 'Harrow' ),
		'image'    => $b['images'] ? $b['images'][0] : null,
		'icon'     => $b['icon'],
		'sample'   => $b['sample'],
	);
}
