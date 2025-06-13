<?php
/**
 * Test de los módulos Dev-Tools existentes
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 */
namespace DevTools\Tests\Modules;


class DashboardModuleTest extends DevToolsTestCase {



    /**
     * Test que verifica que el DatabaseConnectionModule se carga correctamente
     */
    public function test_database_connection_module_loaded() {
        // Los módulos existentes no usan el namespace DevTools\Modules
        $this->assertTrue( 
            class_exists( 'DatabaseConnectionModule' ),
            'El módulo DatabaseConnectionModule debería estar cargado'
        );
    }

    /**
     * Test que verifica que el SiteUrlDetectionModule se carga correctamente
     */
    public function test_site_url_detection_module_loaded() {
        // Los módulos existentes no usan el namespace DevTools\Modules
        $this->assertTrue( 
            class_exists( 'SiteUrlDetectionModule' ),
            'El módulo SiteUrlDetectionModule debería estar cargado'
        );
    }

    /**
     * Test que verifica que los módulos existen como clases
     */
    public function test_module_classes_exist() {
        // Verificar que existen las clases de módulos reales
        $this->assertTrue( class_exists( 'DatabaseConnectionModule' ) );
        $this->assertTrue( class_exists( 'SiteUrlDetectionModule' ) );
    }

    /**
     * Test que verifica la configuración por defecto de los módulos
     */
    public function test_module_default_config() {
        $module_data = $this->create_module_test_data( 'DatabaseConnectionModule', [
            'config' => [
                'auto_detect' => true,
                'debug_mode' => false,
                'fallback_enabled' => true
            ]
        ] );
        
        $this->assertEquals( 'DatabaseConnectionModule', $module_data['name'] );
        $this->assertEquals( 'active', $module_data['status'] );
        $this->assertTrue( $module_data['config']['auto_detect'] );
    }

    /**
     * Test que verifica que los módulos se pueden instanciar
     */
    public function test_module_instantiation() {
        // Test DatabaseConnectionModule
        $db_module = new DatabaseConnectionModule();
        $this->assertInstanceOf( 'DatabaseConnectionModule', $db_module );
        
        // Test SiteUrlDetectionModule  
        $url_module = new SiteUrlDetectionModule();
        $this->assertInstanceOf( 'SiteUrlDetectionModule', $url_module );
    }

    /**
     * Test de integración básica con WordPress
     */
    public function test_wordpress_integration() {
        // Verificar que WordPress está cargado
        $this->assertTrue( function_exists( 'add_action' ) );
        $this->assertTrue( function_exists( 'wp_enqueue_script' ) );
        $this->assertTrue( function_exists( 'wp_enqueue_style' ) );
        
        // Verificar que estamos en el entorno de testing
        $this->assertTrue( defined( 'DEV_TOOLS_TESTING' ) );
        $this->assertEquals( 'unit', DEV_TOOLS_TEST_MODE );
    }
}
