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
        $this->assertTrue(class_exists('DevToolsConfig'));
        $this->assertTrue(class_exists('DevToolsModuleBase'));
        $this->assertTrue(function_exists('dev_tools_get_config'));
    }
    
    /**
     * Test de detección del plugin host
     */
    public function test_plugin_host_detected() {
        $config = dev_tools_get_config();
        $this->assertNotNull($config);
        
        $plugin_info = $config->get('plugin_info');
        $this->assertNotEmpty($plugin_info);
        $this->assertArrayHasKey('name', $plugin_info);
        $this->assertArrayHasKey('file', $plugin_info);
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
        
        // Verificar directorio de logs
        $logs_dir = dirname(dirname(__DIR__)) . '/logs';
        $this->assertTrue(is_dir($logs_dir));
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
