<?php
/**
 * Tests del SystemInfo Module - Dev-Tools Arquitectura 3.0
 *
 * @package TarokinaPro\DevTools\Tests\Unit\Modules
 * @since 1.0.0
 */

namespace DevToolsTests\Unit\Modules;

use DevToolsTestCase;

/**
 * Test del módulo SystemInfo
 */
class SystemInfoModuleTest extends DevToolsTestCase 
{
    private $system_info_module;

    /**
     * Setup antes de cada test
     */
    public function setUp(): void 
    {
        parent::setUp();
        
        // Cargar e instanciar el módulo SystemInfo
        require_once $this->getDevToolsPath() . '/modules/SystemInfoModule.php';
        $this->system_info_module = new \SystemInfoModule();
    }

    /**
     * Test de existencia y estructura de la clase
     */
    public function test_system_info_module_class_structure() 
    {
        $this->assertTrue(class_exists('SystemInfoModule'), 'Clase SystemInfoModule debe existir');
        $this->assertInstanceOf('DevToolsModuleBase', $this->system_info_module, 'SystemInfoModule debe extender DevToolsModuleBase');
        $this->assertInstanceOf('DevToolsModuleInterface', $this->system_info_module, 'SystemInfoModule debe implementar DevToolsModuleInterface');
    }

    /**
     * Test de obtención de información del sistema
     */
    public function test_system_info_retrieval() 
    {
        if (method_exists($this->system_info_module, 'get_system_info')) {
            $info = $this->system_info_module->get_system_info();
            
            $this->assertIsArray($info, 'Información del sistema debe ser array');
            $this->assertNotEmpty($info, 'Información del sistema no debe estar vacía');
            
            // Verificar categorías básicas esperadas
            $expected_categories = ['php', 'wordpress', 'server', 'database'];
            foreach ($expected_categories as $category) {
                if (isset($info[$category])) {
                    $this->assertIsArray($info[$category], "Categoría '{$category}' debe ser array");
                }
            }
        }
    }

    /**
     * Test de información de PHP
     */
    public function test_php_information() 
    {
        if (method_exists($this->system_info_module, 'get_php_info')) {
            $php_info = $this->system_info_module->get_php_info();
            
            $this->assertIsArray($php_info, 'Información de PHP debe ser array');
            
            // Verificar datos críticos de PHP
            $expected_php_data = ['version', 'memory_limit', 'max_execution_time', 'extensions'];
            foreach ($expected_php_data as $key) {
                if (isset($php_info[$key])) {
                    $this->assertNotEmpty($php_info[$key], "Dato de PHP '{$key}' no debe estar vacío");
                }
            }
            
            // Verificar formato de versión de PHP
            if (isset($php_info['version'])) {
                $this->assertMatchesRegularExpression('/^\d+\.\d+/', $php_info['version'], 'Versión de PHP debe tener formato válido');
            }
        }
    }

    /**
     * Test de información de WordPress
     */
    public function test_wordpress_information() 
    {
        if (method_exists($this->system_info_module, 'get_wordpress_info')) {
            $wp_info = $this->system_info_module->get_wordpress_info();
            
            $this->assertIsArray($wp_info, 'Información de WordPress debe ser array');
            
            // Verificar datos básicos de WordPress
            $expected_wp_data = ['version', 'multisite', 'debug_mode', 'theme', 'plugins'];
            foreach ($expected_wp_data as $key) {
                if (isset($wp_info[$key])) {
                    $this->assertNotNull($wp_info[$key], "Dato de WordPress '{$key}' no debe ser null");
                }
            }
            
            // Verificar formato de versión
            if (isset($wp_info['version'])) {
                $this->assertMatchesRegularExpression('/^\d+\.\d+/', $wp_info['version'], 'Versión de WordPress debe tener formato válido');
            }
            
            // Verificar tipo de datos específicos
            if (isset($wp_info['multisite'])) {
                $this->assertIsBool($wp_info['multisite'], 'Multisite debe ser boolean');
            }
        }
    }

    /**
     * Test de información del servidor
     */
    public function test_server_information() 
    {
        if (method_exists($this->system_info_module, 'get_server_info')) {
            $server_info = $this->system_info_module->get_server_info();
            
            $this->assertIsArray($server_info, 'Información del servidor debe ser array');
            
            // Verificar datos del servidor
            $expected_server_data = ['software', 'php_version', 'mysql_version', 'disk_space'];
            foreach ($expected_server_data as $key) {
                if (isset($server_info[$key])) {
                    $this->assertNotEmpty($server_info[$key], "Dato del servidor '{$key}' no debe estar vacío");
                }
            }
        }
    }

    /**
     * Test de información de la base de datos
     */
    public function test_database_information() 
    {
        if (method_exists($this->system_info_module, 'get_database_info')) {
            $db_info = $this->system_info_module->get_database_info();
            
            $this->assertIsArray($db_info, 'Información de la base de datos debe ser array');
            
            // Verificar conexión y datos básicos
            if (isset($db_info['version'])) {
                $this->assertNotEmpty($db_info['version'], 'Versión de base de datos no debe estar vacía');
            }
            
            if (isset($db_info['size'])) {
                $this->assertIsNumeric($db_info['size'], 'Tamaño de base de datos debe ser numérico');
            }
            
            if (isset($db_info['tables_count'])) {
                $this->assertIsInt($db_info['tables_count'], 'Conteo de tablas debe ser entero');
                $this->assertGreaterThan(0, $db_info['tables_count'], 'Debe haber al menos una tabla');
            }
        }
    }

    /**
     * Test de información de plugins
     */
    public function test_plugins_information() 
    {
        if (method_exists($this->system_info_module, 'get_plugins_info')) {
            $plugins_info = $this->system_info_module->get_plugins_info();
            
            $this->assertIsArray($plugins_info, 'Información de plugins debe ser array');
            
            // Verificar estructura de datos de plugins
            foreach ($plugins_info as $plugin_slug => $plugin_data) {
                $this->assertIsString($plugin_slug, 'Slug de plugin debe ser string');
                $this->assertIsArray($plugin_data, 'Datos de plugin deben ser array');
                
                // Verificar campos básicos del plugin
                if (isset($plugin_data['Name'])) {
                    $this->assertNotEmpty($plugin_data['Name'], 'Nombre de plugin no debe estar vacío');
                }
                
                if (isset($plugin_data['Version'])) {
                    $this->assertNotEmpty($plugin_data['Version'], 'Versión de plugin no debe estar vacía');
                }
            }
        }
    }

    /**
     * Test de información de tema activo
     */
    public function test_theme_information() 
    {
        if (method_exists($this->system_info_module, 'get_theme_info')) {
            $theme_info = $this->system_info_module->get_theme_info();
            
            $this->assertIsArray($theme_info, 'Información del tema debe ser array');
            
            // Verificar datos básicos del tema
            if (isset($theme_info['name'])) {
                $this->assertNotEmpty($theme_info['name'], 'Nombre del tema no debe estar vacío');
            }
            
            if (isset($theme_info['version'])) {
                $this->assertNotEmpty($theme_info['version'], 'Versión del tema no debe estar vacía');
            }
            
            if (isset($theme_info['status'])) {
                $this->assertEquals('active', $theme_info['status'], 'Tema debe estar activo');
            }
        }
    }

    /**
     * Test de constantes de WordPress importantes
     */
    public function test_wordpress_constants() 
    {
        if (method_exists($this->system_info_module, 'get_wordpress_constants')) {
            $constants = $this->system_info_module->get_wordpress_constants();
            
            $this->assertIsArray($constants, 'Constantes de WordPress deben ser array');
            
            // Verificar constantes críticas
            $critical_constants = ['WP_DEBUG', 'WP_DEBUG_LOG', 'SCRIPT_DEBUG', 'WP_MEMORY_LIMIT'];
            foreach ($critical_constants as $constant) {
                if (isset($constants[$constant])) {
                    $this->assertNotNull($constants[$constant], "Constante '{$constant}' no debe ser null");
                }
            }
        }
    }

    /**
     * Test de capacidades del servidor
     */
    public function test_server_capabilities() 
    {
        if (method_exists($this->system_info_module, 'get_server_capabilities')) {
            $capabilities = $this->system_info_module->get_server_capabilities();
            
            $this->assertIsArray($capabilities, 'Capacidades del servidor deben ser array');
            
            // Verificar capacidades importantes
            $important_capabilities = ['curl', 'gd', 'mbstring', 'xml'];
            foreach ($important_capabilities as $capability) {
                if (isset($capabilities[$capability])) {
                    $this->assertIsBool($capabilities[$capability], "Capacidad '{$capability}' debe ser boolean");
                }
            }
        }
    }

    /**
     * Test de renderizado de información del sistema
     */
    public function test_system_info_rendering() 
    {
        // Capturar output del renderizado
        ob_start();
        $this->system_info_module->render_content();
        $content = ob_get_clean();
        
        $this->assertIsString($content, 'Contenido renderizado debe ser string');
        $this->assertNotEmpty($content, 'Contenido no debe estar vacío');
        
        // Verificar que contiene información del sistema
        $this->assertStringContainsString('PHP', $content, 'Debe mostrar información de PHP');
        $this->assertStringContainsString('WordPress', $content, 'Debe mostrar información de WordPress');
        
        // Verificar estructura HTML básica
        $this->assertStringContainsString('<div', $content, 'Debe contener elementos div');
        $this->assertStringContainsString('<table', $content, 'Debe contener tablas para mostrar datos');
    }

    /**
     * Test de exportación de información del sistema
     */
    public function test_system_info_export() 
    {
        if (method_exists($this->system_info_module, 'export_system_info')) {
            $exported = $this->system_info_module->export_system_info();
            
            $this->assertIsString($exported, 'Información exportada debe ser string');
            $this->assertNotEmpty($exported, 'Información exportada no debe estar vacía');
            
            // Verificar que es JSON válido o texto estructurado
            $json_decoded = json_decode($exported, true);
            if ($json_decoded !== null) {
                $this->assertIsArray($json_decoded, 'Si es JSON, debe decodificar a array');
            } else {
                // Si no es JSON, debe ser texto estructurado
                $this->assertGreaterThan(100, strlen($exported), 'Texto exportado debe ser sustancial');
            }
        }
    }

    /**
     * Test de diagnóstico del sistema
     */
    public function test_system_diagnostics() 
    {
        if (method_exists($this->system_info_module, 'run_diagnostics')) {
            $diagnostics = $this->system_info_module->run_diagnostics();
            
            $this->assertIsArray($diagnostics, 'Diagnósticos deben ser array');
            
            // Verificar estructura de diagnósticos
            foreach ($diagnostics as $test_name => $result) {
                $this->assertIsString($test_name, 'Nombre de test debe ser string');
                $this->assertIsArray($result, 'Resultado de test debe ser array');
                
                // Verificar campos básicos del resultado
                if (isset($result['status'])) {
                    $this->assertContains($result['status'], ['pass', 'fail', 'warning'], "Status debe ser válido");
                }
                
                if (isset($result['message'])) {
                    $this->assertIsString($result['message'], 'Mensaje debe ser string');
                }
            }
        }
    }

    /**
     * Test de comandos AJAX específicos del módulo
     */
    public function test_system_info_ajax_commands() 
    {
        $ajax_commands = $this->system_info_module->get_ajax_commands();
        
        $this->assertIsArray($ajax_commands, 'Comandos AJAX deben ser array');
        
        // Verificar comando específico de system info
        if (isset($ajax_commands['get_system_info'])) {
            $handler = $ajax_commands['get_system_info'];
            $this->assertTrue(is_callable([$this->system_info_module, $handler]), 'Handler get_system_info debe ser callable');
        }
    }
}
