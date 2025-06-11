<?php
/**
 * WordPress Test Configuration File - Simplified
 * 
 * Configuración simplificada para WordPress PHPUnit Test Suite
 * Compatible con Local by WP Engine
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS PARA TESTS
// =============================================================================

// Configuración específica para Local by WP Engine
// Estos valores son estándar para Local by WP Engine
define('DB_NAME', 'local');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// =============================================================================
// PREFIJO DE TABLAS DE TEST
// =============================================================================

// Usar prefijo diferente para evitar conflictos con tablas principales
$table_prefix = 'test_';

// =============================================================================
// CONFIGURACIÓN DE TESTING
// =============================================================================

// Configuración específica para tests
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

// Configuración de seguridad para tests
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

// =============================================================================
// CONFIGURACIÓN WORDPRESS
// =============================================================================

// Configuración multisite (deshabilitado para tests básicos)
define('WP_TESTS_MULTISITE', false);

// Configuración de debug para tests
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// =============================================================================
// PATHS Y CONFIGURACIÓN FINAL
// =============================================================================

// Path absoluto a WordPress para tests
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

// =============================================================================
// LOGGING DE CONFIGURACIÓN PARA DEBUG
// =============================================================================

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("🧪 WordPress Tests Config - Simplified version loaded");
    error_log("🔧 Database: " . DB_NAME . " with prefix: " . $table_prefix);
    error_log("🔧 WordPress path: " . ABSPATH);
}
