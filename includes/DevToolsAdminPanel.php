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
        add_action('wp_ajax_dev_tools_run_quick_test', [$this, 'ajax_run_quick_test']);
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
     * Renderiza el header simplificado para tests
     */
    private function render_header($title = 'Tests') {
        ?>
        <div class="wrap">
            <div class="container-fluid">
                <!-- Header Principal -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center bg-primary text-white p-3 rounded">
                            <div>
                                <h1 class="h3 mb-1">🔧 <?php echo esc_html($this->config['name']); ?> - Test Runner</h1>
                                <p class="mb-0 opacity-75">v<?php echo esc_html($this->config['version']); ?> - Ejecución y gestión de tests</p>
                            </div>
                            <div class="text-end">
                                <small class="d-block">Entorno: <?php echo $this->get_environment_badge(); ?></small>
                                <small>WordPress <?php echo get_bloginfo('version'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
        <?php
    }
    
    /**
     * Renderiza el footer común
     */
    private function render_footer() {
        ?>
                <!-- Footer -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="border-top pt-3 text-muted text-center">
                            <small>
                                <?php echo esc_html($this->config['name']); ?> - 
                                Test Runner simplificado para desarrollo WordPress
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    

    
    /**
     * Obtiene el badge del entorno actual
     */
    private function get_environment_badge() {
        $site_detector = $this->modules['SiteUrlDetectionModule'] ?? null;
        if ($site_detector) {
            $env_info = $site_detector->get_environment_info();
            if ($env_info['is_local_wp']) {
                return '<span class="badge bg-warning text-dark">Local by WP Engine</span>';
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return '<span class="badge bg-info">Development</span>';
        }
        
        return '<span class="badge bg-success">Production</span>';
    }
    
    /**
     * Renderiza la página simplificada solo con tests
     */
    public function render_dashboard() {
        $this->render_header('Tests');
        ?>
        
        <!-- Contenido principal - Solo Tests -->
        <div class="row">
            <div class="col-12">
                <?php $this->render_tests_content(); ?>
            </div>
        </div>
        
        <?php
        $this->render_footer();
    }
    
    /**
     * Contenido de Tests
     */
    private function render_tests_content() {
        ?>
        <div class="row">
            <div class="col-lg-4">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">🧪 Test Runner</h5>
                        <small class="text-muted">Ejecutar tests PHPUnit con diferentes opciones</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tipos de Test</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="devtools-unitTests" checked>
                                        <label class="form-check-label" for="devtools-unitTests">
                                            Unit Tests
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="devtools-integrationTests">
                                        <label class="form-check-label" for="devtools-integrationTests">
                                            Integration Tests
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="devtools-databaseTests">
                                        <label class="form-check-label" for="devtools-databaseTests">
                                            Database Tests
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Opciones de Salida</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="devtools-verboseOutput">
                                        <label class="form-check-label" for="devtools-verboseOutput">
                                            Verbose Output
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="devtools-generateCoverage">
                                        <label class="form-check-label" for="devtools-generateCoverage">
                                            Generate Coverage
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="devtools-testdoxOutput">
                                        <label class="form-check-label" for="devtools-testdoxOutput">
                                            TestDox Summary
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button id="devtools-runTests" class="btn btn-success" type="button" data-test-action="run-full" data-original-content='<i class="dashicons dashicons-yes-alt"></i> 🚀 Run Selected Tests'>
                                <i class="dashicons dashicons-yes-alt"></i> 🚀 Run Selected Tests
                            </button>
                            <button id="devtools-runQuickTest" class="btn btn-outline-info" type="button" data-test-action="run-quick" data-original-content='<i class="dashicons dashicons-performance"></i> ⚡ Quick Test'>
                                <i class="dashicons dashicons-performance"></i> ⚡ Quick Test
                            </button>
                            <button id="devtools-clearResults" class="btn btn-outline-secondary btn-sm" type="button" data-test-action="clear" data-original-content='<i class="dashicons dashicons-dismiss"></i> Clear Results'>
                                <i class="dashicons dashicons-dismiss"></i> Clear Results
                            </button>
                            <button id="devtools-testConnectivity" class="btn btn-outline-primary btn-sm" type="button" data-test-action="connectivity" data-original-content='<i class="dashicons dashicons-admin-network"></i> Test Connectivity'>
                                <i class="dashicons dashicons-admin-network"></i> Test Connectivity
                            </button>
                        </div>
                        
                        <!-- Estado del runner -->
                        <div id="devtools-testStatus" class="mt-3" style="display: none;">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span id="devtools-statusText">Ejecutando tests...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">📈 Test Results</h5>
                            <small class="opacity-75">Output en tiempo real</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="devtools-testResults" class="p-3" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                            <div class="text-center text-muted py-5">
                                <i class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">No tests executed yet...</p>
                                <small>Selecciona los tipos de test y presiona "Run Selected Tests"</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript específico para tests -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialización directa del TestRunner (sin tabs)
            console.log('🔧 Inicializando Test Runner simplificado...');
            
            // Event listeners específicos para cada botón
            const runTestsBtn = document.getElementById('devtools-runTests');
            const runQuickTestBtn = document.getElementById('devtools-runQuickTest');
            const clearResultsBtn = document.getElementById('devtools-clearResults');
            const testConnectivityBtn = document.getElementById('devtools-testConnectivity');
            
            if (runTestsBtn) {
                runTestsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🔍 Ejecutando tests completos...');
                    
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
            
            if (runQuickTestBtn) {
                runQuickTestBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('⚡ Ejecutando test rápido...');
                    
                    if (window.devTools && window.devTools.testRunner) {
                        if (!window.devTools.testRunner.isRunning) {
                            window.devTools.testRunner.runQuickTest();
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
                });
            }
            
            if (testConnectivityBtn) {
                testConnectivityBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🌐 Probando conectividad...');
                    
                    if (window.devTools && window.devTools.testRunner) {
                        if (!window.devTools.testRunner.isRunning) {
                            window.devTools.testRunner.testConnectivity();
                        } else {
                            console.log('🔍 Test ya ejecutándose, ignorando click');
                        }
                    } else {
                        console.error('TestRunner no disponible');
                    }
                });
            }
            
            // Verificar elementos de la UI
            setTimeout(() => {
                const criticalElements = [
                    'devtools-testResults',
                    'devtools-runTests', 
                    'devtools-runQuickTest'
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
        $available_test_dirs = ['unit', 'integration', 'database'];
        
        // Mapear tipos de test a rutas específicas y verificar que existan tests
        foreach ($test_types as $type) {
            $test_dir = "tests/{$type}/";
            
            switch ($type) {
                case 'unit':
                    // Siempre disponible
                    $test_paths[] = $test_dir;
                    error_log("DEBUG BUILD COMMAND - Added unit test path: " . $test_dir);
                    break;
                case 'integration':
                    // Verificar si hay tests de integración (no solo .gitkeep)
                    $plugin_dev_tools_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools/';
                    $integration_dir = $plugin_dev_tools_dir . 'tests/integration/';
                    if ($this->has_test_files($integration_dir)) {
                        $test_paths[] = $test_dir;
                        error_log("DEBUG BUILD COMMAND - Added integration test path: " . $test_dir);
                    } else {
                        error_log("DEBUG BUILD COMMAND - No integration tests found, skipping");
                    }
                    break;
                case 'database':
                    // Verificar si hay tests de base de datos (no solo .gitkeep)
                    $plugin_dev_tools_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools/';
                    $database_dir = $plugin_dev_tools_dir . 'tests/database/';
                    if ($this->has_test_files($database_dir)) {
                        $test_paths[] = $test_dir;
                        error_log("DEBUG BUILD COMMAND - Added database test path: " . $test_dir);
                    } else {
                        error_log("DEBUG BUILD COMMAND - No database tests found, skipping");
                    }
                    break;
                default:
                    // Tipo no reconocido, usar como ruta directa si existe
                    $test_paths[] = $test_dir;
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
        
        $command = $base_command . ' ' . $test_path;
        
        if (!empty($options)) {
            $command .= ' ' . implode(' ', $options);
        }
        
        return $command;
    }

    /**
     * Ejecutar comando PHPUnit
     */
    private function execute_phpunit($command) {
        $start_time = microtime(true);
        
        // Cambiar al directorio plugin-dev-tools (sistema override para tests específicos del plugin)
        $original_dir = getcwd();
        $plugin_dev_tools_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools';
        
        if (!is_dir($plugin_dev_tools_dir)) {
            throw new \Exception("Directorio plugin-dev-tools no encontrado: {$plugin_dev_tools_dir}");
        }
        
        error_log("DEBUG PHPUNIT EXECUTION - Changing to override directory: " . $plugin_dev_tools_dir);
        chdir($plugin_dev_tools_dir);
        
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
     * Parsear salida de tests para extraer resumen
     */
    private function parse_test_output($output) {
        $summary = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'assertions' => 0,
            'time' => null,
            'memory' => null,
            'status' => 'unknown'
        ];
        
        error_log("DEBUG PARSE - Raw output length: " . strlen($output));
        
        // Buscar línea de resumen en múltiples formatos:
        // Formato 1: "Tests: 7, Assertions: 17, Failures: 1, Skipped: 1, Risky: 1."
        // Formato 2: "OK (26 tests, 54 assertions)" o "ERRORS! (26 tests, 54 assertions, 2 failures)"
        if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $output, $matches)) {
            $summary['total_tests'] = (int)$matches[1];
            $summary['assertions'] = (int)$matches[2];
            error_log("DEBUG PARSE - Format 1 - Found total_tests: {$summary['total_tests']}, assertions: {$summary['assertions']}");
        } elseif (preg_match('/\((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
            $summary['total_tests'] = (int)$matches[1];
            $summary['assertions'] = (int)$matches[2];
            error_log("DEBUG PARSE - Format 2 - Found total_tests: {$summary['total_tests']}, assertions: {$summary['assertions']}");
        }
        
        // Buscar tiempo y memoria: "Time: 00:00.808, Memory: 42.50 MB"
        if (preg_match('/Time: ([\d:\.]+), Memory: ([\d\.]+ \w+)/', $output, $matches)) {
            $summary['time'] = $matches[1];
            $summary['memory'] = $matches[2];
            error_log("DEBUG PARSE - Found time: {$summary['time']}, memory: {$summary['memory']}");
        }
        
        // Buscar específicamente errores, fallos y omitidos en cualquier parte del output
        // Primero buscar en la línea de resumen final si está presente
        if (preg_match('/(Tests: \d+, Assertions: \d+)(?:, Failures?: (\d+))?(?:, Errors?: (\d+))?(?:, Skipped: (\d+))?(?:, Risky: (\d+))?/', $output, $matches)) {
            if (isset($matches[2]) && $matches[2] !== '') {
                $summary['failed'] = (int)$matches[2];
                error_log("DEBUG PARSE - Found failures from summary: {$summary['failed']}");
            }
            if (isset($matches[3]) && $matches[3] !== '') {
                $summary['errors'] = (int)$matches[3];
                error_log("DEBUG PARSE - Found errors from summary: {$summary['errors']}");
            }
            if (isset($matches[4]) && $matches[4] !== '') {
                $summary['skipped'] = (int)$matches[4];
                error_log("DEBUG PARSE - Found skipped from summary: {$summary['skipped']}");
            }
        } else {
            // Fallback: buscar individualmente si no está en el resumen
            if (preg_match('/Errors?: (\d+)/', $output, $matches)) {
                $summary['errors'] = (int)$matches[1];
                error_log("DEBUG PARSE - Found errors (fallback): {$summary['errors']}");
            }
            
            if (preg_match('/Failures?: (\d+)/', $output, $matches)) {
                $summary['failed'] = (int)$matches[1];
                error_log("DEBUG PARSE - Found failures (fallback): {$summary['failed']}");
            }
            
            if (preg_match('/Skipped: (\d+)/', $output, $matches)) {
                $summary['skipped'] = (int)$matches[1];
                error_log("DEBUG PARSE - Found skipped (fallback): {$summary['skipped']}");
            }
        }
        
        // Determinar estado general basado en la salida
        if (strpos($output, 'OK (') !== false && $summary['errors'] === 0 && $summary['failed'] === 0) {
            $summary['status'] = 'success';
            $summary['passed'] = $summary['total_tests'] - $summary['skipped'];
            error_log("DEBUG PARSE - Status: success, passed: {$summary['passed']}");
        } elseif (strpos($output, 'ERRORS!') !== false || strpos($output, 'FAILURES!') !== false) {
            $summary['status'] = 'error';
            $summary['passed'] = $summary['total_tests'] - $summary['errors'] - $summary['failed'] - $summary['skipped'];
            error_log("DEBUG PARSE - Status: error, passed: {$summary['passed']}");
        } elseif (strpos($output, 'OK, but incomplete, skipped, or risky tests!') !== false) {
            $summary['status'] = 'warning';
            $summary['passed'] = $summary['total_tests'] - $summary['errors'] - $summary['failed'] - $summary['skipped'];
            error_log("DEBUG PARSE - Status: warning, passed: {$summary['passed']}");
        } else {
            // Fallback: calcular basado en lo que tenemos
            $summary['passed'] = max(0, $summary['total_tests'] - $summary['errors'] - $summary['failed'] - $summary['skipped']);
            error_log("DEBUG PARSE - Status: fallback, passed: {$summary['passed']}");
        }
        
        // Log final del resumen
        error_log("DEBUG PARSE - Final summary: " . json_encode($summary));
        
        return $summary;
    }

    /**
     * Ejecutar test rápido (solo básicos)
     */
    private function run_quick_test() {
        try {
            // Obtener la ruta correcta de PHP y ejecutar solo el test básico
            $php_binary = $this->get_php_binary_path();
            $basic_test_file = $this->get_basic_test_filename();
            $command = '"' . $php_binary . '" ../dev-tools/vendor/phpunit/phpunit/phpunit ' . $basic_test_file . ' --verbose';
            
            error_log("DEBUG TEST EXECUTION - Final command: " . $command);
            
            $result = $this->execute_phpunit($command);
            
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
                'error' => 'Error ejecutando quick test: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar tests completos con opciones
     */
    private function run_tests_with_options($test_types = ['unit'], $verbose = false, $coverage = false, $testdox = false) {
        try {
            // Construir comando PHPUnit
            $command = $this->build_phpunit_command($test_types, $verbose, $coverage, $testdox);
            
            // Ejecutar tests
            $result = $this->execute_phpunit($command);
            
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
     * Handler AJAX para ejecutar test rápido
     */
    public function ajax_run_quick_test() {
        // Debug del nonce recibido
        $received_nonce = $_POST['nonce'] ?? '';
        $expected_action = 'dev_tools_nonce';
        
        // Log de debugging (remover en producción)
        error_log("DEBUG QUICK TEST NONCE - Received: {$received_nonce}");
        error_log("DEBUG QUICK TEST NONCE - Expected action: {$expected_action}");
        error_log("DEBUG QUICK TEST NONCE - Verification result: " . (wp_verify_nonce($received_nonce, $expected_action) ? 'VALID' : 'INVALID'));
        
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
        
        try {
            $result = $this->run_quick_test();
            
            if ($result['success']) {
                wp_send_json_success($result['data']);
            } else {
                wp_send_json_error([
                    'message' => $result['error'] ?? 'Error desconocido ejecutando quick test'
                ]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Error ejecutando quick test: ' . $e->getMessage()
            ]);
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
