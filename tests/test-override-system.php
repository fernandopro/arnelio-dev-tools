<?php
/**
 * Test del Sistema de Override - WordPress PHPUnit
 *
 * Verifica que el sistema de override tipo child-theme funciona correctamente
 * en el entorno de testing adaptado al estándar WordPress
 *
 * @package DevTools
 * @subpackage Tests
 * @version 3.0.0
 */

class Test_Override_System extends DevToolsTestCase {

    /**
     * Test que el sistema de override está inicializado
     */
    public function test_override_system_initialized() {
        $config = dev_tools_config();
        $override_system = $config->getOverrideSystem();
        
        $this->assertNotNull( $override_system, 'Sistema de override debe estar inicializado' );
        $this->assertTrue( class_exists( 'FileOverrideSystem' ), 'Clase FileOverrideSystem debe existir' );
    }

    /**
     * Test que detecta correctamente los directorios
     */
    public function test_override_directories() {
        $config = dev_tools_config();
        $override_info = $config->get_override_info();
        
        $this->assertIsArray( $override_info, 'get_override_info debe retornar array' );
        $this->assertArrayHasKey( 'parent_dir', $override_info );
        $this->assertArrayHasKey( 'child_dir', $override_info );
        $this->assertArrayHasKey( 'parent_exists', $override_info );
        $this->assertArrayHasKey( 'child_exists', $override_info );
        
        // El directorio padre (dev-tools) debe existir
        $this->assertTrue( $override_info['parent_exists'], 'Directorio padre debe existir' );
        
        // Verificar rutas correctas
        $this->assertStringContains( 'dev-tools', $override_info['parent_dir'] );
        $this->assertStringContains( 'plugin-dev-tools', $override_info['child_dir'] );
    }

    /**
     * Test que la configuración se carga correctamente
     */
    public function test_config_access() {
        $config = dev_tools_config();
        
        // Verificar que podemos acceder a configuración básica
        $admin_url = $config->get_admin_url();
        $this->assertNotEmpty( $admin_url, 'Admin URL debe estar configurada' );
        
        // Verificar que el sistema detectó el plugin host
        $host_info = $config->get( 'host_plugin' );
        $this->assertIsArray( $host_info, 'Información del plugin host debe ser array' );
        $this->assertArrayHasKey( 'name', $host_info );
        $this->assertArrayHasKey( 'version', $host_info );
    }

    /**
     * Test que el sistema de módulos funciona con override
     */
    public function test_module_system_with_override() {
        // Verificar que el ModuleManager existe
        $this->assertTrue( class_exists( 'DevToolsModuleManager' ), 'DevToolsModuleManager debe existir' );
        
        // Verificar que se puede obtener la instancia
        $manager = DevToolsModuleManager::getInstance();
        $this->assertInstanceOf( 'DevToolsModuleManager', $manager );
        
        // Verificar que hay módulos cargados
        $modules_status = $manager->getModulesStatus();
        $this->assertIsArray( $modules_status, 'Status de módulos debe ser array' );
        $this->assertNotEmpty( $modules_status, 'Debe haber al menos un módulo cargado' );
    }

    /**
     * Test que las constantes están definidas correctamente
     */
    public function test_constants_defined() {
        $config = dev_tools_config();
        
        // Verificar que se registraron las constantes
        $constants = $config->get( 'constants' );
        $this->assertIsArray( $constants, 'Constantes deben estar definidas' );
        
        // En el entorno de testing, las constantes deben estar disponibles
        $this->assertTrue( defined( 'ABSPATH' ), 'ABSPATH debe estar definida en testing' );
    }

    /**
     * Test que el sistema funciona en modo debug
     */
    public function test_debug_mode() {
        $config = dev_tools_config();
        
        // En testing debería estar en modo debug
        $is_debug = $config->is_debug_mode();
        $this->assertIsBool( $is_debug, 'Debug mode debe retornar boolean' );
        
        // Verificar que el sistema de logging funciona
        $config->log( 'Test message from PHPUnit' );
        $this->assertTrue( true, 'Log no debe generar errores' );
    }
}
