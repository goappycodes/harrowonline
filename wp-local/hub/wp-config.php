<?php
/**
 * Local development only (Harrow Online Hub, served at http://localhost:8020/hub/). SQLite database via the SQLite
 * Database Integration drop-in (wp-content/db.php), file wp-content/database/hub.sqlite. Committed to git so every
 * developer runs the same local site. Never deploy this file: staging and live have their own wp-config.php.
 */
define( 'DB_NAME', 'harrowonline_local' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// SQLite database file, committed to git so every developer gets the same local site (local only).
define( 'DB_PATH', __DIR__ . '/wp-content/database/hub.sqlite' );

define( 'AUTH_KEY', '5?@kU78bB_|vIzd<FMW-=7=s35#K4&%y0b29Qow~p>;r%WB+]@2.*4I-__!r>Z}+' );
define( 'SECURE_AUTH_KEY', '~8Hz&i=n=Rg-UVG8mu!u[+nS8Z^e5+FZ6^)2vWm-@6a|nVn0JDM9;B|0XqGOl)RN' );
define( 'LOGGED_IN_KEY', 't}|PA>9~^4-QIKJ^voj;w+nN~I{8t]J92M^lV+XIL>=0xo8|Q*O][84.c!x@@^8+' );
define( 'NONCE_KEY', 'K5UO_](O({3xcm-Z,XS9u!)g_cu.+!LOh1G}9vH10zO%.*x_o8S<NT(udIA#mU|;' );
define( 'AUTH_SALT', 'B?BcTi34,@|!n-bdkEKFdTyhQ#SJ0})5MC2V).w@nXtBo3=5s+/zTi0P!C(N9K0]' );
define( 'SECURE_AUTH_SALT', '*J-NM2FAVbVGt>}9Lk8FGMdR}U**wW1EjU([8#fddU|]OV}ut#}Ql/6^*2O<wm1B' );
define( 'LOGGED_IN_SALT', '1!b8&P4_T=+]2}]t;f:UikXg}Y~)PeDTB^&VR,+<k+L/Ps1BOVHtMDO^O%xBSHni' );
define( 'NONCE_SALT', 'jDYY1NQi}RC/&:XvdgO^?BE:[^qyQ}qtFX4HGVQ_XW&VFj4~ryza?XC/W+(:1%@y' );

$table_prefix = 'wp_';

define( 'WP_HOME', 'http://localhost:8020/hub' );
define( 'WP_SITEURL', 'http://localhost:8020/hub' );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
