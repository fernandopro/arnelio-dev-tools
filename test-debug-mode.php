<?php
/**
 * Test de constantes de debug
 */

// Cargar WordPress si no está cargado
if (!defined('ABSPATH')) {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';
}

echo "<h1>Debug de constantes de modo</h1>";

echo "<h2>Constantes de WordPress:</h2>";
echo "<pre>";
echo "WP_DEBUG: " . (defined('WP_DEBUG') ? (WP_DEBUG ? 'true' : 'false') : 'NO DEFINIDO') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') ? (WP_DEBUG_LOG ? 'true' : 'false') : 'NO DEFINIDO') . "\n";
echo "WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') ? (WP_DEBUG_DISPLAY ? 'true' : 'false') : 'NO DEFINIDO') . "\n";
echo "</pre>";

echo "<h2>Constantes de Tarokina:</h2>";
echo "<pre>";
echo "TAROKINA_PRODUCTION_MODE: " . (defined('TAROKINA_PRODUCTION_MODE') ? (TAROKINA_PRODUCTION_MODE ? 'true' : 'false') : 'NO DEFINIDO') . "\n";
echo "TAROKINA_DEV_MODE: " . (defined('TAROKINA_DEV_MODE') ? (TAROKINA_DEV_MODE ? 'true' : 'false') : 'NO DEFINIDO') . "\n";
echo "</pre>";

// Cargar la configuración de dev-tools
require_once __DIR__ . '/config.php';

try {
    $config = dev_tools_config();
    
    echo "<h2>Métodos de Dev Tools:</h2>";
    echo "<pre>";
    echo "config->is_debug_mode(): " . ($config->is_debug_mode() ? 'true' : 'false') . "\n";
    echo "config->is_verbose_mode(): " . ($config->is_verbose_mode() ? 'true' : 'false') . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
