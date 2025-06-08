<?php
/**
 * AJAX Handler para Dev Tools - Arquitectura 3.0
 * Sistema modular con manejo centralizado de peticiones AJAX
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
        $this->logger = new DevToolsLogger();
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
    }
    
    /**
     * Registrar comandos core del sistema
     */
    private function registerCoreCommands() {
        // Comandos básicos del sistema
        $this->registerCommand('ping', [$this, 'handlePing']);
        $this->registerCommand('get_system_info', [$this, 'handleSystemInfo']);
        $this->registerCommand('test_connection', [$this, 'handleTestConnection']);
        $this->registerCommand('clear_cache', [$this, 'handleClearCache']);
        $this->registerCommand('run_test', [$this, 'handleRunTest']);
    }
    
    /**
     * Registrar hooks de WordPress
     */
    private function registerWordPressHooks() {
        $ajax_prefix = $this->config->get('ajax.action_prefix');
        
        // Hook para usuarios logueados
        add_action("wp_ajax_{$ajax_prefix}_dev_tools", [$this, 'handleAjaxRequest']);
        
        // Hook para usuarios no logueados (solo para debugging específico)
        add_action("wp_ajax_nopriv_{$ajax_prefix}_dev_tools", [$this, 'handleAjaxRequest']);
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
        try {
            // Verificar nonce
            $nonce = $this->sanitizeInput($_POST['nonce'] ?? '');
            if (!wp_verify_nonce($nonce, 'dev_tools_nonce')) {
                throw new Exception('Invalid nonce');
            }
            
            // Obtener acción
            $action = $this->sanitizeInput($_POST['action_type'] ?? '');
            if (empty($action)) {
                throw new Exception('No action specified');
            }
            
            // Verificar permisos
            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }
            
            // Ejecutar comando
            $result = $this->executeCommand($action, $_POST);
            
            // Respuesta exitosa
            wp_send_json_success([
                'action' => $action,
                'data' => $result,
                'timestamp' => current_time('c'),
                'memory_usage' => $this->getMemoryUsage()
            ]);
            
        } catch (Exception $e) {
            $this->logger->logError("AJAX Error: " . $e->getMessage());
            
            wp_send_json_error([
                'message' => $e->getMessage(),
                'action' => $action ?? 'unknown',
                'timestamp' => current_time('c')
            ]);
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
}

/**
 * Logger básico para el sistema dev-tools
 */
class DevToolsLogger {
    
    private $log_level;
    private $is_debug;
    
    public function __construct() {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_level = $this->is_debug ? 'debug' : 'error';
    }
    
    /**
     * Log interno (siempre silencioso)
     */
    public function logInternal($message, $data = null) {
        if ($this->is_debug) {
            error_log('[DEV-TOOLS-INTERNAL] ' . $message . ($data ? ' | Data: ' . print_r($data, true) : ''));
        }
    }
    
    /**
     * Log de errores
     */
    public function logError($message, $data = null) {
        error_log('[DEV-TOOLS-ERROR] ' . $message . ($data ? ' | Data: ' . print_r($data, true) : ''));
    }
    
    /**
     * Log condicional (solo si verbose)
     */
    public function logExternal($message, $type = 'info') {
        if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
            error_log('[DEV-TOOLS-' . strtoupper($type) . '] ' . $message);
        }
    }
}

// Inicializar el manejador AJAX
add_action('init', function() {
    DevToolsAjaxHandler::getInstance();
});
