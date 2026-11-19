<?php
/**
 * Claims: the only place that stores claims. Each claim is a private "hub_claim" post (wp-admin > Claims) with:
 *   _hub_claim           { business, user, method, details{...}, role, phone, note, status, email_verified,
 *                          decided_by, decided_at, decision_note }
 *   _hub_claim_business, _hub_claim_user, _hub_claim_status   (for lookups)
 * Uploaded proof is kept outside the public uploads, in wp-content/hub-private/claims/ (blocked on the web; staff
 * download it through wp-admin).
 */
defined( 'ABSPATH' ) || exit;

function hub_claims_db_create( array $c, $title ) {
	$id = wp_insert_post( array( 'post_type' => 'hub_claim', 'post_status' => 'private', 'post_title' => $title ), true );
	if ( ! is_wp_error( $id ) ) {
		hub_claims_db_save( $id, $c );
	}
	return $id;
}

function hub_claims_db_save( $id, array $c ) {
	update_post_meta( $id, '_hub_claim', $c );
	update_post_meta( $id, '_hub_claim_business', (int) $c['business'] );
	update_post_meta( $id, '_hub_claim_user', (int) $c['user'] );
	update_post_meta( $id, '_hub_claim_status', $c['status'] );
}

/** A claim with its post ID and date, or null. */
function hub_claims_db_get( $id ) {
	$post = get_post( (int) $id );
	if ( ! $post || 'hub_claim' !== $post->post_type ) {
		return null;
	}
	$c = get_post_meta( $post->ID, '_hub_claim', true );
	return is_array( $c ) ? $c + array( 'id' => $post->ID, 'submitted' => $post->post_date ) : null;
}

/** Claim IDs, newest first. $by: { user, business, status } (any of them). */
function hub_claims_db_find( array $by, $limit = 20 ) {
	$mq = array();
	foreach ( array( 'user' => '_hub_claim_user', 'business' => '_hub_claim_business', 'status' => '_hub_claim_status' ) as $k => $key ) {
		if ( isset( $by[ $k ] ) ) {
			$mq[] = array( 'key' => $key, 'value' => $by[ $k ] );
		}
	}
	return get_posts( array( 'post_type' => 'hub_claim', 'post_status' => 'private', 'numberposts' => $limit, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'DESC', 'meta_query' => $mq ) );
}

/** Store an uploaded proof file privately. Returns its path below the private folder, or WP_Error. */
function hub_claims_db_store_document( $claim_id, array $file, $ext ) {
	$dir = WP_CONTENT_DIR . '/hub-private/claims';
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'no_folder', 'Could not make the private folder.' );
	}
	foreach ( array( WP_CONTENT_DIR . '/hub-private/.htaccess' => "Require all denied\n", WP_CONTENT_DIR . '/hub-private/index.php' => "<?php // Silence.\n" ) as $path => $body ) {
		if ( ! file_exists( $path ) ) {
			file_put_contents( $path, $body );
		}
	}
	$name = 'claims/' . (int) $claim_id . '-' . wp_generate_password( 16, false ) . '.' . $ext;
	if ( ! is_uploaded_file( $file['tmp_name'] ) || ! @move_uploaded_file( $file['tmp_name'], WP_CONTENT_DIR . '/hub-private/' . $name ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- only files PHP received in this request
		return new WP_Error( 'not_saved', 'Could not save the file.' );
	}
	return $name;
}

/** Full path of a stored proof file, or '' if it's missing. */
function hub_claims_db_document_path( $name ) {
	$path = WP_CONTENT_DIR . '/hub-private/' . ltrim( (string) $name, '/' );
	return ( '' !== (string) $name && false === strpos( (string) $name, '..' ) && is_file( $path ) ) ? $path : '';
}

/** A new key for the business-email link (only its hash is stored). */
function hub_claims_db_new_verify_key( $claim_id ) {
	$key = wp_generate_password( 32, false );
	update_post_meta( (int) $claim_id, '_hub_claim_verify', array( 'hash' => hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ), 'at' => time() ) );
	return $key;
}

function hub_claims_db_check_verify_key( $claim_id, $key, $max_age ) {
	$v = get_post_meta( (int) $claim_id, '_hub_claim_verify', true );
	return is_array( $v ) && time() - (int) $v['at'] <= $max_age && hash_equals( $v['hash'], hash_hmac( 'sha256', (string) $key, wp_salt( 'auth' ) ) );
}

function hub_claims_db_send_email( $to, $subject, $body ) {
	return wp_mail( $to, $subject, $body, array( 'Reply-To: Harrow Online <hub@harrowonline.org>' ) );
}
