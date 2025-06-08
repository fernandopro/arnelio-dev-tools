<?php
/**
 * Test directo de las acciones AJAX registradas
 */

// Cargar WordPress mínimo
require_once '../../../wp-load.php';

echo "=== TEST DIRECTO DE ACCIONES AJAX ===\n\n";

// Cargar dev-tools
require_once 'config.php';
require_once 'ajax-handler.php';

$config = dev_tools_config();
$action_prefix = $config->get('ajax.action_prefix');

echo "Prefijo de acción: {$action_prefix}\n";
echo "Plugin slug: " . $config->get('plugin.slug') . "\n";
echo "Menu slug: " . $config->get('dev_tools.menu_slug') . "\n\n";

// Verificar acciones registradas
global $wp_filter;

$ajax_actions = [];
foreach ($wp_filter as $hook => $callbacks) {
    if (strpos($hook, 'wp_ajax_') === 0 && strpos($hook, $action_prefix) !== false) {
        $ajax_actions[] = $hook;
    }
}

echo "=== ACCIONES AJAX REGISTRADAS ===\n";
if (empty($ajax_actions)) {
    echo "❌ NO se encontraron acciones AJAX registradas\n";
} else {
    foreach ($ajax_actions as $action) {
        echo "✅ {$action}\n";
    }
}
echo "\n";

// Verificar endpoints específicos
$expected_actions = [
    $action_prefix . '_dev_tools_ping',
    $action_prefix . '_dev_tools_check_anti_deadlock', 
    $action_prefix . '_dev_tools_check_test_framework',
    $action_prefix . '_dev_tools_action'
];

echo "=== VERIFICACIÓN DE ENDPOINTS ESPECÍFICOS ===\n";
foreach ($expected_actions as $expected) {
    $hook = 'wp_ajax_' . $expected;
    if (isset($wp_filter[$hook])) {
        echo "✅ {$hook} - REGISTRADO\n";
    } else {
        echo "❌ {$hook} - NO ENCONTRADO\n";
    }
}

echo "\n=== INFORMACIÓN DE DEBUGGING ===\n";
echo "Usuario actual: " . (is_user_logged_in() ? wp_get_current_user()->user_login : 'No logueado') . "\n";
echo "Es admin: " . (current_user_can('manage_options') ? 'SÍ' : 'NO') . "\n";
echo "AJAX URL: " . admin_url('admin-ajax.php') . "\n";
echo "DEV_TOOLS_VERBOSE definido: " . (defined('DEV_TOOLS_VERBOSE') ? (DEV_TOOLS_VERBOSE ? 'SÍ' : 'NO') : 'NO DEFINIDO') . "\n";

// Test de nonce
echo "\n=== TEST DE NONCE ===\n";
$nonce_action = $config->get('ajax.nonce_action');
$test_nonce = wp_create_nonce($nonce_action);
echo "Nonce action: {$nonce_action}\n";
echo "Nonce generado: {$test_nonce}\n";
echo "Nonce verificación: " . (wp_verify_nonce($test_nonce, $nonce_action) ? 'VÁLIDO' : 'INVÁLIDO') . "\n";

echo "\n=== TEST COMPLETADO ===\n";
