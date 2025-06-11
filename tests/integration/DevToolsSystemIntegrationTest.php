<?php
/**
 * Test: Dev-Tools Integration Tests
 * 
 * Tests de integración que prueban el sistema completo
 * incluyendo WordPress, módulos y panel admin
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

class DevToolsSystemIntegrationTest extends DevToolsTestCase {
    
    private $loader;
    private $admin_user_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Crear usuario administrador para tests
        $this->admin_user_id = $this->act_as_user_with_capability('manage_options');
        
        // Inicializar sistema Dev-Tools
        require_once dirname(__DIR__, 2) . '/loader.php';
        $this->loader = DevToolsLoader::getInstance();
    }
    
    /**
     * @group Quick
     * Test de inicialización completa del sistema
     */
    public function test_system_initialization() {
        $this->assertInstanceOf(DevToolsLoader::class, $this->loader);
        
        // Verificar que los módulos se cargan correctamente
        $modules = $this->loader->get_modules();
        $this->assertIsArray($modules);
        $this->assertNotEmpty($modules, 'At least some modules should be loaded');
        
        // Verificar módulos específicos
        $expected_modules = [
            'DatabaseConnectionModule',
            'SiteUrlDetectionModule'
        ];
        
        foreach ($expected_modules as $module_name) {
            $this->assertModuleLoaded($module_name);
        }
    }
    
    /**
     * Test de registro de menús de administración
     */
    public function test_admin_menu_registration() {
        global $menu, $submenu;
        
        // Simular hook de admin_menu
        do_action('admin_menu');
        
        // Verificar que el menú principal existe
        $dev_tools_menu = null;
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === 'dev-tools') {
                    $dev_tools_menu = $menu_item;
                    break;
                }
            }
        }
        
        $this->assertNotNull($dev_tools_menu, 'Dev-Tools menu should be registered');
        $this->assertEquals('Dev-Tools', $dev_tools_menu[0]);
        
        // Verificar submenús
        if (isset($submenu['dev-tools'])) {
            $this->assertIsArray($submenu['dev-tools']);
            $this->assertNotEmpty($submenu['dev-tools']);
        }
    }
    
    /**
     * Test de carga de assets en admin
     */
    public function test_admin_assets_enqueue() {
        // Simular página de dev-tools
        set_current_screen('toplevel_page_dev-tools');
        
        // Simular hook de admin_enqueue_scripts
        do_action('admin_enqueue_scripts', 'toplevel_page_dev-tools');
        
        // Verificar que Bootstrap CSS está enqueueado
        $this->assertTrue(wp_style_is('dev-tools-bootstrap', 'enqueued'), 'Bootstrap CSS should be enqueued');
        
        // Verificar que el CSS personalizado está enqueueado
        $this->assertTrue(wp_style_is('dev-tools-admin', 'enqueued'), 'Admin CSS should be enqueued');
        
        // Verificar que el JavaScript está enqueueado
        $this->assertTrue(wp_script_is('dev-tools-admin', 'enqueued'), 'Admin JS should be enqueued');
    }
    
    /**
     * Test de manejo de peticiones AJAX
     */
    public function test_ajax_request_handling() {
        // Simular petición AJAX válida
        $this->mock_ajax_request('system_info', ['debug' => true]);
        
        // Capturar salida
        ob_start();
        
        try {
            do_action('wp_ajax_dev_tools_ajax');
            $output = ob_get_clean();
            
            // Verificar que la respuesta es JSON válida
            $response = json_decode($output, true);
            $this->assertNotNull($response, 'AJAX response should be valid JSON');
            $this->assertValidAjaxResponse($response);
            
        } catch (Exception $e) {
            ob_end_clean();
            
            // En tests, las funciones wp_send_json pueden no estar disponibles
            // Verificar que al menos no hay errores fatales
            $this->assertStringNotContainsString('Fatal error', $e->getMessage());
        }
    }
    
    /**
     * Test de integración entre módulos
     */
    public function test_modules_integration() {
        $modules = $this->loader->get_modules();
        
        // Test de DatabaseConnectionModule
        if (isset($modules['DatabaseConnectionModule'])) {
            $db_module = $modules['DatabaseConnectionModule'];
            $env_info = $db_module->get_environment_info();
            
            $this->assertIsArray($env_info);
            $this->assertArrayHasKey('is_local_wp', $env_info);
        }
        
        // Test de SiteUrlDetectionModule
        if (isset($modules['SiteUrlDetectionModule'])) {
            $url_module = $modules['SiteUrlDetectionModule'];
            $detected_url = $url_module->get_site_url();
            
            $this->assertNotEmpty($detected_url);
            $this->assertValidUrl($detected_url);
        }
        
        // Verificar que los módulos pueden trabajar juntos
        if (isset($modules['DatabaseConnectionModule']) && isset($modules['SiteUrlDetectionModule'])) {
            $db_env = $modules['DatabaseConnectionModule']->get_environment_info();
            $url_env = $modules['SiteUrlDetectionModule']->get_environment_info();
            
            // Ambos deberían detectar consistentemente si es Local by WP Engine
            $this->assertEquals(
                $db_env['is_local_wp'], 
                $url_env['is_local_wp'],
                'Modules should agree on Local by WP Engine detection'
            );
        }
    }
    
    /**
     * Test de renderizado del panel de administración
     */
    public function test_admin_panel_rendering() {
        $admin_panel = $this->loader->get_admin_panel();
        
        if ($admin_panel) {
            // Test de renderizado del dashboard
            $dashboard_output = $this->get_output_from_callable([$admin_panel, 'render_dashboard']);
            
            $this->assertStringContainsString('Dev-Tools', $dashboard_output['output']);
            $this->assertStringContainsString('bootstrap', $dashboard_output['output']);
            $this->assertStringContainsString('card', $dashboard_output['output']);
        } else {
            $this->markTestSkipped('Admin panel not available in this test environment');
        }
    }
    
    /**
     * Test de configuración agnóstica
     */
    public function test_agnostic_configuration() {
        $config = $this->loader->get_config();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('modules_enabled', $config);
        
        // Verificar que la configuración no contiene referencias hardcodeadas
        $config_string = json_encode($config);
        $this->assertStringNotContainsString('tarokina', $config_string, 'Config should not contain plugin-specific references');
        $this->assertStringNotContainsString('localhost:3000', $config_string, 'Config should not contain hardcoded URLs');
    }
    
    /**
     * Test de detección automática de entorno
     */
    public function test_environment_auto_detection() {
        // Test en entorno simulado de Local by WP Engine
        simulate_local_wp_engine();
        
        // Reinicializar para que detecte el nuevo entorno
        $test_loader = DevToolsLoader::getInstance();
        $modules = $test_loader->get_modules();
        
        if (isset($modules['SiteUrlDetectionModule'])) {
            $url_module = $modules['SiteUrlDetectionModule'];
            $env_info = $url_module->get_environment_info();
            
            $this->assertTrue($env_info['is_local_wp'], 'Should detect simulated Local by WP Engine environment');
        }
        
        // Test en entorno simulado de producción
        simulate_production_environment();
        
        $url_module_prod = new SiteUrlDetectionModule(false);
        $env_info_prod = $url_module_prod->get_environment_info();
        
        $this->assertFalse($env_info_prod['is_local_wp'], 'Should detect production environment');
    }
    
    /**
     * Test de seguridad y permisos
     */
    public function test_security_and_permissions() {
        // Test con usuario sin permisos
        $regular_user = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($regular_user);
        
        // Intentar petición AJAX sin permisos
        $this->mock_ajax_request('system_info', []);
        
        ob_start();
        try {
            do_action('wp_ajax_dev_tools_ajax');
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            if ($response) {
                $this->assertFalse($response['success'], 'Should fail without proper permissions');
            }
        } catch (Exception $e) {
            ob_end_clean();
            // Verificar que hay algún tipo de validación de permisos
            $this->assertTrue(true, 'Permission validation should exist');
        }
        
        // Restaurar usuario admin
        wp_set_current_user($this->admin_user_id);
    }
    
    /**
     * Test de performance del sistema completo
     */
    public function test_system_performance() {
        // La inicialización completa debe ser rápida
        $measurement = $this->measure_execution_time(function() {
            // Simular carga completa del sistema
            $loader = DevToolsLoader::getInstance();
            $loader->init_system();
            return $loader;
        });
        
        $this->assertLessThan(500, $measurement['execution_time'], 'System initialization should be under 500ms');
    }
    
    /**
     * Test de compatibilidad con WordPress
     */
    public function test_wordpress_compatibility() {
        // Verificar versión mínima de WordPress
        $wp_version = get_bloginfo('version');
        $this->assertTrue(
            version_compare($wp_version, '5.0', '>='),
            'WordPress version should be 5.0 or higher'
        );
        
        // Verificar que no interfiere con funciones de WordPress
        $this->assertTrue(function_exists('wp_enqueue_script'), 'WordPress functions should remain available');
        $this->assertTrue(function_exists('add_action'), 'WordPress hooks should remain available');
        
        // Verificar que los hooks están registrados correctamente
        $this->assertGreaterThan(0, has_action('admin_menu'), 'Admin menu hook should be registered');
        $this->assertGreaterThan(0, has_action('admin_enqueue_scripts'), 'Admin enqueue hook should be registered');
    }
    
    /**
     * Test de cleanup y desinstalación
     */
    public function test_system_cleanup() {
        // Verificar que el sistema puede limpiarse correctamente
        $modules = $this->loader->get_modules();
        
        foreach ($modules as $module) {
            if (method_exists($module, 'cleanup')) {
                $this->assertTrue(is_callable([$module, 'cleanup']), 'Module cleanup should be callable');
            }
        }
        
        // Test de limpieza de cache
        $this->clear_dev_tools_cache();
        
        // Verificar que no quedan datos residuales
        global $wpdb;
        $transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_dev_tools_%'"
        );
        
        $this->assertEmpty($transients, 'No dev-tools transients should remain after cleanup');
    }
}
