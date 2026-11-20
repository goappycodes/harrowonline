<?php
/**
 * Claiming a business: the claim pages' sign-in and email-link handling, and wp-admin > Claims, where staff review
 * the proof and approve or reject. Data and decisions are the claims module's controller.
 *
 *   /claim/?listing={slug}     the claim form (page-templates/claim.php); needs a signed-in account
 *   /claim/submitted/          claim received (page-templates/claim-submitted.php)
 *   /claim/verify/?c=&key=     the link in the business-email check
 */
defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', function () {
	$path = hub_account_request_path();
	if ( 'claim/verify' === $path ) {
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		$r = hub_claim_verify_email( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification -- the key in the link is the check
		wp_safe_redirect( $r['redirect'] );
		exit;
	}
	if ( in_array( $path, array( 'claim', 'claim/submitted' ), true ) && ! is_user_logged_in() ) {
		// Step 1 of claiming is signing in (or creating an account), then back here
		$back = hub_url( $path . '/' ) . ( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $back ), hub_url( 'sign-in/' ) ) );
		exit;
	}
}, 5 );

/* ------------------------------------------------------------------ wp-admin > Claims */

add_action( 'init', function () {
	register_post_type( 'hub_claim', array(
		'labels'          => array( 'name' => 'Claims', 'singular_name' => 'Claim', 'menu_name' => 'Claims', 'all_items' => 'All claims', 'edit_item' => 'Review claim', 'search_items' => 'Search claims', 'not_found' => 'No claims yet.' ),
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-yes-alt',
		'menu_position'   => 26,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),   // claims come from the claim page
		'map_meta_cap'    => true,
	) );
} );

/** "Claims (2)" in the menu while claims are waiting. */
add_action( 'admin_menu', function () {
	global $menu;
	$n = count( get_posts( array( 'post_type' => 'hub_claim', 'post_status' => 'private', 'numberposts' => 50, 'fields' => 'ids', 'meta_key' => '_hub_claim_status', 'meta_value' => 'pending' ) ) );
	foreach ( (array) $menu as $i => $item ) {
		if ( $n && isset( $item[2] ) && 'edit.php?post_type=hub_claim' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}, 99 );

// The Claims list's columns are in inc/admin/list-screens.php
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'hub_claim' !== $post->post_type ) {
		return $actions;
	}
	return array( 'edit' => '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Review</a>' );
}, 10, 2 );

/** The review screen: the claim's details and proof, and Approve / Reject. No editing. */
add_action( 'add_meta_boxes_hub_claim', function () {
	remove_meta_box( 'submitdiv', 'hub_claim', 'side' );
	add_meta_box( 'hub_claim_review', 'Claim', 'hub_claim_admin_box', 'hub_claim', 'normal', 'high' );
} );
add_action( 'admin_head-post.php', function () {
	if ( 'hub_claim' === get_post_type() ) {
		echo '<style>#titlediv{display:none}.hub-claim th{width:180px}</style>';
	}
} );

function hub_claim_admin_box( $post ) {
	$c = hub_claim_for_staff( $post->ID );
	if ( ! $c ) {
		return;
	}
	$e    = $c['evidence'];
	$rows = array(
		'Business' => $c['business'] ? '<a href="' . esc_url( $c['business']['url'] ) . '" target="_blank">' . esc_html( $c['business']['name'] ) . '</a><br>' . esc_html( trim( $c['business']['address'] . ', ' . $c['business']['postcode'], ', ' ) ) : '&ndash;',
		'Account'  => $c['account'] ? '<a href="' . esc_url( get_edit_user_link( $c['account']['id'] ) ) . '">' . esc_html( $c['account']['name'] . ' <' . $c['account']['email'] . '>' ) . '</a>' : '&ndash;',
		'Role'     => esc_html( $c['role_label'] ),
		'Phone'    => '' !== $c['phone'] ? esc_html( $c['phone'] ) : '&ndash;',
		'Their note' => '' !== $c['note'] ? nl2br( esc_html( $c['note'] ) ) : '&ndash;',
		'Proof'    => esc_html( $c['method_label'] ),
		'Status'   => '<strong>' . esc_html( $c['status_label'] ) . '</strong>' . ( '' !== $c['decision_note'] ? '<br>Note sent: ' . nl2br( esc_html( $c['decision_note'] ) ) : '' ),
	);
	if ( 'email' === $c['method'] ) {
		$rows['Business email'] = esc_html( $e['email'] ) . ( $e['email_verified'] ? ' &mdash; <strong style="color:#008a20">&check; link clicked</strong>' : ' &mdash; link not clicked yet' );
	} elseif ( 'phone' === $c['method'] ) {
		$rows['Call'] = esc_html( $e['call_number'] ) . ', ask for <strong>' . esc_html( $e['ask_for'] ) . '</strong>' . ( $e['call_times'] ? '<br>' . esc_html( implode( '; ', $e['call_times'] ) ) : '' );
	} else {
		$rows['Document'] = esc_html( $e['doc_type'] ) . ( $e['has_document'] ? ' &mdash; <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hub_claim_document&claim=' . $c['id'] ), 'hub_claim_document_' . $c['id'] ) ) . '">Download</a> (private: don&rsquo;t forward it)' : ' &mdash; missing' );
	}
	echo '<table class="form-table hub-claim" role="presentation">';
	foreach ( $rows as $label => $html ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . $html . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
	}
	echo '</table>';
	if ( 'pending' !== $c['status'] ) {
		return;
	}
	?>
	<hr>
	<p><label for="hub_claim_note"><strong>Note for a rejection</strong> (emailed to them; say what would work instead)</label><br>
	<textarea id="hub_claim_note" name="hub_claim_note" rows="3" class="large-text" form="hub-claim-decide"></textarea></p>
	<p>
		<button class="button button-primary" type="submit" name="decision" value="approve" form="hub-claim-decide">Approve: they manage this business</button>
		<button class="button" type="submit" name="decision" value="reject" form="hub-claim-decide">Reject and email them</button>
	</p>
	<?php
	// The decision form lives outside the post form (forms can't nest); the buttons point at it with form=""
	add_action( 'admin_footer', function () use ( $c ) {
		echo '<form id="hub-claim-decide" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="hub_claim_decide"><input type="hidden" name="claim" value="' . (int) $c['id'] . '">'
			. wp_nonce_field( 'hub_claim_decide_' . $c['id'], '_wpnonce', true, false ) . '</form>';
	} );
}

add_action( 'admin_post_hub_claim_decide', function () {
	$id = isset( $_POST['claim'] ) ? (int) $_POST['claim'] : 0;
	check_admin_referer( 'hub_claim_decide_' . $id );
	$decision = isset( $_POST['decision'] ) ? sanitize_key( $_POST['decision'] ) : '';
	$r        = 'approve' === $decision ? hub_claim_approve( $id ) : hub_claim_reject( $id, isset( $_POST['hub_claim_note'] ) ? wp_unslash( $_POST['hub_claim_note'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- the controller sanitises
	set_transient( 'hub_claim_msg_' . get_current_user_id(), $r ? $r : array( 'ok' => false, 'message' => 'Not allowed.' ), 60 );
	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	$r = get_transient( 'hub_claim_msg_' . get_current_user_id() );
	if ( $r ) {
		delete_transient( 'hub_claim_msg_' . get_current_user_id() );
		echo '<div class="notice notice-' . ( $r['ok'] ? 'success' : 'error' ) . ' is-dismissible"><p>' . wp_kses( $r['message'], array() ) . '</p></div>';
	}
} );

/** Staff download of a claim's proof document (never a public URL). */
add_action( 'admin_post_hub_claim_document', function () {
	$id = isset( $_GET['claim'] ) ? (int) $_GET['claim'] : 0;
	check_admin_referer( 'hub_claim_document_' . $id );
	$doc = hub_claim_document( $id );
	if ( ! $doc ) {
		wp_die( 'No document for this claim, or you can&rsquo;t open it.', 404 );
	}
	nocache_headers();
	header( 'Content-Type: ' . $doc['type'] );
	header( 'Content-Disposition: attachment; filename="' . $doc['name'] . '"' );
	header( 'Content-Length: ' . filesize( $doc['path'] ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $doc['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
} );
