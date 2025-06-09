<?php
/**
 * Debug script para verificar la URL del CSS
 * Ejecutar en el admin de WordPress para debuggear el problema del CSS 404
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

// Cargar configuración de dev-tools
require_once(__DIR__ . '/config.php');

// Obtener configuración
$config = dev_tools_config();

echo "🔧 DEV-TOOLS CSS URL DEBUG\n";
echo "===========================\n\n";

echo "📁 Rutas del sistema:\n";
echo "- __FILE__: " . __FILE__ . "\n";
echo "- __DIR__: " . __DIR__ . "\n";
echo "- ABSPATH: " . (defined('ABSPATH') ? ABSPATH : 'No definido') . "\n";
echo "- WP_CONTENT_DIR: " . (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : 'No definido') . "\n\n";

echo "🌐 URLs generadas por plugins_url():\n";
echo "- plugins_url('/', __FILE__): " . plugins_url('/', __FILE__) . "\n";
echo "- plugins_url('dev-tools/', dirname(__FILE__)): " . plugins_url('dev-tools/', dirname(__FILE__)) . "\n";
echo "- plugins_url() base: " . plugins_url() . "\n\n";

echo "⚙️ Configuración Dev-Tools:\n";
echo "- dev_tools_url: " . $config->get('paths.dev_tools_url') . "\n";
echo "- CSS completa: " . $config->get('paths.dev_tools_url') . 'dist/css/dev-tools-styles.min.css' . "\n";
echo "- CSS handle: " . $config->get('assets.css_handle') . "\n\n";

echo "📋 URLs esperadas vs reales:\n";
$expected_css_url = $config->get('paths.dev_tools_url') . 'dist/css/dev-tools-styles.min.css';
echo "- URL esperada (dinámica): " . $expected_css_url . "\n";
echo "- URL generada: " . $config->get('paths.dev_tools_url') . 'dist/css/dev-tools-styles.min.css' . "\n\n";

echo "📂 Verificación de archivos:\n";
$css_file_path = __DIR__ . '/dist/css/dev-tools-styles.min.css';
echo "- Archivo CSS existe: " . (file_exists($css_file_path) ? '✅ SÍ' : '❌ NO') . "\n";
echo "- Ruta del archivo: " . $css_file_path . "\n";
if (file_exists($css_file_path)) {
    echo "- Tamaño del archivo: " . number_format(filesize($css_file_path)) . " bytes\n";
    echo "- Última modificación: " . date('Y-m-d H:i:s', filemtime($css_file_path)) . "\n";
}

echo "\n🔍 Análisis del problema:\n";
$expected_url = $expected_css_url; // CORRECCIÓN: Usar URL dinámica
$actual_url = $config->get('paths.dev_tools_url') . 'dist/css/dev-tools-styles.min.css';

if ($expected_url === $actual_url) {
    echo "✅ Las URLs coinciden - El problema no es la generación de URL\n";
} else {
    echo "❌ Las URLs NO coinciden:\n";
    echo "   Esperada: $expected_url\n";
    echo "   Actual: $actual_url\n";
}

echo "\n🎯 Recomendaciones:\n";
echo "1. Verificar que wp_enqueue_style() se ejecute en admin_enqueue_scripts\n";
echo "2. Comprobar que el hook de la página sea correcto\n";
echo "3. Verificar permisos del archivo CSS\n";
echo "4. Revisar logs de WordPress para errores 404\n";
