<?php
/**
 * Test para errores 400 (Bad Request) en AJAX
 * 
 * @package DevTools
 * @subpackage Tests
 */

require_once __DIR__ . '/../bootstrap.php';

class Ajax400ErrorTest extends DevToolsTestCase {
    
    protected $ajax_handler;
    
    public function setUp(): void {
        parent::setUp();
        
        // Cargar el debug helper
        require_once dirname(__DIR__, 2) . '/debug-ajax-400.php';
        
        // Obtener instancia del AJAX handler
        $this->ajax_handler = DevToolsAjaxHandler::getInstance();
        
        // Configurar usuario admin
        $admin_id = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
    }
    
    /**
     * Test: Request sin método POST debe dar error 400
     */
    public function test_non_post_request_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: Only POST method allowed', $data['data']['message']);
            throw $e; // Re-lanzar para el expectException
        }
    }
    
    /**
     * Test: Request sin datos POST debe dar error 400
     */
    public function test_empty_post_data_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: No POST data received', $data['data']['message']);
            throw $e;
        }
    }
    
    /**
     * Test: Action de WordPress incorrecta debe dar error 400
     */
    public function test_wrong_wordpress_action_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'wrong_action',
            'nonce' => wp_create_nonce('tarokina-2025_dev_tools_nonce'),
            'action_type' => 'ping'
        ];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: Invalid WordPress action', $data['data']['message']);
            throw $e;
        }
    }
    
    /**
     * Test: Nonce faltante debe dar error 400
     */
    public function test_missing_nonce_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'tarokina-2025_dev_tools',
            'action_type' => 'ping'
        ];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: Nonce parameter missing', $data['data']['message']);
            throw $e;
        }
    }
    
    /**
     * Test: Nonce inválido debe dar error 400
     */
    public function test_invalid_nonce_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'tarokina-2025_dev_tools',
            'nonce' => 'invalid_nonce',
            'action_type' => 'ping'
        ];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: Invalid or expired nonce', $data['data']['message']);
            throw $e;
        }
    }
    
    /**
     * Test: action_type faltante debe dar error 400
     */
    public function test_missing_action_type_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'tarokina-2025_dev_tools',
            'nonce' => wp_create_nonce('tarokina-2025_dev_tools_nonce')
        ];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: action_type parameter missing', $data['data']['message']);
            throw $e;
        }
    }
    
    /**
     * Test: action_type con caracteres inválidos debe dar error 400
     */
    public function test_invalid_action_type_characters_gives_400() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'tarokina-2025_dev_tools',
            'nonce' => wp_create_nonce('tarokina-2025_dev_tools_nonce'),
            'action_type' => 'ping<script>alert("xss")</script>'
        ];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertFalse($data['success']);
            $this->assertStringContains('Bad Request: action_type contains invalid characters', $data['data']['message']);
            throw $e;
        }
    }
    
    /**
     * Test: Request válido debe funcionar correctamente
     */
    public function test_valid_request_works() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'tarokina-2025_dev_tools',
            'nonce' => wp_create_nonce('tarokina-2025_dev_tools_nonce'),
            'action_type' => 'ping'
        ];
        
        $this->expectException(WPAjaxDieContinueException::class);
        
        ob_start();
        try {
            $this->ajax_handler->handleAjaxRequest();
        } catch (WPAjaxDieContinueException $e) {
            $response = ob_get_clean();
            $data = json_decode($response, true);
            
            $this->assertTrue($data['success']);
            $this->assertEquals('ping', $data['data']['action']);
            $this->assertArrayHasKey('timestamp', $data['data']);
            $this->assertArrayHasKey('request_size', $data['data']);
            throw $e;
        }
    }
    
    /**
     * Test: Validador de debug helper
     */
    public function test_debug_validator() {
        // Test request válido
        $valid_request = DevToolsAjax400Debug::generateValidTestRequest('ping');
        $validation = DevToolsAjax400Debug::validateAjaxRequest($valid_request);
        
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertIsArray($validation['warnings']);
        
        // Test request inválido
        $invalid_request = $valid_request;
        unset($invalid_request['nonce']);
        $validation = DevToolsAjax400Debug::validateAjaxRequest($invalid_request);
        
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }
    
    /**
     * Test: Ejecutar tests completos de validación
     */
    public function test_complete_validation_tests() {
        $results = DevToolsAjax400Debug::runValidationTest();
        
        $this->assertArrayHasKey('tests', $results);
        $this->assertArrayHasKey('timestamp', $results);
        
        // Verificar que los tests individuales funcionan
        $this->assertTrue($results['tests']['valid_request']['passed']);
        $this->assertTrue($results['tests']['no_nonce']['passed']); // Debe fallar = test pasa
        $this->assertTrue($results['tests']['wrong_action']['passed']); // Debe fallar = test pasa
        $this->assertTrue($results['tests']['special_chars']['passed']);
    }
}
