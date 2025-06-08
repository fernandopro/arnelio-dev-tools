<?php
/**
 * Diagnóstico completo del sistema Dev-Tools Arquitectura 3.0
 */

// Solo ejecutar para administradores
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para ejecutar este diagnóstico.');
}

echo "<h1>🔍 Diagnóstico Dev-Tools Arquitectura 3.0</h1>";
echo "<style>
    .diagnostic { margin: 20px 0; padding: 15px; border-left: 4px solid #0073aa; background: #f7f7f7; }
    .success { border-left-color: #46b450; }
    .error { border-left-color: #dc3232; }
    .warning { border-left-color: #ffb900; }
    .code { background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; }
</style>";

// 1. Verificar constantes del plugin
echo "<div class='diagnostic'>";
echo "<h2>📋 1. Constantes del Plugin</h2>";
echo "<div class='code'>";
echo "TAROKINA_PRODUCTION_MODE: " . (defined('TAROKINA_PRODUCTION_MODE') ? (TAROKINA_PRODUCTION_MODE ? 'true' : 'false') : 'NO DEFINIDA') . "<br>";
echo "Plugin DIR: " . plugin_dir_path(__FILE__) . "<br>";
echo "Plugin URL: " . plugin_dir_url(__FILE__) . "<br>";
echo "</div>";
echo "</div>";

// 2. Verificar archivos del sistema
echo "<div class='diagnostic'>";
echo "<h2>📁 2. Archivos del Sistema</h2>";
$plugin_root = dirname(__FILE__);
$archivos_criticos = [
    'loader.php' => $plugin_root . '/loader.php',
    'config.php' => $plugin_root . '/config.php',
    'ajax-handler.php' => $plugin_root . '/ajax-handler.php',
    'panel.php' => $plugin_root . '/panel.php',
    'js/dev-tools.min.js' => $plugin_root . '/js/dev-tools.min.js',
    'css/dev-tools-styles.min.css' => $plugin_root . '/css/dev-tools-styles.min.css'
];

foreach ($archivos_criticos as $nombre => $ruta) {
    $existe = file_exists($ruta);
    $clase = $existe ? 'success' : 'error';
    $estado = $existe ? '✅ EXISTE' : '❌ FALTA';
    echo "<div class='$clase'>$nombre: $estado</div>";
}
echo "</div>";

// 3. Verificar carga del loader
echo "<div class='diagnostic'>";
echo "<h2>🔄 3. Estado del Loader</h2>";
$loader_path = $plugin_root . '/loader.php';
if (file_exists($loader_path)) {
    echo "<div class='success'>✅ Loader existe</div>";
    
    // Verificar si las clases están cargadas
    $clases_esperadas = [
        'DevToolsConfig',
        'DevToolsModuleLoader',
        'DevToolsAjaxHandler'
    ];
    
    foreach ($clases_esperadas as $clase) {
        $existe = class_exists($clase);
        $estado = $existe ? '✅ CARGADA' : '❌ NO CARGADA';
        $color = $existe ? 'success' : 'error';
        echo "<div class='$color'>Clase $clase: $estado</div>";
    }
} else {
    echo "<div class='error'>❌ Loader no encontrado</div>";
}
echo "</div>";

// 4. Verificar configuración
echo "<div class='diagnostic'>";
echo "<h2>⚙️ 4. Configuración</h2>";
if (class_exists('DevToolsConfig')) {
    try {
        $config = DevToolsConfig::getInstance();
        echo "<div class='success'>✅ DevToolsConfig inicializado</div>";
        
        // Verificar algunas configuraciones clave
        $configuraciones = [
            'dev_tools.menu_slug',
            'dev_tools.capability',
            'paths.base',
            'paths.modules'
        ];
        
        foreach ($configuraciones as $key) {
            $valor = $config->get($key);
            echo "<div class='code'>$key: " . ($valor ? $valor : 'NO DEFINIDO') . "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error en DevToolsConfig: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ DevToolsConfig no disponible</div>";
}
echo "</div>";

// 5. Verificar módulos
echo "<div class='diagnostic'>";
echo "<h2>🧩 5. Estado de Módulos</h2>";
if (class_exists('DevToolsModuleLoader')) {
    try {
        $loader = DevToolsModuleLoader::getInstance();
        echo "<div class='success'>✅ DevToolsModuleLoader inicializado</div>";
        
        $modulos_esperados = ['dashboard', 'systeminfo', 'cache', 'ajaxtester', 'logs', 'performance'];
        foreach ($modulos_esperados as $modulo) {
            $existe = $loader->moduleExists($modulo);
            $estado = $existe ? '✅ EXISTE' : '❌ NO ENCONTRADO';
            $color = $existe ? 'success' : 'error';
            echo "<div class='$color'>Módulo $modulo: $estado</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error en ModuleLoader: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ DevToolsModuleLoader no disponible</div>";
}
echo "</div>";

// 6. Verificar assets compilados
echo "<div class='diagnostic'>";
echo "<h2>🎨 6. Assets Compilados</h2>";
$js_path = $plugin_root . '/js/dev-tools.min.js';
$css_path = $plugin_root . '/css/dev-tools-styles.min.css';

if (file_exists($js_path)) {
    $js_size = filesize($js_path);
    echo "<div class='success'>✅ JavaScript compilado ($js_size bytes)</div>";
} else {
    echo "<div class='error'>❌ JavaScript no compilado</div>";
}

if (file_exists($css_path)) {
    $css_size = filesize($css_path);
    echo "<div class='success'>✅ CSS compilado ($css_size bytes)</div>";
} else {
    echo "<div class='error'>❌ CSS no compilado</div>";
}
echo "</div>";

// 7. Verificar hooks de WordPress
echo "<div class='diagnostic'>";
echo "<h2>🪝 7. WordPress Hooks</h2>";
$hooks_verificar = [
    'admin_menu',
    'admin_enqueue_scripts',
    'wp_ajax_dev_tools_request'
];

foreach ($hooks_verificar as $hook) {
    $registrado = has_action($hook);
    $estado = $registrado ? '✅ REGISTRADO' : '❌ NO REGISTRADO';
    $color = $registrado ? 'success' : 'warning';
    echo "<div class='$color'>Hook $hook: $estado</div>";
}
echo "</div>";

// 8. Test de acceso directo
echo "<div class='diagnostic'>";
echo "<h2>🔗 8. Test de Acceso</h2>";
$admin_url = admin_url('tools.php?page=tarokina-2025-dev-tools');
echo "<div class='code'>";
echo "URL del panel: <a href='$admin_url' target='_blank'>$admin_url</a><br>";
echo "Usuario actual puede gestionar opciones: " . (current_user_can('manage_options') ? 'SÍ' : 'NO') . "<br>";
echo "Es modo desarrollo: " . ((defined('TAROKINA_PRODUCTION_MODE') && !TAROKINA_PRODUCTION_MODE) ? 'SÍ' : 'NO') . "<br>";
echo "</div>";
echo "</div>";

echo "<h2>🚀 Próximos pasos recomendados:</h2>";
echo "<ol>";
echo "<li>Si hay archivos faltantes, recompilar con: <code>cd dev-tools && npm run dev</code></li>";
echo "<li>Si las clases no están cargadas, verificar el loader.php</li>";
echo "<li>Si los hooks no están registrados, verificar la inicialización en tarokina-pro.php</li>";
echo "</ol>";
