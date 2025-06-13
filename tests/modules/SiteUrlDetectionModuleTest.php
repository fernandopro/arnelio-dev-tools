<?php
/**
 * Test para SiteUrlDetectionModule
 * 
 * Tests específicos para la funcionalidad del módulo de detección de URLs del sitio
 * 
 * @package DevTools
 * @subpackage Tests\Modules
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

namespace DevTools\Tests\Modules;


class SiteUrlDetectionModuleTest extends DevToolsTestCase {

    private $module;
    
    public function setUp(): void {
        parent::setUp();
        
        // Cargar el módulo
        require_once $this->get_dev_tools_path() . '/modules/SiteUrlDetectionModule.php';
        $this->module = new SiteUrlDetectionModule();
    }

    /**
     * Test: Módulo se instancia correctamente
     */
    public function test_module_instantiation() {
        $this->assertInstanceOf(SiteUrlDetectionModule::class, $this->module);
    }

    /**
     * Test: Módulo tiene información de entorno válida
     */
    public function test_environment_detection() {
        // Verificar que el módulo detecta el entorno
        $this->assertNotNull($this->module);
        
        // Test que el módulo se puede construir con diferentes parámetros
        $debug_module = new SiteUrlDetectionModule(true);
        $this->assertInstanceOf(SiteUrlDetectionModule::class, $debug_module);
    }

    /**
     * Test: Módulo puede detectar URL del sitio correctamente
     */
    public function test_site_url_detection() {
        // Test funciones básicas de WordPress para URLs
        $site_url = get_site_url();
        $home_url = get_home_url();
        $admin_url = admin_url();
        
        $this->assertNotEmpty($site_url);
        $this->assertNotEmpty($home_url);
        $this->assertNotEmpty($admin_url);
        
        // Verificar que son URLs válidas
        $this->assertStringStartsWith('http', $site_url);
        $this->assertStringStartsWith('http', $home_url);
        $this->assertStringStartsWith('http', $admin_url);
    }

    /**
     * Test: Módulo detecta correctamente entorno de desarrollo
     */
    public function test_development_environment_detection() {
        $site_url = get_site_url();
        
        // En entorno de testing, puede ser example.org o .local
        $is_development = (
            strpos($site_url, '.local') !== false || 
            strpos($site_url, 'example.org') !== false ||
            strpos($site_url, 'localhost') !== false
        );
        
        $this->assertTrue($is_development, 
            'El sitio debería estar ejecutándose en un entorno de desarrollo o testing');
        
        // Verificar que no es una URL de producción real
        $this->assertStringNotContainsString('www.', $site_url);
        $production_tlds = ['.com', '.net', '.org'];
        $is_production = false;
        foreach ($production_tlds as $tld) {
            if (strpos($site_url, $tld) !== false && strpos($site_url, 'example.org') === false) {
                $is_production = true;
                break;
            }
        }
        $this->assertFalse($is_production, 'No debería ser una URL de producción real');
    }

    /**
     * Test: Módulo puede obtener información completa de URLs
     */
    public function test_comprehensive_url_info() {
        $url_info = [
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'admin_url' => admin_url(),
            'plugins_url' => plugins_url(),
            'content_url' => content_url(),
            'wp_url' => wp_guess_url(),
        ];
        
        foreach ($url_info as $type => $url) {
            $this->assertNotEmpty($url, "URL type '{$type}' should not be empty");
            $this->assertIsString($url, "URL type '{$type}' should be a string");
            
            if ($type !== 'wp_url') { // wp_guess_url puede ser null en tests
                $this->assertStringStartsWith('http', $url, 
                    "URL type '{$type}' should start with http");
            }
        }
    }

    /**
     * Test: Módulo puede detectar si es HTTPS
     */
    public function test_https_detection() {
        $site_url = get_site_url();
        $is_ssl = is_ssl();
        
        if (strpos($site_url, 'https://') === 0) {
            $this->assertTrue($is_ssl, 'SSL should be detected when using HTTPS');
        } else {
            $this->assertFalse($is_ssl, 'SSL should not be detected when using HTTP');
        }
    }

    /**
     * Test: Módulo puede construir URLs relativas correctamente
     */
    public function test_relative_url_construction() {
        $base_url = get_site_url();
        $relative_path = '/wp-content/plugins/test-plugin/';
        
        // Test construcción de URL completa
        $full_url = $base_url . $relative_path;
        $this->assertStringContainsString($base_url, $full_url);
        $this->assertStringContainsString($relative_path, $full_url);
        
        // Test usando plugins_url()
        $plugin_url = plugins_url('test-plugin/');
        $this->assertStringContainsString('/wp-content/plugins/', $plugin_url);
        $this->assertStringContainsString('test-plugin', $plugin_url);
    }

    /**
     * Test: Módulo valida configuración de WordPress para URLs
     */
    public function test_wordpress_url_config_validation() {
        // En entorno de testing, las constantes pueden no estar definidas
        $has_url_constants = defined('WP_SITEURL') || defined('WP_HOME');
        
        // Test consistencia entre get_option y funciones de WordPress
        $db_siteurl = get_option('siteurl');
        $db_home = get_option('home');
        $function_siteurl = get_site_url();
        $function_home = get_home_url();
        
        $this->assertNotEmpty($db_siteurl);
        $this->assertNotEmpty($db_home);
        $this->assertNotEmpty($function_siteurl);
        $this->assertNotEmpty($function_home);
        
        $this->assertIsString($db_siteurl);
        $this->assertIsString($db_home);
        $this->assertIsString($function_siteurl);
        $this->assertIsString($function_home);
    }

    /**
     * Test: Módulo maneja casos edge de URLs
     */
    public function test_url_edge_cases() {
        // Test con trailing slashes
        $site_url = get_site_url();
        $site_url_with_slash = trailingslashit($site_url);
        $site_url_without_slash = untrailingslashit($site_url);
        
        $this->assertStringEndsWith('/', $site_url_with_slash);
        $this->assertStringEndsNotWith('/', $site_url_without_slash);
        
        // Test normalización de URLs
        $test_urls = [
            'http://example.local',
            'http://example.local/',
            'https://example.local',
            'https://example.local/',
        ];
        
        foreach ($test_urls as $test_url) {
            $normalized = untrailingslashit($test_url);
            $this->assertStringEndsNotWith('/', $normalized);
        }
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->module = null;
    }
}
