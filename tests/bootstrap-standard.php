<?php
/**
 * Bootstrap for PHPUnit tests.
 *
 * @package DevTools
 */

// Prevent timeouts when running the tests.
ini_set( 'max_execution_time', 0 );

// Define path to WordPress test configuration
$_config_path = dirname( dirname( __FILE__ ) ) . '/wp-tests-config.php';
if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', $_config_path );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = dirname( dirname( __FILE__ ) ) . '/wordpress-develop/tests/phpunit';
}

// Forward custom PHPUnit Polyfills directory.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) && false !== getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load Dev-Tools configuration
	require dirname( dirname( __FILE__ ) ) . '/config.php';
	
	// Load Dev-Tools loader
	require dirname( dirname( __FILE__ ) ) . '/loader.php';
	
	// Try to load host plugin if available
	$plugin_main_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/tarokina-pro.php';
	if ( file_exists( $plugin_main_file ) ) {
		require $plugin_main_file;
	}
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
