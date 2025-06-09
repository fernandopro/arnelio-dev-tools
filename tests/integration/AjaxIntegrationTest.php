<?php
/**
 * Test de Integración AJAX - Dev-Tools Arquitectura 3.0
 * 
 * Tests que requieren WordPress completo y base de datos.
 * Verifican la integración real entre componentes.
 * 
 * @package DevTools\Tests\Integration
 * @since Arquitectura 3.0
 */

class AjaxIntegrationTest extends DevToolsTestCase {
    
    /**
     * Test de integración: Endpoint AJAX de system info
     */
    public function test_ajax_system_info_integration() {
        // Este test requiere WordPress completo funcionando
        $this->requireDevTools();
        
        // Simular usuario admin
        $admin_id = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
        
        // CORRECCIÓN: Usar configuración dinámica
        $config = dev_tools_config();
        $ajax_action = $config->get('ajax.action_name');
        $nonce_action = $config->get('ajax.nonce_action');
        
        // Preparar datos AJAX reales según patrón dev-tools
        $_POST['action'] = $ajax_action;  // Acción WordPress dinámica
        $_POST['action_type'] = 'get_system_info';     // Comando interno
        $_POST['nonce'] = wp_create_nonce($nonce_action);
        $_REQUEST['_ajax_nonce'] = $_POST['nonce'];
        
        // Capturar salida AJAX
        ob_start();
        
        try {
            // Ejecutar AJAX handler real con acción dinámica
            do_action('wp_ajax_' . $ajax_action);
        } catch (WPAjaxDieContinueException $e) {
            // Expected - AJAX termina con wp_die()
        }
        
        $response = ob_get_clean();
        
        // Verificar respuesta JSON válida
        $data = json_decode($response, true);
        $this->assertIsArray($data, 'Respuesta AJAX debe ser JSON válido');
        $this->assertArrayHasKey('success', $data, 'Respuesta debe tener campo success');
        $this->assertTrue($data['success'], 'AJAX debe ser exitoso');
        
        // Verificar estructura de datos del sistema
        if (isset($data['data'])) {
            $system_info = $data['data'];
            $this->assertArrayHasKey('php_version', $system_info);
            $this->assertArrayHasKey('wordpress_version', $system_info);
            $this->assertArrayHasKey('dev_tools_version', $system_info);
        }
    }
    
    /**
     * Test de integración: Base de datos y opciones
     */
    public function test_database_integration() {
        global $wpdb;
        
        // Verificar conexión real con base de datos de testing
        $this->assertTrue($wpdb->check_connection(), 'Base de datos debe estar conectada');
        
        // Test escribir/leer opciones
        $test_option = 'dev_tools_test_' . time();
        $test_value = ['test' => 'integration_data'];
        
        update_option($test_option, $test_value);
        $retrieved = get_option($test_option);
        
        $this->assertEquals($test_value, $retrieved, 'Opciones deben persistir en BD');
        
        // Limpiar
        delete_option($test_option);
        $this->assertFalse(get_option($test_option), 'Opción debe eliminarse correctamente');
    }
    
    /**
     * Test de integración: Hooks y Filters de WordPress
     */
    public function test_wordpress_hooks_integration() {
        $hook_fired = false;
        
        // Registrar hook temporal
        $callback = function() use (&$hook_fired) {
            $hook_fired = true;
        };
        
        add_action('dev_tools_test_hook', $callback);
        
        // Disparar hook
        do_action('dev_tools_test_hook');
        
        $this->assertTrue($hook_fired, 'Hook debe ejecutarse correctamente');
        
        // Limpiar
        remove_action('dev_tools_test_hook', $callback);
    }
    
    /**
     * Test de integración: Capabilities y permisos
     */
    public function test_user_capabilities_integration() {
        // Crear usuarios con diferentes roles
        $admin_id = $this->factory()->user->create(['role' => 'administrator']);
        $editor_id = $this->factory()->user->create(['role' => 'editor']);
        $subscriber_id = $this->factory()->user->create(['role' => 'subscriber']);
        
        // Test permisos admin
        wp_set_current_user($admin_id);
        $this->assertTrue(current_user_can('manage_options'), 'Admin debe tener manage_options');
        
        // Test permisos editor
        wp_set_current_user($editor_id);
        $this->assertFalse(current_user_can('manage_options'), 'Editor no debe tener manage_options');
        
        // Test permisos subscriber
        wp_set_current_user($subscriber_id);
        $this->assertFalse(current_user_can('edit_posts'), 'Subscriber no debe tener edit_posts');
        
        // Reset a admin para otros tests
        wp_set_current_user($admin_id);
    }
    
    /**
     * Test de integración: Transients y caché
     */
    public function test_transients_integration() {
        $transient_key = 'dev_tools_test_transient';
        $transient_value = ['cached' => 'data', 'timestamp' => time()];
        
        // Set transient con expiración
        set_transient($transient_key, $transient_value, 300); // 5 minutos
        
        // Verificar inmediatamente
        $retrieved = get_transient($transient_key);
        $this->assertEquals($transient_value, $retrieved, 'Transient debe recuperarse correctamente');
        
        // Delete transient
        delete_transient($transient_key);
        $this->assertFalse(get_transient($transient_key), 'Transient debe eliminarse');
    }
}
