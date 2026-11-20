<?php
/**
 * wp-admin business screen: an "At a glance" box at the top of the sidebar. Plan and status, who manages it, card
 * payments, and links to its offers, classes, payments and page on the Hub. Read-only: the fields stay in
 * "Business details". Uses the list screens' helpers and styles (inc/admin/list-screens.php, admin/hub-admin-lists.css).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_hub_business', function ( $post ) {
	if ( 'auto-draft' !== $post->post_status ) {
		add_meta_box( 'hub_glance', 'At a glance', 'hub_admin_glance_box', 'hub_business', 'side', 'high' );
	}
}, 5 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && $screen && 'hub_business' === $screen->post_type ) {
		$file = get_template_directory() . '/admin/hub-admin-lists.css';
		wp_enqueue_style( 'hub-admin-lists', get_template_directory_uri() . '/admin/hub-admin-lists.css', array(), (string) filemtime( $file ) );
	}
} );

function hub_admin_glance_box( WP_Post $post ) {
	$b = hub_admin_business( $post->ID );
	if ( ! $b ) {
		return;
	}
	$rows = array();

	// Plan, and how it's paid
	$p    = HUB_ADMIN_PLAN_PILLS[ isset( HUB_ADMIN_PLAN_PILLS[ $b['plan'] ] ) ? $b['plan'] : 'unclaimed' ];
	$plan = '<span class="hub-cell-row">' . hub_admin_pill( $p[0], $p[1] ) . '</span>';
	$sub  = hub_billing_db_subscription( $post->ID );
	if ( ! empty( $sub['status'] ) && in_array( $sub['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
		$plan .= hub_admin_sub( 'past_due' === $sub['status'] ? 'Payment failed: Stripe is retrying' : 'Paying by card, ' . ( 'annual' === $sub['interval'] ? 'yearly' : 'monthly' ) );
	}
	$rows['Plan'] = $plan;

	// Status
	$s              = $b['checked'] ? array( 'Checked by us', 'blue' ) : ( $b['claimed'] ? array( 'Verified', 'green' ) : array( 'Imported', 'grey' ) );
	$since          = $b['claimed'] && ! empty( $b['record']['claimed']['claimed'] ) ? 'Claimed since ' . $b['record']['claimed']['claimed'] : '';
	$rows['Status'] = '<span class="hub-cell-row">' . hub_admin_pill( $s[0], $s[1] ) . ( 'publish' !== $post->post_status ? hub_admin_status_pill( $post ) : '' ) . '</span>' . hub_admin_sub( $since );

	// Who manages it
	$owner              = hub_account_owner_id( $post->ID );   // account module
	$u                  = $owner ? get_userdata( $owner ) : null;
	$rows['Managed by'] = $u ? '<a class="hub-cell-main" href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->display_name ) . '</a>' . hub_admin_sub( $u->user_email ) : '<span class="hub-cell-quiet">Nobody yet</span>';

	// On the Hub
	$count = function ( $type, $key ) use ( $post ) {
		return count( get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => $key, 'meta_value' => $post->ID ) ) );
	};
	$links = array();
	foreach ( array( array( 'hub_offer', '_hub_business', 'offer', 'offers', 'dashicons-tag' ), array( 'hub_class', 'class_business', 'class', 'classes', 'dashicons-update' ), array( 'hub_payment', '_hub_business', 'payment', 'payments', 'dashicons-money-alt' ) ) as $l ) {
		$n       = 'hub_payment' === $l[0] ? count( get_posts( array( 'post_type' => 'hub_payment', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_hub_business', 'meta_value' => $post->ID ) ) ) : $count( $l[0], $l[1] );
		$links[] = $n
			? '<a class="hub-cell-line" href="' . esc_url( admin_url( 'edit.php?post_type=' . $l[0] . '&hub_biz=' . $post->ID ) ) . '"><span class="dashicons ' . $l[4] . '" aria-hidden="true"></span>' . $n . ' ' . ( 1 === $n ? $l[2] : $l[3] ) . '</a>'
			: '<span class="hub-cell-line hub-cell-quiet"><span class="dashicons ' . $l[4] . '" aria-hidden="true"></span>No ' . $l[3] . '</span>';
	}
	$rows['On the Hub'] = implode( '', $links );

	echo '<dl class="hub-glance">';
	foreach ( $rows as $label => $html ) {
		echo '<dt>' . esc_html( $label ) . '</dt><dd>' . $html . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped
	}
	echo '</dl>';
	if ( 'publish' === $post->post_status ) {
		echo '<p class="hub-glance-view"><a class="button" href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">View on the Hub &rarr;</a></p>';
	}
}
