#!/usr/bin/env php
<?php
/**
 * Script de Verificación de Entorno de Desarrollo
 * 
 * Ejecutar desde WordPress root: php wp-content/plugins/tarokina-2025/dev-tools/scripts/check-environment.php
 */

echo "🚀 Iniciando verificación de entorno...\n";

// Intentar cargar WordPress si no está cargado
if (!defined('ABSPATH')) {
    // Buscar wp-config.php desde diferentes ubicaciones
    $possible_configs = [
        getcwd() . '/wp-config.php',                    // Desde root WP
        __DIR__ . '/../../../../wp-config.php',         // Desde dev-tools/scripts
        __DIR__ . '/../../../../../wp-config.php',      // Un nivel más arriba
        dirname(__DIR__, 6) . '/wp-config.php'          // Alternativo
    ];
    
    $config_found = false;
    foreach ($possible_configs as $config_path) {
        echo "🔍 Buscando wp-config en: " . $config_path . "\n";
        if (file_exists($config_path)) {
            echo "✅ wp-config encontrado: " . $config_path . "\n";
            define('WP_USE_THEMES', false);
            require_once $config_path;
            $config_found = true;
            break;
        }
    }
    
    if (!$config_found) {
        echo "❌ No se pudo encontrar wp-config.php\n";
        echo "💡 Ejecutar desde el directorio raíz de WordPress\n";
        exit(1);
    }
}

if (!defined('ABSPATH')) {
    echo "❌ WordPress no se cargó correctamente\n";
    exit(1);
}

echo "✅ WordPress cargado desde: " . ABSPATH . "\n";

echo "\n" . str_repeat("=", 70) . "\n";
echo "🔧 VERIFICACIÓN DE ENTORNO DE DESARROLLO - TAROKINA 2025\n";
echo str_repeat("=", 70) . "\n";

// Información básica del sistema
echo "📊 INFORMACIÓN DEL SISTEMA:\n";
echo "  🐘 PHP: " . PHP_VERSION . "\n";
echo "  🔗 WordPress: " . get_bloginfo('version') . "\n";
echo "  🖥️  OS: " . PHP_OS . "\n";
echo "  📁 ABSPATH: " . ABSPATH . "\n\n";

// Verificar Local by WP Engine
echo "🏗️  LOCAL BY WP ENGINE:\n";
$db_host = DB_HOST;
if (strpos($db_host, '/Local/run/') !== false) {
    echo "  ✅ Detectado: Socket Local by WP Engine\n";
    echo "  🔗 Socket: " . $db_host . "\n";
    
    // Verificar que el socket existe
    $socket_path = str_replace('localhost:', '', $db_host);
    if (file_exists($socket_path)) {
        echo "  ✅ Socket existe en el sistema\n";
    } else {
        echo "  ❌ Socket no encontrado: " . $socket_path . "\n";
    }
} else {
    echo "  ⚠️  No parece ser Local by WP Engine\n";
    echo "  🔗 DB_HOST: " . $db_host . "\n";
}

// Configuración de base de datos
echo "\n🗄️  BASE DE DATOS:\n";
echo "  📋 Nombre: " . DB_NAME . "\n";
echo "  👤 Usuario: " . DB_USER . "\n";
echo "  🔐 Password: " . (empty(DB_PASSWORD) ? 'VACÍO' : 'CONFIGURADO') . "\n";
echo "  🔤 Charset: " . DB_CHARSET . "\n";
echo "  🏷️  Collate: " . DB_COLLATE . "\n";

// URLs del sitio
echo "\n🌍 URLS DEL SITIO:\n";
$site_url = get_site_url();
$home_url = get_home_url();
echo "  🏠 Home URL: " . $home_url . "\n";
echo "  🌐 Site URL: " . $site_url . "\n";

if (strpos($site_url, '.local') !== false) {
    echo "  ✅ Dominio local detectado\n";
} else {
    echo "  ⚠️  No parece ser dominio local\n";
}

// Configuración de debug
echo "\n🔍 CONFIGURACIÓN DE DEBUG:\n";
echo "  WP_DEBUG: " . (WP_DEBUG ? '✅ ACTIVO' : '❌ INACTIVO') . "\n";
echo "  WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ ACTIVO' : '❌ INACTIVO') . "\n";
echo "  SCRIPT_DEBUG: " . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '✅ ACTIVO' : '❌ INACTIVO') . "\n";

if (defined('WP_ENVIRONMENT_TYPE')) {
    echo "  Environment: " . WP_ENVIRONMENT_TYPE . "\n";
}

// Verificar Node.js y NPM
echo "\n🟢 NODE.JS Y NPM:\n";
$node_version = trim(shell_exec('node --version 2>/dev/null') ?? '');
$npm_version = trim(shell_exec('npm --version 2>/dev/null') ?? '');

if (!empty($node_version)) {
    echo "  ✅ Node.js: " . $node_version . "\n";
} else {
    echo "  ❌ Node.js no encontrado\n";
}

if (!empty($npm_version)) {
    echo "  ✅ NPM: " . $npm_version . "\n";
} else {
    echo "  ❌ NPM no encontrado\n";
}

// Verificar dev-tools
echo "\n🔧 DEV-TOOLS:\n";
$plugin_dir = WP_PLUGIN_DIR . '/tarokina-2025/dev-tools';
if (is_dir($plugin_dir)) {
    echo "  ✅ Directorio dev-tools encontrado\n";
    echo "  📁 Path: " . $plugin_dir . "\n";
    
    // Verificar archivos importantes
    $important_files = [
        'package.json' => 'Configuración NPM',
        'webpack.config.js' => 'Configuración Webpack',
        'composer.json' => 'Configuración Composer',
        'phpunit.xml.dist' => 'Configuración PHPUnit'
    ];
    
    foreach ($important_files as $file => $description) {
        $file_path = $plugin_dir . '/' . $file;
        if (file_exists($file_path)) {
            echo "  ✅ " . $description . " (" . $file . ")\n";
        } else {
            echo "  ❌ " . $description . " faltante (" . $file . ")\n";
        }
    }
    
    // Verificar node_modules
    $node_modules = $plugin_dir . '/node_modules';
    if (is_dir($node_modules)) {
        echo "  ✅ node_modules instalado\n";
        
        // Contar dependencias
        $deps = glob($node_modules . '/*', GLOB_ONLYDIR);
        echo "  📦 Dependencias: " . count($deps) . "\n";
    } else {
        echo "  ⚠️  node_modules no encontrado - ejecutar 'npm install'\n";
    }
    
    // Verificar vendor (Composer)
    $vendor = $plugin_dir . '/vendor';
    if (is_dir($vendor)) {
        echo "  ✅ vendor (Composer) instalado\n";
    } else {
        echo "  ⚠️  vendor no encontrado - ejecutar 'composer install'\n";
    }
    
} else {
    echo "  ❌ Directorio dev-tools no encontrado\n";
}

// Verificar permisos
echo "\n🔐 PERMISOS:\n";
$upload_dir = wp_upload_dir();
echo "  📁 Uploads: " . (is_writable($upload_dir['basedir']) ? '✅ ESCRIBIBLE' : '❌ NO ESCRIBIBLE') . "\n";
echo "  📁 Plugins: " . (is_writable(WP_PLUGIN_DIR) ? '✅ ESCRIBIBLE' : '❌ NO ESCRIBIBLE') . "\n";

// Verificar logs de error
echo "\n📝 LOGS DE ERROR:\n";
$php_error_log = ini_get('error_log');
if (!empty($php_error_log) && file_exists($php_error_log)) {
    $log_size = filesize($php_error_log);
    echo "  📄 PHP Error Log: " . $php_error_log . " (" . number_format($log_size) . " bytes)\n";
    
    if ($log_size > 0) {
        echo "  🔍 Últimas entradas:\n";
        $last_lines = shell_exec("tail -3 '{$php_error_log}' 2>/dev/null");
        if (!empty($last_lines)) {
            $lines = explode("\n", trim($last_lines));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    echo "    " . substr($line, 0, 80) . "...\n";
                }
            }
        }
    }
} else {
    echo "  ⚠️  PHP Error Log no configurado o no existe\n";
}

if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    $wp_debug_log = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($wp_debug_log)) {
        $wp_log_size = filesize($wp_debug_log);
        echo "  📄 WordPress Debug Log: " . number_format($wp_log_size) . " bytes\n";
    } else {
        echo "  📄 WordPress Debug Log: No existe (aún no hay errores)\n";
    }
}

// Recomendaciones
echo "\n💡 RECOMENDACIONES:\n";

if (empty($node_version)) {
    echo "  🔧 Instalar Node.js desde https://nodejs.org/\n";
}

if (!is_dir($plugin_dir . '/node_modules')) {
    echo "  🔧 Ejecutar 'npm install' en directorio dev-tools\n";
}

if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
    echo "  🔧 Activar WP_DEBUG_LOG en wp-config.php\n";
}

if (strpos($site_url, '.local') === false) {
    echo "  🔧 Configurar dominio .local en Local by WP Engine\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Verificación completada\n";
echo str_repeat("=", 70) . "\n\n";
