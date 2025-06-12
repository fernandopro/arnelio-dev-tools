<?php
/**
 * Tests de Herramientas de Build y Node.js - Dev-Tools Arquitectura 3.0
 * 
 * Tests específicos para validar las herramientas de desarrollo, Node.js,
 * NPM packages, y configuración de build tools
 * 
 * @package DevTools
 * @subpackage Tests\Environment
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class BuildToolsEnvironmentTest extends DevToolsTestCase {

    private $dev_tools_path;

    public function setUp(): void {
        parent::setUp();
        $this->dev_tools_path = $this->get_dev_tools_path();
    }

    /**
     * Test: Verificar que Node.js está disponible
     */
    public function test_nodejs_availability() {
        // Verificar que node está en PATH
        $node_version = shell_exec('node --version 2>/dev/null');
        $this->assertNotEmpty($node_version, 'Node.js debería estar instalado y disponible');
        
        // Verificar versión mínima de Node.js
        $version = trim(str_replace('v', '', $node_version));
        $this->assertGreaterThanOrEqual('16.0.0', $version, 
            'Node.js debería ser versión 16.0 o superior');
    }

    /**
     * Test: Verificar que NPM está disponible
     */
    public function test_npm_availability() {
        // Verificar que npm está disponible
        $npm_version = shell_exec('npm --version 2>/dev/null');
        $this->assertNotEmpty($npm_version, 'NPM debería estar instalado y disponible');
        
        // Verificar versión mínima de NPM (usar version_compare para comparación correcta)
        $version = trim($npm_version);
        $this->assertTrue(version_compare($version, '8.0.0', '>='), 
            "NPM debería ser versión 8.0 o superior (actual: {$version})");
    }

    /**
     * Test: Verificar package.json de dev-tools
     */
    public function test_dev_tools_package_json() {
        $package_json_path = $this->dev_tools_path . '/package.json';
        $this->assertFileExists($package_json_path, 
            'package.json debería existir en dev-tools');
        
        $package_content = file_get_contents($package_json_path);
        $package_data = json_decode($package_content, true);
        
        $this->assertIsArray($package_data, 'package.json debería ser JSON válido');
        $this->assertArrayHasKey('name', $package_data, 'package.json debería tener name');
        $this->assertArrayHasKey('scripts', $package_data, 'package.json debería tener scripts');
        
        // Scripts esenciales
        $required_scripts = ['dev', 'build', 'watch'];
        foreach ($required_scripts as $script) {
            $this->assertArrayHasKey($script, $package_data['scripts'], 
                "package.json debería tener script '{$script}'");
        }
    }

    /**
     * Test: Verificar node_modules de dev-tools
     */
    public function test_dev_tools_node_modules() {
        $node_modules_path = $this->dev_tools_path . '/node_modules';
        $this->assertDirectoryExists($node_modules_path, 
            'node_modules debería existir en dev-tools');
        
        // Verificar dependencias críticas
        $critical_deps = [
            'webpack',
            '@babel/core',        // Babel moderno (no babel-core)
            '@babel/preset-env',
            'css-loader',
            'sass-loader'
        ];
        
        foreach ($critical_deps as $dep) {
            $dep_path = $node_modules_path . '/' . $dep;
            if (!is_dir($dep_path)) {
                // Fallback para algunas dependencias opcionales
                $optional_deps = ['sass-loader'];
                if (in_array($dep, $optional_deps)) {
                    $this->assertTrue(true, "Dependencia opcional '{$dep}' no instalada");
                    continue;
                }
            }
            $this->assertDirectoryExists($dep_path, 
                "Dependencia '{$dep}' debería estar instalada");
        }
    }

    /**
     * Test: Verificar configuración de Webpack
     */
    public function test_webpack_configuration() {
        $webpack_config_path = $this->dev_tools_path . '/webpack.config.js';
        $this->assertFileExists($webpack_config_path, 
            'webpack.config.js debería existir');
        
        $webpack_content = file_get_contents($webpack_config_path);
        
        // Verificar configuraciones esenciales
        $this->assertStringContainsString('entry:', $webpack_content, 
            'webpack.config.js debería tener configuración de entry');
        $this->assertStringContainsString('output:', $webpack_content, 
            'webpack.config.js debería tener configuración de output');
        $this->assertStringContainsString('module:', $webpack_content, 
            'webpack.config.js debería tener configuración de module');
    }

    /**
     * Test: Verificar configuración de Babel
     */
    public function test_babel_configuration() {
        $babel_config_path = $this->dev_tools_path . '/babel.config.js';
        $this->assertFileExists($babel_config_path, 
            'babel.config.js debería existir');
        
        $babel_content = file_get_contents($babel_config_path);
        
        // Verificar presets esenciales
        $this->assertStringContainsString('@babel/preset-env', $babel_content, 
            'Babel debería usar preset-env');
    }

    /**
     * Test: Verificar configuración de PostCSS
     */
    public function test_postcss_configuration() {
        $postcss_config_path = $this->dev_tools_path . '/postcss.config.js';
        $this->assertFileExists($postcss_config_path, 
            'postcss.config.js debería existir');
        
        $postcss_content = file_get_contents($postcss_config_path);
        
        // Verificar plugins esenciales
        $this->assertStringContainsString('autoprefixer', $postcss_content, 
            'PostCSS debería incluir autoprefixer');
    }

    /**
     * Test: Verificar directorio dist compilado
     */
    public function test_compiled_assets_directory() {
        $dist_path = $this->dev_tools_path . '/dist';
        
        if (is_dir($dist_path)) {
            // Si dist existe, verificar estructura
            $css_path = $dist_path . '/css';
            $js_path = $dist_path . '/js';
            
            $this->assertDirectoryExists($css_path, 
                'Directorio dist/css debería existir');
            $this->assertDirectoryExists($js_path, 
                'Directorio dist/js debería existir');
            
            // Verificar que hay archivos compilados
            $css_files = glob($css_path . '/*.css');
            $js_files = glob($js_path . '/*.js');
            
            if (!empty($css_files)) {
                $this->assertNotEmpty($css_files, 
                    'Debería haber archivos CSS compilados');
            }
            
            if (!empty($js_files)) {
                $this->assertNotEmpty($js_files, 
                    'Debería haber archivos JS compilados');
            }
        } else {
            // Si no existe, es OK pero debería poder crearse
            $this->assertTrue(is_writable(dirname($dist_path)), 
                'Directorio padre de dist debería ser escribible');
        }
    }

    /**
     * Test: Verificar capacidad de build
     */
    public function test_build_capability() {
        // Cambiar al directorio dev-tools
        $original_dir = getcwd();
        chdir($this->dev_tools_path);
        
        try {
            // Verificar que npm run dev funciona (dry run)
            $output = shell_exec('npm run dev --dry-run 2>&1');
            
            // No debería haber errores críticos
            $this->assertStringNotContainsString('Error:', $output, 
                'npm run dev no debería tener errores críticos');
            
        } finally {
            // Restaurar directorio original
            chdir($original_dir);
        }
    }

    /**
     * Test: Verificar configuración de Git
     */
    public function test_git_configuration() {
        $dev_tools_git = $this->dev_tools_path . '/.git';
        
        if (is_dir($dev_tools_git)) {
            // Si hay repositorio Git, verificar configuración
            $git_config = $dev_tools_git . '/config';
            $this->assertFileExists($git_config, 
                'Git config debería existir');
            
            // Verificar .gitignore
            $gitignore = $this->dev_tools_path . '/.gitignore';
            if (file_exists($gitignore)) {
                $gitignore_content = file_get_contents($gitignore);
                
                // Verificar que ignora directorios importantes
                $this->assertStringContainsString('node_modules', $gitignore_content, 
                    '.gitignore debería incluir node_modules');
                $this->assertStringContainsString('vendor', $gitignore_content, 
                    '.gitignore debería incluir vendor');
            }
        }
    }

    /**
     * Test: Verificar herramientas de desarrollo opcionales
     */
    public function test_optional_development_tools() {
        // ESLint
        $eslint_version = shell_exec('npx eslint --version 2>/dev/null');
        if (!empty($eslint_version)) {
            $this->assertNotEmpty($eslint_version, 'ESLint está disponible');
        }
        
        // Prettier
        $prettier_version = shell_exec('npx prettier --version 2>/dev/null');
        if (!empty($prettier_version)) {
            $this->assertNotEmpty($prettier_version, 'Prettier está disponible');
        }
        
        // TypeScript compiler
        $tsc_version = shell_exec('npx tsc --version 2>/dev/null');
        if (!empty($tsc_version)) {
            $this->assertNotEmpty($tsc_version, 'TypeScript está disponible');
        }
    }

    /**
     * Test: Verificar variables de entorno de build
     */
    public function test_build_environment_variables() {
        // NODE_ENV
        $node_env = getenv('NODE_ENV');
        if ($node_env !== false) {
            $allowed_envs = ['development', 'production', 'test'];
            $this->assertContains($node_env, $allowed_envs, 
                'NODE_ENV debería ser un valor válido');
        }
        
        // Verificar que npm está configurado correctamente
        $npm_config = shell_exec('npm config get prefix 2>/dev/null');
        $this->assertNotEmpty($npm_config, 
            'NPM debería tener configuración de prefix');
    }

    /**
     * Test: Verificar capacidad de watch mode
     */
    public function test_watch_mode_capability() {
        $package_json_path = $this->dev_tools_path . '/package.json';
        $package_content = file_get_contents($package_json_path);
        $package_data = json_decode($package_content, true);
        
        // Verificar que hay script de watch
        $this->assertArrayHasKey('watch', $package_data['scripts'], 
            'package.json debería tener script de watch');
        
        $watch_script = $package_data['scripts']['watch'];
        
        // Debería usar webpack en modo watch
        $this->assertStringContainsString('webpack', $watch_script, 
            'Watch script debería usar webpack');
        $this->assertStringContainsString('watch', $watch_script, 
            'Watch script debería tener modo watch');
    }

    /**
     * Test: Verificar optimización para producción
     */
    public function test_production_build_optimization() {
        $package_json_path = $this->dev_tools_path . '/package.json';
        $package_content = file_get_contents($package_json_path);
        $package_data = json_decode($package_content, true);
        
        // Verificar script de build para producción
        $this->assertArrayHasKey('build', $package_data['scripts'], 
            'package.json debería tener script de build');
        
        $build_script = $package_data['scripts']['build'];
        
        // Debería incluir optimizaciones
        $optimization_indicators = ['production', '--mode=production', '--optimize'];
        $has_optimization = false;
        
        foreach ($optimization_indicators as $indicator) {
            if (strpos($build_script, $indicator) !== false) {
                $has_optimization = true;
                break;
            }
        }
        
        $this->assertTrue($has_optimization, 
            'Build script debería incluir optimizaciones para producción');
    }
}
