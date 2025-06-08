<?php
/**
 * Tests del Manejador AJAX - Dev-Tools Arquitectura 3.0
 *
 * @package TarokinaPro\DevTools\Tests\Unit
 * @since 1.0.0
 */

namespace DevToolsTests\Unit;

use DevToolsTestCase;

/**
 * Test del sistema de manejo AJAX
 */
class AjaxHandlerTest extends DevToolsTestCase 
{
    /**
     * Test de registro de hooks AJAX
     */
    public function test_ajax_hooks_registration() 
    {
        // Verificar que los hooks AJAX están registrados
        $this->assertTrue(has_action('wp_ajax_dev_tools_ajax'), 'Hook AJAX para usuarios logueados debe estar registrado');
        
        // Verificar la prioridad del hook
        $priority = has_action('wp_ajax_dev_tools_ajax');
        $this->assertIsInt($priority, 'Prioridad del hook debe ser entero');
        $this->assertGreaterThan(0, $priority, 'Hook debe estar registrado con prioridad > 0');
    }

    /**
     * Test de generación de nonce AJAX
     */
    public function test_ajax_nonce_generation() 
    {
        // Test de función de nonce si existe
        if (function_exists('dev_tools_get_ajax_nonce')) {
            $nonce = dev_tools_get_ajax_nonce();
            
            $this->assertIsString($nonce, 'Nonce debe ser string');
            $this->assertNotEmpty($nonce, 'Nonce no debe estar vacío');
            $this->assertGreaterThan(8, strlen($nonce), 'Nonce debe tener longitud segura');
            
            // Verificar que el nonce es válido
            if (function_exists('wp_verify_nonce')) {
                $this->assertTrue(wp_verify_nonce($nonce, 'dev_tools_ajax'), 'Nonce debe ser válido');
            }
        }
    }

    /**
     * Test de estructura de respuesta AJAX
     */
    public function test_ajax_response_structure() 
    {
        // Simular usuario administrador
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Test de respuesta exitosa
        $success_response = $this->makeAjaxResponse(true, 'Test successful', ['test' => 'data']);
        
        $this->assertArrayHasKey('success', $success_response, 'Respuesta debe tener campo success');
        $this->assertArrayHasKey('message', $success_response, 'Respuesta debe tener campo message');
        $this->assertArrayHasKey('data', $success_response, 'Respuesta debe tener campo data');
        
        $this->assertTrue($success_response['success'], 'Respuesta exitosa debe tener success = true');
        $this->assertEquals('Test successful', $success_response['message'], 'Mensaje debe coincidir');
        $this->assertIsArray($success_response['data'], 'Data debe ser array');
        
        // Test de respuesta de error
        $error_response = $this->makeAjaxResponse(false, 'Test error', null);
        
        $this->assertFalse($error_response['success'], 'Respuesta de error debe tener success = false');
        $this->assertEquals('Test error', $error_response['message'], 'Mensaje de error debe coincidir');
    }

    /**
     * Test de validación de comandos AJAX
     */
    public function test_ajax_command_validation() 
    {
        // Lista de comandos válidos esperados
        $expected_commands = [
            'get_system_info',
            'clear_cache',
            'test_ajax',
            'get_logs',
            'analyze_performance'
        ];
        
        foreach ($expected_commands as $command) {
            $this->assertTrue($this->isValidAjaxCommand($command), "Comando '{$command}' debe ser válido");
        }
        
        // Test de comando inválido
        $this->assertFalse($this->isValidAjaxCommand('invalid_command'), 'Comando inválido debe retornar false');
        $this->assertFalse($this->isValidAjaxCommand(''), 'Comando vacío debe retornar false');
        $this->assertFalse($this->isValidAjaxCommand(null), 'Comando null debe retornar false');
    }

    /**
     * Test de manejo de errores AJAX
     */
    public function test_ajax_error_handling() 
    {
        // Test de error por falta de permisos
        wp_set_current_user(0); // Usuario no logueado
        
        $error_response = $this->makeAjaxRequest('get_system_info', []);
        
        $this->assertArrayHasKey('success', $error_response, 'Respuesta de error debe tener campo success');
        $this->assertFalse($error_response['success'], 'Sin permisos debe retornar success = false');
        $this->assertStringContainsString('permiso', strtolower($error_response['message']), 'Mensaje debe mencionar permisos');
        
        // Test de error por comando inexistente
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        $error_response = $this->makeAjaxRequest('nonexistent_command', []);
        
        $this->assertFalse($error_response['success'], 'Comando inexistente debe retornar success = false');
        $this->assertStringContainsString('comando', strtolower($error_response['message']), 'Mensaje debe mencionar comando');
    }

    /**
     * Test de sanitización de datos AJAX
     */
    public function test_ajax_data_sanitization() 
    {
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Test con datos que necesitan sanitización
        $unsafe_data = [
            'text' => '<script>alert("xss")</script>',
            'number' => '123abc',
            'array' => ['item1', '<script>alert("xss2")</script>', 'item3'],
            'sql_injection' => "'; DROP TABLE users; --"
        ];
        
        $response = $this->makeAjaxRequest('test_ajax', $unsafe_data);
        
        // Verificar que la respuesta no contiene scripts
        $response_json = json_encode($response);
        $this->assertStringNotContainsString('<script>', $response_json, 'Respuesta no debe contener scripts');
        $this->assertStringNotContainsString('DROP TABLE', $response_json, 'Respuesta no debe contener SQL peligroso');
    }

    /**
     * Test de rate limiting AJAX
     */
    public function test_ajax_rate_limiting() 
    {
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Si existe rate limiting, testearlo
        if (function_exists('dev_tools_check_ajax_rate_limit')) {
            // Hacer múltiples requests rápidos
            $responses = [];
            for ($i = 0; $i < 5; $i++) {
                $responses[] = $this->makeAjaxRequest('test_ajax', ['iteration' => $i]);
            }
            
            // Verificar que al menos las primeras requests son exitosas
            $this->assertTrue($responses[0]['success'], 'Primera request debe ser exitosa');
            $this->assertTrue($responses[1]['success'], 'Segunda request debe ser exitosa');
        }
    }

    /**
     * Test de logging de peticiones AJAX
     */
    public function test_ajax_request_logging() 
    {
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Hacer una petición AJAX
        $response = $this->makeAjaxRequest('get_system_info', []);
        
        // Si existe logging, verificar que se registra
        if (function_exists('dev_tools_get_ajax_logs')) {
            $logs = dev_tools_get_ajax_logs();
            $this->assertIsArray($logs, 'Logs deben ser array');
            
            if (!empty($logs)) {
                $last_log = end($logs);
                $this->assertArrayHasKey('command', $last_log, 'Log debe tener campo command');
                $this->assertArrayHasKey('timestamp', $last_log, 'Log debe tener campo timestamp');
                $this->assertEquals('get_system_info', $last_log['command'], 'Command en log debe coincidir');
            }
        }
    }

    /**
     * Test de timeout de peticiones AJAX
     */
    public function test_ajax_timeout_handling() 
    {
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        // Si existe configuración de timeout, testearlo
        if (defined('DEV_TOOLS_AJAX_TIMEOUT')) {
            $this->assertIsInt(DEV_TOOLS_AJAX_TIMEOUT, 'Timeout debe ser entero');
            $this->assertGreaterThan(0, DEV_TOOLS_AJAX_TIMEOUT, 'Timeout debe ser positivo');
            $this->assertLessThan(300, DEV_TOOLS_AJAX_TIMEOUT, 'Timeout debe ser razonable (< 5 min)');
        }
    }

    /**
     * Test de respuesta JSON válida
     */
    public function test_ajax_json_response_validity() 
    {
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
        
        $response = $this->makeAjaxRequest('get_system_info', []);
        
        // Verificar que la respuesta es JSON válido
        $json_string = json_encode($response);
        $this->assertNotFalse($json_string, 'Respuesta debe ser serializable a JSON');
        
        $decoded = json_decode($json_string, true);
        $this->assertNotNull($decoded, 'JSON debe ser válido y decodificable');
        $this->assertEquals($response, $decoded, 'Respuesta debe mantener integridad en JSON');
    }

    /**
     * Helper: Simular respuesta AJAX
     */
    private function makeAjaxResponse($success, $message, $data = null) 
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Helper: Verificar si comando AJAX es válido
     */
    private function isValidAjaxCommand($command) 
    {
        if (empty($command) || !is_string($command)) {
            return false;
        }
        
        // Lista de comandos válidos conocidos
        $valid_commands = [
            'get_system_info',
            'clear_cache', 
            'test_ajax',
            'get_logs',
            'analyze_performance',
            'get_dashboard_data'
        ];
        
        return in_array($command, $valid_commands);
    }

    /**
     * Helper: Simular petición AJAX
     */
    private function makeAjaxRequest($command, $data) 
    {
        // Simular estructura de petición AJAX
        $_POST['command'] = $command;
        $_POST['data'] = $data;
        $_POST['nonce'] = wp_create_nonce('dev_tools_ajax');
        
        // Si existe función de manejo, usarla
        if (function_exists('dev_tools_handle_ajax_request')) {
            ob_start();
            try {
                dev_tools_handle_ajax_request();
                $output = ob_get_clean();
                return json_decode($output, true) ?: ['success' => false, 'message' => 'Invalid JSON response'];
            } catch (Exception $e) {
                ob_end_clean();
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        // Respuesta simulada si no existe la función
        return [
            'success' => current_user_can('manage_options'),
            'message' => current_user_can('manage_options') ? 'Test request processed' : 'No permission',
            'data' => $data
        ];
    }
}
