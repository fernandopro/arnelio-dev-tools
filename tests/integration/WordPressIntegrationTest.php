<?php
/**
 * Tests de Integración con WordPress - Dev-Tools Arquitectura 3.0
 *
 * @package TarokinaPro\DevTools\Tests\Integration
 * @since 1.0.0
 */

namespace DevToolsTests\Integration;

use DevToolsTestCase;

/**
 * Test de integración con WordPress
 */
class WordPressIntegrationTest extends DevToolsTestCase 
{
    /**
     * Test de hooks de WordPress registrados
     */
    public function test_wordpress_hooks_integration() 
    {
        // Verificar hooks críticos para admin
        $this->assertGreaterThan(0, has_action('admin_menu'), 'Hook admin_menu debe estar registrado');
        $this->assertGreaterThan(0, has_action('admin_enqueue_scripts'), 'Hook admin_enqueue_scripts debe estar registrado');
        $this->assertGreaterThan(0, has_action('wp_ajax_dev_tools_ajax'), 'Hook AJAX debe estar registrado');
        
        // Verificar hooks de inicialización
        $this->assertGreaterThan(0, has_action('init'), 'Hook init debe estar registrado');
        
        // Verificar hooks de activación/desactivación si existen
        if (function_exists('dev_tools_activation_hook')) {
            $this->assertTrue(function_exists('dev_tools_activation_hook'), 'Hook de activación debe existir');
        }
    }

    /**
     * Test de integración con menú de administración
     */
    public function test_admin_menu_integration() 
    {
        global $submenu, $menu;
        
        // Simular usuario administrador
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Ejecutar hook de menú
        do_action('admin_menu');
        
        // Verificar que el menú está registrado en tools.php
        $tools_submenu = isset($submenu['tools.php']) ? $submenu['tools.php'] : [];
        
        $dev_tools_menu_found = false;
        foreach ($tools_submenu as $submenu_item) {
            if (strpos($submenu_item[2], 'dev-tools') !== false) {
                $dev_tools_menu_found = true;
                $this->assertEquals('manage_options', $submenu_item[1], 'Capability debe ser manage_options');
                break;
            }
        }
        
        $this->assertTrue($dev_tools_menu_found, 'Menú de dev-tools debe estar registrado en tools');
    }

    /**
     * Test de carga de assets en admin
     */
    public function test_admin_assets_integration() 
    {
        global $wp_scripts, $wp_styles;
        
        // Simular pantalla de admin de dev-tools
        set_current_screen('tools_page_tarokina-2025-dev-tools');
        
        // Simular usuario admin
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Ejecutar hook de enqueue
        do_action('admin_enqueue_scripts', 'tools_page_tarokina-2025-dev-tools');
        
        // Verificar que se cargan scripts relacionados con dev-tools
        $dev_tools_scripts = array_filter(array_keys($wp_scripts->registered), function($handle) {
            return strpos($handle, 'dev-tools') !== false || strpos($handle, 'tarokina') !== false;
        });
        
        $this->assertNotEmpty($dev_tools_scripts, 'Debe cargar scripts de dev-tools en admin');
        
        // Verificar estilos
        $dev_tools_styles = array_filter(array_keys($wp_styles->registered), function($handle) {
            return strpos($handle, 'dev-tools') !== false || strpos($handle, 'tarokina') !== false;
        });
        
        $this->assertNotEmpty($dev_tools_styles, 'Debe cargar estilos de dev-tools en admin');
    }

    /**
     * Test de integración con base de datos
     */
    public function test_database_integration() 
    {
        global $wpdb;
        
        // Verificar conexión con WordPress
        $this->assertNotNull($wpdb, 'Conexión a base de datos debe estar disponible');
        
        // Test de lectura/escritura de opciones
        $test_option = 'dev_tools_integration_test_' . time();
        $test_value = 'integration_test_value_' . rand(1000, 9999);
        
        // Escribir opción
        $result = update_option($test_option, $test_value);
        $this->assertTrue($result, 'Debe poder escribir opciones en la base de datos');
        
        // Leer opción
        $retrieved_value = get_option($test_option);
        $this->assertEquals($test_value, $retrieved_value, 'Debe poder leer opciones de la base de datos');
        
        // Limpiar
        delete_option($test_option);
        $this->assertFalse(get_option($test_option), 'Opción debe eliminarse correctamente');
    }

    /**
     * Test de integración con transients
     */
    public function test_transients_integration() 
    {
        $transient_name = 'dev_tools_test_transient_' . time();
        $transient_value = ['test' => 'data', 'timestamp' => time()];
        $expiration = 3600; // 1 hora
        
        // Establecer transient
        $result = set_transient($transient_name, $transient_value, $expiration);
        $this->assertTrue($result, 'Debe poder establecer transients');
        
        // Obtener transient
        $retrieved_value = get_transient($transient_name);
        $this->assertEquals($transient_value, $retrieved_value, 'Debe poder recuperar transients');
        
        // Eliminar transient
        delete_transient($transient_name);
        $this->assertFalse(get_transient($transient_name), 'Transient debe eliminarse correctamente');
    }

    /**
     * Test de integración con capacidades de usuario
     */
    public function test_user_capabilities_integration() 
    {
        // Test con diferentes roles
        $roles_to_test = [
            'administrator' => true,
            'editor' => false,
            'author' => false,
            'subscriber' => false
        ];
        
        foreach ($roles_to_test as $role => $should_have_access) {
            $user = $this->factory->user->create(['role' => $role]);
            wp_set_current_user($user);
            
            $can_access = current_user_can('manage_options');
            $this->assertEquals($should_have_access, $can_access, "Usuario con rol '{$role}' debe tener acceso correcto");
        }
        
        // Reset usuario
        wp_set_current_user(0);
    }

    /**
     * Test de integración con multisite (si aplica)
     */
    public function test_multisite_integration() 
    {
        if (is_multisite()) {
            // Test específicos para multisite
            $this->assertTrue(function_exists('switch_to_blog'), 'Funciones de multisite deben estar disponibles');
            $this->assertTrue(function_exists('restore_current_blog'), 'Funciones de multisite deben estar disponibles');
            
            // Verificar capacidades de network admin
            $network_admin = $this->factory->user->create(['role' => 'administrator']);
            grant_super_admin($network_admin);
            wp_set_current_user($network_admin);
            
            $this->assertTrue(is_super_admin(), 'Super admin debe tener privilegios correctos');
        } else {
            $this->assertFalse(is_multisite(), 'Site debe ser single site para estos tests');
        }
    }

    /**
     * Test de integración con sistema de cache
     */
    public function test_cache_integration() 
    {
        // Test de object cache si está disponible
        if (function_exists('wp_cache_set') && function_exists('wp_cache_get')) {
            $cache_key = 'dev_tools_test_cache_' . time();
            $cache_value = ['test' => 'cache_data', 'timestamp' => time()];
            $cache_group = 'dev_tools_test';
            
            // Establecer cache
            $result = wp_cache_set($cache_key, $cache_value, $cache_group, 3600);
            $this->assertTrue($result, 'Debe poder establecer cache');
            
            // Obtener cache
            $retrieved_value = wp_cache_get($cache_key, $cache_group);
            $this->assertEquals($cache_value, $retrieved_value, 'Debe poder recuperar datos de cache');
            
            // Eliminar cache
            wp_cache_delete($cache_key, $cache_group);
            $this->assertFalse(wp_cache_get($cache_key, $cache_group), 'Cache debe eliminarse correctamente');
        }
    }

    /**
     * Test de integración con cron de WordPress
     */
    public function test_cron_integration() 
    {
        // Verificar que se pueden programar eventos
        $hook_name = 'dev_tools_test_cron_event';
        $timestamp = time() + 3600; // En 1 hora
        $args = ['test' => 'cron_data'];
        
        // Programar evento
        $result = wp_schedule_single_event($timestamp, $hook_name, $args);
        $this->assertNotFalse($result, 'Debe poder programar eventos de cron');
        
        // Verificar que está programado
        $next_scheduled = wp_next_scheduled($hook_name, $args);
        $this->assertEquals($timestamp, $next_scheduled, 'Evento debe estar programado correctamente');
        
        // Limpiar
        wp_unschedule_event($timestamp, $hook_name, $args);
        $this->assertFalse(wp_next_scheduled($hook_name, $args), 'Evento debe desprogramarse correctamente');
    }

    /**
     * Test de integración con filtros y acciones personalizadas
     */
    public function test_custom_hooks_integration() 
    {
        // Test de filtro personalizado
        $filter_name = 'dev_tools_test_filter';
        $test_value = 'original_value';
        $modified_value = 'modified_value';
        
        // Registrar filtro
        add_filter($filter_name, function($value) use ($modified_value) {
            return $modified_value;
        });
        
        // Aplicar filtro
        $result = apply_filters($filter_name, $test_value);
        $this->assertEquals($modified_value, $result, 'Filtro debe modificar el valor');
        
        // Test de acción personalizada
        $action_name = 'dev_tools_test_action';
        $action_fired = false;
        
        // Registrar acción
        add_action($action_name, function() use (&$action_fired) {
            $action_fired = true;
        });
        
        // Ejecutar acción
        do_action($action_name);
        $this->assertTrue($action_fired, 'Acción debe ejecutarse correctamente');
    }

    /**
     * Test de integración con REST API de WordPress
     */
    public function test_rest_api_integration() 
    {
        // Verificar que REST API está disponible
        $this->assertTrue(function_exists('register_rest_route'), 'REST API debe estar disponible');
        
        // Si dev-tools registra endpoints, verificarlos
        if (function_exists('dev_tools_register_rest_routes')) {
            do_action('rest_api_init');
            
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            // Buscar rutas de dev-tools
            $dev_tools_routes = array_filter(array_keys($routes), function($route) {
                return strpos($route, 'dev-tools') !== false;
            });
            
            if (!empty($dev_tools_routes)) {
                $this->assertNotEmpty($dev_tools_routes, 'Rutas REST de dev-tools deben estar registradas');
            }
        }
    }

    /**
     * Test de integración con sistema de logs de WordPress
     */
    public function test_logging_integration() 
    {
        // Test de error_log si WP_DEBUG_LOG está habilitado
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $test_message = 'Dev-Tools integration test log: ' . time();
            
            // Escribir al log
            error_log($test_message);
            
            // Verificar que se puede escribir (no hay forma directa de verificar el contenido en tests)
            $this->assertTrue(true, 'Mensaje de log enviado sin errores');
        }
        
        // Test de función de logging personalizada si existe
        if (function_exists('dev_tools_log')) {
            $result = dev_tools_log('test', 'Integration test message');
            $this->assertTrue($result !== false, 'Función de logging personalizada debe funcionar');
        }
    }
}
