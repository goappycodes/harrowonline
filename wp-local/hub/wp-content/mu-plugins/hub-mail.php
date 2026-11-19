<?php
/**
 * How the Hub sends email (all of it goes through wp_mail: sign-up, password reset, billing).
 *
 * - From "Harrow Online <hub@harrowonline.org>" (MAIL_FROM, MAIL_FROM_NAME), not WordPress's wordpress@ address.
 * - On servers, through an SMTP service (Postmark, Brevo, Amazon SES...) set in the environment or .env:
 *   SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS (read in wp-config.php). The service must have verified
 *   the From domain (SPF and DKIM DNS records), or mail lands in spam.
 * - Without SMTP_HOST, PHP's own mail() is used (unreliable), except locally, where hub-local-mail.php saves emails to files.
 * Failures are written to the PHP error log (wp-content/debug.log when WP_DEBUG_LOG is on).
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'wp_mail_from', function ( $from ) {
	return defined( 'HUB_MAIL_FROM' ) && is_email( HUB_MAIL_FROM ) ? HUB_MAIL_FROM : $from;
} );
add_filter( 'wp_mail_from_name', function ( $name ) {
	return defined( 'HUB_MAIL_FROM_NAME' ) && '' !== HUB_MAIL_FROM_NAME ? HUB_MAIL_FROM_NAME : $name;
} );

add_action( 'phpmailer_init', function ( $mail ) {
	if ( ! defined( 'HUB_SMTP_HOST' ) || '' === HUB_SMTP_HOST ) {
		return;
	}
	$mail->isSMTP();
	$mail->Host       = HUB_SMTP_HOST;
	$mail->Port       = HUB_SMTP_PORT;
	$mail->SMTPSecure = in_array( HUB_SMTP_SECURE, array( 'tls', 'ssl' ), true ) ? HUB_SMTP_SECURE : '';
	$mail->SMTPAuth   = '' !== HUB_SMTP_USER;
	$mail->Username   = HUB_SMTP_USER;
	$mail->Password   = HUB_SMTP_PASS;
	$mail->Timeout    = 15;
	$mail->Sender     = $mail->From;   // bounces go to the From address, matching SPF
} );

add_action( 'wp_mail_failed', function ( WP_Error $e ) {
	$data = $e->get_error_data();
	$to   = is_array( $data ) && isset( $data['to'] ) ? implode( ', ', (array) $data['to'] ) : '';
	error_log( 'Hub email not sent' . ( '' !== $to ? ' to ' . $to : '' ) . ': ' . $e->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
} );
