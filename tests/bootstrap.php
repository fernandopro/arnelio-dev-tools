<?php
/**
 * Bootstrap para WordPress PHPUnit Tests - Dev-Tools Arquitectura 3.0
 * 
 * Este archivo sigue las mejores prácticas oficiales de WordPress Core Testing.
 * @see https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

// Configuración de error reporting para tests
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

// Definir que estamos en modo testing
if ( ! defined( 'WP_TESTS_RUNNING' ) ) {
    define( 'WP_TESTS_RUNNING', true );
}

// Directorio base del plugin
define( 'DEV_TOOLS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'DEV_TOOLS_TESTS_DIR', __DIR__ );

// Archivo principal del plugin
define( 'DEV_TOOLS_PLUGIN_FILE', DEV_TOOLS_PLUGIN_DIR . '/loader.php' );

// Verificar que el plugin existe
if ( ! file_exists( DEV_TOOLS_PLUGIN_FILE ) ) {
    die( 'Error: No se puede encontrar el archivo principal del plugin en: ' . DEV_TOOLS_PLUGIN_FILE );
}

// Cargar PHPUnit Polyfills si está disponible
$polyfills_path = DEV_TOOLS_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills_path ) ) {
    require_once $polyfills_path;
} else {
    echo "Advertencia: PHPUnit Polyfills no encontrado en: {$polyfills_path}\n";
}

// Configuración de WordPress para tests
$config_file_path = DEV_TOOLS_PLUGIN_DIR . '/wp-tests-config.php';
if ( ! file_exists( $config_file_path ) ) {
    die( 'Error: Archivo de configuración wp-tests-config.php no encontrado en: ' . $config_file_path );
}

// Cargar configuración de WordPress para tests
require_once $config_file_path;

// Buscar WordPress Test Suite
$wordpress_tests_dir = null;

// Opciones para encontrar WordPress Test Suite (en orden de prioridad)
$test_suite_paths = [
    // Directorio específico desde variable de entorno
    getenv( 'WP_TESTS_DIR' ),
    
    // Ubicaciones comunes para Local by WP Engine y sistemas Unix
    '/tmp/wordpress-tests-lib',
    
    // Ubicaciones estándar del sistema
    '/usr/local/src/wordpress-tests-lib',
    '/var/www/wordpress-tests-lib',
    
    // Ubicación en el directorio padre (instalación manual)
    dirname( dirname( DEV_TOOLS_PLUGIN_DIR ) ) . '/wordpress-tests-lib',
    
    // Fallback local (para cuando se instala localmente)
    DEV_TOOLS_PLUGIN_DIR . '/wordpress-tests-lib'
];

// Buscar WordPress Test Suite
foreach ( $test_suite_paths as $path ) {
    if ( $path && file_exists( $path . '/includes/functions.php' ) ) {
        $wordpress_tests_dir = $path;
        break;
    }
}

// Si no se encuentra WordPress Test Suite, intentar instalarlo automáticamente
if ( ! $wordpress_tests_dir ) {
    echo "WordPress Test Suite no encontrado. Instalando automáticamente...\n";
    
    // Intentar instalación automática para Local by WP Engine
    $install_dir = '/tmp/wordpress-tests-lib';
    $install_script = DEV_TOOLS_PLUGIN_DIR . '/install-wp-tests.sh';
    
    if ( file_exists( $install_script ) ) {
        // Usar configuración de DatabaseConnectionModule para la instalación
        require_once DEV_TOOLS_PLUGIN_DIR . '/modules/DatabaseConnectionModule.php';
        $db_module = new DatabaseConnectionModule();
        $env_info = $db_module->get_environment_info();
        
        echo "Ejecutando instalación con configuración detectada...\n";
        echo "Base de datos: " . $env_info['wp_db_name'] . "\n";
        echo "Usuario: " . $env_info['wp_db_user'] . "\n";
        echo "Host: " . $env_info['wp_db_host'] . "\n";
        
        // Ejecutar script de instalación
        $install_command = sprintf(
            'bash %s %s %s %s %s latest true',
            escapeshellarg($install_script),
            escapeshellarg($env_info['wp_db_name']),
            escapeshellarg($env_info['wp_db_user']),
            escapeshellarg(DB_PASSWORD),
            escapeshellarg($env_info['wp_db_host'])
        );
        
        $output = [];
        $return_code = 0;
        exec($install_command . ' 2>&1', $output, $return_code);
        
        if ($return_code === 0 && file_exists($install_dir . '/includes/functions.php')) {
            $wordpress_tests_dir = $install_dir;
            echo "✅ WordPress Test Suite instalado correctamente\n";
        } else {
            echo "❌ Error en la instalación:\n";
            echo implode("\n", $output) . "\n";
            die('Instalación fallida. Por favor, instale manualmente.');
        }
    } else {
        die( 'Error: Script de instalación no encontrado en: ' . $install_script );
    }
}

// Establecer directorio de WordPress Tests
define( 'WP_TESTS_DIR', $wordpress_tests_dir );

// Mostrar información de configuración si está en modo verbose
if ( defined( 'DEV_TOOLS_TESTS_VERBOSE' ) && DEV_TOOLS_TESTS_VERBOSE ) {
    echo "=== DEV TOOLS TESTS BOOTSTRAP ===\n";
    echo "Plugin Dir: " . DEV_TOOLS_PLUGIN_DIR . "\n";
    echo "Tests Dir: " . DEV_TOOLS_TESTS_DIR . "\n";
    echo "WordPress Tests Dir: " . $wordpress_tests_dir . "\n";
    echo "Config File: " . $config_file_path . "\n";
    echo "=====================================\n";
}

// Cargar funciones de test de WordPress
require_once $wordpress_tests_dir . '/includes/functions.php';

/**
 * Verificar que las funciones de WordPress Test están disponibles
 */
if ( ! function_exists( 'tests_add_filter' ) ) {
    die( 'Error: WordPress Test Suite functions not loaded properly. Check your installation.' );
}

/**
 * Función para cargar el plugin antes de que WordPress se inicialice
 * Esta función se ejecuta antes de que WordPress cargue sus plugins
 */
function dev_tools_manually_load_plugin() {
    // Cargar Composer autoloader
    $composer_autoload = DEV_TOOLS_PLUGIN_DIR . '/vendor/autoload.php';
    if ( file_exists( $composer_autoload ) ) {
        require_once $composer_autoload;
    }
    
    // Cargar el plugin principal
    require DEV_TOOLS_PLUGIN_FILE;
    
    // Mostrar confirmación si está en modo verbose
    if ( defined( 'DEV_TOOLS_TESTS_VERBOSE' ) && DEV_TOOLS_TESTS_VERBOSE ) {
        echo "Plugin Dev-Tools cargado para tests\n";
    }
}

// Registrar la función para cargar el plugin
tests_add_filter( 'muplugins_loaded', 'dev_tools_manually_load_plugin' );

/**
 * Configurar entorno después de que WordPress se haya inicializado
 */
function dev_tools_setup_test_environment() {
    // Configuraciones adicionales específicas para tests
    if ( ! defined( 'WP_DEBUG' ) ) {
        define( 'WP_DEBUG', true );
    }
    
    if ( ! defined( 'WP_DEBUG_LOG' ) ) {
        define( 'WP_DEBUG_LOG', true );
    }
    
    // Limpiar opciones que pueden interferir con tests
    delete_option( 'dev_tools_cache' );
    delete_transient( 'dev_tools_last_check' );
    
    // Configurar capacidades de usuario para tests
    $admin_user = get_user_by( 'login', 'admin' );
    if ( $admin_user ) {
        $admin_user->add_cap( 'manage_dev_tools' );
    }
    
    if ( defined( 'DEV_TOOLS_TESTS_VERBOSE' ) && DEV_TOOLS_TESTS_VERBOSE ) {
        echo "Entorno de test configurado\n";
    }
}

// Registrar configuración de entorno
tests_add_filter( 'wp_loaded', 'dev_tools_setup_test_environment' );

// Incluir el bootstrap de WordPress
require $wordpress_tests_dir . '/includes/bootstrap.php';

// Cargar clases base para tests
require_once DEV_TOOLS_TESTS_DIR . '/includes/class-dev-tools-test-case.php';
require_once DEV_TOOLS_TESTS_DIR . '/includes/class-dev-tools-ajax-test-case.php';

// Cargar test helpers
require_once DEV_TOOLS_TESTS_DIR . '/includes/test-helpers.php';

// Crear directorios necesarios para reports y coverage
$reports_dir = DEV_TOOLS_TESTS_DIR . '/reports';
if ( ! is_dir( $reports_dir ) ) {
    mkdir( $reports_dir, 0755, true );
}

$coverage_dir = DEV_TOOLS_TESTS_DIR . '/coverage';
if ( ! is_dir( $coverage_dir ) ) {
    mkdir( $coverage_dir, 0755, true );
}

// Mostrar información final si está en modo verbose
if ( defined( 'DEV_TOOLS_TESTS_VERBOSE' ) && DEV_TOOLS_TESTS_VERBOSE ) {
    echo "\n=== DEV TOOLS TEST ENVIRONMENT READY ===\n";
    echo "WordPress Version: " . get_bloginfo( 'version' ) . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "PHPUnit Version: " . PHPUnit\Runner\Version::id() . "\n";
    echo "Plugin Dir: " . DEV_TOOLS_PLUGIN_DIR . "\n";
    echo "Tests Dir: " . DEV_TOOLS_TESTS_DIR . "\n";
    echo "WordPress Tests Dir: " . $wordpress_tests_dir . "\n";
    echo "========================================\n\n";
}
