<?php
/**
 * Test Script - SiteUrlDetectionModule
 * 
 * Script para probar detección de URL sin WordPress
 */

// Cargar el módulo SIN WordPress para probar detección independiente
require_once(__DIR__ . '/SiteUrlDetectionModule.php');

echo "<h1>🌐 Test SiteUrlDetectionModule</h1>";
echo "<h2>Detección de URL sin contexto WordPress</h2>";
echo "<hr>";

try {
    // Instanciar módulo con debug activado
    echo "<h3>📊 Inicializando módulo...</h3>";
    $url_detector = new SiteUrlDetectionModule(true);
    
    // Obtener URL detectada
    echo "<h3>🎯 URL Detectada</h3>";
    $detected_url = $url_detector->get_site_url();
    
    if ($detected_url) {
        echo "<div style='color: green; font-weight: bold;'>✅ URL DETECTADA: {$detected_url}</div>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ NO SE PUDO DETECTAR URL</div>";
    }
    
    // Información completa de detección
    echo "<h3>📋 Información de Detección</h3>";
    $detection_info = $url_detector->get_detection_info();
    echo "<pre>";
    print_r($detection_info);
    echo "</pre>";
    
    // Test de construcción de URLs
    echo "<h3>🔧 Test de Construcción de URLs</h3>";
    $examples = [
        'Site URL' => $detected_url,
        'Plugin URL' => $url_detector->get_plugin_url(),
        'Test file URL' => $url_detector->get_plugin_url('dev-tools/modules/test.php'),
        'Admin URL' => $url_detector->build_url('wp-admin'),
        'Custom path' => $url_detector->build_url('custom/path/here')
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tipo</th><th>URL Generada</th></tr>";
    foreach ($examples as $type => $url) {
        echo "<tr><td><strong>{$type}</strong></td><td>{$url}</td></tr>";
    }
    echo "</table>";
    
    // Test completo del módulo
    echo "<h3>🧪 Test Completo</h3>";
    $test_result = $url_detector->test_detection();
    echo "<pre>";
    print_r($test_result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>💥 EXCEPCIÓN CAPTURADA</div>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h3>🔍 Información del Entorno</h3>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'No disponible') . "</p>";
echo "<p><strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'No disponible') . "</p>";
echo "<p><strong>HTTPS:</strong> " . ($_SERVER['HTTPS'] ?? 'No configurado') . "</p>";

echo "<p><em>Test completado - " . date('Y-m-d H:i:s') . "</em></p>";
?>
