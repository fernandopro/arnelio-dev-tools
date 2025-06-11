<?php
/**
 * Dev-Tools Arquitectura 3.0 - Configuración Principal
 * 
 * Configuración agnóstica del sistema que se adapta automáticamente
 * a cualquier entorno y plugin host
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

// Seguridad - No acceso directo
if (!defined('ABSPATH') && !defined('DEV_TOOLS_DIRECT_ACCESS')) {
    exit('Direct access not allowed');
}

return [
    // Información básica del sistema
    'name' => 'Dev-Tools Arquitectura 3.0',
    'version' => '3.0.0',
    'description' => 'Framework agnóstico de herramientas de desarrollo para WordPress',
    
    // Configuración del menú de administración
    'menu' => [
        'slug' => 'dev-tools',
        'capability' => 'manage_options',
        'icon' => 'dashicons-admin-tools',
        'position' => 80
    ],
    
    // Módulos habilitados (auto-discovery)
    'modules_enabled' => [
        'DashboardModule',
        'SystemInfoModule', 
        'DatabaseConnectionModule',
        'SiteUrlDetectionModule',
        'CacheModule',
        'AjaxTesterModule',
        'LogsModule',
        'PerformanceModule',
        'TestSuiteModule'
    ],
    
    // Configuración de assets
    'assets' => [
        'bootstrap_version' => '5.3.0',
        'jquery_required' => true,
        'load_fontawesome' => true
    ],
    
    // Configuración de testing
    'testing' => [
        'phpunit_enabled' => true,
        'test_database' => true,
        'test_environment' => [
            'local_wp_engine' => true,
            'staging' => true,
            'production' => false // Por seguridad
        ]
    ],
    
    // Configuración de logging
    'logging' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
        'file_rotation' => true,
        'max_files' => 5
    ],
    
    // Configuración de cache
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hora
        'prefix' => 'dev_tools_'
    ],
    
    // Configuración de seguridad
    'security' => [
        'nonce_lifetime' => 12 * HOUR_IN_SECONDS,
        'rate_limiting' => true,
        'allowed_capabilities' => ['manage_options', 'administrator']
    ],
    
    // URLs y rutas dinámicas (se calculan automáticamente)
    'paths' => [
        // Estas se calculan dinámicamente por DevToolsPaths
        'base_path' => null,
        'base_url' => null,
        'plugin_path' => null,
        'plugin_url' => null
    ],
    
    // Configuración de entorno
    'environment' => [
        'auto_detect' => true,
        'supported_environments' => [
            'local_wp_engine',
            'docker',
            'xampp',
            'staging', 
            'production'
        ]
    ],
    
    // Configuración específica para Local by WP Engine
    'local_wp_engine' => [
        'socket_detection' => true,
        'router_mode_support' => true,
        'auto_configure_mysql' => true
    ],
    
    // Configuración de AJAX
    'ajax' => [
        'timeout' => 30, // segundos
        'retry_attempts' => 3,
        'debug_mode' => true
    ],
    
    // Configuración de UI
    'ui' => [
        'theme' => 'bootstrap',
        'dark_mode' => false,
        'animations' => true,
        'responsive' => true
    ],
    
    // Información del desarrollador
    'developer' => [
        'name' => 'Dev-Tools Arquitectura 3.0',
        'email' => 'dev@devtools.local',
        'website' => 'https://github.com/dev-tools',
        'support' => 'https://github.com/dev-tools/issues'
    ]
];
