<?php
/**
 * What's waiting for staff: a "Needs attention" box at the top of the Dashboard, and waiting counts on the
 * Businesses, Offers, Events and Classes menus (Claims has its own, in app/setup/claims.php).
 *
 *   Claims waiting for a check · businesses, offers, events and classes sent in from the website and in review ·
 *   events whose £15 isn't paid · failed card payments on plans
 */
defined( 'ABSPATH' ) || exit;

/** Types sent in from the website that staff review: post type => [ label, dashicon ]. */
const HUB_ADMIN_REVIEW_TYPES = array(
	'hub_business' => array( 'Businesses added', 'dashicons-store' ),
	'hub_offer'    => array( 'Offers', 'dashicons-tag' ),
	'hub_event'    => array( 'Events', 'dashicons-calendar-alt' ),
	'hub_class'    => array( 'Classes', 'dashicons-update' ),
);

/**
 * Everything waiting, for the box and the menus (once per request):
 *   [ { key, label, count, url, icon, tone, oldest (timestamp or 0) } ]
 */
function hub_admin_attention() {
	static $items = null;
	if ( null !== $items ) {
		return $items;
	}
	$items = array();

	// Claims
	$claims  = hub_claims_db_find( array( 'status' => 'pending' ), 100 );
	$items[] = array(
		'key'    => 'claims',
		'label'  => 'Claims waiting for a check',
		'count'  => count( $claims ),
		'url'    => admin_url( 'edit.php?post_type=hub_claim&hub_state=pending' ),
		'icon'   => 'dashicons-yes-alt',
		'tone'   => 'amber',
		'oldest' => $claims ? (int) get_post_time( 'U', false, end( $claims ) ) : 0,
	);

	// Sent in from the website, in review
	foreach ( HUB_ADMIN_REVIEW_TYPES as $type => $t ) {
		$n       = (int) wp_count_posts( $type )->pending;
		$first   = $n ? get_posts( array( 'post_type' => $type, 'post_status' => 'pending', 'numberposts' => 1, 'orderby' => 'date', 'order' => 'ASC', 'fields' => 'ids' ) ) : array();
		$items[] = array(
			'key'    => $type,
			'label'  => $t[0] . ' in review',
			'count'  => $n,
			'url'    => admin_url( 'edit.php?post_type=' . $type . '&post_status=pending' ),
			'icon'   => $t[1],
			'tone'   => 'amber',
			'oldest' => $first ? (int) get_post_time( 'U', false, $first[0] ) : 0,
		);
	}

	// £15 event listings not paid (Basic plan), not in the Trash
	$unpaid  = get_posts( array(
		'post_type'   => 'hub_event',
		'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private' ),
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_query'  => hub_admin_unpaid_events_query(),
	) );
	$items[] = array(
		'key'    => 'unpaid',
		'label'  => 'Events with £15 not paid',
		'count'  => count( $unpaid ),
		'url'    => admin_url( 'edit.php?post_type=hub_event&hub_state=unpaid' ),
		'icon'   => 'dashicons-money-alt',
		'tone'   => 'red',
		'oldest' => 0,
	);

	// Plans whose card payment failed (Stripe is retrying)
	$failed  = get_posts( array(
		'post_type'   => 'hub_business',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_query'  => array( array( 'key' => '_hub_subscription', 'value' => '"past_due"', 'compare' => 'LIKE' ) ),
	) );
	$items[] = array(
		'key'    => 'failed',
		'label'  => 'Plans with a failed card payment',
		'count'  => count( $failed ),
		'url'    => admin_url( 'edit.php?post_type=hub_payment&hub_state=failed' ),
		'icon'   => 'dashicons-warning',
		'tone'   => 'red',
		'oldest' => 0,
	);
	return $items;
}

/** Meta query: events that need the £15 and haven't paid it. */
function hub_admin_unpaid_events_query() {
	return array(
		'relation' => 'AND',
		array( 'key' => '_hub_needs_pay', 'value' => '1' ),
		array(
			'relation' => 'OR',
			array( 'key' => '_hub_paid', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_hub_paid', 'value' => '1', 'compare' => '!=' ),
		),
	);
}

/* ------------------------------------------------------------------ the Dashboard box */

add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	wp_add_dashboard_widget( 'hub_attention', 'Needs attention', 'hub_admin_attention_box' );
	// First in the left column
	global $wp_meta_boxes;
	$normal = $wp_meta_boxes['dashboard']['normal']['core'];
	$box    = array( 'hub_attention' => $normal['hub_attention'] );
	unset( $normal['hub_attention'] );
	$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $box, $normal ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
} );

function hub_admin_attention_box() {
	$items = hub_admin_attention();
	$total = array_sum( wp_list_pluck( $items, 'count' ) );
	if ( ! $total ) {
		echo '<p class="hub-attn-clear"><span class="dashicons dashicons-yes" aria-hidden="true"></span> All clear: nothing is waiting.</p>';
		return;
	}
	echo '<ul class="hub-attn">';
	foreach ( $items as $i ) {
		if ( ! $i['count'] ) {
			continue;
		}
		$age = $i['oldest'] ? 'Oldest sent ' . human_time_diff( $i['oldest'], current_time( 'timestamp' ) ) . ' ago' : '';
		echo '<li><a href="' . esc_url( $i['url'] ) . '">'
			. '<span class="hub-attn-icon hub-attn-icon--' . esc_attr( $i['tone'] ) . '"><span class="dashicons ' . esc_attr( $i['icon'] ) . '" aria-hidden="true"></span></span>'
			. '<span class="hub-attn-text"><span class="hub-attn-label">' . esc_html( $i['label'] ) . '</span>'
			. ( '' !== $age ? '<span class="hub-attn-age">' . esc_html( $age ) . '</span>' : '' ) . '</span>'
			. '<span class="hub-attn-count">' . (int) $i['count'] . '</span>'
			. '</a></li>';
	}
	echo '</ul>';
	$clear = array_filter( $items, function ( $i ) {
		return ! $i['count'];
	} );
	if ( $clear ) {
		echo '<p class="hub-attn-none">Nothing waiting: ' . esc_html( strtolower( implode( ', ', wp_list_pluck( $clear, 'label' ) ) ) ) . '.</p>';
	}
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'index.php' === $hook ) {
		$file = get_template_directory() . '/admin/hub-admin-lists.css';
		wp_enqueue_style( 'hub-admin-lists', get_template_directory_uri() . '/admin/hub-admin-lists.css', array(), (string) filemtime( $file ) );
	}
} );

/* ------------------------------------------------------------------ waiting counts on the menus */

add_action( 'admin_menu', function () {
	global $menu;
	$counts = array();
	foreach ( array_keys( HUB_ADMIN_REVIEW_TYPES ) as $type ) {
		$counts[ 'edit.php?post_type=' . $type ] = array( (int) wp_count_posts( $type )->pending );
	}
	foreach ( (array) $menu as $i => $item ) {
		if ( isset( $item[2], $counts[ $item[2] ] ) && $counts[ $item[2] ][0] ) {
			$n = $counts[ $item[2] ][0];
			$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . $n . '" title="' . esc_attr( $n . ' in review' ) . '"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}, 99 );
