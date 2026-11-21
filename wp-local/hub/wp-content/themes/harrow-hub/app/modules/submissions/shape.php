<?php
/**
 * Submissions: the shapes pages, the app and emails get.
 *
 * row (one line of an account's offers, events or classes list; text is plain, URLs full and unescaped):
 *   id, kind ('offer' | 'event' | 'class'), title,
 *   state ('live' | 'pending' | 'draft' | 'declined' | 'ended'), state_label ("Being checked"), state_class (hub.css .status--*),
 *   line   when it is and where it stands: "Sat 21 Nov, 2pm · Sent 10 Nov", "Ends 31 Dec", "Every Tuesday, 6:30pm · Ages 16+"
 *   weekly (bool: a class), featured (bool), note (what staff asked to change, or ''), needs_changes (bool),
 *   mine   (bool) sent from this account; false = put on its business listing by Harrow Online (changed by emailing us)
 *   action { label ('Edit' | 'Edit and resend' | 'View'), url, icon } | null
 *
 * email: { subject, body }, plain text.
 */
defined( 'ABSPATH' ) || exit;

/**
 * @param string $kind  'staff' (new one to check) | 'live' | 'declined'
 * @param array  $a     { first_name, what ('offer' | 'event' | 'class'), title, by (business or organiser), link, account_email,
 *                       note (staff: about payment; declined: what staff wrote) }
 */
function hub_submissions_shape_email( $kind, array $a ) {
	$hi   = 'Hi ' . ( '' !== $a['first_name'] ? $a['first_name'] : 'there' ) . ",\n\n";
	$sign = "\n\nThanks,\nThe Harrow Online team\nhub@harrowonline.org";
	if ( 'live' === $kind ) {
		return array(
			'subject' => 'Your ' . $a['what'] . ' is live: ' . $a['title'],
			'body'    => $hi . 'We\'ve checked your ' . $a['what'] . ' "' . $a['title'] . '" and it\'s now on the Harrow Online Hub:' . "\n" . $a['link'] . $sign,
		);
	}
	if ( 'declined' === $kind ) {
		return array(
			'subject' => 'About your ' . $a['what'] . ': ' . $a['title'],
			'body'    => $hi . 'We couldn\'t publish your ' . $a['what'] . ' "' . $a['title'] . '" on the Harrow Online Hub this time.'
				. ( '' !== $a['note'] ? "\n\nFrom our team:\n" . $a['note'] . "\n\nYou can change it and send it again from your account:\n" : "\n\nReply to this email and we'll tell you what to change, or change it and send it again from your account:\n" )
				. $a['link'] . $sign,
		);
	}
	return array(
		'subject' => 'New ' . $a['what'] . ' to check: ' . $a['title'],
		'body'    => $a['account_email'] . ' (' . $a['by'] . ') sent a new ' . $a['what'] . ', "' . $a['title'] . '"' . ( '' !== $a['note'] ? ' (' . $a['note'] . ')' : '' ) . ".\n\nCheck it, then Publish or move it to the Trash:\n" . $a['link'],
	);
}

/**
 * One row of an account's list.
 *
 * @param string $kind     'offer' | 'event' | 'class'
 * @param array  $d        The item in its module's shape (offers module: offer; events module: event or class).
 * @param string $state    hub_submissions_rule_state()
 * @param bool   $mine     Sent from this account (else put on its listing by Harrow Online).
 * @param string $note     What staff asked to change ('' when nothing).
 * @param array  $times    { saved, declined } timestamps (hub_submissions_db_times()); 0 when not known.
 * @param string $edit_url Its edit page (mine only).
 * @return array The row, plus "_sort" (the controller removes it).
 */
function hub_submissions_shape_row( $kind, array $d, $state, $mine, $note, array $times, $edit_url ) {
	$parts = array();
	if ( 'event' === $kind ) {
		$parts[] = trim( hub_submissions_rule_event_day( $d['date'] ) . ( '' !== $d['time'] ? ', ' . $d['time'] : '' ), ', ' );
	} elseif ( 'class' === $kind ) {
		$parts[] = $d['card']['when'];
		$parts[] = $d['age'];
	} elseif ( in_array( $state, array( 'live', 'ended' ), true ) ) {
		$parts[] = $d['ends']['label'];   // "Ends 31 Dec", "No end date", "Ended 4 Nov"
	}
	if ( 'pending' === $state ) {
		$parts[] = $times['saved'] ? 'Sent ' . hub_submissions_rule_day_month( $times['saved'] ) : 'Sent';
	} elseif ( 'draft' === $state ) {
		$parts[] = '' !== $note ? 'Changes asked for' : 'Not sent yet';
	} elseif ( 'declined' === $state ) {
		$parts[] = $times['declined'] ? 'Not approved ' . hub_submissions_rule_day_month( $times['declined'] ) : 'Not approved';
	} elseif ( 'ended' === $state && 'event' === $kind ) {
		$parts[] = 'Ended';
	}
	if ( ! $mine ) {
		$parts[] = 'Added by Harrow Online';
	}

	$action = null;
	if ( $mine && in_array( $state, array( 'draft', 'pending', 'declined' ), true ) ) {
		$action = array( 'label' => 'declined' === $state ? 'Edit and resend' : 'Edit', 'url' => $edit_url, 'icon' => 'edit' );
	} elseif ( in_array( $state, array( 'live', 'ended' ), true ) && ! empty( $d['url'] ) ) {
		$action = array( 'label' => 'View', 'url' => $d['url'], 'icon' => 'external' );
	}

	if ( 'event' === $kind ) {
		$sort = $d['date'];
	} elseif ( 'class' === $kind ) {
		$sort = sprintf( '%d-%05.2f', null === $d['weekday'] ? 9 : $d['weekday'], $d['sort'] );
	} else {
		$sort = -$times['saved'];   // newest first
	}
	return array(
		'id'            => (int) $d['id'],
		'kind'          => $kind,
		'title'         => $d['title'],
		'state'         => $state,
		'state_label'   => HUB_SUBMISSIONS_STATES[ $state ][0],
		'state_class'   => HUB_SUBMISSIONS_STATES[ $state ][1],
		'line'          => implode( ' · ', array_filter( $parts, 'strlen' ) ),
		'weekly'        => 'class' === $kind,
		'featured'      => ! empty( $d['featured'] ),
		'note'          => (string) $note,
		'needs_changes' => hub_submissions_rule_needs_changes( $state, $note ),
		'mine'          => (bool) $mine,
		'action'        => $action,
		'_sort'         => $sort,
	);
}
