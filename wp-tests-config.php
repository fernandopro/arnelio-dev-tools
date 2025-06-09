<?php
/**
 * Configuración de Base de Datos para Tests - Plugin-Agnóstica
 * 
 * Configuración genérica para dev-tools que funciona con cualquier plugin.
 * Las configuraciones específicas del plugin deben ir en wp-tests-config-local.php
 * 
 * Este archivo será cargado automáticamente por el framework oficial de WordPress PHPUnit.
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 * @version 3.0.0
 */

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS (Local by Flywheel genérica)
// =============================================================================

/**
 * Detecta automáticamente la clave del socket de MySQL para Local by Flywheel
 * 
 * @return string|false Clave del socket o false si no se encuentra
 */
function dev_tools_detect_socket_key() {
    // 1. Detectar desde variables de entorno
    $socket_key = getenv('LOCAL_SOCKET_KEY');
    if ($socket_key) {
        return $socket_key;
    }
    
    // 2. Detectar desde configuración del sitio actual (wp-config.php)
    $abspath = defined('ABSPATH') ? ABSPATH : __DIR__ . '/../../../';
    $wp_config_path = $abspath . 'wp-config.php';
    if (file_exists($wp_config_path)) {
        $wp_config_content = file_get_contents($wp_config_path);
        if (preg_match("/DB_HOST['\"],\s*['\"][^'\"]*\/([^\/]+)\/mysql\/mysqld\.sock/", $wp_config_content, $matches)) {
            return $matches[1];
        }
    }
    
    // 3. Buscar directorios de socket existentes en Local
    $local_run_path = '/Users/' . get_current_user() . '/Library/Application Support/Local/run/';
    if (is_dir($local_run_path)) {
        $socket_dirs = glob($local_run_path . '*/mysql/mysqld.sock');
        if (!empty($socket_dirs)) {
            // Usar el socket más reciente por fecha
            usort($socket_dirs, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $latest_socket = $socket_dirs[0];
            if (preg_match('/\/([^\/]+)\/mysql\/mysqld\.sock$/', $latest_socket, $matches)) {
                return $matches[1];
            }
        }
    }
    
    // 4. Fallback: buscar socket key común
    return false;
}

/**
 * Función auxiliar para detectar el puerto dinámicamente desde Local by Flywheel
 * 
 * @return int|false Puerto detectado o false si no se puede detectar
 */
function dev_tools_detect_local_port() {
    // 1. Detectar desde variables de entorno si están disponibles
    $env_port = getenv('WP_TESTS_PORT') ?: getenv('LOCAL_PORT');
    if ($env_port && is_numeric($env_port)) {
        return (int) $env_port;
    }
    
    // 2. Detectar desde get_site_url() si WordPress está cargado
    if (function_exists('get_site_url')) {
        $site_url = get_site_url();
        if ($site_url && preg_match('/localhost:(\d+)/', $site_url, $matches)) {
            return (int) $matches[1];
        }
    }
    
    // 3. Detectar desde wp-config.php
    $wp_config_path = defined('ABSPATH') ? ABSPATH . 'wp-config.php' : null;
    if (!$wp_config_path) {
        // Buscar wp-config.php relativamente
        $search_paths = [
            __DIR__ . '/../../../wp-config.php',
            __DIR__ . '/../../../../wp-config.php',
            __DIR__ . '/../../../../../wp-config.php'
        ];
        
        foreach ($search_paths as $search_path) {
            if (file_exists($search_path)) {
                $wp_config_path = $search_path;
                break;
            }
        }
    }
    
    if ($wp_config_path && file_exists($wp_config_path)) {
        $wp_config_content = file_get_contents($wp_config_path);
        
        // Buscar puerto en WP_HOME o WP_SITEURL
        $url_patterns = [
            "/define\s*\(\s*['\"]WP_HOME['\"],\s*['\"]([^'\"]+)['\"]\s*\)/",
            "/define\s*\(\s*['\"]WP_SITEURL['\"],\s*['\"]([^'\"]+)['\"]\s*\)/"
        ];
        
        foreach ($url_patterns as $pattern) {
            if (preg_match($pattern, $wp_config_content, $matches)) {
                if (preg_match('/localhost:(\d+)/', $matches[1], $port_matches)) {
                    return (int) $port_matches[1];
                }
            }
        }
    }
    
    return false; // No se pudo detectar
}

/**
 * Función auxiliar para obtener la URL del sitio de manera confiable
 * Carga el entorno de WordPress para usar get_site_url() nativo
 */
function dev_tools_get_test_site_url() {
    // 1. Prioridad a variables de entorno específicas para tests
    if ($domain = getenv('WP_TESTS_DOMAIN')) {
        return 'http://' . $domain;
    }
    
    // 2. Cargar el entorno de WordPress para usar get_site_url()
    $wp_load_paths = [
        dirname( __DIR__ ) . '/wp-load.php',
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../../../wp-load.php'
    ];
    
    foreach ($wp_load_paths as $wp_load_path) {
        if (file_exists($wp_load_path)) {
            // Evitar cargar múltiples veces
            if (!defined('WPINC')) {
                require_once $wp_load_path;
            }
            
            // Usar get_site_url() nativo de WordPress si está disponible
            if (function_exists('get_site_url')) {
                $site_url = get_site_url();
                if (!empty($site_url)) {
                    return $site_url;
                }
            }
            break;
        }
    }
    
    // 3. Detectar puerto dinámicamente desde servidor local
    $dynamic_port = dev_tools_detect_local_port();
    if ($dynamic_port) {
        return 'http://localhost:' . $dynamic_port;
    }
    
    // 4. Fallback final: usar puerto genérico
    return 'http://localhost:8080';
}

// Autodetectar socket key para Local by Flywheel
$socket_key = dev_tools_detect_socket_key();

// Configuración de base de datos con fallbacks inteligentes
define( 'DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'local' );
define( 'DB_USER', getenv('WP_TESTS_DB_USER') ?: 'root' );
define( 'DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: 'root' );

// Host con socket automático o fallback a localhost
if ($socket_key) {
    define( 'DB_HOST', 'localhost:/Users/' . get_current_user() . '/Library/Application Support/Local/run/' . $socket_key . '/mysql/mysqld.sock' );
} else {
    define( 'DB_HOST', getenv('WP_TESTS_DB_HOST') ?: 'localhost' );
}

define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Prefijo de tablas genérico para tests
$table_prefix = getenv('WP_TESTS_TABLE_PREFIX') ?: 'wp_test_';

// Definir constantes para debugging
define( 'DEV_TOOLS_TABLE_PREFIX', $table_prefix );

// Definir dominio de pruebas
if (!defined('WP_TESTS_DOMAIN')) {
    $test_domain = getenv('WP_TESTS_DOMAIN') ?: 'localhost:10019';
    define( 'WP_TESTS_DOMAIN', $test_domain );
}

// =============================================================================
// CONFIGURACIÓN MÍNIMA DE WORDPRESS
// =============================================================================

// Ubicación de WordPress (detección automática)
if (!defined('ABSPATH')) {
    $wp_core_dir = getenv('WP_CORE_DIR');
    if ($wp_core_dir) {
        define( 'ABSPATH', $wp_core_dir . '/' );
    } else {
        // Buscar ABSPATH relativamente
        $search_paths = [
            __DIR__ . '/../../../',
            __DIR__ . '/../../../../',
            __DIR__ . '/../../../../../'
        ];
        
        foreach ($search_paths as $search_path) {
            if (file_exists($search_path . 'wp-config.php')) {
                define( 'ABSPATH', realpath($search_path) . '/' );
                break;
            }
        }
        
        // Fallback final
        if (!defined('ABSPATH')) {
            define( 'ABSPATH', __DIR__ . '/../../../' );
        }
    }
}

// URL del sitio para tests (usando detección automática)
$test_site_url = dev_tools_get_test_site_url();
define( 'WP_SITEURL', $test_site_url );
define( 'WP_HOME', $test_site_url );

// Definir constantes adicionales para debugging 
define( 'DEV_TOOLS_TEST_SITE_URL', $test_site_url );

// Configuración básica de testing genérica
define( 'WP_DEBUG', true );
define( 'WP_TESTS_EMAIL', 'admin@localhost' );
define( 'WP_TESTS_TITLE', 'Dev-Tools Testing Site' );
define( 'WP_PHP_BINARY', PHP_BINARY );
define( 'WP_TESTS_MULTISITE', false );

// Configuraciones adicionales recomendadas por WordPress
define( 'WP_DEFAULT_THEME', 'twentytwentyfour' );
define( 'WP_TESTS_FORCE_KNOWN_BUGS', false );
define( 'WPLANG', '' );

// Keys y salts genéricos para testing
define( 'AUTH_KEY',         'test-auth-key-generic' );
define( 'SECURE_AUTH_KEY',  'test-secure-auth-key-generic' );
define( 'LOGGED_IN_KEY',    'test-logged-in-key-generic' );
define( 'NONCE_KEY',        'test-nonce-key-generic' );
define( 'AUTH_SALT',        'test-auth-salt-generic' );
define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt-generic' );
define( 'LOGGED_IN_SALT',   'test-logged-in-salt-generic' );
define( 'NONCE_SALT',       'test-nonce-salt-generic' );

// =============================================================================
// CONSTANTES DE DEBUG PARA TESTS
// =============================================================================

if (!defined('DEV_TOOLS_TESTS_VERBOSE')) {
    define('DEV_TOOLS_TESTS_VERBOSE', getenv('DEV_TOOLS_TESTS_VERBOSE') === 'true' || getenv('DEV_TOOLS_TESTS_VERBOSE') === '1');
}

if (!defined('DEV_TOOLS_FORCE_DEBUG')) {
    define('DEV_TOOLS_FORCE_DEBUG', getenv('DEV_TOOLS_FORCE_DEBUG') === 'true' || getenv('DEV_TOOLS_FORCE_DEBUG') === '1');
}

if (!defined('WP_TESTS_INDIVIDUAL')) {
    define('WP_TESTS_INDIVIDUAL', getenv('WP_TESTS_INDIVIDUAL') === 'true' || getenv('WP_TESTS_INDIVIDUAL') === '1');
}

// =============================================================================
// CARGA DE CONFIGURACIÓN LOCAL (PLUGIN-ESPECÍFICA)
// =============================================================================

// Cargar configuración específica del plugin si existe
$local_config_files = [
    __DIR__ . '/wp-tests-config-local.php'
];

foreach ($local_config_files as $local_config_file) {
    if (file_exists($local_config_file)) {
        require_once $local_config_file;
        break;
    }
}

// =============================================================================
// SISTEMA DE DEBUG CONDICIONAL
// =============================================================================

/**
 * Función para mostrar información de debug SOLO cuando hay fallos en tests
 */
function dev_tools_show_debug_on_failure() {
    static $debug_shown = false;
    
    if ($debug_shown) {
        return;
    }
    
    $debug_shown = true;
    
    $should_show_debug = false;
    
    if (defined('DEV_TOOLS_TESTS_VERBOSE') && DEV_TOOLS_TESTS_VERBOSE) {
        $should_show_debug = true;
    }
    
    if (defined('DEV_TOOLS_FORCE_DEBUG') && DEV_TOOLS_FORCE_DEBUG) {
        $should_show_debug = true;
    }
    
    if (defined('WP_TESTS_INDIVIDUAL') && WP_TESTS_INDIVIDUAL && WP_DEBUG) {
        $should_show_debug = true;
    }
    
    if ($should_show_debug) {
        error_log( '=== DEV TOOLS TESTS CONFIG - Generic ===' );
        error_log( 'Base de datos: ' . DB_NAME . '@' . DB_HOST );
        error_log( 'Prefijo tablas: ' . DEV_TOOLS_TABLE_PREFIX );
        error_log( 'Sitio URL: ' . DEV_TOOLS_TEST_SITE_URL );
        if ( defined( 'DEV_TOOLS_PLUGIN_FILE' ) ) {
            error_log( 'Plugin: ' . DEV_TOOLS_PLUGIN_FILE );
            error_log( 'Plugin existe: ' . ( file_exists( DEV_TOOLS_PLUGIN_FILE ) ? 'Sí' : 'No' ) );
        } else {
            error_log( 'Plugin: No detectado - usando configuración genérica' );
        }
        error_log( '===============================================' );
    }
}

// Registrar la función de debug en variable global
$GLOBALS['dev_tools_debug_function'] = 'dev_tools_show_debug_on_failure';

// Debug inicial solo en casos específicos
$show_initial_debug = false;

if (defined('WP_TESTS_INDIVIDUAL') && WP_TESTS_INDIVIDUAL) {
    $show_initial_debug = true;
}

if (defined('DEV_TOOLS_TESTS_VERBOSE') && DEV_TOOLS_TESTS_VERBOSE) {
    $show_initial_debug = true;
}

if (defined('DEV_TOOLS_FORCE_DEBUG') && DEV_TOOLS_FORCE_DEBUG) {
    $show_initial_debug = true;
}

if ($show_initial_debug && WP_DEBUG) {
    error_log( '=== DEV TOOLS TESTS CONFIG - Generic ===' );
    error_log( 'Base de datos: ' . DB_NAME . '@' . DB_HOST );
    error_log( 'Prefijo tablas: ' . DEV_TOOLS_TABLE_PREFIX );
    error_log( 'Sitio URL: ' . DEV_TOOLS_TEST_SITE_URL );
    error_log( '=============================================' );
}
