<?php
/**
 * Clase base para tests de Dev-Tools
 * 
 * Extiende WP_UnitTestCase siguiendo las mejores prácticas de WordPress Core
 * @see https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

// Determinar qué clase base usar
if (class_exists('WP_UnitTestCase')) {
    class DevToolsTestCaseBase extends WP_UnitTestCase {}
} else {
    // Fallback si WordPress Test Suite no está disponible
    class DevToolsTestCaseBase extends PHPUnit\Framework\TestCase {}
}

class DevToolsTestCase extends DevToolsTestCaseBase {

    /**
     * Configuración inicial antes de cada test
     * 
     * @since 3.0.0
     */
    public function setUp(): void {
        if (method_exists(parent::class, 'setUp')) {
            parent::setUp();
        }
        
        // Solo si WordPress está disponible
        if (function_exists('delete_option')) {
            // Limpiar opciones específicas de dev-tools
            delete_option( 'dev_tools_settings' );
            delete_option( 'dev_tools_cache' );
            delete_transient( 'dev_tools_last_check' );
        }
        
        // Configurar usuario admin para tests (solo si WP está disponible)
        if (method_exists($this, 'setupAdminUser')) {
            $this->setupAdminUser();
        }
        
        // Configurar entorno limpio
        $this->setupCleanEnvironment();
    }

    /**
     * Limpieza después de cada test
     * 
     * @since 3.0.0
     */
    public function tearDown(): void {
        // Limpiar configuraciones específicas
        $this->cleanupDevToolsData();
        
        if (method_exists(parent::class, 'tearDown')) {
            parent::tearDown();
        }
    }

    /**
     * Configurar usuario administrador para tests
     * 
     * @since 3.0.0
     */
    protected function setupAdminUser() {
        // Solo si WordPress y factory están disponibles
        if (!function_exists('wp_set_current_user') || !isset($this->factory)) {
            return;
        }
        
        // Crear usuario admin si no existe
        $admin_user = $this->factory->user->create( [
            'role' => 'administrator',
            'user_login' => 'test_admin',
            'user_email' => 'test@localhost'
        ] );
        
        // Establecer como usuario actual
        wp_set_current_user( $admin_user );
        
        // Agregar capacidades específicas de dev-tools
        $user = wp_get_current_user();
        $user->add_cap( 'manage_dev_tools' );
        $user->add_cap( 'edit_dev_tools' );
    }

    /**
     * Configurar entorno limpio para tests
     * 
     * @since 3.0.0
     */
    protected function setupCleanEnvironment() {
        // Limpiar hooks que puedan interferir
        remove_all_actions( 'dev_tools_init' );
        remove_all_actions( 'dev_tools_loaded' );
        
        // Configurar constantes de test si no están definidas
        if ( ! defined( 'DEV_TOOLS_TEST_MODE' ) ) {
            define( 'DEV_TOOLS_TEST_MODE', true );
        }
    }

    /**
     * Limpiar datos específicos de dev-tools
     * 
     * @since 3.0.0
     */
    protected function cleanupDevToolsData() {
        global $wpdb;
        
        // Limpiar opciones
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dev_tools_%'" );
        
        // Limpiar transients
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dev_tools_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dev_tools_%'" );
        
        // Limpiar meta
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'dev_tools_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'dev_tools_%'" );
    }

    /**
     * Helper para crear configuración de módulo de test
     * 
     * @param string $module_name Nombre del módulo
     * @param array $config Configuración del módulo
     * @return array
     * 
     * @since 3.0.0
     */
    protected function createTestModuleConfig( $module_name, $config = [] ) {
        $default_config = [
            'name' => $module_name,
            'version' => '1.0.0',
            'description' => 'Test module for ' . $module_name,
            'enabled' => true,
            'settings' => []
        ];
        
        return array_merge( $default_config, $config );
    }

    /**
     * Helper para crear archivo temporal de test
     * 
     * @param string $content Contenido del archivo
     * @param string $extension Extensión del archivo (default: 'php')
     * @return string Ruta del archivo temporal
     * 
     * @since 3.0.0
     */
    protected function createTempFile( $content, $extension = 'php' ) {
        $temp_file = tempnam( sys_get_temp_dir(), 'dev_tools_test_' ) . '.' . $extension;
        file_put_contents( $temp_file, $content );
        
        // Registrar para limpieza automática
        $this->temp_files[] = $temp_file;
        
        return $temp_file;
    }

    /**
     * Helper para simular request AJAX
     * 
     * @param string $action Acción AJAX
     * @param array $data Datos del request
     * @param bool $authenticated Si requiere autenticación
     * @return array Respuesta simulada
     * 
     * @since 3.0.0
     */
    protected function simulateAjaxRequest( $action, $data = [], $authenticated = true ) {
        // Configurar $_POST para AJAX
        $_POST['action'] = $action;
        $_POST = array_merge( $_POST, $data );
        
        if ( $authenticated ) {
            $_POST['_wpnonce'] = wp_create_nonce( 'dev_tools_nonce' );
        }
        
        // Configurar $_REQUEST (WordPress lo usa para AJAX)
        $_REQUEST = $_POST;
        
        try {
            // Capturar salida
            ob_start();
            
            if ( $authenticated ) {
                do_action( 'wp_ajax_' . $action );
            } else {
                do_action( 'wp_ajax_nopriv_' . $action );
            }
            
            $response = ob_get_clean();
            
            return [
                'success' => true,
                'data' => $response
            ];
            
        } catch ( Exception $e ) {
            ob_end_clean();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assert que un hook específico está registrado
     * 
     * @param string $hook_name Nombre del hook
     * @param string $function_name Nombre de la función (opcional)
     * @param int $priority Prioridad esperada (opcional)
     * 
     * @since 3.0.0
     */
    protected function assertHookRegistered( $hook_name, $function_name = null, $priority = null ) {
        $this->assertTrue( has_action( $hook_name ), "Hook '{$hook_name}' no está registrado" );
        
        if ( $function_name ) {
            $this->assertNotFalse( 
                has_action( $hook_name, $function_name ), 
                "Función '{$function_name}' no está registrada en hook '{$hook_name}'" 
            );
        }
        
        if ( $priority !== null && $function_name ) {
            $registered_priority = has_action( $hook_name, $function_name );
            $this->assertEquals( 
                $priority, 
                $registered_priority, 
                "Prioridad esperada {$priority}, encontrada {$registered_priority}" 
            );
        }
    }

    /**
     * Assert que una clase implementa una interfaz específica
     * 
     * @param string $class_name Nombre de la clase
     * @param string $interface_name Nombre de la interfaz
     * 
     * @since 3.0.0
     */
    protected function assertImplementsInterface( $class_name, $interface_name ) {
        $this->assertTrue( 
            in_array( $interface_name, class_implements( $class_name ) ),
            "Clase '{$class_name}' no implementa interfaz '{$interface_name}'"
        );
    }

    /**
     * Array para archivos temporales (limpieza automática)
     * 
     * @var array
     * @since 3.0.0
     */
    protected $temp_files = [];

    /**
     * Limpiar archivos temporales
     * 
     * @since 3.0.0
     */
    public function __destruct() {
        foreach ( $this->temp_files as $file ) {
            if ( file_exists( $file ) ) {
                unlink( $file );
            }
        }
    }
}
        
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
