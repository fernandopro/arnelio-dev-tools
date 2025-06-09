<?php
/**
 * WordPress Test Configuration File
 * 
 * Configuración estándar de WordPress PHPUnit siguiendo las mejores prácticas oficiales.
 * Basado en: https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 * 
 * @package DevTools
 * @since 3.0.0
 */

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define( 'ABSPATH', dirname( __FILE__ ) . '/../../../../' );

/*
 * Path to the theme to test with.
 *
 * The 'default' theme is symlinked from test/phpunit/data/themedir1/default into
 * the themes directory of the WordPress installation defined above.
 */
define( 'WP_DEFAULT_THEME', 'twentytwentythree' );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// ** Database settings ** //

/*
 * This configuration file will be used by the copy of WordPress being tested.
 * wordpress/wp-config.php will be ignored.
 *
 * WARNING WARNING WARNING!
 * These tests will DROP ALL TABLES in the database with the prefix named below.
 * DO NOT use a production database or one that is shared with something else.
 */

// Auto-detect Local by Flywheel socket or use default MySQL
$socket_key = null;

// Try to detect from Local by Flywheel paths
$local_run_path = '/Users/' . get_current_user() . '/Library/Application Support/Local/run/';
if (is_dir($local_run_path)) {
    $socket_dirs = glob($local_run_path . '*/mysql/mysqld.sock');
    if (!empty($socket_dirs)) {
        // Use the most recent socket
        usort($socket_dirs, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $socket_path = $socket_dirs[0];
        $socket_key = basename(dirname(dirname($socket_path)));
    }
}

// Database configuration - solo definir si no están ya definidas
if ( ! defined( 'DB_NAME' ) ) {
    define( 'DB_NAME', 'local' );
}
if ( ! defined( 'DB_USER' ) ) {
    define( 'DB_USER', 'root' );
}
if ( ! defined( 'DB_PASSWORD' ) ) {
    define( 'DB_PASSWORD', 'root' );
}

if ( ! defined( 'DB_HOST' ) ) {
    if ($socket_key) {
        define( 'DB_HOST', 'localhost:/Users/' . get_current_user() . '/Library/Application Support/Local/run/' . $socket_key . '/mysql/mysqld.sock' );
        echo "=== DEV TOOLS TESTS CONFIG - Local by Flywheel ===\n";
        echo "Socket: $socket_key\n";
    } else {
        define( 'DB_HOST', 'localhost' );
        echo "=== DEV TOOLS TESTS CONFIG - Standard MySQL ===\n";
    }
}

define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

echo "Base de datos: " . DB_NAME . "@" . DB_HOST . "\n";
echo "Prefijo tablas: wp_test_\n";
echo "Sitio URL: http://localhost:10019\n";
echo "=============================================\n";

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Testing keys - no need for real security in test environment
 */
define( 'AUTH_KEY',         'test-auth-key-for-dev-tools-testing' );
define( 'SECURE_AUTH_KEY',  'test-secure-auth-key-for-dev-tools' );
define( 'LOGGED_IN_KEY',    'test-logged-in-key-for-dev-tools' );
define( 'NONCE_KEY',        'test-nonce-key-for-dev-tools' );
define( 'AUTH_SALT',        'test-auth-salt-for-dev-tools' );
define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt-for-dev-tools' );
define( 'LOGGED_IN_SALT',   'test-logged-in-salt-for-dev-tools' );
define( 'NONCE_SALT',       'test-nonce-salt-for-dev-tools' );

$table_prefix = 'wp_test_';   // Only numbers, letters, and underscores please!

define( 'WP_TESTS_DOMAIN', 'localhost' );
define( 'WP_TESTS_EMAIL', 'admin@localhost.test' );
define( 'WP_TESTS_TITLE', 'Dev-Tools Test Site' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
