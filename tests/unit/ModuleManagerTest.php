<?php
/**
 * Tests para el gestor de módulos Dev-Tools
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 3.0
 */

class ModuleManagerTest extends DevToolsTestCase {

    private $module_manager;
    private $test_module_data;

    public function setUp(): void {
        parent::setUp();
        
        // Crear instancia del gestor de módulos
        $this->module_manager = new DevToolsModuleManager();
        
        // Datos de módulo de prueba
        $this->test_module_data = [
            'id' => 'test_module',
            'name' => 'Test Module',
            'description' => 'Módulo de prueba',
            'version' => '1.0.0',
            'priority' => 10,
            'ajax_actions' => ['test_action']
        ];
    }

    public function tearDown(): void {
        // Limpiar módulos registrados
        $this->module_manager->clear_all_modules();
        unset($this->module_manager);
        parent::tearDown();
    }

    /**
     * Test inicialización del gestor
     */
    public function testManagerInitialization(): void {
        $this->assertInstanceOf('DevToolsModuleManager', $this->module_manager);
        $this->assertTrue($this->module_manager->is_initialized());
    }

    /**
     * Test registro de módulo
     */
    public function testModuleRegistration(): void {
        $mock_module = $this->createMockModule();
        
        $result = $this->module_manager->register_module($mock_module);
        $this->assertTrue($result);
        
        $registered_modules = $this->module_manager->get_registered_modules();
        $this->assertArrayHasKey('test_module', $registered_modules);
    }

    /**
     * Test descubrimiento automático de módulos
     */
    public function testAutoDiscovery(): void {
        $discovered_modules = $this->module_manager->discover_modules();
        $this->assertIsArray($discovered_modules);
        
        // Verificar que se descubre al menos el DashboardModule
        $module_names = array_keys($discovered_modules);
        $this->assertContains('dashboard', $module_names);
    }

    /**
     * Test carga de módulos
     */
    public function testModuleLoading(): void {
        // Registrar módulo mock
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        // Cargar módulos
        $loaded_count = $this->module_manager->load_modules();
        $this->assertGreaterThan(0, $loaded_count);
        
        // Verificar que el módulo está cargado
        $this->assertTrue($this->module_manager->is_module_loaded('test_module'));
    }

    /**
     * Test activación/desactivación de módulos
     */
    public function testModuleActivationDeactivation(): void {
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        // Test activación
        $result = $this->module_manager->activate_module('test_module');
        $this->assertTrue($result);
        $this->assertTrue($this->module_manager->is_module_active('test_module'));
        
        // Test desactivación
        $result = $this->module_manager->deactivate_module('test_module');
        $this->assertTrue($result);
        $this->assertFalse($this->module_manager->is_module_active('test_module'));
    }

    /**
     * Test obtener información de módulos
     */
    public function testModuleInformation(): void {
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        $module_info = $this->module_manager->get_module_info('test_module');
        $this->assertIsArray($module_info);
        $this->assertEquals('Test Module', $module_info['name']);
        $this->assertEquals('1.0.0', $module_info['version']);
    }

    /**
     * Test prioridades de módulos
     */
    public function testModulePriorities(): void {
        // Crear módulos con diferentes prioridades
        $module_high = $this->createMockModule('high_priority', 5);
        $module_low = $this->createMockModule('low_priority', 20);
        
        $this->module_manager->register_module($module_high);
        $this->module_manager->register_module($module_low);
        
        $sorted_modules = $this->module_manager->get_modules_by_priority();
        $module_ids = array_keys($sorted_modules);
        
        $this->assertEquals('high_priority', $module_ids[0]);
        $this->assertEquals('low_priority', array_pop($module_ids));
    }

    /**
     * Test dependencias entre módulos
     */
    public function testModuleDependencies(): void {
        // Crear módulo con dependencias
        $dependent_module = $this->createMockModule('dependent', 10, ['dashboard']);
        
        $this->module_manager->register_module($dependent_module);
        
        $dependencies = $this->module_manager->get_module_dependencies('dependent');
        $this->assertContains('dashboard', $dependencies);
        
        $can_load = $this->module_manager->can_load_module('dependent');
        $this->assertIsBool($can_load);
    }

    /**
     * Test hooks de módulos
     */
    public function testModuleHooks(): void {
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        // Test que los hooks se registran correctamente
        $hooks = $this->module_manager->get_module_hooks('test_module');
        $this->assertIsArray($hooks);
    }

    /**
     * Test estado de módulos
     */
    public function testModuleStates(): void {
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        // Estados posibles: registered, loaded, active, inactive, error
        $this->assertEquals('registered', $this->module_manager->get_module_state('test_module'));
        
        $this->module_manager->load_module('test_module');
        $this->assertEquals('loaded', $this->module_manager->get_module_state('test_module'));
        
        $this->module_manager->activate_module('test_module');
        $this->assertEquals('active', $this->module_manager->get_module_state('test_module'));
    }

    /**
     * Test manejo de errores en módulos
     */
    public function testModuleErrorHandling(): void {
        // Intentar registrar módulo inválido
        $invalid_module = new stdClass();
        $result = $this->module_manager->register_module($invalid_module);
        $this->assertFalse($result);
        
        // Intentar cargar módulo inexistente
        $result = $this->module_manager->load_module('nonexistent_module');
        $this->assertFalse($result);
        
        $errors = $this->module_manager->get_errors();
        $this->assertNotEmpty($errors);
    }

    /**
     * Test filtros y acciones de módulos
     */
    public function testModuleFiltersActions(): void {
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        // Test filtro antes de cargar módulo
        add_filter('dev_tools_before_load_module', function($module_id) {
            return $module_id === 'test_module' ? 'test_module' : false;
        });
        
        $result = $this->module_manager->load_module('test_module');
        $this->assertTrue($result);
        
        // Test acción después de cargar módulo
        $action_fired = false;
        add_action('dev_tools_module_loaded', function($module_id) use (&$action_fired) {
            if ($module_id === 'test_module') {
                $action_fired = true;
            }
        });
        
        $this->module_manager->load_module('test_module');
        $this->assertTrue($action_fired);
    }

    /**
     * Test cache de módulos
     */
    public function testModuleCache(): void {
        $this->module_manager->clear_module_cache();
        
        // Registrar y cargar módulo
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        $this->module_manager->load_module('test_module');
        
        // Verificar que se puede obtener desde cache
        $cached_module = $this->module_manager->get_cached_module('test_module');
        $this->assertNotNull($cached_module);
        
        // Limpiar cache
        $this->module_manager->clear_module_cache();
        $cached_module = $this->module_manager->get_cached_module('test_module');
        $this->assertNull($cached_module);
    }

    /**
     * Test estadísticas de módulos
     */
    public function testModuleStatistics(): void {
        // Registrar varios módulos
        for ($i = 1; $i <= 3; $i++) {
            $module = $this->createMockModule("test_module_$i");
            $this->module_manager->register_module($module);
        }
        
        $stats = $this->module_manager->get_statistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_registered', $stats);
        $this->assertArrayHasKey('total_loaded', $stats);
        $this->assertArrayHasKey('total_active', $stats);
        
        $this->assertEquals(3, $stats['total_registered']);
    }

    /**
     * Test configuración específica de módulos
     */
    public function testModuleSpecificConfiguration(): void {
        $mock_module = $this->createMockModule();
        $this->module_manager->register_module($mock_module);
        
        // Test configuración del módulo
        $config = $this->module_manager->get_module_config('test_module');
        $this->assertIsArray($config);
        $this->assertEquals('Test Module', $config['name']);
        
        // Test actualizar configuración
        $new_config = ['custom_setting' => 'custom_value'];
        $result = $this->module_manager->update_module_config('test_module', $new_config);
        $this->assertTrue($result);
        
        $updated_config = $this->module_manager->get_module_config('test_module');
        $this->assertEquals('custom_value', $updated_config['custom_setting']);
    }

    /**
     * Helper: Crear módulo mock para testing
     */
    private function createMockModule($id = 'test_module', $priority = 10, $dependencies = []): object {
        $mock = $this->getMockBuilder('DevToolsModuleBase')
                     ->disableOriginalConstructor()
                     ->onlyMethods(['get_module_config', 'init', 'activate', 'deactivate'])
                     ->getMock();
        
        $config = array_merge($this->test_module_data, [
            'id' => $id,
            'priority' => $priority,
            'dependencies' => $dependencies
        ]);
        
        $mock->method('get_module_config')
             ->willReturn($config);
             
        $mock->method('init')
             ->willReturn(true);
             
        $mock->method('activate')
             ->willReturn(true);
             
        $mock->method('deactivate')
             ->willReturn(true);
        
        return $mock;
    }
}
