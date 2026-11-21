<?php
/**
 * Submissions: who sent what. The posts themselves are saved by the offers and events modules; this keeps, on each:
 *   _hub_added_by   the account that sent it
 *   _hub_needs_pay  1 when it's a Basic business's £15 event, _hub_paid 1 once paid (billing webhook)
 *   _hub_decline_note  what staff wrote when they declined it (sent in the email, shown on the account pages until
 *                      it's sent again)
 * A declined one is in the Trash; WordPress's own "_wp_trash_meta_status" (the status it had) tells a declined one
 * ("pending") from one staff took down.
 */
defined( 'ABSPATH' ) || exit;

function hub_submissions_db_mark( $post_id, $user_id, $needs_pay ) {
	update_post_meta( (int) $post_id, '_hub_added_by', (int) $user_id );
	update_post_meta( (int) $post_id, '_hub_needs_pay', $needs_pay ? 1 : 0 );
}

/** { user, needs_pay, paid } or null when it wasn't sent from the website. */
function hub_submissions_db_info( $post_id ) {
	$user = (int) get_post_meta( (int) $post_id, '_hub_added_by', true );
	return $user ? array(
		'user'      => $user,
		'needs_pay' => (bool) get_post_meta( (int) $post_id, '_hub_needs_pay', true ),
		'paid'      => (bool) get_post_meta( (int) $post_id, '_hub_paid', true ),
	) : null;
}

function hub_submissions_db_decline_note( $post_id ) {
	return (string) get_post_meta( (int) $post_id, '_hub_decline_note', true );
}

function hub_submissions_db_set_decline_note( $post_id, $note ) {
	update_post_meta( (int) $post_id, '_hub_decline_note', (string) $note );
}

function hub_submissions_db_trash( $post_id ) {
	return (bool) wp_trash_post( (int) $post_id );
}

function hub_submissions_db_set_paid( $post_id ) {
	update_post_meta( (int) $post_id, '_hub_paid', 1 );
}

/** Upload a photo ($_FILES key) to the Media Library, attached to the post. Its URL, or WP_Error. */
function hub_submissions_db_photo( $post_id, $file_key ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$aid = media_handle_upload( $file_key, (int) $post_id, array(), array( 'test_form' => false, 'mimes' => HUB_SUBMISSIONS_PHOTO_TYPES ) );
	if ( is_wp_error( $aid ) ) {
		return $aid;
	}
	set_post_thumbnail( (int) $post_id, $aid );
	return (string) wp_get_attachment_image_url( $aid, 'large' );
}

function hub_submissions_db_post_type( $post_id ) {
	return (string) get_post_type( (int) $post_id );
}

function hub_submissions_db_send_email( $to, $subject, $body ) {
	return wp_mail( $to, $subject, $body, array( 'Reply-To: Harrow Online <hub@harrowonline.org>' ) );
}

/* ------------------------------------------------------------------ the account lists */

/**
 * IDs of the posts of one type an account sent, most recently saved first.
 *
 * @param string   $post_type 'hub_offer' | 'hub_event' | 'hub_class'
 * @param string[] $statuses  Post statuses (every one by default, the Trash included: declined ones are there).
 * @return int[]
 */
function hub_submissions_db_mine( $post_type, $user_id, array $statuses = array( 'draft', 'pending', 'publish', 'future', 'private', 'trash' ) ) {
	return array_map( 'intval', get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => $statuses,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_hub_added_by', 'value' => (int) $user_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- one account's few posts
		'orderby'        => 'modified',
		'order'          => 'DESC',
	) ) );
}

/** { status, saved (when it was last saved), declined (when it went to the Trash; 0 if it isn't there), trashed_from }. */
function hub_submissions_db_times( $post_id ) {
	$post_id = (int) $post_id;
	return array(
		'status'       => (string) get_post_status( $post_id ),
		'saved'        => (int) get_post_timestamp( $post_id, 'modified' ),
		'declined'     => (int) get_post_meta( $post_id, '_wp_trash_meta_time', true ),
		'trashed_from' => (string) get_post_meta( $post_id, '_wp_trash_meta_status', true ),
	);
}

/** Bring a declined one back from the Trash (as a draft, with its old address), so it can be changed and sent again. */
function hub_submissions_db_untrash( $post_id ) {
	return (bool) wp_untrash_post( (int) $post_id );
}

/** Staff's note is done with once it's sent again. */
function hub_submissions_db_clear_decline_note( $post_id ) {
	delete_post_meta( (int) $post_id, '_hub_decline_note' );
}
