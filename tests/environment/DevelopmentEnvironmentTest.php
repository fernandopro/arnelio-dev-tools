<?php
/**
 * Tests de Entorno de Desarrollo Real - Dev-Tools Arquitectura 3.0
 * 
 * Tests que SOLO se ejecutan en entorno de desarrollo real (no en testing)
 * Usar: ./vendor/bin/phpunit tests/environment/DevelopmentEnvironmentTest.php
 * 
 * @package DevTools
 * @subpackage Tests\Environment
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class DevelopmentEnvironmentTest extends DevToolsTestCase {

    public function setUp(): void {
        parent::setUp();
        
        // Solo ejecutar estos tests en entorno de desarrollo real
        if (defined('WP_TESTS_CONFIG_FILE') || $this->isTestingEnvironment()) {
            $this->markTestSkipped('Tests de desarrollo solo se ejecutan en entorno real, no en testing');
        }
    }

    /**
     * Test: Verificar que estamos en Local by WP Engine real
     */
    public function test_real_local_wp_environment() {
        $db_host = DB_HOST;
        
        // Debe usar socket de Local by WP Engine
        $this->assertStringContainsString('/Local/run/', $db_host, 
            'Entorno real debe usar socket de Local by WP Engine');
        
        $this->assertStringContainsString('.sock', $db_host, 
            'DB_HOST debe apuntar a socket MySQL');
        
        // El socket debe existir físicamente
        $socket_path = str_replace('localhost:', '', $db_host);
        $this->assertFileExists($socket_path, 
            'Socket MySQL debe existir en el sistema');
        
        echo "\n🔧 Socket detectado: {$socket_path}\n";
    }

    /**
     * Test: Configuración de base de datos en desarrollo real
     */
    public function test_real_database_configuration() {
        // En desarrollo real con Local by WP Engine
        $this->assertEquals('local', DB_NAME, 'Database name debe ser "local"');
        $this->assertEquals('root', DB_USER, 'Database user debe ser "root"');
        $this->assertEquals('', DB_PASSWORD, 'Database password debe estar vacío');
        
        // Charset moderno
        $this->assertEquals('utf8mb4', DB_CHARSET, 'Debe usar UTF8MB4');
        $this->assertEquals('utf8mb4_unicode_ci', DB_COLLATE, 'Debe usar collation unicode');
        
        echo "\n📊 BD configurada: " . DB_NAME . " con usuario " . DB_USER . "\n";
    }

    /**
     * Test: URLs del sitio en desarrollo real
     */
    public function test_real_site_urls() {
        $site_url = get_site_url();
        $home_url = get_home_url();
        
        // Debe contener .local domain
        $this->assertStringContainsString('.local', $site_url, 
            'Site URL debe contener .local domain en desarrollo');
        
        $this->assertEquals($site_url, $home_url, 
            'Site URL y Home URL deben ser iguales');
        
        // Verificar que no es producción
        $this->assertStringNotContainsString('tarokina.com', $site_url, 
            'No debe apuntar a producción');
        
        // Verificar estructura de paths Local Sites
        $upload_dir = wp_upload_dir();
        $this->assertStringContainsString('Local Sites', $upload_dir['basedir'], 
            'Upload directory debe estar en Local Sites');
        
        echo "\n🌍 Site URL: {$site_url}\n";
        echo "📁 Upload dir: {$upload_dir['basedir']}\n";
    }

    /**
     * Test: Node.js y NPM en entorno real
     */
    public function test_real_nodejs_environment() {
        // Node.js version
        $node_version = trim(shell_exec('node --version 2>/dev/null') ?? '');
        $this->assertNotEmpty($node_version, 'Node.js debe estar instalado');
        
        $version = str_replace('v', '', $node_version);
        $this->assertTrue(version_compare($version, '16.0.0', '>='), 
            "Node.js debe ser 16.0+ (actual: {$version})");
        
        // NPM version
        $npm_version = trim(shell_exec('npm --version 2>/dev/null') ?? '');
        $this->assertNotEmpty($npm_version, 'NPM debe estar instalado');
        
        $this->assertTrue(version_compare($npm_version, '8.0.0', '>='), 
            "NPM debe ser 8.0+ (actual: {$npm_version})");
        
        echo "\n🟢 Node.js: {$node_version}\n";
        echo "📦 NPM: {$npm_version}\n";
    }

    /**
     * Test: Verificar que node_modules está instalado en dev-tools
     */
    public function test_real_node_modules() {
        $dev_tools_path = $this->get_dev_tools_path();
        $node_modules_path = $dev_tools_path . '/node_modules';
        
        if (is_dir($node_modules_path)) {
            $this->assertDirectoryExists($node_modules_path, 
                'node_modules debe existir en dev-tools');
            
            // Verificar algunas dependencias críticas
            $critical_deps = ['webpack', '@babel/core', 'css-loader'];
            $found_deps = [];
            
            foreach ($critical_deps as $dep) {
                $dep_path = $node_modules_path . '/' . $dep;
                if (is_dir($dep_path)) {
                    $found_deps[] = $dep;
                }
            }
            
            echo "\n📚 Node modules encontrados: " . implode(', ', $found_deps) . "\n";
            
            $this->assertGreaterThan(0, count($found_deps), 
                'Debe haber al menos una dependencia instalada');
        } else {
            echo "\n⚠️  node_modules no encontrado - ejecutar 'npm install' en dev-tools\n";
            $this->markTestSkipped('node_modules no instalado - ejecutar npm install');
        }
    }

    /**
     * Test: Verificar configuración de debug en desarrollo real
     */
    public function test_real_debug_configuration() {
        // WP_DEBUG debe estar activo en desarrollo
        $this->assertTrue(WP_DEBUG, 'WP_DEBUG debe estar activado en desarrollo');
        
        // Reportar estado de WP_DEBUG_LOG
        if (defined('WP_DEBUG_LOG')) {
            $debug_log_active = WP_DEBUG_LOG;
            echo "\n🔍 WP_DEBUG_LOG: " . ($debug_log_active ? 'ACTIVO' : 'INACTIVO') . "\n";
        } else {
            echo "\n⚠️  WP_DEBUG_LOG no está definido\n";
        }
        
        // Reportar SCRIPT_DEBUG
        if (defined('SCRIPT_DEBUG')) {
            $script_debug = SCRIPT_DEBUG;
            echo "📜 SCRIPT_DEBUG: " . ($script_debug ? 'ACTIVO' : 'INACTIVO') . "\n";
        }
        
        // Verificar environment type
        if (defined('WP_ENVIRONMENT_TYPE')) {
            $env_type = WP_ENVIRONMENT_TYPE;
            echo "🏗️  WP_ENVIRONMENT_TYPE: {$env_type}\n";
            $this->assertNotEquals('production', $env_type, 
                'No debe estar en producción');
        }
    }

    /**
     * Test: Verificar herramientas de build en desarrollo real
     */
    public function test_real_build_tools() {
        $dev_tools_path = $this->get_dev_tools_path();
        
        // Package.json debe existir
        $package_json = $dev_tools_path . '/package.json';
        $this->assertFileExists($package_json, 'package.json debe existir');
        
        // Webpack config
        $webpack_config = $dev_tools_path . '/webpack.config.js';
        $this->assertFileExists($webpack_config, 'webpack.config.js debe existir');
        
        // Intentar ejecutar npm run dev (dry run)
        $original_dir = getcwd();
        chdir($dev_tools_path);
        
        try {
            $output = shell_exec('npm run dev --dry-run 2>&1');
            echo "\n🔨 Build test output:\n" . substr($output, 0, 200) . "...\n";
            
            // No debería tener errores críticos
            $this->assertStringNotContainsString('Error:', $output, 
                'Build command no debe tener errores críticos');
            
        } finally {
            chdir($original_dir);
        }
    }

    /**
     * Test: Verificar logs de error en entorno real
     */
    public function test_real_error_logs() {
        // PHP error log
        $php_error_log = ini_get('error_log');
        if (!empty($php_error_log)) {
            echo "\n📝 PHP Error Log: {$php_error_log}\n";
            
            // Verificar si el archivo existe y su tamaño
            if (file_exists($php_error_log)) {
                $log_size = filesize($php_error_log);
                echo "📊 Tamaño del log: " . number_format($log_size) . " bytes\n";
                
                if ($log_size > 0) {
                    // Mostrar últimas líneas del log
                    $last_lines = shell_exec("tail -3 '{$php_error_log}' 2>/dev/null");
                    if (!empty($last_lines)) {
                        echo "🔍 Últimas entradas:\n" . $last_lines . "\n";
                    }
                }
            }
        }
        
        // WordPress debug log
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $wp_debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($wp_debug_log)) {
                $wp_log_size = filesize($wp_debug_log);
                echo "📋 WordPress Debug Log: " . number_format($wp_log_size) . " bytes\n";
            }
        }
        
        $this->assertTrue(true, 'Log information displayed');
    }

    /**
     * Helper: Detectar si estamos en entorno de testing
     */
    private function isTestingEnvironment(): bool {
        // Indicadores de entorno de testing
        $testing_indicators = [
            defined('WP_TESTS_CONFIG_FILE'),
            get_site_url() === 'http://example.org',
            strpos(__FILE__, 'vendor/wp-phpunit') !== false,
            defined('WP_TESTS_TABLE_PREFIX')
        ];
        
        return in_array(true, $testing_indicators, true);
    }

    /**
     * Test: Resumen del entorno de desarrollo
     */
    public function test_development_environment_summary() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🏗️  RESUMEN DEL ENTORNO DE DESARROLLO\n";
        echo str_repeat("=", 60) . "\n";
        
        // Información básica
        echo "🐘 PHP: " . PHP_VERSION . "\n";
        echo "🔗 WordPress: " . get_bloginfo('version') . "\n";
        echo "🌍 Site URL: " . get_site_url() . "\n";
        echo "🗄️  Database: " . DB_NAME . " (" . DB_USER . ")\n";
        echo "🔧 Socket: " . DB_HOST . "\n";
        
        // Configuración de debug
        echo "🔍 WP_DEBUG: " . (WP_DEBUG ? 'ON' : 'OFF') . "\n";
        echo "📝 WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ON' : 'OFF') . "\n";
        
        // Herramientas
        $node_version = trim(shell_exec('node --version 2>/dev/null') ?? 'N/A');
        $npm_version = trim(shell_exec('npm --version 2>/dev/null') ?? 'N/A');
        echo "🟢 Node.js: {$node_version}\n";
        echo "📦 NPM: {$npm_version}\n";
        
        // Paths importantes
        echo "📁 Plugin Dir: " . WP_PLUGIN_DIR . "\n";
        echo "📁 Upload Dir: " . wp_upload_dir()['basedir'] . "\n";
        echo "📁 Dev-Tools: " . $this->get_dev_tools_path() . "\n";
        
        echo str_repeat("=", 60) . "\n";
        
        $this->assertTrue(true, 'Environment summary displayed');
    }
}
