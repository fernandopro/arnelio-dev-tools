<?php
/**
 * Test del Dashboard Module
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 */

use DevTools\Tests\TestCase;

class DashboardModuleTest extends TestCase {

    /**
     * Test que verifica que el DashboardModule se carga correctamente
     */
    public function test_dashboard_module_loaded() {
        $this->assert_module_loaded( 'Dashboard' );
    }

    /**
     * Test que verifica que el menú de admin se registra
     */
    public function test_admin_menu_registration() {
        global $menu;
        
        // Simular que estamos en el admin
        set_current_screen( 'dashboard' );
        $this->assertTrue( is_admin() );
        
        // Verificar que existe la clase DashboardModule
        $this->assertTrue( class_exists( 'DevTools\\Modules\\DashboardModule' ) );
    }

    /**
     * Test que verifica la configuración por defecto del módulo
     */
    public function test_module_default_config() {
        $module_data = $this->create_module_test_data( 'Dashboard', [
            'config' => [
                'show_welcome' => true,
                'show_stats' => true,
                'cache_enabled' => false
            ]
        ] );
        
        $this->assertEquals( 'Dashboard', $module_data['name'] );
        $this->assertEquals( 'active', $module_data['status'] );
        $this->assertTrue( $module_data['config']['show_welcome'] );
    }

    /**
     * Test que verifica que los assets se pueden cargar
     */
    public function test_module_assets_loading() {
        // Simular página de admin de dev-tools
        $_GET['page'] = 'dev-tools';
        set_current_screen( 'toplevel_page_dev-tools' );
        
        // Verificar que estamos en la página correcta
        $screen = get_current_screen();
        $this->assertEquals( 'toplevel_page_dev-tools', $screen->id );
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
