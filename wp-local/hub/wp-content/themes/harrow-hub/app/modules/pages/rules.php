<?php
/**
 * Page text rules: which pages have editable text (What's On, Classes and activities), and the default wording of each (used for any field left empty).
 *
 * To give another page a "Page text" box: add it to HUB_PAGES, write hub_pages_rule_defaults_{page}() here and
 * hub_admin_page_text_{page}() in inc/admin/page-text-box.php, then read hub_pages_text( '{page}' ) in its template.
 */
defined( 'ABSPATH' ) || exit;

/** Pages with editable text: key => the WordPress Page's path. */
const HUB_PAGES = array(
	'events'  => 'events',           // What's On (page-templates/events.php)
	'classes' => 'events/classes',   // Classes and activities (page-templates/classes.php)
);

/** Every field of a page with its default: text, '' for the "…_hide" switches, rows for lists. */
function hub_pages_rule_defaults( $page ) {
	$fn = 'hub_pages_rule_defaults_' . str_replace( '-', '_', (string) $page );
	return isset( HUB_PAGES[ $page ] ) && function_exists( $fn ) ? $fn() : array();
}

/** What's On (/events/): today's wording. */
function hub_pages_rule_defaults_events() {
	return array(
		// Search engines
		'events_seo_title'          => 'What\'s On in Harrow: events this week and weekend',
		'events_seo_description'    => 'Events, things to do and classes across Harrow this week and weekend: family days out, live music, markets and community events.',
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
		// Search engines
		'classes_seo_title'        => 'Classes and activities in Harrow',
		'classes_seo_description'  => 'Weekly classes and activities across Harrow: children\'s clubs, fitness classes, dance schools, workshops and evening courses. Filter by age, area and day.',
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

/**
 * A quick link that only picks a date ("…/events/?when=weekend") -> that date ("weekend"), so the page can show it as
 * selected; '' for any other link.
 */
function hub_pages_rule_when( $link ) {
	parse_str( (string) wp_parse_url( (string) $link, PHP_URL_QUERY ), $q );
	return array( 'when' ) === array_keys( $q ) && is_string( $q['when'] ) ? $q['when'] : '';
}
