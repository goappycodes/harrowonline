<?php
/**
 * Offers (post type hub_offer): the only file that reads offers from WordPress.
 *
 * An offer's details are in its "_hub" meta record (value, code, end, sub, featured, terms, redeem); its title and
 * description are the post title and content. Its business is the ACF field "offer_business", or the imported link
 * "_hub_business" for offers not saved with that field yet. "_hub_index" is its order within its business.
 */
defined( 'ABSPATH' ) || exit;

/** Every published offer, in their stored order. Cached for the request (there are tens, not thousands). */
function hub_offers_db_all() {
	static $posts = null;
	if ( null === $posts ) {
		$posts = get_posts( array(
			'post_type'      => 'hub_offer',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_hub_index',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		) );
	}
	return $posts;
}

/** One published offer by post ID or slug, or null. */
function hub_offers_db_get( $id_or_slug ) {
	$post = is_int( $id_or_slug ) ? get_post( $id_or_slug ) : get_page_by_path( (string) $id_or_slug, OBJECT, 'hub_offer' );
	return ( $post && 'hub_offer' === $post->post_type && 'publish' === $post->post_status ) ? $post : null;
}

/** Published offers of one business, in their stored order. */
function hub_offers_db_for_business( $business_id ) {
	$business_id = (int) $business_id;
	return array_values( array_filter( hub_offers_db_all(), function ( $p ) use ( $business_id ) {
		return hub_offers_db_business_id( $p->ID ) === $business_id;
	} ) );
}

/** The offer's details record (array). */
function hub_offers_db_record( WP_Post $post ) {
	$r = get_post_meta( $post->ID, '_hub', true );
	return is_array( $r ) ? $r : array();
}

/** The offer's position within its business (for the order of lists). */
function hub_offers_db_index( WP_Post $post ) {
	return (int) get_post_meta( $post->ID, '_hub_index', true );
}

/** Post ID of the offer's business (0 when none). */
function hub_offers_db_business_id( $offer_id ) {
	return metadata_exists( 'post', $offer_id, 'offer_business' )
		? (int) get_post_meta( $offer_id, 'offer_business', true )
		: (int) get_post_meta( $offer_id, '_hub_business', true );
}

/**
 * What an offer needs to know about its business: the business record (imported, or from its ACF fields once it
 * has been saved in wp-admin), plus its plan and its place in the directory order. Cached per business.
 *
 * Reads the business directly and never asks for its offers, so building a business page (which lists its
 * offers) cannot loop. Moves to the businesses module's controller when that module is built.
 *
 * @return array|null
 */
function hub_offers_db_business( $business_id ) {
	static $cache = array();
	$business_id = (int) $business_id;
	if ( ! array_key_exists( $business_id, $cache ) ) {
		$post = $business_id ? get_post( $business_id ) : null;
		if ( ! $post || 'hub_business' !== $post->post_type ) {
			$cache[ $business_id ] = null;
		} else {
			$x = hub_record( $post );
			if ( hub_uses_acf( $post->ID ) ) {
				$x = hub_business_from_acf( $post, $x );
			}
			$x['_id']    = $post->ID;
			$x['_tier']  = (string) get_post_meta( $post->ID, '_hub_tier', true );
			$x['_order'] = (int) get_post_meta( $post->ID, '_hub_order', true );
			$cache[ $business_id ] = $x;
		}
	}
	return $cache[ $business_id ];
}

/** Today's date for working out which offers are live (the option hub_today fixes it for the preview). */
function hub_offers_db_today() {
	return hub_today();
}
