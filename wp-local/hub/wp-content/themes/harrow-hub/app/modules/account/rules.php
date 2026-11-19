<?php
/**
 * Accounts: the rules (plain PHP, no WordPress).
 */
defined( 'ABSPATH' ) || exit;

/** The Hub's roles: role => label. */
const HUB_ACCOUNT_ROLES = array(
	'hub_business'  => 'Business owner',
	'hub_organiser' => 'Organiser',
);

/** Account types a visitor can choose when registering, and the role each gets. */
const HUB_ACCOUNT_TYPES = array(
	'business'  => 'hub_business',
	'organiser' => 'hub_organiser',
);

const HUB_ACCOUNT_MIN_PASSWORD = 10;

/** Newsletter editions a new account can join (value => label), as on the register form. */
const HUB_ACCOUNT_EDITIONS = array( 'daily' => 'Daily News', 'weekend' => 'The Weekend', 'business' => 'Harrow Business' );

function hub_account_rule_password_ok( $password ) {
	return strlen( (string) $password ) >= HUB_ACCOUNT_MIN_PASSWORD;
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
