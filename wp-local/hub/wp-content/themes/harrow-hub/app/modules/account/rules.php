<?php
/**
 * Accounts: the rules (plain PHP, no WordPress).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Every Hub account is a WordPress Subscriber. What kind of account it is and which plan it's on are user fields
 * (user meta "hub_account_type" and "hub_plan"), shown in wp-admin > Users.
 */
const HUB_ACCOUNT_ROLE = 'subscriber';

/** Roles from earlier builds; their users move to Subscriber with the matching fields, then the roles are removed. */
const HUB_ACCOUNT_OLD_ROLES = array( 'hub_business', 'hub_organiser', 'hub_basic', 'hub_featured', 'hub_partner' );

/** Account types a visitor can choose when registering: value => label. */
const HUB_ACCOUNT_TYPES = array(
	'business'  => 'Business owner',
	'organiser' => 'Organiser',
);

/** A business owner's plan (Pricing page): value => label. Organisers have no plan. */
const HUB_ACCOUNT_PLANS = array(
	'basic'    => 'Basic',
	'featured' => 'Featured',
	'partner'  => 'Local Partner',
);

/**
 * Attempt limits against password guessing and email flooding: bucket => [ tries, seconds ]. A bucket counts per
 * email and/or IP address; it empties that long after the last try (a successful sign-in clears it).
 */
const HUB_ACCOUNT_LIMITS = array(
	'sign_in'     => array( 5, 15 * 60 ),    // wrong passwords for one email from one IP
	'sign_in_ip'  => array( 30, 15 * 60 ),   // wrong passwords from one IP, any email
	'reset'       => array( 3, 60 * 60 ),    // reset emails to one address
	'reset_ip'    => array( 10, 60 * 60 ),   // reset requests from one IP
	'register_ip' => array( 5, 60 * 60 ),    // new accounts from one IP
	'confirm'     => array( 3, 60 * 60 ),    // confirmation emails resent for one account
	'claim'       => array( 5, 24 * 60 * 60 ),   // claims sent by one account in a day (claims module)
	'add_business' => array( 3, 24 * 60 * 60 ),  // businesses added by one account in a day (businesses module)
);

/** Email notification choices on the settings page: key => [ label, description ]. Receipts are always sent. */
const HUB_ACCOUNT_NOTIFY = array(
	'approved' => array( 'Approvals', 'When an offer, event, class or claim is approved' ),
	'rejected' => array( 'Changes needed', 'When something isn&rsquo;t approved, with our note on what to change' ),
	'ended'    => array( 'Reminders', 'A few days before an offer ends or a featured spot runs out' ),
);

/** How long an email confirmation link works. */
const HUB_ACCOUNT_CONFIRM_DAYS = 7;

const HUB_ACCOUNT_MIN_PASSWORD = 10;

/** Newsletter editions a new account can join (value => label), as on the register form. */
const HUB_ACCOUNT_EDITIONS = array( 'daily' => 'Daily News', 'weekend' => 'The Weekend', 'business' => 'Harrow Business' );

function hub_account_rule_password_ok( $password ) {
	return strlen( (string) $password ) >= HUB_ACCOUNT_MIN_PASSWORD;
}

/** The account's plan for its business's plan (businesses module: unclaimed, basic, enhanced, premium, partner, none). */
function hub_account_rule_plan_for_business( $business_plan ) {
	if ( 'partner' === $business_plan ) {
		return 'partner';
	}
	return in_array( $business_plan, array( 'enhanced', 'premium' ), true ) ? 'featured' : 'basic';
}

/** The business plan to set for an account plan chosen in wp-admin. */
function hub_account_rule_business_plan( $plan ) {
	$map = array( 'basic' => 'basic', 'featured' => 'premium', 'partner' => 'partner' );
	return isset( $map[ $plan ] ) ? $map[ $plan ] : null;
}

/** The account type from the form, defaulting to a business account. */
function hub_account_rule_type( $value ) {
	return isset( HUB_ACCOUNT_TYPES[ $value ] ) ? $value : 'business';
}

/** Only redirect back to a Hub page after signing in (never another site). */
function hub_account_rule_safe_return( $url, $home ) {
	$url = (string) $url;
	return ( '' !== $url && 0 === strpos( $url, $home ) ) ? $url : $home . 'account/';
}

/** Storage key for an attempt bucket: the bucket name and a hash of what it counts (email, IP). */
function hub_account_rule_limit_key( $bucket, array $parts ) {
	return $bucket . '_' . md5( strtolower( implode( '|', $parts ) ) );
}

/** "15 minutes" / "an hour" for a limit's wait, in messages. */
function hub_account_rule_wait( $bucket ) {
	$min = (int) round( HUB_ACCOUNT_LIMITS[ $bucket ][1] / 60 );
	return 60 === $min ? 'an hour' : $min . ' minutes';
}
