<?php
/**
 * Template de Configuración Local para Plugin Específico
 * 
 * Este archivo debe ser copiado como:
 * - wp-tests-config-local.php (para testing)
 * - config-local.php (para configuraciones específicas)
 * 
 * Los archivos *-local.php están excluidos del git del submódulo
 * para evitar contaminación entre plugins.
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit('Este archivo solo puede ser accedido desde WordPress');
}

/**
 * Configuración específica del plugin que usa dev-tools
 * Esta configuración se mantiene LOCAL y NO se comparte entre plugins
 */

// =============================================================================
// CONFIGURACIÓN BASE DEL PLUGIN
// =============================================================================

// Detectar automáticamente el plugin host desde el directorio padre
$dev_tools_dir = __DIR__;
$plugin_dir = dirname($dev_tools_dir);
$plugin_main_file = null;

// Buscar archivo principal del plugin
foreach (glob($plugin_dir . '/*.php') as $file) {
    $content = file_get_contents($file, false, null, 0, 2000);
    if (strpos($content, 'Plugin Name:') !== false) {
        $plugin_main_file = $file;
        break;
    }
}

if (!$plugin_main_file) {
    throw new Exception('❌ No se pudo detectar el archivo principal del plugin');
}

// =============================================================================
// CONFIGURACIÓN AUTOMÁTICA BASADA EN EL PLUGIN DETECTADO
// =============================================================================

$plugin_data = get_file_data($plugin_main_file, [
    'Name' => 'Plugin Name',
    'Version' => 'Version',
    'TextDomain' => 'Text Domain'
]);

$plugin_slug = basename($plugin_dir);
$plugin_name = $plugin_data['Name'] ?: $plugin_slug;
$plugin_version = $plugin_data['Version'] ?: '1.0.0';
$plugin_text_domain = $plugin_data['TextDomain'] ?: $plugin_slug;

// =============================================================================
// CONFIGURACIÓN LOCAL DEL PLUGIN (PERSONALIZAR SEGÚN NECESIDADES)
// =============================================================================

return [
    // Información del plugin
    'plugin' => [
        'slug' => $plugin_slug,
        'name' => $plugin_name,
        'version' => $plugin_version,
        'text_domain' => $plugin_text_domain,
        'main_file' => $plugin_main_file,
        'dir_path' => $plugin_dir,
        'dir_url' => plugins_url('/', $plugin_main_file)
    ],
    
    // Configuración de testing local
    'testing' => [
        'enabled' => true,
        'test_prefix' => strtoupper($plugin_slug) . '_TEST',
        'fixtures_dir' => $plugin_dir . '/tests/fixtures',
        'custom_tests_dir' => $plugin_dir . '/tests/plugin-specific',
        'reports_dir' => $plugin_dir . '/dev-tools/reports/plugin-specific',
        'logs_dir' => $plugin_dir . '/dev-tools/logs/plugin-specific'
    ],
    
    // Base de datos para testing (Local by Flywheel)
    'database' => [
        'name' => 'local',
        'user' => 'root',
        'password' => 'root',
        'host' => '127.0.0.1',
        'charset' => 'utf8',
        'collate' => '',
        'table_prefix' => 'wp_test_' . $plugin_slug . '_'
    ],
    
    // URLs de testing (Local by Flywheel)
    'urls' => [
        'home' => 'https://tarokina-2025.local',
        'site' => 'https://tarokina-2025.local'
    ],
    
    // Configuraciones específicas del plugin
    'plugin_specific' => [
        // Añadir aquí configuraciones específicas para cada plugin
        'custom_post_types' => [], // ej: ['tkina_tarots', 'tarokkina_pro']
        'custom_taxonomies' => [], // ej: ['tarokkina_pro-cat', 'tarokkina_pro-tag']
        'required_functions' => [], // ej: ['is_name_pro', 'activate_tarokina_master']
        'required_constants' => []  // ej: ['TKINA_TAROKINA_PRO_VERSION']
    ]
];
