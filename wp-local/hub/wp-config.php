<?php
/**
 * Harrow Online Hub WordPress config.
 *
 * Local development (the default): http://localhost:8020/hub/, SQLite database wp-content/database/hub.sqlite via the
 * SQLite Database Integration drop-in (wp-content/db.php). Committed to git so every developer runs the same site.
 *
 * Servers (e.g. the Laravel Forge dev site): every setting below can come from the server environment instead, either
 * a real environment variable or the `.env` file in the repository root (Forge: Site > Environment). That file is
 * outside git and outside the web directory. Keys: WP_HOME, DB_ENGINE (sqlite or mysql), DB_PATH, DB_NAME, DB_USER,
 * DB_PASSWORD, DB_HOST, WP_SQLITE_UNSAFE_ENABLE_UNSUPPORTED_VERSIONS, the eight keys and salts (AUTH_KEY ... NONCE_SALT), WP_ENVIRONMENT_TYPE, WP_DEBUG,
 * WP_DEBUG_LOG, DISALLOW_FILE_MODS. Never commit server values. See wp-local/README.md.
 */
$hub_env      = array();
$hub_env_file = dirname( __DIR__, 2 ) . '/.env';
if ( is_readable( $hub_env_file ) ) {
	foreach ( file( $hub_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $hub_line ) {
		$hub_line = trim( $hub_line );
		if ( '' === $hub_line || '#' === $hub_line[0] || false === strpos( $hub_line, '=' ) ) {
			continue;
		}
		list( $hub_k, $hub_v ) = array_map( 'trim', explode( '=', $hub_line, 2 ) );
		if ( strlen( $hub_v ) > 1 && ( '"' === $hub_v[0] || "'" === $hub_v[0] ) && substr( $hub_v, -1 ) === $hub_v[0] ) {
			$hub_v = substr( $hub_v, 1, -1 );
		}
		$hub_env[ $hub_k ] = $hub_v;
	}
}
/** A setting from the server environment (real env var first, then .env), else the local default. */
$hub_cfg = function ( $key, $default ) use ( $hub_env ) {
	$v = getenv( $key );
	if ( false !== $v && '' !== $v ) {
		return $v;
	}
	return isset( $hub_env[ $key ] ) && '' !== $hub_env[ $key ] ? $hub_env[ $key ] : $default;
};
$hub_bool = function ( $key, $default ) use ( $hub_cfg ) {
	return in_array( strtolower( (string) $hub_cfg( $key, $default ? 'true' : 'false' ) ), array( '1', 'true', 'yes', 'on' ), true );
};

// Database: SQLite locally (DB_PATH), MySQL when DB_ENGINE=mysql (the SQLite drop-in then stands aside).
define( 'DB_ENGINE', $hub_cfg( 'DB_ENGINE', 'sqlite' ) );
define( 'DB_NAME', $hub_cfg( 'DB_NAME', 'harrowonline_local' ) );
define( 'DB_USER', $hub_cfg( 'DB_USER', '' ) );
define( 'DB_PASSWORD', $hub_cfg( 'DB_PASSWORD', '' ) );
define( 'DB_HOST', $hub_cfg( 'DB_HOST', '' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'DB_PATH', $hub_cfg( 'DB_PATH', __DIR__ . '/wp-content/database/hub.sqlite' ) );
// Servers whose SQLite library is older than 3.37 (e.g. Ubuntu 20.04's 3.31): the SQLite plugin's own back-compat
// mode, which opens the database's STRICT tables with SQLite 3.27+. Off locally.
define( 'WP_SQLITE_UNSAFE_ENABLE_UNSUPPORTED_VERSIONS', $hub_bool( 'WP_SQLITE_UNSAFE_ENABLE_UNSUPPORTED_VERSIONS', false ) );

// Keys and salts: the committed values are for local development only; servers set their own.
define( 'AUTH_KEY', $hub_cfg( 'AUTH_KEY', '5?@kU78bB_|vIzd<FMW-=7=s35#K4&%y0b29Qow~p>;r%WB+]@2.*4I-__!r>Z}+' ) );
define( 'SECURE_AUTH_KEY', $hub_cfg( 'SECURE_AUTH_KEY', '~8Hz&i=n=Rg-UVG8mu!u[+nS8Z^e5+FZ6^)2vWm-@6a|nVn0JDM9;B|0XqGOl)RN' ) );
define( 'LOGGED_IN_KEY', $hub_cfg( 'LOGGED_IN_KEY', 't}|PA>9~^4-QIKJ^voj;w+nN~I{8t]J92M^lV+XIL>=0xo8|Q*O][84.c!x@@^8+' ) );
define( 'NONCE_KEY', $hub_cfg( 'NONCE_KEY', 'K5UO_](O({3xcm-Z,XS9u!)g_cu.+!LOh1G}9vH10zO%.*x_o8S<NT(udIA#mU|;' ) );
define( 'AUTH_SALT', $hub_cfg( 'AUTH_SALT', 'B?BcTi34,@|!n-bdkEKFdTyhQ#SJ0})5MC2V).w@nXtBo3=5s+/zTi0P!C(N9K0]' ) );
define( 'SECURE_AUTH_SALT', $hub_cfg( 'SECURE_AUTH_SALT', '*J-NM2FAVbVGt>}9Lk8FGMdR}U**wW1EjU([8#fddU|]OV}ut#}Ql/6^*2O<wm1B' ) );
define( 'LOGGED_IN_SALT', $hub_cfg( 'LOGGED_IN_SALT', '1!b8&P4_T=+]2}]t;f:UikXg}Y~)PeDTB^&VR,+<k+L/Ps1BOVHtMDO^O%xBSHni' ) );
define( 'NONCE_SALT', $hub_cfg( 'NONCE_SALT', 'jDYY1NQi}RC/&:XvdgO^?BE:[^qyQ}qtFX4HGVQ_XW&VFj4~ryza?XC/W+(:1%@y' ) );

$table_prefix = 'wp_';

// Site address, including the /hub path (the database's own home/siteurl stay the local ones).
define( 'WP_HOME', rtrim( $hub_cfg( 'WP_HOME', 'http://localhost:8020/hub' ), '/' ) );
define( 'WP_SITEURL', WP_HOME );
define( 'WP_ENVIRONMENT_TYPE', $hub_cfg( 'WP_ENVIRONMENT_TYPE', 'local' ) );
define( 'WP_DEBUG', $hub_bool( 'WP_DEBUG', true ) );
define( 'WP_DEBUG_LOG', $hub_bool( 'WP_DEBUG_LOG', true ) );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', $hub_bool( 'DISALLOW_FILE_MODS', false ) );   // servers: plugins and themes come from git
define( 'AUTOMATIC_UPDATER_DISABLED', true );
unset( $hub_env, $hub_env_file, $hub_line, $hub_k, $hub_v, $hub_cfg, $hub_bool );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
