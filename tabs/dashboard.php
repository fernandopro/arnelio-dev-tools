<?php
/**
 * Dashboard Tab - Panel principal de dev-tools
 */

// Obtener información del sistema
$php_version = PHP_VERSION;
$wp_version = get_bloginfo('version');
$plugin_data = get_plugin_data(dirname(dirname(__DIR__)) . '/tarokina-pro.php');
$plugin_version = $plugin_data['Version'] ?? 'N/A';

// Estadísticas del sistema
$stats = [
    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
    'memory_limit' => ini_get('memory_limit'),
];
?>