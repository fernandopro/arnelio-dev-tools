<?php
/**
 * Template de Override para Bootstrap de Tests
 * 
 * Copiar este archivo como:
 * plugin-dev-tools/tests/bootstrap.php
 * 
 * Este archivo permite personalizar la inicialización de tests específica para cada plugin
 * manteniendo el core compartido intacto.
 * 
 * @package DevTools\Tests
 * @version 3.0.0
 */

// =============================================================================
// CONFIGURACIÓN ESPECÍFICA DEL PLUGIN
// =============================================================================

// Detectar automáticamente el plugin host
$plugin_dir = dirname(dirname(__DIR__));
$plugin_files = glob($plugin_dir . '/*.php');
$plugin_main_file = null;

foreach ($plugin_files as $file) {
    $content = file_get_contents($file, false, null, 0, 2000);
    if (strpos($content, 'Plugin Name:') !== false) {
        $plugin_main_file = $file;
        break;
    }
}

if ($plugin_main_file) {
    $plugin_data = get_file_data($plugin_main_file, [
        'Name' => 'Plugin Name',
        'Version' => 'Version',
        'TextDomain' => 'Text Domain'
    ]);
    
    $plugin_slug = basename($plugin_dir);
    $plugin_name = $plugin_data['Name'] ?: $plugin_slug;
    
    echo "\n🔧 Bootstrap Override para: " . $plugin_name . "\n";
    echo "📁 Plugin Directory: " . $plugin_dir . "\n";
    echo "📄 Plugin File: " . $plugin_main_file . "\n\n";
}

// =============================================================================
// CARGA DE WORDPRESS PARA TESTING
// =============================================================================

// Usar configuración de wp-tests-config.php (puede ser override también)
$wp_tests_config = __DIR__ . '/../wp-tests-config.php';
if (!file_exists($wp_tests_config)) {
    // Buscar en el directorio core si no existe override
    $wp_tests_config = dirname(__DIR__) . '/dev-tools/wp-tests-config.php';
}

if (file_exists($wp_tests_config)) {
    echo "📋 Cargando configuración de tests: " . basename($wp_tests_config) . "\n";
    require_once $wp_tests_config;
} else {
    die("❌ No se encontró wp-tests-config.php\n");
}

// =============================================================================
// CARGA DEL PLUGIN ESPECÍFICO
// =============================================================================

if ($plugin_main_file && file_exists($plugin_main_file)) {
    echo "🔌 Cargando plugin: " . basename($plugin_main_file) . "\n";
    
    // Activar plugin específico para testing
    $plugins_to_activate = [basename($plugin_main_file)];
    update_option('active_plugins', $plugins_to_activate);
    
    // Cargar el plugin
    require_once $plugin_main_file;
    
    // Ejecutar hook de activación si existe
    $activation_hook = $plugin_slug . '_activation_hook';
    if (function_exists($activation_hook)) {
        echo "⚡ Ejecutando hook de activación: " . $activation_hook . "\n";
        call_user_func($activation_hook);
    }
} else {
    echo "⚠️ No se pudo cargar el plugin automáticamente\n";
}

// =============================================================================
// CONFIGURACIÓN ESPECÍFICA DEL PLUGIN
// =============================================================================

// EJEMPLO: Para Tarokina
if (isset($plugin_name) && strpos(strtolower($plugin_name), 'tarokina') !== false) {
    echo "🔮 Configurando entorno específico para Tarokina...\n";
    
    // Configurar custom post types específicos de Tarokina para testing
    add_action('init', function() {
        if (!post_type_exists('tarokina_deck')) {
            register_post_type('tarokina_deck', [
                'public' => true,
                'supports' => ['title', 'editor', 'custom-fields']
            ]);
        }
    });
    
    // Configurar opciones específicas de Tarokina
    add_action('init', function() {
        update_option('tarokina_test_mode', true);
        update_option('tarokina_default_deck', 'test-deck');
    });
    
    echo "✅ Configuración específica de Tarokina aplicada\n";
}

// =============================================================================
// CARGA DE TESTS ESPECÍFICOS DEL PLUGIN
// =============================================================================

// Cargar clases de test específicas del plugin
$plugin_tests_dir = __DIR__;
$test_files = glob($plugin_tests_dir . '/*Test.php');

if (!empty($test_files)) {
    echo "📚 Cargando " . count($test_files) . " archivos de test específicos...\n";
    foreach ($test_files as $test_file) {
        echo "  📄 " . basename($test_file) . "\n";
        require_once $test_file;
    }
}

// =============================================================================
// INICIALIZACIÓN ESPECÍFICA ADICIONAL
// =============================================================================

// Aquí puedes añadir inicialización específica adicional para tu plugin:

// Ejemplo: Crear datos de prueba específicos
/*
add_action('init', function() {
    // Crear posts de prueba específicos del plugin
    wp_insert_post([
        'post_title' => 'Test Post for Plugin',
        'post_content' => 'Content for testing',
        'post_status' => 'publish',
        'post_type' => 'your_custom_post_type'
    ]);
});
*/

// Ejemplo: Configurar usuarios de prueba específicos
/*
add_action('init', function() {
    $test_user = wp_create_user('plugin_test_user', 'password', 'test@plugin.com');
    if (!is_wp_error($test_user)) {
        wp_update_user(['ID' => $test_user, 'role' => 'administrator']);
    }
});
*/

echo "🎯 Bootstrap override específico del plugin completado\n\n";

// IMPORTANTE: Si quieres que este override sea COMPLETO y no cargar nada más
// del bootstrap core, descomenta la siguiente línea:
// define('DEV_TOOLS_BOOTSTRAP_OVERRIDE_COMPLETE', true);
