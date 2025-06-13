<?php
/**
 * Dev-Tools AJAX Handler - Arquitectura 3.0
 * 
 * Manejador agnóstico de peticiones AJAX
 * Sistema de comandos desacoplado del plugin host
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

namespace DevTools;

use Exception;

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DevToolsAjaxHandler {
    
    private $modules;
    private $commands = [];
    
    public function __construct($modules) {
        $this->modules = $modules;
        $this->register_commands();
    }
    
    /**
     * Registra los comandos AJAX disponibles
     */
    private function register_commands() {
        // Comandos básicos del sistema
        $this->commands = [
            'test_connection' => [$this, 'handle_test_connection'],
            'system_info' => [$this, 'handle_system_info'],
            'site_url_detection' => [$this, 'handle_site_url_detection'],
            'clear_cache' => [$this, 'handle_clear_cache'],
            'run_tests' => [$this, 'handle_run_tests'],
            'quick_action' => [$this, 'handle_quick_action']
        ];
        
        // Permitir que los módulos registren sus propios comandos
        foreach ($this->modules as $module) {
            if (method_exists($module, 'register_ajax_commands')) {
                $module_commands = $module->register_ajax_commands();
                if (is_array($module_commands)) {
                    $this->commands = array_merge($this->commands, $module_commands);
                }
            }
        }
    }
    
    /**
     * Maneja la petición AJAX principal
     */
    public function handle_request() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dev_tools_nonce')) {
            $this->send_error('Invalid nonce');
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            $this->send_error('Insufficient permissions');
            return;
        }
        
        // Obtener comando
        $command = sanitize_text_field($_POST['command'] ?? '');
        
        if (empty($command)) {
            $this->send_error('No command specified');
            return;
        }
        
        // Ejecutar comando
        if (isset($this->commands[$command])) {
            try {
                $data = $_POST['data'] ?? [];
                $result = call_user_func($this->commands[$command], $data);
                $this->send_success($result);
            } catch (Exception $e) {
                $this->send_error('Command failed: ' . $e->getMessage());
            }
        } else {
            $this->send_error('Unknown command: ' . $command);
        }
    }
    
    /**
     * Test de conexión a base de datos
     */
    private function handle_test_connection($data) {
        if (!isset($this->modules['DatabaseConnectionModule'])) {
            throw new Exception('DatabaseConnectionModule not loaded');
        }
        
        $db_module = $this->modules['DatabaseConnectionModule'];
        $test_result = $db_module->test_connection();
        
        return [
            'title' => 'Database Connection Test',
            'success' => $test_result['success'],
            'data' => $test_result,
            'formatted' => $this->format_database_result($test_result)
        ];
    }
    
    /**
     * Información del sistema
     */
    private function handle_system_info($data) {
        $system_info = [
            'wordpress' => [
                'version' => get_bloginfo('version'),
                'multisite' => is_multisite(),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'memory_limit' => WP_MEMORY_LIMIT
            ],
            'php' => [
                'version' => phpversion(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'extensions' => get_loaded_extensions()
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown'
            ],
            'database' => $this->get_database_info(),
            'environment' => $this->get_environment_info(),
            'modules' => array_keys($this->modules)
        ];
        
        return [
            'title' => 'System Information',
            'data' => $system_info,
            'formatted' => $this->format_system_info($system_info)
        ];
    }
    
    /**
     * Test de detección de URL del sitio
     */
    private function handle_site_url_detection($data) {
        if (!isset($this->modules['SiteUrlDetectionModule'])) {
            throw new Exception('SiteUrlDetectionModule not loaded');
        }
        
        $url_module = $this->modules['SiteUrlDetectionModule'];
        $test_result = $url_module->test_detection();
        
        return [
            'title' => 'Site URL Detection Test',
            'data' => $test_result,
            'formatted' => $this->format_url_detection_result($test_result)
        ];
    }
    
    /**
     * Limpiar cache
     */
    private function handle_clear_cache($data) {
        $cleared = [];
        
        // WordPress cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $cleared[] = 'WordPress Object Cache';
        }
        
        // Opciones transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $cleared[] = 'WordPress Transients';
        
        return [
            'title' => 'Cache Cleared',
            'message' => 'Cache cleared successfully',
            'cleared' => $cleared
        ];
    }
    
    
    /**
     * Ejecutar tests PHPUnit
     */
    private function handle_run_tests($data) {
        $test_type = $data['type'] ?? 'all';
        $options = $data['options'] ?? [];
        
        // Verificar si PHPUnit está disponible
        $phpunit_path = $this->find_phpunit();
        if (!$phpunit_path) {
            throw new Exception('PHPUnit not found. Please install PHPUnit for WordPress testing.');
        }
        
        $test_results = $this->execute_phpunit_tests($test_type, $options);
        
        return [
            'title' => 'Test Results',
            'test_type' => $test_type,
            'results' => $test_results,
            'formatted' => $this->format_test_results($test_results)
        ];
    }
    
    /**
     * Acción rápida genérica
     */
    private function handle_quick_action($data) {
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'database':
                return $this->handle_test_connection($data);
            case 'site_url':
                return $this->handle_site_url_detection($data);
            case 'tests':
                return $this->handle_run_tests(['type' => 'quick']);
            case 'cache':
                return $this->handle_clear_cache($data);
            default:
                throw new Exception('Unknown quick action: ' . $action);
        }
    }
    
    /**
     * Obtiene información de la base de datos
     */
    private function get_database_info() {
        global $wpdb;
        
        $db_info = [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'charset' => DB_CHARSET,
            'collate' => DB_COLLATE,
            'table_prefix' => $wpdb->prefix,
            'version' => null
        ];
        
        // Intentar obtener versión de MySQL
        try {
            $version = $wpdb->get_var('SELECT VERSION()');
            $db_info['version'] = $version;
        } catch (Exception $e) {
            $db_info['version'] = 'Unable to determine';
        }
        
        return $db_info;
    }
    
    /**
     * Obtiene información del entorno
     */
    private function get_environment_info() {
        $paths = \DevToolsPaths::getInstance();
        $env_info = $paths->get_debug_info();
        
        // Detectar entorno
        if (isset($this->modules['SiteUrlDetectionModule'])) {
            $url_module = $this->modules['SiteUrlDetectionModule'];
            $url_env = $url_module->get_environment_info();
            $env_info = array_merge($env_info, $url_env);
        }
        
        return $env_info;
    }
    
    /**
     * Busca PHPUnit
     */
    private function find_phpunit() {
        $possible_paths = [
            // WordPress PHPUnit
            ABSPATH . 'vendor/bin/phpunit',
            dirname(ABSPATH) . '/vendor/bin/phpunit',
            // Global PHPUnit
            '/usr/local/bin/phpunit',
            '/usr/bin/phpunit',
            // Composer global
            $_SERVER['HOME'] . '/.composer/vendor/bin/phpunit'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Ejecuta tests PHPUnit
     */
    private function execute_phpunit_tests($test_type, $options) {
        $phpunit_path = $this->find_phpunit();
        $tests_dir = dirname(__DIR__) . '/tests/';
        
        if (!is_dir($tests_dir)) {
            throw new Exception('Tests directory not found: ' . $tests_dir);
        }
        
        // Construir comando
        $command = escapeshellarg($phpunit_path);
        
        // Opciones
        if ($options['verbose'] ?? false) {
            $command .= ' --verbose';
        }
        
        if ($options['stop_on_failure'] ?? false) {
            $command .= ' --stop-on-failure';
        }
        
        if ($options['coverage'] ?? false) {
            $command .= ' --coverage-text';
        }
        
        // Tipo de test
        switch ($test_type) {
            case 'unit':
                $command .= ' ' . escapeshellarg($tests_dir . 'unit/');
                break;
            case 'integration':
                $command .= ' ' . escapeshellarg($tests_dir . 'integration/');
                break;
            case 'environment':
                $command .= ' ' . escapeshellarg($tests_dir . 'environment/');
                break;
            case 'quick':
                $command .= ' --filter="Quick" ' . escapeshellarg($tests_dir);
                break;
            default:
                $command .= ' ' . escapeshellarg($tests_dir);
        }
        
        // Ejecutar comando
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        return [
            'command' => $command,
            'output' => implode("\n", $output),
            'return_code' => $return_code,
            'success' => $return_code === 0
        ];
    }
    
    /**
     * Formatea resultado de base de datos para HTML
     */
    private function format_database_result($result) {
        $html = '<div class="test-result">';
        
        if ($result['success']) {
            $html .= '<div class="alert alert-success"><strong>✅ Conexión exitosa</strong></div>';
            $html .= '<p><strong>DSN:</strong> <code>' . esc_html($result['dsn_used']) . '</code></p>';
            $html .= '<p><strong>Server Info:</strong> ' . esc_html($result['server_info']) . '</p>';
            
            if (isset($result['test_query'])) {
                $html .= '<h6>Test Query:</h6>';
                $html .= '<pre>' . esc_html(json_encode($result['test_query'], JSON_PRETTY_PRINT)) . '</pre>';
            }
        } else {
            $html .= '<div class="alert alert-danger"><strong>❌ Error de conexión</strong></div>';
            $html .= '<p><strong>Error:</strong> ' . esc_html($result['error']) . '</p>';
            $html .= '<p><strong>DSN intentado:</strong> <code>' . esc_html($result['dsn_used']) . '</code></p>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Formatea información del sistema para HTML
     */
    private function format_system_info($info) {
        $html = '<div class="system-info">';
        
        foreach ($info as $section => $data) {
            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header"><h6 class="mb-0">' . ucfirst($section) . '</h6></div>';
            $html .= '<div class="card-body">';
            
            if (is_array($data)) {
                $html .= '<dl class="row">';
                foreach ($data as $key => $value) {
                    $html .= '<dt class="col-sm-3">' . esc_html($key) . ':</dt>';
                    $html .= '<dd class="col-sm-9">';
                    
                    if (is_array($value)) {
                        $html .= '<small>' . esc_html(implode(', ', array_slice($value, 0, 5))) . '</small>';
                        if (count($value) > 5) {
                            $html .= '<small class="text-muted"> (and ' . (count($value) - 5) . ' more...)</small>';
                        }
                    } else {
                        $html .= '<code>' . esc_html($value) . '</code>';
                    }
                    
                    $html .= '</dd>';
                }
                $html .= '</dl>';
            } else {
                $html .= '<p>' . esc_html($data) . '</p>';
            }
            
            $html .= '</div></div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Formatea resultado de detección de URL para HTML
     */
    private function format_url_detection_result($result) {
        $html = '<div class="url-detection-result">';
        
        $html .= '<div class="alert alert-info">';
        $html .= '<strong>🌐 URL Detectado:</strong> <code>' . esc_html($result['detected_url']) . '</code>';
        $html .= '</div>';
        
        $html .= '<h6>Métodos de Detección:</h6>';
        $html .= '<ul class="list-group">';
        
        foreach ($result['all_methods'] as $method => $url) {
            $is_active = $result['environment']['detection_method'] === $method;
            $class = $is_active ? 'list-group-item-success' : 'list-group-item-light';
            
            $html .= '<li class="list-group-item ' . $class . '">';
            $html .= '<strong>' . esc_html($method) . ':</strong> ';
            $html .= $url ? '<code>' . esc_html($url) . '</code>' : '<em>No detectado</em>';
            
            if ($is_active) {
                $html .= ' <span class="badge bg-success">ACTIVO</span>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Formatea resultados de tests para HTML
     */
    private function format_test_results($results) {
        $html = '<div class="test-results">';
        
        if ($results['success']) {
            $html .= '<div class="alert alert-success"><strong>✅ Tests completados exitosamente</strong></div>';
        } else {
            $html .= '<div class="alert alert-danger"><strong>❌ Tests fallaron</strong></div>';
        }
        
        $html .= '<h6>Comando ejecutado:</h6>';
        $html .= '<pre class="bg-light p-2"><code>' . esc_html($results['command']) . '</code></pre>';
        
        $html .= '<h6>Output:</h6>';
        $html .= '<pre class="bg-dark text-white p-3" style="max-height: 400px; overflow-y: auto;">';
        $html .= esc_html($results['output']);
        $html .= '</pre>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Envía respuesta de éxito
     */
    private function send_success($data) {
        wp_send_json_success($data);
    }
    
    /**
     * Envía respuesta de error
     */
    private function send_error($message) {
        wp_send_json_error(['message' => $message]);
    }
}
