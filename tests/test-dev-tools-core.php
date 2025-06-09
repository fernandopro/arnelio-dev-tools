<?php
/**
 * Test del Sistema Core Dev-Tools - WordPress PHPUnit
 *
 * Verifica que el sistema core de dev-tools funciona correctamente
 * en el entorno de testing adaptado al estándar WordPress
 *
 * @package DevTools
 * @subpackage Tests
 * @version 3.0.0
 */

class Test_DevTools_Core extends DevToolsTestCase {

    /**
     * Test que el sistema core está inicializado
     */
    public function test_core_system_initialized() {
        $config = dev_tools_config();
        
        $this->assertNotNull( $config, 'Sistema de configuración debe estar inicializado' );
        $this->assertTrue( class_exists( 'DevToolsConfig' ), 'Clase DevToolsConfig debe existir' );
    }

    /**
     * Test que la estructura de directorios es correcta
     */
    public function test_directory_structure() {
        $config = dev_tools_config();
        $paths = $config->get( 'paths' );
        
        $this->assertIsArray( $paths, 'Paths debe ser array' );
        $this->assertArrayHasKey( 'dev_tools_root', $paths );
        $this->assertArrayHasKey( 'dev_tools_url', $paths );
        
        // El directorio dev-tools debe existir
        $dev_tools_dir = $paths['dev_tools_root'];
        $this->assertTrue( is_dir( $dev_tools_dir ), 'Directorio dev-tools debe existir' );
        
        // Verificar rutas correctas
        $this->assertStringContainsString( 'dev-tools', $dev_tools_dir );
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
        $host_info = $config->get( 'host' );
        $this->assertIsArray( $host_info, 'Información del plugin host debe ser array' );
        $this->assertArrayHasKey( 'name', $host_info );
        $this->assertArrayHasKey( 'version', $host_info );
    }

    /**
     * Test que el sistema de módulos funciona
     */
    public function test_module_system() {
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
        // En el entorno de testing, las constantes básicas de WordPress deben estar disponibles
        $this->assertTrue( defined( 'ABSPATH' ), 'ABSPATH debe estar definida en testing' );
        $this->assertTrue( defined( 'WP_DEBUG' ), 'WP_DEBUG debe estar definida en testing' );
        
        // Verificar que las constantes de dev-tools están disponibles
        $this->assertTrue( defined( 'DEV_TOOLS_DIR_PATH' ), 'DEV_TOOLS_DIR_PATH debe estar definida' );
        $this->assertTrue( defined( 'DEV_TOOLS_DIR_URL' ), 'DEV_TOOLS_DIR_URL debe estar definida' );
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
