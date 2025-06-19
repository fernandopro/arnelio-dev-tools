<?php
/**
 * Dev-Tools Admin Panel - Simplified Tests Interface
 * Panel de administración simplificado solo para ejecución de tests
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

namespace DevTools;

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

use Exception;

class DevToolsAdminPanel {
    
    private $config;
    private $modules;
    private $paths;
    
    public function __construct($config, $modules) {
        $this->config = $config;
        $this->modules = $modules;
        $this->paths = \DevToolsPaths::getInstance();
        
        // Registrar handlers AJAX para tests
        add_action('wp_ajax_dev_tools_run_tests', [$this, 'ajax_run_tests']);
        add_action('wp_ajax_dev_tools_test_connectivity', [$this, 'ajax_test_connectivity']);
        add_action('wp_ajax_dev_tools_get_tests_list', [$this, 'ajax_get_tests_list']);
        add_action('wp_ajax_dev_tools_run_specific_test', [$this, 'ajax_run_specific_test']);
    }
    
    /**
     * Handler AJAX para ejecutar tests
     */
    public function ajax_run_tests() {
        // Debug del nonce recibido
        $received_nonce = $_POST['nonce'] ?? '';
        $expected_action = 'dev_tools_nonce';
        
        // Log de debugging (remover en producción)
        error_log("DEBUG NONCE - Received: {$received_nonce}");
        error_log("DEBUG NONCE - Expected action: {$expected_action}");
        error_log("DEBUG NONCE - Verification result: " . (wp_verify_nonce($received_nonce, $expected_action) ? 'VALID' : 'INVALID'));
        
        // Verificar nonce de seguridad
        if (!wp_verify_nonce($received_nonce, $expected_action)) {
            wp_send_json_error(['message' => 'Security check failed - Invalid nonce']);
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Obtener parámetros del POST
        $test_types = $_POST['test_types'] ?? ['unit'];
        $verbose = filter_var($_POST['verbose'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $coverage = filter_var($_POST['coverage'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $testdox = filter_var($_POST['testdox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Asegurar que test_types sea un array
        if (!is_array($test_types)) {
            $test_types = [$test_types];
        }
        
        try {
            $result = $this->run_tests_with_options($test_types, $verbose, $coverage, $testdox);
            
            if ($result['success']) {
                wp_send_json_success($result['data']);
            } else {
                wp_send_json_error([
                    'message' => $result['error'] ?? 'Error desconocido ejecutando tests'
                ]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Error ejecutando tests: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handler AJAX para probar conectividad del sistema
     */
    public function ajax_test_connectivity() {
        // Debug del nonce recibido
        $received_nonce = $_POST['nonce'] ?? '';
        $expected_action = 'dev_tools_nonce';
        
        error_log("DEBUG CONNECTIVITY - Received nonce: {$received_nonce}");
        error_log("DEBUG CONNECTIVITY - Expected action: {$expected_action}");
        
        // Verificar nonce de seguridad
        if (!wp_verify_nonce($received_nonce, $expected_action)) {
            error_log("DEBUG CONNECTIVITY - Nonce verification failed");
            wp_send_json_error(['message' => 'Security check failed - Invalid nonce']);
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            error_log("DEBUG CONNECTIVITY - User permission check failed");
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        error_log("DEBUG CONNECTIVITY - Starting connectivity tests...");
        
        try {
            // Realizar pruebas de conectividad
            $connectivity_results = $this->perform_connectivity_tests();
            
            error_log("DEBUG CONNECTIVITY - Tests completed successfully");
            
            wp_send_json_success([
                'message' => 'Connectivity test completed',
                'results' => $connectivity_results,
                'timestamp' => current_time('mysql'),
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION
            ]);
            
        } catch (Exception $e) {
            error_log("DEBUG CONNECTIVITY - Exception caught: " . $e->getMessage());
            error_log("DEBUG CONNECTIVITY - Exception trace: " . $e->getTraceAsString());
            
            wp_send_json_error([
                'message' => 'Error en prueba de conectividad: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        } catch (\Error $e) {
            error_log("DEBUG CONNECTIVITY - Fatal error caught: " . $e->getMessage());
            error_log("DEBUG CONNECTIVITY - Fatal error trace: " . $e->getTraceAsString());
            
            wp_send_json_error([
                'message' => 'Fatal error en prueba de conectividad: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }
    
    /**
     * Handler AJAX para obtener lista de tests disponibles
     */
    public function ajax_get_tests_list() {
        // Verificar nonce de seguridad
        $received_nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($received_nonce, 'dev_tools_nonce')) {
            wp_send_json_error(['message' => 'Security check failed - Invalid nonce']);
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        try {
            $tests_list = $this->scan_tests_directory();
            wp_send_json_success([
                'tests' => $tests_list,
                'total_count' => count($tests_list),
                'scan_time' => current_time('mysql')
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Error escaneando tests: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Escanea el directorio plugin-dev-tools/tests y retorna información de todos los tests
     */
    private function scan_tests_directory() {
        $tests_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools/tests';
        $tests = [];
        
        if (!is_dir($tests_dir)) {
            throw new Exception('Directorio de tests no encontrado: ' . $tests_dir);
        }
        
        // Escanear recursivamente el directorio de tests
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tests_dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/Test\.php$/', $file->getFilename())) {
                $relative_path = str_replace($tests_dir . '/', '', $file->getPathname());
                $path_parts = explode('/', $relative_path);
                
                // Determinar el tipo basado en la subcarpeta
                $type = 'Other';
                if (count($path_parts) > 1) {
                    $type = ucfirst($path_parts[0]);
                }
                
                // Obtener información del archivo
                $method_count = $this->count_test_methods($file->getPathname());
                
                $tests[] = [
                    'filename' => $file->getFilename(),
                    'relative_path' => $relative_path,
                    'full_path' => $file->getPathname(),
                    'type' => $type,
                    'method_count' => $method_count,
                    'file_size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime())
                ];
            }
        }
        
        // Ordenar por tipo y luego por nombre
        usort($tests, function($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcmp($a['filename'], $b['filename']);
            }
            return strcmp($a['type'], $b['type']);
        });
        
        return $tests;
    }
    
    /**
     * Cuenta los métodos de test en un archivo
     */
    private function count_test_methods($file_path) {
        $content = file_get_contents($file_path);
        $test_count = 0;
        
        // Dividir el contenido en líneas para un análisis más preciso
        $lines = explode("\n", $content);
        $in_comment_block = false;
        $has_test_annotation = false;
        
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Detectar inicio/fin de comentarios de bloque
            if (strpos($line, '/**') !== false) {
                $in_comment_block = true;
                $has_test_annotation = false;
            }
            
            // Buscar anotación @test en comentarios
            if ($in_comment_block && strpos($line, '@test') !== false) {
                $has_test_annotation = true;
            }
            
            // Detectar fin de comentario de bloque
            if (strpos($line, '*/') !== false) {
                $in_comment_block = false;
            }
            
            // Buscar métodos públicos
            if (preg_match('/public\s+function\s+(\w+)/', $line, $matches)) {
                $method_name = $matches[1];
                
                // Contar como test si:
                // 1. El nombre empieza con "test"
                // 2. O tiene la anotación @test
                if (strpos($method_name, 'test') === 0 || $has_test_annotation) {
                    $test_count++;
                }
                
                // Resetear flag de anotación después de procesar el método
                $has_test_annotation = false;
            }
        }
        
        return $test_count;
    }

    /**
     * Handler AJAX para ejecutar un test específico individual
     */
    public function ajax_run_specific_test() {
        // Debug del nonce recibido
        $received_nonce = $_POST['nonce'] ?? '';
        $expected_action = 'dev_tools_nonce';
        
        // Log de debugging
        error_log("DEBUG SPECIFIC TEST - Received: {$received_nonce}");
        error_log("DEBUG SPECIFIC TEST - Expected action: {$expected_action}");
        
        // Verificar nonce de seguridad
        if (!wp_verify_nonce($received_nonce, $expected_action)) {
            wp_send_json_error(['message' => 'Security check failed - Invalid nonce']);
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Obtener parámetros del POST
        $test_file = $_POST['test_file'] ?? '';
        $verbose = filter_var($_POST['verbose'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $coverage = filter_var($_POST['coverage'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $testdox = filter_var($_POST['testdox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        error_log("DEBUG SPECIFIC TEST - Archivo: {$test_file}");
        error_log("DEBUG SPECIFIC TEST - POST datos completos: " . print_r($_POST, true));
        error_log("DEBUG SPECIFIC TEST - Opciones procesadas: verbose=" . ($verbose ? 'true' : 'false') . 
                 ", coverage=" . ($coverage ? 'true' : 'false') . 
                 ", testdox=" . ($testdox ? 'true' : 'false'));
        
        if (empty($test_file)) {
            wp_send_json_error(['message' => 'No se especificó el archivo de test']);
            return;
        }
        
        try {
            $result = $this->run_specific_test_file($test_file, $verbose, $coverage, $testdox);
            
            if ($result['success']) {
                wp_send_json_success($result['data']);
            } else {
                wp_send_json_error([
                    'message' => $result['error'] ?? 'Error desconocido ejecutando test específico'
                ]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Error ejecutando test específico: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Realizar pruebas de conectividad del sistema
     */
    private function perform_connectivity_tests() {
        error_log("DEBUG CONNECTIVITY - Starting perform_connectivity_tests()");
        
        $results = [];
        
        try {
            error_log("DEBUG CONNECTIVITY - Getting basic PHP/WP info");
            $results['php_version'] = PHP_VERSION;
            $results['wp_version'] = get_bloginfo('version');
            $results['memory_limit'] = ini_get('memory_limit');
            $results['max_execution_time'] = ini_get('max_execution_time');
            $results['wordpress_loaded'] = true;
            
            error_log("DEBUG CONNECTIVITY - Checking user permissions");
            $results['user_can_manage'] = current_user_can('manage_options');
            
            error_log("DEBUG CONNECTIVITY - Testing nonce system");
            $test_nonce = wp_create_nonce('dev_tools_nonce');
            $results['nonce_system'] = wp_verify_nonce($test_nonce, 'dev_tools_nonce');
            
            error_log("DEBUG CONNECTIVITY - Getting AJAX URL");
            $results['ajax_url'] = admin_url('admin-ajax.php');
            $results['plugin_active'] = true;
            
            error_log("DEBUG CONNECTIVITY - Checking paths object");
            if ($this->paths) {
                error_log("DEBUG CONNECTIVITY - Paths object exists");
                try {
                    // Usar una ruta alternativa más segura
                    $base_path = dirname(__DIR__); // dev-tools directory
                    error_log("DEBUG CONNECTIVITY - Base path (alternative): " . $base_path);
                    $results['dev_tools_paths'] = [
                        'base_path' => $base_path,
                        'plugin_url' => plugins_url('', $base_path . '/loader.php'),
                        'paths_object_type' => get_class($this->paths)
                    ];
                } catch (Exception $e) {
                    error_log("DEBUG CONNECTIVITY - Error getting paths: " . $e->getMessage());
                    $results['dev_tools_paths'] = [
                        'base_path' => 'Error: ' . $e->getMessage(),
                        'plugin_url' => 'Error getting base path'
                    ];
                }
            } else {
                error_log("DEBUG CONNECTIVITY - Paths object is null");
                $results['dev_tools_paths'] = [
                    'base_path' => 'Paths object not initialized',
                    'plugin_url' => 'N/A'
                ];
            }
            
            error_log("DEBUG CONNECTIVITY - Checking PHPUnit availability");
            try {
                // Usar ruta alternativa segura
                $base_path = dirname(__DIR__); // dev-tools directory
                $phpunit_path = $base_path . '/vendor/phpunit/phpunit/phpunit';
                $results['phpunit_available'] = file_exists($phpunit_path);
                $results['phpunit_path'] = $phpunit_path;
                error_log("DEBUG CONNECTIVITY - PHPUnit path: " . $phpunit_path);
                error_log("DEBUG CONNECTIVITY - PHPUnit exists: " . ($results['phpunit_available'] ? 'Yes' : 'No'));
            } catch (Exception $e) {
                error_log("DEBUG CONNECTIVITY - Error checking PHPUnit: " . $e->getMessage());
                $results['phpunit_available'] = false;
                $results['phpunit_path'] = 'Error: ' . $e->getMessage();
            }
            
            error_log("DEBUG CONNECTIVITY - Getting PHP binary path");
            try {
                $php_binary = $this->get_php_binary_path();
                $results['php_binary'] = $php_binary;
                $results['php_binary_exists'] = file_exists($php_binary) || $php_binary === 'php';
            } catch (Exception $e) {
                error_log("DEBUG CONNECTIVITY - Error getting PHP binary: " . $e->getMessage());
                $results['php_binary'] = 'Error: ' . $e->getMessage();
                $results['php_binary_exists'] = false;
            }
            
            error_log("DEBUG CONNECTIVITY - Checking plugin-dev-tools directory");
            try {
                $plugin_dev_tools_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools';
                $results['plugin_dev_tools_dir'] = $plugin_dev_tools_dir;
                $results['plugin_dev_tools_exists'] = is_dir($plugin_dev_tools_dir);
                error_log("DEBUG CONNECTIVITY - Plugin dev tools dir: " . $plugin_dev_tools_dir);
                error_log("DEBUG CONNECTIVITY - Plugin dev tools exists: " . ($results['plugin_dev_tools_exists'] ? 'Yes' : 'No'));
            } catch (Exception $e) {
                error_log("DEBUG CONNECTIVITY - Error checking plugin-dev-tools: " . $e->getMessage());
                $results['plugin_dev_tools_dir'] = 'Error: ' . $e->getMessage();
                $results['plugin_dev_tools_exists'] = false;
            }
            
            error_log("DEBUG CONNECTIVITY - All tests completed successfully");
            
        } catch (Exception $e) {
            error_log("DEBUG CONNECTIVITY - Exception in perform_connectivity_tests: " . $e->getMessage());
            throw $e;
        } catch (\Error $e) {
            error_log("DEBUG CONNECTIVITY - Fatal error in perform_connectivity_tests: " . $e->getMessage());
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Ejecuta el comando de test
     */
    private function execute_test_command($test_type, $test_file = '') {
        error_log("DEBUG - execute_test_command() called with type: " . $test_type);
        $start_time = microtime(true);
        
        // Cambiar al directorio plugin-dev-tools
        $original_dir = getcwd();
        $plugin_dev_tools_dir = dirname($this->paths->get('base_path')) . '/plugin-dev-tools';
        
        if (!is_dir($plugin_dev_tools_dir)) {
            throw new Exception('Plugin-dev-tools directory not found');
        }
        
        chdir($plugin_dev_tools_dir);
        
        // Construir comando según el tipo (con ruta completa de PHP y archivo PHPUnit real)
        $php_binary = $this->get_php_binary_path();
        $phpunit_path = '"' . $php_binary . '" ../dev-tools/vendor/phpunit/phpunit/phpunit';
        
        switch ($test_type) {
            case 'basic':
                $basic_test_file = $this->get_basic_test_filename();
                $command = $phpunit_path . ' ' . $basic_test_file . ' --verbose';
                break;
            case 'dashboard':
                $command = $phpunit_path . ' tests/unit/dashboard/ --verbose';
                break;
            case 'all':
                $command = $phpunit_path . ' tests/ --verbose';
                break;
            case 'plugin':
                // Ejecutar tests del plugin con bootstrap minimal sin cargar dev-tools
                $command = $phpunit_path . ' --bootstrap ../plugin-dev-tools/tests/minimal-bootstrap.php ../plugin-dev-tools/tests/unit/Tarokina2025BasicTest.php --verbose';
                break;
            case 'specific':
                if (empty($test_file)) {
                    throw new Exception('Test file not specified');
                }
                $command = $phpunit_path . ' ' . escapeshellarg($test_file) . ' --verbose';
                break;
            default:
                throw new Exception('Invalid test type');
        }
        
        // Ejecutar comando con captura de salida
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        // Restaurar directorio
        chdir($original_dir);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        return [
            'output' => implode("\n", $output),
            'success' => $return_code === 0,
            'execution_time' => $execution_time,
            'command' => $command
        ];
    }
    
    /**
     * Renderiza el header moderno y minimalista
     */
    private function render_header($title = 'Dev Tools') {
        ?>
        <div class="devtools-modern-wrap" style="margin: 0; padding: 0; background: #f8fafc; min-height: 100vh;">
            <!-- Header Principal Moderno -->
            <div class="devtools-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; margin: 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <div class="container-fluid" style="max-width: none; padding: 0 2rem;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="devtools-icon" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-right: 1.5rem; font-size: 24px;">
                                    🔧
                                </div>
                                <div>
                                    <h1 class="h2 mb-1" style="font-weight: 600; margin: 0;">Dev Tools</h1>
                                    <p class="mb-0" style="opacity: 0.9; font-size: 0.95rem;">Sistema de desarrollo y testing v<?php echo esc_html($this->config['version']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="devtools-env-info">
                                <?php echo $this->get_environment_badge(); ?>
                                <small class="d-block mt-1" style="opacity: 0.8;">WordPress <?php echo get_bloginfo('version'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }
    
    /**
     * Renderiza el footer moderno
     */
    private function render_footer() {
        ?>
                <!-- Footer Minimalista -->
                <div class="devtools-footer" style="margin-top: 3rem; padding: 2rem 0; background: #ffffff; border-top: 1px solid #e2e8f0;">
                    <div class="container-fluid" style="max-width: none; padding: 0 2rem;">
                        <div class="row">
                            <div class="col-12 text-center">
                                <p class="mb-0" style="color: #64748b; font-size: 0.875rem;">
                                    Dev Tools · 
                                    Sistema de desarrollo para WordPress ·
                                    <span style="color: #667eea;">Dev-Tools Arquitectura 3.0</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    

    
    /**
     * Obtiene el badge del entorno actual - Versión moderna
     */
    private function get_environment_badge() {
        $site_detector = $this->modules['SiteUrlDetectionModule'] ?? null;
        if ($site_detector) {
            $env_info = $site_detector->get_environment_info();
            if ($env_info['is_local_wp']) {
                return '<span class="badge" style="background: rgba(255,193,7,0.9); color: #000; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 500;">Local by WP Engine</span>';
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return '<span class="badge" style="background: rgba(13,202,240,0.9); color: #fff; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 500;">Development</span>';
        }
        
        return '<span class="badge" style="background: rgba(25,135,84,0.9); color: #fff; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 500;">Production</span>';
    }
    
    /**
     * Renderiza la página principal con diseño moderno
     */
    public function render_dashboard() {
        $this->render_header('Dev Tools');
        ?>
        
        <!-- Contenido Principal Moderno -->
        <div class="devtools-main-content" style="padding: 2rem 0; background: #f8fafc;">
            <div class="container-fluid" style="max-width: none; padding: 0 2rem;">
                <?php $this->render_tests_content(); ?>
            </div>
        </div>
        
        <?php
        $this->render_footer();
    }
    
    /**
     * Contenido de Tests - Diseño Moderno y Minimalista con Layout Vertical
     */
    private function render_tests_content() {
        ?>
        <!-- Panel de Control - Ancho completo arriba -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="devtools-card" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); border: none; overflow: hidden;">
                    <!-- Header del card -->
                    <div class="devtools-card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; margin: 0;">
                        <h5 class="mb-0" style="font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 1.2em;">🧪</span>
                            Runner
                        </h5>
                        <p class="mb-0" style="opacity: 0.9; font-size: 0.875rem; margin-top: 0.25rem;">Ejecutar tests PHPUnit con opciones avanzadas</p>
                    </div>
                    
                    <!-- Contenido del card - Layout horizontal -->
                    <div class="devtools-card-body" style="padding: 2rem;">
                        <div class="row g-4">
                            <!-- Columna 1: Tipos de Test -->
                            <div class="col-md-4">
                                <div class="devtools-section">
                                    <label class="devtools-label" style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 1rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">All Test</label>
                                    <div class="devtools-radio-group" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <label class="devtools-radio" style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="radio" name="testType" id="devtools-devtoolsTests" value="devtools" style="margin-right: 0.75rem; accent-color: #667eea;">
                                            <span style="color: #475569; font-weight: 500;">Dev-Tools Tests</span>
                                        </label>
                                        <label class="devtools-radio" style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="radio" name="testType" id="devtools-pluginTests" value="plugin" checked style="margin-right: 0.75rem; accent-color: #667eea;">
                                            <span style="color: #475569; font-weight: 500;">Plugin Tests</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna 2: Opciones de Salida -->
                            <div class="col-md-4">
                                <div class="devtools-section">
                                    <label class="devtools-label" style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 1rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Opciones de Salida</label>
                                    <div class="devtools-checkbox-group" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <label class="devtools-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="checkbox" id="devtools-verboseOutput" style="margin-right: 0.75rem; accent-color: #667eea;">
                                            <span style="color: #475569; font-weight: 500;">Verbose Output</span>
                                        </label>
                                        <label class="devtools-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="checkbox" id="devtools-generateCoverage" style="margin-right: 0.75rem; accent-color: #667eea;">
                                            <span style="color: #475569; font-weight: 500;">Generate Coverage</span>
                                        </label>
                                        <label class="devtools-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="checkbox" id="devtools-testdoxOutput" style="margin-right: 0.75rem; accent-color: #667eea;">
                                            <span style="color: #475569; font-weight: 500;">TestDox Summary</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna 3: Botones de Acción -->
                            <div class="col-md-4">
                                <div class="devtools-section">
                                    <label class="devtools-label" style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 1rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Acciones</label>
                                    <div class="devtools-actions" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <button id="devtools-runTests" class="devtools-btn devtools-btn-primary" type="button" data-test-action="run-full" data-original-content='🚀 Run All Tests' style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                            🚀 Run All Tests
                                        </button>
                                        
                                        <!-- Botones secundarios -->
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button id="devtools-clearResults" class="devtools-btn devtools-btn-outline" type="button" data-test-action="clear" data-original-content='Clear Results' style="background: transparent; color: #64748b; border: 2px solid #e2e8f0; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; flex: 1; font-size: 0.875rem;">
                                                Clear Results
                                            </button>
                                            <button id="devtools-testConnectivity" class="devtools-btn devtools-btn-outline" type="button" data-test-action="connectivity" data-original-content='Test Connectivity' style="background: transparent; color: #64748b; border: 2px solid #e2e8f0; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; flex: 1; font-size: 0.875rem;">
                                                Test Connectivity
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de Resultados - Ancho completo abajo (inicialmente oculto) -->
        <div id="OutputContainer" class="row" style="display: none; opacity: 0; transition: opacity 0.3s ease-in-out;">
            <div class="col-12">
                <div class="devtools-card" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); border: none; overflow: hidden;">
                    <!-- Header del card -->
                    <div class="devtools-card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h5 class="mb-0" style="font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 1.2em;">📊</span>
                                    Results
                                </h5>
                                <p class="mb-0" style="opacity: 0.9; font-size: 0.875rem; margin-top: 0.25rem;">Output y análisis en tiempo real</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenido de resultados -->
                    <div class="devtools-card-body" style="padding: 0;">
                        <div id="devtools-testResults" class="devtools-results" style="min-height: auto; padding: 2rem; background: #f8fafc;">
                            <div class="devtools-empty-state" style="text-align: center; padding: 3rem 2rem; color: #64748b;">
                                <div style="font-size: 4rem; opacity: 0.3; margin-bottom: 1rem;">🔧</div>
                                <h6 style="font-weight: 600; color: #475569; margin-bottom: 0.5rem;">No tests executed yet</h6>
                                <p style="margin: 0; font-size: 0.875rem;">Selecciona los tipos de test y presiona "Run Selected Tests" para comenzar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de Tests Disponibles - Tabla moderna -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="devtools-card" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); border: none; overflow: hidden;">
                    <!-- Header del card -->
                    <div class="devtools-card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h5 class="mb-0" style="font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 1.2em;">🧪</span>
                                    Individual Tests
                                </h5>
                                <p class="mb-0" style="opacity: 0.9; font-size: 0.875rem; margin-top: 0.25rem;">Listado de todos los archivos de test en plugin-dev-tools/tests</p>
                            </div>
                            <div>
                                <button id="devtools-refreshTests" class="btn btn-sm" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 8px; padding: 0.5rem 1rem; font-weight: 500; transition: all 0.3s ease;">
                                    <span style="font-size: 0.875rem;">🔄</span> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenido de la tabla -->
                    <div class="devtools-card-body" style="padding: 0;">
                        <div id="devtools-testsTable" style="min-height: 200px;">
                            <!-- La tabla se cargará aquí dinámicamente -->
                            <div style="padding: 3rem 2rem; text-align: center; color: #64748b;">
                                <div style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;">📋</div>
                                <h6 style="font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Cargando tests...</h6>
                                <p style="margin: 0; font-size: 0.875rem;">Escaneando directorio plugin-dev-tools/tests</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript específico para tests -->
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estilos para métricas mejoradas */
        .metric-card {
            text-align: center;
            background: rgba(0, 0, 0, 0.4) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.875rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .metric-value {
            font-size: 1.225rem;
            line-height: 1.2;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            margin-bottom: 0.25rem;
        }
        
        .metric-label {
            opacity: 0.95;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Colores específicos para métricas */
        .metric-success {
            color: #34d399 !important;
            text-shadow: 0 0 10px rgba(52, 211, 153, 0.3), 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .metric-error {
            color: #f87171 !important;
            text-shadow: 0 0 10px rgba(248, 113, 113, 0.3), 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .metric-warning {
            color: #fbbf24 !important;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.3), 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .metric-info {
            color: #60a5fa !important;
            text-shadow: 0 0 10px rgba(96, 165, 250, 0.3), 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .metric-purple {
            color: #a78bfa !important;
            text-shadow: 0 0 10px rgba(167, 139, 250, 0.3), 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .metric-cyan {
            color: #22d3ee !important;
            text-shadow: 0 0 10px rgba(34, 211, 238, 0.3), 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Responsive design para métricas */
        @media (max-width: 768px) {
            .metric-value {
                font-size: 1.25rem;
            }
            .metric-label {
                font-size: 0.7rem;
            }
            .metric-card {
                padding: 0.75rem;
            }
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialización directa del TestRunner moderno
            console.log('🔧 Inicializando Test Runner moderno...');
            
            // Event listeners específicos para cada botón
            const runTestsBtn = document.getElementById('devtools-runTests');
            const clearResultsBtn = document.getElementById('devtools-clearResults');
            const testConnectivityBtn = document.getElementById('devtools-testConnectivity');
            
            if (runTestsBtn) {
                runTestsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🔍 Ejecutando tests completos...');
                    
                    // Mostrar el OutputContainer con fade-in
                    const outputContainer = document.getElementById('OutputContainer');
                    if (outputContainer) {
                        outputContainer.style.display = 'block';
                        setTimeout(() => {
                            outputContainer.style.opacity = '1';
                        }, 10);
                    }
                    
                    if (window.devTools && window.devTools.testRunner) {
                        if (!window.devTools.testRunner.isRunning) {
                            window.devTools.testRunner.runTests();
                        } else {
                            console.log('🔍 Test ya ejecutándose, ignorando click');
                        }
                    } else {
                        console.error('TestRunner no disponible');
                    }
                });
            }
            
            if (clearResultsBtn) {
                clearResultsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🧹 Limpiando resultados...');
                    
                    if (window.devTools && window.devTools.testRunner) {
                        window.devTools.testRunner.clearResults();
                    } else {
                        console.error('TestRunner no disponible');
                    }
                    
                    // Ocultar el OutputContainer con fade-out después de limpiar
                    setTimeout(() => {
                        const outputContainer = document.getElementById('OutputContainer');
                        if (outputContainer) {
                            outputContainer.style.opacity = '0';
                            setTimeout(() => {
                                outputContainer.style.display = 'none';
                            }, 300); // Espera a que termine la transición
                        }
                    }, 100); // Pequeño delay para que se vea la limpieza
                });
            }
            
            if (testConnectivityBtn) {
                testConnectivityBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🌐 Probando conectividad AJAX...');
                    
                    // Mostrar el OutputContainer con fade-in
                    const outputContainer = document.getElementById('OutputContainer');
                    if (outputContainer) {
                        outputContainer.style.display = 'block';
                        setTimeout(() => {
                            outputContainer.style.opacity = '1';
                        }, 10);
                    }
                    
                    // Mostrar estado de carga
                    const resultArea = document.getElementById('devtools-testResults');
                    if (resultArea) {
                        resultArea.innerHTML = `
                            <div style="padding: 2rem; text-align: center;">
                                <div class="devtools-spinner" style="width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                                <h6 style="color: #475569; font-weight: 600; margin-bottom: 0.5rem;">Probando conectividad...</h6>
                                <p style="color: #64748b; font-size: 0.875rem; margin: 0;">
                                    Verificando conexión AJAX con el servidor...
                                </p>
                            </div>
                        `;
                    }
                    
                    // Realizar llamada AJAX de prueba
                    const formData = new FormData();
                    formData.append('action', 'dev_tools_test_connectivity');
                    formData.append('nonce', '<?php echo wp_create_nonce('dev_tools_nonce'); ?>');
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('✅ Conectividad OK:', data);
                        
                        if (data.success) {
                            const results = data.data.results;
                            const timestamp = data.data.timestamp;
                            
                            // Mostrar resultados exitosos
                            if (resultArea) {
                                resultArea.innerHTML = `
                                    <div style="padding: 2rem;">
                                        <div style="text-align: center; margin-bottom: 2rem;">
                                            <div style="color: #059669; font-size: 3rem; margin-bottom: 1rem;">✅</div>
                                            <h6 style="color: #059669; font-weight: 600; margin-bottom: 0.5rem;">Conectividad AJAX OK</h6>
                                            <p style="color: #64748b; font-size: 0.875rem; margin: 0;">
                                                Prueba realizada: ${timestamp}
                                            </p>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                                            <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; border-left: 4px solid #059669;">
                                                <h6 style="color: #065f46; font-weight: 600; margin-bottom: 0.5rem;">Sistema WordPress</h6>
                                                <small style="color: #065f46;">
                                                    ✅ WordPress ${results.wp_version}<br>
                                                    ✅ PHP ${results.php_version}<br>
                                                    ✅ Usuario autorizado: ${results.user_can_manage ? 'Sí' : 'No'}<br>
                                                    ✅ Sistema de nonce: ${results.nonce_system ? 'OK' : 'Error'}
                                                </small>
                                            </div>
                                            
                                            <div style="background: #eff6ff; padding: 1rem; border-radius: 8px; border-left: 4px solid #2563eb;">
                                                <h6 style="color: #1e40af; font-weight: 600; margin-bottom: 0.5rem;">Configuración PHP</h6>
                                                <small style="color: #1e40af;">
                                                    ✅ Memory Limit: ${results.memory_limit}<br>
                                                    ✅ Max Execution: ${results.max_execution_time}s<br>
                                                    ✅ PHP Binary: ${results.php_binary_exists ? 'Disponible' : 'No encontrado'}<br>
                                                    ✅ PHPUnit: ${results.phpunit_available ? 'Disponible' : 'No encontrado'}
                                                </small>
                                            </div>
                                            
                                            <div style="background: #fef3f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #f5576c;">
                                                <h6 style="color: #f5576c; font-weight: 600; margin-bottom: 0.5rem;">Directorios</h6>
                                                <small style="color: #f5576c;">
                                                    ${results.plugin_dev_tools_exists ? '✅' : '❌'} Plugin Dev Tools<br>
                                                    ✅ AJAX URL: Configurada<br>
                                                    ✅ Plugin URL: Configurada
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-top: 2rem; padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                                            <small style="color: #64748b; font-weight: 500;">
                                                🚀 Sistema listo para ejecutar tests PHPUnit
                                            </small>
                                        </div>
                                    </div>
                                `;
                            }
                        } else {
                            console.error('❌ AJAX Error:', data);
                            
                            // Mostrar error detallado en el área de resultados
                            const errorMessage = data.data?.message || 'Error desconocido';
                            const errorDetails = data.data?.error_details || null;
                            
                            if (resultArea) {
                                resultArea.innerHTML = `
                                    <div style="padding: 2rem;">
                                        <div style="text-align: center; margin-bottom: 2rem;">
                                            <div style="color: #dc2626; font-size: 3rem; margin-bottom: 1rem;">❌</div>
                                            <h6 style="color: #dc2626; font-weight: 600; margin-bottom: 0.5rem;">Error en Prueba de Conectividad</h6>
                                            <p style="color: #64748b; font-size: 0.875rem; margin: 0;">
                                                ${errorMessage}
                                            </p>
                                        </div>
                                        
                                        ${errorDetails ? `
                                            <div style="background: #fef2f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc2626; margin-bottom: 1rem;">
                                                <h6 style="color: #991b1b; font-weight: 600; margin-bottom: 0.5rem;">Detalles del Error</h6>
                                                <small style="color: #991b1b; font-family: monospace;">
                                                    <strong>Archivo:</strong> ${errorDetails.file}<br>
                                                    <strong>Línea:</strong> ${errorDetails.line}<br>
                                                    <strong>Mensaje:</strong> ${errorMessage}
                                                </small>
                                            </div>
                                        ` : ''}
                                        
                                        <div style="background: #fef3f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                                            <small style="color: #991b1b; font-weight: 500;">
                                                📋 Revisa los logs de PHP para más información:<br>
                                                <code style="background: rgba(0,0,0,0.1); padding: 2px 4px; border-radius: 3px;">
                                                /Users/fernandovazquezperez/Local Sites/tarokina-2025/logs/php/error.log
                                                </code>
                                            </small>
                                        </div>
                                    </div>
                                `;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error de conectividad:', error);
                        
                        // Mostrar error en el área de resultados
                        if (resultArea) {
                            resultArea.innerHTML = `
                                <div style="padding: 2rem; text-align: center;">
                                    <div style="color: #dc2626; font-size: 3rem; margin-bottom: 1rem;">❌</div>
                                    <h6 style="color: #dc2626; font-weight: 600; margin-bottom: 0.5rem;">Error de Conectividad</h6>
                                    <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1.5rem;">
                                        ${error.message}
                                    </p>
                                    <div style="background: #fef2f2; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                                        <small style="color: #991b1b; font-weight: 500;">
                                            Verifica la consola del navegador para más detalles
                                        </small>
                                    </div>
                                </div>
                            `;
                        }
                    });
                });
            }
            
            // Verificar elementos de la UI
            setTimeout(() => {
                const criticalElements = [
                    'devtools-testResults',
                    'devtools-runTests'
                ];
                
                let allPresent = true;
                criticalElements.forEach(id => {
                    if (!document.getElementById(id)) {
                        allPresent = false;
                        console.warn('⚠️ Elemento faltante:', id);
                    }
                });
                
                if (allPresent) {
                    console.log('✅ Todos los elementos de UI presentes');
                }
                
                if (typeof window.devTools !== 'undefined' && window.devTools.testRunner) {
                    console.log('✅ TestRunner inicializado correctamente');
                } else {
                    console.warn('⚠️ TestRunner no disponible');
                }
            }, 500);
            
            // Funcionalidad de la tabla de tests
            const refreshTestsBtn = document.getElementById('devtools-refreshTests');
            const testsTableContainer = document.getElementById('devtools-testsTable');
            
            // Función para cargar la lista de tests
            function loadTestsList() {
                if (!testsTableContainer) return;
                
                // Mostrar loading
                testsTableContainer.innerHTML = `
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b;">
                        <div style="width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                        <h6 style="font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Escaneando tests...</h6>
                        <p style="margin: 0; font-size: 0.875rem;">Analizando directorio plugin-dev-tools/tests</p>
                    </div>
                `;
                
                // Realizar llamada AJAX
                const formData = new FormData();
                formData.append('action', 'dev_tools_get_tests_list');
                formData.append('nonce', '<?php echo wp_create_nonce('dev_tools_nonce'); ?>');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Tests cargados exitosamente:', data);
                    if (data.success) {
                        renderTestsTable(data.data.tests, data.data.total_count);
                    } else {
                        showTestsError(data.data?.message || 'Error cargando tests');
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading tests:', error);
                    showTestsError('Error de conectividad al cargar tests');
                });
            }
            
            // Función para renderizar la tabla de tests
            function renderTestsTable(tests, totalCount) {
                if (!testsTableContainer) return;
                
                if (tests.length === 0) {
                    testsTableContainer.innerHTML = `
                        <div style="padding: 3rem 2rem; text-align: center; color: #64748b;">
                            <div style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;">📭</div>
                            <h6 style="font-weight: 600; color: #475569; margin-bottom: 0.5rem;">No se encontraron tests</h6>
                            <p style="margin: 0; font-size: 0.875rem;">El directorio plugin-dev-tools/tests está vacío</p>
                        </div>
                    `;
                    return;
                }
                
                let tableHTML = `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: white;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="width:10%; padding: 1rem; text-align: center; font-weight: 600; color: #374151; font-size: 0.875rem;">Tests</th>
                                    <th style="width:10%; padding: 1rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem;">Tipo</th>
                                    <th style="width:30%; padding: 1rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem;">Archivo</th>
                                    <th style="width:30%; padding: 1rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem;">Ruta</th>
                                    <th style="width:20%; padding: 1rem; text-align: center; font-weight: 600; color: #374151; font-size: 0.875rem;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                tests.forEach((test, index) => {
                    const typeColor = getTypeColor(test.type);
                    const rowBg = index % 2 === 0 ? '#ffffff' : '#f9fafb';
                    
                    tableHTML += `
                        <tr style="background: ${rowBg}; border-bottom: 1px solid #f3f4f6; transition: background-color 0.2s ease;" 
                            onmouseover="this.style.background='#f0f9ff'" 
                            onmouseout="this.style.background='${rowBg}'">
                            <td style="padding: 1rem; text-align: center;">
                                <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem;">
                                    ${test.method_count}
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="background: ${typeColor.bg}; color: ${typeColor.text}; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    ${test.type}
                                </span>
                            </td>
                            <td style="padding: 1rem; font-weight: 500; color: #1f2937;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.2em;">🧪</span>
                                    <span>${test.filename}</span>
                                </div>
                            </td>
                            <td style="padding: 1rem; color: #6b7280; font-size: 0.875rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${test.relative_path}
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <button onclick="runSpecificTest('${test.relative_path}')" 
                                        style="background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                        onmouseover="this.style.background='#5a67d8'"
                                        onmouseout="this.style.background='#667eea'">
                                    ▶ Ejecutar
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
                        <small style="color: #6b7280; font-weight: 500;">
                            Total: ${totalCount} archivos de test encontrados
                        </small>
                    </div>
                `;
                
                testsTableContainer.innerHTML = tableHTML;
            }
            
            // Función para obtener colores según el tipo
            function getTypeColor(type) {
                const colors = {
                    'Unit': { bg: '#dcfce7', text: '#166534' },           // Verde
                    'Integration': { bg: '#dbeafe', text: '#1e40af' },    // Azul
                    'Feature': { bg: '#fef3c7', text: '#d97706' },        // Amarillo
                    'Database': { bg: '#e0e7ff', text: '#5b21b6' },       // Púrpura
                    'Performance': { bg: '#fef2f2', text: '#dc2626' },    // Rojo
                    'Security': { bg: '#f0fdf4', text: '#166534' },       // Verde oscuro
                    'Api': { bg: '#ecfdf5', text: '#059669' },            // Verde esmeralda
                    'Modules': { bg: '#eff6ff', text: '#2563eb' },        // Azul medio
                    'Functional': { bg: '#fdf4ff', text: '#a21caf' },     // Magenta
                    'Acceptance': { bg: '#f0f9ff', text: '#0284c7' },     // Azul cielo
                    'Other': { bg: '#f3f4f6', text: '#6b7280' }           // Gris
                };
                return colors[type] || colors['Other'];
            }
            
            // Función para mostrar errores
            function showTestsError(message) {
                if (!testsTableContainer) return;
                
                testsTableContainer.innerHTML = `
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b;">
                        <div style="color: #dc2626; font-size: 3rem; margin-bottom: 1rem;">❌</div>
                        <h6 style="color: #dc2626; font-weight: 600; margin-bottom: 0.5rem;">Error cargando tests</h6>
                        <p style="margin: 0; font-size: 0.875rem;">${message}</p>
                    </div>
                `;
            }
            
            // Función para ejecutar test específico
            window.runSpecificTest = function(testPath) {
                console.log('🔍 Ejecutando test específico:', testPath);
                
                // Mostrar el OutputContainer con fade-in
                const outputContainer = document.getElementById('OutputContainer');
                if (outputContainer) {
                    outputContainer.style.display = 'block';
                    setTimeout(() => {
                        outputContainer.style.opacity = '1';
                    }, 10);
                }
                
                // Leer los valores de los checkboxes de opciones de salida ANTES de mostrar el loading
                const verboseOutput = document.getElementById('devtools-verboseOutput')?.checked || false;
                const generateCoverage = document.getElementById('devtools-generateCoverage')?.checked || false;
                const testdoxOutput = document.getElementById('devtools-testdoxOutput')?.checked || false;
                
                // Debug detallado de los elementos DOM
                console.log('� DEBUG - Elementos DOM encontrados:');
                console.log('  - verboseOutput elemento:', document.getElementById('devtools-verboseOutput'));
                console.log('  - generateCoverage elemento:', document.getElementById('devtools-generateCoverage'));
                console.log('  - testdoxOutput elemento:', document.getElementById('devtools-testdoxOutput'));
                
                console.log('�📋 Opciones seleccionadas:', {
                    verbose: verboseOutput,
                    coverage: generateCoverage,
                    testdox: testdoxOutput
                });
                
                // Verificar que las opciones no están siempre en false
                if (!verboseOutput && !generateCoverage && !testdoxOutput) {
                    console.warn('⚠️ ADVERTENCIA: Todas las opciones están desactivadas. Esto podría indicar un problema.');
                }
                
                // Mostrar estado de carga en el área de resultados
                const resultArea = document.getElementById('devtools-testResults');
                if (resultArea) {
                    resultArea.innerHTML = `
                        <div style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; margin-bottom: 1rem;">
                            <div style="width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                            <h6 style="font-weight: 600; margin-bottom: 0.5rem;">🧪 Ejecutando Test Individual</h6>
                            <p style="margin: 0 0 0.75rem 0; opacity: 0.9; font-size: 0.875rem;">Archivo: ${testPath}</p>
                            <div style="display: flex; justify-content: center; gap: 0.5rem; font-size: 0.75rem; opacity: 0.8;">
                                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                    ${verboseOutput ? '✅' : '❌'} Verbose
                                </span>
                                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                    ${generateCoverage ? '✅' : '❌'} Coverage
                                </span>
                                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                    ${testdoxOutput ? '✅' : '❌'} TestDox
                                </span>
                            </div>
                        </div>
                    `;
                }
                
                // Realizar llamada AJAX para ejecutar el test específico
                const formData = new FormData();
                formData.append('action', 'dev_tools_run_specific_test');
                formData.append('nonce', '<?php echo wp_create_nonce('dev_tools_nonce'); ?>');
                formData.append('test_file', testPath);
                formData.append('verbose', verboseOutput ? 'true' : 'false');
                formData.append('coverage', generateCoverage ? 'true' : 'false');
                formData.append('testdox', testdoxOutput ? 'true' : 'false');
                
                // Debug de los datos que se envían
                console.log('📤 AJAX - Datos enviados:');
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: ${value}`);
                }
                
                // Debug adicional para verificar los valores de opciones
                console.log('🔍 DEBUGGING - Verificación de opciones:');
                console.log('  - Checkbox verboseOutput DOM:', document.getElementById('devtools-verboseOutput'));
                console.log('  - Checkbox verboseOutput checked:', document.getElementById('devtools-verboseOutput')?.checked);
                console.log('  - Checkbox generateCoverage DOM:', document.getElementById('devtools-generateCoverage'));
                console.log('  - Checkbox generateCoverage checked:', document.getElementById('devtools-generateCoverage')?.checked);
                console.log('  - Checkbox testdoxOutput DOM:', document.getElementById('devtools-testdoxOutput'));
                console.log('  - Checkbox testdoxOutput checked:', document.getElementById('devtools-testdoxOutput')?.checked);
                console.log('  - Variable verboseOutput:', verboseOutput);
                console.log('  - Variable generateCoverage:', generateCoverage);
                console.log('  - Variable testdoxOutput:', testdoxOutput);
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Test específico completado:', data);
                    
                    // Debug adicional del output recibido
                    if (data.success && data.data) {
                        console.log('🔍 DEBUGGING OUTPUT - Contenido del test:');
                        console.log('  - Output length:', data.data.output?.length || 0);
                        console.log('  - Output preview (primeros 200 chars):', data.data.output?.substring(0, 200) || 'NO OUTPUT');
                        console.log('  - Output completo:', data.data.output);
                        console.log('  - Comando ejecutado:', data.data.command);
                        console.log('  - Return code:', data.data.return_code);
                        console.log('  - Execution time:', data.data.execution_time);
                    }
                    
                    if (data.success && resultArea) {
                        const testData = data.data;
                        const summary = testData.summary;
                        
                        // Determinar el color del estado basado en resultados (igual que tests completos)
                        let statusColor = '#10b981'; // Verde por defecto (éxito)
                        let statusIcon = '✅';
                        let statusText = 'Éxito';
                        
                        if (summary.failed > 0 || summary.errors > 0) {
                            statusColor = '#ef4444'; // Rojo para fallos/errores
                            statusIcon = '❌';
                            statusText = 'Error';
                        } else if (summary.skipped > 0 || summary.incomplete > 0 || summary.risky > 0) {
                            statusColor = '#f59e0b'; // Amarillo para advertencias
                            statusIcon = '⚠️';
                            statusText = 'Advertencia';
                        }
                        
                        // Renderizar resultados del test específico
                        resultArea.innerHTML = `
                            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 25px rgba(0,0,0,0.1);">
                                <!-- Header con información del test -->
                                <div style="background: linear-gradient(135deg, ${statusColor} 0%, ${statusColor}dd 100%); color: white; padding: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <div>
                                            <h6 style="margin: 0; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                                ${statusIcon} Test Individual: ${testData.test_file}
                                            </h6>
                                        </div>
                                        <div style="text-align: right;">
                                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500;">
                                                ${statusText}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Estadísticas completas -->
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; font-size: 0.875rem;">
                                        <div class="metric-card">
                                            <div class="metric-value">${summary.total_tests}</div>
                                            <div class="metric-label">Tests</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="metric-value metric-success">${summary.passed}</div>
                                            <div class="metric-label">Pasados</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="metric-value metric-error">${summary.failed}</div>
                                            <div class="metric-label">Fallos</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="metric-value metric-warning">${summary.errors}</div>
                                            <div class="metric-label">Errores</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="metric-value metric-info">${summary.skipped}</div>
                                            <div class="metric-label">Omitidos</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="metric-value metric-purple">${summary.assertions}</div>
                                            <div class="metric-label">Assertions</div>
                                        </div>
                                        <div class="metric-card">
                                            <div class="metric-value">${testData.execution_time}ms</div>
                                            <div class="metric-label">Tiempo</div>
                                        </div>
                                        ${summary.memory ? `
                                        <div class="metric-card">
                                            <div class="metric-value metric-cyan">${summary.memory}</div>
                                            <div class="metric-label">Memoria</div>
                                        </div>
                                        ` : ''}
                                        ${summary.risky > 0 ? `
                                        <div class="metric-card">
                                            <div class="metric-value metric-warning">${summary.risky || 0}</div>
                                            <div class="metric-label">Riesgosos</div>
                                        </div>
                                        ` : ''}
                                    </div>
                                    
                                    <!-- Opciones utilizadas -->
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2);">
                                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.75rem; opacity: 0.9;">
                                            <span style="font-weight: 600;">Opciones:</span>
                                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                                ${verboseOutput ? '✅' : '❌'} Verbose
                                            </span>
                                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                                ${generateCoverage ? '✅' : '❌'} Coverage
                                            </span>
                                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px;">
                                                ${testdoxOutput ? '✅' : '❌'} TestDox
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Salida detallada del test -->
                                <div style="padding: 1.5rem;">
                                    <!-- Todos los tests individuales usan el tema oscuro para consistencia -->
                                    <div class="modern-section">
                                        <div class="modern-section-title">📋 Salida Detallada del Test</div>
                                        <div style="background: #1a1a1a; border: 1px solid #374151; border-radius: 8px; padding: 1.5rem; font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; white-space: pre-wrap; line-height: 1.6; color: #e5e7eb; font-size: 0.875rem;">
                                            ${testData.output.replace(/\\n/g, '\\n').replace(/===.*===/g, '<span style="color: #10b981; font-weight: bold;">$&</span>').replace(/✅|☢|⚠️|❌/g, '<span style="font-size: 1.1em;">$&</span>').replace(/Test.*started|Test.*ended/g, '<span style="color: #6b7280; font-style: italic;">$&</span>').replace(/It should .*/g, '<span style="color: #60a5fa; font-weight: 500;">$&</span>').replace(/Runtime:/g, '<span style="color: #fbbf24; font-weight: 500;">Runtime:</span>').replace(/Configuration:/g, '<span style="color: #fbbf24; font-weight: 500;">Configuration:</span>').replace(/Warning:/g, '<span style="color: #f59e0b; font-weight: 500;">Warning:</span>').replace(/Time:/g, '<span style="color: #34d399; font-weight: 500;">Time:</span>').replace(/Memory:/g, '<span style="color: #60a5fa; font-weight: 500;">Memory:</span>')}
                                        </div>
                                    </div>
                                    
                                    <!-- Análisis de opciones aplicadas -->
                                    <div class="modern-section">
                                        <div class="modern-section-title">⚙️ Análisis de Opciones de Salida</div>
                                        <div style="background: ${verboseOutput || generateCoverage || testdoxOutput ? '#f0f9ff' : '#f9fafb'}; border: 1px solid ${verboseOutput || generateCoverage || testdoxOutput ? '#bae6fd' : '#e5e7eb'}; border-radius: 8px; padding: 1rem;">
                                            ${verboseOutput ? 
                                                `<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px;">
                                                    <p style="margin: 0; color: #065f46; font-size: 0.875rem; font-weight: 500;">
                                                        <strong>✅ Verbose Output Activado:</strong> El output debería incluir información adicional de runtime, configuración de PHPUnit y detalles extendidos.
                                                    </p>
                                                    ${testData.output.includes('Runtime:') ? 
                                                        '<p style="margin: 0.25rem 0 0 0; color: #059669; font-size: 0.8rem;">✓ Se detectó información de runtime en el output.</p>' : 
                                                        '<p style="margin: 0.25rem 0 0 0; color: #d97706; font-size: 0.8rem;">⚠ No se detectó información de runtime específica.</p>'
                                                    }
                                                </div>` : 
                                                `<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px;">
                                                    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                                        <strong>❌ Verbose Output:</strong> Desactivado. El output será más conciso.
                                                    </p>
                                                </div>`
                                            }
                                            
                                            ${generateCoverage ? 
                                                `<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;">
                                                    <p style="margin: 0; color: #1e40af; font-size: 0.875rem; font-weight: 500;">
                                                        <strong>✅ Coverage Report Activado:</strong> Se debería generar análisis de cobertura de código.
                                                    </p>
                                                    ${testData.output.includes('coverage') || testData.output.includes('Coverage') ? 
                                                        '<p style="margin: 0.25rem 0 0 0; color: #2563eb; font-size: 0.8rem;">✓ Se detectaron referencias a coverage en el output.</p>' : 
                                                        '<p style="margin: 0.25rem 0 0 0; color: #d97706; font-size: 0.8rem;">⚠ El coverage puede estar configurado incorrectamente o no aplicarse a este test.</p>'
                                                    }
                                                </div>` : 
                                                `<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px;">
                                                    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                                        <strong>❌ Coverage Report:</strong> Desactivado. No se analizará cobertura de código.
                                                    </p>
                                                </div>`
                                            }
                                            
                                            ${testdoxOutput ? 
                                                `<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: 6px;">
                                                    <p style="margin: 0; color: #7c3aed; font-size: 0.875rem; font-weight: 500;">
                                                        <strong>✅ TestDox Summary Activado:</strong> Los nombres de test deberían estar formateados legiblemente.
                                                    </p>
                                                    ${testData.output.includes('It should') || testData.output.includes('☢') ? 
                                                        '<p style="margin: 0.25rem 0 0 0; color: #8b5cf6; font-size: 0.8rem;">✓ Se detectó formato TestDox en el output.</p>' : 
                                                        '<p style="margin: 0.25rem 0 0 0; color: #d97706; font-size: 0.8rem;">⚠ No se detectó formato TestDox específico.</p>'
                                                    }
                                                </div>` : 
                                                `<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px;">
                                                    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                                        <strong>❌ TestDox Summary:</strong> Desactivado. Nombres de test en formato técnico.
                                                    </p>
                                                </div>`
                                            }
                                            
                                            ${!verboseOutput && !generateCoverage && !testdoxOutput ? 
                                                '<p style="margin: 0; color: #6b7280; font-size: 0.875rem; text-align: center; font-style: italic;">Todas las opciones están desactivadas - output estándar.</p>' : ''
                                            }
                                        </div>
                                    </div>
                                    
                                    <div class="modern-section">
                                        <div class="modern-section-title">💻 Comando Ejecutado</div>
                                        <pre class="modern-code-block modern-code-block-dark"><code>${testData.command}</code></pre>
                                    </div>
                                    
                                    <!-- Información adicional para tests informativos -->
                                    ${testData.output.includes('TABLA DE TRANSIENTS') ? 
                                        `<div class="modern-section">
                                            <div class="modern-section-title">ℹ️ Información del Test</div>
                                            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1rem;">
                                                <p style="margin: 0; color: #0369a1; font-size: 0.875rem;">
                                                    <strong>LicenseTransientsInspectorTest:</strong> Este test inspecciona los transients de licencias 
                                                    almacenados en la base de datos de WordPress, mostrando su estado actual, tiempo de expiración 
                                                    y proporcionando recomendaciones para el mantenimiento del sistema de licencias.
                                                </p>
                                            </div>
                                        </div>` : ''
                                    }
                                </div>
                            </div>
                        `;
                    } else {
                        // Mostrar error
                        if (resultArea) {
                            resultArea.innerHTML = `
                                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 25px rgba(0,0,0,0.1);">
                                    <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 1.5rem;">
                                        <h6 style="margin: 0; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                            ❌ Error ejecutando test: ${testPath}
                                        </h6>
                                    </div>
                                    <div style="padding: 1.5rem;">
                                        <p style="margin: 0; color: #374151;">${data.data?.message || 'Error desconocido'}</p>
                                    </div>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Error ejecutando test específico:', error);
                    
                    // Mostrar error en el área de resultados
                    if (resultArea) {
                        resultArea.innerHTML = `
                            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 25px rgba(0,0,0,0.1);">
                                <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 1.5rem;">
                                    <h6 style="margin: 0; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                        ❌ Error de conectividad
                                    </h6>
                                </div>
                                <div style="padding: 1.5rem;">
                                    <p style="margin: 0; color: #374151;">Error al ejecutar el test: ${error.message}</p>
                                    <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">Archivo: ${testPath}</p>
                                </div>
                            </div>
                        `;
                    }
                });
            };
            
            // Event listener para el botón de actualizar
            if (refreshTestsBtn) {
                refreshTestsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🔄 Actualizando lista de tests...');
                    
                    // Agregar feedback visual al botón
                    const originalContent = refreshTestsBtn.innerHTML;
                    const originalStyle = refreshTestsBtn.style.cssText;
                    
                    // Mostrar estado de carga en el botón
                    refreshTestsBtn.innerHTML = '<span style="font-size: 0.875rem;">🔄</span> Actualizando...';
                    refreshTestsBtn.style.background = 'rgba(255,255,255,0.3)';
                    refreshTestsBtn.style.opacity = '0.7';
                    refreshTestsBtn.disabled = true;
                    
                    // Función para restaurar el botón
                    const restoreButton = () => {
                        setTimeout(() => {
                            refreshTestsBtn.innerHTML = originalContent;
                            refreshTestsBtn.style.cssText = originalStyle;
                            refreshTestsBtn.disabled = false;
                        }, 500); // Pequeño delay para que se vea el cambio
                    };
                    
                    // Llamar a la función de carga y restaurar el botón cuando termine
                    loadTestsList();
                    restoreButton();
                });
            }
            
            // Cargar la lista de tests al inicializar
            setTimeout(() => {
                loadTestsList();
            }, 1000);
            
            // Debug helper - función global para probar la actualización manualmente
            window.debugRefreshTests = function() {
                console.log('🔧 DEBUG: Forzando actualización de tests...');
                const btn = document.getElementById('devtools-refreshTests');
                if (btn) {
                    console.log('✅ Botón encontrado, simulando click...');
                    btn.click();
                } else {
                    console.error('❌ Botón no encontrado');
                }
            };
            
            // Verificar que el botón existe
            setTimeout(() => {
                const refreshBtn = document.getElementById('devtools-refreshTests');
                if (refreshBtn) {
                    console.log('✅ Botón de actualizar tests encontrado correctamente');
                } else {
                    console.warn('⚠️ Botón de actualizar tests NO encontrado');
                }
            }, 2000);
        });
        </script>
        <?php
    }
    
    /**
     * Construir comando PHPUnit
     */
    private function build_phpunit_command($test_types, $verbose = false, $coverage = false, $testdox = false) {
        // Obtener la ruta correcta de PHP
        $php_binary = $this->get_php_binary_path();
        
        // Construir comando con ruta completa de PHP (usar quotes manuales para espacios)
        // Usar el archivo PHPUnit real, no el wrapper que busca php en PATH
        $base_command = '"' . $php_binary . '" ../dev-tools/vendor/phpunit/phpunit/phpunit';
        $options = [];
        
        // Agregar verbosidad
        if ($verbose) {
            $options[] = '--verbose';
        }
        
        // Agregar cobertura
        if ($coverage) {
            $options[] = '--coverage-text';
            error_log("DEBUG BUILD COMMAND - Added --coverage-text option");
        }
        
        // Agregar testdox
        if ($testdox) {
            $options[] = '--testdox';
            error_log("DEBUG BUILD COMMAND - Added --testdox option");
        }
        
        error_log("DEBUG BUILD COMMAND - All options: " . print_r($options, true));
        
        // Determinar qué tests ejecutar según los tipos seleccionados
        $test_paths = [];
        
        // Mapear tipos de test a rutas específicas
        foreach ($test_types as $type) {
            switch ($type) {
                case 'devtools':
                    // Ejecutar TODOS los tests del framework dev-tools
                    $test_paths[] = 'tests/';
                    error_log("DEBUG BUILD COMMAND - Added dev-tools test path: tests/");
                    break;
                case 'plugin':
                    // Ejecutar TODOS los tests del plugin (toda la carpeta tests y subcarpetas)
                    // Como el comando se ejecuta desde plugin-dev-tools, usar rutas relativas desde ahí
                    $test_paths[] = 'tests/';
                    // Bootstrap relativo desde plugin-dev-tools
                    $options[] = '--bootstrap';
                    $options[] = 'tests/bootstrap.php';
                    error_log("DEBUG BUILD COMMAND - Added ALL plugin tests directory: tests/");
                    break;
                default:
                    // Tipo no reconocido, ignorar
                    error_log("DEBUG BUILD COMMAND - Unknown test type: " . $type);
                    break;
            }
        }
        
        // Si no hay rutas válidas de test, usar solo unit tests
        if (empty($test_paths)) {
            $test_path = 'tests/unit/';
            error_log("DEBUG BUILD COMMAND - No valid test paths found, using unit tests");
        } elseif (count($test_paths) == 1) {
            // Un solo tipo de test válido
            $test_path = $test_paths[0];
            error_log("DEBUG BUILD COMMAND - Single test path selected: " . $test_path);
        } else {
            // Múltiples tipos específicos válidos, ejecutar solo esos
            // Para simplificar, ejecutar todos los tests cuando hay múltiples tipos
            $test_path = 'tests/';
            error_log("DEBUG BUILD COMMAND - Multiple test paths, using all tests: " . $test_path);
            error_log("DEBUG BUILD COMMAND - Selected paths were: " . print_r($test_paths, true));
        }
        
        // Construir comando final
        if (!empty($options)) {
            $command = $base_command . ' ' . implode(' ', $options) . ' ' . $test_path;
        } else {
            $command = $base_command . ' ' . $test_path;
        }
        
        return $command;
    }

    /**
     * Ejecutar comando PHPUnit
     */
    private function execute_phpunit($command, $test_types = []) {
        $start_time = microtime(true);
        
        // Determinar directorio de trabajo según el tipo de test
        $original_dir = getcwd();
        $working_dir = null;
        
        // Si incluye tests de plugin, usar plugin-dev-tools
        if (in_array('plugin', $test_types)) {
            $working_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools';
            error_log("DEBUG PHPUNIT EXECUTION - Using plugin-dev-tools directory for plugin tests");
        } else {
            // Para otros tests (unit, integration, database), permanecer en dev-tools
            $working_dir = dirname(__DIR__);
            error_log("DEBUG PHPUNIT EXECUTION - Using dev-tools directory for framework tests");
        }
        
        if (!is_dir($working_dir)) {
            throw new \Exception("Directorio de trabajo no encontrado: {$working_dir}");
        }
        
        error_log("DEBUG PHPUNIT EXECUTION - Changing to directory: " . $working_dir);
        chdir($working_dir);
        
        try {
            // Ejecutar el comando tal como viene de build_phpunit_command
            $start_time = microtime(true);
            
            error_log("DEBUG PHPUNIT EXECUTION - Original command: " . $command);
            
            // Configurar el PATH para incluir el directorio del PHP binary
            $php_binary = $this->get_php_binary_path();
            $php_dir = dirname($php_binary);
            $current_path = getenv('PATH');
            $new_path = $php_dir . ':' . $current_path;
            
            // Ejecutar con PATH configurado - USAR EL COMANDO ORIGINAL
            $output = [];
            $exit_code = 0;
            
            // Configurar el entorno para la ejecución
            $env_command = "export PATH=\"{$new_path}\" && " . $command . ' 2>&1';
            
            error_log("DEBUG PHPUNIT EXECUTION - Final command with PATH: " . $env_command);
            
            exec($env_command, $output, $exit_code);
            
            $execution_time = round((microtime(true) - $start_time) * 1000);
            
            $output_string = implode("\n", $output);
            
            error_log("DEBUG PHPUNIT EXECUTION - Exit code: " . $exit_code);
            error_log("DEBUG PHPUNIT EXECUTION - Output length: " . strlen($output_string));
            
            return [
                'output' => $output_string,
                'exit_code' => $exit_code,
                'execution_time' => $execution_time
            ];
            
        } finally {
            // Restaurar directorio original
            chdir($original_dir);
        }
    }

    /**
     * Ejecución mejorada de PHPUnit para tests individuales con captura completa de output
     */
    private function execute_phpunit_enhanced($command, $test_file) {
        $start_time = microtime(true);
        
        // Determinar directorio de trabajo - usar plugin-dev-tools para tests individuales
        $original_dir = getcwd();
        $working_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools';
        
        if (!is_dir($working_dir)) {
            throw new \Exception("Directorio plugin-dev-tools no encontrado: {$working_dir}");
        }
        
        error_log("DEBUG ENHANCED EXECUTION - Changing to directory: " . $working_dir);
        error_log("DEBUG ENHANCED EXECUTION - Test file: " . $test_file);
        chdir($working_dir);
        
        try {
            // Configurar el PATH para incluir el directorio del PHP binary
            $php_binary = $this->get_php_binary_path();
            $php_dir = dirname($php_binary);
            $current_path = getenv('PATH');
            $new_path = $php_dir . ':' . $current_path;
            
            // Ejecutar con configuración especial para capturar todo el output
            $output = [];
            $exit_code = 0;
            
            // Usar un enfoque mejorado para capturar output completo (incluye echo statements)
            $env_command = "export PATH=\"{$new_path}\" && " . $command . ' 2>&1';
            
            error_log("DEBUG ENHANCED EXECUTION - Command: " . $env_command);
            
            // Ejecutar y capturar todo
            exec($env_command, $output, $exit_code);
            
            $execution_time = round((microtime(true) - $start_time) * 1000);
            $output_string = implode("\n", $output);
            
            error_log("DEBUG ENHANCED EXECUTION - Exit code: " . $exit_code);
            error_log("DEBUG ENHANCED EXECUTION - Output length: " . strlen($output_string));
            error_log("DEBUG ENHANCED EXECUTION - Raw output (first 1000 chars): " . substr($output_string, 0, 1000));
            
            // Si el output parece truncado o vacío, intentar una segunda captura
            if (strlen($output_string) < 100 && $exit_code === 0) {
                error_log("DEBUG ENHANCED EXECUTION - Output seems short, trying alternative capture method");
                
                // Método alternativo usando shell_exec para capturar posibles echo statements
                $alt_output = shell_exec($env_command);
                if (!empty($alt_output) && strlen($alt_output) > strlen($output_string)) {
                    $output_string = $alt_output;
                    error_log("DEBUG ENHANCED EXECUTION - Alternative capture provided more output: " . strlen($alt_output) . " chars");
                }
            }
            
            return [
                'output' => $output_string,
                'exit_code' => $exit_code,
                'execution_time' => $execution_time
            ];
            
        } finally {
            // Restaurar directorio original
            chdir($original_dir);
        }
    }

    /**
     * Parsear salida de tests para extraer resumen
     */
    private function parse_test_output($output) {
        $summary = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'risky' => 0,
            'assertions' => 0,
            'incomplete' => 0,
            'time' => null,
            'memory' => null,
            'status' => 'unknown'
        ];
        
        error_log("DEBUG PARSE - Raw output length: " . strlen($output));
        error_log("DEBUG PARSE - Full output: " . $output);
        error_log("DEBUG PARSE - Last 500 chars: " . substr($output, -500));
        
        // Buscar la línea de resumen final más completa
        // Formato: "Tests: 18, Assertions: 37, Errors: 2, Failures: 2, Skipped: 2, Incomplete: 1, Risky: 1."
        $final_summary_pattern = '/Tests: (\d+), Assertions: (\d+)(?:, Errors?: (\d+))?(?:, Failures?: (\d+))?(?:, Skipped: (\d+))?(?:, Incomplete: (\d+))?(?:, Risky: (\d+))?/';
        
        if (preg_match($final_summary_pattern, $output, $matches)) {
            $summary['total_tests'] = (int)$matches[1];
            $summary['assertions'] = (int)$matches[2];
            $summary['errors'] = isset($matches[3]) && $matches[3] !== '' ? (int)$matches[3] : 0;
            $summary['failed'] = isset($matches[4]) && $matches[4] !== '' ? (int)$matches[4] : 0;
            $summary['skipped'] = isset($matches[5]) && $matches[5] !== '' ? (int)$matches[5] : 0;
            $summary['incomplete'] = isset($matches[6]) && $matches[6] !== '' ? (int)$matches[6] : 0;
            $summary['risky'] = isset($matches[7]) && $matches[7] !== '' ? (int)$matches[7] : 0;
            
            error_log("DEBUG PARSE - Complete parsing successful:");
            error_log("  Total: {$summary['total_tests']}, Assertions: {$summary['assertions']}");
            error_log("  Errors: {$summary['errors']}, Failures: {$summary['failed']}");
            error_log("  Skipped: {$summary['skipped']}, Incomplete: {$summary['incomplete']}, Risky: {$summary['risky']}");
        } else {
            // Fallback: buscar cada métrica individualmente
            error_log("DEBUG PARSE - Using fallback individual parsing");
            
            // También buscar formato alternativo como "OK (8 tests, 27 assertions)"
            if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
                $summary['total_tests'] = (int)$matches[1];
                $summary['assertions'] = (int)$matches[2];
                error_log("DEBUG PARSE - Found OK format: {$summary['total_tests']} tests, {$summary['assertions']} assertions");
            }
            
            // Buscar tests ejecutados individualmente
            if (preg_match('/(\d+) test[s]? completed/i', $output, $matches)) {
                $summary['total_tests'] = (int)$matches[1];
            } elseif (preg_match('/test[s]? run: (\d+)/i', $output, $matches)) {
                $summary['total_tests'] = (int)$matches[1];
            }
            
            // Buscar assertions
            if (preg_match('/(\d+) assertion[s]?/i', $output, $matches)) {
                $summary['assertions'] = (int)$matches[1];
            }
        }
        
        // Calcular tests pasados
        if ($summary['total_tests'] > 0 && $summary['passed'] === 0) {
            $summary['passed'] = $summary['total_tests'] - $summary['failed'] - $summary['errors'] - $summary['skipped'] - $summary['incomplete'];
        }
        
        // Buscar información de tiempo
        if (preg_match('/Time: ([0-9.]+)\s*([a-zA-Z]+)/', $output, $matches)) {
            $time_value = $matches[1];
            $time_unit = strtolower($matches[2]);
            
            if ($time_unit === 'ms' || $time_unit === 'milliseconds') {
                $summary['time'] = $time_value . ' ms';
            } elseif ($time_unit === 's' || $time_unit === 'seconds') {
                $summary['time'] = $time_value . ' s';
            } else {
                $summary['time'] = $time_value . ' ' . $time_unit;
            }
        }
        
        // Buscar información de memoria
        if (preg_match('/Memory: ([0-9.]+)\s*([a-zA-Z]+)/', $output, $matches)) {
            $summary['memory'] = $matches[1] . ' ' . $matches[2];
        }
        
        // Determinar el estado general
        if ($summary['failed'] > 0 || $summary['errors'] > 0) {
            $summary['status'] = 'error';
        } elseif ($summary['skipped'] > 0 || $summary['incomplete'] > 0) {
            $summary['status'] = 'warning';
        } elseif ($summary['risky'] > 0) {
            // Para tests informativos que generan output útil, considerarlos como success si no hay errores
            if (stripos($output, 'TABLA DE TRANSIENTS') !== false || 
                stripos($output, 'INSPECCIÓN') !== false ||
                stripos($output, '===') !== false) {
                $summary['status'] = 'success';
                error_log("DEBUG PARSE - Risky test detected as informational with valuable content");
            } else {
                $summary['status'] = 'warning';
            }
        } elseif ($summary['total_tests'] > 0) {
            $summary['status'] = 'success';
        } else {
            // Para tests informativos que no tienen estructura tradicional de test
            if (stripos($output, 'TABLA DE TRANSIENTS') !== false || 
                stripos($output, 'INSPECCIÓN') !== false ||
                stripos($output, '===') !== false ||
                preg_match('/PHPUnit.*OK/i', $output)) {
                // Es un test informativo con contenido válido
                $summary['status'] = 'success';
                $summary['total_tests'] = 1;
                $summary['passed'] = 1;
                $summary['assertions'] = 1;
                error_log("DEBUG PARSE - Detected informational test with valid content");
            } else {
                $summary['status'] = 'unknown';
            }
        }
        
        error_log("DEBUG PARSE - Final summary: " . json_encode($summary));
        
        return $summary;
    }

    /**
     * Ejecutar tests completos con opciones
     */
    private function run_tests_with_options($test_types = ['unit'], $verbose = false, $coverage = false, $testdox = false) {
        try {
            // Construir comando PHPUnit
            $command = $this->build_phpunit_command($test_types, $verbose, $coverage, $testdox);
            
            // Ejecutar tests
            $result = $this->execute_phpunit($command, $test_types);
            
            return [
                'success' => true,
                'data' => [
                    'command' => $command,
                    'output' => $result['output'],
                    'return_code' => $result['exit_code'],
                    'execution_time' => $result['execution_time'],
                    'summary' => $this->parse_test_output($result['output'])
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error ejecutando tests: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ejecutar un archivo de test específico
     */
    private function run_specific_test_file($test_file, $verbose = false, $coverage = false, $testdox = false) {
        try {
            // Validar que el archivo de test existe
            $tests_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools/tests';
            $full_test_path = $tests_dir . '/' . $test_file;
            
            if (!file_exists($full_test_path)) {
                throw new \Exception("Archivo de test no encontrado: {$test_file}");
            }
            
            // Construir comando PHPUnit para el archivo específico usando la configuración correcta
            $php_binary = $this->get_php_binary_path();
            $phpunit_path = '"' . $php_binary . '" ../dev-tools/vendor/phpunit/phpunit/phpunit';
            $options = [];
            
            // Usar la configuración específica para plugin-dev-tools que incluye el bootstrap de WordPress
            $options[] = '--configuration=phpunit-plugin-only.xml';
            
            // Aplicar opciones basadas en los parámetros del usuario
            if ($verbose) {
                $options[] = '--verbose';
                error_log("DEBUG SPECIFIC TEST - Added --verbose option (user requested)");
            }
            
            // Siempre agregar debug para capturar echo statements en tests informativos
            $options[] = '--debug';
            
            // Agregar opciones adicionales basadas en los parámetros recibidos
            if ($coverage) {
                $options[] = '--coverage-text';
                $options[] = '--coverage-html=reports/coverage';
                error_log("DEBUG SPECIFIC TEST - Added coverage options");
            }
            
            if ($testdox) {
                $options[] = '--testdox';
                error_log("DEBUG SPECIFIC TEST - Added --testdox option");
            }
            
            error_log("DEBUG SPECIFIC TEST - User options: verbose=" . ($verbose ? 'true' : 'false') . 
                     ", coverage=" . ($coverage ? 'true' : 'false') . 
                     ", testdox=" . ($testdox ? 'true' : 'false'));
            error_log("DEBUG SPECIFIC TEST - Final PHPUnit options: " . implode(' ', $options));
            
            // El comando se ejecutará desde plugin-dev-tools, usar ruta relativa con prefijo tests/
            $test_path_for_command = 'tests/' . $test_file;
            $command = $phpunit_path . ' ' . implode(' ', $options) . ' ' . escapeshellarg($test_path_for_command);
            
            error_log("DEBUG SPECIFIC TEST - Command with WordPress bootstrap: " . $command);
            
            // Ejecutar el test específico con captura mejorada de output
            $result = $this->execute_phpunit_enhanced($command, $test_file);
            
            return [
                'success' => true,
                'data' => [
                    'command' => $command,
                    'output' => $result['output'],
                    'return_code' => $result['exit_code'],
                    'execution_time' => $result['execution_time'],
                    'test_file' => $test_file,
                    'summary' => $this->parse_test_output($result['output']),
                    'is_specific_test' => true
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error ejecutando test específico: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener la ruta al ejecutable PHP
     */
    private function get_php_path() {
        // Rutas comunes para Local by WP Engine
        $possible_paths = [
            '/opt/homebrew/bin/php',           // Local by WP Engine (Apple Silicon)
            '/usr/local/bin/php',              // Local by WP Engine (Intel)
            '/usr/bin/php',                    // Sistema estándar
            '/usr/bin/php8.1',                 // PHP 8.1 específico
            '/usr/bin/php8.0',                 // PHP 8.0 específico
            '/usr/bin/php7.4',                 // PHP 7.4 específico
        ];
        
        // Probar cada ruta
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Si no encontramos ninguna, intentar usar which
        $which_result = shell_exec('which php 2>/dev/null');
        if (!empty($which_result)) {
            $php_path = trim($which_result);
            if (file_exists($php_path) && is_executable($php_path)) {
                return $php_path;
            }
        }
        
        // Fallback a php genérico (puede fallar)
        return 'php';
    }
    
    /**
     * Detecta la ruta del binario PHP según el entorno
     */
    private function get_php_binary_path() {
        // SIMPLIFICADO: Usar PHP del sistema macOS
        // Priorizar el PHP instalado por Homebrew que está en PATH
        
        // Primero, intentar con which php para obtener el PHP activo del sistema
        $which_php = shell_exec('which php 2>/dev/null');
        if ($which_php && trim($which_php)) {
            $php_path = trim($which_php);
            if (file_exists($php_path) && is_executable($php_path)) {
                error_log("DEBUG PHP DETECTION - Found system PHP via 'which': " . $php_path);
                return $php_path;
            }
        }
        
        // Rutas estándar del sistema macOS (orden de prioridad)
        $standard_paths = [
            '/opt/homebrew/bin/php',      // Homebrew Apple Silicon
            '/usr/local/bin/php',         // Homebrew Intel
            '/usr/bin/php',               // PHP nativo de macOS
            '/Applications/XAMPP/xamppfiles/bin/php'  // XAMPP
        ];
        
        foreach ($standard_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                error_log("DEBUG PHP DETECTION - Found system PHP at: " . $path);
                return $path;
            }
        }
        
        // Último recurso: usar 'php' y esperar que esté en el PATH del sistema
        error_log("DEBUG PHP DETECTION - Using fallback 'php' command (should work with system PATH)");
        return 'php';
    }

    /**
     * Check if a test directory has test files
     *
     * @param string $test_dir Absolute path to the test directory
     * @return bool True if test files exist, false otherwise
     */
    private function has_test_files($test_dir) {
        error_log("DEBUG TEST FILES CHECK - Checking directory: " . $test_dir);
        
        if (!is_dir($test_dir)) {
            error_log("DEBUG TEST FILES CHECK - Directory does not exist");
            return false;
        }
        
        // Search for PHP test files
        $test_files = glob($test_dir . '/*Test.php');
        $has_files = !empty($test_files);
        
        error_log("DEBUG TEST FILES CHECK - Found " . count($test_files) . " test files");
        if ($has_files) {
            error_log("DEBUG TEST FILES CHECK - Test files: " . implode(', ', array_map('basename', $test_files)));
        }
        
        return $has_files;
    }
    
    /**
     * Obtiene el nombre del archivo de test básico dinámico basado en el nombre del plugin
     */
    private function get_basic_test_filename() {
        // Obtener el nombre del plugin desde la ruta del directorio
        $plugin_name = basename(dirname(dirname(dirname(__FILE__))));
        
        // Convertir a formato seguro para nombre de clase (similar a FileOverrideSystem.php)
        $safe_plugin_name = preg_replace('/[^a-zA-Z0-9_]/', '', ucwords(str_replace(['-', '_'], ' ', $plugin_name)));
        $safe_plugin_name = str_replace(' ', '', $safe_plugin_name);
        
        return "tests/unit/{$safe_plugin_name}BasicTest.php";
    }
}
