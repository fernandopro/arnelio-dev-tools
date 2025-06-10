<?php
/**
 * AJAX Handler para Dev Tools - Arquitectura 3.0
 * Sistema de manejo centralizado de peticiones AJAX
 * 
 * @package DevTools
 * @version 3.0.0
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manejador AJAX principal para el sistema dev-tools
 * Implementa patrón Command para ejecutar acciones de forma modular
 */
class DevToolsAjaxHandler {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Configuración del sistema
     */
    private $config;
    
    /**
     * Registro de comandos AJAX disponibles
     */
    private $commands = [];
    
    /**
     * Logger del sistema
     */
    private $logger;
    
    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        $this->config = dev_tools_config();
        $this->logger = DevToolsLogger::getInstance();
        $this->init();
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar el manejador AJAX
     */
    private function init() {
        $this->registerCoreCommands();
        $this->registerWordPressHooks();
        
        // CRÍTICO: Inicializar Module Manager para que los comandos de módulos estén disponibles durante AJAX
        $this->initializeModules();
    }
    
    /**
     * Inicializar módulos del sistema durante peticiones AJAX
     */
    private function initializeModules() {
        // Solo inicializar si estamos en una petición AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $module_manager = dev_tools_get_module_manager();
            if ($module_manager) {
                // Forzar inicialización de módulos para que registren sus comandos AJAX
                $module_manager->initialize();
                $this->logger->logInternal("Module Manager initialized for AJAX context");
            }
        }
    }
    
    /**
     * Registrar comandos core del sistema
     */
    private function registerCoreCommands() {
        // Comandos básicos del sistema
        $this->registerCommand('ping', [$this, 'handlePing']);
        $this->registerCommand('get_system_info', [$this, 'handleSystemInfo']);
        $this->registerCommand('system_info', [$this, 'handleSystemInfo']); // Alias para compatibilidad
        $this->registerCommand('test_connection', [$this, 'handleTestConnection']);
        $this->registerCommand('clear_cache', [$this, 'handleClearCache']);
        $this->registerCommand('run_test', [$this, 'handleRunTest']);
        
        // Comandos específicos para verificaciones del sistema
        $this->registerCommand('check_anti_deadlock', [$this, 'handleCheckAntiDeadlock']);
        $this->registerCommand('check_test_framework', [$this, 'handleCheckTestFramework']);
    }
    
    /**
     * Registrar hooks de WordPress
     */
    private function registerWordPressHooks() {
        $ajax_prefix = $this->config->get('ajax.action_prefix');
        
        // Hook principal con prefijo dinámico del plugin
        add_action("wp_ajax_{$ajax_prefix}_dev_tools", [$this, 'handleAjaxRequest']);
        add_action("wp_ajax_nopriv_{$ajax_prefix}_dev_tools", [$this, 'handleAjaxRequest']);
        
        $this->logger->logInternal("Registered AJAX hooks: wp_ajax_{$ajax_prefix}_dev_tools");
    }
    
    /**
     * Registrar un comando AJAX
     */
    public function registerCommand($action, $callback) {
        if (!is_callable($callback)) {
            $this->logger->logError("Invalid callback for command: {$action}");
            return false;
        }
        
        $this->commands[$action] = $callback;
        $this->logger->logInternal("Registered AJAX command: {$action}");
        return true;
    }
    
    /**
     * Manejar petición AJAX principal
     */
    public function handleAjaxRequest() {
        $action = 'unknown';
        
        // DEBUG: Log inmediato para verificar si llegamos aquí
        error_log('[DEV-TOOLS-DEBUG] handleAjaxRequest() called');
        error_log('[DEV-TOOLS-DEBUG] REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'undefined'));
        error_log('[DEV-TOOLS-DEBUG] POST data: ' . print_r($_POST, true));
        
        try {
            // 1. Validar método HTTP
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Bad Request: Only POST method allowed', 400);
            }
            
            // 2. Validar Content-Type
            $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
            if (!empty($content_type) && 
                !str_contains($content_type, 'application/x-www-form-urlencoded') && 
                !str_contains($content_type, 'multipart/form-data')) {
                $this->logger->logError("Unexpected Content-Type: {$content_type}");
            }
            
            // 3. Validar que $_POST existe y no está vacío
            if (empty($_POST)) {
                throw new Exception('Bad Request: No POST data received', 400);
            }
            
            // 4. Verificar acción WordPress
            $wp_action = $this->sanitizeInput($_POST['action'] ?? '');
            $expected_action = $this->config->get('ajax.action_name');
            if ($wp_action !== $expected_action) {
                throw new Exception("Bad Request: Invalid WordPress action. Expected: {$expected_action}, got: {$wp_action}", 400);
            }
            
            // 5. Verificar nonce
            $nonce = $this->sanitizeInput($_POST['nonce'] ?? '');
            if (empty($nonce)) {
                throw new Exception('Bad Request: Nonce parameter missing', 400);
            }
            
            $nonce_action = $this->config->get('ajax.nonce_action', 'dev_tools_nonce');
            if (!wp_verify_nonce($nonce, $nonce_action)) {
                throw new Exception('Bad Request: Invalid or expired nonce', 400);
            }
            
            // 6. Obtener acción interna
            $action = $this->sanitizeInput($_POST['action_type'] ?? '');
            if (empty($action)) {
                throw new Exception('Bad Request: action_type parameter missing', 400);
            }
            
            // 7. Verificar que la acción contiene solo caracteres válidos
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $action)) {
                throw new Exception('Bad Request: action_type contains invalid characters', 400);
            }
            
            // 8. Verificar permisos
            if (!current_user_can('manage_options')) {
                throw new Exception('Forbidden: Insufficient permissions', 403);
            }
            
            // 9. Validar tamaño del request
            $request_size = strlen(http_build_query($_POST));
            if ($request_size > 1048576) { // 1MB límite
                throw new Exception('Bad Request: Request too large', 400);
            }
            
            // 10. Ejecutar comando
            $result = $this->executeCommand($action, $_POST);
            
            // Respuesta exitosa
            wp_send_json_success([
                'action' => $action,
                'data' => $result,
                'timestamp' => current_time('c'),
                'memory_usage' => $this->getMemoryUsage(),
                'request_size' => $request_size
            ]);
            
        } catch (Exception $e) {
            $error_code = $e->getCode() ?: 500;
            $this->logger->logError("AJAX Error ({$error_code}): " . $e->getMessage());
            
            // Si es error 400, proporcionar información adicional de debug
            $error_response = [
                'message' => $e->getMessage(),
                'action' => $action,
                'timestamp' => current_time('c'),
                'error_code' => $error_code
            ];
            
            // En modo debug, añadir información adicional
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_response['debug_info'] = [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not-set',
                    'post_params' => array_keys($_POST),
                    'get_params' => array_keys($_GET),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'not-set'
                ];
            }
            
            // Enviar respuesta con código HTTP apropiado
            status_header($error_code);
            wp_send_json_error($error_response);
        }
    }
    
    /**
     * Ejecutar comando registrado
     */
    private function executeCommand($action, $data) {
        if (!isset($this->commands[$action])) {
            throw new Exception("Unknown command: {$action}");
        }
        
        $this->logger->logInternal("Executing command: {$action}");
        
        $start_time = microtime(true);
        $result = call_user_func($this->commands[$action], $data);
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $this->logger->logInternal("Command executed in {$execution_time}ms");
        
        return $result;
    }
    
    /**
     * Comando: Ping del sistema
     */
    public function handlePing($data) {
        return [
            'status' => 'alive',
            'version' => $this->config->get('version'),
            'timestamp' => current_time('c'),
            'wp_version' => get_bloginfo('version')
        ];
    }
    
    /**
     * Comando: Información del sistema
     */
    public function handleSystemInfo($data) {
        return [
            'plugin_info' => [
                'host_plugin' => $this->config->get('host_plugin.name'),
                'version' => $this->config->get('version'),
                'path' => $this->config->get('paths.dev_tools')
            ],
            'wordpress_info' => [
                'version' => get_bloginfo('version'),
                'multisite' => is_multisite(),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ],
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];
    }
    
    /**
     * Comando: Test de conexión
     */
    public function handleTestConnection($data) {
        global $wpdb;
        
        // Test database
        $db_test = $wpdb->get_var("SELECT 1");
        
        // Test cache
        $cache_key = 'dev_tools_test_' . time();
        wp_cache_set($cache_key, 'test_value', 'dev_tools', 60);
        $cache_test = wp_cache_get($cache_key, 'dev_tools') === 'test_value';
        
        return [
            'database' => $db_test === '1',
            'cache' => $cache_test,
            'ajax' => true, // Si llegamos aquí, AJAX funciona
            'nonce' => true // Si llegamos aquí, nonce es válido
        ];
    }
    
    /**
     * Comando: Limpiar cache
     */
    public function handleClearCache($data) {
        // Limpiar cache de WordPress
        wp_cache_flush();
        
        // Limpiar transients de dev-tools
        $transients_cleared = 0;
        global $wpdb;
        
        $transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_dev_tools_%'"
        );
        
        foreach ($transients as $transient) {
            delete_transient(str_replace('_transient_', '', $transient->option_name));
            $transients_cleared++;
        }
        
        return [
            'cache_flushed' => true,
            'transients_cleared' => $transients_cleared,
            'timestamp' => current_time('c')
        ];
    }
    
    /**
     * Comando: Ejecutar test
     */
    public function handleRunTest($data) {
        $test_type = $this->sanitizeInput($data['test_type'] ?? 'basic');
        
        switch ($test_type) {
            case 'basic':
                return $this->runBasicTest();
            case 'performance':
                return $this->runPerformanceTest();
            case 'connectivity':
                return $this->runConnectivityTest();
            default:
                throw new Exception("Unknown test type: {$test_type}");
        }
    }
    
    /**
     * Test básico del sistema
     */
    private function runBasicTest() {
        $tests = [];
        
        // Test 1: Configuración
        $tests['config'] = [
            'name' => 'Configuration Test',
            'passed' => $this->config instanceof DevToolsConfig,
            'message' => 'System configuration loaded'
        ];
        
        // Test 2: WordPress functions
        $tests['wordpress'] = [
            'name' => 'WordPress Functions Test',
            'passed' => function_exists('wp_send_json_success'),
            'message' => 'WordPress AJAX functions available'
        ];
        
        // Test 3: Permisos
        $tests['permissions'] = [
            'name' => 'Permissions Test',
            'passed' => current_user_can('manage_options'),
            'message' => 'User has required permissions'
        ];
        
        // Test 4: Rutas
        $dev_tools_path = $this->config->get('paths.dev_tools');
        $tests['paths'] = [
            'name' => 'Paths Test',
            'passed' => file_exists($dev_tools_path),
            'message' => 'Dev-tools directory accessible'
        ];
        
        $passed_count = count(array_filter($tests, function($test) {
            return $test['passed'];
        }));
        
        return [
            'test_type' => 'basic',
            'total_tests' => count($tests),
            'passed_tests' => $passed_count,
            'success_rate' => round(($passed_count / count($tests)) * 100, 2),
            'tests' => $tests
        ];
    }
    
    /**
     * Test de rendimiento
     */
    private function runPerformanceTest() {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Simulación de operaciones
        for ($i = 0; $i < 1000; $i++) {
            $temp = str_repeat('x', 100);
            unset($temp);
        }
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        $memory_used = memory_get_usage() - $start_memory;
        
        return [
            'test_type' => 'performance',
            'execution_time_ms' => $execution_time,
            'memory_used_bytes' => $memory_used,
            'memory_used_mb' => round($memory_used / 1024 / 1024, 2),
            'php_memory_limit' => ini_get('memory_limit'),
            'current_memory_usage' => $this->getMemoryUsage()
        ];
    }
    
    /**
     * Test de conectividad
     */
    private function runConnectivityTest() {
        global $wpdb;
        
        $results = [];
        
        // Test base de datos
        $db_start = microtime(true);
        $db_result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
        $db_time = round((microtime(true) - $db_start) * 1000, 2);
        
        $results['database'] = [
            'connected' => $db_result !== null,
            'response_time_ms' => $db_time,
            'posts_count' => intval($db_result)
        ];
        
        // Test sistema de archivos
        $temp_file = $this->config->get('paths.dev_tools') . '/temp_test.txt';
        $file_write = @file_put_contents($temp_file, 'test');
        $file_read = @file_get_contents($temp_file);
        @unlink($temp_file);
        
        $results['filesystem'] = [
            'writable' => $file_write !== false,
            'readable' => $file_read === 'test'
        ];
        
        // Test AJAX (si llegamos aquí, funciona)
        $results['ajax'] = [
            'working' => true,
            'endpoint' => admin_url('admin-ajax.php')
        ];
        
        return [
            'test_type' => 'connectivity',
            'timestamp' => current_time('c'),
            'results' => $results
        ];
    }
    
    /**
     * Sanitizar input
     */
    private function sanitizeInput($input) {
        if (is_string($input)) {
            return sanitize_text_field($input);
        }
        return $input;
    }
    
    /**
     * Obtener uso de memoria
     */
    private function getMemoryUsage() {
        return [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'limit' => ini_get('memory_limit')
        ];
    }
    
    /**
     * Comando: Verificar sistema anti-deadlock
     * Compatible con DevToolsTestCase y framework de testing
     */
    public function handleCheckAntiDeadlock($data) {
        try {
            $checks = [];
            
            // Verificar constantes del sistema anti-deadlock
            $checks['constants'] = [
                'DEV_TOOLS_DISABLE_ANTI_DEADLOCK' => defined('DEV_TOOLS_DISABLE_ANTI_DEADLOCK') ? 
                    constant('DEV_TOOLS_DISABLE_ANTI_DEADLOCK') : false,
                'DEV_TOOLS_FORCE_ANTI_DEADLOCK' => defined('DEV_TOOLS_FORCE_ANTI_DEADLOCK') ? 
                    constant('DEV_TOOLS_FORCE_ANTI_DEADLOCK') : null,
                'DEV_TOOLS_TESTS_VERBOSE' => defined('DEV_TOOLS_TESTS_VERBOSE') ? 
                    constant('DEV_TOOLS_TESTS_VERBOSE') : false
            ];
            
            // Verificar archivos del sistema
            $test_files = [
                'DevToolsTestCase.php' => $this->config->get('paths.dev_tools') . '/tests/DevToolsTestCase.php',
                'bootstrap.php' => $this->config->get('paths.dev_tools') . '/tests/bootstrap.php',
                'phpunit.xml' => $this->config->get('paths.dev_tools') . '/phpunit.xml'
            ];
            
            $checks['files'] = [];
            foreach ($test_files as $name => $path) {
                $checks['files'][$name] = file_exists($path);
            }
            
            // Verificar clases del sistema
            $checks['classes'] = [
                'DevToolsTestCase' => class_exists('DevToolsTestCase'),
                'DevToolsAjaxHandler' => class_exists('DevToolsAjaxHandler'),
                'DevToolsConfig' => class_exists('DevToolsConfig')
            ];
            
            // Verificar contexto AJAX
            $checks['context'] = [
                'ajax_context' => defined('DOING_AJAX') && DOING_AJAX,
                'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
                'phpunit_running' => defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING
            ];
            
            // Verificar procesos de base de datos
            global $wpdb;
            $processes_result = $wpdb->get_var("SHOW STATUS LIKE 'Threads_connected'");
            $checks['database'] = [
                'connected' => $wpdb->ready,
                'processes_count' => $processes_result ? intval($processes_result) : 0,
                'processes_ok' => $processes_result ? (intval($processes_result) < 50) : true
            ];
            
            // Determinar estado general
            $all_files_ok = !in_array(false, $checks['files']);
            $all_classes_ok = !in_array(false, $checks['classes']);
            $database_ok = $checks['database']['connected'] && $checks['database']['processes_ok'];
            
            return [
                'anti_deadlock_active' => !$checks['constants']['DEV_TOOLS_DISABLE_ANTI_DEADLOCK'],
                'processes_ok' => $database_ok,
                'files_ok' => $all_files_ok,
                'classes_ok' => $all_classes_ok,
                'system_ready' => $all_files_ok && $all_classes_ok && $database_ok,
                'checks' => $checks,
                'timestamp' => current_time('c')
            ];
            
        } catch (Exception $e) {
            throw new Exception("Anti-deadlock check failed: " . $e->getMessage());
        }
    }
    
    /**
     * Comando: Verificar framework de testing WordPress PHPUnit
     */
    public function handleCheckTestFramework($data) {
        try {
            $checks = [];
            
            // Verificar archivos principales de testing
            $test_files = [
                'phpunit.xml' => $this->config->get('paths.dev_tools') . '/phpunit.xml',
                'bootstrap.php' => $this->config->get('paths.dev_tools') . '/tests/bootstrap.php',
                'DevToolsTestCase.php' => $this->config->get('paths.dev_tools') . '/tests/DevToolsTestCase.php'
            ];
            
            $checks['core_files'] = [];
            foreach ($test_files as $name => $path) {
                $checks['core_files'][$name] = [
                    'exists' => file_exists($path),
                    'readable' => file_exists($path) && is_readable($path),
                    'size' => file_exists($path) ? filesize($path) : 0
                ];
            }
            
            // Verificar directorios de testing
            $test_dirs = [
                'tests' => $this->config->get('paths.dev_tools') . '/tests',
                'unit' => $this->config->get('paths.dev_tools') . '/tests/unit',
                'integration' => $this->config->get('paths.dev_tools') . '/tests/integration'
            ];
            
            $checks['directories'] = [];
            foreach ($test_dirs as $name => $path) {
                $checks['directories'][$name] = [
                    'exists' => is_dir($path),
                    'writable' => is_dir($path) && is_writable($path)
                ];
            }
            
            // Verificar constantes de PHPUnit
            $checks['phpunit'] = [
                'phpunit_running' => defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING,
                'wp_tests_domain' => defined('WP_TESTS_DOMAIN') ? constant('WP_TESTS_DOMAIN') : null,
                'wp_tests_email' => defined('WP_TESTS_EMAIL') ? constant('WP_TESTS_EMAIL') : null
            ];
            
            // Verificar clases de testing
            $checks['classes'] = [
                'WP_UnitTestCase' => class_exists('WP_UnitTestCase'),
                'DevToolsTestCase' => class_exists('DevToolsTestCase'),
                'WP_Ajax_UnitTestCase' => class_exists('WP_Ajax_UnitTestCase')
            ];
            
            // Verificar funciones de WordPress testing
            $checks['functions'] = [
                '_delete_all_data' => function_exists('_delete_all_data'),
                'wp_set_current_user' => function_exists('wp_set_current_user'),
                'wp_create_nonce' => function_exists('wp_create_nonce')
            ];
            
            // Determinar estado general
            $all_files_ok = true;
            foreach ($checks['core_files'] as $file_info) {
                if (!$file_info['exists'] || !$file_info['readable'] || $file_info['size'] == 0) {
                    $all_files_ok = false;
                    break;
                }
            }
            
            $all_dirs_ok = true;
            foreach ($checks['directories'] as $dir_info) {
                if (!$dir_info['exists']) {
                    $all_dirs_ok = false;
                    break;
                }
            }
            
            $classes_ok = $checks['classes']['WP_UnitTestCase'] || $checks['classes']['DevToolsTestCase'];
            $functions_ok = $checks['functions']['wp_set_current_user'] && $checks['functions']['wp_create_nonce'];
            
            return [
                'all_files_ok' => $all_files_ok,
                'directories_ok' => $all_dirs_ok,
                'classes_ok' => $classes_ok,
                'functions_ok' => $functions_ok,
                'framework_ready' => $all_files_ok && $all_dirs_ok && $classes_ok && $functions_ok,
                'checks' => $checks,
                'timestamp' => current_time('c')
            ];
            
        } catch (Exception $e) {
            throw new Exception("Test framework check failed: " . $e->getMessage());
        }
    }
    
    /**
     * Comando: Debug de errores 400
     * Proporciona información detallada sobre validación de requests
     */
    public function handleDebug400Errors($data) {
        // Cargar el debug helper si no está ya cargado
        if (!class_exists('DevToolsAjax400Debug')) {
            require_once dirname(__FILE__) . '/debug-ajax-400.php';
        }
        
        try {
            $debug_results = DevToolsAjax400Debug::runValidationTest();
            $request_log = DevToolsAjax400Debug::getRequestLog();
            
            return [
                'message' => 'Debug de errores 400 completado',
                'validation_tests' => $debug_results,
                'recent_requests' => array_slice($request_log, -5), // Últimos 5 requests
                'server_info' => [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not-set',
                    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not-set',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'not-set'
                ],
                'config_info' => [
                    'ajax_action' => $this->config->get('ajax.action_name'),
                    'nonce_action' => $this->config->get('ajax.nonce_action'),
                    'wp_debug' => defined('WP_DEBUG') && WP_DEBUG
                ],
                'timestamp' => current_time('c')
            ];
            
        } catch (Exception $e) {
            throw new Exception("400 debug failed: " . $e->getMessage());
        }
    }
    
    /**
     * Comando: Validar request actual
     * Valida el request actual usando el debug helper
     */
    public function handleValidateRequest($data) {
        // Cargar el debug helper si no está ya cargado
        if (!class_exists('DevToolsAjax400Debug')) {
            require_once dirname(__FILE__) . '/debug-ajax-400.php';
        }
        
        try {
            $validation = DevToolsAjax400Debug::validateAjaxRequest($data);
            
            return [
                'message' => 'Validación de request completada',
                'validation_result' => $validation,
                'request_data' => [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not-set',
                    'post_count' => count($_POST),
                    'get_count' => count($_GET)
                ],
                'recommendations' => $this->getValidationRecommendations($validation),
                'timestamp' => current_time('c')
            ];
            
        } catch (Exception $e) {
            throw new Exception("Request validation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener recomendaciones basadas en validación
     */
    private function getValidationRecommendations($validation) {
        $recommendations = [];
        
        if (!$validation['valid']) {
            $recommendations[] = 'Request inválido - revisar errores listados';
        }
        
        if (!empty($validation['warnings'])) {
            $recommendations[] = 'Hay advertencias - considerar revisarlas';
        }
        
        if ($validation['request_size'] > 4096) {
            $recommendations[] = 'Request grande - considerar optimizar datos enviados';
        }
        
        if ($validation['params_count'] > 20) {
            $recommendations[] = 'Muchos parámetros - considerar simplificar request';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Request parece correcto - no hay recomendaciones específicas';
        }
        
        return $recommendations;
    }
}

/**
 * Logger básico para el sistema dev-tools
 * VERSIÓN SINGLETON: Evita instancias múltiples y bucles infinitos
 */
class DevToolsLogger {
    
    private static $instance = null;
    private $log_level;
    private $is_debug;
    private $initialization_count = 0;
    
    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        $this->initialization_count++;
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_level = $this->is_debug ? 'debug' : 'error';
        
        // Debug: detectar inicializaciones múltiples
        if ($this->initialization_count > 1) {
            error_log('[DEV-TOOLS-WARNING] DevToolsLogger inicializado múltiples veces: ' . $this->initialization_count);
        }
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log interno (siempre silencioso para evitar spam)
     */
    public function logInternal($message, $data = null) {
        if ($this->is_debug) {
            error_log('[DEV-TOOLS-INTERNAL] ' . $message . ($data ? ' | Data: ' . print_r($data, true) : ''));
        }
    }
    
    /**
     * Log de errores (solo errores importantes para evitar spam)
     */
    public function logError($message, $data = null) {
        // Solo loggear errores importantes, no advertencias menores
        if (!$this->isMinorWarning($message)) {
            error_log('[DEV-TOOLS-ERROR] ' . $message . ($data ? ' | Data: ' . print_r($data, true) : ''));
        }
    }
    
    /**
     * Log condicional (solo si verbose)
     */
    public function logExternal($message, $type = 'info') {
        $verbose = (defined('DEV_TOOLS_VERBOSE') && constant('DEV_TOOLS_VERBOSE')) || 
                   (defined('WP_DEBUG') && WP_DEBUG);
        
        if ($verbose) {
            error_log('[DEV-TOOLS-' . strtoupper($type) . '] ' . $message);
        }
    }
    
    /**
     * Verificar si es una advertencia menor que no debe loggearse
     */
    private function isMinorWarning($message) {
        $minor_warnings = [
            'Module already registered',
            'already initialized',
            'already loaded',
            'already exists'
        ];
        
        foreach ($minor_warnings as $warning) {
            if (strpos($message, $warning) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

// Inicializar SOLO el manejador AJAX muy temprano para asegurar que los hooks se registren
// NOTA: El Module Manager se inicializa desde loader.php para evitar duplicaciones
add_action('plugins_loaded', function() {
    // Solo inicializar el handler AJAX, no el sistema completo
    $ajax_handler = DevToolsAjaxHandler::getInstance();
    
    // Debug: confirmar que solo se ejecuta una vez
    static $ajax_init_count = 0;
    $ajax_init_count++;
    if ($ajax_init_count > 1) {
        error_log('[DEV-TOOLS-WARNING] AJAX Handler inicializado múltiples veces: ' . $ajax_init_count);
    }
}, 5); // Prioridad 5 para ejecutar temprano
