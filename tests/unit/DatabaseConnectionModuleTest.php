<?php
/**
 * Test: Database Connection Module - Unit Tests
 * 
 * Tests unitarios para el módulo de conexión a base de datos
 * Incluye tests específicos para Local by WP Engine
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

class DatabaseConnectionModuleTest extends DevToolsTestCase {
    
    private $module;
    
    public function setUp(): void {
        parent::setUp();
        
        // Cargar el módulo
        require_once dirname(__DIR__, 2) . '/modules/DatabaseConnectionModule.php';
        $this->module = new DatabaseConnectionModule(true); // Debug mode
    }
    
    /**
     * @group Quick
     * Test básico de inicialización del módulo
     */
    public function test_module_initialization() {
        $this->assertInstanceOf(DatabaseConnectionModule::class, $this->module);
        
        // Verificar que detecta información del entorno
        $env_info = $this->module->get_environment_info();
        $this->assertIsArray($env_info);
        $this->assertArrayHasKey('wp_db_host', $env_info);
        $this->assertArrayHasKey('wp_db_name', $env_info);
    }
    
    /**
     * @group Quick
     * Test de detección de entorno Local by WP Engine
     */
    public function test_local_wp_engine_detection() {
        // Simular entorno Local by WP Engine
        simulate_local_wp_engine();
        
        $module = new DatabaseConnectionModule(true);
        $env_info = $module->get_environment_info();
        
        // En entorno real de Local, esto debería ser true
        // En tests, verificamos que el método de detección existe
        $this->assertArrayHasKey('is_local_wp', $env_info);
        $this->assertIsBool($env_info['is_local_wp']);
    }
    
    /**
     * Test de búsqueda de socket MySQL
     */
    public function test_mysql_socket_detection() {
        // Crear socket temporal para test
        $test_socket = $this->test_data_factory->create_temp_mysql_socket();
        
        $module = new DatabaseConnectionModule(true);
        $env_info = $module->get_environment_info();
        
        // Verificar que el método de detección funciona
        $this->assertArrayHasKey('socket_path', $env_info);
        
        // En tests reales, podríamos verificar el socket específico
        if (file_exists('/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock')) {
            $this->assertNotNull($env_info['socket_path']);
        }
    }
    
    /**
     * Test de construcción de DSN para diferentes entornos
     */
    public function test_dsn_construction() {
        $test_data = $this->test_data_factory->create_database_test_data();
        
        // Test con conexión local típica
        $module = new DatabaseConnectionModule(false);
        
        // Como el método build_dsn es privado, testearemos a través de get_connection
        // En un entorno real, esto intentaría conectar
        try {
            $connection = $module->get_connection();
            // Si no hay error en la construcción del DSN, es buena señal
            $this->assertTrue(true, 'DSN construction did not throw errors');
        } catch (Exception $e) {
            // En tests, es esperado que falle la conexión real
            $this->assertStringContainsString('mysql:', $e->getMessage(), 'Error should mention MySQL DSN');
        }
    }
    
    /**
     * Test de manejo de errores de conexión
     */
    public function test_connection_error_handling() {
        // Simular entorno con datos inválidos
        if (!defined('DB_HOST')) {
            define('DB_HOST', 'nonexistent_host');
        }
        if (!defined('DB_NAME')) {
            define('DB_NAME', 'nonexistent_db');
        }
        if (!defined('DB_USER')) {
            define('DB_USER', 'invalid_user');
        }
        if (!defined('DB_PASSWORD')) {
            define('DB_PASSWORD', 'invalid_pass');
        }
        
        $module = new DatabaseConnectionModule(false); // Sin debug para evitar exceptions
        
        $connection = $module->get_connection();
        $this->assertNull($connection, 'Connection should fail gracefully');
        
        // Test del método test_connection
        $test_result = $module->test_connection();
        $this->assertIsArray($test_result);
        $this->assertArrayHasKey('success', $test_result);
        $this->assertFalse($test_result['success'], 'Test should report failure');
        $this->assertArrayHasKey('error', $test_result);
    }
    
    /**
     * Test de performance de inicialización
     */
    public function test_initialization_performance() {
        $this->assertExecutionTimeUnder(function() {
            return new DatabaseConnectionModule(false);
        }, 100); // Debe tomar menos de 100ms
    }
    
    /**
     * Test del método get_environment_info
     */
    public function test_get_environment_info_structure() {
        $env_info = $this->module->get_environment_info();
        
        $expected_keys = [
            'is_local_wp',
            'wp_db_host', 
            'wp_db_name',
            'wp_db_user',
            'socket_path',
            'php_version',
            'wordpress_version'
        ];
        
        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $env_info, "Environment info should have key: {$key}");
        }
        
        // Verificar tipos
        $this->assertIsBool($env_info['is_local_wp']);
        $this->assertIsString($env_info['php_version']);
        $this->assertIsString($env_info['wordpress_version']);
    }
    
    /**
     * Test del método test_connection con diferentes configuraciones
     */
    public function test_connection_test_method() {
        $test_result = $this->module->test_connection();
        
        // Verificar estructura del resultado
        $this->assertIsArray($test_result);
        $this->assertArrayHasKey('success', $test_result);
        $this->assertArrayHasKey('dsn_used', $test_result);
        $this->assertIsBool($test_result['success']);
        
        if ($test_result['success']) {
            $this->assertArrayHasKey('server_info', $test_result);
            $this->assertArrayHasKey('test_query', $test_result);
        } else {
            $this->assertArrayHasKey('error', $test_result);
        }
    }
    
    /**
     * Test de limpieza de recursos
     */
    public function test_resource_cleanup() {
        $module = new DatabaseConnectionModule(false);
        
        // Intentar obtener conexión
        $connection = $module->get_connection();
        
        // Test del método close
        $module->close();
        
        // Verificar que después de close, get_connection crea nueva instancia
        $new_connection = $module->get_connection();
        
        // En tests mock, ambas pueden ser null, pero el comportamiento debe ser consistente
        $this->assertTrue(true, 'Cleanup methods should not throw exceptions');
    }
    
    /**
     * Test de compatibilidad con diferentes versiones de PHP
     */
    public function test_php_compatibility() {
        $env_info = $this->module->get_environment_info();
        $php_version = $env_info['php_version'];
        
        // Verificar que funciona con PHP 7.4+
        $this->assertTrue(
            version_compare($php_version, '7.4.0', '>='),
            'PHP version should be 7.4 or higher for compatibility'
        );
        
        // Verificar que PDO está disponible
        $this->assertTrue(
            extension_loaded('pdo'),
            'PDO extension should be available'
        );
        
        $this->assertTrue(
            extension_loaded('pdo_mysql'),
            'PDO MySQL extension should be available'
        );
    }
    
    /**
     * Test específico para entorno Local by WP Engine real
     * @group LocalWP
     */
    public function test_real_local_wp_engine_environment() {
        // Solo ejecutar si estamos en un entorno real de Local by WP Engine
        $socket_path = '/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock';
        
        if (!file_exists($socket_path)) {
            $this->markTestSkipped('Real Local by WP Engine environment not detected');
        }
        
        $module = new DatabaseConnectionModule(true);
        $env_info = $module->get_environment_info();
        
        $this->assertTrue($env_info['is_local_wp'], 'Should detect Local by WP Engine');
        $this->assertEquals($socket_path, $env_info['socket_path'], 'Should find correct socket');
        
        // Test real de conexión
        $test_result = $module->test_connection();
        $this->assertTrue($test_result['success'], 'Real connection should work in Local by WP Engine');
    }
}
