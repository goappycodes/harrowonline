<?php
/**
 * Offers (post type hub_offer): the only file that reads offers from WordPress.
 *
 * An offer's details are its fields (offer_*, below; edited in wp-admin) on top of its imported "_hub" record (value,
 * code, end, sub, featured, terms, redeem); its title and description are the post title and content. Its business is the post meta "offer_business" (set in wp-admin on the
 * offer or on the business), or the imported link "_hub_business" for offers never saved. "_hub_index" is its order
 * within its business.
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

/**
 * The offer's fields (plain post meta, edited in wp-admin's "Offer details" box): one meta key each, terms a list.
 * Offers with fields have "_hub_fields" = 1; the others still read their imported record.
 */
const HUB_OFFERS_FIELDS = array( 'offer_value', 'offer_end', 'offer_featured', 'offer_sub', 'offer_code', 'offer_redeem', 'offer_terms', 'offer_icon', 'offer_position' );

/** The offer's details record (array): the imported record with the fields on top. */
function hub_offers_db_record( WP_Post $post ) {
	$r = get_post_meta( $post->ID, '_hub', true );
	$r = is_array( $r ) ? $r : array();
	if ( '1' !== (string) get_post_meta( $post->ID, '_hub_fields', true ) ) {
		return $r;
	}
	$v = hub_offers_db_fields( $post->ID );
	foreach ( array( 'value', 'end', 'code', 'redeem', 'icon' ) as $k ) {
		if ( '' !== (string) $v[ 'offer_' . $k ] ) {
			$r[ $k ] = $v[ 'offer_' . $k ];
		} else {
			unset( $r[ $k ] );
		}
	}
	$r['featured'] = '1' === (string) $v['offer_featured'];
	$r['sub']      = '1' === (string) $v['offer_sub'];
	$r['terms']    = is_array( $v['offer_terms'] ) ? array_values( $v['offer_terms'] ) : array();
	return $r;
}

/** Every field's stored value (for the wp-admin box). */
function hub_offers_db_fields( $post_id ) {
	$out = array();
	foreach ( HUB_OFFERS_FIELDS as $k ) {
		$out[ $k ] = get_post_meta( $post_id, $k, true );
	}
	return $out;
}

/** Save an offer's fields (already cleaned by the wp-admin box), then the sort keys. Empty values are removed. */
function hub_offers_db_save_fields( $post_id, array $values ) {
	hub_db_save_meta( $post_id, HUB_OFFERS_FIELDS, $values );
	$f = hub_offers_db_fields( $post_id );
	update_post_meta( $post_id, '_hub_end', (string) $f['offer_end'] );
	update_post_meta( $post_id, '_hub_featured', '1' === (string) $f['offer_featured'] ? 1 : 0 );
	update_post_meta( $post_id, '_hub_index', '' !== (string) $f['offer_position'] ? (int) $f['offer_position'] : 99 );
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

/* ------------------------------------------------------------------ writing */

/** Every offer for the wp-admin lists, any status: [ { id, title, status, business } ] by title. */
function hub_offers_db_admin_list() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'hub_offer', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		$out[] = array( 'id' => $p->ID, 'title' => $p->post_title, 'status' => $p->post_status, 'business' => hub_offers_db_business_id( $p->ID ) );
	}
	return $out;
}

/** Set (or clear, with 0) the business an offer belongs to. An offer has one business. */
function hub_offers_db_set_business( $offer_id, $business_id ) {
	$business_id = (int) $business_id;
	update_post_meta( $offer_id, 'offer_business', $business_id ? (string) $business_id : '' );
	update_post_meta( $offer_id, '_hub_business', $business_id );
	if ( '' === get_post_meta( $offer_id, '_hub_index', true ) ) {
		update_post_meta( $offer_id, '_hub_index', 99 );   // new offers go after the business's imported ones
	}
}

/* ------------------------------------------------------------------ offers sent from the website (submissions module) */

/** An offer in any status (drafts and ones waiting for review), or null. */
function hub_offers_db_get_any( $id ) {
	$post = get_post( (int) $id );
	return ( $post && 'hub_offer' === $post->post_type ) ? $post : null;
}

/**
 * Create or update an offer sent from the website: its record, business and fields.
 *
 * @param int    $id          0 for a new offer
 * @param string $status      'draft' | 'pending'
 * @param array  $record      the "_hub" record
 * @param array  $form        the form's values, for editing it again ("_hub_form")
 * @return int|WP_Error
 */
function hub_offers_db_save( $id, $status, $title, $content, array $record, $business_id, array $form ) {
	$args = array( 'post_type' => 'hub_offer', 'post_status' => $status, 'post_title' => $title, 'post_content' => $content );
	if ( $id ) {
		$args['ID'] = (int) $id;
	}
	$id = $id ? wp_update_post( $args, true ) : wp_insert_post( $args, true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	// WordPress leaves the slug empty on drafts, and clears it on "pending" posts saved by accounts that can't publish
	// (every Hub account): set it here, so the record and the page link work at every status
	if ( '' === (string) get_post_field( 'post_name', $id ) ) {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_name' => wp_unique_post_slug( sanitize_title( $title ), $id, 'publish', 'hub_offer', 0 ) ), array( 'ID' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( $id );
	}
	$record['slug'] = get_post_field( 'post_name', $id );
	update_post_meta( $id, '_hub', $record );
	update_post_meta( $id, '_hub_end', $record['end'] );
	update_post_meta( $id, '_hub_featured', 0 );
	update_post_meta( $id, '_hub_form', $form );
	if ( '' === get_post_meta( $id, '_hub_index', true ) ) {
		update_post_meta( $id, '_hub_index', 99 );   // after the business's existing offers
	}
	hub_offers_db_set_business( $id, (int) $business_id );
	// The offer's fields (what the pages read and wp-admin edits) follow the record sent from the website
	hub_offers_db_save_fields( $id, hub_fields_offer_from_record( $id, $record ) );
	update_post_meta( $id, '_hub_featured', 0 );
	return (int) $id;
}
