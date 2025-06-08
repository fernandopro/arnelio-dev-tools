<?php
/**
 * Configuración de Base de Datos para Tests - Local by Flywheel
 * 
 * Configuración minimalista para usar la misma base de datos que el sitio principal
 * pero con prefijo de tablas diferente para separar los datos de testing.
 * 
 * Este archivo será cargado automáticamente por el framework oficial de WordPress PHPUnit.
 * 
 * @package TarokinaPro
 * @subpackage DevTools\Tests
 * @since 1.0.0
 */

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS (Local by Flywheel específica)
// =============================================================================

// IMPORTANTE: Usa la MISMA base de datos que el sitio principal ('local') 
// pero con PREFIJO DIFERENTE ('wp_test_') para separar las tablas de testing
// Configuración específica para Local by Flywheel (detección automática)

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
    
    // 4. Fallback: clave actual del entorno de desarrollo
    return 'T7OGkjtdu';
}

// Insertar siempre la clave manualmente para evitar problemas de detección
$socket_key = 'T7OGkjtdu';

define( 'DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'local' );        // MISMA BD que el sitio principal
define( 'DB_USER', getenv('WP_TESTS_DB_USER') ?: 'root' );         // Usuario Local by Flywheel
define( 'DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: 'root' ); // Password Local by Flywheel  
define( 'DB_HOST', 'localhost:/Users/fernandovazquezperez/Library/Application Support/Local/run/' . $socket_key . '/mysql/mysqld.sock' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// ⚠️ CRÍTICO: Prefijo de tablas para tests DIFERENTE al prefijo principal (wp_)
// Esto garantiza que los tests no interfieran con tu sitio de desarrollo:
// - Sitio principal: tablas con prefijo 'wp_' (wp_posts, wp_options, etc.)
// - Tests: tablas con prefijo 'wp_test_' (wp_test_posts, wp_test_options, etc.)
$table_prefix = getenv('WP_TESTS_TABLE_PREFIX') ?: 'wp_test_';

// Definir constantes para debugging
define( 'DEV_TOOLS_TABLE_PREFIX', $table_prefix );

// =============================================================================
// CONFIGURACIÓN MÍNIMA DE WORDPRESS
// =============================================================================

// Ubicación de WordPress (Local by Flywheel)
define( 'ABSPATH', getenv('WP_CORE_DIR') ? getenv('WP_CORE_DIR') . '/' : '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/' );

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
    $wp_config_path = defined('ABSPATH') ? ABSPATH . 'wp-config.php' : '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/wp-config.php';
    if (file_exists($wp_config_path)) {
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
    
    // 4. Detectar desde configuración de Local by Flywheel
    $local_config_paths = [
        // Rutas típicas de configuración de Local by Flywheel
        '/Users/' . get_current_user() . '/Library/Application Support/Local/sites.json',
        '/Users/' . get_current_user() . '/Library/Application Support/Local/config.json',
    ];
    
    foreach ($local_config_paths as $config_path) {
        if (file_exists($config_path)) {
            $config_content = file_get_contents($config_path);
            $config_data = json_decode($config_content, true);
            
            if (is_array($config_data)) {
                // Buscar puerto en configuración de sitios
                $port = dev_tools_extract_port_from_config($config_data);
                if ($port) {
                    return $port;
                }
            }
        }
    }
    
    // 5. Detectar desde $_SERVER si está en contexto web
    if (isset($_SERVER['SERVER_PORT']) && is_numeric($_SERVER['SERVER_PORT'])) {
        return (int) $_SERVER['SERVER_PORT'];
    }
    
    // 6. Detectar desde HTTP_HOST si está disponible
    if (isset($_SERVER['HTTP_HOST']) && preg_match('/localhost:(\d+)/', $_SERVER['HTTP_HOST'], $matches)) {
        return (int) $matches[1];
    }
    
    return false; // No se pudo detectar
}

/**
 * Función auxiliar para extraer puerto de configuración de Local by Flywheel
 * 
 * @param array $config_data Datos de configuración parseados
 * @return int|false Puerto encontrado o false
 */
function dev_tools_extract_port_from_config($config_data) {
    // Buscar en diferentes estructuras de configuración de Local
    $search_patterns = [
        'sites.*.port',
        'sites.*.nginx.port', 
        'port',
        'nginx.port',
        'server.port'
    ];
    
    foreach ($search_patterns as $pattern) {
        $port = dev_tools_get_nested_config_value($config_data, $pattern);
        if ($port && is_numeric($port)) {
            return (int) $port;
        }
    }
    
    return false;
}

/**
 * Función auxiliar para obtener valores anidados de configuración
 * 
 * @param array $data Datos de configuración
 * @param string $path Ruta con notación de puntos (ej: 'sites.*.port')
 * @return mixed Valor encontrado o null
 */
function dev_tools_get_nested_config_value($data, $path) {
    $keys = explode('.', $path);
    $current = $data;
    
    foreach ($keys as $key) {
        if ($key === '*') {
            // Wildcard: buscar en todos los elementos del array
            if (is_array($current)) {
                foreach ($current as $item) {
                    if (is_array($item)) {
                        $remaining_path = implode('.', array_slice($keys, array_search('*', $keys) + 1));
                        $result = dev_tools_get_nested_config_value($item, $remaining_path);
                        if ($result !== null) {
                            return $result;
                        }
                    }
                }
            }
            return null;
        } else {
            if (!is_array($current) || !isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }
    }
    
    return $current;
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
    $wp_load_path = dirname( __DIR__ ) . '/wp-load.php';
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
    }
    
    // 3. Fallback: detectar desde wp-config.php si WordPress no está cargado
    $wp_config_path = defined('ABSPATH') ? ABSPATH . 'wp-config.php' : '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/wp-config.php';
    if (file_exists($wp_config_path)) {
        $wp_config_content = file_get_contents($wp_config_path);
        
        // Buscar WP_HOME o WP_SITEURL en wp-config.php
        if (preg_match("/define\s*\(\s*['\"]WP_HOME['\"],\s*['\"]([^'\"]+)['\"]\s*\)/", $wp_config_content, $matches)) {
            return $matches[1];
        }
        if (preg_match("/define\s*\(\s*['\"]WP_SITEURL['\"],\s*['\"]([^'\"]+)['\"]\s*\)/", $wp_config_content, $matches)) {
            return $matches[1];
        }
    }
    
    // 4. Fallback: detectar puerto dinámicamente desde servidor local
    $dynamic_port = dev_tools_detect_local_port();
    if ($dynamic_port) {
        return 'http://localhost:' . $dynamic_port;
    }
    
    // 5. Fallback final: usar puerto por defecto de Local by Flywheel
    return 'http://localhost:10019';
}

// URL del sitio para tests (usando get_site_url() nativo cuando es posible)
$test_site_url = dev_tools_get_test_site_url();
define( 'WP_SITEURL', $test_site_url );
define( 'WP_HOME', $test_site_url );

// Definir constantes adicionales para debugging 
define( 'DEV_TOOLS_TEST_SITE_URL', $test_site_url );

// Configuración básica de testing
define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', 'localhost:10019' );
define( 'WP_TESTS_EMAIL', 'admin@localhost' );
define( 'WP_TESTS_TITLE', 'Tarokina Pro Testing Site' );
define( 'WP_PHP_BINARY', PHP_BINARY );
define( 'WP_TESTS_MULTISITE', false );

// Configuraciones adicionales recomendadas por WordPress
define( 'WP_DEFAULT_THEME', 'twentytwentyfour' );
define( 'WP_TESTS_FORCE_KNOWN_BUGS', false );
define( 'WPLANG', '' );

// Keys y salts simples para testing
define( 'AUTH_KEY',         'test-auth-key-tarokina' );
define( 'SECURE_AUTH_KEY',  'test-secure-auth-key-tarokina' );
define( 'LOGGED_IN_KEY',    'test-logged-in-key-tarokina' );
define( 'NONCE_KEY',        'test-nonce-key-tarokina' );
define( 'AUTH_SALT',        'test-auth-salt-tarokina' );
define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt-tarokina' );
define( 'LOGGED_IN_SALT',   'test-logged-in-salt-tarokina' );
define( 'NONCE_SALT',       'test-nonce-salt-tarokina' );

// =============================================================================
// CONSTANTES DE DEBUG PARA TESTS
// =============================================================================

// Definir constantes de debug que pueden ser usadas en las funciones
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
// CONFIGURACIÓN DEL PLUGIN TAROKINA PRO
// =============================================================================

// Ruta específica al plugin Tarokina Pro
$dev_tools_dir = __DIR__;
$plugin_dir = dirname( $dev_tools_dir );
$tarokina_plugin_file = $plugin_dir . '/tarokina-pro.php';

// Verificar que el archivo del plugin existe
if ( file_exists( $tarokina_plugin_file ) ) {
    define( 'DEV_TOOLS_PLUGIN_FILE', $tarokina_plugin_file );
    define( 'DEV_TOOLS_TESTING', true );
    define( 'TAROKINA_TESTING_MODE', true );
    
    /**
     * Función para cargar el plugin Tarokina Pro durante los tests
     */
    function dev_tools_load_tarokina_plugin() {
        $plugin_file = DEV_TOOLS_PLUGIN_FILE;
        
        if ( file_exists( $plugin_file ) ) {
            // Cargar el plugin principal
            include_once $plugin_file;
            
            // Verificar que las constantes del plugin se cargaron
            if ( defined( 'TKINA_TAROKINA_PRO_DIR_PATH' ) ) {
                error_log( '✅ TAROKINA TESTS: Plugin Tarokina Pro cargado exitosamente' );
                error_log( '📁 Plugin Path: ' . TKINA_TAROKINA_PRO_DIR_PATH );
                error_log( '🌐 Plugin URL: ' . ( defined( 'TKINA_TAROKINA_PRO_DIR_URL' ) ? TKINA_TAROKINA_PRO_DIR_URL : 'N/A' ) );
                
                // Trigger activation hooks si es necesario
                if ( function_exists( 'activate_tarokina_master' ) ) {
                    error_log( '🔧 TAROKINA TESTS: Ejecutando hooks de activación...' );
                }
            } else {
                error_log( '⚠️  TAROKINA TESTS: Plugin cargado pero constantes no definidas' );
            }
        } else {
            error_log( '❌ TAROKINA TESTS: Archivo del plugin no encontrado: ' . $plugin_file );
        }
    }
    
    /**
     * Función de verificación post-carga del plugin
     */
    function dev_tools_verify_tarokina_plugin() {
        // Verificar Custom Post Types
        if ( post_type_exists( 'tkina_tarots' ) && post_type_exists( 'tarokkina_pro' ) ) {
            error_log( '✅ TAROKINA TESTS: Custom Post Types registrados correctamente' );
        } else {
            error_log( '⚠️  TAROKINA TESTS: Custom Post Types no registrados aún' );
        }
        
        // Verificar taxonomías
        if ( taxonomy_exists( 'tarokkina_pro-cat' ) && taxonomy_exists( 'tarokkina_pro-tag' ) ) {
            error_log( '✅ TAROKINA TESTS: Taxonomías registradas correctamente' );
        } else {
            error_log( '⚠️  TAROKINA TESTS: Taxonomías no registradas aún' );
        }
        
        // Verificar funciones principales
        if ( function_exists( 'is_name_pro' ) ) {
            error_log( '✅ TAROKINA TESTS: Funciones principales del plugin disponibles' );
        } else {
            error_log( '⚠️  TAROKINA TESTS: Funciones principales no disponibles' );
        }
    }
    
} else {
    error_log( '❌ TAROKINA TESTS: Archivo principal del plugin no encontrado: ' . $tarokina_plugin_file );
}

// =============================================================================
// HOOKS PARA CARGA DEL PLUGIN EN TESTS
// =============================================================================

/**
 * Configurar funciones que se ejecutarán cuando WordPress esté cargado
 */

// Esta variable será usada por el bootstrap para cargar el plugin en el momento correcto
$GLOBALS['dev_tools_plugin_loader'] = 'dev_tools_load_tarokina_plugin';
$GLOBALS['dev_tools_plugin_verifier'] = 'dev_tools_verify_tarokina_plugin';

// =============================================================================
// SISTEMA DE DEBUG CONDICIONAL
// =============================================================================

/**
 * Función para mostrar información de debug SOLO cuando hay fallos en tests
 * Esta función se ejecuta desde el PHP handler cuando se detectan fallos
 */
function dev_tools_show_debug_on_failure() {
    static $debug_shown = false;
    
    // Evitar mostrar múltiples veces en la misma ejecución
    if ($debug_shown) {
        return;
    }
    
    $debug_shown = true;
    
    // Determinar si debemos mostrar debug basándonos en diferentes condiciones
    $should_show_debug = false;
    
    // 1. Modo verbose activado explícitamente
    if (defined('DEV_TOOLS_TESTS_VERBOSE') && DEV_TOOLS_TESTS_VERBOSE) {
        $should_show_debug = true;
    }
    
    // 2. Debug forzado desde variable de entorno o definición
    if (defined('DEV_TOOLS_FORCE_DEBUG') && DEV_TOOLS_FORCE_DEBUG) {
        $should_show_debug = true;
    }
    
    // 3. Tests individuales con WP_DEBUG habilitado
    if (defined('WP_TESTS_INDIVIDUAL') && WP_TESTS_INDIVIDUAL && WP_DEBUG) {
        $should_show_debug = true;
    }
    
    if ($should_show_debug) {
        error_log( '=== DEV TOOLS TESTS CONFIG - Local by Flywheel ===' );
        error_log( 'Base de datos: ' . DB_NAME . '@' . DB_HOST );
        error_log( 'Prefijo tablas: ' . DEV_TOOLS_TABLE_PREFIX );
        error_log( 'Sitio URL: ' . DEV_TOOLS_TEST_SITE_URL );
        if ( defined( 'DEV_TOOLS_PLUGIN_FILE' ) ) {
            error_log( 'Plugin: ' . DEV_TOOLS_PLUGIN_FILE );
            error_log( 'Plugin existe: ' . ( file_exists( DEV_TOOLS_PLUGIN_FILE ) ? 'Sí' : 'No' ) );
        } else {
            error_log( 'Plugin: No detectado' );
        }
        error_log( '====================================================' );
    }
}

// Registrar la función de debug en variable global para que sea accesible desde PHP handlers
$GLOBALS['dev_tools_debug_function'] = 'dev_tools_show_debug_on_failure';

// =============================================================================
// DEBUG INFORMACIÓN INICIAL (Solo para individual tests o modo verbose)
// =============================================================================

// Solo mostrar debug inicial en casos específicos:
// - Tests individuales (WP_TESTS_INDIVIDUAL = true)
// - Modo verbose activado (DEV_TOOLS_TESTS_VERBOSE = true)
// - Debug forzado (DEV_TOOLS_FORCE_DEBUG = true)
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
    error_log( '=== DEV TOOLS TESTS CONFIG - Local by Flywheel ===' );
    error_log( 'Base de datos: ' . DB_NAME . '@' . DB_HOST );
    error_log( 'Prefijo tablas: ' . DEV_TOOLS_TABLE_PREFIX );
    error_log( 'Sitio URL: ' . DEV_TOOLS_TEST_SITE_URL );
    if ( defined( 'DEV_TOOLS_PLUGIN_FILE' ) ) {
        error_log( 'Plugin: ' . DEV_TOOLS_PLUGIN_FILE );
        error_log( 'Plugin existe: ' . ( file_exists( DEV_TOOLS_PLUGIN_FILE ) ? 'Sí' : 'No' ) );
    } else {
        error_log( 'Plugin: No detectado' );
    }
    error_log( '====================================================' );
}
