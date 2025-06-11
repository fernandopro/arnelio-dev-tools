<?php
/**
 * Test con WordPress Test Suite - Funcionalidad completa de WordPress
 * 
 * Este test usa el framework completo de WordPress Test Suite
 * Incluye base de datos, usuarios, posts, y todas las funciones de WordPress
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

class WordPressIntegrationTest extends WP_UnitTestCase {
    
    private $admin_user_id;
    private $test_post_id;
    
    /**
     * @group WordPress
     * Configuración inicial del test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Crear usuario administrador para tests
        $this->admin_user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'test_admin_wp',
            'user_email' => 'admin@wp-test.local'
        ]);
        
        // Crear un post de prueba
        $this->test_post_id = $this->factory->post->create([
            'post_title' => 'Test Post for Dev-Tools',
            'post_content' => 'Content for testing Dev-Tools functionality',
            'post_author' => $this->admin_user_id,
            'post_status' => 'publish'
        ]);
        
        // Establecer el usuario actual
        wp_set_current_user($this->admin_user_id);
    }
    
    /**
     * @group WordPress
     * Test básico de WordPress functions
     */
    public function test_wordpress_functions_available() {
        // Verificar que las funciones básicas de WordPress están disponibles
        $this->assertTrue(function_exists('get_option'), 'get_option should be available');
        $this->assertTrue(function_exists('get_site_url'), 'get_site_url should be available');
        $this->assertTrue(function_exists('wp_insert_post'), 'wp_insert_post should be available');
        $this->assertTrue(function_exists('get_user_by'), 'get_user_by should be available');
        $this->assertTrue(function_exists('current_user_can'), 'current_user_can should be available');
    }
    
    /**
     * @group WordPress
     * Test de usuarios y capacidades
     */
    public function test_user_capabilities() {
        // Verificar que el usuario admin fue creado correctamente
        $user = get_user_by('ID', $this->admin_user_id);
        
        $this->assertInstanceOf('WP_User', $user, 'Should create a valid WP_User');
        $this->assertEquals('test_admin_wp', $user->user_login, 'Username should match');
        $this->assertTrue(user_can($user, 'manage_options'), 'Admin should have manage_options capability');
        $this->assertTrue(user_can($user, 'edit_posts'), 'Admin should have edit_posts capability');
        
        // Verificar usuario actual
        $current_user = wp_get_current_user();
        $this->assertEquals($this->admin_user_id, $current_user->ID, 'Current user should be our test admin');
        $this->assertTrue(current_user_can('manage_options'), 'Current user should have admin capabilities');
    }
    
    /**
     * @group WordPress
     * Test de posts y contenido
     */
    public function test_post_creation_and_retrieval() {
        // Verificar que el post fue creado
        $post = get_post($this->test_post_id);
        
        $this->assertInstanceOf('WP_Post', $post, 'Should create a valid WP_Post');
        $this->assertEquals('Test Post for Dev-Tools', $post->post_title, 'Post title should match');
        $this->assertEquals('publish', $post->post_status, 'Post should be published');
        $this->assertEquals($this->admin_user_id, $post->post_author, 'Post author should be our test user');
        
        // Verificar contenido del post
        $content = get_post_field('post_content', $this->test_post_id);
        $this->assertStringContainsString('testing Dev-Tools', $content, 'Post content should contain expected text');
    }
    
    /**
     * @group WordPress
     * Test de opciones y configuración
     */
    public function test_wordpress_options() {
        // Probar operaciones con opciones
        $test_option_name = 'dev_tools_test_option';
        $test_option_value = ['test' => 'value', 'number' => 42];
        
        // Agregar opción
        $result = add_option($test_option_name, $test_option_value);
        $this->assertTrue($result, 'Should be able to add option');
        
        // Recuperar opción
        $retrieved_value = get_option($test_option_name);
        $this->assertEquals($test_option_value, $retrieved_value, 'Retrieved option should match original');
        
        // Actualizar opción
        $new_value = ['updated' => 'value', 'number' => 84];
        $update_result = update_option($test_option_name, $new_value);
        $this->assertTrue($update_result, 'Should be able to update option');
        
        $updated_value = get_option($test_option_name);
        $this->assertEquals($new_value, $updated_value, 'Updated option should match new value');
        
        // Eliminar opción
        $delete_result = delete_option($test_option_name);
        $this->assertTrue($delete_result, 'Should be able to delete option');
        
        $deleted_value = get_option($test_option_name, 'default');
        $this->assertEquals('default', $deleted_value, 'Deleted option should return default value');
    }
    
    /**
     * @group WordPress
     * Test de URL del sitio y configuración
     */
    public function test_site_url_and_configuration() {
        // Verificar URLs del sitio
        $site_url = get_site_url();
        $home_url = get_home_url();
        
        $this->assertNotEmpty($site_url, 'Site URL should not be empty');
        $this->assertNotEmpty($home_url, 'Home URL should not be empty');
        $this->assertStringStartsWith('http', $site_url, 'Site URL should start with http');
        $this->assertStringStartsWith('http', $home_url, 'Home URL should start with http');
        
        // Verificar información del sitio
        $blog_name = get_option('blogname');
        $this->assertNotEmpty($blog_name, 'Blog name should not be empty');
        
        // Verificar información de WordPress
        $wp_version = get_bloginfo('version');
        $this->assertNotEmpty($wp_version, 'WordPress version should be available');
        $this->assertTrue(version_compare($wp_version, '5.0', '>='), 'WordPress version should be 5.0 or higher');
    }
    
    /**
     * @group WordPress
     * Test de hooks y filtros
     */
    public function test_wordpress_hooks_and_filters() {
        $test_value = 'original_value';
        $modified_value = 'modified_value';
        
        // Agregar filtro
        add_filter('dev_tools_test_filter', function($value) use ($modified_value) {
            return $modified_value;
        });
        
        // Aplicar filtro
        $filtered_value = apply_filters('dev_tools_test_filter', $test_value);
        $this->assertEquals($modified_value, $filtered_value, 'Filter should modify the value');
        
        // Test de acciones
        $action_called = false;
        add_action('dev_tools_test_action', function() use (&$action_called) {
            $action_called = true;
        });
        
        // Ejecutar acción
        do_action('dev_tools_test_action');
        $this->assertTrue($action_called, 'Action should be called');
    }
    
    /**
     * @group WordPress
     * Test de transients (cache temporal)
     */
    public function test_wordpress_transients() {
        $transient_name = 'dev_tools_test_transient';
        $transient_value = ['cache' => 'data', 'timestamp' => time()];
        
        // Establecer transient
        $result = set_transient($transient_name, $transient_value, 3600);
        $this->assertTrue($result, 'Should be able to set transient');
        
        // Recuperar transient
        $retrieved_value = get_transient($transient_name);
        $this->assertEquals($transient_value, $retrieved_value, 'Retrieved transient should match original');
        
        // Eliminar transient
        $delete_result = delete_transient($transient_name);
        $this->assertTrue($delete_result, 'Should be able to delete transient');
        
        $deleted_value = get_transient($transient_name);
        $this->assertFalse($deleted_value, 'Deleted transient should return false');
    }
    
    /**
     * @group WordPress
     * Test de base de datos WordPress
     */
    public function test_wordpress_database() {
        global $wpdb;
        
        // Verificar que $wpdb está disponible
        $this->assertInstanceOf('wpdb', $wpdb, 'Global $wpdb should be available');
        
        // Probar consulta simple
        $user_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $this->assertGreaterThan(0, $user_count, 'Should have at least one user in database');
        
        // Verificar prefijo de tablas de test
        $this->assertStringStartsWith('test_', $wpdb->prefix, 'Should use test_ prefix for tables');
        
        // Verificar que las tablas principales existen
        $tables = ['posts', 'users', 'options', 'postmeta', 'usermeta'];
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
            $this->assertNotEmpty($table_exists, "Table {$table} should exist");
        }
    }
    
    /**
     * @group WordPress
     * Test de WordPress assertions específicas
     */
    public function test_wordpress_specific_assertions() {
        // Test WP_Error
        $error = new WP_Error('test_error', 'Test error message');
        $this->assertWPError($error, 'Should recognize WP_Error object');
        
        $not_error = 'This is not an error';
        $this->assertNotWPError($not_error, 'Should recognize non-error value');
        
        // Test arrays con WP_UnitTestCase assertions
        $array1 = ['a', 'b', 'c'];
        $array2 = ['c', 'a', 'b'];
        $this->assertEqualSets($array1, $array2, 'Arrays should contain same elements regardless of order');
        
        $indexed_array1 = ['key1' => 'value1', 'key2' => 'value2'];
        $indexed_array2 = ['key2' => 'value2', 'key1' => 'value1'];
        $this->assertEqualSetsWithIndex($indexed_array1, $indexed_array2, 'Associative arrays should be equal');
    }
    
    /**
     * @group WordPress
     * Test de limpieza después del test
     */
    public function tearDown(): void {
        // WordPress Test Suite limpia automáticamente, pero podemos hacer limpieza específica
        
        // Eliminar opciones de prueba si quedaron
        delete_option('dev_tools_test_option');
        delete_transient('dev_tools_test_transient');
        
        parent::tearDown();
    }
}
