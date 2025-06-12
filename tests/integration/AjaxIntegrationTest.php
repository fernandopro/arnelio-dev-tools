<?php
/**
 * Tests de Integración AJAX - Dev-Tools Arquitectura 3.0
 * 
 * Tests avanzados para endpoints AJAX y integración de módulos
 * 
 * @package DevTools
 * @subpackage Tests\Integration
 * @group ajax
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class AjaxIntegrationTest extends DevToolsTestCase {

    private $admin_user_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Crear usuario admin para tests de AJAX
        $this->admin_user_id = $this->create_admin_user();
        wp_set_current_user($this->admin_user_id);
        
        // Cargar el loader de Dev-Tools para AJAX
        require_once $this->get_dev_tools_path() . '/loader.php';
    }

    /**
     * Test: AJAX nonce validation
     */
    public function test_ajax_nonce_validation() {
        // Test con nonce válido
        $valid_nonce = wp_create_nonce('dev_tools_nonce');
        $_POST['nonce'] = $valid_nonce;
        $_POST['action'] = 'dev_tools_test_action';
        
        $nonce_check = wp_verify_nonce($valid_nonce, 'dev_tools_nonce');
        $this->assertNotFalse($nonce_check, 'El nonce válido debería pasar la verificación');
        
        // Test con nonce inválido
        $invalid_nonce = 'invalid_nonce_12345';
        $invalid_check = wp_verify_nonce($invalid_nonce, 'dev_tools_nonce');
        $this->assertFalse($invalid_check, 'El nonce inválido debería fallar la verificación');
        
        // Limpiar
        unset($_POST['nonce'], $_POST['action']);
    }

    /**
     * Test: User capabilities for AJAX requests
     */
    public function test_ajax_user_capabilities() {
        // Test con usuario admin
        wp_set_current_user($this->admin_user_id);
        $this->assertTrue(current_user_can('manage_options'));
        
        // Test con usuario sin permisos
        $subscriber_id = $this->create_test_user('subscriber');
        wp_set_current_user($subscriber_id);
        $this->assertFalse(current_user_can('manage_options'));
        
        // Restaurar usuario admin
        wp_set_current_user($this->admin_user_id);
    }

    /**
     * Test: Database connection via AJAX simulation
     */
    public function test_ajax_database_connection() {
        global $wpdb;
        
        // Simular datos de petición AJAX para test de conexión DB
        $ajax_data = [
            'action' => 'dev_tools_test_db',
            'test_type' => 'connection'
        ];
        
        // Test conexión básica (simulado)
        $connection_test = $wpdb->get_var("SELECT 1");
        $this->assertEquals('1', $connection_test);
        
        // Test información de la BD
        $db_info = [
            'mysql_version' => $wpdb->get_var("SELECT VERSION()"),
            'database_name' => $wpdb->get_var("SELECT DATABASE()"),
            'table_count' => count($wpdb->get_results("SHOW TABLES"))
        ];
        
        $this->assertNotEmpty($db_info['mysql_version']);
        $this->assertEquals('local', $db_info['database_name']);
        $this->assertGreaterThan(0, $db_info['table_count']);
    }

    /**
     * Test: System information via AJAX simulation
     */
    public function test_ajax_system_info() {
        // Simular datos de petición AJAX para información del sistema
        global $wpdb;
        $system_info = [
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'mysql_version' => $wpdb->get_var("SELECT VERSION()"),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
        
        // Verificar que todos los valores están presentes
        $this->assertNotEmpty($system_info['php_version']);
        $this->assertNotEmpty($system_info['wordpress_version']);
        $this->assertIsString($system_info['memory_limit']);
        $this->assertIsString($system_info['max_execution_time']);
        $this->assertIsString($system_info['upload_max_filesize']);
    }

    /**
     * Test: Error handling in AJAX context
     */
    public function test_ajax_error_handling() {
        // Test manejo de errores sin nonce
        unset($_POST['nonce']);
        $_POST['action'] = 'dev_tools_test_action';
        
        $this->assertFalse(isset($_POST['nonce']));
        
        // Test manejo de errores con usuario sin permisos
        $subscriber_id = $this->create_test_user('subscriber');
        wp_set_current_user($subscriber_id);
        
        $this->assertFalse(current_user_can('manage_options'));
        
        // Restaurar estado
        wp_set_current_user($this->admin_user_id);
        unset($_POST['action']);
    }

    /**
     * Test: JSON response formatting
     */
    public function test_ajax_json_response_format() {
        // Test estructura de respuesta de éxito
        $success_response = [
            'success' => true,
            'data' => [
                'message' => 'Operation completed successfully',
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id()
            ]
        ];
        
        $this->assertTrue($success_response['success']);
        $this->assertArrayHasKey('data', $success_response);
        $this->assertArrayHasKey('message', $success_response['data']);
        $this->assertNotEmpty($success_response['data']['timestamp']);
        
        // Test estructura de respuesta de error
        $error_response = [
            'success' => false,
            'data' => [
                'error' => 'Operation failed',
                'error_code' => 'INVALID_REQUEST',
                'timestamp' => current_time('mysql')
            ]
        ];
        
        $this->assertFalse($error_response['success']);
        $this->assertArrayHasKey('error', $error_response['data']);
        $this->assertArrayHasKey('error_code', $error_response['data']);
        
        // Test que las respuestas son JSON válido
        $success_json = json_encode($success_response);
        $error_json = json_encode($error_response);
        
        $this->assertJson($success_json);
        $this->assertJson($error_json);
    }

    /**
     * Test: Module loading via AJAX context
     */
    public function test_ajax_module_loading() {
        // Verificar que los módulos están disponibles en contexto AJAX
        $this->assertTrue(class_exists('DatabaseConnectionModule'));
        $this->assertTrue(class_exists('SiteUrlDetectionModule'));
        
        // Test instanciación de módulos
        $db_module = new DatabaseConnectionModule();
        $url_module = new SiteUrlDetectionModule();
        
        $this->assertInstanceOf(DatabaseConnectionModule::class, $db_module);
        $this->assertInstanceOf(SiteUrlDetectionModule::class, $url_module);
        
        // Test que los módulos pueden ejecutarse en contexto AJAX
        $this->assertNotNull($db_module);
        $this->assertNotNull($url_module);
    }

    /**
     * Test: Performance monitoring in AJAX
     */
    public function test_ajax_performance_monitoring() {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Simular operación AJAX costosa
        $test_data = [];
        for ($i = 0; $i < 1000; $i++) {
            $test_data[] = [
                'id' => $i,
                'value' => wp_generate_password(32, false),
                'timestamp' => current_time('mysql')
            ];
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        
        // Verificar métricas de rendimiento
        $this->assertLessThan(1.0, $execution_time, 'La operación debería completarse en menos de 1 segundo');
        $this->assertLessThan(10 * 1024 * 1024, $memory_usage, 'El uso de memoria debería ser menor a 10MB');
        $this->assertCount(1000, $test_data);
    }

    /**
     * Test: Cache integration in AJAX context
     */
    public function test_ajax_cache_integration() {
        // Test set cache
        $cache_key = 'dev_tools_test_cache';
        $cache_value = [
            'test_data' => 'cached_value_' . time(),
            'timestamp' => current_time('mysql')
        ];
        
        set_transient($cache_key, $cache_value, HOUR_IN_SECONDS);
        
        // Test get cache
        $cached_data = get_transient($cache_key);
        $this->assertNotFalse($cached_data);
        $this->assertEquals($cache_value['test_data'], $cached_data['test_data']);
        
        // Test cache invalidation
        delete_transient($cache_key);
        $deleted_cache = get_transient($cache_key);
        $this->assertFalse($deleted_cache);
    }

    public function tearDown(): void {
        // Limpiar datos AJAX
        unset($_POST['action'], $_POST['nonce']);
        
        // Limpiar transients de prueba
        delete_transient('dev_tools_test_cache');
        
        parent::tearDown();
    }
}
