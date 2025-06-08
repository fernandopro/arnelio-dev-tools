<?php
/**
 * Script de diagnóstico para Dev-Tools Arquitectura 3.0
 * Verificar por qué el panel sigue en modo compatibilidad
 */

// Solo ejecutar desde el admin de WordPress
if (!defined('ABSPATH')) {
    require_once('../../../../wp-load.php');
}

echo "<h1>Diagnóstico Dev-Tools Arquitectura 3.0</h1>";

// 1. Verificar archivos core
echo "<h2>1. Verificación de Archivos Core</h2>";
$archivos_core = [
    'config.php',
    'loader.php', 
    'ajax-handler.php',
    'panel.php',
    'core/DevToolsModuleManager.php',
    'core/DevToolsModuleBase.php',
    'core/interfaces/DevToolsModuleInterface.php'
];

foreach ($archivos_core as $archivo) {
    $path = __DIR__ . '/' . $archivo;
    if (file_exists($path)) {
        echo "<span style='color: green;'>✅ {$archivo}</span><br>";
    } else {
        echo "<span style='color: red;'>❌ {$archivo} - NO ENCONTRADO</span><br>";
    }
}

// 2. Verificar configuración
echo "<h2>2. Configuración</h2>";
if (function_exists('dev_tools_config')) {
    $config = dev_tools_config();
    echo "<span style='color: green;'>✅ Función dev_tools_config() disponible</span><br>";
    echo "<pre>" . print_r($config->get_all(), true) . "</pre>";
} else {
    echo "<span style='color: red;'>❌ Función dev_tools_config() NO disponible</span><br>";
}

// 3. Verificar módulos
echo "<h2>3. Módulos</h2>";
$modulos = [
    'modules/DashboardModule.php',
    'modules/SystemInfoModule.php',
    'modules/CacheModule.php',
    'modules/AjaxTesterModule.php',
    'modules/LogsModule.php',
    'modules/PerformanceModule.php'
];

foreach ($modulos as $modulo) {
    $path = __DIR__ . '/' . $modulo;
    if (file_exists($path)) {
        echo "<span style='color: green;'>✅ {$modulo}</span><br>";
    } else {
        echo "<span style='color: red;'>❌ {$modulo} - NO ENCONTRADO</span><br>";
    }
}

// 4. Verificar assets compilados
echo "<h2>4. Assets Compilados</h2>";
$assets = [
    'dist/css/dev-tools-styles.min.css',
    'dist/js/dev-tools.min.js',
    'dist/js/dashboard.min.js'
];

foreach ($assets as $asset) {
    $path = __DIR__ . '/' . $asset;
    if (file_exists($path)) {
        echo "<span style='color: green;'>✅ {$asset}</span><br>";
    } else {
        echo "<span style='color: red;'>❌ {$asset} - NO COMPILADO</span><br>";
    }
}

// 5. Verificar WordPress hooks
echo "<h2>5. WordPress Hooks</h2>";
if (has_action('admin_menu', 'dev_tools_admin_menu')) {
    echo "<span style='color: green;'>✅ Hook admin_menu registrado</span><br>";
} else {
    echo "<span style='color: orange;'>⚠️ Hook admin_menu NO registrado</span><br>";
}

// 6. Verificar errores PHP
echo "<h2>6. Errores PHP</h2>";
if (function_exists('error_get_last')) {
    $error = error_get_last();
    if ($error) {
        echo "<span style='color: red;'>❌ Último error PHP:</span><br>";
        echo "<pre>" . print_r($error, true) . "</pre>";
    } else {
        echo "<span style='color: green;'>✅ No hay errores PHP recientes</span><br>";
    }
}

// 7. Verificar constantes WordPress
echo "<h2>7. Constantes WordPress</h2>";
$constantes = ['WP_DEBUG', 'ABSPATH', 'WPINC'];
foreach ($constantes as $constante) {
    if (defined($constante)) {
        echo "<span style='color: green;'>✅ {$constante}: " . constant($constante) . "</span><br>";
    } else {
        echo "<span style='color: red;'>❌ {$constante} NO definida</span><br>";
    }
}

echo "<h2>8. Test de Carga Manual</h2>";
echo "<p>Intentando cargar manualmente el loader...</p>";

try {
    if (file_exists(__DIR__ . '/loader.php')) {
        // Simular la carga como lo haría tarokina-pro.php
        include_once __DIR__ . '/loader.php';
        echo "<span style='color: green;'>✅ Loader cargado sin errores</span><br>";
    } else {
        echo "<span style='color: red;'>❌ loader.php no encontrado</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Error al cargar loader: " . $e->getMessage() . "</span><br>";
}
