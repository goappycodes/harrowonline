<?php
/**
 * Claims: the rules (plain PHP, no WordPress). A signed-in account asks to manage an unclaimed business, proves it
 * one of three ways, and staff approve or reject it in wp-admin > Claims.
 */
defined( 'ABSPATH' ) || exit;

/** Ways to prove ownership, fastest first: value => label. */
const HUB_CLAIMS_METHODS = array(
	'email'    => 'Email on the business website',
	'phone'    => 'We phone the business',
	'document' => 'Upload a document',
);

const HUB_CLAIMS_ROLES = array(
	'owner'   => 'Owner',
	'manager' => 'Manager',
	'staff'   => 'Staff member allowed to manage the listing',
	'agency'  => 'Marketing agency acting for the owner',
);

const HUB_CLAIMS_CALL_TIMES = array(
	'weekday-morning'   => 'Weekday mornings, 9am to 12pm',
	'weekday-afternoon' => 'Weekday afternoons, 12pm to 5pm',
	'saturday'          => 'Saturday, 10am to 1pm',
);

const HUB_CLAIMS_DOC_TYPES = array(
	'utility'         => 'Utility bill (last 3 months)',
	'rates'           => 'Business rates bill',
	'companies-house' => 'Companies House record',
);

/** Uploaded proof: extension => MIME type, and the size limit. */
const HUB_CLAIMS_FILE_TYPES = array( 'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png' );
const HUB_CLAIMS_FILE_MAX   = 10485760;   // 10MB

const HUB_CLAIMS_STATUSES = array(
	'pending'  => 'Waiting for a check',
	'approved' => 'Approved',
	'rejected' => 'Not approved',
);

/** How long the link in the business-email check works. */
const HUB_CLAIMS_VERIFY_DAYS = 7;

/** Website hosts that aren't the business's own domain (an email there proves nothing). */
const HUB_CLAIMS_SHARED_HOSTS = array( 'facebook.com', 'instagram.com', 'linktr.ee', 'google.com', 'business.site', 'wixsite.com', 'square.site', 'tiktok.com', 'x.com', 'twitter.com', 'linkedin.com', 'yell.com', 'gmail.com', 'outlook.com', 'hotmail.com' );

/** The business's own email domain from its website ("https://www.example.co.uk/x" -> "example.co.uk"), or ''. */
function hub_claims_rule_domain( $website ) {
	$host = strtolower( (string) parse_url( ( false === strpos( (string) $website, '://' ) ? 'http://' : '' ) . trim( (string) $website ), PHP_URL_HOST ) );
	$host = preg_replace( '/^www\./', '', $host );
	if ( '' === $host || false === strpos( $host, '.' ) ) {
		return '';
	}
	foreach ( HUB_CLAIMS_SHARED_HOSTS as $shared ) {
		if ( $host === $shared || substr( $host, -strlen( '.' . $shared ) ) === '.' . $shared ) {
			return '';
		}
	}
	return $host;
}

/** The part before @ that someone typed: letters, digits and . _ % + - only. */
function hub_claims_rule_email_local_ok( $local ) {
	return (bool) preg_match( '/^[A-Za-z0-9._%+-]{1,64}$/', (string) $local );
}

/** A file's extension if it's an allowed type and size, else ''. */
function hub_claims_rule_file_ok( $name, $mime, $size ) {
	$ext = strtolower( pathinfo( (string) $name, PATHINFO_EXTENSION ) );
	return ( isset( HUB_CLAIMS_FILE_TYPES[ $ext ] ) && HUB_CLAIMS_FILE_TYPES[ $ext ] === $mime && $size > 0 && $size <= HUB_CLAIMS_FILE_MAX ) ? $ext : '';
}
