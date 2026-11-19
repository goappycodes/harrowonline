<?php
/**
 * Claims: the array shapes pages, wp-admin and the app get, and the claim emails.
 *
 * claim:
 *   id, status ('pending' | 'approved' | 'rejected'), status_label, submitted ('Y-m-d H:i:s'),
 *   method ('email' | 'phone' | 'document'), method_label, role_label, phone, note, decision_note,
 *   business { id, name, url, category, address, postcode, area } | null,
 *   account { id, name, email } | null,
 *   evidence (staff only, else null): { email, email_verified, ask_for, call_times[], call_number, doc_type, has_document }
 */
defined( 'ABSPATH' ) || exit;

function hub_claim_shape( array $c, $business, $user, $for_staff ) {
	$d = isset( $c['details'] ) ? (array) $c['details'] : array();
	return array(
		'id'            => (int) $c['id'],
		'status'        => $c['status'],
		'status_label'  => HUB_CLAIMS_STATUSES[ $c['status'] ],
		'submitted'     => $c['submitted'],
		'method'        => $c['method'],
		'method_label'  => HUB_CLAIMS_METHODS[ $c['method'] ],
		'role_label'    => isset( HUB_CLAIMS_ROLES[ $c['role'] ] ) ? HUB_CLAIMS_ROLES[ $c['role'] ] : '',
		'phone'         => (string) $c['phone'],
		'note'          => (string) $c['note'],
		'decision_note' => isset( $c['decision_note'] ) ? (string) $c['decision_note'] : '',
		'business'      => $business ? array(
			'id'       => $business['id'],
			'name'     => $business['name'],
			'url'      => $business['url'],
			'category' => $business['category']['name'],
			'address'  => $business['address']['street'],
			'postcode' => $business['address']['postcode'],
			'area'     => $business['area']['name'],
		) : null,
		'account'       => $user ? array( 'id' => $user->ID, 'name' => $user->display_name, 'email' => $user->user_email ) : null,
		'evidence'      => $for_staff ? array(
			'email'          => isset( $d['email'] ) ? $d['email'] : '',
			'email_verified' => ! empty( $c['email_verified'] ),
			'ask_for'        => isset( $d['ask_for'] ) ? $d['ask_for'] : '',
			'call_times'     => array_values( array_intersect_key( HUB_CLAIMS_CALL_TIMES, array_flip( isset( $d['call_times'] ) ? (array) $d['call_times'] : array() ) ) ),
			'call_number'    => isset( $d['call_number'] ) ? $d['call_number'] : '',
			'doc_type'       => isset( $d['doc_type'], HUB_CLAIMS_DOC_TYPES[ $d['doc_type'] ] ) ? HUB_CLAIMS_DOC_TYPES[ $d['doc_type'] ] : '',
			'has_document'   => ! empty( $d['document'] ),
		) : null,
	);
}

/**
 * Claim emails, plain text: { subject, body }.
 *
 * @param string $kind 'verify' (to the business address), 'approved', 'rejected' (to the account), 'staff' (new claim)
 * @param array  $a    { first_name, business, link, note, method_label, account_email }
 */
function hub_claims_shape_email( $kind, array $a ) {
	$hi   = 'Hi ' . ( '' !== $a['first_name'] ? $a['first_name'] : 'there' ) . ",\n\n";
	$sign = "\n\nThanks,\nThe Harrow Online team\nhub@harrowonline.org";
	switch ( $kind ) {
		case 'verify':
			return array(
				'subject' => 'Confirm your claim for ' . $a['business'] . ' on Harrow Online Hub',
				'body'    => "Hi,\n\n" . 'Someone with a Harrow Online Hub account (' . $a['account_email'] . ') asked to manage the listing for ' . $a['business'] . ' and gave this address to prove it.'
					. "\n\nIf that was you, confirm here:\n" . $a['link']
					. "\n\nThe link works for " . HUB_CLAIMS_VERIFY_DAYS . " days. Our team then checks the claim, usually the same day.\n\nIf it wasn't you, ignore this email and nothing changes." . $sign,
			);
		case 'approved':
			return array(
				'subject' => 'Your claim for ' . $a['business'] . ' is approved',
				'body'    => $hi . 'Good news: you now manage ' . $a['business'] . ' on the Harrow Online Hub, on the free Basic plan, with a verified badge.'
					. "\n\nStart with your description, opening hours and a photo:\n" . $a['link']
					. "\n\nWant more photos, Local Offers, events and a place at the top of your category? You can upgrade any time from your account." . $sign,
			);
		case 'rejected':
			return array(
				'subject' => 'About your claim for ' . $a['business'],
				'body'    => $hi . 'We couldn\'t approve your claim for ' . $a['business'] . ' on the Harrow Online Hub.'
					. ( '' !== $a['note'] ? "\n\nOur note:\n" . $a['note'] : '' )
					. "\n\nYou can try again with a different way of proving it's yours:\n" . $a['link']
					. "\n\nOr reply to this email and we'll help." . $sign,
			);
	}
	return array(
		'subject' => 'New claim to check: ' . $a['business'],
		'body'    => $a['account_email'] . ' asked to manage ' . $a['business'] . ' (' . $a['method_label'] . ").\n\nCheck it in wp-admin:\n" . $a['link'],
	);
}
