<?php
/**
 * Keeping the Hub's data tidy in wp-admin:
 *   - Businesses > "Possible duplicates": businesses with the same name or the same phone number, side by side, with
 *     what they share. Staff decide (branches of one business are fine).
 *   - Offers, Events, Classes > Bulk actions > "Unpublish (move to Draft)", for ones that are over.
 *   - WordPress's "please update" notices only for people who can update WordPress.
 * Counts for the Dashboard's Needs attention box come from here and inc/admin/attention.php.
 */
defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ possible duplicate businesses */

/** A name for matching: lower case, no punctuation, no "the", "ltd", "limited", "and", "co", "uk", "plc". */
function hub_admin_match_name( $name ) {
	$n = strtolower( html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ) );
	$n = str_replace( '&', ' and ', $n );
	$n = preg_replace( "/['\x{2019}]/u", '', $n );
	$n = preg_replace( '/[^a-z0-9]+/', ' ', $n );
	$n = preg_replace( '/\b(the|ltd|limited|and|co|uk|plc)\b/', ' ', $n );
	return preg_replace( '/\s+/', '', $n );
}

/** A phone number for matching: digits only, +44 as 0; '' when too short to mean anything. */
function hub_admin_match_phone( $phone ) {
	$d = preg_replace( '/\D/', '', (string) $phone );
	$d = preg_replace( '/^44/', '0', $d );
	return strlen( $d ) >= 10 ? $d : '';
}

/**
 * Groups of businesses that may be the same: [ [ { id, name, postcode, status, mname, mphone } ... ] ... ], each group
 * sorted by name. Worked out from every business (live, waiting, draft) and kept for 6 hours or until a business changes.
 */
function hub_admin_duplicate_groups() {
	static $groups = null;
	if ( null !== $groups ) {
		return $groups;
	}
	$cached = get_transient( 'hub_dupe_groups' );
	if ( is_array( $cached ) ) {
		return $groups = $cached;
	}
	$rows   = hub_businesses_db_match_rows();   // businesses module
	$parent = array();
	$find   = function ( $x ) use ( &$parent, &$find ) {
		return $parent[ $x ] === $x ? $x : ( $parent[ $x ] = $find( $parent[ $x ] ) );
	};
	$by     = array();
	$info   = array();
	foreach ( $rows as $r ) {
		$id            = (int) $r['id'];
		$parent[ $id ] = $id;
		$info[ $id ]   = array( 'id' => $id, 'name' => $r['name'], 'postcode' => $r['postcode'], 'status' => $r['status'], 'mname' => hub_admin_match_name( $r['name'] ), 'mphone' => hub_admin_match_phone( $r['phone'] ) );
		foreach ( array( 'n:' . $info[ $id ]['mname'], 'p:' . $info[ $id ]['mphone'] ) as $key ) {
			if ( 'n:' === $key || 'p:' === $key ) {
				continue;
			}
			if ( isset( $by[ $key ] ) ) {
				$parent[ $find( $id ) ] = $find( $by[ $key ] );
			} else {
				$by[ $key ] = $id;
			}
		}
	}
	$sets = array();
	foreach ( array_keys( $info ) as $id ) {
		$sets[ $find( $id ) ][] = $info[ $id ];
	}
	$groups = array();
	foreach ( $sets as $set ) {
		if ( count( $set ) > 1 ) {
			usort( $set, function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			} );
			$groups[] = $set;
		}
	}
	usort( $groups, function ( $a, $b ) {
		return strcasecmp( $a[0]['name'], $b[0]['name'] );
	} );
	set_transient( 'hub_dupe_groups', $groups, 6 * HOUR_IN_SECONDS );
	return $groups;
}

/** Work the groups out again after any business changes. */
foreach ( array( 'save_post_hub_business', 'trashed_post', 'untrashed_post', 'deleted_post' ) as $hub_hook ) {
	add_action( $hub_hook, function () {
		delete_transient( 'hub_dupe_groups' );
	} );
}
unset( $hub_hook );

function hub_admin_showing_dupes() {
	return ! empty( $_GET['hub_dupes'] ); // phpcs:ignore WordPress.Security.NonceVerification
}

/** "Possible duplicates (N)" above the Businesses list. */
add_filter( 'views_edit-hub_business', function ( $views ) {
	$n = count( hub_admin_duplicate_groups() );
	if ( $n ) {
		$on                 = hub_admin_showing_dupes();
		$views['hub_dupes'] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=hub_business&hub_dupes=1' ) ) . '"' . ( $on ? ' class="current" aria-current="page"' : '' ) . '>Possible duplicates <span class="count">(' . $n . ')</span></a>';
		if ( $on ) {
			foreach ( $views as $k => $v ) {
				if ( 'hub_dupes' !== $k ) {
					$views[ $k ] = str_replace( array( ' class="current"', ' aria-current="page"' ), '', $v );
				}
			}
		}
	}
	return $views;
} );

/** The list: only businesses in a group, a group's businesses one after another. */
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	global $pagenow;
	if ( ! is_admin() || ! $q->is_main_query() || 'edit.php' !== $pagenow || 'hub_business' !== $q->get( 'post_type' ) || ! hub_admin_showing_dupes() ) {
		return;
	}
	$ids = array();
	foreach ( hub_admin_duplicate_groups() as $g ) {
		$ids = array_merge( $ids, wp_list_pluck( $g, 'id' ) );
	}
	$q->set( 'post__in', $ids ? $ids : array( 0 ) );
	$q->set( 'orderby', 'post__in' );
	$q->set( 'post_status', array( 'publish', 'pending', 'draft' ) );
}, 20 );

/** A "Looks like" column while showing them: the others in its group and what they share. */
add_filter( 'manage_hub_business_posts_columns', function ( $cols ) {
	if ( ! hub_admin_showing_dupes() ) {
		return $cols;
	}
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'title' === $k ) {
			$out['hub_dupe'] = 'Looks like';
		}
	}
	unset( $out['hub_content'] );
	return $out;
}, 20 );
add_action( 'manage_hub_business_posts_custom_column', function ( $col, $post_id ) {
	if ( 'hub_dupe' !== $col ) {
		return;
	}
	foreach ( hub_admin_duplicate_groups() as $g ) {
		$me = null;
		foreach ( $g as $b ) {
			if ( (int) $b['id'] === (int) $post_id ) {
				$me = $b;
			}
		}
		if ( ! $me ) {
			continue;
		}
		foreach ( $g as $b ) {
			if ( $b['id'] === $me['id'] ) {
				continue;
			}
			$why = array();
			if ( '' !== $me['mname'] && $me['mname'] === $b['mname'] ) {
				$why[] = 'same name';
			}
			if ( '' !== $me['mphone'] && $me['mphone'] === $b['mphone'] ) {
				$why[] = 'same phone';
			}
			$why = $why ? implode( ' and ', $why ) : 'linked through another';
			echo '<span class="hub-cell-line"><a href="' . esc_url( get_edit_post_link( $b['id'] ) ) . '">' . esc_html( html_entity_decode( $b['name'], ENT_QUOTES, 'UTF-8' ) ) . '</a>'
				. ( '' !== $b['postcode'] ? ' <span class="hub-cell-quiet">' . esc_html( $b['postcode'] ) . '</span>' : '' ) . '</span>'
				. '<span class="hub-dupe-why">' . esc_html( ucfirst( $why ) ) . ( 'publish' !== $b['status'] ? ' · ' . esc_html( 'pending' === $b['status'] ? 'in review' : $b['status'] ) : '' ) . '</span>';
		}
		return;
	}
}, 10, 2 );

/** A note above the list while showing them. */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( $screen && 'edit-hub_business' === $screen->id && hub_admin_showing_dupes() ) {
		echo '<div class="notice notice-info"><p><strong>Possible duplicates:</strong> businesses with the same name or phone number, next to each other. Branches of one business are fine. For a real duplicate, keep the better one (claimed, with more details) and move the other to the Trash. <a href="' . esc_url( admin_url( 'edit.php?post_type=hub_business' ) ) . '">Show all businesses</a></p></div>';
	}
} );

/* ------------------------------------------------------------------ Unpublish (move to Draft), for ones that are over */

foreach ( array( 'hub_offer', 'hub_event', 'hub_class' ) as $hub_type ) {
	add_filter( "bulk_actions-edit-{$hub_type}", function ( $actions ) {
		$actions['hub_unpublish'] = 'Unpublish (move to Draft)';
		return $actions;
	} );
	add_filter( "handle_bulk_actions-edit-{$hub_type}", function ( $redirect, $action, $ids ) {
		if ( 'hub_unpublish' !== $action ) {
			return $redirect;
		}
		$n = 0;
		foreach ( (array) $ids as $id ) {
			if ( current_user_can( 'edit_post', (int) $id ) && 'publish' === get_post_status( (int) $id ) && ! is_wp_error( wp_update_post( array( 'ID' => (int) $id, 'post_status' => 'draft' ), true ) ) ) {
				++$n;
			}
		}
		return add_query_arg( 'hub_unpublished', $n, remove_query_arg( array( 'hub_unpublished', 'trashed', 'untrashed', 'deleted', 'ids' ), $redirect ) );
	}, 10, 3 );
}
unset( $hub_type );

add_action( 'admin_notices', function () {
	if ( isset( $_GET['hub_unpublished'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$n = (int) $_GET['hub_unpublished']; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $n . ( 1 === $n ? ' item is' : ' items are' ) . ' now Drafts: off the Hub, kept here.' ) . '</p></div>';
	}
} );

/* ------------------------------------------------------------------ WordPress update notices: admins only */

add_action( 'admin_init', function () {
	if ( current_user_can( 'update_core' ) ) {
		return;
	}
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_action( 'network_admin_notices', 'update_nag', 3 );
	remove_action( 'admin_notices', 'maintenance_nag', 10 );
	remove_filter( 'update_footer', 'core_update_footer' );
} );
