<?php
/**
 * Clase base para tests AJAX de Dev-Tools
 * 
 * Extiende WP_Ajax_UnitTestCase siguiendo las mejores prácticas de WordPress Core
 * @see https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

class DevToolsAjaxTestCase extends WP_Ajax_UnitTestCase {

    /**
     * Configuración inicial antes de cada test AJAX
     * 
     * @since 3.0.0
     */
    public function setUp(): void {
        parent::setUp();
        
        // Limpiar datos específicos de dev-tools
        delete_option( 'dev_tools_settings' );
        delete_option( 'dev_tools_cache' );
        delete_transient( 'dev_tools_last_check' );
        
        // Configurar usuario admin para tests AJAX
        $this->setupAdminUser();
        
        // Configurar entorno AJAX
        $this->setupAjaxEnvironment();
    }

    /**
     * Limpieza después de cada test AJAX
     * 
     * @since 3.0.0
     */
    public function tearDown(): void {
        // Limpiar configuraciones AJAX
        $this->cleanupAjaxEnvironment();
        
        parent::tearDown();
    }

    /**
     * Configurar usuario administrador para tests AJAX
     * 
     * @since 3.0.0
     */
    protected function setupAdminUser() {
        // Crear usuario admin si no existe
        $admin_user = $this->factory->user->create( [
            'role' => 'administrator',
            'user_login' => 'test_admin_ajax',
            'user_email' => 'ajax@localhost'
        ] );
        
        // Establecer como usuario actual
        wp_set_current_user( $admin_user );
        
        // Agregar capacidades específicas de dev-tools
        $user = wp_get_current_user();
        $user->add_cap( 'manage_dev_tools' );
        $user->add_cap( 'edit_dev_tools' );
    }

    /**
     * Configurar entorno AJAX
     * 
     * @since 3.0.0
     */
    protected function setupAjaxEnvironment() {
        // Configurar constantes AJAX si no están definidas
        if ( ! defined( 'DOING_AJAX' ) ) {
            define( 'DOING_AJAX', true );
        }
        
        if ( ! defined( 'WP_ADMIN' ) ) {
            define( 'WP_ADMIN', true );
        }
        
        // Limpiar acciones AJAX que puedan interferir
        remove_all_actions( 'wp_ajax_dev_tools_test' );
        remove_all_actions( 'wp_ajax_nopriv_dev_tools_test' );
    }

    /**
     * Limpiar entorno AJAX
     * 
     * @since 3.0.0
     */
    protected function cleanupAjaxEnvironment() {
        // Limpiar variables globales AJAX
        unset( $_POST['action'] );
        unset( $_POST['_wpnonce'] );
        unset( $_REQUEST['action'] );
        unset( $_REQUEST['_wpnonce'] );
        
        // Limpiar buffer de salida si existe
        if ( ob_get_level() ) {
            ob_end_clean();
        }
    }

    /**
     * Helper para ejecutar acción AJAX con autenticación
     * 
     * @param string $action Acción AJAX
     * @param array $data Datos adicionales
     * @return string Respuesta AJAX
     * 
     * @since 3.0.0
     */
    protected function makeAjaxRequest( $action, $data = [] ) {
        // Configurar datos del request
        $_POST['action'] = $action;
        $_POST['_wpnonce'] = wp_create_nonce( 'dev_tools_nonce' );
        $_POST = array_merge( $_POST, $data );
        $_REQUEST = $_POST;
        
        try {
            // Ejecutar acción AJAX y capturar salida
            $this->_handleAjax( $action );
            
            // Si llegamos aquí, el AJAX se ejecutó correctamente
            return $this->_last_response;
            
        } catch ( WPAjaxDieContinueException $e ) {
            // Respuesta exitosa con wp_die()
            return $this->_last_response;
            
        } catch ( WPAjaxDieStopException $e ) {
            // Error en la respuesta AJAX
            $this->fail( 'AJAX request failed: ' . $e->getMessage() );
        }
    }

    /**
     * Helper para ejecutar acción AJAX sin autenticación (pública)
     * 
     * @param string $action Acción AJAX
     * @param array $data Datos adicionales
     * @return string Respuesta AJAX
     * 
     * @since 3.0.0
     */
    protected function makePublicAjaxRequest( $action, $data = [] ) {
        // Configurar datos del request sin autenticación
        $_POST['action'] = $action;
        $_POST = array_merge( $_POST, $data );
        $_REQUEST = $_POST;
        
        // Limpiar usuario actual para simular request no autenticado
        wp_set_current_user( 0 );
        
        try {
            // Ejecutar acción AJAX pública
            $this->_handleAjax( $action );
            
            return $this->_last_response;
            
        } catch ( WPAjaxDieContinueException $e ) {
            return $this->_last_response;
            
        } catch ( WPAjaxDieStopException $e ) {
            $this->fail( 'Public AJAX request failed: ' . $e->getMessage() );
        }
    }

    /**
     * Assert que la respuesta AJAX es exitosa
     * 
     * @param string $response Respuesta AJAX
     * @param string $message Mensaje personalizado
     * 
     * @since 3.0.0
     */
    protected function assertAjaxSuccess( $response = null, $message = '' ) {
        if ( $response === null ) {
            $response = $this->_last_response;
        }
        
        $decoded = json_decode( $response, true );
        
        $this->assertIsArray( $decoded, 'Respuesta AJAX no es JSON válido' . ( $message ? ': ' . $message : '' ) );
        $this->assertTrue( $decoded['success'], 'Respuesta AJAX no fue exitosa' . ( $message ? ': ' . $message : '' ) );
    }

    /**
     * Assert que la respuesta AJAX contiene error
     * 
     * @param string $response Respuesta AJAX
     * @param string $expected_error Error esperado (opcional)
     * @param string $message Mensaje personalizado
     * 
     * @since 3.0.0
     */
    protected function assertAjaxError( $response = null, $expected_error = null, $message = '' ) {
        if ( $response === null ) {
            $response = $this->_last_response;
        }
        
        $decoded = json_decode( $response, true );
        
        $this->assertIsArray( $decoded, 'Respuesta AJAX no es JSON válido' . ( $message ? ': ' . $message : '' ) );
        $this->assertFalse( $decoded['success'], 'Respuesta AJAX fue exitosa cuando se esperaba error' . ( $message ? ': ' . $message : '' ) );
        
        if ( $expected_error ) {
            $this->assertStringContainsString( 
                $expected_error, 
                $decoded['data'], 
                'Error AJAX no contiene mensaje esperado' . ( $message ? ': ' . $message : '' )
            );
        }
    }

    /**
     * Assert que la respuesta AJAX contiene datos específicos
     * 
     * @param array $expected_data Datos esperados
     * @param string $response Respuesta AJAX
     * @param string $message Mensaje personalizado
     * 
     * @since 3.0.0
     */
    protected function assertAjaxContainsData( $expected_data, $response = null, $message = '' ) {
        if ( $response === null ) {
            $response = $this->_last_response;
        }
        
        $decoded = json_decode( $response, true );
        
        $this->assertIsArray( $decoded, 'Respuesta AJAX no es JSON válido' . ( $message ? ': ' . $message : '' ) );
        $this->assertTrue( $decoded['success'], 'Respuesta AJAX no fue exitosa' . ( $message ? ': ' . $message : '' ) );
        
        foreach ( $expected_data as $key => $value ) {
            $this->assertArrayHasKey( 
                $key, 
                $decoded['data'], 
                "Clave '{$key}' no encontrada en datos AJAX" . ( $message ? ': ' . $message : '' )
            );
            
            $this->assertEquals( 
                $value, 
                $decoded['data'][$key], 
                "Valor para '{$key}' no coincide" . ( $message ? ': ' . $message : '' )
            );
        }
    }

    /**
     * Helper para registrar acción AJAX de test temporal
     * 
     * @param string $action Nombre de la acción
     * @param callable $callback Función callback
     * @param bool $authenticated Si requiere autenticación
     * 
     * @since 3.0.0
     */
    protected function registerTestAjaxAction( $action, $callback, $authenticated = true ) {
        if ( $authenticated ) {
            add_action( 'wp_ajax_' . $action, $callback );
        } else {
            add_action( 'wp_ajax_nopriv_' . $action, $callback );
        }
        
        // Registrar para limpieza automática
        $this->registered_actions[] = [
            'hook' => $authenticated ? 'wp_ajax_' . $action : 'wp_ajax_nopriv_' . $action,
            'callback' => $callback
        ];
    }

    /**
     * Array para acciones registradas (limpieza automática)
     * 
     * @var array
     * @since 3.0.0
     */
    protected $registered_actions = [];

    /**
     * Limpiar acciones registradas
     * 
     * @since 3.0.0
     */
    public function __destruct() {
        foreach ( $this->registered_actions as $action_data ) {
            remove_action( $action_data['hook'], $action_data['callback'] );
        }
    }
}
