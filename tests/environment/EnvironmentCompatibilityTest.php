<?php
/**
 * Test: Environment Compatibility Tests
 * 
 * Tests específicos para verificar compatibilidad con diferentes entornos
 * Especialmente diseñado para Local by WP Engine
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

class EnvironmentCompatibilityTest extends DevToolsTestCase {
    
    /**
     * Test de compatibilidad con Local by WP Engine
     */
    public function test_local_wp_engine_compatibility() {
        // Verificar si estamos en un entorno real de Local by WP Engine
        $is_real_local = $this->detect_real_local_wp_engine();
        
        if (!$is_real_local) {
            $this->markTestSkipped('Not running in real Local by WP Engine environment');
        }
        
        // Test de estructura de archivos de Local
        $this->assert_local_wp_file_structure();
        
        // Test de configuración de MySQL
        $this->assert_local_wp_mysql_config();
        
        // Test de detección de Router Mode
        $this->assert_local_wp_router_mode();
    }
    
    /**
     * Test de entorno de desarrollo general
     */
    public function test_development_environment() {
        // Verificar configuraciones típicas de desarrollo
        $this->assertTrue(defined('WP_DEBUG'), 'WP_DEBUG should be defined in development');
        
        // Verificar extensiones PHP necesarias
        $required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
        
        foreach ($required_extensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "PHP extension {$extension} should be available"
            );
        }
        
        // Verificar versión de PHP
        $php_version = phpversion();
        $this->assertTrue(
            version_compare($php_version, '7.4.0', '>='),
            'PHP version should be 7.4 or higher'
        );
    }
    
    /**
     * Test de entorno macOS específico
     */
    public function test_macos_environment() {
        // Solo ejecutar en macOS
        if (PHP_OS !== 'Darwin') {
            $this->markTestSkipped('Not running on macOS');
        }
        
        // Verificar rutas típicas de macOS
        $typical_paths = [
            '/Users',
            '/Applications',
            '/System',
            '/Library'
        ];
        
        foreach ($typical_paths as $path) {
            $this->assertDirectoryExists($path, "macOS path {$path} should exist");
        }
        
        // Verificar configuración específica de Local by WP Engine en macOS
        $local_base_path = '/Users/' . get_current_user() . '/Library/Application Support/Local';
        
        if (is_dir($local_base_path)) {
            $this->assertDirectoryExists($local_base_path, 'Local by WP Engine base directory should exist');
            
            // Verificar estructura de Local
            $local_run_path = $local_base_path . '/run';
            if (is_dir($local_run_path)) {
                $this->assertDirectoryExists($local_run_path, 'Local run directory should exist');
            }
        }
    }
    
    /**
     * Test de compatibilidad con diferentes versiones de WordPress
     */
    public function test_wordpress_version_compatibility() {
        $wp_version = get_bloginfo('version');
        
        // Verificar versión mínima soportada
        $this->assertTrue(
            version_compare($wp_version, '5.0', '>='),
            'WordPress 5.0+ required for full compatibility'
        );
        
        // Verificar funciones específicas necesarias
        $required_functions = [
            'wp_enqueue_script',
            'wp_enqueue_style', 
            'add_action',
            'add_filter',
            'wp_create_nonce',
            'current_user_can',
            'get_site_url',
            'plugins_url'
        ];
        
        foreach ($required_functions as $function) {
            $this->assertTrue(
                function_exists($function),
                "WordPress function {$function} should be available"
            );
        }
    }
    
    /**
     * Test de configuración de base de datos
     */
    public function test_database_configuration() {
        // Verificar constantes de WordPress DB
        $db_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        
        foreach ($db_constants as $constant) {
            $this->assertTrue(
                defined($constant),
                "Database constant {$constant} should be defined"
            );
        }
        
        // Test específico para Local by WP Engine
        if ($this->detect_real_local_wp_engine()) {
            $this->assertEquals('localhost', DB_HOST, 'Local by WP Engine should use localhost');
            $this->assertEquals('root', DB_USER, 'Local by WP Engine typically uses root user');
        }
    }
    
    /**
     * Test de permisos de archivos
     */
    public function test_file_permissions() {
        // Verificar que podemos crear archivos temporales
        $temp_file = tempnam(sys_get_temp_dir(), 'dev_tools_permission_test');
        $this->assertNotFalse($temp_file, 'Should be able to create temporary files');
        
        // Verificar escritura
        $test_content = 'test content';
        $write_result = file_put_contents($temp_file, $test_content);
        $this->assertNotFalse($write_result, 'Should be able to write to temporary files');
        
        // Verificar lectura
        $read_content = file_get_contents($temp_file);
        $this->assertEquals($test_content, $read_content, 'Should be able to read from temporary files');
        
        // Limpiar
        unlink($temp_file);
    }
    
    /**
     * Test de conectividad de red
     */
    public function test_network_connectivity() {
        // Test de conectividad básica (si hay internet)
        $test_urls = [
            'https://api.wordpress.org/core/version-check/1.7/',
            'https://cdn.jsdelivr.net'
        ];
        
        foreach ($test_urls as $url) {
            $headers = @get_headers($url, 1);
            
            if ($headers !== false) {
                $this->assertStringContainsString('200', $headers[0], "Should be able to connect to {$url}");
            } else {
                // Si no hay conectividad, solo advertir
                $this->markTestIncomplete("Network connectivity test for {$url} failed - this may be expected in isolated environments");
            }
        }
    }
    
    /**
     * Test de configuración de memoria PHP
     */
    public function test_php_memory_configuration() {
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->convert_memory_limit_to_bytes($memory_limit);
        
        // Verificar que hay suficiente memoria (mínimo 128MB)
        $this->assertGreaterThanOrEqual(
            128 * 1024 * 1024,
            $memory_bytes,
            'PHP memory limit should be at least 128MB'
        );
        
        // Verificar límite de tiempo de ejecución
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0) { // 0 significa sin límite
            $this->assertGreaterThanOrEqual(
                30,
                $max_execution_time,
                'Max execution time should be at least 30 seconds'
            );
        }
    }
    
    /**
     * Test de configuración de debugging
     */
    public function test_debug_configuration() {
        // En entorno de desarrollo, debugging debería estar habilitado
        if ($this->detect_real_local_wp_engine()) {
            $this->assertTrue(
                defined('WP_DEBUG') && WP_DEBUG,
                'WP_DEBUG should be enabled in Local by WP Engine'
            );
            
            if (defined('WP_DEBUG_LOG')) {
                $this->assertTrue(WP_DEBUG_LOG, 'WP_DEBUG_LOG should be enabled');
            }
        }
    }
    
    /**
     * Detectar si estamos en un entorno real de Local by WP Engine
     */
    private function detect_real_local_wp_engine() {
        $indicators = [
            // Path característico
            strpos(__FILE__, '/Local Sites/') !== false,
            // Usuario de sistema típico
            strpos(get_current_user(), 'fernandovazquezperez') !== false,
            // Socket MySQL específico
            file_exists('/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock'),
            // Host característico
            defined('DB_HOST') && DB_HOST === 'localhost',
            // Estructura de directorios
            is_dir('/Users/' . get_current_user() . '/Library/Application Support/Local')
        ];
        
        return count(array_filter($indicators)) >= 2;
    }
    
    /**
     * Verificar estructura de archivos de Local by WP Engine
     */
    private function assert_local_wp_file_structure() {
        $base_user_path = '/Users/' . get_current_user();
        
        // Verificar directorios base de Local
        $local_paths = [
            $base_user_path . '/Library/Application Support/Local',
            $base_user_path . '/Local Sites'
        ];
        
        foreach ($local_paths as $path) {
            if (is_dir($path)) {
                $this->assertDirectoryExists($path, "Local by WP Engine path {$path} should exist");
            }
        }
        
        // Verificar logs si el directorio existe
        $logs_path = dirname(__FILE__, 6) . '/logs';
        if (is_dir($logs_path)) {
            $this->assertDirectoryExists($logs_path, 'Local by WP Engine logs directory should exist');
        }
    }
    
    /**
     * Verificar configuración MySQL de Local by WP Engine
     */
    private function assert_local_wp_mysql_config() {
        // Socket específico del proyecto
        $socket_path = '/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock';
        
        if (file_exists($socket_path)) {
            $this->assertFileExists($socket_path, 'MySQL socket should exist');
            
            // Verificar permisos del socket
            $this->assertTrue(is_readable($socket_path), 'MySQL socket should be readable');
        }
        
        // Verificar configuración de DB
        $this->assertEquals('localhost', DB_HOST, 'Local by WP Engine should use localhost');
        $this->assertEquals('root', DB_USER, 'Local by WP Engine typically uses root');
    }
    
    /**
     * Verificar detección de Router Mode
     */
    private function assert_local_wp_router_mode() {
        require_once dirname(__DIR__, 2) . '/modules/SiteUrlDetectionModule.php';
        
        $url_module = new SiteUrlDetectionModule(true);
        $env_info = $url_module->get_environment_info();
        
        $this->assertTrue($env_info['is_local_wp'], 'Should detect Local by WP Engine environment');
        $this->assertContains($env_info['router_mode'], ['site_domains', 'localhost'], 'Should detect valid router mode');
    }
    
    /**
     * Convertir límite de memoria a bytes
     */
    private function convert_memory_limit_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $value = (int) $memory_limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}
