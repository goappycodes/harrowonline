<?php
/**
 * Page text rules: which pages have editable text (What's On, Classes and activities, Local Offers, Pricing, Privacy,
 * Terms), and the default wording of each (used for any field left empty).
 *
 * To give another page a "Page text" box: add it to HUB_PAGES, write hub_pages_rule_defaults_{page}() here and
 * hub_admin_page_text_{page}() in inc/admin/page-text-box.php, then read hub_pages_text( '{page}' ) in its template.
 */
defined( 'ABSPATH' ) || exit;

/** Pages with editable text: key => the WordPress Page's path. */
const HUB_PAGES = array(
	'events'  => 'events',           // What's On (page-templates/events.php)
	'classes' => 'events/classes',   // Classes and activities (page-templates/classes.php)
	'offers'  => 'offers',           // Local Offers (page-templates/offers.php)
	'pricing' => 'pricing',          // Plans and prices (page-templates/pricing.php); the prices come from billing
	'privacy' => 'privacy',          // Privacy notice: its text is the page's own editor (HUB_PAGES_DOCUMENTS)
	'terms'   => 'terms',            // Terms of use: the same
);

/** Pages whose main text is the WordPress editor's (with an "On this page" list from its headings). */
const HUB_PAGES_DOCUMENTS = array( 'privacy', 'terms' );

/** Every field of a page with its default: text, '' for the "…_hide" switches, rows for lists. */
function hub_pages_rule_defaults( $page ) {
	$fn = 'hub_pages_rule_defaults_' . str_replace( '-', '_', (string) $page );
	return isset( HUB_PAGES[ $page ] ) && function_exists( $fn ) ? $fn() : array();
}

/** What's On (/events/): today's wording. */
function hub_pages_rule_defaults_events() {
	return array(
		// Top of the page
		'events_crumb'              => 'What’s On',
		'events_title'              => 'What’s On in Harrow',
		'events_lead'               => 'Events, days out and regular classes across the borough, checked by the Harrow Online team. Running something?',
		'events_lead_link_label'    => 'Add your event',
		'events_lead_link'          => 'register/',
		'events_lead_after'         => '. It’s free for schools, clubs and charities, and included in the Featured and Local Partner plans for businesses.',
		// Above and in the list
		'events_chips_hide'         => '',
		'events_chips'              => array(
			array( 'icon' => '', 'label' => 'Today', 'link' => 'events/?when=today' ),
			array( 'icon' => '', 'label' => 'This weekend', 'link' => 'events/?when=weekend' ),
			array( 'icon' => '', 'label' => 'Next 7 days', 'link' => 'events/?when=7days' ),
			array( 'icon' => 'repeat', 'label' => 'Classes and activities', 'link' => 'events/classes/' ),
			array( 'icon' => '', 'label' => 'Family', 'link' => 'events/?cat=family' ),
			array( 'icon' => '', 'label' => 'Free', 'link' => 'events/?free=1' ),
		),
		'events_featured_title'     => 'Featured events',
		'events_empty_text'         => 'Nothing listed for that date yet.',
		'events_empty_link_label'   => 'See the month calendar',
		'events_empty_link'         => 'events/month/',
		'events_month_button'       => 'See the whole month',
		'events_month_link'         => 'events/month/',
		// Under the list: the classes panel and the newsletter panel
		'events_classes_hide'       => '',
		'events_classes_title'      => 'Looking for a regular class?',
		'events_classes_text'       => 'Fitness, dance, kids’ clubs and workshops that run every week, all in one place.',
		'events_classes_link_label' => 'Browse classes and activities',
		'events_classes_link'       => 'events/classes/',
		'events_nl_hide'            => '',
		'events_nl_title'           => 'The Weekend, every Thursday',
		'events_nl_text'            => 'Our Thursday newsletter picks the best things to do in Harrow that weekend.',
		'events_nl_edition'         => 'weekend',
		'events_nl_placeholder'     => 'Your email',
		'events_nl_button'          => 'Get The Weekend',
		'events_nl_note'            => 'Free. The Weekend only: it won’t sign you up to Daily News or Harrow Business.',
		'events_nl_done'            => 'Thanks! Check your inbox to confirm. The Weekend arrives every Thursday.',
		// The box at the bottom
		'events_cta_hide'           => '',
		'events_cta_title'          => 'Running an event in Harrow?',
		'events_cta_text'           => 'Businesses add events and classes on the Featured or Local Partner plan, or list a single event for £15 with no plan. Schools, clubs, charities and community groups add them free with an organiser account. We check every listing before it goes live, usually within one working day.',
		'events_cta_buttons'        => array(
			array( 'label' => 'Add your event', 'link' => 'register/' ),
			array( 'label' => 'Business plans', 'link' => 'pricing/' ),
		),
	);
}

/** Classes and activities (/events/classes/): today's wording. */
function hub_pages_rule_defaults_classes() {
	return array(
		// Top of the page
		'classes_crumb'            => 'Classes and activities',
		'classes_title'            => 'Classes and activities',
		'classes_lead'             => 'Regular classes that run every week across Harrow: kids’ clubs, fitness, dance schools, workshops and evening courses. Each class has one page that the provider keeps up to date.',
		// The list
		'classes_all_label'        => 'All classes',
		'classes_empty_text'       => 'No classes match that yet.',
		'classes_empty_link_label' => 'See all classes',
		// The box at the bottom
		'classes_cta_hide'         => '',
		'classes_cta_title'        => 'Run a class in Harrow?',
		'classes_cta_text'         => 'Businesses add classes on the Featured or Local Partner plan. Schools, clubs, charities and community groups add them free with an organiser account. You keep one page up to date all year, and we check it before it goes live.',
		'classes_cta_buttons'      => array(
			array( 'label' => 'List your class', 'link' => 'register/' ),
			array( 'label' => 'Business plans', 'link' => 'pricing/' ),
		),
	);
}

/** Local Offers (/offers/): today's wording. */
function hub_pages_rule_defaults_offers() {
	return array(
		// Top of the page
		'offers_crumb'             => 'Local Offers',
		'offers_title'             => 'Local Offers',
		'offers_lead'              => 'Deals from Harrow businesses, checked by our team and removed automatically when they end. Some are just for newsletter subscribers.',
		// The list
		'offers_all_label'         => 'All offers',
		'offers_empty_text'        => 'No offers match that yet.',
		'offers_empty_link_label'  => 'See all offers',
		'offers_note_hide'         => '',
		'offers_note'              => 'Offers are run by the businesses listed. Check terms with the business before you buy. Spotted a problem with an offer?',
		'offers_note_link_label'   => 'Let us know',
		'offers_note_link'         => 'report/',
		// The box at the bottom
		'offers_cta_hide'          => '',
		'offers_cta_title'         => 'Run an offer for Harrow',
		'offers_cta_text'          => 'Hub members on a paid plan can post Local Offers from their dashboard. Offers appear here, on your profile and in the newsletter.',
		'offers_cta_buttons'       => array(
			array( 'label' => 'See plans', 'link' => 'pricing/' ),
			array( 'label' => 'Add an offer', 'link' => 'account/offers/' ),
		),
	);
}

/**
 * Plans and prices (/pricing/): today's wording. The £ amounts come from the billing module; in any text,
 * {event_price}, {featured_monthly}, {featured_annual}, {partner_monthly} and {partner_annual} are filled in, and in
 * the paid plans' notes {annual} and {per_month} (the plan's own).
 */
function hub_pages_rule_defaults_pricing() {
	return array(
		'pricing_eyebrow'               => 'For Harrow businesses',
		'pricing_title'                 => 'Get found by Harrow',
		'pricing_lead'                  => 'Claiming your listing is free. Upgrade when you want more visibility, Local Offers, events and a place in the Harrow Online Newsletter.',
		'pricing_monthly_label'         => 'Monthly',
		'pricing_annual_label'          => 'Annual',
		'pricing_annual_save'           => '2 months free',
		'pricing_basic_name'            => 'Basic',
		'pricing_basic_for'             => 'For any Harrow business. Claim your listing and keep it up to date.',
		'pricing_basic_price'           => '£0',
		'pricing_basic_price_small'     => 'free forever',
		'pricing_basic_note'            => 'No card needed',
		'pricing_basic_button'          => 'Find and claim your business',
		'pricing_basic_link'            => 'search/',
		'pricing_basic_features'        => array( 'Verified business listing', 'Business details and opening hours', 'One photograph', 'Ability to keep the listing updated' ),
		'pricing_featured_flag_hide'    => '',
		'pricing_featured_flag'         => 'Most popular',
		'pricing_featured_name'         => 'Featured',
		'pricing_featured_for'          => 'For businesses that want to be seen first and keep customers coming back.',
		'pricing_featured_button'       => 'Choose Featured',
		'pricing_featured_lead'         => 'Everything in Basic, plus:',
		'pricing_featured_features'     => array( 'Priority placement', 'Social media links', 'Additional photographs', 'Ability to publish Local Offers', 'Ability to add events and classes', 'Featured positions within the Hub' ),
		'pricing_partner_name'          => 'Local Partner',
		'pricing_partner_for'           => 'Featured on the Hub, plus a monthly Daily News advert and Facebook Group post.',
		'pricing_partner_button'        => 'Become a Local Partner',
		'pricing_partner_lead'          => 'Everything in Featured, plus:',
		'pricing_partner_features'      => array( 'One advert each month in the Daily News newsletter', 'One Harrow Online Facebook Group post each month' ),
		'pricing_per_month'             => '/month',
		'pricing_per_year'              => '/year',
		'pricing_paid_note_monthly'     => 'Or {annual} a year',
		'pricing_paid_note_annual'      => 'Works out at {per_month} a month',
		'pricing_oneoff_hide'           => '',
		'pricing_oneoff_eyebrow'        => 'No plan needed',
		'pricing_oneoff_title'          => 'Just one event? List it for {event_price}',
		'pricing_oneoff_text'           => 'Pay when you submit it, our team checks it, and it stays on What’s On until the event date. Weekly classes need Featured or Local Partner.',
		'pricing_oneoff_per'            => '/event',
		'pricing_oneoff_button'         => 'Add a one-off event',
		'pricing_oneoff_link'           => 'register/',
		'pricing_info_hide'             => '',
		'pricing_info_title'            => 'Not claimed yet?',
		'pricing_info_text'             => 'Unclaimed listings show only the basics we imported from open map data (name, address, phone and website) with a Claim button, and they stay out of search engines until someone claims them and fills them in.',
		'pricing_info_link_label'       => 'About our data',
		'pricing_info_link'             => 'data-sources/',
		'pricing_labels_hide'           => '',
		'pricing_labels_title'          => 'What the labels mean',
		'pricing_labels_sub'            => 'Every business page shows where its details come from, with one of these icons next to its name. In lists and search results only verified and checked businesses carry an icon. Hover or tap an icon to see what it means.',
		'pricing_label_verified'        => 'Verified by the business',
		'pricing_label_verified_text'   => 'The owner has claimed the listing and keeps the details up to date.',
		'pricing_label_checked'         => 'Checked by Harrow Online',
		'pricing_label_checked_text'    => 'Our team has checked the details with the business. The owner hasn’t claimed it yet.',
		'pricing_label_imported'        => 'Information imported from public data',
		'pricing_label_imported_text'   => 'Taken from public sources and not checked yet. If it’s your business, claim it free to update it.',
		'pricing_labels_help'           => 'Only verified or checked businesses appear in homepage features, recommendations and paid spots. Imported listings stay searchable and can be claimed.',
		'pricing_compare_hide'          => '',
		'pricing_compare_title'         => 'Compare every plan',
		'pricing_compare_sub'           => 'Prices shown {period}. Switch at the top of the page.',
		'pricing_period_monthly'        => 'monthly',
		'pricing_period_annual'         => 'for a year',
		'pricing_compare_button'        => 'Show the full comparison',
		'pricing_compare_caption'       => 'Features included in each Harrow Online Hub plan',
		'pricing_col_feature'           => 'Feature',
		'pricing_col_unclaimed'         => 'Unclaimed',
		'pricing_col_basic'             => 'Basic',
		'pricing_col_featured'          => 'Featured',
		'pricing_col_partner'           => 'Local Partner',
		'pricing_compare_price_label'   => 'Price',
		'pricing_compare_free'          => 'Free',
		'pricing_compare_rows'          => array(
			array( 'heading' => '1', 'feature' => 'Listing', 'unclaimed' => '', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '', 'feature' => 'Name, address, phone and website', 'unclaimed' => 'yes', 'basic' => 'yes', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Verified business listing', 'unclaimed' => 'no', 'basic' => 'yes', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Business details and opening hours', 'unclaimed' => 'no', 'basic' => 'yes', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Keep the listing updated yourself', 'unclaimed' => 'no', 'basic' => 'yes', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Photographs', 'unclaimed' => '- None', 'basic' => 'One', 'featured' => 'Additional photographs', 'partner' => 'Additional photographs' ),
			array( 'heading' => '', 'feature' => 'Social media links', 'unclaimed' => 'no', 'basic' => 'no', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Shown in Google and other search engines', 'unclaimed' => 'no', 'basic' => 'yes', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '1', 'feature' => 'Visibility', 'unclaimed' => '', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '', 'feature' => 'Place in category and search results', 'unclaimed' => 'Last', 'basic' => 'Above unclaimed', 'featured' => 'Priority placement', 'partner' => 'Priority placement' ),
			array( 'heading' => '', 'feature' => 'Featured positions within the Hub', 'unclaimed' => 'no', 'basic' => 'no', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '1', 'feature' => 'Offers and events', 'unclaimed' => '', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '', 'feature' => 'Publish Local Offers', 'unclaimed' => 'no', 'basic' => 'no', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Add events and classes to What’s On', 'unclaimed' => 'no', 'basic' => 'no', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Single event without a plan', 'unclaimed' => '{event_price} per event, listed until the event date', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '1', 'feature' => 'Newsletter and marketing', 'unclaimed' => '', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '', 'feature' => 'Daily News advert each month', 'unclaimed' => 'no', 'basic' => 'no', 'featured' => 'no', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Harrow Online Facebook Group post each month', 'unclaimed' => 'no', 'basic' => 'no', 'featured' => 'no', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Newsletter adverts', 'unclaimed' => '<a href="/hub/advertise/newsletter/">Book separately</a>, on any plan', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '1', 'feature' => 'Billing', 'unclaimed' => '', 'basic' => '', 'featured' => '', 'partner' => '' ),
			array( 'heading' => '', 'feature' => 'Pay monthly or annually', 'unclaimed' => '- Not applicable', 'basic' => '- Not applicable', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Upgrade, downgrade or cancel any time', 'unclaimed' => '- Not applicable', 'basic' => '- Not applicable', 'featured' => 'yes', 'partner' => 'yes' ),
			array( 'heading' => '', 'feature' => 'Receipts and PDF invoices', 'unclaimed' => '- Not applicable', 'basic' => '- Not applicable', 'featured' => 'yes', 'partner' => 'yes' ),
		),
		'pricing_billing_hide'          => '',
		'pricing_billing_title'         => 'Simple, safe billing',
		'pricing_billing_sub'           => 'Payments run through Stripe. You stay in control of your plan.',
		'pricing_billing_panels'        => array(
			array( 'icon' => 'lock', 'title' => 'Secure checkout', 'text' => 'Pay by card on Stripe Checkout. Your card details go straight to Stripe and never touch our site.' ),
			array( 'icon' => 'check', 'title' => 'Live straight away', 'text' => 'Paid features switch on as soon as your payment succeeds. No waiting for approval.' ),
			array( 'icon' => 'settings', 'title' => 'Change or cancel any time', 'text' => 'Upgrade, downgrade, switch between monthly and annual, update your card or cancel in the Stripe customer portal.' ),
			array( 'icon' => 'pound', 'title' => 'Fair proration', 'text' => 'Change plan mid-month and you only pay the difference. Downgrades are credited to your next bill.' ),
			array( 'icon' => 'download', 'title' => 'Receipts and invoices', 'text' => 'Stripe emails a receipt for every payment, and you can download PDF invoices from your account.' ),
			array( 'icon' => 'heart', 'title' => 'Nothing is deleted', 'text' => 'If you downgrade or cancel, your listing drops to Basic. Extra photos, offers and links are hidden, not deleted, and come back if you upgrade.' ),
		),
		'pricing_faq_hide'              => '',
		'pricing_faq_title'             => 'Questions',
		'pricing_faq'                   => array(
			array( 'question' => 'Is claiming really free?', 'answer' => 'Yes. Claiming puts you on the Basic plan, which is free for as long as you like. You get a verified listing, your business details and opening hours, one photograph, and you can keep the listing up to date yourself.' ),
			array( 'question' => 'What’s the difference between Basic and Featured?', 'answer' => 'Basic gets your business listed and verified. Featured puts you ahead of other businesses in your category and in search, gives you featured positions around the Hub, and lets you add more photographs, social media links, Local Offers, and events and classes.' ),
			array( 'question' => 'How long does it take to go live?', 'answer' => 'Claims are checked by our team, usually within 2 working days, and we email you either way. That’s the only approval step. Once your listing is yours, paid upgrades go live the moment your payment succeeds. Offers and events are checked before they appear.' ),
			array( 'question' => 'Can I switch between monthly and annual?', 'answer' => 'Yes. Switch in the Stripe customer portal, linked from your account. Annual plans cost the same as 10 months, so you get 2 months free. Any change is prorated, so you only pay for what you use.' ),
			array( 'question' => 'What happens if I downgrade or cancel?', 'answer' => 'Your plan runs to the end of the period you’ve paid for, then your listing drops to Basic. We keep all your data. Extra photos, social media links, offers and featured spots are hidden, not deleted, so everything comes back if you upgrade again.' ),
			array( 'question' => 'What if a payment fails?', 'answer' => 'Stripe tries again over the following days and emails you so you can update your card. If the last try fails, your listing drops to Basic with all your data kept.' ),
			array( 'question' => 'Do you take discount codes?', 'answer' => 'Yes. If we’ve given you a code, enter it on the Stripe Checkout page. Codes work on Hub plans only.' ),
			array( 'question' => 'What does a Local Partner get?', 'answer' => 'Everything in Featured, plus one Daily News advert each month and one Harrow Online Facebook Group post each month. Our team writes and schedules them with you.' ),
			array( 'question' => 'Can I list an event without a paid plan?', 'answer' => 'Yes. Any business can list a single event for {event_price}, with no plan or subscription. You pay when you submit it, we check it, and it stays on What’s On until the event date. Featured and Local Partner include unlimited events and classes, so they’re better value if you run more than one event a month. Schools, clubs, charities and community groups can open a free organiser account and add events and classes without paying. <a href="/hub/register/">Create a free account</a>.' ),
			array( 'question' => 'What is the difference between an event and a class?', 'answer' => 'An event happens on a set date, like a quiz night or a market, and leaves What’s On once it’s over. A class runs every week, like a dance class or a football session, and keeps the same page for as long as it runs.' ),
			array( 'question' => 'Can I manage more than one business?', 'answer' => 'For now, each account manages one business listing. If you run more than one business, <a href="https://harrowonline.org/contact-us/">contact us</a> and we’ll help.' ),
		),
		'pricing_cta_hide'              => '',
		'pricing_cta_title'             => 'Want to reach more of Harrow?',
		'pricing_cta_text'              => 'Advertise in Harrow Online news articles, or advertise in the newsletter on the dates you choose.',
		'pricing_cta_buttons'           => array(
			array( 'label' => 'Advertise in the newsletter', 'link' => 'advertise/newsletter/' ),
			array( 'label' => 'Other advertising', 'link' => 'news-site/advertise/' ),
		),
	);
}

/** Privacy notice (/privacy/): today's wording. The text itself is the page's editor (content/privacy.html to start). */
function hub_pages_rule_defaults_privacy() {
	return array(
		'privacy_crumb'       => 'Privacy notice',
		'privacy_title'       => 'Privacy notice',
		'privacy_lead'        => 'How Harrow Online Hub uses your information, who we share it with and the choices you have.',
		'privacy_toc_title'   => 'On this page',
		'privacy_updated'     => 'Last updated: 11 November 2026',
		'privacy_draft_hide'  => '',
		'privacy_draft_title' => 'Draft for legal review.',
		'privacy_draft_text'  => 'This notice has not yet been approved and may change before the Hub launches. Items marked [to confirm] need checking.',
	);
}

/** Terms of use (/terms/): today's wording. The text itself is the page's editor (content/terms.html to start). */
function hub_pages_rule_defaults_terms() {
	return array(
		'terms_crumb'       => 'Terms of use',
		'terms_title'       => 'Terms of use',
		'terms_lead'        => 'The rules for using Harrow Online Hub, whether you’re reading, listing a business or running events.',
		'terms_toc_title'   => 'On this page',
		'terms_updated'     => 'Last updated: 11 November 2026',
		'terms_draft_hide'  => '',
		'terms_draft_title' => 'Draft for legal review.',
		'terms_draft_text'  => 'These terms have not yet been approved and may change before the Hub launches. Items marked [to confirm] need checking.',
	);
}

/** The starting text of a document page (privacy, terms): content/{page}.html. */
function hub_pages_rule_default_body( $page ) {
	$file = get_template_directory() . '/content/' . sanitize_key( $page ) . '.html';
	return is_readable( $file ) ? (string) file_get_contents( $file ) : '';
}

/**
 * A document's headings (h2): each gets an id (kept if it has one) and a line in "On this page" (its text without a
 * leading "1." number). @return array [ html, toc: [ [ id, label ] ] ]
 */
function hub_pages_rule_toc( $html ) {
	$toc  = array();
	$used = array();
	$html = preg_replace_callback( '#<h2(\s[^>]*)?>(.*?)</h2>#s', function ( $m ) use ( &$toc, &$used ) {
		$attrs = isset( $m[1] ) ? $m[1] : '';
		$text  = trim( html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES, 'UTF-8' ) );
		if ( preg_match( '/\sid="([^"]+)"/', $attrs, $id ) ) {
			$id = $id[1];
		} else {
			$base = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( preg_replace( '/^\d+[.)]\s*/', '', $text ) ) ), '-' );
			$id   = '' !== $base ? $base : 'section';
			for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
				$id = $base . '-' . $n;
			}
			$attrs .= ' id="' . $id . '"';
		}
		$used[ $id ] = true;
		$toc[]       = array( $id, preg_replace( '/^\d+[.)]\s*/', '', $text ) );
		return '<h2' . $attrs . '>' . $m[2] . '</h2>';
	}, (string) $html );
	return array( $html, $toc );
}

/** Fill {placeholders} in every text of a page (lists included). */
function hub_pages_rule_fill( $value, array $vars ) {
	if ( is_array( $value ) ) {
		return array_map( function ( $v ) use ( $vars ) {
			return hub_pages_rule_fill( $v, $vars );
		}, $value );
	}
	return is_string( $value ) ? strtr( $value, $vars ) : $value;
}

/**
 * Pricing's comparison rows -> table rows. A cell: "yes" (tick), "no" (dash, read out "Not included"), "- None" (dash,
 * read out "None"), or text (links allowed). A row with only the first plan column filled spans all four.
 * @return array [ { heading: bool, feature, cells: [ { kind: yes|no|text, sr, html } ], span: bool } ]
 */
function hub_pages_rule_compare( array $rows ) {
	$out = array();
	foreach ( $rows as $r ) {
		if ( '1' === (string) $r['heading'] ) {
			$out[] = array( 'heading' => true, 'feature' => $r['feature'], 'cells' => array(), 'span' => false );
			continue;
		}
		$cols  = array( $r['unclaimed'], $r['basic'], $r['featured'], $r['partner'] );
		$span  = '' !== trim( $cols[0] ) && '' === trim( $cols[1] . $cols[2] . $cols[3] );
		$cells = array();
		foreach ( $span ? array( $cols[0] ) : $cols as $c ) {
			$c = trim( (string) $c );
			if ( in_array( strtolower( $c ), array( 'yes', '✓' ), true ) ) {
				$cells[] = array( 'kind' => 'yes', 'sr' => 'Included', 'html' => '' );
			} elseif ( 'no' === strtolower( $c ) || '-' === $c || '–' === $c ) {
				$cells[] = array( 'kind' => 'no', 'sr' => 'Not included', 'html' => '' );
			} elseif ( preg_match( '/^[-–]\s+(.+)$/u', $c, $m ) ) {
				$cells[] = array( 'kind' => 'no', 'sr' => $m[1], 'html' => '' );
			} else {
				$cells[] = array( 'kind' => 'text', 'sr' => '', 'html' => $c );
			}
		}
		$out[] = array( 'heading' => false, 'feature' => $r['feature'], 'cells' => $cells, 'span' => $span );
	}
	return $out;
}

/**
 * A quick link that only picks a date ("…/events/?when=weekend") -> that date ("weekend"), so the page can show it as
 * selected; '' for any other link.
 */
function hub_pages_rule_when( $link ) {
	parse_str( (string) wp_parse_url( (string) $link, PHP_URL_QUERY ), $q );
	return array( 'when' ) === array_keys( $q ) && is_string( $q['when'] ) ? $q['when'] : '';
}
