<?php
/**
 * Test Runner Module - Ejecuta tests desde la interfaz web
 * 
 * @package DevTools
 * @version 3.0
 */

namespace DevTools;

if (!defined('ABSPATH')) {
    exit;
}

class TestRunnerModule extends DevToolsModuleBase {
    
    public function __construct() {
        parent::__construct();
        $this->module_name = 'TestRunner';
        $this->version = '1.0.0';
    }
    
    /**
     * Inicializar el módulo
     */
    public function init() {
        // Registrar comandos AJAX
        $this->register_ajax_command('run_tests', [$this, 'run_tests']);
        $this->register_ajax_command('run_quick_test', [$this, 'run_quick_test']);
        $this->register_ajax_command('get_test_status', [$this, 'get_test_status']);
        
        return true;
    }
    
    /**
     * Ejecutar tests completos
     */
    public function run_tests($data = []) {
        try {
            // Validar datos de entrada
            $test_types = $data['test_types'] ?? ['unit'];
            $verbose = isset($data['verbose']) && $data['verbose'];
            $coverage = isset($data['coverage']) && $data['coverage'];
            
            // Construir comando PHPUnit
            $command = $this->build_phpunit_command($test_types, $verbose, $coverage);
            
            // Ejecutar tests
            $result = $this->execute_phpunit($command);
            
            return [
                'success' => true,
                'data' => [
                    'command' => $command,
                    'output' => $result['output'],
                    'exit_code' => $result['exit_code'],
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
     * Ejecutar test rápido (solo básicos)
     */
    public function run_quick_test($data = []) {
        try {
            // Ejecutar solo el test básico de Tarokina
            $command = '../dev-tools/vendor/bin/phpunit tests/unit/TarokinaBasicTest.php --verbose';
            $result = $this->execute_phpunit($command);
            
            return [
                'success' => true,
                'data' => [
                    'command' => $command,
                    'output' => $result['output'],
                    'exit_code' => $result['exit_code'],
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
     * Obtener estado de tests en ejecución
     */
    public function get_test_status($data = []) {
        // Para futuras implementaciones de tests en background
        return [
            'success' => true,
            'data' => [
                'running' => false,
                'progress' => 100,
                'current_test' => null
            ]
        ];
    }
    
    /**
     * Construir comando PHPUnit
     */
    private function build_phpunit_command($test_types, $verbose = false, $coverage = false) {
        $base_command = '../dev-tools/vendor/bin/phpunit';
        $options = [];
        
        // Agregar verbosidad
        if ($verbose) {
            $options[] = '--verbose';
        }
        
        // Agregar cobertura
        if ($coverage) {
            $options[] = '--coverage-text';
        }
        
        // Determinar qué tests ejecutar
        $test_path = 'tests/';
        if (in_array('unit', $test_types) && count($test_types) == 1) {
            $test_path = 'tests/unit/TarokinaBasicTest.php';
        } elseif (in_array('dashboard', $test_types)) {
            $test_path = 'tests/unit/dashboard/';
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
        
        // Cambiar al directorio correcto
        $original_dir = getcwd();
        $plugin_dev_tools_dir = dirname(dirname(__DIR__)) . '/plugin-dev-tools';
        
        if (!is_dir($plugin_dev_tools_dir)) {
            throw new \Exception("Directorio plugin-dev-tools no encontrado: {$plugin_dev_tools_dir}");
        }
        
        chdir($plugin_dev_tools_dir);
        
        try {
            // Ejecutar comando y capturar salida
            $output = [];
            $exit_code = 0;
            
            exec($command . ' 2>&1', $output, $exit_code);
            
            $execution_time = microtime(true) - $start_time;
            
            return [
                'output' => implode("\n", $output),
                'exit_code' => $exit_code,
                'execution_time' => round($execution_time, 2)
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
        
        // Buscar línea de resumen tipo: "Tests: 7, Assertions: 17, Risky: 4."
        if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $output, $matches)) {
            $summary['total_tests'] = (int)$matches[1];
            $summary['assertions'] = (int)$matches[2];
        }
        
        // Buscar tiempo y memoria: "Time: 00:00.808, Memory: 42.50 MB"
        if (preg_match('/Time: ([\d:\.]+), Memory: ([\d\.]+ \w+)/', $output, $matches)) {
            $summary['time'] = $matches[1];
            $summary['memory'] = $matches[2];
        }
        
        // Determinar estado general
        if (strpos($output, 'OK (') !== false) {
            $summary['status'] = 'success';
            $summary['passed'] = $summary['total_tests'];
        } elseif (strpos($output, 'ERRORS!') !== false || strpos($output, 'FAILURES!') !== false) {
            $summary['status'] = 'error';
            
            // Contar errores y fallos
            if (preg_match('/(\d+) error/', $output, $matches)) {
                $summary['errors'] = (int)$matches[1];
            }
            if (preg_match('/(\d+) failure/', $output, $matches)) {
                $summary['failed'] = (int)$matches[1];
            }
            if (preg_match('/(\d+) skipped/', $output, $matches)) {
                $summary['skipped'] = (int)$matches[1];
            }
            
            $summary['passed'] = $summary['total_tests'] - $summary['errors'] - $summary['failed'] - $summary['skipped'];
        } elseif (strpos($output, 'OK, but incomplete, skipped, or risky tests!') !== false) {
            $summary['status'] = 'warning';
            $summary['passed'] = $summary['total_tests'];
            
            if (preg_match('/(\d+) risky/', $output, $matches)) {
                $summary['skipped'] = (int)$matches[1];
            }
        }
        
        return $summary;
    }
    
    /**
     * Obtener información del módulo
     */
    public function get_info() {
        return [
            'name' => $this->module_name,
            'version' => $this->version,
            'description' => 'Ejecuta tests PHPUnit desde la interfaz web',
            'status' => 'Activo'
        ];
    }
}
