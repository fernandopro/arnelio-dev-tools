<?php
/**
 * Bootstrap para Tests - Dev-Tools Arquitectura 3.0
 * 
 * Bootstrap siguiendo estándar oficial de WordPress con sistema de override.
 * Basado en: https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 * 
 * @package DevTools\Tests
 * @since Arquitectura 3.0
 * @author Dev-Tools Team
 */

// =============================================================================
// CONFIGURACIÓN INICIAL - ESTÁNDAR WORDPRESS
// =============================================================================

// Prevent timeouts when running the tests.
ini_set( 'max_execution_time', 0 );

// =============================================================================
// SISTEMA DE OVERRIDE (Child Theme Pattern)
// =============================================================================

/**
 * Cargar bootstrap override específico del plugin si existe
 */
function load_bootstrap_override() {
    $override_bootstrap = dirname(dirname(dirname(__FILE__))) . '/plugin-dev-tools/tests/bootstrap.php';
    
    if (file_exists($override_bootstrap)) {
        echo "🔄 Cargando bootstrap override específico del plugin...\n";
        require_once $override_bootstrap;
        
        // Si el override define que debe terminar aquí, respetarlo
        if (defined('DEV_TOOLS_BOOTSTRAP_OVERRIDE_COMPLETE') && DEV_TOOLS_BOOTSTRAP_OVERRIDE_COMPLETE) {
            echo "✅ Bootstrap override completado, finalizando bootstrap core.\n";
            return true;
        }
        
        echo "✅ Bootstrap override cargado, continuando con bootstrap core.\n";
    } else {
        echo "ℹ️ No se encontró bootstrap override, usando configuración core.\n";
    }
    
    return false;
}

// Intentar cargar override primero
if (load_bootstrap_override()) {
    return; // El override se encarga de todo
}

// =============================================================================
// CONFIGURACIÓN WORDPRESS TESTING FRAMEWORK - ESTÁNDAR OFICIAL
// =============================================================================

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    // Usar nuestro wordpress-develop local
    $_tests_dir = dirname( dirname( __FILE__ ) ) . '/wordpress-develop/tests/phpunit';
}

// CRÍTICO: Configurar la ruta del archivo de configuración ANTES de cargar el framework
$config_file_path = dirname( dirname( __FILE__ ) ) . '/wp-tests-config.php';
if ( ! file_exists( $config_file_path ) ) {
    echo "Error: wp-tests-config.php no encontrado en: $config_file_path" . PHP_EOL;
    exit( 1 );
}

// Definir la constante que espera WordPress
define( 'WP_TESTS_CONFIG_FILE_PATH', $config_file_path );

// Forward custom PHPUnit Polyfills directory.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) && false !== getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit( 1 );
}

echo "✅ Configuración WordPress testing establecida\n";
echo "📁 Tests dir: $_tests_dir\n";
echo "📄 Config file: $config_file_path\n";

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested - ESTÁNDAR WORDPRESS
 */
function _manually_load_plugin() {
    echo "🏗️ Cargando Dev-Tools Arquitectura 3.0...\n";

    
    // Cargar Dev-Tools configuration
    $dev_tools_root = dirname( dirname( __FILE__ ) );
    require_once $dev_tools_root . '/config.php';
    echo "✅ Configuración cargada\n";
    
    // Cargar Dev-Tools loader (sistema modular)
    require_once $dev_tools_root . '/loader.php';
    echo "✅ Loader principal cargado\n";
    
    // Cargar AJAX handler
    require_once $dev_tools_root . '/ajax-handler.php';
    echo "✅ AJAX Handler cargado\n";
    
    // Cargar plugin host si está disponible
    $plugin_root = dirname( $dev_tools_root );
    $plugin_main_file = $plugin_root . '/tarokina-pro.php';
    if ( file_exists( $plugin_main_file ) ) {
        require_once $plugin_main_file;
        echo "✅ Plugin host cargado: Tarokina Pro\n";
    } else {
        echo "ℹ️ Ejecutando dev-tools en modo independiente\n";
    }
    
    // Verificar que los módulos estén disponibles
    if ( class_exists( 'DevToolsModuleManager' ) ) {
        echo "✅ Sistema de módulos disponible\n";
    } else {
        echo "⚠️ Sistema de módulos no disponible\n";
    }
}

// Hook para cargar nuestro plugin usando el estándar WordPress
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment - ESTÁNDAR WORDPRESS
require $_tests_dir . '/includes/bootstrap.php';

// =============================================================================
// CARGAR CLASE BASE DE TESTING
// =============================================================================

// Cargar nuestra clase base personalizada
$test_case_file = __DIR__ . '/DevToolsTestCase.php';
if ( file_exists( $test_case_file ) ) {
    require_once $test_case_file;
    echo "✅ DevToolsTestCase cargada\n";
} else {
    echo "⚠️ DevToolsTestCase no encontrada - usando WP_UnitTestCase estándar\n";
}

// =============================================================================
// CONFIGURACIÓN FINAL
// =============================================================================

// Hook para después de que WordPress esté completamente cargado
add_action( 'init', function() {
    echo "🎉 Sistema completo iniciado - WordPress + Dev-Tools Arquitectura 3.0\n";
    
    // Verificar que los módulos estén disponibles
    if ( class_exists( 'DevToolsModuleManager' ) ) {
        $manager = DevToolsModuleManager::getInstance();
        $modules = $manager->getModulesStatus();
        echo '📦 Módulos cargados: ' . implode( ', ', array_keys( $modules ) ) . "\n";
    }
    
    // Información del entorno (plugin-agnóstico)
    $current_theme = wp_get_theme();
    echo "🎨 Tema activo: {$current_theme->get('Name')}\n";
    echo "🔗 Site URL: " . get_site_url() . "\n";
}, 1 );

echo "✅ Bootstrap completado - Listo para tests de Arquitectura 3.0\n";