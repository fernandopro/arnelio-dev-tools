<?php
/**
 * Dashboard Tab - Panel principal de dev-tools
 * PLUGIN-AGNÓSTICO: Detecta automáticamente el plugin host
 */

// Obtener configuración dinámica del sistema
$config = dev_tools_config();

// Obtener información del sistema
$php_version = PHP_VERSION;
$wp_version = get_bloginfo('version');

// CORRECCIÓN: Usar configuración dinámica en lugar de hardcoded
$plugin_file = $config->get('host.file');
$plugin_data = get_plugin_data($plugin_file);
$plugin_version = $plugin_data['Version'] ?? 'N/A';
$plugin_name = $config->get('host.name');

// Estadísticas del sistema
$stats = [
    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
    'memory_limit' => ini_get('memory_limit'),
];
?>