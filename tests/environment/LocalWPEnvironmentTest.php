<?php
/**
 * Tests de Entorno Local by WP Engine - Dev-Tools Arquitectura 3.0
 * 
 * Tests específicos para validar la configuración y estado del entorno de desarrollo
 * Local by WP Engine
 * 
 * @package DevTools
 * @subpackage Tests\Environment
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class LocalWPEnvironmentTest extends DevToolsTestCase {

    /**
     * Test: Verificar que estamos en entorno Local by WP Engine
     */
    public function test_local_wp_environment_detection() {
        // Verificar indicadores de Local by WP Engine
        $db_host = DB_HOST;
        
        // Local by WP Engine usa sockets Unix
        $this->assertStringContainsString('/Local/run/', $db_host, 
            'Debería estar usando socket de Local by WP Engine');
        
        // Verificar que es un socket válido
        $this->assertStringContainsString('.sock', $db_host, 
            'DB_HOST debería apuntar a un socket MySQL');
        
        // Verificar que el socket existe
        $socket_path = str_replace('localhost:', '', $db_host);
        $this->assertFileExists($socket_path, 
            'El socket MySQL debería existir en el sistema');
    }

    /**
     * Test: Verificar configuración de base de datos Local WP
     */
    public function test_local_wp_database_config() {
        // En entorno de testing, la configuración puede ser diferente
        if (defined('WP_TESTS_CONFIG_FILE')) {
            // Estamos en testing - verificar configuración de testing
            $this->assertEquals('local', DB_NAME, 'Database name en testing debería ser "local"');
            // En testing, la configuración puede variar
            $this->assertNotEmpty(DB_USER, 'Database user debería estar configurado');
        } else {
            // Configuración típica de Local by WP Engine en desarrollo
            $this->assertEquals('local', DB_NAME, 'Database name debería ser "local"');
            $this->assertEquals('root', DB_USER, 'Database user debería ser "root"');
            $this->assertEquals('', DB_PASSWORD, 'Database password debería estar vacío');
        }
        
        // Charset moderno
        $this->assertEquals('utf8mb4', DB_CHARSET, 'Debería usar UTF8MB4');
        $this->assertEquals('utf8mb4_unicode_ci', DB_COLLATE, 'Debería usar collation unicode');
    }

    /**
     * Test: Verificar URLs y paths del sitio local
     */
    public function test_local_wp_site_configuration() {
        $site_url = get_site_url();
        $home_url = get_home_url();
        
        // En entorno de testing, las URLs son diferentes
        if (defined('WP_TESTS_CONFIG_FILE')) {
            // Testing environment - verificar que no es producción
            $this->assertStringNotContainsString('tarokina.com', $site_url, 
                'En testing no debería apuntar a producción');
            $this->assertEquals($site_url, $home_url, 
                'Site URL y Home URL deberían ser iguales');
        } else {
            // Local development environment
            $this->assertStringContainsString('.local', $site_url, 
                'Site URL debería contener .local domain');
            $this->assertEquals($site_url, $home_url, 
                'Site URL y Home URL deberían ser iguales en desarrollo');
            
            // Verificar estructura de paths
            $upload_dir = wp_upload_dir();
            $this->assertStringContainsString('Local Sites', $upload_dir['basedir'], 
                'Upload directory debería estar en Local Sites');
        }
        
        // Verificar que no es producción
        $this->assertStringNotContainsString('https://tarokina.com', $site_url, 
            'No debería apuntar a producción');
    }

    /**
     * Test: Verificar configuración de desarrollo WordPress
     */
    public function test_wordpress_development_config() {
        // Debug debería estar activado en desarrollo
        $this->assertTrue(WP_DEBUG, 'WP_DEBUG debería estar activado');
        
        // WP_DEBUG_LOG puede no estar definido en testing
        if (defined('WP_DEBUG_LOG')) {
            $this->assertTrue(WP_DEBUG_LOG, 'WP_DEBUG_LOG debería estar activado');
        } else {
            // En entorno de testing, esto es opcional
            $this->assertTrue(true, 'WP_DEBUG_LOG no está definido en entorno de testing');
        }
        
        // Script debug para assets no minificados
        if (defined('SCRIPT_DEBUG')) {
            $this->assertTrue(SCRIPT_DEBUG, 'SCRIPT_DEBUG debería estar activado');
        }
        
        // Verificar que no estamos en ambiente de producción
        if (defined('WP_ENVIRONMENT_TYPE')) {
            $this->assertNotEquals('production', WP_ENVIRONMENT_TYPE, 
                'No debería estar en producción');
        }
    }

    /**
     * Test: Verificar memoria y límites PHP
     */
    public function test_php_memory_and_limits() {
        // Memoria mínima para desarrollo
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->parse_memory_limit($memory_limit);
        
        // -1 significa memoria ilimitada (válido en desarrollo)
        if ($memory_bytes == -1) {
            $this->assertTrue(true, 'Memory limit está configurado como ilimitado');
        } else {
            $this->assertGreaterThanOrEqual(128 * 1024 * 1024, $memory_bytes, 
                'Memory limit debería ser al menos 128MB');
        }
        
        // Max execution time apropiado para desarrollo
        $max_execution = ini_get('max_execution_time');
        if ($max_execution == 0) {
            $this->assertTrue(true, 'Max execution time está configurado como ilimitado');
        } else {
            $this->assertGreaterThanOrEqual(30, $max_execution, 
                'Max execution time debería ser al menos 30 segundos');
        }
        
        // Upload limits
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        
        $this->assertNotEmpty($upload_max, 'Upload max filesize debería estar configurado');
        $this->assertNotEmpty($post_max, 'Post max size debería estar configurado');
    }

    /**
     * Test: Verificar extensiones PHP requeridas
     */
    public function test_required_php_extensions() {
        $required_extensions = [
            'mysqli',     // Database
            'gd',         // Image processing
            'curl',       // HTTP requests
            'mbstring',   // Multibyte strings
            'json',       // JSON handling
            'zip',        // Archive handling
            'xml',        // XML parsing
            'intl',       // Internationalization
            'exif'        // Image metadata
        ];
        
        foreach ($required_extensions as $extension) {
            $this->assertTrue(extension_loaded($extension), 
                "Extensión PHP '{$extension}' debería estar cargada");
        }
    }

    /**
     * Test: Verificar versiones de software
     */
    public function test_software_versions() {
        // PHP version
        $php_version = PHP_VERSION;
        $this->assertGreaterThanOrEqual('8.0.0', $php_version, 
            'PHP debería ser versión 8.0 o superior');
        
        // WordPress version
        global $wp_version;
        $this->assertGreaterThanOrEqual('6.0', $wp_version, 
            'WordPress debería ser versión 6.0 o superior');
        
        // MySQL version
        global $wpdb;
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        $this->assertNotEmpty($mysql_version, 'Debería poder obtener versión de MySQL');
        
        // Extraer número de versión principal
        preg_match('/^(\d+\.\d+)/', $mysql_version, $matches);
        if (!empty($matches[1])) {
            $this->assertGreaterThanOrEqual('5.7', $matches[1], 
                'MySQL debería ser versión 5.7 o superior');
        }
    }

    /**
     * Test: Verificar permisos de archivos y directorios
     */
    public function test_file_permissions() {
        // Directorio de plugins debería ser escribible
        $plugins_dir = WP_PLUGIN_DIR;
        $this->assertTrue(is_writable($plugins_dir), 
            'Directorio de plugins debería ser escribible');
        
        // Directorio de uploads
        $upload_dir = wp_upload_dir();
        $this->assertTrue(is_writable($upload_dir['basedir']), 
            'Directorio de uploads debería ser escribible');
        
        // wp-config.php debería existir y ser legible
        $wp_config_path = ABSPATH . 'wp-config.php';
        $this->assertFileExists($wp_config_path, 'wp-config.php debería existir');
        $this->assertTrue(is_readable($wp_config_path), 'wp-config.php debería ser legible');
    }

    /**
     * Test: Verificar configuración de logs de error
     */
    public function test_error_logging_configuration() {
        // Verificar que log_errors está activado
        $this->assertTrue((bool)ini_get('log_errors'), 
            'Error logging debería estar activado');
        
        // Verificar ubicación del log de errores
        $error_log = ini_get('error_log');
        if (!empty($error_log)) {
            $log_dir = dirname($error_log);
            $this->assertTrue(is_writable($log_dir), 
                'Directorio de logs debería ser escribible');
        }
        
        // Verificar que WordPress debug log está configurado
        if (WP_DEBUG_LOG) {
            $wp_content_dir = WP_CONTENT_DIR;
            $debug_log_path = $wp_content_dir . '/debug.log';
            
            // Si existe, debería ser escribible
            if (file_exists($debug_log_path)) {
                $this->assertTrue(is_writable($debug_log_path), 
                    'WordPress debug.log debería ser escribible');
            }
        }
    }

    /**
     * Test: Verificar configuración SSL/TLS
     */
    public function test_ssl_configuration() {
        // En desarrollo local, normalmente no se usa SSL
        $is_ssl = is_ssl();
        
        if ($is_ssl) {
            // Si SSL está activo, verificar configuración
            $this->assertStringStartsWith('https://', get_site_url(), 
                'Site URL debería usar HTTPS si SSL está activo');
        } else {
            // En desarrollo local es normal no tener SSL
            $this->assertStringStartsWith('http://', get_site_url(), 
                'Site URL debería usar HTTP en desarrollo local');
        }
        
        // Verificar que openssl está disponible
        $this->assertTrue(extension_loaded('openssl'), 
            'Extensión OpenSSL debería estar disponible');
    }

    /**
     * Helper: Convertir memory_limit a bytes
     */
    private function parse_memory_limit($limit) {
        $limit = trim($limit);
        
        // -1 significa ilimitado
        if ($limit == '-1') {
            return -1;
        }
        
        $last = strtolower($limit[strlen($limit)-1]);
        $limit = (int) $limit;
        
        switch($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }

    /**
     * Test: Verificar herramientas de desarrollo disponibles
     */
    public function test_development_tools_availability() {
        // Verificar que Composer está disponible (vendor directory)
        $vendor_dir = dirname(__DIR__, 2) . '/vendor';
        $this->assertDirectoryExists($vendor_dir, 
            'Directorio vendor de Composer debería existir');
        
        // Verificar autoloader de Composer
        $autoloader = $vendor_dir . '/autoload.php';
        $this->assertFileExists($autoloader, 
            'Autoloader de Composer debería existir');
        
        // Verificar que PHPUnit está instalado
        $phpunit_binary = $vendor_dir . '/bin/phpunit';
        $this->assertFileExists($phpunit_binary, 
            'PHPUnit binary debería estar instalado');
        
        // Verificar que wp-phpunit está disponible
        $wp_phpunit_dir = $vendor_dir . '/wp-phpunit';
        $this->assertDirectoryExists($wp_phpunit_dir, 
            'wp-phpunit debería estar instalado');
    }
}
