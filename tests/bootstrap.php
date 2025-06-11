<?php
/**
 * Dev-Tools Test Bootstrap
 * 
 * Bootstrap para PHPUnit oficial de WordPress
 * Configuración para entorno de testing agnóstico
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

// Define testing environment
define('DEV_TOOLS_TESTING', true);
define('DEV_TOOLS_TEST_MODE', true);
define('DEV_TOOLS_DIRECT_ACCESS', true);

// Get the WordPress test environment path
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Check if WordPress test environment exists
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "WordPress test environment not found at: $_tests_dir\n";
    echo "Please install WordPress test environment:\n";
    echo "bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
    exit(1);
}

// Load WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Load WordPress
    require_once dirname(__DIR__, 6) . '/wp-load.php';
    
    // Load Dev-Tools system
    require_once dirname(__DIR__) . '/loader.php';
    
    // Load all modules for testing
    $modules_dir = dirname(__DIR__) . '/modules/';
    $module_files = glob($modules_dir . '*Module.php');
    
    foreach ($module_files as $module_file) {
        require_once $module_file;
    }
}

// Hook to load plugin
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load Dev-Tools test utilities
require_once __DIR__ . '/includes/class-dev-tools-test-case.php';
require_once __DIR__ . '/includes/class-test-data-factory.php';
require_once __DIR__ . '/includes/test-helpers.php';

// Create reports directory if it doesn't exist
$reports_dir = __DIR__ . '/reports';
if (!is_dir($reports_dir)) {
    mkdir($reports_dir, 0755, true);
}

// Create coverage directory if it doesn't exist
$coverage_dir = __DIR__ . '/coverage';
if (!is_dir($coverage_dir)) {
    mkdir($coverage_dir, 0755, true);
}

echo "🔧 Dev-Tools Test Environment Initialized\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Tests Directory: " . __DIR__ . "\n";
echo "WordPress Tests Directory: " . $_tests_dir . "\n\n";
