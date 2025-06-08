<?php
/**
 * Tests del Dashboard Module - Dev-Tools Arquitectura 3.0
 *
 * @package TarokinaPro\DevTools\Tests\Unit\Modules
 * @since 1.0.0
 */

namespace DevToolsTests\Unit\Modules;

use DevToolsTestCase;

/**
 * Test del módulo Dashboard
 */
class DashboardModuleTest extends DevToolsTestCase 
{
    private $dashboard_module;

    /**
     * Setup antes de cada test
     */
    public function setUp(): void 
    {
        parent::setUp();
        
        // Cargar e instanciar el módulo Dashboard
        require_once $this->getDevToolsPath() . '/modules/DashboardModule.php';
        $this->dashboard_module = new \DashboardModule();
    }

    /**
     * Test de existencia y estructura de la clase
     */
    public function test_dashboard_module_class_structure() 
    {
        $this->assertTrue(class_exists('DashboardModule'), 'Clase DashboardModule debe existir');
        $this->assertInstanceOf('DevToolsModuleBase', $this->dashboard_module, 'DashboardModule debe extender DevToolsModuleBase');
        $this->assertInstanceOf('DevToolsModuleInterface', $this->dashboard_module, 'DashboardModule debe implementar DevToolsModuleInterface');
    }

    /**
     * Test de inicialización del módulo
     */
    public function test_dashboard_module_initialization() 
    {
        // Verificar que el módulo se inicializa correctamente
        $this->dashboard_module->init();
        
        // Verificar que el módulo está registrado
        $this->assertTrue($this->dashboard_module->is_enabled(), 'Dashboard module debe estar habilitado');
        
        // Verificar configuración básica
        $config = $this->dashboard_module->get_config();
        $this->assertIsArray($config, 'Configuración debe ser array');
        $this->assertEquals('dashboard', $config['slug'], 'Slug debe ser dashboard');
    }

    /**
     * Test de métodos requeridos por la interfaz
     */
    public function test_dashboard_interface_methods() 
    {
        // Verificar que todos los métodos requeridos están implementados
        $required_methods = [
            'get_title',
            'get_description', 
            'render_content',
            'enqueue_assets',
            'get_ajax_commands'
        ];
        
        foreach ($required_methods as $method) {
            $this->assertTrue(method_exists($this->dashboard_module, $method), "Método {$method} debe existir");
        }
    }

    /**
     * Test de metadatos del módulo
     */
    public function test_dashboard_module_metadata() 
    {
        $title = $this->dashboard_module->get_title();
        $description = $this->dashboard_module->get_description();
        
        $this->assertIsString($title, 'Título debe ser string');
        $this->assertNotEmpty($title, 'Título no debe estar vacío');
        $this->assertStringContainsString('Dashboard', $title, 'Título debe contener "Dashboard"');
        
        $this->assertIsString($description, 'Descripción debe ser string');
        $this->assertNotEmpty($description, 'Descripción no debe estar vacía');
        $this->assertGreaterThan(10, strlen($description), 'Descripción debe ser descriptiva');
    }

    /**
     * Test de comandos AJAX del módulo
     */
    public function test_dashboard_ajax_commands() 
    {
        $ajax_commands = $this->dashboard_module->get_ajax_commands();
        
        $this->assertIsArray($ajax_commands, 'Comandos AJAX deben ser array');
        
        if (!empty($ajax_commands)) {
            foreach ($ajax_commands as $command => $handler) {
                $this->assertIsString($command, "Comando '{$command}' debe ser string");
                $this->assertTrue(is_callable([$this->dashboard_module, $handler]), "Handler '{$handler}' debe ser callable");
            }
        }
    }

    /**
     * Test de renderizado de contenido
     */
    public function test_dashboard_content_rendering() 
    {
        // Capturar output del renderizado
        ob_start();
        $this->dashboard_module->render_content();
        $content = ob_get_clean();
        
        $this->assertIsString($content, 'Contenido renderizado debe ser string');
        $this->assertNotEmpty($content, 'Contenido no debe estar vacío');
        
        // Verificar que contiene elementos HTML básicos
        $this->assertStringContainsString('<div', $content, 'Debe contener elementos div');
        
        // Verificar estructura Bootstrap si se usa
        if (strpos($content, 'bootstrap') !== false || strpos($content, 'container') !== false) {
            $this->assertStringContainsString('container', $content, 'Debe usar clases de contenedor Bootstrap');
        }
    }

    /**
     * Test de enqueue de assets
     */
    public function test_dashboard_asset_enqueuing() 
    {
        // Simular contexto de admin
        set_current_screen('tools_page_tarokina-2025-dev-tools');
        
        // Ejecutar enqueue
        $this->dashboard_module->enqueue_assets();
        
        // Verificar que se encolan scripts y estilos
        global $wp_scripts, $wp_styles;
        
        $dashboard_scripts = array_filter(array_keys($wp_scripts->registered), function($handle) {
            return strpos($handle, 'dashboard') !== false || strpos($handle, 'dev-tools') !== false;
        });
        
        $this->assertNotEmpty($dashboard_scripts, 'Debe encolar al menos un script relacionado con dashboard');
    }

    /**
     * Test de datos de dashboard
     */
    public function test_dashboard_data_retrieval() 
    {
        // Si el módulo tiene método para obtener datos
        if (method_exists($this->dashboard_module, 'get_dashboard_data')) {
            $data = $this->dashboard_module->get_dashboard_data();
            
            $this->assertIsArray($data, 'Datos de dashboard deben ser array');
            
            // Verificar estructura esperada de datos
            $expected_keys = ['system_status', 'modules_status', 'recent_activity'];
            foreach ($expected_keys as $key) {
                if (isset($data[$key])) {
                    $this->assertNotNull($data[$key], "Dato '{$key}' no debe ser null");
                }
            }
        }
    }

    /**
     * Test de estadísticas del sistema
     */
    public function test_dashboard_system_stats() 
    {
        // Si el módulo maneja estadísticas del sistema
        if (method_exists($this->dashboard_module, 'get_system_stats')) {
            $stats = $this->dashboard_module->get_system_stats();
            
            $this->assertIsArray($stats, 'Estadísticas deben ser array');
            
            // Verificar métricas básicas esperadas
            $expected_metrics = ['memory_usage', 'load_time', 'database_queries'];
            foreach ($expected_metrics as $metric) {
                if (isset($stats[$metric])) {
                    $this->assertIsNumeric($stats[$metric], "Métrica '{$metric}' debe ser numérica");
                }
            }
        }
    }

    /**
     * Test de widgets de dashboard
     */
    public function test_dashboard_widgets() 
    {
        // Si el módulo tiene widgets
        if (method_exists($this->dashboard_module, 'get_widgets')) {
            $widgets = $this->dashboard_module->get_widgets();
            
            $this->assertIsArray($widgets, 'Widgets deben ser array');
            
            foreach ($widgets as $widget_id => $widget_config) {
                $this->assertIsString($widget_id, "ID de widget debe ser string");
                $this->assertIsArray($widget_config, "Configuración de widget debe ser array");
                
                // Verificar estructura básica del widget
                $this->assertArrayHasKey('title', $widget_config, "Widget debe tener título");
                $this->assertArrayHasKey('content', $widget_config, "Widget debe tener contenido");
            }
        }
    }

    /**
     * Test de navegación del dashboard
     */
    public function test_dashboard_navigation() 
    {
        // Si el módulo maneja navegación
        if (method_exists($this->dashboard_module, 'get_navigation_items')) {
            $nav_items = $this->dashboard_module->get_navigation_items();
            
            $this->assertIsArray($nav_items, 'Items de navegación deben ser array');
            
            foreach ($nav_items as $item) {
                $this->assertArrayHasKey('label', $item, "Item de navegación debe tener label");
                $this->assertArrayHasKey('url', $item, "Item de navegación debe tener URL");
                
                if (isset($item['url'])) {
                    $this->assertIsString($item['url'], "URL debe ser string");
                    $this->assertNotEmpty($item['url'], "URL no debe estar vacía");
                }
            }
        }
    }

    /**
     * Test de configuración específica del dashboard
     */
    public function test_dashboard_specific_configuration() 
    {
        $config = $this->dashboard_module->get_config();
        
        // Verificar configuraciones específicas del dashboard
        $this->assertEquals('dashboard', $config['slug'], 'Slug debe ser dashboard');
        $this->assertTrue($config['enabled'], 'Dashboard debe estar habilitado por defecto');
        
        if (isset($config['position'])) {
            $this->assertIsInt($config['position'], 'Posición debe ser entero');
            $this->assertEquals(0, $config['position'], 'Dashboard debe tener posición 0 (primera)');
        }
        
        if (isset($config['capabilities'])) {
            $this->assertIsArray($config['capabilities'], 'Capacidades deben ser array');
            $this->assertContains('manage_options', $config['capabilities'], 'Debe requerir manage_options');
        }
    }

    /**
     * Test de comportamiento en diferentes roles de usuario
     */
    public function test_dashboard_user_role_behavior() 
    {
        // Test con administrador
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        $this->assertTrue($this->dashboard_module->can_access(), 'Administrador debe poder acceder');
        
        // Test con editor
        $editor_user = $this->factory->user->create(['role' => 'editor']);
        wp_set_current_user($editor_user);
        
        // Editor no debería poder acceder por defecto
        $this->assertFalse($this->dashboard_module->can_access(), 'Editor no debe poder acceder por defecto');
        
        // Reset usuario
        wp_set_current_user(0);
    }
}
