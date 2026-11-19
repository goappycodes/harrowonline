<?php
/**
 * Local development only (WP_ENVIRONMENT_TYPE = local and no SMTP_HOST): emails aren't sent. Each one is saved as a text file in
 * wp-content/mail-log/ (gitignored), so you can read sign-up, password reset and billing emails without a mail server.
 * On the dev server and live, the environment type is different, so this does nothing.
 */
defined( 'ABSPATH' ) || exit;

if ( 'local' === wp_get_environment_type() && ( ! defined( 'HUB_SMTP_HOST' ) || '' === HUB_SMTP_HOST ) ) {
	add_filter( 'pre_wp_mail', function ( $null, $atts ) {
		$dir = WP_CONTENT_DIR . '/mail-log';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			file_put_contents( $dir . '/.htaccess', "Require all denied\n" );
		}
		$to   = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'];
		$name = gmdate( 'Ymd-His' ) . '-' . substr( md5( $to . $atts['subject'] . microtime() ), 0, 6 ) . '.txt';
		file_put_contents( $dir . '/' . $name, 'To: ' . $to . "\nSubject: " . $atts['subject'] . "\n\n" . $atts['message'] . "\n" );
		return true;   // handled: don't try to send
	}, 10, 2 );
}
