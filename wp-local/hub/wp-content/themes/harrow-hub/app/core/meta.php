<?php
/**
 * Saving a post's fields (plain post meta) for the modules' data.php files: businesses, offers, events, classes.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Write the given fields (only the keys in $allowed), removing empty ones rather than storing them, and mark the post
 * as having fields ("_hub_fields" = 1: its details now come from the fields, not only the imported record).
 * "0" is a value and is kept.
 */
function hub_db_save_meta( $post_id, array $allowed, array $values ) {
	foreach ( $allowed as $k ) {
		if ( ! array_key_exists( $k, $values ) ) {
			continue;
		}
		$v = $values[ $k ];
		if ( '' === $v || null === $v || array() === $v ) {
			delete_post_meta( $post_id, $k );
		} else {
			update_post_meta( $post_id, $k, $v );
		}
	}
	update_post_meta( $post_id, '_hub_fields', 1 );
}
