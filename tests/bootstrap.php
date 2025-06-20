<?php
/**
 * Bootstrap agnóstico para testing PHPUnit
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 * 
 * Este archivo inicializa automáticamente el entorno de testing para WordPress y Dev-Tools
 * detectando dinámicamente la instalación de WordPress y la estructura del plugin
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

// Cargar autoloader de Composer
require_once $plugin_dir . '/vendor/autoload.php';

// Si WP_TESTS_DIR no está definido, intentar encontrar WordPress dinámicamente
if ( ! $wp_tests_dir ) {
    // Detectar dinámicamente la instalación de WordPress
    $current_dir = $plugin_dir;
    $max_depth = 10;
    $current_depth = 0;
    
    while ($current_depth < $max_depth) {
        if (file_exists($current_dir . '/wp-config.php') || file_exists($current_dir . '/wp-settings.php')) {
            $wp_tests_dir = $current_dir;
            break;
        }
        $parent = dirname($current_dir);
        if ($parent === $current_dir) {
            // Llegamos al directorio raíz sin encontrar WordPress
            break;
        }
        $current_dir = $parent;
        $current_depth++;
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
    // Cargar el plugin principal que ya incluye dev-tools
    require dirname( dirname( __DIR__ ) ) . '/tarokina-pro.php';
}

// Registrar la función para cargar el plugin
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Cargar el bootstrap de WordPress desde wp-phpunit
require $wp_phpunit_dir . '/includes/bootstrap.php';

// Incluir helpers adicionales para testing
require_once __DIR__ . '/includes/TestCase.php';
require_once __DIR__ . '/includes/Helpers.php';

// Activar el plugin programáticamente para las pruebas
// Detectar dinámicamente el path del plugin principal
$plugin_base_dir = dirname($plugin_dir);
$plugin_name = basename($plugin_base_dir);
$main_plugin_path = $plugin_name . '/tarokina-pro.php';

// Intentar activar el plugin principal (que ya incluye dev-tools)
if (function_exists('activate_plugin')) {
    try {
        activate_plugin($main_plugin_path);
    } catch (Exception $e) {
        // Si falla, no es crítico para todos los tests
        // El plugin se cargará mediante _manually_load_plugin()
    }
}

echo "✅ Bootstrap de Dev-Tools testing cargado correctamente\n";
