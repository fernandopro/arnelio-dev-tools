<?php
/**
 * Test para verificar si WordPress está disponible al cargar dev-tools
 */

// Simular carga desde tarokina-pro.php
echo "=== TEST: Disponibilidad de WordPress en dev-tools ===\n";

// Verificar si las funciones principales están disponibles
$wp_functions = [
    'is_admin',
    'admin_url',
    'wp_get_current_user',
    'add_action',
    'wp_enqueue_script',
    'wp_enqueue_style',
    'wp_localize_script',
    'get_site_url',
    'plugins_url'
];

echo "Verificando funciones de WordPress:\n";
foreach ($wp_functions as $function) {
    $available = function_exists($function) ? '✅' : '❌';
    echo "- {$function}: {$available}\n";
}

echo "\nVerificando constantes de WordPress:\n";
$wp_constants = ['ABSPATH', 'WP_CONTENT_DIR', 'WP_PLUGIN_DIR'];
foreach ($wp_constants as $constant) {
    $defined = defined($constant) ? '✅' : '❌';
    echo "- {$constant}: {$defined}\n";
}

echo "\nVerificando contexto:\n";
echo "- is_admin(): " . (function_exists('is_admin') ? (is_admin() ? '✅ SÍ' : '❌ NO') : '❌ FUNCIÓN NO DISPONIBLE') . "\n";
echo "- get_site_url(): " . (function_exists('get_site_url') ? get_site_url() : 'NO DISPONIBLE') . "\n";
