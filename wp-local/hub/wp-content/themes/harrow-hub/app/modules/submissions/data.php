<?php
/**
 * Submissions: who sent what. The posts themselves are saved by the offers and events modules; this keeps, on each:
 *   _hub_added_by   the account that sent it
 *   _hub_needs_pay  1 when it's a Basic business's £15 event, _hub_paid 1 once paid (billing webhook)
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
