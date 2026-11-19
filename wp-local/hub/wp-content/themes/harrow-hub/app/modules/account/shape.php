<?php
/**
 * Accounts: the one array shape pages and the app get.
 *
 * account:
 *   signed_in (bool), id, name, first_name, email, type ('business' | 'organiser' | 'admin'),
 *   business: business summary (businesses module shape) | null,
 *   plan ('none' | 'basic' | 'enhanced' | 'premium' | 'partner'), plan_label ('Basic', 'Featured', 'Local Partner'),
 *   paid (bool: on Featured or Local Partner)
 *
 * Never holds passwords, Stripe IDs or anything private beyond the user's own name and email.
 */
defined( 'ABSPATH' ) || exit;

/** The signed-out shape. */
function hub_account_shape_guest() {
	return array(
		'signed_in'  => false,
		'id'         => 0,
		'name'       => '',
		'first_name' => '',
		'email'      => '',
		'type'       => '',
		'business'   => null,
		'plan'       => 'none',
		'plan_label' => '',
		'paid'       => false,
		'email_confirmed' => false,
	);
}

/**
 * @param WP_User    $u
 * @param string     $type      hub_account_db_type()
 * @param array|null $business  Business summary, or null.
 * @param bool       $email_confirmed
 */
function hub_account_shape( WP_User $u, $type, $business, $email_confirmed = true ) {
	$plan   = $business ? ( 'unclaimed' === $business['plan'] ? 'basic' : $business['plan'] ) : 'none';
	$labels = array( 'basic' => 'Basic', 'enhanced' => 'Featured', 'premium' => 'Featured', 'partner' => 'Local Partner' );
	return array(
		'signed_in'  => true,
		'id'         => (int) $u->ID,
		'name'       => $u->display_name,
		'first_name' => '' !== $u->first_name ? $u->first_name : $u->display_name,
		'email'      => $u->user_email,
		'type'       => $type,
		'business'   => $business,
		'plan'       => $plan,
		'plan_label' => isset( $labels[ $plan ] ) ? $labels[ $plan ] : '',
		'paid'       => in_array( $plan, array( 'enhanced', 'premium', 'partner' ), true ),
		'email_confirmed' => (bool) $email_confirmed,
	);
}

/** The password reset email, as plain text: { subject, body }. $link opens the forgot-password page's second step. */
function hub_account_shape_reset_email( $first_name, $link ) {
	return array(
		'subject' => 'Reset your Harrow Online Hub password',
		'body'    => 'Hi ' . ( '' !== $first_name ? $first_name : 'there' ) . ",\n\n"
			. "Someone asked to reset the password for your Harrow Online Hub account. To choose a new one, open this link:\n\n"
			. $link . "\n\n"
			. "The link works once and expires after an hour.\n\n"
			. "If you didn't ask for this, you can ignore this email. Your password won't change.\n\n"
			. "Thanks,\nThe Harrow Online team\nhub@harrowonline.org",
	);
}

/** The confirm-your-email message sent after registering: { subject, body }. */
function hub_account_shape_confirm_email( $first_name, $link ) {
	return array(
		'subject' => 'Confirm your email for Harrow Online Hub',
		'body'    => 'Hi ' . ( '' !== $first_name ? $first_name : 'there' ) . ",\n\n"
			. "Thanks for joining the Harrow Online Hub. Please confirm this is your email address:\n\n"
			. $link . "\n\n"
			. 'The link works for ' . HUB_ACCOUNT_CONFIRM_DAYS . " days. You'll need to confirm before you can choose a paid plan.\n\n"
			. "If you didn't create an account, you can ignore this email.\n\n"
			. "Thanks,\nThe Harrow Online team\nhub@harrowonline.org",
	);
}

/** The confirm-your-new-email message (settings page email change): { subject, body }. */
function hub_account_shape_email_change( $first_name, $link ) {
	return array(
		'subject' => 'Confirm your new email for Harrow Online Hub',
		'body'    => 'Hi ' . ( '' !== $first_name ? $first_name : 'there' ) . ",\n\n"
			. "You asked to use this address for your Harrow Online Hub account. To confirm, open this link:\n\n"
			. $link . "\n\n"
			. 'The link works for ' . HUB_ACCOUNT_CONFIRM_DAYS . " days. Until then, keep signing in with your old address.\n\n"
			. "If you didn't ask for this, ignore this email and nothing changes.\n\n"
			. "Thanks,\nThe Harrow Online team\nhub@harrowonline.org",
	);
}
