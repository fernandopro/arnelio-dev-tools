<?php
/**
 * WordPress Test Configuration File
 * 
 * Configuración oficial para WordPress PHPUnit Test Suite
 * Compatible con Local by WP Engine usando DatabaseConnectionModule
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 * @version 3.0.0
 * 
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */

// =============================================================================
// CONFIGURACIÓN USANDO DATABASE CONNECTION MODULE
// =============================================================================

// Cargar el DatabaseConnectionModule para obtener configuración automática
require_once dirname(__FILE__) . '/modules/DatabaseConnectionModule.php';

// Crear instancia del módulo de conexión
$db_module = new DatabaseConnectionModule(true); // Debug habilitado para tests
$env_info = $db_module->get_environment_info();

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS PARA TESTS
// =============================================================================

// Usar la MISMA base de datos de WordPress pero con prefijo diferente
// Solo redefinir si no están ya definidas (evitar conflicts)
if (!defined('DB_NAME')) define( 'DB_NAME', $env_info['wp_db_name'] ); 
if (!defined('DB_USER')) define( 'DB_USER', $env_info['wp_db_user'] ); 
// DB_PASSWORD se obtiene de wp-config.php que ya se carga en bootstrap.php
if (!defined('DB_HOST')) define( 'DB_HOST', $env_info['wp_db_host'] );

// Charset y collation
if (!defined('DB_CHARSET')) define( 'DB_CHARSET', 'utf8mb4' );
if (!defined('DB_COLLATE')) define( 'DB_COLLATE', '' );

// IMPORTANTE: Prefijo DIFERENTE para tests (evita conflictos)
$table_prefix = 'test_';

// =============================================================================
// CONFIGURACIÓN DEL SITIO DE TESTS
// =============================================================================

// Dominio para tests (detectado automáticamente)
$test_domain = 'localhost';
if (defined('WP_HOME')) {
    $parsed_url = parse_url(WP_HOME);
    if (isset($parsed_url['host'])) {
        $test_domain = $parsed_url['host'];
        if (isset($parsed_url['port'])) {
            $test_domain .= ':' . $parsed_url['port'];
        }
    }
}

define( 'WP_TESTS_DOMAIN', getenv('WP_TESTS_DOMAIN') ?: $test_domain );
define( 'WP_TESTS_EMAIL', getenv('WP_TESTS_EMAIL') ?: 'admin@' . WP_TESTS_DOMAIN );
define( 'WP_TESTS_TITLE', getenv('WP_TESTS_TITLE') ?: 'WordPress Test Site' );

// =============================================================================
// CONFIGURACIÓN DE WORDPRESS PARA TESTS
// =============================================================================

// Detectar ruta de WordPress automáticamente
if ( ! defined( 'ABSPATH' ) ) {
    $wp_core_dir = getenv('WP_CORE_DIR');
    if ( $wp_core_dir ) {
        define( 'ABSPATH', $wp_core_dir . '/' );
    } else {
        // Buscar WordPress en rutas comunes para Local by WP Engine
        $search_paths = [
            dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/',
            dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/',
        ];
        
        foreach ($search_paths as $search_path) {
            if (file_exists($search_path . 'wp-config.php')) {
                define( 'ABSPATH', $search_path );
                break;
            }
        }
        
        // Fallback
        if (!defined('ABSPATH')) {
            define( 'ABSPATH', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/' );
        }
    }
}

// URL del sitio para tests  
if (!defined('WP_SITEURL')) define( 'WP_SITEURL', 'http://' . WP_TESTS_DOMAIN );
if (!defined('WP_HOME')) define( 'WP_HOME', 'http://' . WP_TESTS_DOMAIN );

// =============================================================================
// KEYS Y SALTS PARA TESTS (No usar en producción)
// =============================================================================

if (!defined('AUTH_KEY')) define( 'AUTH_KEY',         'test-auth-key-for-testing-only' );
if (!defined('SECURE_AUTH_KEY')) define( 'SECURE_AUTH_KEY',  'test-secure-auth-key-for-testing-only' );
if (!defined('LOGGED_IN_KEY')) define( 'LOGGED_IN_KEY',    'test-logged-in-key-for-testing-only' );
if (!defined('NONCE_KEY')) define( 'NONCE_KEY',        'test-nonce-key-for-testing-only' );
if (!defined('AUTH_SALT')) define( 'AUTH_SALT',        'test-auth-salt-for-testing-only' );
if (!defined('SECURE_AUTH_SALT')) define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt-for-testing-only' );
if (!defined('LOGGED_IN_SALT')) define( 'LOGGED_IN_SALT',   'test-logged-in-salt-for-testing-only' );
if (!defined('NONCE_SALT')) define( 'NONCE_SALT',       'test-nonce-salt-for-testing-only' );

// =============================================================================
// CONFIGURACIÓN DE DEBUG Y DESARROLLO
// =============================================================================

if (!defined('WP_DEBUG')) define( 'WP_DEBUG', true );
if (!defined('WP_DEBUG_LOG')) define( 'WP_DEBUG_LOG', true );
if (!defined('WP_DEBUG_DISPLAY')) define( 'WP_DEBUG_DISPLAY', false );

// Configuraciones específicas para tests
if (!defined('WP_DEFAULT_THEME')) define( 'WP_DEFAULT_THEME', 'twentytwentyfour' );
if (!defined('WP_TESTS_FORCE_KNOWN_BUGS')) define( 'WP_TESTS_FORCE_KNOWN_BUGS', false );
if (!defined('WPLANG')) define( 'WPLANG', '' );
if (!defined('WP_PHP_BINARY')) define( 'WP_PHP_BINARY', PHP_BINARY );
if (!defined('WP_TESTS_MULTISITE')) define( 'WP_TESTS_MULTISITE', false );

// =============================================================================
// CONFIGURACIÓN ESPECÍFICA DE DEV-TOOLS
// =============================================================================

// Definir que estamos en entorno de testing
if (!defined('DEV_TOOLS_TESTING')) define( 'DEV_TOOLS_TESTING', true );

// Configuración verbose para debugging
if (!defined('DEV_TOOLS_TESTS_VERBOSE')) {
    define( 'DEV_TOOLS_TESTS_VERBOSE', getenv('DEV_TOOLS_TESTS_VERBOSE') === 'true' );
}

// Debug inicial si está habilitado
if (DEV_TOOLS_TESTS_VERBOSE) {
    error_log('[DEV-TOOLS TEST] WordPress Test Configuration Loaded');
    error_log('[DEV-TOOLS TEST] Database: ' . DB_NAME . '@' . DB_HOST);
    error_log('[DEV-TOOLS TEST] Site URL: ' . WP_SITEURL);
    error_log('[DEV-TOOLS TEST] Table Prefix: ' . $table_prefix);
    error_log('[DEV-TOOLS TEST] Environment Info: ' . json_encode($env_info));
}
