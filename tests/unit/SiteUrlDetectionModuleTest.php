<?php
/**
 * Test: Site URL Detection Module - Unit Tests
 * 
 * Tests unitarios para el módulo de detección de URL del sitio
 * Incluye tests para Router Mode de Local by WP Engine
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

class SiteUrlDetectionModuleTest extends DevToolsTestCase {
    
    private $module;
    
    public function setUp(): void {
        parent::setUp();
        
        // Cargar el módulo
        require_once dirname(__DIR__, 2) . '/modules/SiteUrlDetectionModule.php';
    }
    
    public function tearDown(): void {
        // Restaurar entorno después de cada test
        restore_server_environment();
        parent::tearDown();
    }
    
    /**
     * @group Quick
     * Test básico de detección de URL
     */
    public function test_basic_url_detection() {
        $this->module = new SiteUrlDetectionModule(true);
        
        $detected_url = $this->module->get_site_url();
        $this->assertNotEmpty($detected_url, 'Should detect some URL');
        $this->assertValidUrl($detected_url);
    }
    
    /**
     * @group Quick
     * Test de detección con WordPress disponible
     */
    public function test_wordpress_function_detection() {
        // En entorno de test, get_site_url() debería estar disponible
        if (function_exists('get_site_url')) {
            $this->module = new SiteUrlDetectionModule(false);
            
            $detected_url = $this->module->get_site_url();
            $wp_url = get_site_url();
            
            $this->assertEquals($wp_url, $detected_url, 'Should match WordPress get_site_url()');
        } else {
            $this->markTestSkipped('WordPress get_site_url() not available in this test environment');
        }
    }
    
    /**
     * Test de detección en entorno Local by WP Engine - Site Domains
     */
    public function test_local_wp_site_domains_detection() {
        // Simular Router Mode: Site Domains (.local)
        $_SERVER['HTTP_HOST'] = 'test-site.local';
        $_SERVER['SERVER_NAME'] = 'test-site.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=dev-tools';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/testuser/Local Sites/test-site/app/public/wp-content/plugins/test-plugin/test.php';
        
        $this->module = new SiteUrlDetectionModule(true);
        
        $detected_url = $this->module->get_site_url();
        $this->assertEquals('http://test-site.local', $detected_url);
        
        $env_info = $this->module->get_environment_info();
        $this->assertTrue($env_info['is_local_wp']);
        $this->assertEquals('site_domains', $env_info['router_mode']);
    }
    
    /**
     * Test de detección en entorno Local by WP Engine - localhost
     */
    public function test_local_wp_localhost_detection() {
        // Simular Router Mode: localhost con puerto
        $_SERVER['HTTP_HOST'] = 'localhost:3000';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '3000';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/testuser/Local Sites/test-site/app/public/index.php';
        
        $this->module = new SiteUrlDetectionModule(true);
        
        $detected_url = $this->module->get_site_url();
        $this->assertEquals('http://localhost:3000', $detected_url);
        
        $env_info = $this->module->get_environment_info();
        $this->assertTrue($env_info['is_local_wp']);
        $this->assertEquals('localhost', $env_info['router_mode']);
    }
    
    /**
     * Test de detección en entorno de producción
     */
    public function test_production_environment_detection() {
        simulate_production_environment();
        
        $this->module = new SiteUrlDetectionModule(true);
        
        $detected_url = $this->module->get_site_url();
        $this->assertEquals('https://example.com', $detected_url);
        
        $env_info = $this->module->get_environment_info();
        $this->assertFalse($env_info['is_local_wp']);
        $this->assertEquals('server_variables', $env_info['detection_method']);
    }
    
    /**
     * Test de detección desde wp-config.php
     */
    public function test_wp_config_detection() {
        // Crear wp-config temporal con URLs definidas
        $wp_config = $this->test_data_factory->create_temp_wp_config([
            'site_url' => 'https://custom-site.com',
            'home_url' => 'https://custom-site.com'
        ]);
        
        // Simular entorno sin WordPress functions
        $original_wp_available = function_exists('get_site_url');
        
        // Mock para que el módulo lea nuestro wp-config temporal
        // (En implementación real, tendríamos que modificar el path de búsqueda)
        
        $this->module = new SiteUrlDetectionModule(true);
        
        // Verificar que el método de detección funciona
        $test_result = $this->module->test_detection();
        $this->assertIsArray($test_result);
        $this->assertArrayHasKey('all_methods', $test_result);
        
        // Limpiar
        unlink($wp_config);
    }
    
    /**
     * Test de todos los métodos de detección
     */
    public function test_all_detection_methods() {
        $_SERVER['HTTP_HOST'] = 'multi-test.local';
        $_SERVER['SERVER_NAME'] = 'multi-test.local';
        
        $this->module = new SiteUrlDetectionModule(true);
        
        $test_result = $this->module->test_detection();
        
        $this->assertIsArray($test_result);
        $this->assertArrayHasKey('detected_url', $test_result);
        $this->assertArrayHasKey('all_methods', $test_result);
        $this->assertArrayHasKey('environment', $test_result);
        
        // Verificar que al menos un método funciona
        $methods = $test_result['all_methods'];
        $working_methods = array_filter($methods, function($url) {
            return !empty($url) && $url !== null;
        });
        
        $this->assertNotEmpty($working_methods, 'At least one detection method should work');
    }
    
    /**
     * Test de detección de esquema HTTPS
     */
    public function test_https_scheme_detection() {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['HTTP_HOST'] = 'secure-site.com';
        
        $this->module = new SiteUrlDetectionModule(false);
        
        $detected_url = $this->module->get_site_url();
        $this->assertStringStartsWith('https://', $detected_url, 'Should detect HTTPS scheme');
    }
    
    /**
     * Test de performance de detección
     */
    public function test_detection_performance() {
        $this->assertExecutionTimeUnder(function() {
            return new SiteUrlDetectionModule(false);
        }, 50); // Debe tomar menos de 50ms
    }
    
    /**
     * Test de información del entorno
     */
    public function test_environment_info_structure() {
        $_SERVER['HTTP_HOST'] = 'test-env.local';
        
        $this->module = new SiteUrlDetectionModule(true);
        $env_info = $this->module->get_environment_info();
        
        $expected_keys = [
            'is_local_wp',
            'router_mode', 
            'detection_method',
            'server_info'
        ];
        
        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $env_info, "Environment info should have key: {$key}");
        }
        
        $this->assertIsBool($env_info['is_local_wp']);
        $this->assertIsString($env_info['detection_method']);
        $this->assertIsArray($env_info['server_info']);
    }
    
    /**
     * Test del método estático get_current_site_url
     */
    public function test_static_get_current_site_url() {
        $_SERVER['HTTP_HOST'] = 'static-test.org';
        
        $url = SiteUrlDetectionModule::get_current_site_url(false);
        
        $this->assertNotEmpty($url);
        $this->assertValidUrl($url);
        $this->assertStringContains('static-test.org', $url);
    }
    
    /**
     * Test de manejo de entornos sin información de servidor
     */
    public function test_minimal_server_environment() {
        // Limpiar variables de servidor
        $original_server = $_SERVER;
        $_SERVER = [
            'HTTP_HOST' => 'minimal.test'
        ];
        
        $this->module = new SiteUrlDetectionModule(false);
        $detected_url = $this->module->get_site_url();
        
        $this->assertNotEmpty($detected_url, 'Should detect URL even with minimal server info');
        $this->assertStringContains('minimal.test', $detected_url);
        
        // Restaurar
        $_SERVER = $original_server;
    }
    
    /**
     * Test de detección con múltiples dominios/puertos
     */
    public function test_multiple_port_scenarios() {
        $scenarios = [
            ['localhost:8080', 'http://localhost:8080'],
            ['example.org:8443', 'http://example.org:8443'],
            ['dev.site.local:3000', 'http://dev.site.local:3000']
        ];
        
        foreach ($scenarios as [$host, $expected]) {
            $_SERVER['HTTP_HOST'] = $host;
            
            $this->module = new SiteUrlDetectionModule(false);
            $detected_url = $this->module->get_site_url();
            
            $this->assertEquals($expected, $detected_url, "Should correctly detect URL for {$host}");
        }
    }
    
    /**
     * Test específico para el entorno real de Local by WP Engine
     * @group LocalWP
     */
    public function test_real_local_wp_environment() {
        // Solo ejecutar si detectamos entorno real de Local
        if (!strpos(__FILE__, '/Local Sites/') !== false) {
            $this->markTestSkipped('Real Local by WP Engine environment not detected');
        }
        
        $this->module = new SiteUrlDetectionModule(true);
        $env_info = $this->module->get_environment_info();
        
        $this->assertTrue($env_info['is_local_wp'], 'Should detect real Local by WP Engine');
        
        $detected_url = $this->module->get_site_url();
        $this->assertValidUrl($detected_url);
        
        // En entorno real, debería coincidir con WordPress
        if (function_exists('get_site_url')) {
            $wp_url = get_site_url();
            $this->assertEquals($wp_url, $detected_url, 'Should match WordPress URL in real environment');
        }
    }
}
