<?php
/**
 * Template de Override para wp-tests-config.php
 * 
 * Copiar este archivo como:
 * plugin-dev-tools/wp-tests-config.php
 * 
 * Este archivo permite personalizar la configuración de testing específica para cada plugin
 * manteniendo el core compartido intacto.
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    // Buscar WordPress desde la ubicación del plugin
    $wp_search_paths = [
        __DIR__ . '/../../../..',
        __DIR__ . '/../../..',
        dirname(dirname(dirname(__DIR__)))
    ];
    
    foreach ($wp_search_paths as $wp_path) {
        if (file_exists($wp_path . '/wp-config.php')) {
            define('ABSPATH', $wp_path . '/');
            break;
        }
    }
}

// =============================================================================
// CONFIGURACIÓN ESPECÍFICA DEL PLUGIN
// =============================================================================

// Detectar automáticamente el plugin host
$plugin_dir = dirname(__DIR__);
$plugin_files = glob($plugin_dir . '/*.php');
$plugin_main_file = null;

foreach ($plugin_files as $file) {
    $content = file_get_contents($file, false, null, 0, 2000);
    if (strpos($content, 'Plugin Name:') !== false) {
        $plugin_main_file = $file;
        break;
    }
}

if ($plugin_main_file) {
    $plugin_data = get_file_data($plugin_main_file, [
        'Name' => 'Plugin Name',
        'Version' => 'Version',
        'TextDomain' => 'Text Domain'
    ]);
    
    $plugin_slug = basename($plugin_dir);
    $plugin_name = $plugin_data['Name'] ?: $plugin_slug;
    
    // Plugin específico para testing
    define('DEV_TOOLS_PLUGIN_FILE', $plugin_main_file);
    define('DEV_TOOLS_TESTING', true);
    
    // Personalizar según el plugin detectado
    // EJEMPLO: Para Tarokina
    if (strpos(strtolower($plugin_name), 'tarokina') !== false) {
        define('TAROKINA_TESTING_MODE', true);
        
        // Base de datos específica para Tarokina (Local by Flywheel)
        define('DB_NAME', 'local');
        define('DB_USER', 'root');
        define('DB_PASSWORD', 'root');
        
        // URLs específicas
        define('WP_HOME', 'https://tarokina-2025.local');
        define('WP_SITEURL', 'https://tarokina-2025.local');
        
        // Prefijo específico para tests de Tarokina
        $table_prefix = 'wp_test_tarokina_';
    }
}

// =============================================================================
// CONFIGURACIÓN DE DEBUG ESPECÍFICA
// =============================================================================

// Activar debug específico del plugin
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Keys específicas para testing del plugin
define('AUTH_KEY', 'test-auth-key-' . ($plugin_slug ?? 'plugin'));
define('SECURE_AUTH_KEY', 'test-secure-auth-key-' . ($plugin_slug ?? 'plugin'));
define('LOGGED_IN_KEY', 'test-logged-in-key-' . ($plugin_slug ?? 'plugin'));
define('NONCE_KEY', 'test-nonce-key-' . ($plugin_slug ?? 'plugin'));
define('AUTH_SALT', 'test-auth-salt-' . ($plugin_slug ?? 'plugin'));
define('SECURE_AUTH_SALT', 'test-secure-auth-salt-' . ($plugin_slug ?? 'plugin'));
define('LOGGED_IN_SALT', 'test-logged-in-salt-' . ($plugin_slug ?? 'plugin'));
define('NONCE_SALT', 'test-nonce-salt-' . ($plugin_slug ?? 'plugin'));

// =============================================================================
// CONFIGURACIÓN DE TESTS ESPECÍFICOS
// =============================================================================

// Directorio de tests específicos del plugin
define('DEV_TOOLS_PLUGIN_TESTS_DIR', __DIR__ . '/tests');

// Email y título específicos
define('WP_TESTS_EMAIL', 'admin@' . ($plugin_slug ?? 'plugin') . '.test');
define('WP_TESTS_TITLE', ($plugin_name ?? 'Plugin') . ' Testing Site');

// =============================================================================
// CONFIGURACIÓN PERSONALIZADA
// =============================================================================

// Aquí puedes añadir configuraciones específicas adicionales para tu plugin:

// Ejemplo: Configurar custom post types para testing
/*
add_action('init', function() {
    register_post_type('test_cpt', [
        'public' => true,
        'supports' => ['title', 'editor']
    ]);
});
*/

// Ejemplo: Configurar opciones específicas del plugin para testing
/*
add_action('init', function() {
    update_option('plugin_test_option', 'test_value');
});
*/

// IMPORTANTE: Si quieres que este override sea COMPLETO y no cargar nada más
// del core wp-tests-config.php, descomenta la siguiente línea:
// define('DEV_TOOLS_OVERRIDE_COMPLETE', true);
