<?php
/**
 * Test de verificación de configuración dev_tools_url
 */

// Simular contexto WordPress mínimo
define('ABSPATH', '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/');

// Cargar configuración
require_once __DIR__ . '/config.php';

echo "🔧 Verificando configuración de dev_tools_url...\n\n";

try {
    $config = dev_tools_config();
    
    echo "✅ Configuración cargada correctamente\n";
    
    $dev_tools_url = $config->get('paths.dev_tools_url');
    echo "📍 dev_tools_url: " . $dev_tools_url . "\n";
    
    $host_plugin = $config->get('host');
    echo "🏠 Host plugin URL: " . $host_plugin['dir_url'] . "\n";
    
    $expected_url = $host_plugin['dir_url'] . 'dev-tools/';
    echo "🎯 URL esperada: " . $expected_url . "\n";
    
    if ($dev_tools_url === $expected_url) {
        echo "✅ La URL está correcta\n";
    } else {
        echo "❌ La URL no coincide con lo esperado\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test completado\n";
?>
