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

/** Today's date for working out which offers are live (the option hub_today fixes it for the preview). */
function hub_offers_db_today() {
	return hub_today();
}

/* ------------------------------------------------------------------ writing: keep the sort keys in step */

add_action( 'acf/save_post', function ( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'hub_offer' !== get_post_type( $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_hub_business', (int) get_post_meta( $post_id, 'offer_business', true ) );
	if ( '' === get_post_meta( $post_id, '_hub_index', true ) ) {
		update_post_meta( $post_id, '_hub_index', 99 );   // new offers go after the business's imported ones
	}
}, 20 );

/* An offer has one business. When an offer is added on a business, ACF puts both businesses in the offer's "Business"
 * field ([old, new]): keep the new one and take the offer off the old business's list. (ACF is already inside its
 * own two-way update here, so this does not loop.) */
add_filter( 'acf/update_value/key=field_hub_offer_business', function ( $value, $post_id ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	$ids = array_values( array_filter( array_map( 'intval', $value ) ) );
	$new = $ids ? end( $ids ) : 0;
	foreach ( array_diff( $ids, array( $new ) ) as $old ) {
		$list = array_map( 'intval', acf_get_array( get_field( 'biz_offers', $old, false ) ) );
		update_field( 'field_hub_biz_offers', array_values( array_diff( $list, array( (int) $post_id ) ) ), $old );
	}
	return $new ? (string) $new : '';
}, 20, 2 );
