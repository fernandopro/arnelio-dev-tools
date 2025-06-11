<?php
/**
 * Test Script - Dev-Tools Paths Configuration
 * 
 * Script para probar el sistema agnóstico de rutas
 * Funciona tanto desde navegador como desde línea de comandos
 */

// Permitir acceso directo para testing
define('DEV_TOOLS_DIRECT_ACCESS', true);

// Cargar WordPress si está disponible
$wp_load_path = dirname(__FILE__, 6) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
}

// Cargar el sistema de rutas
require_once __DIR__ . '/../config/paths.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Dev-Tools Paths</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .info { color: blue; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .path-cell { font-family: monospace; font-size: 12px; word-break: break-all; }
    </style>
</head>
<body>
    <h1>🔧 Test Dev-Tools Paths Configuration</h1>
    <p><strong>Dev-Tools Arquitectura 3.0 - Sistema Agnóstico de Rutas</strong></p>
    
    <?php
    echo "<div class='box'>";
    echo "<h2>📊 Constantes Globales Definidas</h2>";
    
    $constants = [
        'DEV_TOOLS_BASE_PATH' => 'Ruta base de dev-tools',
        'DEV_TOOLS_BASE_URL' => 'URL base de dev-tools', 
        'DEV_TOOLS_PLUGIN_PATH' => 'Ruta del plugin host',
        'DEV_TOOLS_PLUGIN_URL' => 'URL del plugin host',
        'DEV_TOOLS_CONFIG_PATH' => 'Ruta del directorio config',
        'DEV_TOOLS_CONFIG_URL' => 'URL del directorio config',
        'DEV_TOOLS_MODULES_PATH' => 'Ruta del directorio modules',
        'DEV_TOOLS_MODULES_URL' => 'URL del directorio modules',
        'DEV_TOOLS_DOCS_PATH' => 'Ruta del directorio docs',
        'DEV_TOOLS_DOCS_URL' => 'URL del directorio docs',
        'DEV_TOOLS_VENDOR_PATH' => 'Ruta del directorio vendor',
        'DEV_TOOLS_DIST_PATH' => 'Ruta del directorio dist',
        'DEV_TOOLS_DIST_URL' => 'URL del directorio dist'
    ];
    
    echo "<table>";
    echo "<tr><th>Constante</th><th>Descripción</th><th>Valor</th><th>Existe</th></tr>";
    
    foreach ($constants as $constant => $description) {
        $value = defined($constant) ? constant($constant) : 'NO DEFINIDA';
        $exists = 'N/A';
        
        if (defined($constant)) {
            $path = constant($constant);
            if (strpos($constant, '_URL') !== false) {
                $exists = '<span class="info">URL (no verificable)</span>';
            } else {
                $exists = is_dir($path) ? '<span class="success">✅ SÍ</span>' : '<span class="warning">❌ NO</span>';
            }
        }
        
        echo "<tr>";
        echo "<td><code>{$constant}</code></td>";
        echo "<td>{$description}</td>";
        echo "<td class='path-cell'>{$value}</td>";
        echo "<td>{$exists}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Test de funciones helper
    echo "<div class='box'>";
    echo "<h2>🔧 Test de Funciones Helper</h2>";
    
    echo "<h3>Función dev_tools_path():</h3>";
    echo "<ul>";
    echo "<li><strong>Base path:</strong> <code>" . dev_tools_path() . "</code></li>";
    echo "<li><strong>Config path:</strong> <code>" . dev_tools_path('config') . "</code></li>";
    echo "<li><strong>Modules path:</strong> <code>" . dev_tools_path('modules') . "</code></li>";
    echo "</ul>";
    
    echo "<h3>Función dev_tools_url():</h3>";
    echo "<ul>";
    echo "<li><strong>Base URL:</strong> <code>" . dev_tools_url() . "</code></li>";
    echo "<li><strong>Config URL:</strong> <code>" . dev_tools_url('config') . "</code></li>";
    echo "<li><strong>Modules URL:</strong> <code>" . dev_tools_url('modules') . "</code></li>";
    echo "</ul>";
    echo "</div>";
    
    // Información de debugging
    echo "<div class='box'>";
    echo "<h2>🔍 Información de Debug del Sistema</h2>";
    $paths = DevToolsPaths::getInstance();
    $debug_info = $paths->get_debug_info();
    echo "<pre>" . json_encode($debug_info, JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    
    // Test de carga de archivos
    echo "<div class='box'>";
    echo "<h2>📁 Test de Acceso a Archivos</h2>";
    
    $test_files = [
        'paths.php' => DEV_TOOLS_CONFIG_PATH . 'paths.php',
        'DatabaseConnectionModule.php' => DEV_TOOLS_MODULES_PATH . 'DatabaseConnectionModule.php',
        'SiteUrlDetectionModule.php' => DEV_TOOLS_MODULES_PATH . 'SiteUrlDetectionModule.php'
    ];
    
    echo "<table>";
    echo "<tr><th>Archivo</th><th>Ruta</th><th>Existe</th><th>Readable</th></tr>";
    
    foreach ($test_files as $name => $path) {
        $exists = file_exists($path);
        $readable = $exists && is_readable($path);
        
        echo "<tr>";
        echo "<td><code>{$name}</code></td>";
        echo "<td class='path-cell'>{$path}</td>";
        echo "<td>" . ($exists ? '<span class="success">✅ SÍ</span>' : '<span class="warning">❌ NO</span>') . "</td>";
        echo "<td>" . ($readable ? '<span class="success">✅ SÍ</span>' : '<span class="warning">❌ NO</span>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Test de compatibilidad con WordPress
    if (function_exists('get_site_url')) {
        echo "<div class='box'>";
        echo "<h2>🎯 Compatibilidad con WordPress</h2>";
        echo "<p><strong>WordPress detectado:</strong> <span class='success'>✅ SÍ</span></p>";
        echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
        echo "<p><strong>Plugin URL calculado:</strong> " . DEV_TOOLS_PLUGIN_URL . "</p>";
        echo "<p><strong>Dev-Tools URL calculado:</strong> " . DEV_TOOLS_BASE_URL . "</p>";
        echo "</div>";
    } else {
        echo "<div class='box'>";
        echo "<h2>⚠️ Modo Standalone</h2>";
        echo "<p><strong>WordPress:</strong> <span class='warning'>❌ NO detectado</span></p>";
        echo "<p>Funcionando en modo agnóstico sin WordPress</p>";
        echo "</div>";
    }
    ?>
    
    <div class='box'>
        <h2>📋 Características del Sistema Agnóstico</h2>
        <ul>
            <li>✅ <strong>Auto-detección de rutas:</strong> Funciona sin configuración manual</li>
            <li>✅ <strong>Compatible WordPress:</strong> Usa plugin_dir_url() cuando está disponible</li>
            <li>✅ <strong>Modo Standalone:</strong> Funciona sin WordPress</li>
            <li>✅ <strong>URLs dinámicas:</strong> Se adapta a Local by WP Engine automáticamente</li>
            <li>✅ <strong>Submódulo ready:</strong> Perfecto para Git submodules</li>
            <li>✅ <strong>Zero coupling:</strong> Sin referencias hardcodeadas al plugin host</li>
        </ul>
    </div>
    
    <p><em>Test completado - <?php echo date('Y-m-d H:i:s'); ?></em></p>
</body>
</html>
