<?php
/**
 * wp-admin list screens (Posts, Pages, Businesses, Offers, Events, Classes, Payments, Claims): more useful columns, filters, sorting and
 * styling (admin/hub-admin-lists.css). Read-only: each cell shows what the Hub shows, from the modules' data functions.
 *
 *   Businesses  plan, status (verified / checked / imported), owner, category and area, offers and classes
 *   Offers      business, run dates (ends in / ended), code and who can see it, featured, status, sent from the website
 *   Events      date and time (upcoming / past), category, venue, organiser, price and age, featured, status, £15 paid
 *   Classes     day and time, type, provider, venue, price and age, featured, status
 *   Pages       address on the Hub, template, editable text
 *   Payments    what was bought, amount, paid / failed, business and plan, account
 *   Claims      business, account and their role, waiting / approved / not approved, proof
 *   Users       email (confirmed or not), account type and business, plan and card payments, sign-up details, joined
 *   Categories  businesses in each, how many are claimed and paying
 *   Areas       the same, and accounts that chose the area at sign-up
 *   Posts       a note: the Hub doesn't use Posts
 */
defined( 'ABSPATH' ) || exit;

const HUB_ADMIN_LIST_TYPES = array( 'post', 'page', 'hub_business', 'hub_offer', 'hub_event', 'hub_class', 'hub_payment', 'hub_claim' );

/* ------------------------------------------------------------------ styles */

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	$list = 'edit.php' === $hook && $screen && in_array( $screen->post_type, HUB_ADMIN_LIST_TYPES, true );
	$more = 'users.php' === $hook || ( 'edit-tags.php' === $hook && $screen && in_array( $screen->taxonomy, array( 'hub_category', 'hub_area' ), true ) );
	if ( $list || $more ) {
		$file = get_template_directory() . '/admin/hub-admin-lists.css';
		wp_enqueue_style( 'hub-admin-lists', get_template_directory_uri() . '/admin/hub-admin-lists.css', array(), (string) filemtime( $file ) );
	}
} );

/* ------------------------------------------------------------------ small pieces of markup */

/** A coloured label. $tone: green | amber | grey | blue | purple | red | gold */
function hub_admin_pill( $text, $tone = 'grey', $title = '' ) {
	return '<span class="hub-pill hub-pill--' . esc_attr( $tone ) . '"' . ( '' !== $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>' . esc_html( $text ) . '</span>';
}

/** A second, quieter line under a cell's main text. */
function hub_admin_sub( $text ) {
	return '' !== (string) $text ? '<span class="hub-cell-sub">' . esc_html( $text ) . '</span>' : '';
}

/** "–" for an empty cell. */
function hub_admin_none() {
	return '<span class="hub-cell-none" aria-label="None">&ndash;</span>';
}

/** The post's own status as a pill: Live, In review, Draft, Scheduled, Private. */
function hub_admin_status_pill( WP_Post $post ) {
	$map = array(
		'publish' => array( 'Live', 'green' ),
		'pending' => array( 'In review', 'amber' ),
		'draft'   => array( 'Draft', 'grey' ),
		'future'  => array( 'Scheduled', 'blue' ),
		'private' => array( 'Private', 'grey' ),
		'trash'   => array( 'In the Trash', 'red' ),
	);
	$s = isset( $map[ $post->post_status ] ) ? $map[ $post->post_status ] : array( ucfirst( $post->post_status ), 'grey' );
	return hub_admin_pill( $s[0], $s[1] );
}

/** "Sent from the website by …" for posts added through the Hub's account pages or Add a business. */
function hub_admin_sent_by( $post_id ) {
	$user = (int) get_post_meta( $post_id, '_hub_added_by', true );
	$u    = $user ? get_userdata( $user ) : null;
	return $u ? '<span class="hub-cell-sub">Sent by <a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '" title="' . esc_attr( $u->user_email ) . '">' . esc_html( $u->display_name ) . '</a></span>' : '';
}

/** A date as "Sat 13 Dec 2026" (dates are stored Y-m-d). */
function hub_admin_date( $ymd, $format = 'D j M Y' ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $ymd );
	return $d ? $d->format( $format ) : '';
}

/** Days from the Hub's "today" to a Y-m-d date (negative when it's passed), or null. */
function hub_admin_days_until( $ymd ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $ymd, wp_timezone() );
	return $d ? (int) hub_today()->diff( $d )->format( '%r%a' ) : null;
}

const HUB_ADMIN_PLAN_PILLS = array(
	'unclaimed' => array( 'Unclaimed', 'grey' ),
	'basic'     => array( 'Basic', 'blue' ),
	'enhanced'  => array( 'Featured', 'gold' ),
	'premium'   => array( 'Featured', 'gold' ),
	'partner'   => array( 'Local Partner', 'purple' ),
);

/** A business: its record, plan and names, for the cells (cached per request). */
function hub_admin_business( $post_id ) {
	static $cache = array();
	if ( ! isset( $cache[ $post_id ] ) ) {
		$post = get_post( $post_id );
		if ( ! $post || 'hub_business' !== $post->post_type ) {
			return $cache[ $post_id ] = null;
		}
		$r      = hub_businesses_db_record( $post );
		$cats   = get_the_terms( $post->ID, 'hub_category' );
		$areas  = get_the_terms( $post->ID, 'hub_area' );
		$claim  = ! empty( $r['claimed'] );
		$cache[ $post_id ] = array(
			'post'     => $post,
			'record'   => $r,
			'plan'     => hub_businesses_db_meta( $post )['plan'],
			'claimed'  => $claim,
			'checked'  => ! empty( $r['checked'] ),
			'category' => $cats && ! is_wp_error( $cats ) ? $cats[0] : null,
			'more_cats' => $cats && ! is_wp_error( $cats ) ? count( $cats ) - 1 : 0,
			'area'     => $areas && ! is_wp_error( $areas ) ? $areas[0] : null,
		);
	}
	return $cache[ $post_id ];
}

/** "Business name" linked to its edit screen, with its plan, for offers and classes. */
function hub_admin_business_link( $business_id ) {
	$b = $business_id ? hub_admin_business( (int) $business_id ) : null;
	if ( ! $b ) {
		return hub_admin_none();
	}
	$p = HUB_ADMIN_PLAN_PILLS[ isset( HUB_ADMIN_PLAN_PILLS[ $b['plan'] ] ) ? $b['plan'] : 'unclaimed' ];
	return '<a class="hub-cell-main" href="' . esc_url( get_edit_post_link( $b['post']->ID ) ) . '">' . esc_html( get_the_title( $b['post'] ) ) . '</a><span class="hub-cell-row">' . hub_admin_pill( $p[0], $p[1] ) . '</span>';
}

/* ------------------------------------------------------------------ Businesses */

add_filter( 'manage_hub_business_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Business',
		'hub_plan'     => 'Plan',
		'hub_bstatus'  => 'Status',
		'hub_owner'    => 'Managed by',
		'hub_where'    => 'Category and area',
		'hub_content'  => 'Offers and classes',
		'date'         => $cols['date'],
	);
} );
add_action( 'manage_hub_business_posts_custom_column', function ( $col, $post_id ) {
	$b = hub_admin_business( $post_id );
	if ( ! $b ) {
		return;
	}
	$r = $b['record'];
	switch ( $col ) {
		case 'hub_plan':
			$p = HUB_ADMIN_PLAN_PILLS[ isset( HUB_ADMIN_PLAN_PILLS[ $b['plan'] ] ) ? $b['plan'] : 'unclaimed' ];
			echo hub_admin_pill( $p[0], $p[1] ); // phpcs:ignore WordPress.Security.EscapeOutput
			$sub = hub_billing_db_subscription( $post_id );
			if ( ! empty( $sub['status'] ) && in_array( $sub['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
				echo hub_admin_sub( 'past_due' === $sub['status'] ? 'Payment failed' : 'Paying by card, ' . ( 'annual' === $sub['interval'] ? 'yearly' : 'monthly' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_bstatus':
			$s = $b['checked'] ? array( 'Checked by us', 'blue', 'Checked by Harrow Online with the business' ) : ( $b['claimed'] ? array( 'Verified', 'green', 'Claimed and kept up to date by the business' ) : array( 'Imported', 'grey', 'From open map data, not checked yet' ) );
			$since = $b['claimed'] && ! empty( $r['claimed']['claimed'] ) ? ' since ' . $r['claimed']['claimed'] : '';
			echo '<span class="hub-cell-row">' . hub_admin_pill( $s[0], $s[1], $s[2] . $since ) . ( 'publish' !== $b['post']->post_status ? hub_admin_status_pill( $b['post'] ) : '' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_owner':
			$owner = hub_account_owner_id( $post_id );
			$u     = $owner ? get_userdata( $owner ) : null;
			echo $u ? '<a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '" title="' . esc_attr( $u->user_email ) . '">' . esc_html( $u->display_name ) . '</a>' : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			echo hub_admin_sent_by( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_where':
			$cat = $b['category'] ? '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'hub_business', 'hub_category' => $b['category']->slug ), admin_url( 'edit.php' ) ) ) . '">' . esc_html( html_entity_decode( $b['category']->name, ENT_QUOTES ) ) . '</a>' . ( $b['more_cats'] > 0 ? ' <span class="hub-cell-sub hub-inline">+' . (int) $b['more_cats'] . '</span>' : '' ) : hub_admin_none();
			echo $cat; // phpcs:ignore WordPress.Security.EscapeOutput
			$where = trim( ( $b['area'] ? html_entity_decode( $b['area']->name, ENT_QUOTES ) : '' ) . ( ! empty( $r['postcode'] ) ? ' · ' . $r['postcode'] : '' ), ' ·' );
			echo '' !== $where ? '<span class="hub-cell-sub hub-cell-where"><span class="dashicons dashicons-location" aria-hidden="true"></span>' . esc_html( $where ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_content':
			$offers  = count( get_posts( array( 'post_type' => 'hub_offer', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_hub_business', 'meta_value' => $post_id ) ) );
			$classes = count( get_posts( array( 'post_type' => 'hub_class', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'class_business', 'meta_value' => $post_id ) ) );
			$bits    = array();
			if ( $offers ) {
				$bits[] = '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'hub_offer', 'hub_biz' => $post_id ), admin_url( 'edit.php' ) ) ) . '"><span class="dashicons dashicons-tag" aria-hidden="true"></span>' . $offers . ' offer' . ( 1 === $offers ? '' : 's' ) . '</a>';
			}
			if ( $classes ) {
				$bits[] = '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'hub_class', 'hub_biz' => $post_id ), admin_url( 'edit.php' ) ) ) . '"><span class="dashicons dashicons-update" aria-hidden="true"></span>' . $classes . ' class' . ( 1 === $classes ? '' : 'es' ) . '</a>';
			}
			echo $bits ? '<span class="hub-cell-line">' . implode( '</span><span class="hub-cell-line">', $bits ) . '</span>' : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-hub_business_sortable_columns', function ( $cols ) {
	$cols['hub_plan'] = 'hub_plan';
	return $cols;
} );

/* ------------------------------------------------------------------ Offers */

add_filter( 'manage_hub_offer_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Offer',
		'hub_obiz'     => 'Business',
		'hub_runs'     => 'Runs until',
		'hub_claim'    => 'How to claim',
		'hub_featured' => '<span class="dashicons dashicons-star-filled" title="Featured"></span><span class="screen-reader-text">Featured</span>',
		'hub_state'    => 'Status',
		'date'         => $cols['date'],
	);
} );
add_action( 'manage_hub_offer_posts_custom_column', function ( $col, $post_id ) {
	$post = get_post( $post_id );
	$r    = hub_offers_db_record( $post );
	switch ( $col ) {
		case 'hub_obiz':
			echo hub_admin_business_link( hub_offers_db_business_id( $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_runs':
			$end = isset( $r['end'] ) ? (string) $r['end'] : '';
			if ( '' === $end ) {
				echo hub_admin_pill( 'No end date', 'grey' ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			}
			$days = hub_admin_days_until( $end );
			echo '<strong>' . esc_html( hub_admin_date( $end ) ) . '</strong><br>';
			if ( null !== $days && $days < 0 ) {
				echo hub_admin_pill( 'Ended', 'grey' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} elseif ( 0 === $days ) {
				echo hub_admin_pill( 'Ends today', 'red' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo hub_admin_pill( $days . ' day' . ( 1 === $days ? '' : 's' ) . ' left', $days <= 7 ? 'amber' : 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_claim':
			$code = isset( $r['code'] ) ? trim( (string) $r['code'] ) : '';
			echo '<span class="hub-cell-row">' . ( '' !== $code ? '<code class="hub-code">' . esc_html( $code ) . '</code>' : '<span class="hub-cell-quiet">No code</span>' );
			if ( ! empty( $r['sub'] ) ) {
				echo hub_admin_pill( 'Subscribers only', 'purple', 'Readers confirm their newsletter email to see it' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</span>';
			echo hub_admin_sub( isset( $r['redeem'] ) ? html_entity_decode( (string) $r['redeem'], ENT_QUOTES ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_featured':
			echo ! empty( $r['featured'] ) || '1' === (string) get_post_meta( $post_id, '_hub_featured', true ) ? '<span class="dashicons dashicons-star-filled hub-star" title="Featured"></span>' : '<span class="hub-cell-none">&ndash;</span>';
			break;
		case 'hub_state':
			echo '<span class="hub-cell-row">' . hub_admin_status_pill( $post ) . '</span>' . hub_admin_sent_by( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-hub_offer_sortable_columns', function ( $cols ) {
	$cols['hub_runs'] = 'hub_end';
	return $cols;
} );

/* ------------------------------------------------------------------ Events */

add_filter( 'manage_hub_event_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Event',
		'hub_when'     => 'When',
		'hub_ecat'     => 'Category',
		'hub_venue'    => 'Where',
		'hub_org'      => 'Organiser',
		'hub_price'    => 'Price and ages',
		'hub_featured' => '<span class="dashicons dashicons-star-filled" title="Featured"></span><span class="screen-reader-text">Featured</span>',
		'hub_state'    => 'Status',
	);
} );
add_action( 'manage_hub_event_posts_custom_column', function ( $col, $post_id ) {
	$post = get_post( $post_id );
	$r    = hub_events_db_record( $post );
	switch ( $col ) {
		case 'hub_when':
			$day  = isset( $r['date'] ) ? (string) $r['date'] : ( isset( $r['start'] ) ? substr( (string) $r['start'], 0, 10 ) : '' );
			$days = hub_admin_days_until( $day );
			echo '<strong>' . esc_html( '' !== $day ? hub_admin_date( $day ) : 'No date' ) . '</strong>';
			echo hub_admin_sub( isset( $r['time'] ) ? html_entity_decode( (string) $r['time'], ENT_QUOTES ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( null !== $days ) {
				echo $days < 0 ? hub_admin_pill( 'Past', 'grey' ) : ( 0 === $days ? hub_admin_pill( 'Today', 'red' ) : hub_admin_pill( 7 >= $days ? 'This week' : 'Upcoming', 7 >= $days ? 'amber' : 'green' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_ecat':
			echo ! empty( $r['cat'] ) ? esc_html( html_entity_decode( (string) $r['cat'], ENT_QUOTES ) ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_venue':
			echo ! empty( $r['venue'] ) ? esc_html( html_entity_decode( (string) $r['venue'], ENT_QUOTES ) ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			echo hub_admin_sub( ! empty( $r['area'] ) ? html_entity_decode( (string) $r['area'], ENT_QUOTES ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_org':
			$org = ! empty( $r['org'] ) ? html_entity_decode( (string) $r['org'], ENT_QUOTES ) : '';
			$biz = '' !== $org ? get_posts( array( 'post_type' => 'hub_business', 'post_status' => 'publish', 'title' => $org, 'numberposts' => 1, 'fields' => 'ids' ) ) : array();
			if ( $biz ) {
				echo hub_admin_business_link( (int) $biz[0] ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '' !== $org ? esc_html( $org ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_price':
			echo esc_html( ! empty( $r['price'] ) ? html_entity_decode( (string) $r['price'], ENT_QUOTES ) : 'Free' );
			echo hub_admin_sub( ! empty( $r['age'] ) ? html_entity_decode( (string) $r['age'], ENT_QUOTES ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_featured':
			echo ! empty( $r['featured'] ) ? '<span class="dashicons dashicons-star-filled hub-star" title="Featured"></span>' : '<span class="hub-cell-none">&ndash;</span>';
			break;
		case 'hub_state':
			$pay = '';
			if ( get_post_meta( $post_id, '_hub_needs_pay', true ) ) {
				$pay = get_post_meta( $post_id, '_hub_paid', true ) ? hub_admin_pill( '£15 paid', 'green' ) : hub_admin_pill( '£15 not paid', 'red', 'Basic plan: one-off event listing' );
			}
			echo '<span class="hub-cell-row">' . hub_admin_status_pill( $post ) . $pay . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo hub_admin_sent_by( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( ! empty( $r['sample'] ) ) {
				echo hub_admin_sub( 'Sample for the preview' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-hub_event_sortable_columns', function ( $cols ) {
	$cols['hub_when'] = 'hub_start';
	return $cols;
} );

/* ------------------------------------------------------------------ Classes */

add_filter( 'manage_hub_class_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Class',
		'hub_cwhen'    => 'Every week',
		'hub_ctype'    => 'Type',
		'hub_provider' => 'Run by',
		'hub_venue'    => 'Where',
		'hub_price'    => 'Price and ages',
		'hub_featured' => '<span class="dashicons dashicons-star-filled" title="Featured"></span><span class="screen-reader-text">Featured</span>',
		'hub_state'    => 'Status',
	);
} );
add_action( 'manage_hub_class_posts_custom_column', function ( $col, $post_id ) {
	$post = get_post( $post_id );
	$r    = hub_events_db_record( $post );
	$real = ! empty( $r['real'] ) && is_array( $r['real'] ) ? $r['real'] : array();
	switch ( $col ) {
		case 'hub_cwhen':
			$days = array( 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays', 'Sundays' );
			$day  = isset( $r['day'] ) && '' !== (string) $r['day'] && null !== $r['day'] ? (int) $r['day'] : null;
			echo '<strong>' . esc_html( null !== $day && isset( $days[ $day ] ) ? $days[ $day ] : 'Day not set' ) . '</strong>';
			echo hub_admin_sub( ! empty( $r['time'] ) ? html_entity_decode( (string) $r['time'], ENT_QUOTES ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( isset( $r['page'] ) && ! $r['page'] ) {
				echo hub_admin_pill( 'Card only, no page', 'grey' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_ctype':
			echo ! empty( $r['type'] ) ? esc_html( html_entity_decode( (string) $r['type'], ENT_QUOTES ) ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_provider':
			$biz = (int) get_post_meta( $post_id, 'class_business', true );
			if ( $biz ) {
				echo hub_admin_business_link( $biz ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo ! empty( $r['provider'] ) ? esc_html( html_entity_decode( (string) $r['provider'], ENT_QUOTES ) ) . hub_admin_sub( 'Organiser' ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_venue':
			echo ! empty( $r['venue'] ) ? esc_html( html_entity_decode( (string) $r['venue'], ENT_QUOTES ) ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			echo hub_admin_sub( trim( ( ! empty( $r['area'] ) ? html_entity_decode( (string) $r['area'], ENT_QUOTES ) : '' ) . ( ! empty( $real['postcode'] ) ? ' · ' . $real['postcode'] : '' ), ' ·' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_price':
			echo esc_html( ! empty( $r['price'] ) ? html_entity_decode( (string) $r['price'], ENT_QUOTES ) : 'Free' );
			echo hub_admin_sub( ! empty( $r['age'] ) ? html_entity_decode( (string) $r['age'], ENT_QUOTES ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_featured':
			echo ! empty( $r['featured'] ) ? '<span class="dashicons dashicons-star-filled hub-star" title="Featured"></span>' : '<span class="hub-cell-none">&ndash;</span>';
			break;
		case 'hub_state':
			echo '<span class="hub-cell-row">' . hub_admin_status_pill( $post ) . '</span>' . hub_admin_sent_by( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( empty( $real ) ) {
				echo hub_admin_sub( 'Sample for the preview' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-hub_class_sortable_columns', function ( $cols ) {
	$cols['hub_cwhen'] = 'hub_day';
	return $cols;
} );

/* ------------------------------------------------------------------ Payments (written by the Stripe webhook only) */

add_filter( 'manage_hub_payment_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Payment',
		'hub_amount'   => 'Amount',
		'hub_pstatus'  => 'Status',
		'hub_pbiz'     => 'Business',
		'hub_account'  => 'Account',
		'date'         => 'Date',
	);
} );
add_action( 'manage_hub_payment_posts_custom_column', function ( $col, $post_id ) {
	$p = hub_billing_db_payment( $post_id );
	if ( ! $p ) {
		return;
	}
	switch ( $col ) {
		case 'hub_amount':
			echo '<strong class="hub-amount">' . esc_html( ! empty( $p['amount'] ) ? hub_billing_rule_money( (int) $p['amount'] ) : '£0' ) . '</strong>';
			$what = 'Invoice' === $p['kind'] ? 'Plan, invoice ' . $p['item'] : ( isset( HUB_BILLING_ITEMS[ $p['item'] ] ) ? HUB_BILLING_ITEMS[ $p['item'] ]['name'] : (string) $p['item'] );
			echo hub_admin_sub( html_entity_decode( $what, ENT_QUOTES ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_pstatus':
			echo 'paid' === $p['status'] ? hub_admin_pill( 'Paid', 'green' ) : hub_admin_pill( ucfirst( (string) $p['status'] ), 'payment failed' === $p['status'] ? 'red' : 'grey' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo hub_admin_sub( 'Invoice' === $p['kind'] ? 'Card, by subscription' : 'Card, one-off' ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_pbiz':
			echo ! empty( $p['business'] ) && hub_admin_business( (int) $p['business'] ) ? hub_admin_business_link( (int) $p['business'] ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_account':
			$u = ! empty( $p['user'] ) ? get_userdata( (int) $p['user'] ) : null;
			echo $u ? '<a class="hub-cell-main" href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->display_name ) . '</a>' . hub_admin_sub( $u->user_email ) : hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
	}
}, 10, 2 );

/** Payments and claims are always private (staff only), so "— Private" after each title says nothing. */
add_filter( 'display_post_states', function ( $states, $post ) {
	if ( in_array( $post->post_type, array( 'hub_payment', 'hub_claim' ), true ) ) {
		unset( $states['private'] );
	}
	return $states;
}, 10, 2 );

/* ------------------------------------------------------------------ Claims */

add_filter( 'manage_hub_claim_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Claim',
		'hub_cstatus'  => 'Status',
		'hub_cbiz'     => 'Business',
		'hub_account'  => 'Account',
		'hub_proof'    => 'Proof',
		'date'         => 'Sent',
	);
} );
add_action( 'manage_hub_claim_posts_custom_column', function ( $col, $post_id ) {
	$c = hub_claim_for_staff( $post_id );
	if ( ! is_array( $c ) || empty( $c['status'] ) ) {
		return;
	}
	switch ( $col ) {
		case 'hub_cstatus':
			$tone = array( 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red' );
			echo hub_admin_pill( $c['status_label'], isset( $tone[ $c['status'] ] ) ? $tone[ $c['status'] ] : 'grey' ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( '' !== $c['decision_note'] ) {
				echo hub_admin_sub( 'Note: ' . wp_trim_words( $c['decision_note'], 12 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_cbiz':
			if ( ! $c['business'] ) {
				echo hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			}
			echo hub_admin_business_link( (int) $c['business']['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			$where = trim( $c['business']['area'] . ( '' !== (string) $c['business']['postcode'] ? ' · ' . $c['business']['postcode'] : '' ), ' ·' );
			echo '' !== $where ? '<span class="hub-cell-sub hub-cell-where"><span class="dashicons dashicons-location" aria-hidden="true"></span>' . esc_html( $where ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_account':
			if ( ! $c['account'] ) {
				echo hub_admin_none(); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			}
			echo '<a class="hub-cell-main" href="' . esc_url( get_edit_user_link( $c['account']['id'] ) ) . '">' . esc_html( $c['account']['name'] ) . '</a>';
			echo hub_admin_sub( $c['account']['email'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo hub_admin_sub( trim( $c['role_label'] . ( '' !== $c['phone'] ? ' · ' . $c['phone'] : '' ), ' ·' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_proof':
			$e = $c['evidence'];
			echo '<span class="hub-cell-main">' . esc_html( $c['method_label'] ) . '</span>';
			if ( 'email' === $c['method'] ) {
				echo '<span class="hub-cell-row">' . hub_admin_pill( $e['email_verified'] ? 'Link clicked' : 'Link not clicked yet', $e['email_verified'] ? 'green' : 'grey' ) . '</span>' . hub_admin_sub( $e['email'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			} elseif ( 'phone' === $c['method'] ) {
				echo hub_admin_sub( trim( $e['call_number'] . ( '' !== $e['ask_for'] ? ', ask for ' . $e['ask_for'] : '' ), ', ' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<span class="hub-cell-row">' . hub_admin_pill( $e['has_document'] ? 'Document attached' : 'Document missing', $e['has_document'] ? 'blue' : 'red' ) . '</span>' . hub_admin_sub( $e['doc_type'] ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
	}
}, 10, 2 );

/* ------------------------------------------------------------------ Users */

add_filter( 'manage_users_columns', function ( $cols ) {
	return array(
		'cb'          => $cols['cb'],
		'username'    => $cols['username'],
		'hub_email'   => 'Name and email',
		'hub_account' => 'Hub account',
		'hub_plan'    => 'Plan',
		'hub_signup'  => 'Sign-up details',
		'hub_joined'  => 'Joined',
	);
} );
add_filter( 'manage_users_sortable_columns', function ( $cols ) {
	$cols['hub_email']  = 'email';
	$cols['hub_joined'] = 'registered';
	return $cols;
} );
add_filter( 'manage_users_custom_column', function ( $out, $col, $user_id ) {
	$u = get_userdata( $user_id );
	if ( ! $u ) {
		return $out;
	}
	$member = hub_account_is_member( $user_id );
	switch ( $col ) {
		case 'hub_email':
			$name = trim( $u->first_name . ' ' . $u->last_name );
			$name = '' !== $name ? $name : ( $u->display_name !== $u->user_login && $u->display_name !== $u->user_email ? $u->display_name : '' );
			$out  = '' !== $name ? '<strong class="hub-cell-name">' . esc_html( $name ) . '</strong>' : '';
			$out .= '<a class="hub-cell-main" href="mailto:' . esc_attr( $u->user_email ) . '">' . str_replace( '@', '@<wbr>', esc_html( $u->user_email ) ) . '</a>';   // wraps after the @ on narrow screens
			if ( $member ) {
				$ok   = hub_account_email_confirmed( $user_id );
				$out .= '<span class="hub-cell-row">' . hub_admin_pill( $ok ? 'Confirmed' : 'Not confirmed yet', $ok ? 'green' : 'amber', $ok ? 'Clicked the link in the confirmation email' : 'Hasn\'t clicked the link in the confirmation email' ) . '</span>';
			}
			return $out;
		case 'hub_account':
			if ( ! $member ) {
				if ( ! user_can( $u, 'edit_posts' ) ) {
					return hub_admin_none();
				}
				$roles = wp_roles()->get_names();
				$role  = $u->roles ? reset( $u->roles ) : '';
				return '<span class="hub-cell-row">' . hub_admin_pill( 'Harrow Online staff', 'grey' ) . '</span>' . hub_admin_sub( isset( $roles[ $role ] ) ? translate_user_role( $roles[ $role ] ) : '' );
			}
			$a    = hub_account_for_user( $user_id );
			$type = isset( $a['type'], HUB_ACCOUNT_TYPES[ $a['type'] ] ) ? $a['type'] : '';
			$out  = '<span class="hub-cell-row">' . hub_admin_pill( '' !== $type ? HUB_ACCOUNT_TYPES[ $type ] : 'Account', 'organiser' === $type ? 'purple' : 'blue' ) . '</span>';
			$biz  = hub_account_business_id( $user_id );
			$b    = $biz ? hub_admin_business( $biz ) : null;
			if ( $b ) {
				$out .= '<span class="hub-cell-sub"><span class="dashicons dashicons-store" aria-hidden="true"></span><a href="' . esc_url( get_edit_post_link( $biz ) ) . '">' . esc_html( get_the_title( $biz ) ) . '</a>' . ( 'publish' !== $b['post']->post_status ? ' (' . esc_html( strtolower( get_post_status_object( $b['post']->post_status )->label ) ) . ')' : '' ) . '</span>';
			} elseif ( 'business' === $type ) {
				$out .= hub_admin_sub( 'No business yet' );
			}
			return $out;
		case 'hub_plan':
			$plan = (string) get_user_meta( $user_id, 'hub_plan', true );
			if ( ! isset( HUB_ACCOUNT_PLANS[ $plan ] ) ) {
				return hub_admin_none();
			}
			$tones = array( 'basic' => 'blue', 'featured' => 'gold', 'partner' => 'purple' );
			$out   = '<span class="hub-cell-row">' . hub_admin_pill( HUB_ACCOUNT_PLANS[ $plan ], $tones[ $plan ] ) . '</span>';
			$biz   = hub_account_business_id( $user_id );
			$sub   = $biz ? hub_billing_db_subscription( $biz ) : array();
			if ( ! empty( $sub['status'] ) && in_array( $sub['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
				$out .= 'past_due' === $sub['status'] ? hub_admin_sub( 'Payment failed' ) : hub_admin_sub( 'Paying by card, ' . ( 'annual' === $sub['interval'] ? 'yearly' : 'monthly' ) );
			}
			return $out;
		case 'hub_signup':
			if ( ! $member ) {
				return hub_admin_none();
			}
			$d     = hub_account_admin_details( $user_id );
			$lines = '' !== $d['organisation'] ? '<span class="hub-cell-main">' . esc_html( $d['organisation'] ) . '</span>' : '';
			if ( '' !== $d['area_name'] ) {
				$lines .= '<span class="hub-cell-sub hub-cell-where"><span class="dashicons dashicons-location" aria-hidden="true"></span>' . esc_html( $d['area_name'] ) . '</span>';
			}
			if ( $d['edition_labels'] ) {
				$lines .= hub_admin_sub( 'Newsletters: ' . implode( ', ', $d['edition_labels'] ) );
			}
			return '' !== $lines ? $lines : hub_admin_none();
		case 'hub_joined':
			$t = strtotime( $u->user_registered . ' UTC' );
			return '<span class="hub-nowrap">' . esc_html( wp_date( 'j M Y', $t ) ) . '</span>' . hub_admin_sub( human_time_diff( $t, time() ) . ' ago' );
	}
	return $out;
}, 10, 3 );

/* ------------------------------------------------------------------ Categories and Areas (of businesses) */

/** How many published businesses in a term, optionally only those whose plan is in $tiers. Cached per request. */
function hub_admin_term_businesses( $taxonomy, $term_id, array $tiers = array() ) {
	$args = array(
		'post_type'      => 'hub_business',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => false,
		'tax_query'      => array( array( 'taxonomy' => $taxonomy, 'terms' => (int) $term_id ) ),
	);
	if ( $tiers ) {
		$args['meta_query'] = array( array( 'key' => '_hub_tier', 'value' => $tiers, 'compare' => 'IN' ) );
	}
	return ( new WP_Query( $args ) )->found_posts;
}

foreach ( array( 'hub_category', 'hub_area' ) as $hub_tax ) {
	add_filter( "manage_edit-{$hub_tax}_columns", function ( $cols ) use ( $hub_tax ) {
		$cols += array( 'cb' => '<input type="checkbox" />', 'name' => 'Name', 'slug' => 'Slug' );   // WordPress sometimes asks with no columns yet
		$new   = array(
			'cb'          => $cols['cb'],
			'name'        => $cols['name'],
			'slug'        => $cols['slug'],
			'posts'       => 'Businesses',
			'hub_claimed' => 'Claimed',
			'hub_paying'  => 'Paid plan',
		);
		if ( 'hub_area' === $hub_tax ) {
			$new['hub_people'] = 'Accounts';
		}
		return $new;
	} );
	add_filter( "manage_{$hub_tax}_custom_column", function ( $out, $col, $term_id ) use ( $hub_tax ) {
		$term = get_term( $term_id, $hub_tax );
		if ( ! $term || is_wp_error( $term ) ) {
			return $out;
		}
		$list = add_query_arg( array( 'post_type' => 'hub_business', $hub_tax => $term->slug ), admin_url( 'edit.php' ) );
		switch ( $col ) {
			case 'hub_claimed':
				$n = hub_admin_term_businesses( $hub_tax, $term_id, array( 'basic', 'enhanced', 'premium', 'partner' ) );
				return $n ? '<a href="' . esc_url( $list ) . '">' . (int) $n . '</a>' . hub_admin_sub( round( 100 * $n / max( 1, (int) $term->count ) ) . '% of them' ) : hub_admin_none();
			case 'hub_paying':
				$n = hub_admin_term_businesses( $hub_tax, $term_id, array( 'enhanced', 'premium', 'partner' ) );
				return $n ? (string) (int) $n : hub_admin_none();
			case 'hub_people':
				$n = count( get_users( array( 'meta_key' => 'hub_area', 'meta_value' => $term->slug, 'fields' => 'ID' ) ) );
				return $n ? (int) $n . hub_admin_sub( 'chose it at sign-up' ) : hub_admin_none();
		}
		return $out;
	}, 10, 3 );
}
unset( $hub_tax );

/* ------------------------------------------------------------------ Pages */

add_filter( 'manage_page_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Page',
		'hub_path'     => 'Address on the Hub',
		'hub_template' => 'Template',
		'hub_text'     => 'Editable text',
		'date'         => $cols['date'],
	);
} );
add_action( 'manage_page_posts_custom_column', function ( $col, $post_id ) {
	static $map = null;
	if ( null === $map ) {
		$map = (array) require get_template_directory() . '/inc/page-map.php';
	}
	$is_home = function_exists( 'hub_home_page_id' ) && (int) $post_id === (int) hub_home_page_id();
	$path    = $is_home ? '' : get_page_uri( $post_id );
	switch ( $col ) {
		case 'hub_path':
			$url = $is_home ? hub_url( '' ) : hub_url( $path . '/' );
			echo '<a class="hub-path" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">/hub/' . esc_html( '' !== $path ? $path . '/' : '' ) . '</a>';
			if ( $is_home ) {
				echo ' ' . hub_admin_pill( 'Homepage', 'purple' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( 0 === strpos( $path, 'account' ) ) {
				echo hub_admin_sub( 'Signed-in accounts only' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;
		case 'hub_template':
			$tpl = isset( $map[ $is_home ? '' : $path ]['tpl'] ) ? $map[ $is_home ? '' : $path ]['tpl'] : '';
			echo '' !== $tpl ? '<code class="hub-code hub-code--quiet">' . esc_html( $tpl ) . '</code>' : hub_admin_pill( 'No Hub template', 'grey', 'Uses the default page layout' ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;
		case 'hub_text':
			if ( $is_home ) {
				echo hub_admin_pill( 'Homepage box', 'green', 'Edit the homepage text, links and sections' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} elseif ( function_exists( 'hub_pages_for_post' ) && hub_pages_for_post( $post_id ) ) {
				echo hub_admin_pill( 'Page text box', 'green', 'Edit this page\'s headings, text and SEO title' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<span class="hub-cell-sub hub-inline">From the theme</span>';
			}
			break;
	}
}, 10, 2 );

/* ------------------------------------------------------------------ Posts: not used by the Hub */

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( $screen && 'edit-post' === $screen->id ) {
		echo '<div class="notice notice-info hub-posts-note"><p><strong>The Hub doesn&rsquo;t use Posts.</strong> News articles are on harrowonline.org. Hub content lives under <a href="' . esc_url( admin_url( 'edit.php?post_type=hub_business' ) ) . '">Businesses</a>, <a href="' . esc_url( admin_url( 'edit.php?post_type=hub_offer' ) ) . '">Offers</a>, <a href="' . esc_url( admin_url( 'edit.php?post_type=hub_event' ) ) . '">Events</a> and <a href="' . esc_url( admin_url( 'edit.php?post_type=hub_class' ) ) . '">Classes</a>.</p></div>';
	}
} );

/* ------------------------------------------------------------------ filters above the lists, and sorting */

add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( 'hub_business' === $post_type ) {
		$plan = isset( $_GET['hub_plan'] ) ? sanitize_key( $_GET['hub_plan'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<label class="screen-reader-text" for="hub-filter-plan">Plan</label><select name="hub_plan" id="hub-filter-plan"><option value="">All plans</option>';
		foreach ( array( 'unclaimed' => 'Unclaimed', 'basic' => 'Basic', 'featured' => 'Featured', 'partner' => 'Local Partner' ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $plan, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
		foreach ( array( 'hub_category' => 'All categories', 'hub_area' => 'All areas' ) as $tax => $all ) {
			wp_dropdown_categories( array(
				'taxonomy'        => $tax,
				'name'            => $tax,
				'value_field'     => 'slug',
				'show_option_all' => $all,
				'selected'        => isset( $_GET[ $tax ] ) ? sanitize_title( wp_unslash( $_GET[ $tax ] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
				'hide_empty'      => true,
				'orderby'         => 'name',
				'hierarchical'    => true,
			) );
		}
	}
	if ( in_array( $post_type, array( 'hub_offer', 'hub_class', 'hub_payment', 'hub_claim' ), true ) && ! empty( $_GET['hub_biz'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$id = (int) $_GET['hub_biz']; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<input type="hidden" name="hub_biz" value="' . (int) $id . '"><span class="hub-filter-note">Showing <strong>' . esc_html( get_the_title( $id ) ) . '</strong> only &middot; <a href="' . esc_url( admin_url( 'edit.php?post_type=' . $post_type ) ) . '">Show all</a></span>';
	}
	$states = array(
		'hub_claim'   => array( '' => 'All statuses' ) + HUB_CLAIMS_STATUSES,
		'hub_payment' => array( '' => 'Paid and failed', 'paid' => 'Paid', 'failed' => 'Payment failed' ),
		'hub_event'   => array( '' => 'Paid and unpaid', 'unpaid' => '£15 not paid' ),
	);
	if ( isset( $states[ $post_type ] ) ) {
		$state = isset( $_GET['hub_state'] ) ? sanitize_key( $_GET['hub_state'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<label class="screen-reader-text" for="hub-filter-state">Status</label><select name="hub_state" id="hub-filter-state">';
		foreach ( $states[ $post_type ] as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $state, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	}
	if ( in_array( $post_type, array( 'hub_offer', 'hub_event' ), true ) ) {
		$when = isset( $_GET['hub_when'] ) ? sanitize_key( $_GET['hub_when'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<label class="screen-reader-text" for="hub-filter-when">When</label><select name="hub_when" id="hub-filter-when">';
		foreach ( array( '' => 'Past and upcoming', 'current' => 'hub_offer' === $post_type ? 'Running now' : 'Upcoming', 'past' => 'hub_offer' === $post_type ? 'Ended' : 'Past' ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $when, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	}
} );

add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) {
		return;
	}
	global $pagenow;
	if ( 'edit.php' !== $pagenow ) {
		return;
	}
	$type  = $q->get( 'post_type' );
	$meta  = (array) $q->get( 'meta_query' );
	$today = hub_today()->format( 'Y-m-d' );
	if ( 'hub_business' === $type ) {
		$plan  = isset( $_GET['hub_plan'] ) ? sanitize_key( $_GET['hub_plan'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$tiers = array( 'unclaimed' => array( 'unclaimed' ), 'basic' => array( 'basic' ), 'featured' => array( 'enhanced', 'premium' ), 'partner' => array( 'partner' ) );
		if ( isset( $tiers[ $plan ] ) ) {
			$meta[] = array( 'key' => '_hub_tier', 'value' => $tiers[ $plan ], 'compare' => 'IN' );
		}
		if ( 'hub_plan' === $q->get( 'orderby' ) ) {
			$q->set( 'meta_key', '_hub_rank' );
			$q->set( 'orderby', 'meta_value_num' );
		}
	}
	$biz_keys = array( 'hub_offer' => '_hub_business', 'hub_class' => 'class_business', 'hub_payment' => '_hub_business', 'hub_claim' => '_hub_claim_business' );
	if ( isset( $biz_keys[ $type ] ) && ! empty( $_GET['hub_biz'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$meta[] = array( 'key' => $biz_keys[ $type ], 'value' => (int) $_GET['hub_biz'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	$state = isset( $_GET['hub_state'] ) ? sanitize_key( $_GET['hub_state'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'hub_claim' === $type && isset( HUB_CLAIMS_STATUSES[ $state ] ) ) {
		$meta[] = array( 'key' => '_hub_claim_status', 'value' => $state );
	}
	if ( 'hub_event' === $type && 'unpaid' === $state ) {
		$meta[] = hub_admin_unpaid_events_query();   // inc/admin/attention.php
	}
	if ( 'hub_payment' === $type && in_array( $state, array( 'paid', 'failed' ), true ) ) {
		// the status is inside the stored payment array: "status";s:4:"paid" or "status";s:14:"payment failed"
		$meta[] = array( 'key' => '_hub_payment', 'value' => 'paid' === $state ? '"status";s:4:"paid"' : '"status";s:14:"payment failed"', 'compare' => 'LIKE' );
	}
	$when = isset( $_GET['hub_when'] ) ? sanitize_key( $_GET['hub_when'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'hub_offer' === $type && in_array( $when, array( 'current', 'past' ), true ) ) {
		$meta[] = array( 'key' => '_hub_end', 'value' => $today, 'compare' => 'current' === $when ? '>=' : '<', 'type' => 'CHAR' );
	}
	if ( 'hub_event' === $type && in_array( $when, array( 'current', 'past' ), true ) ) {
		$meta[] = array( 'key' => '_hub_start', 'value' => $today, 'compare' => 'current' === $when ? '>=' : '<', 'type' => 'CHAR' );
	}
	if ( $meta ) {
		$q->set( 'meta_query', $meta );
	}
	$sorts = array( 'hub_end' => '_hub_end', 'hub_start' => '_hub_start', 'hub_day' => '_hub_day' );
	$by    = (string) $q->get( 'orderby' );
	if ( isset( $sorts[ $by ] ) ) {
		$q->set( 'meta_key', $sorts[ $by ] );
		$q->set( 'orderby', 'hub_day' === $by ? 'meta_value_num' : 'meta_value' );
	}
} );
