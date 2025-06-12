<?php
/**
 * wp-tests-config.php - Configuración para testing con PHPUnit en Local by WP Engine
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 * 
 * Este archivo configura la conexión a la base de datos MySQL de Local by WP Engine
 * usando el socket específico del sitio tarokina-2025
 */

// ** Configuración de Base de Datos para Testing ** //

/** Nombre de la base de datos - Usar la misma que el sitio principal */
define( 'DB_NAME', 'local' );

/** Usuario de la base de datos */
define( 'DB_USER', 'root' );

/** Contraseña de la base de datos */
define( 'DB_PASSWORD', 'root' );

/** 
 * Host de la base de datos - CRÍTICO: Usar el socket específico de Local
 * Ruta encontrada: /Users/fernandovazquezperez/Library/Application Support/Local/run/6ld71Gw6d/mysql/mysqld.sock
 */
define( 'DB_HOST', 'localhost:/Users/fernandovazquezperez/Library/Application Support/Local/run/6ld71Gw6d/mysql/mysqld.sock' );

/** Charset de la base de datos */
define( 'DB_CHARSET', 'utf8' );

/** Collate de la base de datos */
define( 'DB_COLLATE', '' );

/**
 * Prefijo de tablas para las pruebas
 * IMPORTANTE: Esto crea un conjunto separado de tablas en la misma BD
 * Las tablas de prueba usarán el prefijo 'wptests_' para aislamiento
 */
define( 'WP_TESTS_TABLE_PREFIX', 'wptests_' );

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
define( 'WP_PHP_BINARY', 'php' );

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
 * Esto se ajustará automáticamente por el bootstrap de testing
 */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/' );
}
