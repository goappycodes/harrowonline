<?php
/**
 * wp-admin: the "Offer details", "Event details" and "Class details" boxes (native meta boxes, no plugin).
 * Values are read and saved through the offers and events modules (hub_offers_db_fields / _save_fields,
 * hub_events_db_fields / _save_event_fields / _save_class_fields). An offer's or class's business is the separate
 * "Business" box (link-box.php). The title and the main text box are the offer's, event's or class's title and
 * description.
 */
defined( 'ABSPATH' ) || exit;

/** Area names for the Area selects ("" first). */
function hub_admin_area_choices() {
	$out = array( '' => '— Choose an area —' );
	foreach ( hub_areas_list()['items'] as $a ) {
		$out[ $a['name'] ] = $a['name'];
	}
	return $out;
}

/** [ post type => [ box title, fields (by tab), get values, save ] ] */
function hub_admin_listing_boxes() {
	$days = array( '' => 'No fixed day (no page)' );
	foreach ( HUB_EVENTS_DAY_NAMES as $i => $d ) {
		$days[ (string) $i ] = $d;
	}
	$types = array( '' => '— Choose a type —' );
	foreach ( array_keys( HUB_EVENTS_CLASS_TYPES ) as $t ) {
		$types[ $t ] = $t;
	}
	$cats = array( '' => '— Choose a category —' );
	foreach ( array( 'Community', 'Family', 'Music', 'Food and drink', 'Arts', 'Nightlife' ) as $c ) {
		$cats[ $c ] = $c;
	}
	$areas = hub_admin_area_choices();
	$icon  = 'Shown when there is no photo: calendar, music, family, food, people, star, leaf, flag…';

	return array(
		'hub_offer' => array( 'Offer details', array(
			'offer' => array( 'Offer', array(
				array( 'key' => 'offer_value', 'label' => 'Headline value', 'type' => 'text', 'width' => 34, 'placeholder' => '20% OFF', 'help' => 'Big text on offer tiles. Leave empty to work it out from the title.' ),
				array( 'key' => 'offer_end', 'label' => 'Ends on', 'type' => 'date', 'width' => 33, 'help' => 'Leave empty for no end date. The offer shows as ended the day after.' ),
				array( 'key' => 'offer_position', 'label' => 'Position', 'type' => 'number', 'width' => 33, 'help' => 'Order among this business’s offers (0 first).' ),
				array( 'key' => 'offer_featured', 'label' => 'Featured', 'type' => 'toggle', 'width' => 34, 'help' => 'Shown first, with a Featured badge.' ),
				array( 'key' => 'offer_sub', 'label' => 'Subscribers only', 'type' => 'toggle', 'width' => 33, 'help' => 'The code is only shown after the reader confirms their newsletter email.' ),
				array( 'key' => 'offer_code', 'label' => 'Code', 'type' => 'text', 'width' => 33, 'placeholder' => 'HOCUT20', 'help' => 'Leave empty if no code is needed.' ),
				array( 'key' => 'offer_redeem', 'label' => 'How to redeem', 'type' => 'text', 'placeholder' => HUB_OFFERS_DEFAULT_REDEEM ),
				array( 'key' => 'offer_terms', 'label' => 'Terms', 'type' => 'list', 'flat' => true, 'cols' => array( 't' => array( 'Term' ) ), 'add' => 'Add a term' ),
				array( 'key' => 'offer_icon', 'label' => 'Icon', 'type' => 'text', 'width' => 34, 'help' => 'Optional: news, food, heart, family, wrench, edit…' ),
			) ),
		), 'hub_offers_db_fields', 'hub_offers_db_save_fields' ),

		'hub_event' => array( 'Event details', array(
			'when' => array( 'When and where', array(
				array( 'key' => 'event_date', 'label' => 'Date', 'type' => 'date', 'width' => 34 ),
				array( 'key' => 'event_start', 'label' => 'Starts', 'type' => 'time', 'width' => 33 ),
				array( 'key' => 'event_end', 'label' => 'Ends', 'type' => 'time', 'width' => 33, 'help' => 'Used for “Add to calendar”. An earlier time than the start means after midnight.' ),
				array( 'key' => 'event_time', 'label' => 'Time as shown', 'type' => 'text', 'width' => 50, 'placeholder' => '7pm to 10pm' ),
				array( 'key' => 'event_note', 'label' => 'Note', 'type' => 'text', 'width' => 50, 'placeholder' => 'Lights switched on at 5pm' ),
				array( 'key' => 'event_venue', 'label' => 'Venue', 'type' => 'text', 'width' => 50 ),
				array( 'key' => 'event_area', 'label' => 'Area', 'type' => 'select', 'choices' => $areas, 'other' => true, 'width' => 50 ),
			) ),
			'details' => array( 'Details', array(
				array( 'key' => 'event_cat', 'label' => 'Category', 'type' => 'select', 'choices' => $cats, 'other' => true, 'width' => 34 ),
				array( 'key' => 'event_price', 'label' => 'Price', 'type' => 'text', 'width' => 33, 'placeholder' => 'Free, or £5' ),
				array( 'key' => 'event_age', 'label' => 'Ages', 'type' => 'text', 'width' => 33, 'placeholder' => 'All ages', 'help' => 'For example “All ages”, “Adults”, “Ages 5 to 11” (the age filters read this).' ),
				array( 'key' => 'event_org', 'label' => 'Organiser (business name)', 'type' => 'text', 'width' => 50, 'help' => 'Exactly as the business is named on the Hub, to show the event on its page.' ),
				array( 'key' => 'event_icon', 'label' => 'Icon', 'type' => 'text', 'width' => 50, 'help' => $icon ),
				array( 'key' => 'event_image', 'label' => 'Photo', 'type' => 'image', 'width' => 34 ),
				array( 'key' => 'event_featured', 'label' => 'Featured', 'type' => 'toggle', 'width' => 33 ),
				array( 'key' => 'event_sample', 'label' => 'Sample (made up for the preview)', 'type' => 'toggle', 'width' => 33 ),
			) ),
		), function ( $id ) { return hub_events_db_fields( 'hub_event', $id ); }, 'hub_events_db_save_event_fields' ),

		'hub_class' => array( 'Class details', array(
			'when' => array( 'When and where', array(
				array( 'key' => 'class_day', 'label' => 'Day', 'type' => 'select', 'choices' => $days, 'width' => 34 ),
				array( 'key' => 'class_time', 'label' => 'Time', 'type' => 'text', 'width' => 33, 'placeholder' => '10am to 11am' ),
				array( 'key' => 'class_sort', 'label' => 'Start hour (for sorting)', 'type' => 'number', 'width' => 33, 'placeholder' => '10', 'help' => 'Orders classes on the same day: 10 for 10am, 17.5 for 5.30pm.' ),
				array( 'key' => 'class_page', 'label' => 'Has its own page', 'type' => 'toggle', 'width' => 34, 'help' => 'Off for listings like a club’s teams with no fixed weekly time.' ),
				array( 'key' => 'class_venue', 'label' => 'Venue', 'type' => 'text', 'width' => 33 ),
				array( 'key' => 'class_area', 'label' => 'Area', 'type' => 'select', 'choices' => $areas, 'other' => true, 'width' => 33 ),
			) ),
			'details' => array( 'Details', array(
				array( 'key' => 'class_type', 'label' => 'Type', 'type' => 'select', 'choices' => $types, 'other' => true, 'width' => 34 ),
				array( 'key' => 'class_price', 'label' => 'Price', 'type' => 'text', 'width' => 33, 'placeholder' => '£6' ),
				array( 'key' => 'class_age', 'label' => 'Ages', 'type' => 'text', 'width' => 33, 'placeholder' => 'Ages 4 to 7' ),
				array( 'key' => 'class_provider', 'label' => 'Run by (name shown)', 'type' => 'text', 'width' => 50, 'help' => 'The organiser’s name. Pick the business itself in the “Business” box on the right.' ),
				array( 'key' => 'class_icon', 'label' => 'Icon', 'type' => 'text', 'width' => 25, 'help' => $icon ),
				array( 'key' => 'class_order', 'label' => 'Order', 'type' => 'number', 'width' => 25, 'help' => 'Position in the classes list.' ),
				array( 'key' => 'class_featured', 'label' => 'Featured', 'type' => 'toggle', 'width' => 34 ),
				array( 'key' => 'class_sample', 'label' => 'Sample (made up for the preview)', 'type' => 'toggle', 'width' => 66, 'help' => 'Samples have no photo or address, and say they are made up.' ),
			) ),
			'real' => array( 'Photo and address', array(
				array( 'key' => 'class_image', 'label' => 'Photo or logo', 'type' => 'image', 'width' => 34 ),
				array( 'key' => 'class_image_fit', 'label' => 'Show it as', 'type' => 'select', 'choices' => array( 'photo' => 'A photo (fills the space)', 'logo' => 'A logo (shown whole)' ), 'width' => 33 ),
				array( 'key' => 'class_image_alt', 'label' => 'Image description', 'type' => 'text', 'width' => 33, 'help' => 'For screen readers. Empty: the image’s own “Alternative text”.' ),
				array( 'key' => 'class_street', 'label' => 'Street', 'type' => 'text', 'width' => 67 ),
				array( 'key' => 'class_postcode', 'label' => 'Postcode', 'type' => 'text', 'width' => 33 ),
				array( 'key' => 'class_note', 'label' => 'Note', 'type' => 'text', 'placeholder' => 'Details from the organiser, checked by Harrow Online.' ),
			) ),
			'card' => array( 'Classes page card', array(
				array( 'key' => 'note', 'type' => 'note', 'label' => 'How this class shows on the <strong>Classes and activities</strong> page. Leave a box empty to use the class’s own details.' ),
				array( 'key' => 'class_card_order', 'label' => 'Order on the page', 'type' => 'number', 'width' => 25 ),
				array( 'key' => 'class_card_text', 'label' => 'Short text', 'type' => 'textarea', 'width' => 75 ),
				array( 'key' => 'class_card_when', 'label' => 'When (as shown)', 'type' => 'text', 'width' => 50, 'placeholder' => 'Every Saturday, 10am to 11am' ),
				array( 'key' => 'class_card_price', 'label' => 'Price (as shown)', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'class_card_age', 'label' => 'Ages (as shown)', 'type' => 'text', 'width' => 25 ),
				array( 'key' => 'class_card_provider', 'label' => 'Run by (as shown)', 'type' => 'text', 'width' => 50 ),
			) ),
		), function ( $id ) { return hub_events_db_fields( 'hub_class', $id ); }, 'hub_events_db_save_class_fields' ),
	);
}

add_action( 'add_meta_boxes', function ( $post_type ) {
	$boxes = hub_admin_listing_boxes();
	if ( isset( $boxes[ $post_type ] ) ) {
		add_meta_box( 'hub-listing-details', $boxes[ $post_type ][0], 'hub_admin_listing_box', $post_type, 'normal', 'high' );
	}
} );

/** The box: tabs when there are several. */
function hub_admin_listing_box( WP_Post $post ) {
	$box    = hub_admin_listing_boxes()[ $post->post_type ];
	$values = call_user_func( $box[2], $post->ID );
	// A new class has its own page unless unticked
	if ( 'hub_class' === $post->post_type && 'auto-draft' === $post->post_status ) {
		$values['class_page'] = '1';
	}
	wp_nonce_field( 'hub_listing_save', 'hub_listing_nonce' );
	echo '<div class="hub-box" data-hub-box>';
	if ( count( $box[1] ) > 1 ) {
		echo '<nav class="hub-tabs" role="tablist">';
		$first = true;
		foreach ( $box[1] as $slug => $tab ) {
			echo '<button type="button" role="tab" class="hub-tabs__tab' . ( $first ? ' is-active' : '' ) . '" data-hub-tab="' . esc_attr( $slug ) . '">' . esc_html( $tab[0] ) . '</button>';
			$first = false;
		}
		echo '</nav>';
	}
	$first = true;
	foreach ( $box[1] as $slug => $tab ) {
		echo '<div class="hub-panel" data-hub-panel="' . esc_attr( $slug ) . '"' . ( $first ? '' : ' hidden' ) . '><div class="hub-grid">';
		foreach ( $tab[1] as $f ) {
			hub_admin_field( $f, 'note' === $f['type'] ? '' : $values[ $f['key'] ], 'hub[' . $f['key'] . ']' );
		}
		echo '</div></div>';
		$first = false;
	}
	echo '</div>';
}

foreach ( array( 'hub_offer', 'hub_event', 'hub_class' ) as $hub_type ) {
	add_action( 'save_post_' . $hub_type, function ( $post_id, $post ) {
		if ( ! isset( $_POST['hub_listing_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_listing_nonce'] ), 'hub_listing_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$box    = hub_admin_listing_boxes()[ $post->post_type ];
		$fields = array();
		foreach ( $box[1] as $tab ) {
			$fields = array_merge( $fields, $tab[1] );
		}
		$sent = isset( $_POST['hub'] ) && is_array( $_POST['hub'] ) ? $_POST['hub'] : array(); // phpcs:ignore -- cleaned field by field
		call_user_func( $box[3], $post_id, hub_admin_clean_all( $fields, $sent ) );
	}, 10, 2 );
}
unset( $hub_type );
