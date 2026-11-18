<?php
/**
 * ACF (Pro) fields, written in PHP so they are part of the theme and the same for everyone:
 *
 *   Hub settings > Newsletter (options page)  the sign-up band above the footer on every page
 *   Business                                   listing, contact, hours, photos, social, news, offers and classes
 *   Offer, Class                               the business it belongs to
 *
 * Offers and classes are linked to their business both ways (ACF bidirectional fields): pick offers and classes on
 * the business, or pick the business on the offer or class, and the other side updates itself.
 * The pages read these fields through hub_business() and hub_class() (inc/business-acf.php) and the offers module
 * (app/modules/offers/data.php).
 */
defined( 'ABSPATH' ) || exit;

/** Options page: Hub settings, with Newsletter as its first sub page. */
add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page( array(
		'page_title' => 'Hub settings',
		'menu_title' => 'Hub settings',
		'menu_slug'  => 'hub-settings',
		'capability' => 'manage_options',
		'icon_url'   => 'dashicons-admin-generic',
		'position'   => 59,
		'redirect'   => true,   // the parent opens its first sub page
	) );
	acf_add_options_sub_page( array(
		'page_title'  => 'Newsletter sign-up',
		'menu_title'  => 'Newsletter',
		'menu_slug'   => 'hub-newsletter',
		'parent_slug' => 'hub-settings',
		'capability'  => 'manage_options',
	) );
} );

/** Small helpers so the field lists below stay readable. */
function hub_acf_field( $type, $name, $label, array $extra = array() ) {
	return array_merge( array( 'key' => 'field_hub_' . $name, 'name' => $name, 'label' => $label, 'type' => $type ), $extra );
}
function hub_acf_tab( $label ) {
	return array( 'key' => 'field_hub_tab_' . sanitize_key( $label ), 'label' => $label, 'type' => 'tab', 'placement' => 'top' );
}

add_action( 'acf/include_fields', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	/* ------------------------------------------------------------ Newsletter (options) */
	acf_add_local_field_group( array(
		'key'      => 'group_hub_newsletter',
		'title'    => 'Newsletter sign-up band',
		'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'hub-newsletter' ) ) ),
		'fields'   => array(
			hub_acf_field( 'message', 'nl_help', '', array( 'message' => 'The dark sign-up band shown above the footer on every Hub page. Leave a box empty to use the default wording.' ) ),
			hub_acf_field( 'true_false', 'nl_show', 'Show the band', array( 'ui' => 1, 'default_value' => 1, 'instructions' => 'Turn off to hide the band on every page.' ) ),
			hub_acf_field( 'text', 'nl_title', 'Heading', array( 'placeholder' => 'Harrow in your inbox, every morning' ) ),
			hub_acf_field( 'textarea', 'nl_text', 'Text', array( 'rows' => 2, 'new_lines' => '', 'placeholder' => 'The day’s local news, what’s on and the best Local Offers. Free, from Harrow Online Newsletter.' ) ),
			hub_acf_field( 'repeater', 'nl_editions', 'Newsletters to choose from', array(
				'instructions' => 'One tick box each. "Value" is what the sign-up form sends (daily, weekend, business).',
				'layout'       => 'table',
				'button_label' => 'Add newsletter',
				'sub_fields'   => array(
					hub_acf_field( 'text', 'nl_ed_value', 'Value', array( 'required' => 1, 'wrapper' => array( 'width' => 15 ) ) ),
					hub_acf_field( 'text', 'nl_ed_label', 'Name', array( 'required' => 1, 'wrapper' => array( 'width' => 25 ) ) ),
					hub_acf_field( 'text', 'nl_ed_desc', 'Short description', array( 'wrapper' => array( 'width' => 45 ) ) ),
					hub_acf_field( 'true_false', 'nl_ed_checked', 'Ticked', array( 'ui' => 1, 'wrapper' => array( 'width' => 15 ) ) ),
				),
			) ),
			hub_acf_field( 'text', 'nl_placeholder', 'Email box placeholder', array( 'placeholder' => 'Your email address', 'wrapper' => array( 'width' => 50 ) ) ),
			hub_acf_field( 'text', 'nl_button', 'Button', array( 'placeholder' => 'Sign up free', 'wrapper' => array( 'width' => 50 ) ) ),
			hub_acf_field( 'url', 'nl_action', 'Form sends to', array( 'placeholder' => 'https://harrowonline.org/wp-json/ho/v1/subscribe', 'wrapper' => array( 'width' => 70 ) ) ),
			hub_acf_field( 'text', 'nl_source', 'Sign-up source', array( 'placeholder' => 'hub-strip', 'instructions' => 'Sent with each sign-up so you can see where it came from.', 'wrapper' => array( 'width' => 30 ) ) ),
		),
	) );

	/* ------------------------------------------------------------ Business */
	$plans = array(
		'unclaimed' => 'Not claimed (imported from public data)',
		'basic'     => 'Basic (free)',
		'enhanced'  => 'Enhanced',
		'premium'   => 'Featured',
		'partner'   => 'Local Partner',
	);
	$days  = array();
	foreach ( array( 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' ) as $k => $d ) {
		$days[] = hub_acf_field( 'text', 'biz_hours_' . $k, $d, array( 'placeholder' => '9am to 5pm, or Closed', 'wrapper' => array( 'width' => 25 ) ) );
	}
	$claimed_only = array( array( array( 'field' => 'field_hub_biz_claimed', 'operator' => '==', 'value' => '1' ) ) );
	// Tabs whose fields are all for claimed listings say so on unclaimed ones, instead of looking empty
	$unclaimed_note = function ( $tab, $what ) {
		return hub_acf_field( 'message', 'biz_note_' . sanitize_key( $tab ), '', array(
			'message'           => '<strong>Only for claimed businesses.</strong> ' . $what . ' show on the business page once it is claimed. To add them, turn on “Claimed by the business” in the Listing tab.',
			'esc_html'          => 0,
			'conditional_logic' => array( array( array( 'field' => 'field_hub_biz_claimed', 'operator' => '!=', 'value' => '1' ) ) ),
		) );
	};

	acf_add_local_field_group( array(
		'key'        => 'group_hub_business',
		'title'      => 'Business details',
		'location'   => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hub_business' ) ) ),
		'position'   => 'normal',
		'style'      => 'default',
		'menu_order' => 0,
		'instructions' => '',
		'fields'     => array(
			// Listing
			hub_acf_tab( 'Listing' ),
			hub_acf_field( 'select', 'biz_plan', 'Plan', array( 'choices' => $plans, 'default_value' => 'unclaimed', 'wrapper' => array( 'width' => 34 ) ) ),
			hub_acf_field( 'true_false', 'biz_claimed', 'Claimed by the business', array( 'ui' => 1, 'instructions' => 'Shows the full profile and the "Verified by the business" tick.', 'wrapper' => array( 'width' => 33 ) ) ),
			hub_acf_field( 'true_false', 'biz_checked', 'Checked by Harrow Online', array( 'ui' => 1, 'instructions' => 'Shows the "Checked by Harrow Online" tick instead.', 'wrapper' => array( 'width' => 33 ) ) ),
			hub_acf_field( 'text', 'biz_sub', 'Type of business', array( 'instructions' => 'Shown next to the category, for example "Pizza takeaway and delivery".' ) ),
			hub_acf_field( 'textarea', 'biz_tagline', 'One-line summary', array( 'rows' => 2, 'new_lines' => '', 'instructions' => 'Under the name on the profile and in listing rows. The full description goes in the main text box above.', 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_icon', 'Icon', array( 'instructions' => 'Shown when there is no photo: store, food, people, scissors, tooth, wrench…', 'placeholder' => 'store', 'wrapper' => array( 'width' => 34 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_claimed_since', 'Claimed since', array( 'placeholder' => 'November 2026', 'wrapper' => array( 'width' => 33 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'true_false', 'biz_botw', 'Business of the Week', array( 'ui' => 1, 'wrapper' => array( 'width' => 33 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_verified_note', 'Note under the hours', array( 'instructions' => 'For example "Details supplied by the business and checked by Harrow Online in November 2026."', 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_seo_title', 'Page title for search engines', array( 'instructions' => 'Leave empty for "{Name} in {Area}".', 'conditional_logic' => $claimed_only ) ),

			// Contact
			hub_acf_tab( 'Contact and location' ),
			hub_acf_field( 'text', 'biz_address', 'Street address', array( 'wrapper' => array( 'width' => 70 ) ) ),
			hub_acf_field( 'text', 'biz_postcode', 'Postcode', array( 'wrapper' => array( 'width' => 30 ) ) ),
			hub_acf_field( 'text', 'biz_service_area', 'Works across (no shop or office)', array( 'instructions' => 'For trades that visit customers, for example "Harrow and north-west London". Hides the street address.', 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_phone', 'Phone', array( 'wrapper' => array( 'width' => 34 ) ) ),
			hub_acf_field( 'url', 'biz_website', 'Website', array( 'wrapper' => array( 'width' => 33 ) ) ),
			hub_acf_field( 'email', 'biz_email', 'Email', array( 'wrapper' => array( 'width' => 33 ) ) ),
			hub_acf_field( 'repeater', 'biz_phones_extra', 'More phone numbers', array(
				'layout' => 'table', 'button_label' => 'Add phone number',
				'sub_fields' => array( hub_acf_field( 'text', 'biz_phone_extra', 'Phone' ) ),
			) ),
			hub_acf_field( 'number', 'biz_lat', 'Map pin: latitude', array( 'step' => 'any', 'wrapper' => array( 'width' => 50 ) ) ),
			hub_acf_field( 'number', 'biz_lng', 'Map pin: longitude', array( 'step' => 'any', 'wrapper' => array( 'width' => 50 ) ) ),
			hub_acf_field( 'repeater', 'biz_other_locations', 'Other locations', array(
				'layout' => 'table', 'button_label' => 'Add location', 'conditional_logic' => $claimed_only,
				'sub_fields' => array(
					hub_acf_field( 'text', 'biz_loc_label', 'Label', array( 'placeholder' => 'Soccer School in winter' ) ),
					hub_acf_field( 'text', 'biz_loc_name', 'Place' ),
					hub_acf_field( 'text', 'biz_loc_address', 'Address' ),
				),
			) ),

			// Hours
			hub_acf_tab( 'Opening hours' ),
			$unclaimed_note( 'hours', 'Opening hours and timetables' ),
			hub_acf_field( 'group', 'biz_hours', 'Opening hours', array( 'layout' => 'block', 'sub_fields' => $days, 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_hours_note', 'Note under the hours', array( 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_status', 'Status instead of "Open today"', array( 'instructions' => 'For example "By appointment". Leave empty to work it out from the hours.', 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_schedule_title', 'Timetable heading (instead of opening hours)', array( 'placeholder' => 'When we meet', 'instructions' => 'For clubs and groups. Fill in the timetable below and leave the opening hours empty.', 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'repeater', 'biz_schedule', 'Timetable', array(
				'layout' => 'table', 'button_label' => 'Add row', 'conditional_logic' => $claimed_only,
				'sub_fields' => array(
					hub_acf_field( 'text', 'biz_sch_day', 'Day', array( 'wrapper' => array( 'width' => 30 ) ) ),
					hub_acf_field( 'text', 'biz_sch_text', 'What and when' ),
				),
			) ),
			hub_acf_field( 'textarea', 'biz_schedule_note', 'Timetable note', array( 'rows' => 2, 'new_lines' => '', 'conditional_logic' => $claimed_only ) ),

			// Photos
			hub_acf_tab( 'Logo and photos' ),
			$unclaimed_note( 'photos', 'A logo, cover photo and photos' ),
			hub_acf_field( 'image', 'biz_logo', 'Logo', array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all', 'wrapper' => array( 'width' => 34 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'image', 'biz_cover', 'Cover photo (Featured and Local Partner)', array( 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all', 'wrapper' => array( 'width' => 33 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'text', 'biz_cover_bg', 'Cover background', array( 'instructions' => 'Only for a logo-style cover: a colour behind it, for example #ffffff.', 'wrapper' => array( 'width' => 33 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'gallery', 'biz_gallery', 'Photos', array( 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all', 'instructions' => 'Up to 5 show on the profile (paid plans). The image "Alternative text" is used for screen readers.', 'conditional_logic' => $claimed_only ) ),

			// Social and news
			hub_acf_tab( 'Social and news' ),
			$unclaimed_note( 'social', 'Social links and news articles' ),
			hub_acf_field( 'url', 'biz_facebook', 'Facebook', array( 'wrapper' => array( 'width' => 50 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'url', 'biz_instagram', 'Instagram', array( 'wrapper' => array( 'width' => 50 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'url', 'biz_tiktok', 'TikTok', array( 'wrapper' => array( 'width' => 34 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'url', 'biz_linkedin', 'LinkedIn', array( 'wrapper' => array( 'width' => 33 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'url', 'biz_x', 'X (Twitter)', array( 'wrapper' => array( 'width' => 33 ), 'conditional_logic' => $claimed_only ) ),
			hub_acf_field( 'repeater', 'biz_news', 'In the news (Harrow Online articles)', array(
				'layout' => 'table', 'button_label' => 'Add article', 'conditional_logic' => $claimed_only,
				'instructions' => 'Only links to harrowonline.org are shown.',
				'sub_fields' => array(
					hub_acf_field( 'text', 'biz_news_title', 'Headline' ),
					hub_acf_field( 'url', 'biz_news_url', 'Link' ),
					hub_acf_field( 'text', 'biz_news_cat', 'Section', array( 'wrapper' => array( 'width' => 15 ) ) ),
					hub_acf_field( 'text', 'biz_news_date', 'Date', array( 'wrapper' => array( 'width' => 15 ) ) ),
				),
			) ),

			// Offers and classes (two-way)
			hub_acf_tab( 'Offers and classes' ),
			hub_acf_field( 'relationship', 'biz_offers', 'Offers', array(
				'post_type' => array( 'hub_offer' ), 'filters' => array( 'search' ), 'return_format' => 'id',
				'instructions' => 'This business’s Local Offers. Adding one here also sets the business on the offer. Offers show on paid plans only.',
				'bidirectional' => 1, 'bidirectional_target' => array( 'field_hub_offer_business' ),
			) ),
			hub_acf_field( 'relationship', 'biz_classes', 'Weekly classes', array(
				'post_type' => array( 'hub_class' ), 'filters' => array( 'search' ), 'return_format' => 'id',
				'instructions' => 'Classes this business runs. They show on the profile under "Upcoming events and classes", and the class page links back here.',
				'bidirectional' => 1, 'bidirectional_target' => array( 'field_hub_class_business' ),
			) ),
		),
	) );

	/* ------------------------------------------------------------ Offer and class: their business */
	foreach ( array( 'offer' => 'hub_offer', 'class' => 'hub_class' ) as $k => $type ) {
		acf_add_local_field_group( array(
			'key'      => "group_hub_{$k}_business",
			'title'    => 'Business',
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => $type ) ) ),
			'position' => 'side',
			'fields'   => array(
				hub_acf_field( 'post_object', "{$k}_business", 'Business', array(
					'post_type' => array( 'hub_business' ), 'return_format' => 'id', 'allow_null' => 1, 'ui' => 1,
					'instructions' => 'offer' === $k ? 'The business giving this offer.' : 'The business that runs this class.',
					'bidirectional' => 1, 'bidirectional_target' => array( 'offer' === $k ? 'field_hub_biz_offers' : 'field_hub_biz_classes' ),
				) ),
			),
		) );
	}
} );
