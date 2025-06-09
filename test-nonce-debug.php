<?php
/**
 * Script para debuggear configuración del nonce en PHP
 * Ejecutar desde CLI o incluir en WordPress para ver la configuración actual
 */

// Asegurar que estamos en contexto de WordPress
if (!defined('ABSPATH')) {
    require_once '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/wp-load.php';
}

echo "🔍 DEBUGGING NONCE CONFIGURATION (PHP)\n";
echo "=====================================\n\n";

// 1. Verificar si dev-tools está cargado
if (!function_exists('dev_tools_config')) {
    echo "❌ dev-tools no está cargado\n";
    exit;
}

// 2. Obtener configuración
$config = dev_tools_config();

echo "1. CONFIGURACIÓN DETECTADA:\n";
echo "   - Host plugin file: " . $config->get('host.file') . "\n";
echo "   - Host plugin name: " . $config->get('host.name') . "\n";
echo "   - Host plugin slug: " . $config->get('host.slug') . "\n";
echo "   - Host plugin dir: " . $config->get('host.dir_path') . "\n\n";

echo "2. CONFIGURACIÓN AJAX:\n";
echo "   - action_prefix: " . $config->get('ajax.action_prefix') . "\n";
echo "   - nonce_action: " . $config->get('ajax.nonce_action') . "\n";
echo "   - Ajax action: " . $config->get('dev_tools.ajax_action') . "\n";
echo "   - Nonce key: " . $config->get('dev_tools.nonce_key') . "\n\n";

echo "3. NONCES GENERADOS:\n";
$nonce_action = $config->get('ajax.nonce_action');
$nonce_value = wp_create_nonce($nonce_action);
echo "   - Nonce action: '{$nonce_action}'\n";
echo "   - Nonce value: '{$nonce_value}'\n\n";

echo "4. VERIFICACIÓN DE NONCE:\n";
// Simular verificación como lo haría AJAX handler
$test_nonce = wp_create_nonce($nonce_action);
$is_valid = wp_verify_nonce($test_nonce, $nonce_action);
echo "   - Test nonce: '{$test_nonce}'\n";
echo "   - Verificación: " . ($is_valid ? "✅ VÁLIDO" : "❌ INVÁLIDO") . "\n\n";

echo "5. CONFIGURACIÓN JAVASCRIPT (como se envía al cliente):\n";
$js_config = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce($config->get('ajax.nonce_action')),
    'actionPrefix' => $config->get('ajax.action_prefix'),
    'debug' => defined('WP_DEBUG') && WP_DEBUG,
    'version' => '3.0.0'
];

foreach ($js_config as $key => $value) {
    echo "   - {$key}: '{$value}'\n";
}

echo "\n6. HOOKS AJAX REGISTRADOS:\n";
$ajax_prefix = $config->get('ajax.action_prefix');
echo "   - wp_ajax_{$ajax_prefix}_dev_tools\n";
echo "   - wp_ajax_nopriv_{$ajax_prefix}_dev_tools\n\n";

echo "7. COMPARACIÓN CON LEGACY:\n";
echo "   - Legacy nonce key: " . $config->get('dev_tools.nonce_key') . "\n";
echo "   - Nuevo nonce action: " . $config->get('ajax.nonce_action') . "\n";
echo "   - ¿Son iguales?: " . ($config->get('dev_tools.nonce_key') === $config->get('ajax.nonce_action') ? "✅ SÍ" : "❌ NO") . "\n\n";

echo "✅ Debug completado\n";
?>
