<?php
/**
 * Simple AJAX debug test - accesible desde wp-admin
 */

// Solo ejecutar en AJAX
if (!defined('DOING_AJAX') || !DOING_AJAX) {
    // Cargar WordPress si no está cargado
    if (!defined('ABSPATH')) {
        require_once __DIR__ . '/../../../../wp-load.php';
    }
}

// Cargar configuración
require_once __DIR__ . '/config.php';

try {
    $config = dev_tools_config();
    
    // Test básico de configuración
    $js_config = $config->get_js_config();
    
    // Respuesta simple
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'config_type' => gettype($js_config),
        'has_nonce' => isset($js_config['nonce']),
        'nonce_value' => $js_config['nonce'] ?? 'MISSING',
        'ajax_url' => $js_config['ajaxUrl'] ?? 'MISSING',
        'js_config_var' => $config->get('dev_tools.js_config_var'),
        'ajax_prefix' => $config->get('ajax.action_prefix'),
        'full_config' => $js_config
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
exit;
?>
