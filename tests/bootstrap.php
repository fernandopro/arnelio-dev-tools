<?php
/**
 * Bootstrap para testing PHPUnit
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 * 
 * Este archivo inicializa el entorno de testing para WordPress y Dev-Tools
 */

// Definir constantes de testing antes de cargar WordPress
if ( ! defined( 'DEV_TOOLS_TESTING' ) ) {
    define( 'DEV_TOOLS_TESTING', true );
}

if ( ! defined( 'DEV_TOOLS_TEST_MODE' ) ) {
    define( 'DEV_TOOLS_TEST_MODE', 'unit' );
}

// Configurar la zona horaria
date_default_timezone_set( 'UTC' );

// Verificar que estamos en el directorio correcto
$plugin_dir = dirname( __DIR__ );
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

// Si WP_TESTS_DIR no está definido, intentar encontrar WordPress
if ( ! $wp_tests_dir ) {
    // Buscar WordPress en la instalación de Local
    $wp_root = '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public';
    if ( file_exists( $wp_root . '/wp-config.php' ) ) {
        $wp_tests_dir = $wp_root;
    }
}

// Cargar PHPUnit Polyfills - OBLIGATORIO para WordPress Test Suite
require_once $plugin_dir . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Verificar que tenemos wp-phpunit disponible
$wp_phpunit_dir = $plugin_dir . '/vendor/wp-phpunit/wp-phpunit';
if ( ! file_exists( $wp_phpunit_dir . '/includes/functions.php' ) ) {
    echo "Error: wp-phpunit no encontrado. Ejecuta: composer require --dev wp-phpunit/wp-phpunit\n";
    exit( 1 );
}

// Cargar wp-tests-config.php
$config_file = $plugin_dir . '/tests/wp-tests-config.php';
if ( ! file_exists( $config_file ) ) {
    echo "Error: wp-tests-config.php no encontrado en tests/\n";
    exit( 1 );
}

// Cargar la configuración
require_once $config_file;

// Definir WP_TESTS_CONFIG_FILE_PATH para wp-phpunit
if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
    define( 'WP_TESTS_CONFIG_FILE_PATH', $config_file );
}

// Cargar las funciones de testing de WordPress desde wp-phpunit
require_once $wp_phpunit_dir . '/includes/functions.php';

/**
 * Función para cargar el plugin antes de que WordPress se inicialice
 */
function _manually_load_plugin() {
    // Cargar el loader de Dev-Tools
    require dirname( __DIR__ ) . '/loader.php';
}

// Registrar la función para cargar el plugin
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Cargar el bootstrap de WordPress desde wp-phpunit
require $wp_phpunit_dir . '/includes/bootstrap.php';

// Incluir helpers adicionales para testing
require_once __DIR__ . '/includes/TestCase.php';
require_once __DIR__ . '/includes/Helpers.php';

// Activar el plugin programáticamente para las pruebas
activate_plugin( 'tarokina-2025/dev-tools/loader.php' );

echo "✅ Bootstrap de Dev-Tools testing cargado correctamente\n";
