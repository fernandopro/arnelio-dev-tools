<?php
/**
 * Test Script - DatabaseConnectionModule
 * 
 * Script de prueba para verificar la conexión a MySQL
 * en Local by WP Engine
 */


// Cargar WordPress - Ruta corregida para Local by WP Engine
require_once(__DIR__ . '/../../../../../wp-load.php');

// Cargar el módulo
require_once(__DIR__ . '/DatabaseConnectionModule.php');

echo "<h1>🔧 Test DatabaseConnectionModule</h1>";
echo "<h2>Dev-Tools Arquitectura 3.0</h2>";
echo "<hr>";


try {
    // Instanciar módulo con debug activado
    echo "<h3>📊 Inicializando módulo...</h3>";
    $db_module = new DatabaseConnectionModule(true);
    
    // Obtener información del entorno
    echo "<h3>🌍 Información del Entorno</h3>";
    $env_info = $db_module->get_environment_info();
    echo "<pre>";
    print_r($env_info);
    echo "</pre>";
    
    // Test de conexión completo
    echo "<h3>🔌 Test de Conexión</h3>";
    $test_result = $db_module->test_connection();
    
    if ($test_result['success']) {
        echo "<div style='color: green; font-weight: bold;'>✅ CONEXIÓN EXITOSA</div>";
        echo "<p><strong>DSN usado:</strong> " . $test_result['dsn_used'] . "</p>";
        echo "<p><strong>Información del servidor:</strong> " . $test_result['server_info'] . "</p>";
        
        if (isset($test_result['test_query'])) {
            echo "<h4>📋 Query de prueba:</h4>";
            echo "<pre>";
            print_r($test_result['test_query']);
            echo "</pre>";
        }
        
        // Test adicional: consulta a WordPress
        echo "<h3>🎯 Test consulta WordPress</h3>";
        $wp_test = $db_module->query(
            "SELECT post_title, post_type, post_status FROM {$GLOBALS['wpdb']->posts} LIMIT 5"
        );
        $posts = $wp_test->fetchAll();
        
        echo "<p><strong>Primeros 5 posts encontrados:</strong></p>";
        echo "<pre>";
        print_r($posts);
        echo "</pre>";
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ ERROR EN CONEXIÓN</div>";
        echo "<p><strong>Error:</strong> " . $test_result['error'] . "</p>";
        echo "<p><strong>DSN intentado:</strong> " . $test_result['dsn_used'] . "</p>";
    }
    
    // Mostrar información completa del test
    echo "<h3>📄 Resultado completo del test</h3>";
    echo "<pre>";
    print_r($test_result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>💥 EXCEPCIÓN CAPTURADA</div>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h3>🏠 Información del entorno WordPress</h3>";
echo "<p><strong>WP Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>DB_HOST:</strong> " . DB_HOST . "</p>";
echo "<p><strong>DB_NAME:</strong> " . DB_NAME . "</p>";
echo "<p><strong>DB_USER:</strong> " . DB_USER . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current User:</strong> " . get_current_user() . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";

// Verificar si el socket existe
$socket_path = '/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock';
echo "<h3>🔍 Verificación del Socket</h3>";
echo "<p><strong>Socket path:</strong> " . $socket_path . "</p>";
echo "<p><strong>Existe:</strong> " . (file_exists($socket_path) ? '✅ SÍ' : '❌ NO') . "</p>";

if (file_exists($socket_path)) {
    $perms = fileperms($socket_path);
    echo "<p><strong>Permisos:</strong> " . substr(sprintf('%o', $perms), -4) . "</p>";
}

echo "<p><em>Test completado - " . date('Y-m-d H:i:s') . "</em></p>";
?>
