<?php
/**
 * Submissions: emails about an offer, event or class sent from the account pages, plain text: { subject, body }.
 *
 * @param string $kind 'staff' (new one to check) | 'live' | 'declined'
 * @param array  $a    { first_name, what ('offer' | 'event' | 'class'), title, by (business or organiser), link, account_email,
 *                      note (staff: about payment; declined: what staff wrote) }
 */
defined( 'ABSPATH' ) || exit;

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
				. ( '' !== $a['note'] ? "\n\nFrom our team:\n" . $a['note'] . "\n\nYou can add it again from your account:\n" : "\n\nReply to this email and we'll tell you what to change, or add it again from your account:\n" )
				. $a['link'] . $sign,
		);
	}
	return array(
		'subject' => 'New ' . $a['what'] . ' to check: ' . $a['title'],
		'body'    => $a['account_email'] . ' (' . $a['by'] . ') sent a new ' . $a['what'] . ', "' . $a['title'] . '"' . ( '' !== $a['note'] ? ' (' . $a['note'] . ')' : '' ) . ".\n\nCheck it, then Publish or move it to the Trash:\n" . $a['link'],
	);
}
