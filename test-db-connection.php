<?php
/**
 * Test directo del DatabaseConnectionModule
 * Script simple para verificar que la conexión funciona
 */

// Cargar WordPress
require_once(__DIR__ . '/../../../../wp-load.php');

// Cargar el módulo
require_once(__DIR__ . '/modules/DatabaseConnectionModule.php');

echo "🔧 Test DatabaseConnectionModule - PHPUnit Setup\n";
echo "============================================\n\n";

try {
    // Instanciar módulo con debug activado
    echo "📊 Creando instancia del módulo...\n";
    $db_module = new DatabaseConnectionModule(true);
    
    // Obtener información del entorno
    echo "🌍 Información del Entorno:\n";
    $env_info = $db_module->get_environment_info();
    print_r($env_info);
    echo "\n";
    
    // Test de conexión
    echo "🔌 Test de Conexión:\n";
    $test_result = $db_module->test_connection();
    
    if ($test_result['success']) {
        echo "✅ CONEXIÓN EXITOSA\n";
        echo "DSN usado: " . $test_result['dsn_used'] . "\n";
        echo "Servidor: " . $test_result['server_info'] . "\n";
        
        if (isset($test_result['test_query'])) {
            echo "MySQL Version: " . $test_result['test_query']['version'] . "\n";
        }
    } else {
        echo "❌ ERROR DE CONEXIÓN\n";
        echo "Error: " . ($test_result['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n";
    
    // Verificar constantes de WordPress
    echo "🏠 Constantes de WordPress:\n";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'No definida') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'No definida') . "\n";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'No definida') . "\n";
    echo "DB_PASSWORD: " . (defined('DB_PASSWORD') ? '***definida***' : 'No definida') . "\n";
    
    echo "\n✅ Test completado exitosamente\n";
    
} catch (Exception $e) {
    echo "💥 EXCEPCIÓN CAPTURADA\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
