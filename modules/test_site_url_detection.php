<?php
/**
 * Test Script para SiteUrlDetectionModule
 * Prueba la detección de URL en Local by WP Engine
 */

// Cargar WordPress si está disponible
$wp_load_path = dirname(__FILE__, 6) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
    echo "✅ WordPress cargado desde: $wp_load_path\n\n";
} else {
    echo "⚠️  WordPress no disponible, probando detección independiente\n\n";
}

// Cargar el módulo
require_once __DIR__ . '/SiteUrlDetectionModule.php';

echo "🔧 =================================================\n";
echo "   PRUEBA DEL SITEURLDETECTION MODULE\n";
echo "🔧 =================================================\n\n";

// Test con debug activado
$detector = new SiteUrlDetectionModule(true);

echo "📊 INFORMACIÓN DEL ENTORNO:\n";
echo "----------------------------\n";
$env_info = $detector->get_environment_info();
foreach ($env_info as $key => $value) {
    if (is_array($value)) {
        echo "• $key: " . json_encode($value, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "• $key: " . ($value ? 'true' : ($value === false ? 'false' : $value)) . "\n";
    }
}

echo "\n🎯 URL DETECTADO:\n";
echo "----------------\n";
echo "URL del sitio: " . $detector->get_site_url() . "\n\n";

echo "🧪 TEST COMPLETO DE DETECCIÓN:\n";
echo "------------------------------\n";
$test_result = $detector->test_detection();
echo json_encode($test_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "🚀 PRUEBA MÉTODO ESTÁTICO:\n";
echo "--------------------------\n";
$static_url = SiteUrlDetectionModule::get_current_site_url(true);
echo "URL estático: $static_url\n\n";

echo "📋 INFORMACIÓN ESPECÍFICA LOCAL BY WP ENGINE:\n";
echo "--------------------------------------------\n";
echo "• Router Mode detectado: " . $env_info['router_mode'] . "\n";
echo "• Es Local by WP Engine: " . ($env_info['is_local_wp'] ? 'SÍ' : 'NO') . "\n";
echo "• WordPress disponible: " . ($env_info['wp_available'] ? 'SÍ' : 'NO') . "\n";
echo "• Método de detección: " . $env_info['detection_method'] . "\n\n";

if ($env_info['is_local_wp']) {
    echo "🔍 ANÁLISIS ROUTER MODE:\n";
    echo "-----------------------\n";
    switch ($env_info['router_mode']) {
        case 'localhost_mode':
            echo "• Modo: localhost Router Mode\n";
            echo "• Comportamiento: Acceso directo por puerto (ej: localhost:10019)\n";
            echo "• Sin router en puerto 80\n";
            echo "• HTTPS no disponible\n";
            break;
        case 'site_domains_mode':
            echo "• Modo: Site Domains Router Mode\n";
            echo "• Comportamiento: Router en puerto 80 (ej: sitio.local)\n";
            echo "• HTTPS disponible\n";
            echo "• Live Links disponible\n";
            break;
        default:
            echo "• Modo: No determinado o configuración custom\n";
    }
}

echo "\n✅ Prueba completada\n";
