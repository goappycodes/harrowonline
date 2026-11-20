<?php
/**
 * Homepage (the WordPress Page set as the front page, "Home"): the only file that reads or writes its fields.
 *
 * Every field is plain post meta on that Page (home_*), edited in wp-admin's "Homepage" box (inc/admin/home-box.php).
 * Lists are arrays in one meta key each. An empty field means "use the default" (rules.php); the "…_hide" switches
 * hide a section.
 */
defined( 'ABSPATH' ) || exit;

const HUB_HOME_FIELDS = array(
	// First screen
	'home_hero_eyebrow', 'home_hero_title', 'home_hero_title_em', 'home_hero_lead', 'home_search_placeholder', 'home_search_button',
	'home_popular_label', 'home_popular', 'home_tiles', 'home_add_buttons', 'home_ticker_hide',
	// This weekend (two events, the offer, the newsletter card)
	'home_weekend_hide', 'home_weekend_title', 'home_weekend_link_label', 'home_weekend_link', 'home_weekend_offer',
	'home_nl_hide', 'home_nl_title', 'home_nl_text', 'home_nl_editions', 'home_nl_placeholder', 'home_nl_button',
	// More Local Offers
	'home_offers_hide', 'home_offers_title', 'home_offers_sub', 'home_offers_link_label', 'home_offers_link',
	// The numbers
	'home_stats_hide', 'home_stat_offers_label', 'home_stat_offers_note', 'home_stat_events_label', 'home_stat_events_note',
	'home_stat_classes_label', 'home_stat_classes_note', 'home_stat_biz_label', 'home_stat_biz_note',
	// Featured local businesses
	'home_featured_hide', 'home_featured_title', 'home_featured_sub', 'home_featured_link_label', 'home_featured_link', 'home_featured_pick',
	// Popular categories
	'home_cats_hide', 'home_cats_title', 'home_cats_sub', 'home_cats_link_label', 'home_cats_link', 'home_cats_pick',
	// Browse by area, and the "hidden gem" box under it
	'home_areas_hide', 'home_areas_title', 'home_areas_sub',
	'home_gem_hide', 'home_gem_eyebrow', 'home_gem_title', 'home_gem_text', 'home_gem_button', 'home_gem_link',
	// For businesses
	'home_biz_hide', 'home_biz_eyebrow', 'home_biz_title', 'home_biz_lead', 'home_biz_buttons', 'home_biz_note',
	'home_biz_note_link_label', 'home_biz_note_link', 'home_biz_steps',
);

/** The homepage's Page ID (Settings > Reading > Homepage), or 0. */
function hub_home_db_page_id() {
	return 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
}

/** Every field's stored value ('' / array() when not set). */
function hub_home_db_fields() {
	$id  = hub_home_db_page_id();
	$out = array();
	foreach ( HUB_HOME_FIELDS as $k ) {
		$out[ $k ] = $id ? get_post_meta( $id, $k, true ) : '';
	}
	return $out;
}

/** Save the fields (already cleaned by the wp-admin box). Empty values are removed rather than stored. */
function hub_home_db_save_fields( array $values ) {
	$id = hub_home_db_page_id();
	if ( $id ) {
		hub_db_save_meta( $id, HUB_HOME_FIELDS, $values );
	}
}
