<?php
/**
 * Test Browser para SiteUrlDetectionModule
 * 
 * Para ejecutar desde el navegador y ver cómo funciona
 * con el entorno real de Local by WP Engine
 */

// Cargar WordPress
$wp_load_path = dirname(__FILE__, 6) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
}

// Cargar el módulo
require_once __DIR__ . '/SiteUrlDetectionModule.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test SiteUrlDetectionModule</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Test SiteUrlDetectionModule</h1>
    <p><strong>Ejecutando en entorno real de Local by WP Engine</strong></p>
    
    <?php
    // Test del módulo
    echo "<div class='box'>";
    echo "<h2>📍 Creando instancia con debug...</h2>";
    $detector = new SiteUrlDetectionModule(true);
    
    echo "<h3 class='success'>✅ URL DETECTADO:</h3>";
    echo "<p><strong>" . ($detector->get_site_url() ?: 'NO DETECTADO') . "</strong></p>";
    
    echo "<h3>📊 INFORMACIÓN DEL ENTORNO:</h3>";
    $env_info = $detector->get_environment_info();
    echo "<pre>" . json_encode($env_info, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h3>🧪 TEST COMPLETO:</h3>";
    $test_result = $detector->test_detection();
    echo "<pre>" . json_encode($test_result, JSON_PRETTY_PRINT) . "</pre>";
    
    // Comparación con WordPress
    if (function_exists('get_site_url')) {
        echo "<h3>🔄 COMPARACIÓN CON WORDPRESS:</h3>";
        $wp_url = get_site_url();
        $detected_url = $detector->get_site_url();
        
        echo "<p><strong>WordPress get_site_url():</strong> " . $wp_url . "</p>";
        echo "<p><strong>Nuestro módulo detectó:</strong> " . $detected_url . "</p>";
        
        if ($wp_url === $detected_url) {
            echo "<p class='success'>✅ ¡Coinciden perfectamente!</p>";
        } else {
            echo "<p class='error'>❌ No coinciden</p>";
        }
    }
    
    echo "</div>";
    
    // Información del servidor real
    echo "<div class='box'>";
    echo "<h3>🌐 INFORMACIÓN REAL DEL SERVIDOR:</h3>";
    echo "<pre>";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NO DEFINIDO') . "\n";
    echo "SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? 'NO DEFINIDO') . "\n";
    echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO') . "\n";
    echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NO DEFINIDO') . "\n";
    echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NO DEFINIDO') . "\n";
    echo "</pre>";
    echo "</div>";
    ?>
    
    <div class='box'>
        <h3>📋 RESUMEN DE CAPACIDADES</h3>
        <ul>
            <li>✅ <strong>Plugin-agnóstico:</strong> Funciona sin WordPress</li>
            <li>✅ <strong>Auto-detección Local by WP Engine:</strong> Detecta router modes</li>
            <li>✅ <strong>Múltiples métodos:</strong> wp-config, server vars, Local config</li>
            <li>✅ <strong>Router Mode detection:</strong> localhost:port vs .local domains</li>
            <li>✅ <strong>Fallback inteligente:</strong> Funciona en cualquier entorno</li>
        </ul>
    </div>
</body>
</html>
