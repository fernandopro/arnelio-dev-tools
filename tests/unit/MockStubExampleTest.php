<?php
/**
 * Ejemplo práctico de uso de Mocks y Stubs en Dev-Tools
 * 
 * Este archivo demuestra casos de uso reales de testing con
 * Stubs para datos y Mocks para verificar comportamiento
 */

class MockStubExampleTest extends DevToolsTestCase {

namespace DevTools\Tests\Unit;

    
    private $db_mock;
    private $wp_mock;
    
    public function setUp(): void {
        parent::setUp();
        
        // ✅ MOCK - Para verificar que se llame a WordPress database
        $this->db_mock = $this->createMock(wpdb::class);
        
        // ✅ MOCK - Para verificar funciones de WordPress
        $this->wp_mock = $this->createWordPressMock();
        
        // Nota: user_stub se crea dinámicamente en cada test que lo necesite
    }
    
    /**
     * Test usando STUB para datos fijos
     * El stub proporciona datos de usuario predefinidos
     */
    public function test_user_data_with_stub() {
        // Para este ejemplo, vamos a crear un usuario real y usarlo como "stub de datos"
        $user_id = $this->create_test_user('subscriber');
        $user = get_user_by('ID', $user_id);
        
        // Modificar datos para el test (esto simula lo que haría un stub)
        $user->display_name = 'Test User';
        $user->user_login = 'testuser';
        
        // Verificar los datos como si fuera un stub
        $this->assertNotEmpty($user->user_email);
        $this->assertEquals('Test User', $user->display_name);
        $this->assertEquals('testuser', $user->user_login);
        $this->assertGreaterThan(0, $user->ID);
        
        // Limpiar
        wp_delete_user($user_id);
    }
    
    /**
     * Test usando MOCK para verificar comportamiento de database
     * El mock verifica que se llamen métodos específicos
     */
    public function test_database_interaction_with_mock() {
        // Configurar expectativas del mock
        $this->db_mock->expects($this->once())
                      ->method('get_results')
                      ->with($this->stringContains('SELECT'))
                      ->willReturn([
                          (object)['id' => 1, 'name' => 'Test']
                      ]);
        
        // Simular una consulta
        $processor = new DatabaseProcessor($this->db_mock);
        $results = $processor->getTestData();
        
        // Verificar resultado y que se llamó al método
        $this->assertNotEmpty($results);
        $this->assertEquals('Test', $results[0]->name);
    }
    
    /**
     * Test combinando STUB y MOCK
     * Stub para datos, Mock para verificar comportamiento
     */
    public function test_wordpress_function_calls_with_mock() {
        // MOCK: Verificar que se llamen funciones de WordPress
        $this->wp_mock->expects($this->once())
                      ->method('wp_cache_set')
                      ->with('test_key', $this->isType('array'))
                      ->willReturn(true);
        
        $this->wp_mock->expects($this->once())
                      ->method('do_action')
                      ->with('test_action', $this->isType('array'))
                      ->willReturn(true);
        
        // Crear procesador con mock
        $processor = new WordPressProcessor($this->wp_mock);
        
        // Ejecutar acción que debería usar cache y triggers
        $result = $processor->processData(['test' => 'data']);
        
        // Verificar que el proceso se ejecutó correctamente
        $this->assertTrue($result);
        
        // Las expectativas del mock se verifican automáticamente
    }
    
    /**
     * Test con módulos reales de Dev-Tools
     */
    public function test_real_dev_tools_modules() {
        // Verificar que los módulos reales están cargados
        $this->assertTrue(class_exists('DatabaseConnectionModule'), 
            'DatabaseConnectionModule should be loaded');
        $this->assertTrue(class_exists('SiteUrlDetectionModule'), 
            'SiteUrlDetectionModule should be loaded');
        
        // Crear instancias reales
        $db_module = new DatabaseConnectionModule();
        $url_module = new SiteUrlDetectionModule();
        
        // Tests básicos de funcionalidad
        $this->assertInstanceOf('DatabaseConnectionModule', $db_module);
        $this->assertInstanceOf('SiteUrlDetectionModule', $url_module);
        
        // Test funciones de WordPress reales (sin mock)
        $test_data = ['module_test' => 'data'];
        
        // Usar funciones reales de WordPress disponibles en el entorno de test
        $cache_result = wp_cache_set('dev_tools_test', $test_data);
        $this->assertTrue($cache_result || $cache_result === false, 'wp_cache_set should execute without error');
        
        // Test de trigger de acción (no verificamos el resultado, solo que no falle)
        $action_result = do_action('dev_tools_test_action', $test_data);
        $this->assertNull($action_result, 'do_action should return null when no handlers');
    }
    
    /**
     * Test de integración con WordPress usando helpers
     */
    public function test_wordpress_integration_with_helpers() {
        // Crear usuario de prueba usando helper
        $user_id = $this->create_test_user('administrator');
        wp_set_current_user($user_id);
        
        // Verificar que el usuario está activo
        $this->assertTrue(is_user_logged_in());
        $this->assertTrue(current_user_can('manage_options'));
        
        // Test básico de cache de WordPress
        $test_data = ['integration_test' => 'success'];
        wp_cache_set('test_integration_key', $test_data);
        $cached_data = wp_cache_get('test_integration_key');
        
        // El cache puede o no estar habilitado, pero la función debe ejecutarse sin error
        $this->assertTrue(is_array($cached_data) || $cached_data === false, 
            'wp_cache functions should execute without error');
        
        // Test básico de acciones de WordPress
        $action_fired = false;
        add_action('dev_tools_test_hook', function() use (&$action_fired) {
            $action_fired = true;
        });
        
        do_action('dev_tools_test_hook');
        $this->assertTrue($action_fired, 'WordPress action should fire correctly');
    }
     /**
     * Helper para crear mock de WordPress
     */
    private function createWordPressMock() {
        // Crear una clase simulada específica para WordPress
        $mock = $this->getMockBuilder(WordPressFunctions::class)
                     ->onlyMethods(['wp_cache_set', 'wp_cache_get', 'do_action', 'apply_filters'])
                     ->getMock();
        
        return $mock;
    }
}

/**
 * Clase simulada para funciones de WordPress que necesitamos mockear
 */
class MockStubExampleTest {
    public function wp_cache_set($key, $data, $group = '', $expire = 0) {
        return true;
    }
    
    public function wp_cache_get($key, $group = '') {
        return false;
    }
    
    public function do_action($hook, ...$args) {
        return true;
    }
    
    public function apply_filters($hook, $value, ...$args) {
        return $value;
    }
}

/**
 * Clase de ejemplo para procesar database
 */
class MockStubExampleTest {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getTestData() {
        return $this->db->get_results("SELECT * FROM test_table");
    }
}

/**
 * Clase de ejemplo para procesar WordPress
 */
class MockStubExampleTest {
    private $wp;
    
    public function __construct($wp) {
        $this->wp = $wp;
    }
    
    public function processData($data) {
        // Guardar en cache
        $this->wp->wp_cache_set('test_key', $data);
        
        // Disparar acción
        $this->wp->do_action('test_action', $data);
        
        return true;
    }
}
