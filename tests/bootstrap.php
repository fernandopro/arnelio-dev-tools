<?php
/**
 * Bootstrap para Tests - Dev-Tools Arquitectura 3.0
 * 
 * Bootstrap PLUGIN-AGNÓSTICO para la Arquitectura 3.0 de Dev-Tools.
 * Diseñado para funcionar con cualquier plugin de WordPress.
 * 
 * Características:
 * - 100% independiente del plugin host
 * - Carga automática de la arquitectura modular
 * - Configuración dinámica sin dependencias externas
 * - Compatibilidad completa con PHPUnit
 * - Sistema de logging unificado
 * - Detección automática del entorno
 * 
 * @package DevTools\Tests
 * @since Arquitectura 3.0
 * @author Dev-Tools Team
 */

// =============================================================================
// CONFIGURACIÓN INICIAL
// =============================================================================

// Definir que estamos en contexto de testing
if (!defined('WP_TESTS_INDIVIDUAL')) {
    define('WP_TESTS_INDIVIDUAL', true);
}

if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

// Configuración de verbosidad para dev-tools
if (!defined('DEV_TOOLS_VERBOSE')) {
    define('DEV_TOOLS_VERBOSE', getenv('DEV_TOOLS_VERBOSE') === '1');
}

// =============================================================================
// SISTEMA DE OVERRIDE (Child Theme Pattern)
// =============================================================================

/**
 * Cargar bootstrap override específico del plugin si existe
 */
function load_bootstrap_override() {
    $override_bootstrap = dirname(dirname(dirname(__FILE__))) . '/plugin-dev-tools/tests/bootstrap.php';
    
    if (file_exists($override_bootstrap)) {
        test_log("🔄 Cargando bootstrap override específico del plugin...");
        require_once $override_bootstrap;
        
        // Si el override define que debe terminar aquí, respetarlo
        if (defined('DEV_TOOLS_BOOTSTRAP_OVERRIDE_COMPLETE') && DEV_TOOLS_BOOTSTRAP_OVERRIDE_COMPLETE) {
            test_log("✅ Bootstrap override completado, finalizando bootstrap core.");
            return true;
        }
        
        test_log("✅ Bootstrap override cargado, continuando con bootstrap core.");
    } else {
        test_log("ℹ️ No se encontró bootstrap override, usando configuración core.");
    }
    
    return false;
}

// Intentar cargar override primero
if (load_bootstrap_override()) {
    return; // El override se encarga de todo
}

// =============================================================================
// FUNCIONES DE UTILIDAD
// =============================================================================

/**
 * Echo seguro para entorno de testing
 */
function test_log($message) {
    if (php_sapi_name() === 'cli') {
        echo $message . PHP_EOL;
    }
}

/**
 * Verificar si un archivo existe y es legible
 */
function verify_file($path, $description) {
    if (!file_exists($path)) {
        throw new Exception("❌ {$description} no encontrado: {$path}");
    }
    test_log("✅ {$description}: " . basename($path));
    return $path;
}

// =============================================================================
// VERIFICACIONES INICIALES
// =============================================================================

test_log('🚀 DEV-TOOLS ARQUITECTURA 3.0: Iniciando bootstrap plugin-agnóstico...');

// Verificar estructura de directorios
$dev_tools_root = dirname(__DIR__);
$plugin_root = dirname($dev_tools_root);

test_log("📁 Dev-Tools detectado en: " . basename($dev_tools_root));
test_log("📁 Plugin host detectado en: " . basename($plugin_root));

// Verificar archivos críticos de Arquitectura 3.0
verify_file($dev_tools_root . '/config.php', 'Config principal');
verify_file($dev_tools_root . '/loader.php', 'Loader de Arquitectura 3.0');
verify_file($dev_tools_root . '/ajax-handler.php', 'AJAX Handler');
verify_file($dev_tools_root . '/core/DevToolsModuleBase.php', 'Clase base de módulos');

// =============================================================================
// CONFIGURACIÓN DE WORDPRESS TESTING
// =============================================================================

// Configurar ruta de configuración de tests
$config_file_path = $dev_tools_root . '/wp-tests-config.php';
verify_file($config_file_path, 'Configuración de tests');

define('WP_TESTS_CONFIG_FILE_PATH', $config_file_path);

// Verificar framework oficial de WordPress
$wp_tests_bootstrap = $dev_tools_root . '/wordpress-develop/tests/phpunit/includes/bootstrap.php';
verify_file($wp_tests_bootstrap, 'Framework WordPress PHPUnit');

test_log('🔧 Cargando framework oficial de WordPress...');
require_once $wp_tests_bootstrap;

test_log('✅ WordPress testing framework cargado');

// =============================================================================
// CARGAR DEV-TOOLS ARQUITECTURA 3.0
// =============================================================================

test_log('🏗️  Cargando Dev-Tools Arquitectura 3.0...');

// 1. Cargar configuración principal
require_once $dev_tools_root . '/config.php';
test_log('✅ Configuración cargada');

// 2. Cargar loader principal (que carga todo el sistema modular)
require_once $dev_tools_root . '/loader.php';
test_log('✅ Loader principal cargado');

// 3. Inicializar el sistema de módulos
if (class_exists('DevToolsModuleManager')) {
    // El loader ya debería haber inicializado el sistema
    test_log('✅ Sistema de módulos disponible');
} else {
    test_log('⚠️  Sistema de módulos no disponible - cargando manualmente...');
    
    // Cargar core manualmente si es necesario
    require_once $dev_tools_root . '/core/interfaces/DevToolsModuleInterface.php';
    require_once $dev_tools_root . '/core/DevToolsModuleBase.php';
    require_once $dev_tools_root . '/core/DevToolsModuleManager.php';
}

// 4. Cargar AJAX handler
require_once $dev_tools_root . '/ajax-handler.php';
test_log('✅ AJAX Handler cargado');

// =============================================================================
// CARGAR PLUGIN HOST (OPCIONAL)
// =============================================================================

test_log('🔌 Detectando plugin host...');

// Buscar archivo principal del plugin host (sin asumir nombres específicos)
$possible_plugin_files = glob($plugin_root . '/*.php');
$main_plugin_file = null;

foreach ($possible_plugin_files as $file) {
    $content = file_get_contents($file, false, null, 0, 1000); // Solo primeros 1000 bytes
    if (strpos($content, 'Plugin Name:') !== false) {
        $main_plugin_file = $file;
        break;
    }
}

if ($main_plugin_file) {
    // Extraer nombre del plugin del header
    $plugin_content = file_get_contents($main_plugin_file, false, null, 0, 2000);
    preg_match('/Plugin Name:\s*(.+)/i', $plugin_content, $matches);
    $plugin_name = isset($matches[1]) ? trim($matches[1]) : basename($main_plugin_file, '.php');
    
    test_log("📦 Plugin host detectado: {$plugin_name}");
    test_log("📄 Archivo principal: " . basename($main_plugin_file));
    
    // Cargar plugin solo si es seguro (evitar efectos secundarios)
    if (file_exists($main_plugin_file)) {
        require_once $main_plugin_file;
        test_log('✅ Plugin host cargado');
    }
} else {
    test_log('ℹ️  No se detectó plugin host - ejecutando dev-tools en modo independiente');
}

// =============================================================================
// CARGAR CLASE BASE DE TESTING
// =============================================================================

// Cargar nuestra clase base personalizada
$test_case_file = __DIR__ . '/DevToolsTestCase.php';
if (file_exists($test_case_file)) {
    require_once $test_case_file;
    test_log('✅ DevToolsTestCase cargada');
} else {
    test_log('⚠️  DevToolsTestCase no encontrada - usando WP_UnitTestCase estándar');
}

// =============================================================================
// CONFIGURACIÓN FINAL
// =============================================================================

// Hook para después de que WordPress esté completamente cargado
add_action('init', function() {
    test_log('🎉 Sistema completo iniciado - WordPress + Dev-Tools Arquitectura 3.0');
    
    // Verificar que los módulos estén disponibles
    if (class_exists('DevToolsModuleManager')) {
        $manager = DevToolsModuleManager::getInstance();
        $modules = $manager->getModulesStatus();
        test_log('📦 Módulos cargados: ' . implode(', ', array_keys($modules)));
    }
    
    // Información del entorno (plugin-agnóstico)
    $current_theme = wp_get_theme();
    test_log("🎨 Tema activo: {$current_theme->get('Name')}");
    test_log("🔗 Site URL: " . get_site_url());
}, 1);

test_log('✅ Bootstrap completado - Listo para tests de Arquitectura 3.0');

// =============================================================================
// CONFIGURACIÓN DE ERROR HANDLING
// =============================================================================

// Configurar manejo de errores para testing
if (DEV_TOOLS_VERBOSE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

// Configurar timeout generoso para tests
ini_set('max_execution_time', 300); // 5 minutos