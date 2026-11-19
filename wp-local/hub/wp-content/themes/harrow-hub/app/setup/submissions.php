<?php
/**
 * Offers, events and classes sent from the account pages (account/offers/edit/, account/events/edit/,
 * account/classes/edit/): they wait as "Pending Review" in wp-admin > Offers / Events / Classes. Publishing one puts
 * it live; moving it to the Trash declines it. Both email the person (if their settings allow). The pages are
 * page-templates/account-*-edit.php; the work is the submissions module's controller.
 */
defined( 'ABSPATH' ) || exit;

/** The forms: saved before the page is drawn; on success to the next page, else shown again with the errors. */
add_action( 'template_redirect', function () {
	$kinds = array( 'account/offers/edit' => 'offer', 'account/events/edit' => 'event', 'account/classes/edit' => 'class' );
	$path  = hub_account_request_path();
	if ( isset( $kinds[ $path ] ) && is_user_logged_in() && 'POST' === $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$r = hub_submission_save( $kinds[ $path ], $_POST + $_GET, $_FILES ); // phpcs:ignore WordPress.Security.NonceVerification -- the controller checks it and unslashes
		if ( $r['ok'] ) {
			wp_safe_redirect( $r['redirect'] );
			exit;
		}
		hub_submission_result( $r );
	}
}, 6 );

/** Pending Review -> Published / Trash, handled at the end of the request (after wp-admin has saved its fields). */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( ! in_array( $post->post_type, array( 'hub_offer', 'hub_event', 'hub_class' ), true ) || $new === $old || ! hub_submission_info( $post->ID ) ) {
		return;
	}
	if ( 'publish' === $new && in_array( $old, array( 'pending', 'draft' ), true ) ) {
		$GLOBALS['hub_submission_queue'][ $post->ID ] = 'published';
	} elseif ( 'trash' === $new && 'pending' === $old ) {
		$GLOBALS['hub_submission_queue'][ $post->ID ] = 'declined';
	}
}, 10, 3 );
add_action( 'shutdown', function () {
	foreach ( isset( $GLOBALS['hub_submission_queue'] ) ? (array) $GLOBALS['hub_submission_queue'] : array() as $id => $what ) {
		'published' === $what ? hub_submission_published( $id ) : hub_submission_declined( $id );
	}
	$GLOBALS['hub_submission_queue'] = array();
}, 1 );

/** A Basic business's £15 event listing was paid (billing webhook). */
add_action( 'hub_billing_item_paid', 'hub_submission_paid', 10, 4 );

/** wp-admin edit screens: who sent it, and whether a £15 event is paid. */
add_action( 'add_meta_boxes', function ( $post_type, $post ) {
	if ( in_array( $post_type, array( 'hub_offer', 'hub_event', 'hub_class' ), true ) && $post instanceof WP_Post && hub_submission_info( $post->ID ) ) {
		add_meta_box( 'hub_submission', 'Sent from the website', 'hub_submission_admin_box', $post_type, 'side', 'high' );
	}
}, 10, 2 );
function hub_submission_admin_box( $post ) {
	$i = hub_submission_info( $post->ID );
	echo '<p><strong>' . esc_html( $i['by'] ) . '</strong><br>' . esc_html( $i['email'] ) . '</p>';
	if ( $i['needs_pay'] ) {
		echo '<p>' . ( $i['paid'] ? '&check; <strong>£15 event listing paid</strong>' : '<strong style="color:#b32d2e">£15 event listing not paid yet</strong> (Basic plan)' ) . '</p>';
	}
	if ( 'pending' === $post->post_status ) {
		echo '<p class="description">Check it, then <strong>Publish</strong> to put it live, or <strong>Move to Trash</strong> to decline. They&rsquo;re emailed either way, unless they turned those emails off.</p>';
	}
}
