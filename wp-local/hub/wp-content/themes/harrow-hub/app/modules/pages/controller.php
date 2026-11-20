<?php
/**
 * Page text: the editable wording of the Hub's pages (What's On, Classes and activities, Local Offers, Pricing, Privacy,
 * Terms, Data sources, Advertise in the newsletter; see rules.php to add one).
 */
defined( 'ABSPATH' ) || exit;

/**
 * A page's text, for its template and the app.
 *
 * @param string $page A key of HUB_PAGES ("events").
 * @return array|WP_REST_Response|WP_Error|null  every field of the page (shape.php)
 */
function hub_pages_text( $page, $mode = HUB_WEB ) {
	if ( ! isset( HUB_PAGES[ $page ] ) ) {
		return hub_respond_error( 'hub_page_unknown', 'That page has no editable text.', $mode );
	}
	$id = hub_pages_page_id( $page );
	return hub_respond( hub_pages_shape( $page, $id ? hub_pages_db_fields( $id, array_keys( hub_pages_rule_defaults( $page ) ) ) : array() ), $mode );
}

/** The WordPress Page's ID for a page key (0 when that Page does not exist). */
function hub_pages_page_id( $page ) {
	return isset( HUB_PAGES[ $page ] ) ? hub_pages_db_page_id( HUB_PAGES[ $page ] ) : 0;
}

/** Which page key a WordPress Page is ("events"), or '' when it has no editable text. */
function hub_pages_for_post( $post_id ) {
	foreach ( array_keys( HUB_PAGES ) as $page ) {
		if ( $post_id && (int) $post_id === hub_pages_page_id( $page ) ) {
			return $page;
		}
	}
	return '';
}

/** A page's fields for the wp-admin box: what is saved, with the default for anything not saved. */
function hub_pages_fields( $page ) {
	$defaults = hub_pages_rule_defaults( $page );
	$id       = hub_pages_page_id( $page );
	$saved    = $id ? hub_pages_db_fields( $id, array_keys( $defaults ) ) : array();
	$out      = array();
	foreach ( $defaults as $k => $default ) {
		$v         = isset( $saved[ $k ] ) ? $saved[ $k ] : '';
		$out[ $k ] = ( '' === $v || array() === $v ) ? $default : $v;
	}
	return $out;
}

/** Save a page's fields (already cleaned by the wp-admin box). */
function hub_pages_save_fields( $page, array $values ) {
	hub_pages_db_save_fields( hub_pages_page_id( $page ), array_keys( hub_pages_rule_defaults( $page ) ), $values );
}

/**
 * A document page (privacy, terms): its text from the page's editor (or content/{page}.html while it is empty), as the
 * page shows it, and the "On this page" list from its headings.
 * @return array|WP_REST_Response { html, toc: [ [ id, label ] ] }
 */
function hub_pages_document( $page, $mode = HUB_WEB ) {
	$raw = hub_pages_db_body( hub_pages_page_id( $page ) );
	if ( '' === trim( $raw ) ) {
		$raw = hub_pages_rule_default_body( $page );
	}
	list( $html, $toc ) = hub_pages_rule_toc( hub_html_links( apply_filters( 'the_content', $raw ) ) );
	return hub_respond( array( 'html' => $html, 'toc' => $toc ), $mode );
}

/**
 * Pricing: the page's text with the prices filled in from the billing module.
 * @return array|WP_REST_Response { text, plans: { featured|partner: { name, monthly, annual, annual_per_month, note_monthly, note_annual } }, event_price, compare }
 */
function hub_pages_pricing( $mode = HUB_WEB ) {
	$t     = hub_pages_text( 'pricing' );
	$cat   = hub_billing_catalogue();   // billing module
	$event = '';
	foreach ( $cat['items'] as $i ) {
		if ( 'event_listing' === $i['key'] ) {
			$event = $i['price'];
		}
	}
	$vars  = array( '{event_price}' => $event );
	$plans = array();
	foreach ( $cat['plans'] as $p ) {
		$vars[ '{' . $p['key'] . '_monthly}' ] = $p['monthly'];
		$vars[ '{' . $p['key'] . '_annual}' ]  = $p['annual'];
		$plans[ $p['key'] ]                    = $p;
	}
	$t = hub_pages_rule_fill( $t, $vars );
	foreach ( $plans as $key => $p ) {
		$plans[ $key ]['note_monthly'] = strtr( $t['pricing_paid_note_monthly'], array( '{annual}' => $p['annual'], '{per_month}' => $p['annual_per_month'] ) );
		$plans[ $key ]['note_annual']  = strtr( $t['pricing_paid_note_annual'], array( '{annual}' => $p['annual'], '{per_month}' => $p['annual_per_month'] ) );
	}
	return hub_respond( array( 'text' => $t, 'plans' => $plans, 'event_price' => $event, 'compare' => hub_pages_rule_compare( $t['pricing_compare_rows'] ) ), $mode );
}

/** Document pages with an empty editor get their starting text (content/{page}.html). Nothing is overwritten. */
function hub_pages_setup_bodies() {
	foreach ( HUB_PAGES_DOCUMENTS as $page ) {
		$id = hub_pages_page_id( $page );
		if ( $id && '' === trim( hub_pages_db_body( $id ) ) ) {
			hub_pages_db_set_body( $id, hub_pages_rule_default_body( $page ) );
		}
	}
}

/**
 * Advertise in the newsletter: the page's text with the prices filled in (billing module), the signed-in account
 * (account module) and the booking calendars from today's date.
 * @return array|WP_REST_Response { text, prices: { day, week, weekend, business: { label "£45", number "45", each "£45.00" }, save },
 *         account: { signed_in, name, plan_label, icon, website }, calendars: { days, weeks, weekend, business }, days_from, days_to, preview_date }
 */
function hub_pages_advert( $mode = HUB_WEB ) {
	$t     = hub_pages_text( 'advertise' );
	$items = array();
	foreach ( hub_billing_catalogue()['items'] as $i ) {   // billing module
		$items[ $i['key'] ] = $i;
	}
	$amount = function ( $key ) {
		$items = hub_billing_items();   // billing module: the prices in force
		return isset( $items[ $key ] ) ? (int) $items[ $key ]['amount'] : 0;
	};
	$price  = function ( $key ) use ( $amount ) {
		$p = $amount( $key );
		return array( 'label' => hub_billing_rule_money( $p ), 'number' => rtrim( rtrim( number_format( $p / 100, 2, '.', '' ), '0' ), '.' ), 'each' => '£' . number_format( $p / 100, 2 ) );
	};
	$prices = array(
		'day'      => $price( 'sponsor_daily_day' ),
		'week'     => $price( 'sponsor_daily_week' ),
		'weekend'  => $price( 'sponsor_weekend' ),
		'business' => $price( 'sponsor_business' ),
		'save'     => hub_billing_rule_money( max( 0, 5 * $amount( 'sponsor_daily_day' ) - $amount( 'sponsor_daily_week' ) ) ),
	);
	$t = hub_pages_rule_fill( $t, array( '{day}' => $prices['day']['label'], '{week}' => $prices['week']['label'], '{save}' => $prices['save'], '{weekend}' => $prices['weekend']['label'], '{business}' => $prices['business']['label'] ) );

	$no_issue = array( 'daily' => array(), 'weekend' => array(), 'business' => array() );
	foreach ( $t['advertise_no_issue'] as $row ) {
		$ed = strtolower( trim( $row['edition'] ) );
		if ( isset( $no_issue[ $ed ] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', trim( $row['date'] ) ) ) {
			$no_issue[ $ed ][ trim( $row['date'] ) ] = true;
		}
	}
	$earliest  = hub_pages_rule_advert_earliest( hub_today() );
	$calendars = array(
		'days'     => hub_pages_rule_advert_slots( $earliest, 'days', $no_issue['daily'] ),
		'weeks'    => hub_pages_rule_advert_slots( $earliest, 'weeks', $no_issue['daily'] ),
		'weekend'  => hub_pages_rule_advert_slots( $earliest, 'weekend', $no_issue['weekend'] ),
		'business' => hub_pages_rule_advert_slots( $earliest, 'business', $no_issue['business'] ),
	);
	$first = reset( $calendars['days'] );
	$last  = end( $calendars['days'] );
	$from  = date_create_immutable( $first['date'] );
	$to    = date_create_immutable( $last['date'] );
	$t['advertise_days_heading'] = strtr( $t['advertise_days_heading'], array(
		'{from}' => $from->format( 'Y' ) === $to->format( 'Y' ) ? $from->format( 'j F' ) : $from->format( 'j F Y' ),
		'{to}'   => $to->format( 'j F Y' ),
	) );
	$preview = $earliest;
	foreach ( $calendars['days'] as $d ) {
		if ( 'open' === $d['state'] ) {
			$preview = date_create_immutable( $d['date'] );
			break;
		}
	}

	$acc      = hub_account_current();   // account module
	$business = ! empty( $acc['business'] ) ? $acc['business'] : null;
	return hub_respond( array(
		'text'         => $t,
		'prices'       => $prices,
		'account'      => array(
			'signed_in'  => ! empty( $acc['signed_in'] ),
			'name'       => $business ? $business['name'] : ( isset( $acc['name'] ) ? $acc['name'] : '' ),
			'plan_label' => isset( $acc['plan_label'] ) ? $acc['plan_label'] : '',
			'icon'       => $business && ! empty( $business['icon'] ) ? $business['icon'] : 'store',
			'website'    => $business && ! empty( $business['website'] ) ? $business['website'] : '',
		),
		'calendars'    => $calendars,
		'preview_date' => $preview->format( 'l j F' ),
	), $mode );
}
