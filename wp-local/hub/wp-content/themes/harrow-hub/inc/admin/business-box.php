<?php
/**
 * wp-admin: the "Business details" box on a business (native meta box, no plugin), with tabs:
 * Listing, Contact and location, Opening hours, Logo and photos, Social and news, Offers and classes.
 *
 * Values are read and saved through the businesses module (hub_businesses_db_fields / _save_fields). The "Offers and
 * classes" tab ticks this business's offers and weekly classes: ticking one gives it to this business (an offer or
 * class has one business), unticking takes it off. The same link can be set on the offer or class (link-box.php).
 */
defined( 'ABSPATH' ) || exit;

/** The business fields, by tab. */
function hub_admin_business_fields() {
	$plans = array(
		'unclaimed' => 'Not claimed (imported)',
		'basic'     => 'Basic (free)',
		'enhanced'  => 'Enhanced',
		'premium'   => 'Featured',
		'partner'   => 'Local Partner',
	);
	$only = function ( $what ) {
		return array( 'key' => 'note', 'type' => 'note', 'unclaimed' => true,
			'label' => $what . ' show once the business is claimed. Turn on <strong>Claimed</strong> in the General tab to add them.' );
	};
	return array(
		'listing' => array( 'General', array(
			array( 'key' => 'biz_plan', 'label' => 'Plan', 'type' => 'select', 'choices' => $plans, 'width' => 34, 'help' => 'Applies once the business is claimed.' ),
			array( 'key' => 'biz_claimed', 'label' => 'Claimed', 'type' => 'toggle', 'width' => 33, 'help' => 'Full profile and the “Verified” tick.' ),
			array( 'key' => 'biz_checked', 'label' => 'Checked', 'type' => 'toggle', 'width' => 33, 'help' => 'Shows the “Checked by Harrow Online” tick.' ),
			array( 'key' => 'biz_sub', 'label' => 'Business type', 'type' => 'text', 'help' => 'Next to the category, e.g. “Pizza takeaway”.' ),
			array( 'key' => 'biz_tagline', 'label' => 'Summary', 'type' => 'textarea', 'claimed' => true, 'help' => 'One line under the name. The full description goes in the editor above.' ),
			array( 'key' => 'biz_icon', 'label' => 'Icon', 'type' => 'text', 'placeholder' => 'store', 'width' => 34, 'claimed' => true, 'help' => 'When there’s no photo: store, food, people, scissors…' ),
			array( 'key' => 'biz_claimed_since', 'label' => 'Claimed since', 'type' => 'text', 'placeholder' => 'November 2026', 'width' => 33, 'claimed' => true ),
			array( 'key' => 'biz_botw', 'label' => 'Business of the Week', 'type' => 'toggle', 'width' => 33, 'claimed' => true ),
			array( 'key' => 'biz_verified_note', 'label' => 'Verification note', 'type' => 'text', 'claimed' => true, 'help' => 'e.g. “Details checked by Harrow Online in November 2026.”' ),
		) ),
		'contact' => array( 'Contact', array(
			array( 'key' => 'biz_address', 'label' => 'Address', 'type' => 'text', 'width' => 70 ),
			array( 'key' => 'biz_postcode', 'label' => 'Postcode', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'biz_service_area', 'label' => 'Service area', 'type' => 'text', 'claimed' => true, 'help' => 'For businesses without premises. Hides the address.' ),
			array( 'key' => 'biz_phone', 'label' => 'Phone', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'biz_website', 'label' => 'Website', 'type' => 'url', 'width' => 33 ),
			array( 'key' => 'biz_email', 'label' => 'Email', 'type' => 'email', 'width' => 33 ),
			array( 'key' => 'biz_phones_extra', 'label' => 'Other phones', 'type' => 'list', 'flat' => true, 'cols' => array( 'phone' => array( 'Phone' ) ), 'add' => 'Add phone' ),
			array( 'key' => 'biz_lat', 'label' => 'Latitude', 'type' => 'number', 'width' => 50 ),
			array( 'key' => 'biz_lng', 'label' => 'Longitude', 'type' => 'number', 'width' => 50 ),
			array( 'key' => 'biz_other_locations', 'label' => 'Other locations', 'type' => 'list', 'claimed' => true, 'add' => 'Add location',
				'cols' => array( 'label' => array( 'Label' ), 'name' => array( 'Place' ), 'address' => array( 'Address' ) ) ),
		) ),
		'hours' => array( 'Hours', array(
			$only( 'Hours and timetables' ),
			array( 'key' => 'biz_hours', 'label' => 'Opening hours', 'type' => 'hours', 'claimed' => true, 'help' => 'Leave empty for groups that use a timetable.' ),
			array( 'key' => 'biz_hours_note', 'label' => 'Hours note', 'type' => 'text', 'claimed' => true ),
			array( 'key' => 'biz_status', 'label' => 'Status', 'type' => 'text', 'claimed' => true, 'help' => 'e.g. “By appointment”. Empty: worked out from the hours.' ),
			array( 'key' => 'biz_schedule_title', 'label' => 'Timetable title', 'type' => 'text', 'placeholder' => 'When we meet', 'claimed' => true ),
			array( 'key' => 'biz_schedule', 'label' => 'Timetable', 'type' => 'list', 'claimed' => true, 'add' => 'Add row',
				'cols' => array( 0 => array( 'Day' ), 1 => array( 'What and when' ) ) ),
			array( 'key' => 'biz_schedule_note', 'label' => 'Timetable note', 'type' => 'textarea', 'claimed' => true ),
		) ),
		'photos' => array( 'Media', array(
			$only( 'Logo, cover and gallery' ),
			array( 'key' => 'biz_logo', 'label' => 'Logo', 'type' => 'image', 'width' => 34, 'claimed' => true ),
			array( 'key' => 'biz_cover', 'label' => 'Cover', 'help' => 'Featured and Local Partner plans.', 'type' => 'image', 'width' => 33, 'claimed' => true ),
			array( 'key' => 'biz_cover_bg', 'label' => 'Cover colour', 'type' => 'text', 'width' => 33, 'claimed' => true, 'help' => 'Behind a logo-style cover, e.g. #ffffff.' ),
			array( 'key' => 'biz_gallery', 'label' => 'Gallery', 'type' => 'gallery', 'claimed' => true, 'help' => 'Up to 5 show (paid plans). Set alt text in the media library.' ),
		) ),
		'social' => array( 'Social', array(
			$only( 'Social links and news' ),
			array( 'key' => 'biz_facebook', 'label' => 'Facebook', 'type' => 'url', 'width' => 50, 'claimed' => true ),
			array( 'key' => 'biz_instagram', 'label' => 'Instagram', 'type' => 'url', 'width' => 50, 'claimed' => true ),
			array( 'key' => 'biz_tiktok', 'label' => 'TikTok', 'type' => 'url', 'width' => 34, 'claimed' => true ),
			array( 'key' => 'biz_linkedin', 'label' => 'LinkedIn', 'type' => 'url', 'width' => 33, 'claimed' => true ),
			array( 'key' => 'biz_x', 'label' => 'X', 'type' => 'url', 'width' => 33, 'claimed' => true ),
			array( 'key' => 'biz_news', 'label' => 'News articles', 'type' => 'list', 'claimed' => true, 'add' => 'Add article',
				'help' => 'harrowonline.org links only.',
				'cols' => array( 'title' => array( 'Title' ), 'url' => array( 'URL', 'url' ), 'cat' => array( 'Category' ), 'date' => array( 'Date' ) ) ),
		) ),
	);
}

/** Every field definition in one flat list (for saving). */
function hub_admin_business_field_list() {
	$all = array();
	foreach ( hub_admin_business_fields() as $tab ) {
		$all = array_merge( $all, $tab[1] );
	}
	return $all;
}

/** The fields are edited in these boxes only: keep them out of WordPress's own "Custom Fields" box. */
add_filter( 'is_protected_meta', function ( $protected, $key ) {
	return $protected || 0 === strpos( $key, 'biz_' ) || in_array( $key, array( 'offer_business', 'class_business' ), true );
}, 10, 2 );

add_action( 'add_meta_boxes_hub_business', function () {
	add_meta_box( 'hub-business-details', 'Business details', 'hub_admin_business_box', 'hub_business', 'normal', 'high' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( $screen && in_array( $screen->post_type, array( 'hub_business', 'hub_offer', 'hub_event', 'hub_class' ), true ) && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		hub_admin_enqueue();
	}
} );

/** The box. */
function hub_admin_business_box( WP_Post $post ) {
	wp_nonce_field( 'hub_business_save', 'hub_business_nonce' );
	$values  = hub_businesses_db_fields( $post->ID );
	$tabs    = hub_admin_business_fields();
	$claimed = ! empty( $values['biz_claimed'] );
	if ( '' === (string) $values['biz_plan'] ) {
		$values['biz_plan'] = 'unclaimed';
	}
	echo '<div class="hub-box" data-hub-box data-claimed="' . ( $claimed ? '1' : '0' ) . '">';
	echo '<nav class="hub-tabs" role="tablist">';
	$first = true;
	foreach ( $tabs as $slug => $tab ) {
		echo '<button type="button" role="tab" class="hub-tabs__tab' . ( $first ? ' is-active' : '' ) . '" data-hub-tab="' . esc_attr( $slug ) . '">' . esc_html( $tab[0] ) . '</button>';
		$first = false;
	}
	echo '<button type="button" role="tab" class="hub-tabs__tab" data-hub-tab="links">Offers &amp; classes</button></nav>';

	$first = true;
	foreach ( $tabs as $slug => $tab ) {
		echo '<div class="hub-panel" data-hub-panel="' . esc_attr( $slug ) . '"' . ( $first ? '' : ' hidden' ) . '><div class="hub-grid">';
		foreach ( $tab[1] as $f ) {
			hub_admin_field( $f, hub_admin_is_display( $f ) ? '' : $values[ $f['key'] ], 'hub[' . $f['key'] . ']' );
		}
		echo '</div></div>';
		$first = false;
	}

	echo '<div class="hub-panel" data-hub-panel="links" hidden>';
	hub_admin_business_links( $post->ID, 'offers', 'Offers', 'Tick to link an offer. It moves here from any other business. Shown on paid plans only.', hub_offers_db_admin_list() );
	hub_admin_business_links( $post->ID, 'classes', 'Weekly classes', 'Tick to link a class. Shown on the profile; the class page links back here.', hub_events_db_admin_classes() );
	echo '</div></div>';
}

/** One tick list (offers or classes) on the "Offers and classes" tab. */
function hub_admin_business_links( $business_id, $kind, $title, $help, array $items ) {
	$names = array();
	echo '<div class="hub-f"><p class="hub-f__label">' . esc_html( $title ) . '</p><p class="hub-f__help">' . esc_html( $help ) . '</p>';
	echo '<input type="search" class="hub-links__search" placeholder="Search ' . esc_attr( strtolower( $title ) ) . '…" data-hub-links-search>';
	echo '<input type="hidden" name="hub_links[' . esc_attr( $kind ) . '][sent]" value="1">';
	echo '<ul class="hub-links">';
	usort( $items, function ( $a, $b ) use ( $business_id ) {
		return array( $a['business'] === $business_id ? 0 : 1, html_entity_decode( $a['title'] ) ) <=> array( $b['business'] === $business_id ? 0 : 1, html_entity_decode( $b['title'] ) );
	} );
	foreach ( $items as $it ) {
		$mine  = $it['business'] === (int) $business_id;
		$other = '';
		if ( ! $mine && $it['business'] ) {
			if ( ! isset( $names[ $it['business'] ] ) ) {
				$names[ $it['business'] ] = html_entity_decode( get_the_title( $it['business'] ), ENT_QUOTES, 'UTF-8' );
			}
			$other = '<span class="hub-links__other">now: ' . esc_html( $names[ $it['business'] ] ) . '</span>';
		}
		$status = 'publish' !== $it['status'] ? ' <span class="hub-links__other">(' . esc_html( $it['status'] ) . ')</span>' : '';
		echo '<li><label><input type="checkbox" name="hub_links[' . esc_attr( $kind ) . '][ids][]" value="' . (int) $it['id'] . '"' . checked( $mine, true, false ) . '> '
			. esc_html( html_entity_decode( $it['title'], ENT_QUOTES, 'UTF-8' ) ) . $status . ' ' . $other
			. ' <a href="' . esc_url( get_edit_post_link( $it['id'] ) ) . '" class="hub-links__edit">Edit</a></label></li>';
	}
	echo '</ul></div>';
}

add_action( 'save_post_hub_business', function ( $post_id, $post ) {
	if ( ! isset( $_POST['hub_business_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_business_nonce'] ), 'hub_business_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$sent = isset( $_POST['hub'] ) && is_array( $_POST['hub'] ) ? $_POST['hub'] : array(); // phpcs:ignore -- cleaned field by field
	hub_businesses_db_save_fields( $post_id, hub_admin_clean_all( hub_admin_business_field_list(), $sent ) );
	// The plan may have changed: other modules follow it (e.g. the owner's role, app/setup/accounts.php)
	do_action( 'hub_business_plan_changed', (int) $post_id, hub_businesses_db_meta( get_post( $post_id ) )['plan'] );

	// Offers and classes: ticked ones belong to this business; unticked ones it had are let go
	$links = isset( $_POST['hub_links'] ) && is_array( $_POST['hub_links'] ) ? wp_unslash( $_POST['hub_links'] ) : array(); // phpcs:ignore
	$sets  = array(
		'offers'  => array( 'hub_offers_db_admin_list', 'hub_offers_db_set_business' ),
		'classes' => array( 'hub_events_db_admin_classes', 'hub_events_db_set_class_business' ),
	);
	foreach ( $sets as $kind => $fn ) {
		if ( empty( $links[ $kind ]['sent'] ) ) {
			continue;
		}
		$ticked = array_map( 'absint', isset( $links[ $kind ]['ids'] ) ? (array) $links[ $kind ]['ids'] : array() );
		foreach ( call_user_func( $fn[0] ) as $it ) {
			$want = in_array( $it['id'], $ticked, true );
			if ( $want && $it['business'] !== $post_id ) {
				call_user_func( $fn[1], $it['id'], $post_id );
			} elseif ( ! $want && $it['business'] === $post_id ) {
				call_user_func( $fn[1], $it['id'], 0 );
			}
		}
	}
}, 10, 2 );
