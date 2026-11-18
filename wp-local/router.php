<?php
/**
 * Router for PHP's built-in server (local development only). The Hub's WordPress lives in /hub, as it will on
 * harrowonline.org/hub/. Real files are served as they are, folders run their index.php, every other /hub/ URL
 * goes to WordPress (pretty permalinks), and / redirects to /hub/.
 *   C:\php\php.exe -S localhost:8020 -t wp-local wp-local/router.php
 */
$path = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$file = __DIR__ . $path;
if ( strpos( $path . '/', '/hub/' ) !== 0 ) {
	header( 'Location: /hub/', true, 302 );
	return;
}
if ( is_file( $file ) ) {
	return false;   // static file or a .php script
}
if ( is_dir( $file ) && is_file( rtrim( $file, '/' ) . '/index.php' ) && rtrim( $path, '/' ) !== '/hub' ) {
	$_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php';
	chdir( rtrim( $file, '/' ) );
	require rtrim( $file, '/' ) . '/index.php';
	return;
}
$_SERVER['SCRIPT_NAME'] = '/hub/index.php';
chdir( __DIR__ . '/hub' );
require __DIR__ . '/hub/index.php';
