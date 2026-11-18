<?php
/** One-off local install (CLI): php wp-local/hub/install-local.php  — creates the SQLite database and the admin user. */
define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST'] = 'localhost:8020';
$_SERVER['REQUEST_URI'] = '/hub/';
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( is_blog_installed() ) { echo "Already installed\n"; exit; }
$pass = wp_generate_password( 20, false );
$r = wp_install( 'Harrow Online', 'admin', 'admin@harrowonline.test', false, '', $pass );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'timezone_string', 'Europe/London' );
update_option( 'date_format', 'j F Y' );
file_put_contents( __DIR__ . '/LOCAL-ADMIN.txt', "Local WordPress admin (development only)\nURL: http://localhost:8020/hub/wp-admin/\nUser: admin\nPassword: {$pass}\n" );
echo "Installed. Admin login saved to wp-local/hub/LOCAL-ADMIN.txt\n";
