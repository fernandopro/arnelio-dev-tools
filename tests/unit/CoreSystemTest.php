<?php
/**
 * Tests Unitarios del Sistema Core - Dev-Tools Arquitectura 3.0
 *
 * @package TarokinaPro\DevTools\Tests\Unit
 * @since 1.0.0
 */

namespace DevToolsTests\Unit;

use DevToolsTestCase;

/**
 * Test del sistema core de Dev-Tools
 */
class CoreSystemTest extends DevToolsTestCase 
{
    /**
     * Test de inicialización del sistema core
     */
    public function test_core_system_initialization() 
    {
        // Verificar que las clases core están disponibles
        $this->assertTrue(class_exists('DevToolsLoader'), 'DevToolsLoader debe estar disponible');
        $this->assertTrue(class_exists('DevToolsModuleManager'), 'DevToolsModuleManager debe estar disponible');
        $this->assertTrue(class_exists('DevToolsModuleBase'), 'DevToolsModuleBase debe estar disponible');
        
        // Verificar que el sistema está inicializado
        $this->assertTrue(defined('DEV_TOOLS_VERSION'), 'DEV_TOOLS_VERSION debe estar definida');
        $this->assertTrue(defined('DEV_TOOLS_PATH'), 'DEV_TOOLS_PATH debe estar definida');
    }

    /**
     * Test de carga de configuración
     */
    public function test_config_loading() 
    {
        // Verificar que la configuración está cargada
        $this->assertTrue(function_exists('dev_tools_get_config'), 'Función dev_tools_get_config debe existir');
        
        // Test de configuración básica
        $config = dev_tools_get_config();
        $this->assertNotEmpty($config, 'La configuración no debe estar vacía');
        $this->assertIsArray($config, 'La configuración debe ser un array');
        
        // Verificar configuraciones críticas
        $this->assertArrayHasKey('menu_slug', $config, 'Debe tener menu_slug definido');
        $this->assertArrayHasKey('capability', $config, 'Debe tener capability definido');
    }

    /**
     * Test de autoloader de módulos
     */
    public function test_module_autoloader() 
    {
        // Verificar que el autoloader funciona
        $modules_dir = $this->getDevToolsPath() . '/modules';
        $this->assertTrue(is_dir($modules_dir), 'Directorio de módulos debe existir');
        
        // Verificar carga automática de módulos
        $expected_modules = [
            'DashboardModule',
            'SystemInfoModule', 
            'CacheModule',
            'AjaxTesterModule',
            'LogsModule',
            'PerformanceModule'
        ];
        
        foreach ($expected_modules as $module) {
            $module_file = $modules_dir . '/' . $module . '.php';
            $this->assertTrue(file_exists($module_file), "Archivo de módulo {$module}.php debe existir");
            
            // Verificar que la clase se puede cargar
            require_once $module_file;
            $this->assertTrue(class_exists($module), "Clase {$module} debe estar disponible");
        }
    }

    /**
     * Test de hooks de WordPress
     */
    public function test_wordpress_hooks() 
    {
        // Verificar que los hooks críticos están registrados
        $this->assertGreaterThan(0, has_action('admin_menu'), 'Hook admin_menu debe estar registrado');
        $this->assertGreaterThan(0, has_action('wp_ajax_dev_tools_ajax'), 'Hook AJAX debe estar registrado');
        $this->assertGreaterThan(0, has_action('admin_enqueue_scripts'), 'Hook admin_enqueue_scripts debe estar registrado');
    }

    /**
     * Test de capacidades y permisos
     */
    public function test_capabilities_and_permissions() 
    {
        // Crear usuario administrador para tests
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Verificar capacidades
        $this->assertTrue(current_user_can('manage_options'), 'Admin debe tener manage_options');
        
        // Verificar función de verificación de capacidades
        if (function_exists('dev_tools_current_user_can_access')) {
            $this->assertTrue(dev_tools_current_user_can_access(), 'Admin debe poder acceder a dev-tools');
        }
    }

    /**
     * Test de constantes y paths
     */
    public function test_constants_and_paths() 
    {
        // Verificar constantes críticas
        $required_constants = [
            'DEV_TOOLS_VERSION',
            'DEV_TOOLS_PATH',
            'DEV_TOOLS_URL'
        ];
        
        foreach ($required_constants as $constant) {
            $this->assertTrue(defined($constant), "Constante {$constant} debe estar definida");
            $this->assertNotEmpty(constant($constant), "Constante {$constant} no debe estar vacía");
        }
        
        // Verificar que los paths existen
        $this->assertTrue(file_exists(DEV_TOOLS_PATH), 'DEV_TOOLS_PATH debe apuntar a directorio existente');
        $this->assertTrue(is_dir(DEV_TOOLS_PATH), 'DEV_TOOLS_PATH debe ser un directorio');
    }

    /**
     * Test de funciones helper críticas
     */
    public function test_critical_helper_functions() 
    {
        $critical_functions = [
            'dev_tools_get_config',
            'dev_tools_get_admin_url',
            'dev_tools_is_dev_environment'
        ];
        
        foreach ($critical_functions as $function) {
            $this->assertTrue(function_exists($function), "Función {$function} debe existir");
        }
    }

    /**
     * Test de integridad de archivos core
     */
    public function test_core_files_integrity() 
    {
        $core_files = [
            'config.php',
            'loader.php', 
            'ajax-handler.php',
            'core/DevToolsModuleBase.php',
            'core/DevToolsModuleManager.php',
            'core/interfaces/DevToolsModuleInterface.php'
        ];
        
        $dev_tools_path = $this->getDevToolsPath();
        
        foreach ($core_files as $file) {
            $full_path = $dev_tools_path . '/' . $file;
            $this->assertTrue(file_exists($full_path), "Archivo core {$file} debe existir");
            $this->assertGreaterThan(0, filesize($full_path), "Archivo {$file} no debe estar vacío");
            
            // Verificar sintaxis PHP
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $syntax_check = shell_exec("php -l \"{$full_path}\" 2>&1");
                $this->assertStringContainsString('No syntax errors', $syntax_check, "Archivo {$file} debe tener sintaxis PHP válida");
            }
        }
    }

    /**
     * Test de configuración de entorno
     */
    public function test_environment_configuration() 
    {
        // Verificar detección de entorno de desarrollo
        if (function_exists('dev_tools_is_dev_environment')) {
            $is_dev = dev_tools_is_dev_environment();
            $this->assertIsBool($is_dev, 'dev_tools_is_dev_environment debe retornar boolean');
        }
        
        // Verificar configuración de debugging
        if (function_exists('dev_tools_is_debug_enabled')) {
            $debug_enabled = dev_tools_is_debug_enabled();
            $this->assertIsBool($debug_enabled, 'dev_tools_is_debug_enabled debe retornar boolean');
        }
    }

    /**
     * Test de cleanup y estado limpio
     */
    public function test_cleanup_and_clean_state() 
    {
        // Verificar que no hay data residual de tests anteriores
        $this->assertFalse(get_option('dev_tools_test_data'), 'No debe haber data residual de tests');
        
        // Test de función de limpieza si existe
        if (function_exists('dev_tools_clean_test_state')) {
            dev_tools_clean_test_state();
            $this->assertTrue(true, 'Función de limpieza ejecutada sin errores');
        }
    }
}
