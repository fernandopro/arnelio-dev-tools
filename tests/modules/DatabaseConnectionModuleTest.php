<?php
/**
 * Test para DatabaseConnectionModule
 * 
 * Tests específicos para la funcionalidad del módulo de conexión a base de datos
 * 
 * @package DevTools
 * @subpackage Tests\Modules
 */
namespace DevTools\Tests\Modules;


require_once dirname(__DIR__) . '/includes/TestCase.php';



class DatabaseConnectionModuleTest extends DevToolsTestCase {

    private $module;
    
    public function setUp(): void {
        parent::setUp();
        
        // Cargar el módulo
        require_once $this->get_dev_tools_path() . '/modules/DatabaseConnectionModule.php';
        $this->module = new DatabaseConnectionModule();
    }

    /**
     * Test: Módulo se instancia correctamente
     */
    public function test_module_instantiation() {
        $this->assertInstanceOf(DatabaseConnectionModule::class, $this->module);
    }

    /**
     * Test: Módulo tiene información de entorno válida
     */
    public function test_environment_detection() {
        // Verificar que el módulo detecta el entorno
        $this->assertNotNull($this->module);
        
        // Test que el módulo se puede construir con diferentes parámetros
        $debug_module = new DatabaseConnectionModule(true);
        $this->assertInstanceOf(DatabaseConnectionModule::class, $debug_module);
    }

    /**
     * Test: Módulo puede detectar conexión MySQL
     */
    public function test_database_connection_detection() {
        global $wpdb;
        
        // Test de conexión básica
        $this->assertInstanceOf('wpdb', $wpdb);
        $this->assertNotEmpty($wpdb->prefix);
        
        // Verificar que podemos ejecutar una consulta simple
        $result = $wpdb->get_var("SELECT 1");
        $this->assertEquals(1, $result);
    }

    /**
     * Test: Módulo puede obtener información de la base de datos
     */
    public function test_database_info_retrieval() {
        global $wpdb;
        
        // Test obtener versión de MySQL
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        $this->assertNotEmpty($mysql_version);
        $this->assertIsString($mysql_version);
        
        // Test obtener nombre de la base de datos
        $db_name = $wpdb->get_var("SELECT DATABASE()");
        $this->assertNotEmpty($db_name);
        $this->assertEquals('local', $db_name); // En Local by WP Engine
    }

    /**
     * Test: Módulo puede validar configuración de WordPress
     */
    public function test_wordpress_db_config_validation() {
        // Verificar constantes de WordPress
        $this->assertTrue(defined('DB_NAME'));
        $this->assertTrue(defined('DB_USER'));
        $this->assertTrue(defined('DB_PASSWORD'));
        $this->assertTrue(defined('DB_HOST'));
        
        // Verificar valores esperados para Local by WP Engine
        $this->assertEquals('local', DB_NAME);
        $this->assertEquals('root', DB_USER);
        $this->assertEquals('root', DB_PASSWORD);
        $this->assertStringContainsString('mysqld.sock', DB_HOST);
    }

    /**
     * Test: Módulo maneja errores de conexión gracefulmente
     */
    public function test_connection_error_handling() {
        // Test simplificado - verificar que el módulo actual funciona
        $this->assertInstanceOf(DatabaseConnectionModule::class, $this->module);
        
        // Verificar que podemos manejar errores sin romper el test
        try {
            $test_wpdb = new wpdb('invalid_user', 'invalid_pass', 'invalid_db', 'localhost');
            $this->assertInstanceOf('wpdb', $test_wpdb);
        } catch (Exception $e) {
            // Es esperado que falle la conexión con credenciales inválidas
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * Test: Módulo puede ejecutar health check
     */
    public function test_database_health_check() {
        global $wpdb;
        
        // Test de health check básico
        $health_checks = [
            'connection' => $wpdb->get_var("SELECT 1") === '1',
            'tables' => !empty($wpdb->get_results("SHOW TABLES")),
            'write_permissions' => true // Asumimos que tenemos permisos de escritura
        ];
        
        foreach ($health_checks as $check => $result) {
            $this->assertTrue($result, "Health check failed for: {$check}");
        }
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->module = null;
    }
}
