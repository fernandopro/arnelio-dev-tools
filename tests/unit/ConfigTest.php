<?php
/**
 * Tests para el sistema de configuración Dev-Tools
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 3.0
 */

class ConfigTest extends DevToolsTestCase {

    private $config;

    public function setUp(): void {
        parent::setUp();
        
        // Crear instancia limpia de configuración
        $this->config = dev_tools_config();
    }

    public function tearDown(): void {
        // Limpiar configuración después de cada test
        unset($this->config);
        parent::tearDown();
    }

    /**
     * Test configuración básica del sistema
     */
    public function testBasicConfiguration(): void {
        $this->assertInstanceOf('DevToolsConfig', $this->config);
        $this->assertTrue($this->config->is_loaded());
    }

    /**
     * Test detección automática del plugin host
     */
    public function testHostPluginDetection(): void {
        $host_plugin = $this->config->get('host_plugin.slug');
        $this->assertNotEmpty($host_plugin);
        $this->assertEquals('tarokina-2025', $host_plugin);
    }

    /**
     * Test generación dinámica de URLs
     */
    public function testDynamicUrlGeneration(): void {
        $menu_slug = $this->config->get('dev_tools.menu_slug');
        $this->assertEquals('tarokina-2025-dev-tools', $menu_slug);
        
        $ajax_prefix = $this->config->get('ajax.action_prefix');
        $this->assertEquals('tarokina-2025', $ajax_prefix);
    }

    /**
     * Test configuración de rutas
     */
    public function testPathConfiguration(): void {
        $dev_tools_path = $this->config->get('paths.dev_tools');
        $this->assertNotEmpty($dev_tools_path);
        $this->assertTrue(is_dir($dev_tools_path));
        
        $modules_path = $this->config->get('paths.modules');
        $this->assertNotEmpty($modules_path);
        $this->assertTrue(is_dir($modules_path));
    }

    /**
     * Test configuración de assets
     */
    public function testAssetsConfiguration(): void {
        $assets_url = $this->config->get('assets.url');
        $this->assertNotEmpty($assets_url);
        $this->assertStringContains('dev-tools/dist', $assets_url);
        
        $version = $this->config->get('assets.version');
        $this->assertNotEmpty($version);
    }

    /**
     * Test configuración de base de datos
     */
    public function testDatabaseConfiguration(): void {
        $prefix = $this->config->get('database.prefix');
        $this->assertNotEmpty($prefix);
        
        $charset = $this->config->get('database.charset');
        $this->assertEquals('utf8mb4', $charset);
    }

    /**
     * Test configuración de seguridad
     */
    public function testSecurityConfiguration(): void {
        $required_capability = $this->config->get('security.required_capability');
        $this->assertEquals('manage_options', $required_capability);
        
        $nonce_action = $this->config->get('security.nonce_action');
        $this->assertStringContains('dev_tools', $nonce_action);
    }

    /**
     * Test configuración de entorno
     */
    public function testEnvironmentConfiguration(): void {
        $environment = $this->config->get('environment');
        $this->assertContains($environment, ['development', 'staging', 'production']);
        
        $debug_mode = $this->config->get('debug.enabled');
        $this->assertIsBool($debug_mode);
    }

    /**
     * Test métodos get/set de configuración
     */
    public function testGetSetMethods(): void {
        // Test get con valor por defecto
        $default_value = 'test_default';
        $result = $this->config->get('nonexistent.key', $default_value);
        $this->assertEquals($default_value, $result);
        
        // Test get con clave existente
        $existing_value = $this->config->get('host_plugin.slug');
        $this->assertNotEquals($default_value, $existing_value);
    }

    /**
     * Test configuración de módulos
     */
    public function testModulesConfiguration(): void {
        $modules_config = $this->config->get('modules');
        $this->assertIsArray($modules_config);
        
        $enabled_modules = $this->config->get('modules.enabled');
        $this->assertIsArray($enabled_modules);
        $this->assertContains('dashboard', $enabled_modules);
    }

    /**
     * Test configuración de AJAX
     */
    public function testAjaxConfiguration(): void {
        $ajax_url = $this->config->get('ajax.url');
        $this->assertStringContains('admin-ajax.php', $ajax_url);
        
        $timeout = $this->config->get('ajax.timeout');
        $this->assertIsInt($timeout);
        $this->assertGreaterThan(0, $timeout);
    }

    /**
     * Test configuración de logging
     */
    public function testLoggingConfiguration(): void {
        $log_level = $this->config->get('logging.level');
        $this->assertContains($log_level, ['debug', 'info', 'warning', 'error']);
        
        $log_file = $this->config->get('logging.file');
        $this->assertNotEmpty($log_file);
    }

    /**
     * Test validación de configuración
     */
    public function testConfigurationValidation(): void {
        $is_valid = $this->config->validate();
        $this->assertTrue($is_valid);
        
        $validation_errors = $this->config->get_validation_errors();
        $this->assertEmpty($validation_errors);
    }

    /**
     * Test configuración de cache
     */
    public function testCacheConfiguration(): void {
        $cache_enabled = $this->config->get('cache.enabled');
        $this->assertIsBool($cache_enabled);
        
        if ($cache_enabled) {
            $cache_ttl = $this->config->get('cache.ttl');
            $this->assertIsInt($cache_ttl);
            $this->assertGreaterThan(0, $cache_ttl);
        }
    }

    /**
     * Test configuración específica de Local by Flywheel
     */
    public function testLocalFlyWheelConfiguration(): void {
        if ($this->config->is_local_environment()) {
            $socket_key = $this->config->get('local.socket_key');
            $this->assertNotEmpty($socket_key);
            
            $local_url = $this->config->get('local.url');
            $this->assertStringStartsWith('http://localhost:', $local_url);
        }
    }

    /**
     * Test helpers de configuración
     */
    public function testConfigurationHelpers(): void {
        // Test get_admin_url
        $admin_url = $this->config->get_admin_url();
        $this->assertStringContains('wp-admin', $admin_url);
        
        // Test get_admin_url con parámetros
        $tools_url = $this->config->get_admin_url('tools.php');
        $this->assertStringContains('tools.php', $tools_url);
        
        // Test is_ajax_request (en contexto de test será false)
        $is_ajax = $this->config->is_ajax_request();
        $this->assertIsBool($is_ajax);
    }

    /**
     * Test configuración de assets minificados
     */
    public function testMinifiedAssetsConfiguration(): void {
        $use_minified = $this->config->get('assets.use_minified');
        $this->assertIsBool($use_minified);
        
        $js_files = $this->config->get('assets.js_files');
        $this->assertIsArray($js_files);
        $this->assertNotEmpty($js_files);
        
        $css_files = $this->config->get('assets.css_files');
        $this->assertIsArray($css_files);
        $this->assertNotEmpty($css_files);
    }

    /**
     * Test configuración condicional basada en entorno
     */
    public function testConditionalConfiguration(): void {
        if ($this->config->is_development()) {
            $debug_enabled = $this->config->get('debug.enabled');
            $this->assertTrue($debug_enabled);
        }
        
        if ($this->config->is_production()) {
            $use_minified = $this->config->get('assets.use_minified');
            $this->assertTrue($use_minified);
        }
    }
}
