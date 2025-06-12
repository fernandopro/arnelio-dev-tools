<?php
/**
 * Ejemplo práctico de uso de Mocks y Stubs en Dev-Tools
 * 
 * Este archivo demuestra casos de uso reales de testing con
 * Stubs para datos y Mocks para verificar comportamiento
 */

class MockStubExampleTest extends DevToolsTestCase {
    
    private $db_mock;
    private $user_stub;
    private $wp_mock;
    
    public function setUp(): void {
        parent::setUp();
        
        // ✅ MOCK - Para verificar que se llame a WordPress database
        $this->db_mock = $this->createMock(wpdb::class);
        
        // ✅ STUB - Para proporcionar datos fijos de usuario
        $this->user_stub = $this->createUserStub();
        
        // ✅ MOCK - Para verificar funciones de WordPress
        $this->wp_mock = $this->createWordPressMock();
    }
    
    /**
     * Test usando STUB para datos fijos
     * El stub proporciona datos de usuario predefinidos
     */
    public function test_user_data_with_stub() {
        // El stub usa propiedades reales de WP_User
        $this->assertEquals('test@example.com', $this->user_stub->user_email);
        $this->assertEquals('Test User', $this->user_stub->display_name);
        $this->assertEquals('testuser', $this->user_stub->user_login);
        $this->assertEquals(123, $this->user_stub->ID);
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
                      ->with('test_key', $this->isType('array'));
        
        $this->wp_mock->expects($this->once())
                      ->method('do_action')
                      ->with('test_action', $this->isType('array'));
        
        // Crear procesador con mock
        $processor = new WordPressProcessor($this->wp_mock);
        
        // Ejecutar acción que debería usar cache y triggers
        $processor->processData(['test' => 'data']);
        
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
        
        // Simular petición AJAX de WordPress
        $this->simulate_ajax_request('test_action', ['test' => 'data']);
        
        // Verificar que la petición se procesó
        $response = $this->get_ajax_response();
        $this->assertNotEmpty($response);
    }
    
    /**
     * Helper para crear stub de usuario - USANDO WP_User que SÍ está disponible
     */
    private function createUserStub() {
        // ✅ CORRECTO: WP_User SÍ está disponible en tests
        $stub = $this->createStub(WP_User::class);
        
        // Configurar propiedades del stub (no métodos que no existen)
        $stub->ID = 123;
        $stub->user_email = 'test@example.com';
        $stub->display_name = 'Test User';
        $stub->user_login = 'testuser';
        
        return $stub;
    }
    
    /**
     * Helper para crear mock de WordPress
     */
    private function createWordPressMock() {
        // Crear un mock genérico que simule funciones de WordPress
        $mock = $this->createMock(stdClass::class);
        
        // Configurar métodos que esperamos
        $mock->method('wp_cache_set')->willReturn(true);
        $mock->method('wp_cache_get')->willReturn(false);
        $mock->method('do_action')->willReturn(true);
        $mock->method('apply_filters')->willReturn('filtered_value');
        
        return $mock;
    }
}

/**
 * Clase de ejemplo para procesar database
 */
class DatabaseProcessor {
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
class WordPressProcessor {
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
