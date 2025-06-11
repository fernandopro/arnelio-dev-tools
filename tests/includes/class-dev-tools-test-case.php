<?php
/**
 * Dev-Tools Test Case Base Class
 * 
 * Clase base para todos los tests de Dev-Tools
 * Extiende WP_UnitTestCase para compatibilidad con WordPress
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

class DevToolsTestCase extends WP_UnitTestCase {
    
    protected $dev_tools_loader;
    protected $test_data_factory;
    
    /**
     * Setup común para todos los tests
     */
    public function setUp(): void {
        parent::setUp();
        
        // Inicializar Dev-Tools si no está ya inicializado
        if (class_exists('DevToolsLoader')) {
            $this->dev_tools_loader = DevToolsLoader::getInstance();
        }
        
        // Inicializar factory de datos de prueba
        $this->test_data_factory = new DevToolsTestDataFactory();
        
        // Configurar entorno de testing
        $this->setup_test_environment();
    }
    
    /**
     * Cleanup después de cada test
     */
    public function tearDown(): void {
        // Limpiar cache de Dev-Tools
        $this->clear_dev_tools_cache();
        
        // Limpiar datos de prueba
        $this->test_data_factory->cleanup();
        
        parent::tearDown();
    }
    
    /**
     * Configurar entorno de testing
     */
    protected function setup_test_environment() {
        // Configurar constantes de testing si no existen
        if (!defined('DEV_TOOLS_TESTING')) {
            define('DEV_TOOLS_TESTING', true);
        }
        
        // Configurar variables $_SERVER para tests
        $_SERVER['HTTP_HOST'] = 'example.org';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = '80';
        
        // Configurar usuario de prueba con permisos de administrador
        $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'test_admin',
            'user_email' => 'admin@example.org'
        ]);
    }
    
    /**
     * Limpiar cache de Dev-Tools
     */
    protected function clear_dev_tools_cache() {
        // Limpiar transients de WordPress
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dev_tools_%'");
        
        // Limpiar object cache si está disponible
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Assert que un módulo existe y está cargado
     */
    protected function assertModuleLoaded($module_name) {
        $this->assertTrue(
            class_exists($module_name),
            "Module {$module_name} should be loaded"
        );
        
        if ($this->dev_tools_loader) {
            $module = $this->dev_tools_loader->get_module($module_name);
            $this->assertNotNull(
                $module,
                "Module {$module_name} should be registered in DevToolsLoader"
            );
        }
    }
    
    /**
     * Assert que una conexión de base de datos funciona
     */
    protected function assertDatabaseConnectionWorks($connection) {
        $this->assertNotNull($connection, 'Database connection should not be null');
        
        if ($connection && method_exists($connection, 'query')) {
            // Test simple query
            try {
                $result = $connection->query('SELECT 1 as test');
                $this->assertNotNull($result, 'Simple query should work');
            } catch (Exception $e) {
                $this->fail('Database connection test failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Assert que una URL es válida
     */
    protected function assertValidUrl($url) {
        $this->assertNotEmpty($url, 'URL should not be empty');
        $this->assertTrue(
            filter_var($url, FILTER_VALIDATE_URL) !== false,
            "'{$url}' should be a valid URL"
        );
    }
    
    /**
     * Assert que un resultado AJAX es válido
     */
    protected function assertValidAjaxResponse($response) {
        $this->assertIsArray($response, 'AJAX response should be an array');
        $this->assertArrayHasKey('success', $response, 'AJAX response should have success key');
        
        if ($response['success']) {
            $this->assertArrayHasKey('data', $response, 'Successful AJAX response should have data');
        } else {
            $this->assertArrayHasKey('data', $response, 'Failed AJAX response should have error data');
            $this->assertArrayHasKey('message', $response['data'], 'Error response should have message');
        }
    }
    
    /**
     * Mock de $_POST para tests AJAX
     */
    protected function mock_ajax_request($command, $data = [], $nonce = null) {
        if (!$nonce) {
            $nonce = wp_create_nonce('dev_tools_nonce');
        }
        
        $_POST = [
            'action' => 'dev_tools_ajax',
            'nonce' => $nonce,
            'command' => $command,
            'data' => $data
        ];
        
        $_REQUEST = $_POST;
    }
    
    /**
     * Simular usuario logueado con capacidades específicas
     */
    protected function act_as_user_with_capability($capability = 'manage_options') {
        $user_id = $this->factory->user->create([
            'role' => 'administrator'
        ]);
        
        wp_set_current_user($user_id);
        
        // Verificar que el usuario tiene la capacidad
        $this->assertTrue(
            current_user_can($capability),
            "Test user should have {$capability} capability"
        );
        
        return $user_id;
    }
    
    /**
     * Simular entorno Local by WP Engine
     */
    protected function simulate_local_wp_engine_environment() {
        // Simular path típico de Local by WP Engine
        $_SERVER['SCRIPT_FILENAME'] = '/Users/testuser/Local Sites/test-site/app/public/wp-content/plugins/test-plugin/test.php';
        $_SERVER['DOCUMENT_ROOT'] = '/Users/testuser/Local Sites/test-site/app/public';
        
        // Simular socket MySQL de Local by WP Engine
        $socket_path = '/Users/testuser/Library/Application Support/Local/run/testsite/mysql/mysqld.sock';
        
        // Mock del DB_HOST si es necesario
        if (!defined('DB_HOST')) {
            define('DB_HOST', 'localhost');
        }
    }
    
    /**
     * Crear archivo temporal para tests
     */
    protected function create_temp_file($content = '', $extension = '.tmp') {
        $temp_file = tempnam(sys_get_temp_dir(), 'dev_tools_test') . $extension;
        file_put_contents($temp_file, $content);
        
        // Registrar para cleanup
        $this->temp_files[] = $temp_file;
        
        return $temp_file;
    }
    
    /**
     * Cleanup de archivos temporales
     */
    private $temp_files = [];
    
    protected function cleanup_temp_files() {
        foreach ($this->temp_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->temp_files = [];
    }
    
    /**
     * Override de tearDown para cleanup automático
     */
    public function tearDown(): void {
        $this->cleanup_temp_files();
        parent::tearDown();
    }
    
    /**
     * Helper para capturar output de funciones
     */
    protected function get_output_from_callable($callable, ...$args) {
        ob_start();
        $result = call_user_func_array($callable, $args);
        $output = ob_get_clean();
        
        return ['result' => $result, 'output' => $output];
    }
    
    /**
     * Assert que no hay errores PHP en los logs
     */
    protected function assertNoPhpErrors() {
        $errors = error_get_last();
        if ($errors && $errors['type'] === E_ERROR) {
            $this->fail('PHP Error detected: ' . $errors['message']);
        }
    }
    
    /**
     * Helper para tests de performance
     */
    protected function measure_execution_time($callable, ...$args) {
        $start_time = microtime(true);
        $result = call_user_func_array($callable, $args);
        $end_time = microtime(true);
        
        return [
            'result' => $result,
            'execution_time' => ($end_time - $start_time) * 1000 // en millisegundos
        ];
    }
    
    /**
     * Assert que la ejecución es rápida (menos de X millisegundos)
     */
    protected function assertExecutionTimeUnder($callable, $max_time_ms, ...$args) {
        $measurement = $this->measure_execution_time($callable, ...$args);
        
        $this->assertLessThan(
            $max_time_ms,
            $measurement['execution_time'],
            "Execution should be under {$max_time_ms}ms but took {$measurement['execution_time']}ms"
        );
        
        return $measurement['result'];
    }
}
