<?php
/**
 * Configuración de Testing Específica para Tarokina Pro
 * 
 * Este archivo contiene configuraciones que eran específicas de Tarokina
 * en wp-tests-config.php y ahora se mantienen localmente.
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// CONFIGURACIÓN ESPECÍFICA DE TAROKINA PRO
// =============================================================================

// Base de datos Local by Flywheel para Tarokina
define('DB_NAME', 'local');
define('DB_USER', 'root'); 
define('DB_PASSWORD', 'root');
define('DB_HOST', '127.0.0.1');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Prefijo específico para tests de Tarokina
$table_prefix = 'wp_test_tarokina_';

// URLs específicas de Tarokina (Local by Flywheel)
define('WP_HOME', 'https://tarokina-2025.local');
define('WP_SITEURL', 'https://tarokina-2025.local');

// Plugin de Tarokina para testing
$plugin_dir = dirname(__DIR__);
$tarokina_plugin_file = $plugin_dir . '/tarokina-pro.php';

if (file_exists($tarokina_plugin_file)) {
    define('DEV_TOOLS_PLUGIN_FILE', $tarokina_plugin_file);
    define('DEV_TOOLS_TESTING', true);
    define('TAROKINA_TESTING_MODE', true);
}

// Configuración de debug específica de Tarokina
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Keys específicas para Tarokina testing
define('AUTH_KEY', 'test-auth-key-tarokina');
define('SECURE_AUTH_KEY', 'test-secure-auth-key-tarokina');
define('LOGGED_IN_KEY', 'test-logged-in-key-tarokina');
define('NONCE_KEY', 'test-nonce-key-tarokina');
define('AUTH_SALT', 'test-auth-salt-tarokina');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt-tarokina');
define('LOGGED_IN_SALT', 'test-logged-in-salt-tarokina');
define('NONCE_SALT', 'test-nonce-salt-tarokina');

// Función para cargar Tarokina Pro durante tests
function dev_tools_load_tarokina_plugin() {
    $plugin_file = DEV_TOOLS_PLUGIN_FILE;
    
    if (file_exists($plugin_file)) {
        include_once $plugin_file;
        
        // Verificar constantes específicas de Tarokina
        if (defined('TKINA_TAROKINA_PRO_DIR_PATH')) {
            error_log('✅ TAROKINA TESTS: Plugin Tarokina Pro cargado exitosamente');
            error_log('📁 Plugin Path: ' . TKINA_TAROKINA_PRO_DIR_PATH);
            error_log('🌐 Plugin URL: ' . (defined('TKINA_TAROKINA_PRO_DIR_URL') ? TKINA_TAROKINA_PRO_DIR_URL : 'N/A'));
        }
    }
}

// Función para verificar elementos específicos de Tarokina
function dev_tools_verify_tarokina_plugin() {
    // Verificar Custom Post Types de Tarokina
    if (post_type_exists('tkina_tarots') && post_type_exists('tarokkina_pro')) {
        error_log('✅ TAROKINA TESTS: Custom Post Types registrados correctamente');
    } else {
        error_log('⚠️  TAROKINA TESTS: Custom Post Types no registrados aún');
    }
    
    // Verificar taxonomías de Tarokina
    if (taxonomy_exists('tarokkina_pro-cat') && taxonomy_exists('tarokkina_pro-tag')) {
        error_log('✅ TAROKINA TESTS: Taxonomías registradas correctamente');
    } else {
        error_log('⚠️  TAROKINA TESTS: Taxonomías no registradas aún');
    }
    
    // Verificar funciones principales de Tarokina
    if (function_exists('is_name_pro')) {
        error_log('✅ TAROKINA TESTS: Funciones principales del plugin disponibles');
    } else {
        error_log('⚠️  TAROKINA TESTS: Funciones principales no disponibles');
    }
}

// Registrar funciones de carga
$GLOBALS['dev_tools_plugin_loader'] = 'dev_tools_load_tarokina_plugin';
$GLOBALS['dev_tools_plugin_verifier'] = 'dev_tools_verify_tarokina_plugin';

// Debug condicional (solo cuando hay fallos)
function dev_tools_show_debug_on_failure() {
    static $debug_shown = false;
    if ($debug_shown) return;
    $debug_shown = true;
    
    error_log('=== DEBUG TAROKINA TESTS ===');
    error_log('Base de datos: ' . DB_NAME . '@' . DB_HOST);
    error_log('Prefijo tablas: ' . $table_prefix);
    error_log('Sitio URL: ' . WP_HOME);
    error_log('Plugin: ' . (defined('DEV_TOOLS_PLUGIN_FILE') ? DEV_TOOLS_PLUGIN_FILE : 'No detectado'));
    error_log('============================');
}

$GLOBALS['dev_tools_debug_function'] = 'dev_tools_show_debug_on_failure';
