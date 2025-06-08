<?php
/**
 * Debug AJAX - Sistema de Depuración AJAX para Dev-Tools Arquitectura 3.0
 * 
 * Sistema centralizado de depuración para todas las llamadas AJAX del dev-tools.
 * Proporciona logging detallado, captura de errores y análisis de rendimiento.
 * 
 * @package DevTools
 * @subpackage Debug
 * @version 3.0.0
 * @since 1.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DevToolsAjaxDebugger - Singleton para depuración AJAX
 * 
 * Características:
 * - Logging centralizado de todas las llamadas AJAX
 * - Captura automática de errores PHP y JavaScript
 * - Medición de tiempos de respuesta
 * - Análisis de memoria utilizada
 * - Validación de permisos y nonces
 * - Stack trace completo en errores
 * - Logging de datos de entrada y salida
 * 
 * @since 1.0.0
 */
class DevToolsAjaxDebugger {
    
    /** @var DevToolsAjaxDebugger Instancia singleton */
    private static $instance = null;
    
    /** @var array Configuración del debugger */
    private $config = [];
    
    /** @var array Log de todas las llamadas AJAX */
    private $ajax_log = [];
    
    /** @var float Tiempo de inicio de la solicitud actual */
    private $request_start_time = 0;
    
    /** @var int Memoria inicial de la solicitud actual */
    private $request_start_memory = 0;
    
    /** @var bool Estado del debugger */
    private $debug_enabled = false;
    
    /** @var string Directorio de logs */
    private $log_dir = '';
    
    /** @var string Archivo de log actual */
    private $log_file = '';

    /**
     * Constructor privado - Singleton
     */
    private function __construct() {
        $this->init_debugger();
    }

    /**
     * Obtener instancia singleton
     * 
     * @return DevToolsAjaxDebugger
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar el debugger
     */
    private function init_debugger() {
        // Configuración por defecto
        $this->config = [
            'debug_enabled' => defined('WP_DEBUG') && WP_DEBUG,
            'log_to_file' => true,
            'log_to_console' => true,
            'max_log_size' => 10 * 1024 * 1024, // 10MB
            'log_retention_days' => 7,
            'detailed_errors' => true,
            'log_request_data' => true,
            'log_response_data' => true,
            'performance_tracking' => true
        ];

        // Verificar si está habilitado
        $this->debug_enabled = $this->config['debug_enabled'];
        
        if (!$this->debug_enabled) {
            return;
        }

        // Configurar directorio de logs
        $this->setup_log_directory();
        
        // Configurar manejadores de errores
        $this->setup_error_handlers();
        
        // Hook para capturar AJAX
        add_action('wp_ajax_nopriv_dev_tools_ajax', [$this, 'debug_ajax_request'], 1);
        add_action('wp_ajax_dev_tools_ajax', [$this, 'debug_ajax_request'], 1);
        
        // Hook para finalizar debug
        add_action('wp_die', [$this, 'finalize_debug'], 999);
        add_action('shutdown', [$this, 'finalize_debug'], 999);
    }

    /**
     * Configurar directorio de logs
     */
    private function setup_log_directory() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/dev-tools-logs/ajax/';
        
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
            
            // Crear archivo .htaccess para proteger logs
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($this->log_dir . '.htaccess', $htaccess_content);
        }
        
        // Archivo de log del día actual
        $this->log_file = $this->log_dir . 'ajax-debug-' . date('Y-m-d') . '.log';
        
        // Limpiar logs antiguos
        $this->cleanup_old_logs();
    }

    /**
     * Configurar manejadores de errores PHP
     */
    private function setup_error_handlers() {
        // Capturar errores PHP
        set_error_handler([$this, 'handle_php_error'], E_ALL);
        
        // Capturar excepciones no manejadas
        set_exception_handler([$this, 'handle_php_exception']);
        
        // Capturar errores fatales
        register_shutdown_function([$this, 'handle_fatal_error']);
    }

    /**
     * Iniciar debug de solicitud AJAX
     */
    public function debug_ajax_request() {
        if (!$this->debug_enabled) {
            return;
        }

        $this->request_start_time = microtime(true);
        $this->request_start_memory = memory_get_usage(true);

        $debug_data = [
            'timestamp' => current_time('mysql'),
            'request_id' => uniqid('ajax_', true),
            'action' => sanitize_text_field($_POST['action'] ?? ''),
            'command' => sanitize_text_field($_POST['command'] ?? ''),
            'user_id' => get_current_user_id(),
            'user_ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'referer' => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? ''),
            'request_method' => sanitize_text_field($_SERVER['REQUEST_METHOD'] ?? ''),
            'request_uri' => sanitize_text_field($_SERVER['REQUEST_URI'] ?? ''),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'memory_start' => $this->format_bytes($this->request_start_memory),
            'start_time' => $this->request_start_time
        ];

        // Log de datos de entrada si está habilitado
        if ($this->config['log_request_data']) {
            $debug_data['request_data'] = $this->sanitize_request_data($_POST);
        }

        // Verificar nonce si está presente
        if (isset($_POST['nonce'])) {
            $debug_data['nonce_valid'] = wp_verify_nonce($_POST['nonce'], 'dev_tools_nonce');
        }

        // Verificar permisos
        $debug_data['user_can_manage'] = current_user_can('manage_options');

        $this->ajax_log[] = $debug_data;
        $this->log_to_file("=== INICIO SOLICITUD AJAX ===", $debug_data);
    }

    /**
     * Finalizar debug de solicitud
     */
    public function finalize_debug() {
        if (!$this->debug_enabled || empty($this->ajax_log)) {
            return;
        }

        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        
        $last_request = &$this->ajax_log[count($this->ajax_log) - 1];
        
        $last_request['end_time'] = $end_time;
        $last_request['execution_time'] = round(($end_time - $this->request_start_time) * 1000, 2) . 'ms';
        $last_request['memory_end'] = $this->format_bytes($end_memory);
        $last_request['memory_used'] = $this->format_bytes($end_memory - $this->request_start_memory);
        $last_request['memory_peak'] = $this->format_bytes(memory_get_peak_usage(true));

        $this->log_to_file("=== FIN SOLICITUD AJAX ===", [
            'execution_time' => $last_request['execution_time'],
            'memory_used' => $last_request['memory_used'],
            'memory_peak' => $last_request['memory_peak']
        ]);
    }

    /**
     * Manejar errores PHP
     */
    public function handle_php_error($severity, $message, $file, $line) {
        if (!$this->debug_enabled) {
            return false;
        }

        $error_data = [
            'type' => 'PHP_ERROR',
            'severity' => $this->get_error_type_name($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'stack_trace' => $this->get_stack_trace()
        ];

        $this->log_to_file("ERROR PHP DETECTADO", $error_data);
        
        // No interferir con el manejo normal de errores
        return false;
    }

    /**
     * Manejar excepciones PHP
     */
    public function handle_php_exception($exception) {
        if (!$this->debug_enabled) {
            return;
        }

        $exception_data = [
            'type' => 'PHP_EXCEPTION',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString()
        ];

        $this->log_to_file("EXCEPCIÓN PHP DETECTADA", $exception_data);
    }

    /**
     * Manejar errores fatales
     */
    public function handle_fatal_error() {
        if (!$this->debug_enabled) {
            return;
        }

        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $fatal_data = [
                'type' => 'FATAL_ERROR',
                'severity' => $this->get_error_type_name($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ];

            $this->log_to_file("ERROR FATAL DETECTADO", $fatal_data);
        }
    }

    /**
     * Log personalizado para eventos específicos
     */
    public function log_event($event, $data = []) {
        if (!$this->debug_enabled) {
            return;
        }

        $event_data = [
            'type' => 'CUSTOM_EVENT',
            'event' => $event,
            'data' => $data,
            'timestamp' => current_time('mysql')
        ];

        $this->log_to_file("EVENTO PERSONALIZADO: {$event}", $event_data);
    }

    /**
     * Escribir al archivo de log
     */
    private function log_to_file($message, $data = []) {
        if (!$this->config['log_to_file'] || !$this->log_file) {
            return;
        }

        $log_entry = [
            'timestamp' => current_time('c'),
            'message' => $message,
            'data' => $data
        ];

        $formatted_log = date('[Y-m-d H:i:s] ') . $message . "\n";
        if (!empty($data)) {
            $formatted_log .= "Data: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        $formatted_log .= str_repeat('-', 80) . "\n";

        file_put_contents($this->log_file, $formatted_log, FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip_headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field($_SERVER[$header]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    /**
     * Sanitizar datos de solicitud para logging
     */
    private function sanitize_request_data($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), ['password', 'pwd', 'pass', 'secret', 'token', 'key'])) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_string($value) ? sanitize_text_field($value) : $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtener stack trace
     */
    private function get_stack_trace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $formatted_trace = [];
        
        foreach ($trace as $i => $frame) {
            $formatted_trace[] = sprintf(
                "#%d %s(%d): %s%s%s()",
                $i,
                $frame['file'] ?? '[internal]',
                $frame['line'] ?? 0,
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function'] ?? ''
            );
        }
        
        return implode("\n", $formatted_trace);
    }

    /**
     * Convertir tipo de error a nombre legible
     */
    private function get_error_type_name($type) {
        $error_types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        
        return $error_types[$type] ?? "UNKNOWN_ERROR_TYPE({$type})";
    }

    /**
     * Formatear bytes a unidades legibles
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Limpiar logs antiguos
     */
    private function cleanup_old_logs() {
        if (!is_dir($this->log_dir)) {
            return;
        }

        $retention_days = $this->config['log_retention_days'];
        $cutoff_time = time() - ($retention_days * 24 * 60 * 60);
        
        $files = glob($this->log_dir . 'ajax-debug-*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }

    /**
     * Obtener estadísticas de debug
     */
    public function get_debug_stats() {
        return [
            'debug_enabled' => $this->debug_enabled,
            'total_requests' => count($this->ajax_log),
            'log_file' => $this->log_file,
            'log_file_size' => file_exists($this->log_file) ? $this->format_bytes(filesize($this->log_file)) : '0 B',
            'config' => $this->config
        ];
    }

    /**
     * Obtener log completo de la sesión actual
     */
    public function get_session_log() {
        return $this->ajax_log;
    }

    /**
     * Limpiar log de la sesión actual
     */
    public function clear_session_log() {
        $this->ajax_log = [];
    }

    /**
     * Habilitar/deshabilitar debug
     */
    public function set_debug_enabled($enabled) {
        $this->debug_enabled = (bool) $enabled;
        $this->config['debug_enabled'] = $this->debug_enabled;
    }

    /**
     * Obtener contenido del archivo de log del día
     */
    public function get_todays_log() {
        if (!file_exists($this->log_file)) {
            return '';
        }
        
        return file_get_contents($this->log_file);
    }

    /**
     * Generar reporte de debug en formato HTML
     */
    public function generate_debug_report() {
        $stats = $this->get_debug_stats();
        $session_log = $this->get_session_log();
        
        ob_start();
        ?>
        <div class="dev-tools-debug-report">
            <h3>🔧 Reporte de Debug AJAX - Dev-Tools</h3>
            
            <div class="debug-stats">
                <h4>📊 Estadísticas</h4>
                <ul>
                    <li><strong>Debug Habilitado:</strong> <?php echo $stats['debug_enabled'] ? '✅ Sí' : '❌ No'; ?></li>
                    <li><strong>Solicitudes en Sesión:</strong> <?php echo $stats['total_requests']; ?></li>
                    <li><strong>Archivo de Log:</strong> <?php echo basename($stats['log_file']); ?></li>
                    <li><strong>Tamaño del Log:</strong> <?php echo $stats['log_file_size']; ?></li>
                </ul>
            </div>
            
            <?php if (!empty($session_log)): ?>
            <div class="debug-session-log">
                <h4>📝 Log de la Sesión Actual</h4>
                <?php foreach ($session_log as $entry): ?>
                    <div class="debug-entry">
                        <strong><?php echo esc_html($entry['timestamp'] ?? ''); ?></strong>
                        - <?php echo esc_html($entry['action'] ?? ''); ?>
                        <?php if (!empty($entry['command'])): ?>
                            (<?php echo esc_html($entry['command']); ?>)
                        <?php endif; ?>
                        <?php if (!empty($entry['execution_time'])): ?>
                            - <em><?php echo esc_html($entry['execution_time']); ?></em>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Prevenir clonación
     */
    private function __clone() {}

    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función helper para acceso global
function dev_tools_ajax_debugger() {
    return DevToolsAjaxDebugger::getInstance();
}

// Inicializar debugger automáticamente si está en contexto AJAX
if (defined('DOING_AJAX') && DOING_AJAX) {
    dev_tools_ajax_debugger();
}

// Función helper para logging rápido desde cualquier parte del código
function dev_tools_debug_log($event, $data = []) {
    dev_tools_ajax_debugger()->log_event($event, $data);
}
