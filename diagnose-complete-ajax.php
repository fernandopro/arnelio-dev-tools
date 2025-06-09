<?php
/**
 * Script de diagnóstico completo para el sistema AJAX
 * Verifica el estado de registro de hooks y configuración
 */

// Incluir WordPress
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

// Asegurar que somos admin
if (!current_user_can('manage_options')) {
    wp_die('Acceso denegado');
}

echo "🔧 DIAGNÓSTICO COMPLETO DEL SISTEMA AJAX\n";
echo "========================================\n\n";

// 1. Verificar configuración
$config = dev_tools_config();
echo "📋 CONFIGURACIÓN:\n";
echo "- Ajax Action Prefix: " . $config->get('ajax.action_prefix') . "\n";
echo "- Ajax Action Name: " . $config->get('ajax.action_name') . "\n";
echo "- Dev Tools Ajax Action: " . $config->get('dev_tools.ajax_action') . "\n";
echo "- Nonce Action: " . $config->get('ajax.nonce_action') . "\n";
echo "\n";

// 2. Verificar configuración JS
$js_config = $config->get_js_config();
echo "📱 CONFIGURACIÓN JAVASCRIPT:\n";
echo "- ajaxAction: " . $js_config['ajaxAction'] . "\n";
echo "- actionPrefix: " . $js_config['actionPrefix'] . "\n";
echo "- nonce: " . substr($js_config['nonce'], 0, 10) . "...\n";
echo "\n";

// 3. Verificar hooks registrados
$expected_hook = 'wp_ajax_' . $js_config['ajaxAction'];
$expected_hook_nopriv = 'wp_ajax_nopriv_' . $js_config['ajaxAction'];

echo "🔌 HOOKS DE WORDPRESS:\n";
echo "- Hook esperado: {$expected_hook}\n";
echo "- Hook registrado: " . (has_action($expected_hook) ? 'SÍ' : 'NO') . "\n";
echo "- Hook nopriv esperado: {$expected_hook_nopriv}\n";
echo "- Hook nopriv registrado: " . (has_action($expected_hook_nopriv) ? 'SÍ' : 'NO') . "\n";
echo "\n";

// 4. Verificar instancia AJAX Handler
echo "🛠️ AJAX HANDLER:\n";
try {
    $ajax_handler = DevToolsAjaxHandler::getInstance();
    echo "- Instancia creada: SÍ\n";
    
    // Verificar si los hooks se registraron después de crear la instancia
    echo "- Hook después de instancia: " . (has_action($expected_hook) ? 'SÍ' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "- Error creando instancia: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Verificar todos los hooks AJAX registrados
echo "📝 TODOS LOS HOOKS AJAX REGISTRADOS:\n";
global $wp_filter;
$ajax_hooks = [];
foreach ($wp_filter as $hook_name => $hook_obj) {
    if (strpos($hook_name, 'wp_ajax_') === 0) {
        $ajax_hooks[] = $hook_name;
    }
}

if (empty($ajax_hooks)) {
    echo "- ¡NO HAY HOOKS AJAX REGISTRADOS!\n";
} else {
    foreach ($ajax_hooks as $hook) {
        echo "- {$hook}\n";
    }
}
echo "\n";

// 6. Probar generación de nonce
echo "🔐 PRUEBA DE NONCE:\n";
$nonce_action = $config->get('ajax.nonce_action');
$test_nonce = wp_create_nonce($nonce_action);
echo "- Nonce Action: {$nonce_action}\n";
echo "- Nonce generado: {$test_nonce}\n";
echo "- Nonce válido: " . (wp_verify_nonce($test_nonce, $nonce_action) ? 'SÍ' : 'NO') . "\n";
echo "\n";

// 7. Estado del sistema
echo "🎯 ESTADO DEL SISTEMA:\n";
echo "- WordPress cargado: " . (defined('ABSPATH') ? 'SÍ' : 'NO') . "\n";
echo "- Usuario admin: " . (current_user_can('manage_options') ? 'SÍ' : 'NO') . "\n";
echo "- Dev-tools config: " . (function_exists('dev_tools_config') ? 'SÍ' : 'NO') . "\n";
echo "- AjaxHandler class: " . (class_exists('DevToolsAjaxHandler') ? 'SÍ' : 'NO') . "\n";
echo "\n";

// 8. Recomendar acción
echo "🚀 RECOMENDACIÓN:\n";
if (!has_action($expected_hook)) {
    echo "- PROBLEMA: Hook {$expected_hook} NO está registrado\n";
    echo "- SOLUCIÓN: Verificar que DevToolsAjaxHandler se esté inicializando correctamente\n";
    echo "- VERIFICAR: El momento en que se registran los hooks (init, admin_init, etc.)\n";
} else {
    echo "- ✅ Sistema correctamente configurado\n";
}

?>
