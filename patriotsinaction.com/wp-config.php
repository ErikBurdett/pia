<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'xsblwqte_WPGFA');

/** Database username */
define('DB_USER', 'xsblwqte_WPGFA');

/** Database password */
define('DB_PASSWORD', '>TBM^2CMm}^kL}gBB');

/** Database hostname */
define('DB_HOST', 'localhost');

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'a071a013cefb2d9f8f7e8532fcd0ccb8dc74c23de20a47514c2245e3f42a6462');
define('SECURE_AUTH_KEY', 'a68309090777fa661b4c081b35d73e6fd14aea23e863261ca89d1d32c7c283de');
define('LOGGED_IN_KEY', 'c4b29909168089f5d9b482d2e62bb21cd1665368b3d9c650edccf83d601d4f9d');
define('NONCE_KEY', 'f2877271e9eb7ec855b46a0297950ee5f4f4a5949064376918be6b94b52bb002');
define('AUTH_SALT', 'ccad3846b75bbffa4f2255f4d7498f7d4c3fba788729d1a734ad75046440410d');
define('SECURE_AUTH_SALT', '0bd00e56142e1b4fa6578fb7435cbd5f816d9e83025d3c17e34b0c2fa11d43bf');
define('LOGGED_IN_SALT', 'da2c54baafa51ef2a8919604012d88cfa373e2ccf8381a71b334d805eb4a3fd0');
define('NONCE_SALT', 'e1993cff54d06ad336c860f1e54263274d7a1c058c86d7df4d9742a8f9aeee84');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = '2fJ_';
define('WP_CRON_LOCK_TIMEOUT', 120);
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 20);
define('EMPTY_TRASH_DAYS', 7);
define('WP_AUTO_UPDATE_CORE', true);

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

// Vimeo API token for PIA Vimeo Showcase Proxy (MU)
define('PIA_VIMEO_ACCESS_TOKEN', 'd57de58a59aa0f831024d61bd336d868');


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
