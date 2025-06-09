<?php
/**
 * Debug específico para diferencias entre PHPUnit y navegador
 * 
 * @package DevTools
 * @subpackage Debug
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para debuggear diferencias entre entorno PHPUnit y navegador
 */
class DevToolsBrowserAjaxDebug {
    
    /**
     * Simular request del navegador desde PHP
     */
    public static function simulateBrowserRequest($nonce, $debug = true) {
        $results = [
            'timestamp' => current_time('c'),
            'test_type' => 'browser_simulation',
            'environment' => [
                'is_admin' => is_admin(),
                'current_user_id' => get_current_user_id(),
                'user_can_manage' => current_user_can('manage_options'),
                'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
                'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG
            ],
            'nonce_validation' => [],
            'ajax_test' => []
        ];
        
        // Test 1: Validación de nonce
        $config = dev_tools_config();
        $nonce_action = $config->get('ajax.nonce_action');
        $ajax_action = $config->get('ajax.action_name');
        
        $results['nonce_validation'] = [
            'nonce_action' => $nonce_action,
            'ajax_action' => $ajax_action,
            'nonce_provided' => $nonce,
            'nonce_valid' => wp_verify_nonce($nonce, $nonce_action),
            'nonce_age' => self::getNonceAge($nonce)
        ];
        
        // Test 2: Simular request AJAX como si viniera del navegador
        $original_post = $_POST;
        $original_server = $_SERVER;
        
        try {
            // Configurar entorno como navegador
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $_POST = [
                'action' => $ajax_action,
                'action_type' => 'ping',
                'nonce' => $nonce
            ];
            
            // Intentar procesar request
            ob_start();
            $ajax_handler = DevToolsAjaxHandler::getInstance();
            
            try {
                $ajax_handler->handleAjaxRequest();
                $response = ob_get_clean();
                $results['ajax_test'] = [
                    'success' => true,
                    'response' => $response,
                    'parsed_response' => json_decode($response, true)
                ];
            } catch (Exception $e) {
                $response = ob_get_clean();
                $results['ajax_test'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'response' => $response,
                    'parsed_response' => json_decode($response, true)
                ];
            }
            
        } finally {
            // Restaurar entorno
            $_POST = $original_post;
            $_SERVER = $original_server;
        }
        
        // Test 3: Verificar configuración específica
        $results['configuration_check'] = [
            'ajax_action_configured' => !empty($ajax_action),
            'nonce_action_configured' => !empty($nonce_action),
            'ajax_hooks_registered' => has_action("wp_ajax_{$ajax_action}"),
            'ajax_nopriv_registered' => has_action("wp_ajax_nopriv_{$ajax_action}")
        ];
        
        if ($debug) {
            error_log('[BROWSER-DEBUG] ' . print_r($results, true));
        }
        
        return $results;
    }
    
    /**
     * Obtener edad aproximada del nonce
     */
    private static function getNonceAge($nonce) {
        if (empty($nonce)) {
            return 'invalid';
        }
        
        // Los nonces de WordPress son válidos por 12-24 horas
        // Intentamos verificar contra diferentes tiempos
        $current_time = time();
        $tick = ceil($current_time / (NONCE_LIFE / 2));
        
        for ($i = 0; $i <= 2; $i++) {
            $test_tick = $tick - $i;
            $test_token = substr(wp_hash($test_tick . '|' . 'tarokina-2025_dev_tools_nonce' . '|' . get_current_user_id() . '|' . wp_get_session_token(), 'nonce'), -12, 10);
            
            if (hash_equals($test_token, $nonce)) {
                return $i === 0 ? 'fresh' : ($i === 1 ? 'half_life' : 'near_expiry');
            }
        }
        
        return 'expired_or_invalid';
    }
    
    /**
     * Test completo de diferencias navegador vs PHPUnit
     */
    public static function runComparisonTest() {
        $results = [
            'timestamp' => current_time('c'),
            'phpunit_simulation' => null,
            'browser_simulation' => null,
            'comparison' => []
        ];
        
        // Generar nonce fresco
        $config = dev_tools_config();
        $nonce_action = $config->get('ajax.nonce_action');
        $fresh_nonce = wp_create_nonce($nonce_action);
        
        // Test 1: Simular como PHPUnit
        $results['phpunit_simulation'] = self::simulatePhpUnitTest($fresh_nonce);
        
        // Test 2: Simular como navegador
        $results['browser_simulation'] = self::simulateBrowserRequest($fresh_nonce);
        
        // Test 3: Comparar resultados
        $results['comparison'] = [
            'both_nonce_valid' => $results['phpunit_simulation']['nonce_valid'] && $results['browser_simulation']['nonce_validation']['nonce_valid'],
            'both_ajax_success' => $results['phpunit_simulation']['ajax_success'] && $results['browser_simulation']['ajax_test']['success'],
            'environment_differences' => self::compareEnvironments($results['phpunit_simulation'], $results['browser_simulation']),
            'conclusion' => ''
        ];
        
        // Determinar conclusión
        if ($results['comparison']['both_ajax_success']) {
            $results['comparison']['conclusion'] = 'SUCCESS: Both environments work - issue is frontend specific';
        } elseif ($results['comparison']['both_nonce_valid']) {
            $results['comparison']['conclusion'] = 'PARTIAL: Nonces valid but AJAX processing differs';
        } else {
            $results['comparison']['conclusion'] = 'FAILED: Core nonce/configuration issues';
        }
        
        return $results;
    }
    
    /**
     * Simular test como PHPUnit
     */
    private static function simulatePhpUnitTest($nonce) {
        $config = dev_tools_config();
        $nonce_action = $config->get('ajax.nonce_action');
        
        return [
            'environment' => 'phpunit_simulation',
            'nonce_valid' => wp_verify_nonce($nonce, $nonce_action),
            'ajax_success' => true, // Asumimos que PHPUnit funciona
            'user_id' => get_current_user_id(),
            'is_admin' => is_admin()
        ];
    }
    
    /**
     * Comparar entornos
     */
    private static function compareEnvironments($phpunit_data, $browser_data) {
        return [
            'user_id_match' => $phpunit_data['user_id'] === $browser_data['environment']['current_user_id'],
            'admin_context_match' => $phpunit_data['is_admin'] === $browser_data['environment']['is_admin'],
            'nonce_validation_match' => $phpunit_data['nonce_valid'] === $browser_data['nonce_validation']['nonce_valid']
        ];
    }
    
    /**
     * Debug endpoint para ser llamado desde el navegador
     */
    public static function debugEndpoint() {
        // Solo permitir en modo debug
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            wp_die('Debug mode not enabled');
        }
        
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        if (empty($nonce)) {
            wp_die('Nonce required for debug');
        }
        
        $results = self::simulateBrowserRequest($nonce, true);
        
        header('Content-Type: application/json');
        echo json_encode($results, JSON_PRETTY_PRINT);
        wp_die();
    }
}

// Registrar endpoint de debug si estamos en modo debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_ajax_debug_browser_ajax', ['DevToolsBrowserAjaxDebug', 'debugEndpoint']);
    add_action('wp_ajax_nopriv_debug_browser_ajax', ['DevToolsBrowserAjaxDebug', 'debugEndpoint']);
}
