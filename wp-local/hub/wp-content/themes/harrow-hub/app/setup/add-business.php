<?php
/**
 * Adding a business from the website (/add-business/): sign-in first, and what happens when staff review it.
 * New businesses wait as "Pending Review" in wp-admin > Businesses. Publishing one makes it live (an owner gets it,
 * claimed on Basic); moving it to the Trash declines it. Both email the person who added it.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', function () {
	if ( 'add-business' === hub_account_request_path() && ! is_user_logged_in() ) {
		$back = hub_url( 'add-business/' ) . ( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $back ), hub_url( 'sign-in/' ) ) );
		exit;
	}
}, 5 );

/**
 * Pending Review -> Published / Trash. Handled at the end of the request: wp-admin's edit screen saves its ACF fields
 * after this hook, and they would overwrite "Claimed".
 */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'hub_business' !== $post->post_type || 'pending' !== $old || ! hub_business_added_info( $post->ID ) ) {
		return;
	}
	if ( 'publish' === $new ) {
		$GLOBALS['hub_added_queue'][ $post->ID ] = 'published';
	} elseif ( 'trash' === $new ) {
		$GLOBALS['hub_added_queue'][ $post->ID ] = 'declined';
	}
}, 10, 3 );
add_action( 'shutdown', function () {
	foreach ( isset( $GLOBALS['hub_added_queue'] ) ? (array) $GLOBALS['hub_added_queue'] : array() as $id => $what ) {
		'published' === $what ? hub_business_added_published( $id ) : hub_business_added_declined( $id );
	}
	$GLOBALS['hub_added_queue'] = array();
}, 1 );

/** wp-admin business screen: who added it from the website, and what they ticked that has no field yet. */
add_action( 'add_meta_boxes_hub_business', function ( $post ) {
	if ( hub_business_added_info( $post->ID ) ) {
		add_meta_box( 'hub_added_by', 'Added from the website', 'hub_business_added_box', 'hub_business', 'side', 'high' );
	}
} );
function hub_business_added_box( $post ) {
	$i = hub_business_added_info( $post->ID );
	echo '<p><strong>' . esc_html( $i['email'] ) . '</strong><br>' . ( 'owner' === $i['as'] ? 'As the owner: publishing makes it theirs (claimed, Basic plan).' : 'As a recommendation (not the owner).' ) . '</p>';
	if ( $i['facility_labels'] ) {
		echo '<p>Facilities: ' . esc_html( implode( ', ', $i['facility_labels'] ) ) . '</p>';
	}
	if ( 'pending' === $post->post_status ) {
		echo '<p class="description">Check it, then <strong>Publish</strong> to put it live, or decline it below. They&rsquo;re emailed either way.</p>';
		hub_review_decline_fields( $post );   // app/setup/submissions.php
	}
}
