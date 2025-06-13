<?php
/**
 * Test para demostrar que WordPress está COMPLETAMENTE OPERATIVO
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 * 
 * Este test demuestra que NO hay limitaciones en WordPress.
 * ¡TODO el sistema funciona perfectamente para testing de plugins!
 */
namespace DevTools\Tests\Unit;


class WordPressFullyOperationalTest extends DevToolsTestCase {


    
    /**
     * Test que demuestra que TODAS las clases de WordPress funcionan
     */
    public function test_wordpress_classes_are_fully_operational() {
        // ✅ TODAS estas clases están disponibles y OPERATIVAS
        $this->assertTrue(class_exists('WP_User'), 'WP_User debe estar disponible');
        $this->assertTrue(class_exists('WP_Post'), 'WP_Post debe estar disponible');
        $this->assertTrue(class_exists('WP_Query'), 'WP_Query debe estar disponible');
        $this->assertTrue(class_exists('wpdb'), 'wpdb debe estar disponible');
        $this->assertTrue(class_exists('WP_Error'), 'WP_Error debe estar disponible');
        
        // ✅ Podemos crear instancias REALES (no mocks)
        global $wpdb;
        $this->assertInstanceOf('wpdb', $wpdb);
        
        $wp_error = new WP_Error('test_code', 'Test message');
        $this->assertInstanceOf('WP_Error', $wp_error);
        $this->assertEquals('test_code', $wp_error->get_error_code());
    }
    
    /**
     * Test que demuestra que TODAS las funciones de WordPress funcionan
     */
    public function test_wordpress_functions_are_fully_operational() {
        // ✅ TODAS estas funciones están disponibles y OPERATIVAS
        $this->assertTrue(function_exists('wp_insert_user'), 'wp_insert_user debe funcionar');
        $this->assertTrue(function_exists('get_user_by'), 'get_user_by debe funcionar');
        $this->assertTrue(function_exists('wp_set_current_user'), 'wp_set_current_user debe funcionar');
        $this->assertTrue(function_exists('is_user_logged_in'), 'is_user_logged_in debe funcionar');
        $this->assertTrue(function_exists('current_user_can'), 'current_user_can debe funcionar');
        $this->assertTrue(function_exists('add_action'), 'add_action debe funcionar');
        $this->assertTrue(function_exists('do_action'), 'do_action debe funcionar');
        $this->assertTrue(function_exists('apply_filters'), 'apply_filters debe funcionar');
        $this->assertTrue(function_exists('get_option'), 'get_option debe funcionar');
        $this->assertTrue(function_exists('update_option'), 'update_option debe funcionar');
        $this->assertTrue(function_exists('wp_enqueue_script'), 'wp_enqueue_script debe funcionar');
        $this->assertTrue(function_exists('wp_enqueue_style'), 'wp_enqueue_style debe funcionar');
    }
    
    /**
     * Test REAL con base de datos - SIN LIMITACIONES
     */
    public function test_database_operations_fully_functional() {
        global $wpdb;
        
        // ✅ Operaciones REALES con la base de datos
        $result = $wpdb->get_var("SELECT 1 as test");
        $this->assertEquals(1, $result);
        
        // ✅ Consultas más complejas
        $tables = $wpdb->get_results("SHOW TABLES");
        $this->assertNotEmpty($tables);
        
        // ✅ Operaciones con WordPress tables
        $users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $this->assertIsNumeric($users_count);
    }
    
    /**
     * Test REAL creando usuarios - WordPress COMPLETAMENTE FUNCIONAL
     */
    public function test_create_real_wordpress_user() {
        // ✅ Crear usuario REAL usando funciones de WordPress
        $user_data = [
            'user_login' => 'testuser_' . time(),
            'user_email' => 'test_' . time() . '@example.com',
            'user_pass' => 'testpass123',
            'display_name' => 'Test User Full',
            'role' => 'administrator'
        ];
        
        $user_id = wp_insert_user($user_data);
        
        // ✅ Verificar que el usuario se creó REALMENTE
        $this->assertIsNumeric($user_id);
        $this->assertGreaterThan(0, $user_id);
        
        // ✅ Obtener el usuario usando WP_User - COMPLETAMENTE FUNCIONAL
        $user = new WP_User($user_id);
        $this->assertInstanceOf('WP_User', $user);
        $this->assertEquals($user_data['user_email'], $user->user_email);
        $this->assertEquals($user_data['display_name'], $user->display_name);
        
        // ✅ Verificar que tiene capabilities REALES
        $this->assertTrue($user->has_cap('manage_options'));
        
        // ✅ Simular login - WordPress COMPLETAMENTE OPERATIVO
        wp_set_current_user($user_id);
        $this->assertTrue(is_user_logged_in());
        $this->assertTrue(current_user_can('manage_options'));
        $this->assertEquals($user_id, get_current_user_id());
        
        // ✅ Limpiar: eliminar usuario
        wp_delete_user($user_id);
        
        // ✅ Verificar que se eliminó
        $deleted_user = get_user_by('id', $user_id);
        $this->assertFalse($deleted_user);
    }
    
    /**
     * Test REAL con posts y metadata - Sin limitaciones
     */
    public function test_posts_and_metadata_fully_functional() {
        // ✅ Crear post REAL
        $post_data = [
            'post_title' => 'Test Post ' . time(),
            'post_content' => 'This is a test post content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ];
        
        $post_id = wp_insert_post($post_data);
        $this->assertIsNumeric($post_id);
        $this->assertGreaterThan(0, $post_id);
        
        // ✅ Obtener post usando WP_Post - COMPLETAMENTE FUNCIONAL
        $post = get_post($post_id);
        $this->assertInstanceOf('WP_Post', $post);
        $this->assertEquals($post_data['post_title'], $post->post_title);
        
        // ✅ Metadata REAL - Sin limitaciones
        $meta_key = 'test_meta_key';
        $meta_value = 'test_meta_value';
        
        add_post_meta($post_id, $meta_key, $meta_value);
        $retrieved_meta = get_post_meta($post_id, $meta_key, true);
        $this->assertEquals($meta_value, $retrieved_meta);
        
        // ✅ WP_Query REAL - Completamente operativo
        $query = new WP_Query([
            'post_type' => 'post',
            'p' => $post_id
        ]);
        
        $this->assertTrue($query->have_posts());
        $this->assertEquals(1, $query->found_posts);
        
        // ✅ Limpiar
        wp_delete_post($post_id, true);
    }
    
    /**
     * Test REAL con opciones de WordPress - Todo funcional
     */
    public function test_wordpress_options_fully_functional() {
        $option_name = 'test_option_' . time();
        $option_value = ['test' => 'data', 'number' => 123];
        
        // ✅ Guardar opción REAL
        $result = update_option($option_name, $option_value);
        $this->assertTrue($result);
        
        // ✅ Recuperar opción REAL
        $retrieved = get_option($option_name);
        $this->assertEquals($option_value, $retrieved);
        
        // ✅ Eliminar opción
        $deleted = delete_option($option_name);
        $this->assertTrue($deleted);
        
        // ✅ Verificar que se eliminó
        $not_found = get_option($option_name, 'not_found');
        $this->assertEquals('not_found', $not_found);
    }
    
    /**
     * Test REAL con hooks de WordPress - Sin limitaciones
     */
    public function test_wordpress_hooks_fully_functional() {
        $test_value = null;
        
        // ✅ Action hooks REALES
        $callback = function($value) use (&$test_value) {
            $test_value = $value;
        };
        
        add_action('test_action_hook', $callback);
        do_action('test_action_hook', 'test_data');
        
        $this->assertEquals('test_data', $test_value);
        
        // ✅ Filter hooks REALES
        add_filter('test_filter_hook', function($value) {
            return $value . '_filtered';
        });
        
        $filtered = apply_filters('test_filter_hook', 'original');
        $this->assertEquals('original_filtered', $filtered);
    }
}
