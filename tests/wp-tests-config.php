<?php
/**
 * wp-tests-config.php - Configuración agnóstica para testing con PHPUnit
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 * 
 * Este archivo configura automáticamente la conexión a la base de datos
 * detectando dinámicamente el entorno de desarrollo (Local by WP Engine, XAMPP, etc.)
 */

// ** Configuración de Base de Datos para Testing ** //

/** Nombre de la base de datos - Usar la misma que el sitio principal */
define( 'DB_NAME', 'local' );

/** Usuario de la base de datos */
define( 'DB_USER', 'root' );

/** Contraseña de la base de datos */
define( 'DB_PASSWORD', 'root' );

/** 
 * Host de la base de datos - Detección dinámica de Local by WP Engine
 * Intentamos detectar automáticamente el socket de MySQL
 */
if (!defined('DB_HOST')) {
    // Intentar detectar automáticamente el socket de Local by WP Engine
    $possible_sockets = [
        // Patrón típico de Local by WP Engine
        '/Users/' . get_current_user() . '/Library/Application Support/Local/run/*/mysql/mysqld.sock',
        // Fallback a localhost estándar
        'localhost'
    ];
    
    $db_host = 'localhost';
    
    // Buscar sockets existentes
    foreach ($possible_sockets as $socket_pattern) {
        if (strpos($socket_pattern, '*') !== false) {
            $sockets = glob($socket_pattern);
            if (!empty($sockets)) {
                $db_host = 'localhost:' . $sockets[0];
                break;
            }
        } else {
            if (file_exists($socket_pattern)) {
                $db_host = 'localhost:' . $socket_pattern;
                break;
            }
        }
    }
    
    define( 'DB_HOST', $db_host );
}

/** Charset de la base de datos */
define( 'DB_CHARSET', 'utf8' );

/** Collate de la base de datos */
define( 'DB_COLLATE', '' );

/**
 * Prefijo de tablas para las pruebas
 * IMPORTANTE: Esto crea un conjunto separado de tablas en la misma BD
 * Las tablas de prueba usarán el prefijo 'wptests_' para aislamiento
 * 
 * NOTA: Esta constante se define automáticamente por wp-phpunit
 * a través de la variable WP_PHPUNIT__TABLE_PREFIX en phpunit.xml.dist
 */
// La constante WP_TESTS_TABLE_PREFIX se define automáticamente por wp-phpunit

/**
 * Variable global requerida por WordPress
 */
$table_prefix = 'wptests_';

/**
 * Dominio para las pruebas
 */
if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
    define( 'WP_TESTS_DOMAIN', 'example.org' );
}

/**
 * Email del administrador para las pruebas
 */
if ( ! defined( 'WP_TESTS_EMAIL' ) ) {
    define( 'WP_TESTS_EMAIL', 'admin@example.org' );
}

/**
 * Título del sitio para las pruebas
 */
if ( ! defined( 'WP_TESTS_TITLE' ) ) {
    define( 'WP_TESTS_TITLE', 'Test Blog' );
}

/**
 * Configuración de WordPress para testing
 */
if ( ! defined( 'WP_PHP_BINARY' ) ) {
    define( 'WP_PHP_BINARY', 'php' );
}

/**
 * Configuración de debug para testing
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

/**
 * Deshabilitar archivos .htaccess para testing
 */
define( 'WP_USE_THEMES', false );

/**
 * Configuración de salts y keys - Usar las mismas del sitio principal para consistencia
 * O generar nuevas específicas para testing
 */
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

/**
 * Configurar la ruta absoluta a WordPress
 * Detección dinámica de la instalación de WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
    // Detectar dinámicamente la ruta de WordPress
    $wp_root = __DIR__;
    $max_depth = 10;
    $current_depth = 0;
    
    while ($current_depth < $max_depth) {
        if (file_exists($wp_root . '/wp-config.php') || file_exists($wp_root . '/wp-settings.php')) {
            break;
        }
        $parent = dirname($wp_root);
        if ($parent === $wp_root) {
            // Llegamos al directorio raíz sin encontrar WordPress
            throw new Exception('No se pudo encontrar la instalación de WordPress');
        }
        $wp_root = $parent;
        $current_depth++;
    }
    
    define( 'ABSPATH', $wp_root . '/' );
}
