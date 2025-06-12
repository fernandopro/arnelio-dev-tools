<?php
/**
 * Clase base para tests de Dev-Tools
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 */

class DevToolsTestCase extends WP_UnitTestCase {

    /**
     * Almacena la última respuesta AJAX
     */
    protected $_last_response = '';

    /**
     * Setup que se ejecuta antes de cada test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Limpiar la respuesta AJAX anterior
        $this->_last_response = '';
        
        // Limpiar cualquier configuración previa
        $this->clean_dev_tools_config();
        
        // Configurar el entorno de testing
        $this->setup_dev_tools_environment();
    }

    /**
     * Teardown que se ejecuta después de cada test
     */
    public function tearDown(): void {
        // Limpiar después del test
        $this->clean_dev_tools_config();
        
        parent::tearDown();
    }

    /**
     * Limpiar configuración de Dev-Tools
     */
    protected function clean_dev_tools_config() {
        // Limpiar opciones de WordPress relacionadas con Dev-Tools
        delete_option( 'dev_tools_config' );
        delete_option( 'dev_tools_modules' );
        delete_option( 'dev_tools_cache' );
        
        // Limpiar transients
        delete_transient( 'dev_tools_system_info' );
        delete_transient( 'dev_tools_performance_data' );
    }

    /**
     * Configurar entorno de testing para Dev-Tools
     */
    protected function setup_dev_tools_environment() {
        // Definir constantes de testing si no están definidas
        if ( ! defined( 'DEV_TOOLS_TESTING' ) ) {
            define( 'DEV_TOOLS_TESTING', true );
        }
        
        if ( ! defined( 'DEV_TOOLS_TEST_MODE' ) ) {
            define( 'DEV_TOOLS_TEST_MODE', 'unit' );
        }
    }

    /**
     * Obtener la ruta base de Dev-Tools
     */
    protected function get_dev_tools_path() {
        return dirname( dirname( __DIR__ ) );
    }

    /**
     * Crear un usuario admin para testing
     */
    protected function create_admin_user() {
        return static::factory()->user->create( [
            'role' => 'administrator'
        ] );
    }

    /**
     * Crear un post de prueba
     */
    protected function create_test_post( $args = [] ) {
        $defaults = [
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ];
        
        return static::factory()->post->create( array_merge( $defaults, $args ) );
    }

    /**
     * Crear una página de prueba
     */
    protected function create_test_page( $args = [] ) {
        $defaults = [
            'post_title' => 'Test Page',
            'post_content' => 'Test page content',
            'post_status' => 'publish',
            'post_type' => 'page'
        ];
        
        return static::factory()->post->create( array_merge( $defaults, $args ) );
    }

    /**
     * Crear un usuario de prueba con rol específico
     */
    protected function create_test_user( $role = 'subscriber', $args = [] ) {
        $defaults = [
            'role' => $role,
            'user_login' => 'testuser_' . wp_generate_password( 6, false ),
            'user_email' => 'test_' . wp_generate_password( 6, false ) . '@example.com'
        ];
        
        return static::factory()->user->create( array_merge( $defaults, $args ) );
    }

    /**
     * Simular una petición AJAX
     */
    protected function simulate_ajax_request( $action, $data = [] ) {
        $_POST['action'] = $action;
        $_POST['nonce'] = wp_create_nonce( 'dev_tools_nonce' );
        
        foreach ( $data as $key => $value ) {
            $_POST[$key] = $value;
        }
        
        // Capturar output buffer para la respuesta
        ob_start();
        
        try {
            do_action( 'wp_ajax_' . $action );
        } catch ( \WPAjaxDieContinueException $e ) {
            // Expected for AJAX tests
        }
        
        $this->_last_response = ob_get_clean();
    }

    /**
     * Obtener el output de la última respuesta AJAX
     */
    protected function get_ajax_response() {
        return $this->_last_response;
    }

    /**
     * Verificar que una tabla de prueba existe
     */
    protected function assert_test_table_exists( $table_name ) {
        global $wpdb;
        
        $full_table_name = $wpdb->prefix . $table_name;
        $table_exists = $wpdb->get_var( 
            $wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $full_table_name 
            ) 
        );
        
        $this->assertEquals( $full_table_name, $table_exists );
    }

    /**
     * Verificar que un módulo está cargado
     */
    protected function assert_module_loaded( $module_name ) {
        $this->assertTrue( 
            class_exists( "DevTools\\Modules\\{$module_name}Module" ),
            "El módulo {$module_name} debería estar cargado"
        );
    }

    /**
     * Crear datos de testing para un módulo
     */
    protected function create_module_test_data( $module_name, $data = [] ) {
        $default_data = [
            'name' => $module_name,
            'status' => 'active',
            'version' => '1.0.0',
            'config' => []
        ];
        
        return array_merge( $default_data, $data );
    }
}
