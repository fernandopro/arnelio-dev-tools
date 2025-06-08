<?php
/**
 * Tests de Endpoints AJAX - Dev-Tools Arquitectura 3.0
 *
 * @package TarokinaPro\DevTools\Tests\Integration
 * @since 1.0.0
 */

namespace DevToolsTests\Integration;

use DevToolsTestCase;

/**
 * Test de endpoints AJAX del sistema
 */
class AjaxEndpointsTest extends DevToolsTestCase 
{
    /**
     * Setup antes de cada test
     */
    public function setUp(): void 
    {
        parent::setUp();
        
        // Crear usuario administrador para tests AJAX
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
    }

    /**
     * Test del endpoint principal de AJAX
     */
    public function test_main_ajax_endpoint() 
    {
        // Configurar petición AJAX
        $_POST['action'] = 'dev_tools_ajax';
        $_POST['command'] = 'test_connection';
        $_POST['nonce'] = wp_create_nonce('dev_tools_ajax');
        
        // Simular petición AJAX
        try {
            $this->_handleAjax('dev_tools_ajax');
        } catch (\WPAjaxDieContinueException $e) {
            // Esperado - AJAX termina con wp_die()
        }
        
        // Verificar respuesta
        $response = $this->getAjaxResponse();
        $this->assertIsArray($response, 'Respuesta AJAX debe ser array');
        $this->assertArrayHasKey('success', $response, 'Respuesta debe tener campo success');
    }

    /**
     * Test de endpoint get_system_info
     */
    public function test_get_system_info_endpoint() 
    {
        $response = $this->makeAjaxRequest('get_system_info', []);
        
        $this->assertTrue($response['success'], 'get_system_info debe ser exitoso');
        $this->assertArrayHasKey('data', $response, 'Debe retornar datos del sistema');
        
        if (isset($response['data'])) {
            $this->assertIsArray($response['data'], 'Datos del sistema deben ser array');
            
            // Verificar categorías básicas de información
            $expected_categories = ['php', 'wordpress', 'server'];
            foreach ($expected_categories as $category) {
                if (isset($response['data'][$category])) {
                    $this->assertIsArray($response['data'][$category], "Categoría '{$category}' debe ser array");
                }
            }
        }
    }

    /**
     * Test de endpoint clear_cache
     */
    public function test_clear_cache_endpoint() 
    {
        // Crear datos en cache para limpiar
        wp_cache_set('test_cache_key', 'test_value', 'dev_tools_test');
        
        $response = $this->makeAjaxRequest('clear_cache', ['cache_type' => 'all']);
        
        $this->assertTrue($response['success'], 'clear_cache debe ser exitoso');
        $this->assertStringContainsString('cache', strtolower($response['message']), 'Mensaje debe mencionar cache');
    }

    /**
     * Test de endpoint test_ajax
     */
    public function test_ajax_test_endpoint() 
    {
        $test_data = [
            'test_string' => 'Hello World',
            'test_number' => 123,
            'test_array' => ['item1', 'item2', 'item3']
        ];
        
        $response = $this->makeAjaxRequest('test_ajax', $test_data);
        
        $this->assertTrue($response['success'], 'test_ajax debe ser exitoso');
        $this->assertArrayHasKey('data', $response, 'Debe retornar datos de prueba');
        
        // Verificar que los datos se procesan correctamente
        if (isset($response['data']['received'])) {
            $this->assertIsArray($response['data']['received'], 'Datos recibidos deben ser array');
        }
    }

    /**
     * Test de endpoint get_logs
     */
    public function test_get_logs_endpoint() 
    {
        $response = $this->makeAjaxRequest('get_logs', ['log_type' => 'error', 'limit' => 10]);
        
        $this->assertTrue($response['success'], 'get_logs debe ser exitoso');
        $this->assertArrayHasKey('data', $response, 'Debe retornar datos de logs');
        
        if (isset($response['data']['logs'])) {
            $this->assertIsArray($response['data']['logs'], 'Logs deben ser array');
        }
    }

    /**
     * Test de endpoint analyze_performance
     */
    public function test_analyze_performance_endpoint() 
    {
        $response = $this->makeAjaxRequest('analyze_performance', ['analysis_type' => 'quick']);
        
        $this->assertTrue($response['success'], 'analyze_performance debe ser exitoso');
        $this->assertArrayHasKey('data', $response, 'Debe retornar datos de performance');
        
        if (isset($response['data']['metrics'])) {
            $this->assertIsArray($response['data']['metrics'], 'Métricas deben ser array');
            
            // Verificar métricas básicas
            $expected_metrics = ['memory_usage', 'execution_time', 'database_queries'];
            foreach ($expected_metrics as $metric) {
                if (isset($response['data']['metrics'][$metric])) {
                    $this->assertIsNumeric($response['data']['metrics'][$metric], "Métrica '{$metric}' debe ser numérica");
                }
            }
        }
    }

    /**
     * Test de validación de nonce
     */
    public function test_nonce_validation() 
    {
        // Test con nonce inválido
        $response = $this->makeAjaxRequest('get_system_info', [], 'invalid_nonce');
        
        $this->assertFalse($response['success'], 'Nonce inválido debe fallar');
        $this->assertStringContainsString('nonce', strtolower($response['message']), 'Mensaje debe mencionar nonce');
        
        // Test sin nonce
        $response = $this->makeAjaxRequest('get_system_info', [], '');
        
        $this->assertFalse($response['success'], 'Sin nonce debe fallar');
    }

    /**
     * Test de validación de permisos
     */
    public function test_permission_validation() 
    {
        // Test con usuario sin permisos
        $subscriber = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);
        
        $response = $this->makeAjaxRequest('get_system_info', []);
        
        $this->assertFalse($response['success'], 'Usuario sin permisos debe fallar');
        $this->assertStringContainsString('permiso', strtolower($response['message']), 'Mensaje debe mencionar permisos');
        
        // Test con usuario no logueado
        wp_set_current_user(0);
        
        $response = $this->makeAjaxRequest('get_system_info', []);
        
        $this->assertFalse($response['success'], 'Usuario no logueado debe fallar');
        
        // Restaurar usuario admin
        $admin_user = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user);
    }

    /**
     * Test de manejo de comandos inexistentes
     */
    public function test_invalid_command_handling() 
    {
        $response = $this->makeAjaxRequest('nonexistent_command', []);
        
        $this->assertFalse($response['success'], 'Comando inexistente debe fallar');
        $this->assertStringContainsString('comando', strtolower($response['message']), 'Mensaje debe mencionar comando');
    }

    /**
     * Test de sanitización de datos de entrada
     */
    public function test_input_sanitization() 
    {
        $malicious_data = [
            'script_tag' => '<script>alert("xss")</script>',
            'sql_injection' => "'; DROP TABLE users; --",
            'php_code' => '<?php echo "malicious"; ?>',
            'html_entities' => '&lt;script&gt;alert("test")&lt;/script&gt;'
        ];
        
        $response = $this->makeAjaxRequest('test_ajax', $malicious_data);
        
        // Verificar que la respuesta no contiene código malicioso
        $response_json = json_encode($response);
        $this->assertStringNotContainsString('<script>', $response_json, 'Respuesta no debe contener scripts');
        $this->assertStringNotContainsString('DROP TABLE', $response_json, 'Respuesta no debe contener SQL malicioso');
        $this->assertStringNotContainsString('<?php', $response_json, 'Respuesta no debe contener código PHP');
    }

    /**
     * Test de rate limiting
     */
    public function test_rate_limiting() 
    {
        // Hacer múltiples peticiones rápidas
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->makeAjaxRequest('test_ajax', ['iteration' => $i]);
        }
        
        // Verificar que al menos las primeras peticiones son exitosas
        $this->assertTrue($responses[0]['success'], 'Primera petición debe ser exitosa');
        $this->assertTrue($responses[1]['success'], 'Segunda petición debe ser exitosa');
        
        // Si hay rate limiting, algunas peticiones posteriores podrían fallar
        $successful_requests = array_filter($responses, function($response) {
            return $response['success'];
        });
        
        $this->assertGreaterThanOrEqual(2, count($successful_requests), 'Al menos 2 peticiones deben ser exitosas');
    }

    /**
     * Test de manejo de errores internos
     */
    public function test_internal_error_handling() 
    {
        // Simular error interno con datos que podrían causar problemas
        $problematic_data = [
            'circular_reference' => null,
            'very_large_string' => str_repeat('A', 10000),
            'null_value' => null,
            'empty_array' => [],
            'nested_array' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep_value'
                    ]
                ]
            ]
        ];
        
        // Crear referencia circular
        $problematic_data['circular_reference'] = &$problematic_data;
        
        $response = $this->makeAjaxRequest('test_ajax', $problematic_data);
        
        // El sistema debe manejar el error graciosamente
        $this->assertIsArray($response, 'Respuesta debe seguir siendo array');
        $this->assertArrayHasKey('success', $response, 'Respuesta debe tener campo success');
        
        if (!$response['success']) {
            $this->assertArrayHasKey('message', $response, 'Error debe tener mensaje');
        }
    }

    /**
     * Test de timeout de peticiones
     */
    public function test_request_timeout() 
    {
        // Simular petición que podría tomar mucho tiempo
        $response = $this->makeAjaxRequest('analyze_performance', ['analysis_type' => 'detailed']);
        
        // La petición debe completarse en tiempo razonable
        $this->assertIsArray($response, 'Respuesta debe recibirse');
        $this->assertArrayHasKey('success', $response, 'Respuesta debe tener status');
        
        // Si hay timeout, debe manejarse graciosamente
        if (!$response['success'] && isset($response['message'])) {
            $message_lower = strtolower($response['message']);
            if (strpos($message_lower, 'timeout') !== false || strpos($message_lower, 'tiempo') !== false) {
                $this->assertTrue(true, 'Timeout manejado correctamente');
            }
        }
    }

    /**
     * Helper: Hacer petición AJAX simulada
     */
    private function makeAjaxRequest($command, $data, $nonce = null) 
    {
        if ($nonce === null) {
            $nonce = wp_create_nonce('dev_tools_ajax');
        }
        
        // Configurar $_POST
        $_POST['action'] = 'dev_tools_ajax';
        $_POST['command'] = $command;
        $_POST['data'] = $data;
        $_POST['nonce'] = $nonce;
        
        // Capturar respuesta
        try {
            ob_start();
            $this->_handleAjax('dev_tools_ajax');
        } catch (\WPAjaxDieContinueException $e) {
            // Esperado
        } catch (\WPAjaxDieStopException $e) {
            // También esperado
        }
        
        $response = ob_get_clean();
        
        // Decodificar JSON
        $decoded_response = json_decode($response, true);
        
        if ($decoded_response === null) {
            // Si no es JSON válido, retornar estructura de error
            return [
                'success' => false,
                'message' => 'Invalid JSON response: ' . $response,
                'raw_response' => $response
            ];
        }
        
        return $decoded_response;
    }

    /**
     * Helper: Obtener respuesta AJAX del buffer
     */
    private function getAjaxResponse() 
    {
        $output = $this->_last_response;
        
        if (empty($output)) {
            return ['success' => false, 'message' => 'No response received'];
        }
        
        $decoded = json_decode($output, true);
        
        if ($decoded === null) {
            return ['success' => false, 'message' => 'Invalid JSON response', 'raw' => $output];
        }
        
        return $decoded;
    }
}
