<?php
/**
 * Test básico para verificar el funcionamiento del sistema de testing
 * 
 * @package DevTools\Tests\Unit
 * @since Arquitectura 3.0
 */

class DevToolsBasicTest extends DevToolsTestCase {
    
    /**
     * Test básico de WordPress
     */
    public function test_wordpress_is_loaded() {
        $this->assertTrue(function_exists('wp_head'));
        $this->assertTrue(defined('ABSPATH'));
        $this->assertTrue(defined('WP_CONTENT_DIR'));
    }
    
    /**
     * Test de configuración de Dev-Tools
     */
    public function test_dev_tools_config_loaded() {
        // Verificar que las clases principales están disponibles
        $this->assertTrue(class_exists('DevToolsModuleBase'), 'DevToolsModuleBase class should be loaded');
        
        // Verificar que existe el archivo de configuración
        $config_file = dirname(dirname(__DIR__)) . '/config.php';
        $this->assertTrue(file_exists($config_file), 'Config file should exist');
        
        // Verificar que existe el loader principal
        $loader_file = dirname(dirname(__DIR__)) . '/loader.php';
        $this->assertTrue(file_exists($loader_file), 'Loader file should exist');
    }
    
    /**
     * Test de detección del plugin host
     */
    public function test_plugin_host_detected() {
        // Verificar que el directorio del plugin host existe
        $plugin_dir = dirname(dirname(dirname(__DIR__)));
        $this->assertTrue(is_dir($plugin_dir), 'Plugin directory should exist');
        
        // Verificar que existe un archivo principal del plugin
        $possible_files = ['tarokina-pro.php', 'tarokina-2025.php'];
        $plugin_file_found = false;
        
        foreach ($possible_files as $file) {
            if (file_exists($plugin_dir . '/' . $file)) {
                $plugin_file_found = true;
                break;
            }
        }
        
        $this->assertTrue($plugin_file_found, 'Plugin main file should exist');
    }
    
    /**
     * Test de base de datos para testing
     */
    public function test_database_connection() {
        global $wpdb;
        
        $this->assertNotNull($wpdb);
        $this->assertTrue($wpdb->check_connection());
        
        // Verificar que estamos usando la base de datos de testing
        $this->assertEquals('local', DB_NAME);
        $this->assertStringStartsWith('wp_test_', $wpdb->prefix);
    }
    
    /**
     * Test de carga de módulos
     */
    public function test_modules_system() {
        $this->assertTrue(class_exists('DevToolsModuleBase'));
        
        // Verificar que existe el directorio de módulos
        $modules_dir = dirname(dirname(__DIR__)) . '/modules';
        $this->assertTrue(is_dir($modules_dir));
    }
    
    /**
     * Test de sistema AJAX
     */
    public function test_ajax_handler() {
        $this->assertTrue(class_exists('DevToolsAjaxHandler'));
        
        // Verificar que el handler AJAX está disponible
        $ajax_file = dirname(dirname(__DIR__)) . '/ajax-handler.php';
        $this->assertTrue(file_exists($ajax_file));
    }
    
    /**
     * Test de logging y debug
     */
    public function test_logging_system() {
        $this->assertTrue(defined('WP_DEBUG'));
        
        // Verificar que el directorio de logs existe o se puede crear
        $logs_dir = dirname(dirname(__DIR__)) . '/logs';
        if (!is_dir($logs_dir)) {
            // Intentar crear el directorio
            wp_mkdir_p($logs_dir);
        }
        $this->assertTrue(is_dir($logs_dir), 'Directorio de logs debe existir o poder crearse');
    }
    
    /**
     * Test de constantes específicas de testing
     */
    public function test_testing_constants() {
        $this->assertTrue(defined('WP_TESTS_INDIVIDUAL'));
        $this->assertTrue(defined('PHPUNIT_RUNNING'));
        $this->assertTrue(WP_TESTS_INDIVIDUAL);
        $this->assertTrue(PHPUNIT_RUNNING);
    }
}
