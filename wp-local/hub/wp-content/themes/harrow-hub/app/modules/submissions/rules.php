<?php
/**
 * Submissions: the rules (plain PHP, no WordPress). Offers, events and weekly classes added from the account pages
 * wait as "Pending Review" (or stay a draft) until staff publish them.
 */
defined( 'ABSPATH' ) || exit;

/** What can be sent: kind => [ post type, name, account list page ]. */
const HUB_SUBMISSIONS_KINDS = array(
	'offer' => array( 'hub_offer', 'offer', 'account/offers/' ),
	'event' => array( 'hub_event', 'event', 'account/events/' ),
	'class' => array( 'hub_class', 'class', 'account/classes/' ),
);

/** Event categories on the form: value => [ label (as on What's On), icon ]. */
const HUB_SUBMISSIONS_EVENT_CATS = array(
	'family'         => array( 'Family', 'family' ),
	'music'          => array( 'Music', 'music' ),
	'food-and-drink' => array( 'Food and drink', 'food' ),
	'community'      => array( 'Community', 'heart' ),
	'nightlife'      => array( 'Nightlife', 'beer' ),
);

/** Class types on the form: value => [ label (as on Classes), icon ]. */
const HUB_SUBMISSIONS_CLASS_TYPES = array(
	'childrens-activities' => array( 'Children’s activities', 'family' ),
	'dance'                => array( 'Dance schools', 'music' ),
	'fitness'              => array( 'Fitness', 'dumbbell' ),
	'workshops'            => array( 'Workshops', 'wrench' ),
	'other'                => array( 'Other regular classes', 'repeat' ),
);

const HUB_SUBMISSIONS_AGES      = array( 'All ages', 'Under 5s', 'Ages 5 to 11', 'Ages 12 to 17', 'Ages 16+', 'Adults', 'Over 60s' );
const HUB_SUBMISSIONS_LEVELS    = array( 'All levels', 'Beginners', 'Improvers', 'Advanced' );
const HUB_SUBMISSIONS_PRICE_PER = array( 'session' => 'a session', 'term' => 'a term', 'month' => 'a month' );
const HUB_SUBMISSIONS_DAYS      = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

/** Photo for an event or class: extension => MIME type, and the size limit. */
const HUB_SUBMISSIONS_PHOTO_TYPES = array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png' );
const HUB_SUBMISSIONS_PHOTO_MAX   = 5242880;   // 5MB

/** Post status -> what the owner sees. */
const HUB_SUBMISSIONS_STATUS_LABELS = array( 'draft' => 'Draft', 'pending' => 'Being checked', 'publish' => 'Live', 'trash' => 'Not approved' );

/**
 * May this account add this kind? (Pricing page: offers need Featured or Local Partner; events and classes need a
 * paid plan, or an organiser account; a Basic business can add a single event for £15.)
 *
 * @param string $type 'business' | 'organiser' | 'admin'
 * @param bool   $paid on Featured or Local Partner
 * @return string 'yes' | 'pay' (Basic event, £15) | 'upgrade' | 'no'
 */
function hub_submissions_rule_access( $kind, $type, $has_business, $paid ) {
	if ( 'organiser' === $type ) {
		return 'offer' === $kind ? 'no' : 'yes';
	}
	if ( ! $has_business ) {
		return 'no';
	}
	if ( $paid ) {
		return 'yes';
	}
	return 'event' === $kind ? 'pay' : 'upgrade';
}

/** "09:00" -> "9am", "18:30" -> "6:30pm". '' if it isn't a time. */
function hub_submissions_rule_time_label( $hhmm ) {
	if ( ! preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', (string) $hhmm, $m ) ) {
		return '';
	}
	$h = (int) $m[1];
	$p = $h >= 12 ? 'pm' : 'am';
	$h = 0 === $h % 12 ? 12 : $h % 12;
	return $h . ( '00' === $m[2] ? '' : ':' . $m[2] ) . $p;
}

function hub_submissions_rule_is_time( $hhmm ) {
	return '' !== hub_submissions_rule_time_label( $hhmm );
}

function hub_submissions_rule_is_date( $ymd ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $ymd );
	return $d && $d->format( 'Y-m-d' ) === $ymd;
}

/** A UK postcode, tidied, or '' if it doesn't look like one. */
function hub_submissions_rule_postcode( $pc ) {
	$pc = strtoupper( preg_replace( '/\s+/', '', (string) $pc ) );
	return preg_match( '/^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/', $pc ) ? substr( $pc, 0, -3 ) . ' ' . substr( $pc, -3 ) : '';
}

/** A class's price as shown: "Free", "£25 a session", "£25 a session, first class free". */
function hub_submissions_rule_class_price( $price, $per, $free_trial ) {
	$price = trim( (string) $price );
	if ( '' === $price ) {
		return 'Free';
	}
	if ( preg_match( '/^\d+(\.\d{1,2})?$/', $price ) ) {
		$price = '£' . $price;
	}
	$label = $price . ( isset( HUB_SUBMISSIONS_PRICE_PER[ $per ] ) && preg_match( '/\d/', $price ) ? ' ' . HUB_SUBMISSIONS_PRICE_PER[ $per ] : '' );
	return $free_trial ? $label . ', first class free' : $label;
}

/** Terms box -> list: one per line, blank lines dropped. */
function hub_submissions_rule_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $text ) ), 'strlen' ) );
}

/* ------------------------------------------------------------------ the account lists (account/offers/, events/, classes/) */

/**
 * Where each one stands -> [ its label, its status pill (hub.css .status--*), its tab ], in the tabs' order.
 *   live      published (and, for offers and events, not over yet)
 *   pending   sent, waiting for staff to check it
 *   draft     saved, not sent
 *   declined  staff declined it (it's in the Trash); it can be changed and sent again
 *   ended     published, but the offer's end date or the event's date has passed (classes don't end)
 */
const HUB_SUBMISSIONS_STATES = array(
	'live'     => array( 'Live', 'published', 'Live' ),
	'pending'  => array( 'Being checked', 'pending', 'Being checked' ),
	'draft'    => array( 'Draft', 'draft', 'Drafts' ),
	'declined' => array( 'Not approved', 'rejected', 'Not approved' ),
	'ended'    => array( 'Ended', 'ended', 'Ended' ),
);

/** The order of the list: what's waiting on the account first. */
const HUB_SUBMISSIONS_STATE_ORDER = array( 'declined', 'draft', 'pending', 'live', 'ended' );

/**
 * A post's place in the list, from its status. '' when it isn't listed: something staff took down (trashed from
 * anything but "pending") is gone for the account too.
 *
 * @param bool   $over         the offer's end date or the event's date has passed
 * @param string $trashed_from the status it had before the Trash
 */
function hub_submissions_rule_state( $status, $over, $trashed_from = '' ) {
	if ( 'trash' === $status ) {
		return 'pending' === $trashed_from ? 'declined' : '';
	}
	if ( 'publish' === $status ) {
		return $over ? 'ended' : 'live';
	}
	return 'pending' === $status ? 'pending' : 'draft';
}

/** Waiting for the account to change it: declined, or a declined one brought back as a draft and not sent yet. */
function hub_submissions_rule_needs_changes( $state, $note ) {
	return 'declined' === $state || ( 'draft' === $state && '' !== (string) $note );
}

/** List order: by HUB_SUBMISSIONS_STATE_ORDER, then by each row's "_sort" (an event's date, a class's day and time, newest first otherwise). */
function hub_submissions_rule_compare( array $a, array $b ) {
	return array( array_search( $a['state'], HUB_SUBMISSIONS_STATE_ORDER, true ), $a['_sort'] )
		<=> array( array_search( $b['state'], HUB_SUBMISSIONS_STATE_ORDER, true ), $b['_sort'] );
}

/** "12 Nov" for a timestamp, in UK time (with a non-breaking space, so it never splits across lines). */
function hub_submissions_rule_day_month( $ts ) {
	$d = ( new DateTimeImmutable( '@' . (int) $ts ) )->setTimezone( new DateTimeZone( 'Europe/London' ) );
	return $d->format( 'j' ) . "\u{00A0}" . $d->format( 'M' );
}

/** "Sat 21 Nov" for a date (Y-m-d), with non-breaking spaces. '' if it isn't one. */
function hub_submissions_rule_event_day( $ymd ) {
	if ( ! hub_submissions_rule_is_date( $ymd ) ) {
		return '';
	}
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd );
	return implode( "\u{00A0}", array( $d->format( 'D' ), $d->format( 'j' ), $d->format( 'M' ) ) );
}
