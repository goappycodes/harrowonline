<?php
/**
 * wp-admin: the "Page text" box on the Hub's pages (Pages > What's On, Classes and activities, Local Offers, Pricing,
 * Privacy notice, Terms of use), a native meta box with tabs. Read and saved through the pages module (hub_pages_fields / hub_pages_save_fields); an emptied
 * field goes back to the default wording. The fields of each page: hub_admin_page_text_{page}().
 */
defined( 'ABSPATH' ) || exit;

/** A page's fields, by tab (empty for a page without a box). */
function hub_admin_page_text_fields( $page ) {
	$fn = 'hub_admin_page_text_' . str_replace( '-', '_', (string) $page );
	return function_exists( $fn ) ? $fn() : array();
}

/** What's On (/events/). */
function hub_admin_page_text_events() {
	$link = 'A Hub path (events/classes/) or a full URL.';
	$hide = function ( $key, $what ) {
		return array( 'key' => $key, 'label' => 'Hide ' . $what, 'type' => 'toggle', 'width' => 100 );
	};
	$editions = array();
	foreach ( hub_newsletter_settings()['editions'] as $e ) {
		$editions[ (string) $e[0] ] = $e[1];
	}
	return array(
		'top'    => array( 'Header', array(
			array( 'key' => 'events_title', 'label' => 'Title', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'events_crumb', 'label' => 'Breadcrumb', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_lead', 'label' => 'Intro', 'type' => 'textarea' ),
			array( 'key' => 'events_lead_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_lead_link', 'label' => 'Link URL', 'type' => 'text', 'width' => 60, 'help' => $link ),
			array( 'key' => 'events_lead_after', 'label' => 'Text after link', 'type' => 'textarea', 'help' => 'Starts right after the link: begin with a full stop or a space.' ),
		) ),
		'list'   => array( 'List', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'Events, dates, counts and filters come from the listings.' ),
			$hide( 'events_chips_hide', 'quick links' ),
			array( 'key' => 'events_chips', 'label' => 'Quick links', 'type' => 'list', 'add' => 'Add a link',
				'help' => 'e.g. events/?when=today, events/?cat=family or events/?free=1. Icon optional: repeat, star, calendar…',
				'cols' => array( 'icon' => array( 'Icon' ), 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
			array( 'key' => 'events_featured_title', 'label' => 'Featured title', 'type' => 'text', 'width' => 100 ),
			array( 'key' => 'events_month_button', 'label' => 'Button text', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_month_link', 'label' => 'Button URL', 'type' => 'text', 'width' => 60, 'help' => $link ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'No results' ),
			array( 'key' => 'events_empty_text', 'label' => 'Message', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_empty_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 30 ),
			array( 'key' => 'events_empty_link', 'label' => 'Link URL', 'type' => 'text', 'width' => 30 ),
		) ),
		'panels' => array( 'Panels', array(
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Classes panel' ),
			$hide( 'events_classes_hide', 'panel' ),
			array( 'key' => 'events_classes_title', 'label' => 'Title', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_classes_text', 'label' => 'Text', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'events_classes_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_classes_link', 'label' => 'Link URL', 'type' => 'text', 'width' => 60, 'help' => $link ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Newsletter panel' ),
			$hide( 'events_nl_hide', 'panel' ),
			array( 'key' => 'events_nl_title', 'label' => 'Title', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'events_nl_text', 'label' => 'Text', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'events_nl_edition', 'label' => 'Newsletter', 'type' => 'select', 'choices' => $editions, 'width' => 34,
				'help' => 'Set in Hub settings > Newsletter.' ),
			array( 'key' => 'events_nl_placeholder', 'label' => 'Email placeholder', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'events_nl_button', 'label' => 'Button text', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'events_nl_note', 'label' => 'Note', 'type' => 'text' ),
			array( 'key' => 'events_nl_done', 'label' => 'Success message', 'type' => 'text' ),
		) ),
		'cta'    => array( 'Bottom box', array(
			$hide( 'events_cta_hide', 'box' ),
			array( 'key' => 'events_cta_title', 'label' => 'Title', 'type' => 'text' ),
			array( 'key' => 'events_cta_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'events_cta_buttons', 'label' => 'Buttons', 'type' => 'list', 'add' => 'Add a button', 'help' => 'The first is red. Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
		) ),
	);
}

/** Classes and activities (/events/classes/). */
function hub_admin_page_text_classes() {
	$link = 'A Hub path (events/classes/) or a full URL.';
	return array(
		'top'  => array( 'Header', array(
			array( 'key' => 'classes_title', 'label' => 'Title', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'classes_crumb', 'label' => 'Breadcrumb', 'type' => 'text', 'width' => 40,
				'help' => 'After “Hub › What’s On” (set on the What’s On page).' ),
			array( 'key' => 'classes_lead', 'label' => 'Intro', 'type' => 'textarea' ),
		) ),
		'list' => array( 'List', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'Class types, counts, filters and classes come from the listings.' ),
			array( 'key' => 'classes_all_label', 'label' => '“All” link text', 'type' => 'text', 'width' => 100 ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'No results' ),
			array( 'key' => 'classes_empty_text', 'label' => 'Message', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'classes_empty_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40, 'help' => 'The link clears the filters.' ),
		) ),
		'cta'  => array( 'Bottom box', array(
			array( 'key' => 'classes_cta_hide', 'label' => 'Hide box', 'type' => 'toggle', 'width' => 100 ),
			array( 'key' => 'classes_cta_title', 'label' => 'Title', 'type' => 'text' ),
			array( 'key' => 'classes_cta_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'classes_cta_buttons', 'label' => 'Buttons', 'type' => 'list', 'add' => 'Add a button', 'help' => 'The first is red. Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
		) ),
	);
}

/** Local Offers (/offers/). */
function hub_admin_page_text_offers() {
	$link = 'A Hub path (events/classes/) or a full URL.';
	return array(
		'top'  => array( 'Header', array(
			array( 'key' => 'offers_title', 'label' => 'Title', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'offers_crumb', 'label' => 'Breadcrumb', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'offers_lead', 'label' => 'Intro', 'type' => 'textarea' ),
		) ),
		'list' => array( 'List', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'Categories, counts, filters and offers come from the listings. Ended offers drop off by themselves.' ),
			array( 'key' => 'offers_all_label', 'label' => '“All” link text', 'type' => 'text', 'width' => 100 ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'No results' ),
			array( 'key' => 'offers_empty_text', 'label' => 'Message', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'offers_empty_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40, 'help' => 'The link clears the filters.' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Small print' ),
			array( 'key' => 'offers_note_hide', 'label' => 'Hide small print', 'type' => 'toggle', 'width' => 100 ),
			array( 'key' => 'offers_note', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'offers_note_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'offers_note_link', 'label' => 'Link URL', 'type' => 'text', 'width' => 60, 'help' => $link ),
		) ),
		'cta'  => array( 'Bottom box', array(
			array( 'key' => 'offers_cta_hide', 'label' => 'Hide box', 'type' => 'toggle', 'width' => 100 ),
			array( 'key' => 'offers_cta_title', 'label' => 'Title', 'type' => 'text' ),
			array( 'key' => 'offers_cta_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'offers_cta_buttons', 'label' => 'Buttons', 'type' => 'list', 'add' => 'Add a button', 'help' => 'The first is red. Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
		) ),
	);
}

/** Plans and prices (/pricing/). */
function hub_admin_page_text_pricing() {
	$link   = 'A Hub path (events/classes/) or a full URL.';
	$hide   = function ( $key, $what ) {
		return array( 'key' => $key, 'label' => 'Hide ' . $what, 'type' => 'toggle', 'width' => 100 );
	};
	$prices = array();
	foreach ( hub_billing_catalogue()['plans'] as $p ) {   // billing module
		$prices[] = $p['name'] . ' ' . $p['monthly'] . ' / ' . $p['annual'];
	}
	$price_note = 'Prices come from the billing settings (' . implode( ', ', $prices ) . '), so they always match checkout.';
	return array(
		'top'     => array( 'Header', array(
			array( 'key' => 'pricing_eyebrow', 'label' => 'Label', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_title', 'label' => 'Title', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'pricing_lead', 'label' => 'Intro', 'type' => 'textarea' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Monthly / annual switch' ),
			array( 'key' => 'pricing_monthly_label', 'label' => 'Monthly button', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_annual_label', 'label' => 'Annual button', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_annual_save', 'label' => 'Annual badge', 'type' => 'text', 'width' => 34 ),
		) ),
		'plans'   => array( 'Plans', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => $price_note ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Basic' ),
			array( 'key' => 'pricing_basic_name', 'label' => 'Name', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_basic_price', 'label' => 'Price', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_basic_price_small', 'label' => 'Price note', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_basic_for', 'label' => 'Description', 'type' => 'text' ),
			array( 'key' => 'pricing_basic_note', 'label' => 'Small line', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_basic_button', 'label' => 'Button text', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_basic_link', 'label' => 'Button URL', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_basic_features', 'label' => 'Features', 'type' => 'list', 'flat' => true, 'cols' => array( 't' => array( 'Feature' ) ), 'add' => 'Add feature' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Featured' ),
			array( 'key' => 'pricing_featured_name', 'label' => 'Name', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_featured_flag', 'label' => 'Badge', 'type' => 'text', 'width' => 33 ),
			array( 'key' => 'pricing_featured_flag_hide', 'label' => 'Hide badge', 'type' => 'toggle', 'width' => 33 ),
			array( 'key' => 'pricing_featured_for', 'label' => 'Description', 'type' => 'text' ),
			array( 'key' => 'pricing_featured_button', 'label' => 'Button text', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_featured_lead', 'label' => 'Features intro', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_featured_features', 'label' => 'Features', 'type' => 'list', 'flat' => true, 'cols' => array( 't' => array( 'Feature' ) ), 'add' => 'Add feature' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Local Partner' ),
			array( 'key' => 'pricing_partner_name', 'label' => 'Name', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_partner_for', 'label' => 'Description', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'pricing_partner_button', 'label' => 'Button text', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_partner_lead', 'label' => 'Features intro', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_partner_features', 'label' => 'Features', 'type' => 'list', 'flat' => true, 'cols' => array( 't' => array( 'Feature' ) ), 'add' => 'Add feature' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Paid plans', 'help' => '{annual} and {per_month} are filled in with each plan’s own prices.' ),
			array( 'key' => 'pricing_per_month', 'label' => 'After the monthly price', 'type' => 'text', 'width' => 25 ),
			array( 'key' => 'pricing_per_year', 'label' => 'After the annual price', 'type' => 'text', 'width' => 25 ),
			array( 'key' => 'pricing_paid_note_monthly', 'label' => 'Note (monthly)', 'type' => 'text', 'width' => 25 ),
			array( 'key' => 'pricing_paid_note_annual', 'label' => 'Note (annual)', 'type' => 'text', 'width' => 25 ),
		) ),
		'extras'  => array( 'Event &amp; note', array(
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'One-off event box', 'help' => '{event_price} is the one-off event price from the billing settings.' ),
			$hide( 'pricing_oneoff_hide', 'box' ),
			array( 'key' => 'pricing_oneoff_eyebrow', 'label' => 'Label', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_oneoff_title', 'label' => 'Title', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'pricing_oneoff_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'pricing_oneoff_per', 'label' => 'After the price', 'type' => 'text', 'width' => 25 ),
			array( 'key' => 'pricing_oneoff_button', 'label' => 'Button text', 'type' => 'text', 'width' => 35 ),
			array( 'key' => 'pricing_oneoff_link', 'label' => 'Button URL', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Note under the plans' ),
			$hide( 'pricing_info_hide', 'note' ),
			array( 'key' => 'pricing_info_title', 'label' => 'Bold start', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_info_text', 'label' => 'Text', 'type' => 'textarea', 'width' => 66 ),
			array( 'key' => 'pricing_info_link_label', 'label' => 'Link text', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_info_link', 'label' => 'Link URL', 'type' => 'text', 'width' => 66, 'help' => $link ),
		) ),
		'labels'  => array( 'Labels', array(
			$hide( 'pricing_labels_hide', 'section' ),
			array( 'key' => 'pricing_labels_title', 'label' => 'Title', 'type' => 'text' ),
			array( 'key' => 'pricing_labels_sub', 'label' => 'Intro', 'type' => 'textarea' ),
			array( 'key' => 'pricing_label_verified', 'label' => 'Verified: name', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_label_verified_text', 'label' => 'Verified: text', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'pricing_label_checked', 'label' => 'Checked: name', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_label_checked_text', 'label' => 'Checked: text', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'pricing_label_imported', 'label' => 'Imported: name', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'pricing_label_imported_text', 'label' => 'Imported: text', 'type' => 'text', 'width' => 66 ),
			array( 'key' => 'pricing_labels_help', 'label' => 'Note under the list', 'type' => 'textarea' ),
		) ),
		'compare' => array( 'Compare', array(
			$hide( 'pricing_compare_hide', 'section' ),
			array( 'key' => 'pricing_compare_title', 'label' => 'Title', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_compare_button', 'label' => 'Open button', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_compare_sub', 'label' => 'Subtitle', 'type' => 'text', 'width' => 50, 'help' => '{period} shows the two words on the right, following the switch.' ),
			array( 'key' => 'pricing_period_monthly', 'label' => '{period}: monthly', 'type' => 'text', 'width' => 25 ),
			array( 'key' => 'pricing_period_annual', 'label' => '{period}: annual', 'type' => 'text', 'width' => 25 ),
			array( 'key' => 'pricing_compare_caption', 'label' => 'Table caption (read out)', 'type' => 'text' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Columns and price row' ),
			array( 'key' => 'pricing_col_feature', 'label' => 'First column', 'type' => 'text', 'width' => 20 ),
			array( 'key' => 'pricing_col_unclaimed', 'label' => 'Unclaimed', 'type' => 'text', 'width' => 20 ),
			array( 'key' => 'pricing_col_basic', 'label' => 'Basic', 'type' => 'text', 'width' => 20 ),
			array( 'key' => 'pricing_col_featured', 'label' => 'Featured', 'type' => 'text', 'width' => 20 ),
			array( 'key' => 'pricing_col_partner', 'label' => 'Local Partner', 'type' => 'text', 'width' => 20 ),
			array( 'key' => 'pricing_compare_price_label', 'label' => 'Price row label', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_compare_free', 'label' => 'Free plans show', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'pricing_compare_rows', 'label' => 'Rows', 'type' => 'list', 'add' => 'Add row',
				'help' => 'In a plan column: <strong>yes</strong> = tick, <strong>no</strong> = dash, <strong>- None</strong> = dash read out as “None”; any other text shows as typed (links allowed). Fill only Unclaimed for a row across all four. Tick Heading for a section heading. {event_price} works here.',
				'cols' => array( 'heading' => array( 'Heading', 'check' ), 'feature' => array( 'Feature' ), 'unclaimed' => array( 'Unclaimed', 'html' ), 'basic' => array( 'Basic', 'html' ), 'featured' => array( 'Featured', 'html' ), 'partner' => array( 'Local Partner', 'html' ) ) ),
		) ),
		'billing' => array( 'Billing', array(
			$hide( 'pricing_billing_hide', 'section' ),
			array( 'key' => 'pricing_billing_title', 'label' => 'Title', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'pricing_billing_sub', 'label' => 'Subtitle', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'pricing_billing_panels', 'label' => 'Panels', 'type' => 'list', 'add' => 'Add panel', 'help' => 'Icons: lock, check, settings, pound, download, heart, star…',
				'cols' => array( 'icon' => array( 'Icon' ), 'title' => array( 'Title' ), 'text' => array( 'Text', 'html' ) ) ),
		) ),
		'faq'     => array( 'Questions', array(
			$hide( 'pricing_faq_hide', 'section' ),
			array( 'key' => 'pricing_faq_title', 'label' => 'Title', 'type' => 'text' ),
			array( 'key' => 'pricing_faq', 'label' => 'Questions and answers', 'type' => 'list', 'add' => 'Add a question', 'help' => 'Links and bold are allowed in answers. {event_price} works here.',
				'cols' => array( 'question' => array( 'Question' ), 'answer' => array( 'Answer', 'html' ) ) ),
		) ),
		'cta'     => array( 'Bottom box', array(
			$hide( 'pricing_cta_hide', 'box' ),
			array( 'key' => 'pricing_cta_title', 'label' => 'Title', 'type' => 'text' ),
			array( 'key' => 'pricing_cta_text', 'label' => 'Text', 'type' => 'textarea' ),
			array( 'key' => 'pricing_cta_buttons', 'label' => 'Buttons', 'type' => 'list', 'add' => 'Add a button', 'help' => 'The first is red. Links: ' . $link,
				'cols' => array( 'label' => array( 'Text' ), 'link' => array( 'URL' ) ) ),
		) ),
	);
}

/** Privacy notice (/privacy/): the header and the side card. The text itself is the editor above. */
function hub_admin_page_text_privacy() {
	return array(
		'top'  => array( 'Header', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'The notice itself is in the editor above. Each Heading 2 makes a line in “On this page” (a number like “1.” at the start is left out).' ),
			array( 'key' => 'privacy_title', 'label' => 'Title', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'privacy_crumb', 'label' => 'Breadcrumb', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'privacy_lead', 'label' => 'Intro', 'type' => 'textarea' ),
		) ),
		'side' => array( 'Side card', array(
			array( 'key' => 'privacy_toc_title', 'label' => '“On this page” title', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'privacy_updated', 'label' => 'Updated line', 'type' => 'text', 'width' => 50, 'help' => 'e.g. “Last updated: 11 November 2026”.' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Draft note' ),
			array( 'key' => 'privacy_draft_hide', 'label' => 'Hide the draft note', 'type' => 'toggle', 'width' => 100, 'help' => 'Turn on once the text is approved.' ),
			array( 'key' => 'privacy_draft_title', 'label' => 'Bold start', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'privacy_draft_text', 'label' => 'Text', 'type' => 'text', 'width' => 66 ),
		) ),
	);
}

/** Terms of use (/terms/): the header and the side card. The text itself is the editor above. */
function hub_admin_page_text_terms() {
	return array(
		'top'  => array( 'Header', array(
			array( 'key' => 'note', 'type' => 'note', 'label' => 'The terms text itself is in the editor above. Each Heading 2 makes a line in “On this page” (a number like “1.” at the start is left out).' ),
			array( 'key' => 'terms_title', 'label' => 'Title', 'type' => 'text', 'width' => 60 ),
			array( 'key' => 'terms_crumb', 'label' => 'Breadcrumb', 'type' => 'text', 'width' => 40 ),
			array( 'key' => 'terms_lead', 'label' => 'Intro', 'type' => 'textarea' ),
		) ),
		'side' => array( 'Side card', array(
			array( 'key' => 'terms_toc_title', 'label' => '“On this page” title', 'type' => 'text', 'width' => 50 ),
			array( 'key' => 'terms_updated', 'label' => 'Updated line', 'type' => 'text', 'width' => 50, 'help' => 'e.g. “Last updated: 11 November 2026”.' ),
			array( 'key' => 'note', 'type' => 'heading', 'label' => 'Draft note' ),
			array( 'key' => 'terms_draft_hide', 'label' => 'Hide the draft note', 'type' => 'toggle', 'width' => 100, 'help' => 'Turn on once the text is approved.' ),
			array( 'key' => 'terms_draft_title', 'label' => 'Bold start', 'type' => 'text', 'width' => 34 ),
			array( 'key' => 'terms_draft_text', 'label' => 'Text', 'type' => 'text', 'width' => 66 ),
		) ),
	);
}

add_action( 'add_meta_boxes_page', function ( WP_Post $post ) {
	if ( hub_pages_for_post( $post->ID ) ) {
		add_meta_box( 'hub-page-text', 'Page text', 'hub_admin_page_text_box', 'page', 'normal', 'high' );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && isset( $_GET['post'] ) && hub_pages_for_post( (int) $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		hub_admin_enqueue();
	}
} );

/** The box. */
function hub_admin_page_text_box( WP_Post $post ) {
	$page = hub_pages_for_post( $post->ID );
	wp_nonce_field( 'hub_page_text_save', 'hub_page_text_nonce' );
	echo '<p class="hub-f__help" style="margin:4px 12px 0">Empty fields use the default text. The listings fill in from the Hub’s content.</p>';
	hub_admin_tabs( hub_admin_page_text_fields( $page ), hub_pages_fields( $page ) );
}

add_action( 'save_post_page', function ( $post_id ) {
	$page = hub_pages_for_post( $post_id );
	if ( ! $page || ! isset( $_POST['hub_page_text_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hub_page_text_nonce'] ), 'hub_page_text_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$sent = isset( $_POST['hub'] ) && is_array( $_POST['hub'] ) ? $_POST['hub'] : array(); // phpcs:ignore -- cleaned field by field
	hub_pages_save_fields( $page, hub_admin_clean_all( hub_admin_tab_fields( hub_admin_page_text_fields( $page ) ), $sent ) );
} );
