<?php
/**
 * Clase Base de Testing - Dev-Tools Arquitectura 3.0
 * 
 * Clase base PLUGIN-AGNÓSTICA para tests de Dev-Tools.
 * Extiende WP_UnitTestCase con funcionalidades específicas para la Arquitectura 3.0.
 * 
 * Características:
 * - 100% independiente del plugin host
 * - Helpers para testing de módulos
 * - Gestión automática de estado de dev-tools
 * - Limpieza automática entre tests
 * - Utilities para AJAX testing
 * - Mock factories para módulos
 * 
 * @package DevTools\Tests
 * @since Arquitectura 3.0
 * @author Dev-Tools Team
 */

if (!class_exists('DevToolsTestCase')) {

    class DevToolsTestCase extends WP_UnitTestCase 
    {
        // =====================================================================
        // PROPIEDADES DE TESTING
        // =====================================================================
        
        /** @var DevToolsModuleManager|null Cache del module manager */
        protected static $module_manager = null;
        
        /** @var array Estado original de módulos antes del test */
        protected $original_modules_state = [];
        
        /** @var bool Si dev-tools está disponible en este test */
        protected $dev_tools_available = false;
        
        // =====================================================================
        // MÉTODOS DE SETUP Y TEARDOWN
        // =====================================================================
        
        /**
         * Setup que se ejecuta antes de cada test
         */
        protected function setUp(): void 
        {
            parent::setUp();
            
            // Verificar disponibilidad de dev-tools
            $this->dev_tools_available = $this->checkDevToolsAvailability();
            
            if ($this->dev_tools_available) {
                // Guardar estado original de módulos
                $this->original_modules_state = $this->getModulesState();
                
                // Limpiar logs de dev-tools
                $this->clearDevToolsLogs();
            }
        }
        
        /**
         * Teardown que se ejecuta después de cada test
         */
        protected function tearDown(): void 
        {
            if ($this->dev_tools_available) {
                // Restaurar estado original de módulos
                $this->restoreModulesState();
                
                // Limpiar datos temporales de dev-tools
                $this->cleanupDevToolsData();
            }
            
            parent::tearDown();
        }
        
        // =====================================================================
        // HELPERS PARA DEV-TOOLS
        // =====================================================================
        
        /**
         * Verificar si dev-tools está disponible
         */
        protected function checkDevToolsAvailability(): bool
        {
            return class_exists('DevToolsModuleManager') && 
                   class_exists('DevToolsModuleBase') && 
                   interface_exists('DevToolsModuleInterface');
        }
        
        /**
         * Obtener instancia del module manager
         */
        protected function getModuleManager(): ?DevToolsModuleManager
        {
            if (!$this->dev_tools_available) {
                return null;
            }
            
            if (self::$module_manager === null) {
                self::$module_manager = DevToolsModuleManager::getInstance();
            }
            
            return self::$module_manager;
        }
        
        /**
         * Obtener estado actual de módulos
         */
        protected function getModulesState(): array
        {
            $manager = $this->getModuleManager();
            if (!$manager) {
                return [];
            }
            
            return $manager->getModulesStatus();
        }
        
        /**
         * Restaurar estado de módulos
         */
        protected function restoreModulesState(): void
        {
            $manager = $this->getModuleManager();
            if (!$manager) {
                return;
            }
            
            // Limpiar y reinicializar módulos
            $manager->cleanup();
            $manager->initialize();
        }
        
        /**
         * Limpiar logs de dev-tools
         */
        protected function clearDevToolsLogs(): void
        {
            // No hay función global, implementar limpieza básica
            delete_transient('dev_tools_logs');
            delete_option('dev_tools_internal_logs');
        }
        
        /**
         * Limpiar datos temporales de dev-tools
         */
        protected function cleanupDevToolsData(): void
        {
            // Limpiar opciones temporales de dev-tools
            delete_option('dev_tools_temp_data');
            delete_transient('dev_tools_cache');
            
            // Limpiar meta temporales
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'dev_tools_test_%'");
        }
        
        // =====================================================================
        // HELPERS PARA TESTING DE MÓDULOS
        // =====================================================================
        
        /**
         * Crear un módulo mock para testing
         * 
         * @param string $module_name
         * @param array $methods
         * @return DevToolsModuleBase&PHPUnit\Framework\MockObject\MockObject
         */
        protected function createMockModule(string $module_name, array $methods = []): DevToolsModuleBase
        {
            $mock = $this->getMockBuilder(DevToolsModuleBase::class)
                         ->setMockClassName($module_name . 'Module')
                         ->disableOriginalConstructor()
                         ->getMock();
            
            // Configurar métodos básicos
            $mock->method('getModuleInfo')->willReturn([
                'name' => $module_name,
                'version' => '1.0.0-test',
                'description' => "Mock module for testing: {$module_name}"
            ]);
            
            foreach ($methods as $method => $return_value) {
                $mock->method($method)->willReturn($return_value);
            }
            
            /** @var DevToolsModuleBase $mock */
            return $mock;
        }
        
        /**
         * Registrar un módulo temporal para testing
         */
        protected function registerTestModule(string $module_name, DevToolsModuleBase $module): void
        {
            $manager = $this->getModuleManager();
            if ($manager) {
                $manager->registerModule($module_name, $module);
            }
        }
        
        /**
         * Verificar que un módulo está cargado
         */
        protected function assertModuleLoaded(string $module_name): void
        {
            $manager = $this->getModuleManager();
            $this->assertNotNull($manager, 'DevToolsModuleManager no está disponible');
            
            $modules = $manager->getModulesStatus();
            $this->assertArrayHasKey($module_name, $modules, "Módulo '{$module_name}' no está cargado");
        }
        
        /**
         * Verificar que un módulo NO está cargado
         */
        protected function assertModuleNotLoaded(string $module_name): void
        {
            $manager = $this->getModuleManager();
            if (!$manager) {
                return; // Si no hay manager, obviamente no está cargado
            }
            
            $modules = $manager->getModulesStatus();
            $this->assertArrayNotHasKey($module_name, $modules, "Módulo '{$module_name}' está cargado cuando no debería");
        }
        
        // =====================================================================
        // HELPERS PARA AJAX TESTING
        // =====================================================================
        
        /**
         * Simular una petición AJAX de dev-tools
         */
        protected function simulateDevToolsAjax(string $action, array $data = []): array
        {
            if (!$this->dev_tools_available) {
                $this->fail('Dev-Tools no está disponible para testing AJAX');
            }
            
            // Simular contexto AJAX
            $_POST['action'] = 'dev_tools_ajax';
            $_POST['command'] = $action;
            $_POST['data'] = wp_json_encode($data);
            $_POST['nonce'] = wp_create_nonce('dev_tools_ajax');
            
            // Capturar output
            ob_start();
            
            try {
                // Simular el handler AJAX
                do_action('wp_ajax_dev_tools_ajax');
                $output = ob_get_clean();
                
                // Decodificar respuesta JSON
                $response = json_decode($output, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->fail("Respuesta AJAX no es JSON válido: {$output}");
                }
                
                return $response;
                
            } catch (Exception $e) {
                ob_end_clean();
                $this->fail("Error en AJAX simulation: " . $e->getMessage());
            }
            
            // Este return nunca debería ejecutarse, pero evita el warning de PHP
            return [];
        }
        
        // =====================================================================
        // ASSERTIONS ESPECÍFICAS
        // =====================================================================
        
        /**
         * Verificar que dev-tools está funcionando
         */
        protected function assertDevToolsWorking(): void
        {
            $this->assertTrue($this->dev_tools_available, 'Dev-Tools no está disponible');
            
            $manager = $this->getModuleManager();
            $this->assertInstanceOf(DevToolsModuleManager::class, $manager, 'ModuleManager no es accesible');
        }
        
        /**
         * Verificar respuesta AJAX exitosa
         */
        protected function assertAjaxSuccess(array $response): void
        {
            $this->assertArrayHasKey('success', $response, 'Respuesta AJAX no tiene campo success');
            $this->assertTrue($response['success'], 'Respuesta AJAX indica error: ' . 
                            (isset($response['message']) ? $response['message'] : 'Sin mensaje'));
        }
        
        /**
         * Verificar respuesta AJAX con error
         */
        protected function assertAjaxError(array $response): void
        {
            $this->assertArrayHasKey('success', $response, 'Respuesta AJAX no tiene campo success');
            $this->assertFalse($response['success'], 'Respuesta AJAX indica éxito cuando se esperaba error');
        }
        
        // =====================================================================
        // SKIP METHODS
        // =====================================================================
        
        /**
         * Skip test si dev-tools no está disponible
         */
        protected function requireDevTools(): void
        {
            if (!$this->dev_tools_available) {
                $this->markTestSkipped('Dev-Tools Arquitectura 3.0 no está disponible');
            }
        }
        
        /**
         * Skip test si un módulo específico no está disponible
         */
        protected function requireModule(string $module_name): void
        {
            $this->requireDevTools();
            
            $manager = $this->getModuleManager();
            $modules = $manager->getModulesStatus();
            
            if (!isset($modules[$module_name])) {
                $this->markTestSkipped("Módulo '{$module_name}' no está disponible");
            }
        }
    }
}
