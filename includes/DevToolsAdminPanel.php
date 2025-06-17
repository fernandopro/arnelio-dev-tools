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
                                    <h1 class="h2 mb-1" style="font-weight: 600; margin: 0;"><?php echo esc_html($this->config['name']); ?></h1>
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
                                    <?php echo esc_html($this->config['name']); ?> · 
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
     * Contenido de Tests - Diseño Moderno y Minimalista
     */
    private function render_tests_content() {
        ?>
        <div class="row g-4">
            <!-- Panel de Control -->
            <div class="col-xl-4">
                <div class="devtools-card" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); border: none; overflow: hidden;">
                    <!-- Header del card -->
                    <div class="devtools-card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; margin: 0;">
                        <h5 class="mb-0" style="font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 1.2em;">🧪</span>
                            Test Runner
                        </h5>
                        <p class="mb-0" style="opacity: 0.9; font-size: 0.875rem; margin-top: 0.25rem;">Ejecutar tests PHPUnit con opciones avanzadas</p>
                    </div>
                    
                    <!-- Contenido del card -->
                    <div class="devtools-card-body" style="padding: 2rem;">
                        <!-- Tipos de Test -->
                        <div class="devtools-section mb-4">
                            <label class="devtools-label" style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 1rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Tipos de Test</label>
                            <div class="devtools-checkbox-group" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <label class="devtools-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="devtools-unitTests" checked style="margin-right: 0.75rem; accent-color: #667eea;">
                                    <span style="color: #475569; font-weight: 500;">Unit Tests</span>
                                </label>
                                <label class="devtools-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="devtools-integrationTests" style="margin-right: 0.75rem; accent-color: #667eea;">
                                    <span style="color: #475569; font-weight: 500;">Integration Tests</span>
                                </label>
                                <label class="devtools-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="devtools-databaseTests" style="margin-right: 0.75rem; accent-color: #667eea;">
                                    <span style="color: #475569; font-weight: 500;">Database Tests</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Opciones de Salida -->
                        <div class="devtools-section mb-4">
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
                        
                        <!-- Botones de Acción -->
                        <div class="devtools-actions" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button id="devtools-runTests" class="devtools-btn devtools-btn-primary" type="button" data-test-action="run-full" data-original-content='🚀 Run Selected Tests' style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                🚀 Run Selected Tests
                            </button>
                            <button id="devtools-runQuickTest" class="devtools-btn devtools-btn-secondary" type="button" data-test-action="run-quick" data-original-content='⚡ Quick Test' style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; padding: 0.875rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                ⚡ Quick Test
                            </button>
                            
                            <!-- Botones secundarios -->
                            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                <button id="devtools-clearResults" class="devtools-btn devtools-btn-outline" type="button" data-test-action="clear" data-original-content='Clear Results' style="background: transparent; color: #64748b; border: 2px solid #e2e8f0; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; flex: 1; font-size: 0.875rem;">
                                    Clear Results
                                </button>
                                <button id="devtools-testConnectivity" class="devtools-btn devtools-btn-outline" type="button" data-test-action="connectivity" data-original-content='Test Connectivity' style="background: transparent; color: #64748b; border: 2px solid #e2e8f0; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; flex: 1; font-size: 0.875rem;">
                                    Test Connectivity
                                </button>
                            </div>
                        </div>
                        
                        <!-- Estado del runner -->
                        <div id="devtools-testStatus" class="devtools-status" style="display: none; margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="devtools-spinner" style="width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                <span id="devtools-statusText" style="font-weight: 500;">Ejecutando tests...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Resultados -->
            <div class="col-xl-8">
                <div class="devtools-card" style="background: #ffffff; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); border: none; overflow: hidden; height: fit-content;">
                    <!-- Header del card -->
                    <div class="devtools-card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; margin: 0;">
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <div>
                                <h5 class="mb-0" style="font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 1.2em;">�</span>
                                    Test Results
                                </h5>
                                <p class="mb-0" style="opacity: 0.9; font-size: 0.875rem; margin-top: 0.25rem;">Output y análisis en tiempo real</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenido de resultados -->
                    <div class="devtools-card-body" style="padding: 0;">
                        <div id="devtools-testResults" class="devtools-results" style="min-height: 500px; max-height: 700px; overflow-y: auto; padding: 2rem; background: #f8fafc;">
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
        
        <!-- Estilos CSS modernos embebidos -->
        <style>
            #wpfooter {
                display: none !important;
            }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .devtools-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .devtools-btn-outline:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }
        
        .devtools-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 4px;
        }
        
        .devtools-results::-webkit-scrollbar {
            width: 8px;
        }
        
        .devtools-results::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .devtools-results::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .devtools-results::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        </style>
        
        <!-- JavaScript específico para tests -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialización directa del TestRunner moderno
            console.log('🔧 Inicializando Test Runner moderno...');
            
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
