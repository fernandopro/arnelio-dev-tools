<?php
/**
 * Debug Helper para errores 400 (Bad Request) en AJAX
 * Herramienta de diagnóstico para dev-tools
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para debuggear errores 400 en requests AJAX
 */
class DevToolsAjax400Debug {
    
    /**
     * Log de requests recibidos
     */
    private static $request_log = [];
    
    /**
     * Validar estructura de request AJAX
     */
    public static function validateAjaxRequest($request_data = null) {
        $data = $request_data ?? $_POST;
        $errors = [];
        $warnings = [];
        
        // 1. Verificar method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = 'AJAX debe usar método POST, recibido: ' . $_SERVER['REQUEST_METHOD'];
        }
        
        // 2. Verificar Content-Type
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if (empty($content_type)) {
            $warnings[] = 'Content-Type no especificado';
        } elseif (!str_contains($content_type, 'application/x-www-form-urlencoded') && 
                  !str_contains($content_type, 'multipart/form-data')) {
            $warnings[] = 'Content-Type inesperado: ' . $content_type;
        }
        
        // 3. Verificar parámetros requeridos
        $required_params = ['action', 'nonce'];
        foreach ($required_params as $param) {
            if (!isset($data[$param]) || empty($data[$param])) {
                $errors[] = "Parámetro requerido faltante: {$param}";
            }
        }
        
        // 4. Verificar action correcta
        if (isset($data['action'])) {
            $config = dev_tools_config();
            $expected_action = $config->get('ajax.action_name');
            if ($data['action'] !== $expected_action) {
                $errors[] = "Action incorrecta. Esperada: {$expected_action}, recibida: {$data['action']}";
            }
        }
        
        // 5. Verificar action_type
        if (!isset($data['action_type']) || empty($data['action_type'])) {
            $errors[] = 'action_type no especificado';
        }
        
        // 6. Verificar longitud de parámetros
        foreach ($data as $key => $value) {
            if (is_string($value) && strlen($value) > 10000) {
                $warnings[] = "Parámetro {$key} es muy largo: " . strlen($value) . ' caracteres';
            }
        }
        
        // 7. Verificar caracteres especiales problemáticos
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Buscar caracteres que podrían causar problemas
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
                    $warnings[] = "Parámetro {$key} contiene caracteres de control";
                }
                if (mb_strlen($value) !== strlen($value)) {
                    $warnings[] = "Parámetro {$key} contiene caracteres multibyte";
                }
            }
        }
        
        // 8. Verificar tamaño total del request
        $total_size = strlen(http_build_query($data));
        if ($total_size > 8192) { // 8KB límite común
            $warnings[] = "Request muy grande: {$total_size} bytes";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'request_size' => $total_size,
            'params_count' => count($data)
        ];
    }
    
    /**
     * Generar request de prueba válido
     */
    public static function generateValidTestRequest($action_type = 'ping') {
        $config = dev_tools_config();
        
        return [
            'action' => $config->get('ajax.action_name'),
            'action_type' => $action_type,
            'nonce' => wp_create_nonce($config->get('ajax.nonce_action')),
            'timestamp' => time()
        ];
    }
    
    /**
     * Test completo de validación AJAX
     */
    public static function runValidationTest() {
        $results = [
            'timestamp' => current_time('c'),
            'tests' => []
        ];
        
        // Test 1: Request válido
        $valid_request = self::generateValidTestRequest('ping');
        $validation = self::validateAjaxRequest($valid_request);
        $results['tests']['valid_request'] = [
            'description' => 'Request válido básico',
            'passed' => $validation['valid'],
            'details' => $validation
        ];
        
        // Test 2: Request sin nonce
        $no_nonce = $valid_request;
        unset($no_nonce['nonce']);
        $validation = self::validateAjaxRequest($no_nonce);
        $results['tests']['no_nonce'] = [
            'description' => 'Request sin nonce (debe fallar)',
            'passed' => !$validation['valid'], // Debe fallar
            'details' => $validation
        ];
        
        // Test 3: Action incorrecta
        $wrong_action = $valid_request;
        $wrong_action['action'] = 'wrong_action';
        $validation = self::validateAjaxRequest($wrong_action);
        $results['tests']['wrong_action'] = [
            'description' => 'Action incorrecta (debe fallar)',
            'passed' => !$validation['valid'], // Debe fallar
            'details' => $validation
        ];
        
        // Test 4: Request con caracteres especiales
        $special_chars = $valid_request;
        $special_chars['test_param'] = "Test with ñáéíóú and special chars: ®©™";
        $validation = self::validateAjaxRequest($special_chars);
        $results['tests']['special_chars'] = [
            'description' => 'Request con caracteres especiales',
            'passed' => $validation['valid'],
            'details' => $validation
        ];
        
        return $results;
    }
    
    /**
     * Interceptar y loggear requests para debugging
     */
    public static function interceptRequest() {
        $request_info = [
            'timestamp' => microtime(true),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not-set',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not-set',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'not-set',
            'post_data' => $_POST,
            'get_data' => $_GET,
            'headers' => getallheaders() ?: [],
            'validation' => self::validateAjaxRequest()
        ];
        
        self::$request_log[] = $request_info;
        
        // Log a archivo si hay errores
        if (!$request_info['validation']['valid']) {
            error_log('[DEV-TOOLS-400-DEBUG] ' . json_encode($request_info, JSON_PRETTY_PRINT));
        }
        
        return $request_info;
    }
    
    /**
     * Obtener log de requests
     */
    public static function getRequestLog() {
        return self::$request_log;
    }
    
    /**
     * Limpiar log de requests
     */
    public static function clearRequestLog() {
        self::$request_log = [];
    }
}

// Registrar debug automático si estamos en modo debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Solo interceptar en admin AJAX
    if (defined('DOING_AJAX') && DOING_AJAX) {
        DevToolsAjax400Debug::interceptRequest();
    }
}
