<?php
/**
 * Homepage rules: the default wording (used for any field left empty) and how links are written.
 */
defined( 'ABSPATH' ) || exit;

/** How many cards the homepage shows. */
const HUB_HOME_FEATURED   = 4;
const HUB_HOME_CATEGORIES = 6;
const HUB_HOME_OFFERS     = 6;   // More Local Offers tiles

/** The default for every field: today's homepage. Lists are rows of plain values. */
function hub_home_rule_defaults() {
	return array(
		'home_seo_title'          => 'Harrow Online Hub: local businesses, offers and what\'s on in Harrow',
		'home_seo_description'    => 'Discover what\'s happening in Harrow. Find local businesses, events, classes and exclusive offers from across the borough, from Harrow Online.',
		'home_hero_eyebrow'       => 'Harrow Online Hub',
		'home_hero_title'         => 'Discover what’s happening',
		'home_hero_title_em'      => 'in Harrow',
		'home_hero_lead'          => 'Find local businesses, events, classes and exclusive offers from across the borough.',
		'home_search_placeholder' => 'Plumber, Indian restaurant…',
		'home_search_button'      => 'Search',
		'home_popular_label'      => 'Popular:',
		'home_popular'            => array(
			array( 'label' => 'Restaurants', 'link' => 'category/restaurants/' ),
			array( 'label' => 'Hair & Beauty', 'link' => 'category/hair-and-beauty/' ),
			array( 'label' => 'Home & Garden', 'link' => 'category/home-and-garden/' ),
			array( 'label' => 'Dentists', 'link' => 'category/doctors-dentists-and-clinics/' ),
		),
		'home_tiles'              => array(
			array( 'icon' => 'store', 'title' => 'Find a business', 'text' => 'Shops, trades and services', 'link' => 'search/' ),
			array( 'icon' => 'calendar', 'title' => 'What’s On', 'text' => 'Events and days out', 'link' => 'events/' ),
			array( 'icon' => 'repeat', 'title' => 'Classes & clubs', 'text' => 'Regular activities for all ages', 'link' => 'events/classes/' ),
			array( 'icon' => 'tag', 'title' => 'Local offers', 'text' => 'Deals from Harrow businesses', 'link' => 'offers/' ),
		),
		'home_add_buttons'        => array(
			array( 'label' => 'Add your event', 'link' => 'register/' ),
			array( 'label' => 'Add your business', 'link' => 'add-business/' ),
		),
		'home_weekend_title'      => 'This weekend in Harrow',
		'home_weekend_link_label' => 'Everything this weekend',
		'home_weekend_link'       => 'events/?when=weekend',
		'home_nl_title'           => 'Get Harrow’s best events and local offers every Thursday',
		'home_nl_text'            => 'Free. Pick the newsletters you want; each one is separate.',
		'home_nl_editions'        => array(
			array( 'value' => 'daily', 'label' => 'Daily News', 'note' => '(Daily)', 'checked' => '' ),
			array( 'value' => 'weekend', 'label' => 'The Weekend', 'note' => '(Thu)', 'checked' => '1' ),
			array( 'value' => 'business', 'label' => 'Harrow Business', 'note' => '(Mon)', 'checked' => '' ),
		),
		'home_nl_placeholder'     => 'Your email address',
		'home_nl_button'          => 'Sign up free',
		'home_offers_title'       => 'More Local Offers',
		'home_offers_sub'         => 'Deals from Harrow businesses. Some are just for newsletter subscribers.',
		'home_offers_link_label'  => 'All offers',
		'home_offers_link'        => 'offers/',
		'home_stat_offers_label'  => 'Offers live',
		'home_stat_offers_note'   => 'and counting',
		'home_stat_events_label'  => 'This week',
		'home_stat_events_note'   => 'events across Harrow',
		'home_stat_classes_label' => 'Every week',
		'home_stat_classes_note'  => 'regular classes',
		'home_stat_biz_label'     => 'Local businesses',
		'home_stat_biz_note'      => 'across {areas} areas',
		'home_featured_title'     => 'Featured local businesses',
		'home_featured_sub'       => 'Featured and Local Partner businesses on the Hub.',
		'home_featured_link_label' => 'Get featured',
		'home_featured_link'      => 'pricing/',
		'home_cats_title'         => 'Popular categories',
		'home_cats_sub'           => 'The categories with the most Harrow businesses.',
		'home_cats_link_label'    => 'View all categories',
		'home_cats_link'          => 'categories/',
		'home_areas_title'        => 'Browse by area',
		'home_areas_sub'          => 'From Pinner to Edgware, find what’s near you.',
		'home_gem_eyebrow'        => 'Local favourites',
		'home_gem_title'          => 'Know a hidden gem?',
		'home_gem_text'           => 'Tell us about a Harrow business you love. We’re always looking for great local places.',
		'home_gem_button'         => 'Recommend a business',
		'home_gem_link'           => 'add-business/?recommend=1',
		'home_biz_eyebrow'        => 'For businesses',
		'home_biz_title'          => 'Run a business in Harrow?',
		'home_biz_lead'           => 'Claim your free listing, publish Local Offers and reach Harrow Online readers.',
		'home_biz_buttons'        => array(
			array( 'label' => 'Find my business', 'link' => 'search/?type=businesses' ),
			array( 'label' => 'See business plans', 'link' => 'pricing/' ),
		),
		'home_biz_note'           => 'Plans from free to Local Partner.',
		'home_biz_note_link_label' => 'See what’s included',
		'home_biz_note_link'      => 'pricing/',
		'home_biz_steps'          => array(
			array( 'title' => 'Claim or add your listing', 'text' => 'Most Harrow businesses are already listed. Find yours and claim it free, or add it in two minutes.' ),
			array( 'title' => 'Add events, classes and offers', 'text' => 'On the Featured or Local Partner plan, your events go on What’s On, weekly classes on Classes and deals on Offers.' ),
			array( 'title' => 'Manage everything in one place', 'text' => 'One account for your listing, events, classes, offers and plan, with a checklist to get you started.' ),
		),
	);
}

/** The categories shown when none are picked: today's six. */
const HUB_HOME_DEFAULT_CATEGORIES = array( 'shops', 'restaurants', 'doctors-dentists-and-clinics', 'hair-and-beauty', 'motoring', 'finance-and-accountants' );

/**
 * A link as typed in wp-admin -> full URL. "events/classes/" or "/hub/events/" is a Hub page; a full web address,
 * "mailto:" or "#…" is used as it is.
 */
function hub_home_rule_link( $link ) {
	$link = trim( (string) $link );
	if ( '' === $link || preg_match( '#^(https?:|mailto:|tel:|\#)#', $link ) ) {
		return $link;
	}
	$path = ltrim( $link, '/' );
	$base = trim( (string) wp_parse_url( hub_url(), PHP_URL_PATH ), '/' );   // "hub"
	if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	return hub_url( $path );
}

/** The width of an area's bar: its count against the biggest area, as a whole percentage. */
function hub_home_rule_bar( $count, $max ) {
	return $max > 0 ? (int) round( 100 * $count / $max ) : 0;
}
