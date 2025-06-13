<?php
/**
 * Tests de Operaciones CRUD Avanzadas - Dev-Tools Arquitectura 3.0
 * 
 * Tests para operaciones Create, Read, Update, Delete complejas
 * 
 * @package DevTools
 * @subpackage Tests\Database
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class DatabaseCrudTest extends DevToolsTestCase {

    /**
     * Test: Operaciones CRUD en posts
     */
    public function test_posts_crud_operations() {
        global $wpdb;
        
        // CREATE - Crear post con datos complejos
        $post_data = [
            'post_title' => 'Test CRUD Post',
            'post_content' => 'Contenido de prueba para operaciones CRUD',
            'post_excerpt' => 'Extracto del post de prueba',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => $this->create_admin_user(),
            'meta_input' => [
                'custom_field_1' => 'valor_personalizado_1',
                'custom_field_2' => 'valor_personalizado_2',
                'numeric_field' => 42
            ]
        ];
        
        $post_id = wp_insert_post($post_data);
        $this->assertGreaterThan(0, $post_id, "Debería crear el post correctamente");
        
        // READ - Verificar que se creó correctamente
        $created_post = get_post($post_id);
        $this->assertNotNull($created_post, "El post debería existir");
        $this->assertEquals($post_data['post_title'], $created_post->post_title);
        $this->assertEquals($post_data['post_content'], $created_post->post_content);
        $this->assertEquals($post_data['post_excerpt'], $created_post->post_excerpt);
        
        // Verificar metadatos
        $this->assertEquals('valor_personalizado_1', get_post_meta($post_id, 'custom_field_1', true));
        $this->assertEquals('valor_personalizado_2', get_post_meta($post_id, 'custom_field_2', true));
        $this->assertEquals(42, get_post_meta($post_id, 'numeric_field', true));
        
        // UPDATE - Actualizar el post
        $update_data = [
            'ID' => $post_id,
            'post_title' => 'Test CRUD Post - Actualizado',
            'post_content' => 'Contenido actualizado para pruebas CRUD',
            'post_status' => 'draft'
        ];
        
        $update_result = wp_update_post($update_data);
        $this->assertEquals($post_id, $update_result, "La actualización debería ser exitosa");
        
        // Verificar actualización
        $updated_post = get_post($post_id);
        $this->assertEquals($update_data['post_title'], $updated_post->post_title);
        $this->assertEquals($update_data['post_content'], $updated_post->post_content);
        $this->assertEquals('draft', $updated_post->post_status);
        
        // UPDATE metadatos
        update_post_meta($post_id, 'custom_field_1', 'valor_actualizado');
        add_post_meta($post_id, 'new_field', 'nuevo_valor');
        
        $this->assertEquals('valor_actualizado', get_post_meta($post_id, 'custom_field_1', true));
        $this->assertEquals('nuevo_valor', get_post_meta($post_id, 'new_field', true));
        
        // DELETE - Eliminar metadatos específicos
        delete_post_meta($post_id, 'custom_field_2');
        $this->assertEquals('', get_post_meta($post_id, 'custom_field_2', true));
        
        // DELETE - Eliminar post
        $delete_result = wp_delete_post($post_id, true);
        $this->assertNotFalse($delete_result, "La eliminación debería ser exitosa");
        
        // Verificar eliminación
        $deleted_post = get_post($post_id);
        $this->assertNull($deleted_post, "El post no debería existir después de eliminarlo");
    }

    /**
     * Test: Operaciones CRUD en usuarios
     */
    public function test_users_crud_operations() {
        // CREATE - Crear usuario con metadatos
        $user_data = [
            'user_login' => 'testuser_crud_' . time(),
            'user_email' => 'testcrud' . time() . '@example.com',
            'user_pass' => 'test_password_123',
            'first_name' => 'Test',
            'last_name' => 'CRUD',
            'role' => 'subscriber'
        ];
        
        $user_id = wp_insert_user($user_data);
        $this->assertGreaterThan(0, $user_id, "Debería crear el usuario correctamente");
        
        // Agregar metadatos de usuario
        add_user_meta($user_id, 'custom_user_field', 'valor_usuario');
        add_user_meta($user_id, 'user_preferences', 'preference_value');
        
        // READ - Verificar creación
        $created_user = get_user_by('id', $user_id);
        $this->assertNotFalse($created_user, "El usuario debería existir");
        $this->assertEquals($user_data['user_login'], $created_user->user_login);
        $this->assertEquals($user_data['user_email'], $created_user->user_email);
        $this->assertEquals($user_data['first_name'], $created_user->first_name);
        
        // Verificar metadatos de usuario
        $this->assertEquals('valor_usuario', get_user_meta($user_id, 'custom_user_field', true));
        $this->assertEquals('preference_value', get_user_meta($user_id, 'user_preferences', true));
        
        // UPDATE - Actualizar usuario
        $update_data = [
            'ID' => $user_id,
            'first_name' => 'Test Updated',
            'last_name' => 'CRUD Updated',
            'description' => 'Usuario actualizado para testing CRUD'
        ];
        
        $update_result = wp_update_user($update_data);
        $this->assertEquals($user_id, $update_result, "La actualización del usuario debería ser exitosa");
        
        // Verificar actualización
        $updated_user = get_user_by('id', $user_id);
        $this->assertEquals($update_data['first_name'], $updated_user->first_name);
        $this->assertEquals($update_data['last_name'], $updated_user->last_name);
        $this->assertEquals($update_data['description'], $updated_user->description);
        
        // UPDATE metadatos de usuario
        update_user_meta($user_id, 'custom_user_field', 'valor_actualizado_usuario');
        
        $this->assertEquals('valor_actualizado_usuario', get_user_meta($user_id, 'custom_user_field', true));
        
        // DELETE - Eliminar usuario
        $delete_result = wp_delete_user($user_id);
        $this->assertTrue($delete_result, "La eliminación del usuario debería ser exitosa");
        
        // Verificar eliminación
        $deleted_user = get_user_by('id', $user_id);
        $this->assertFalse($deleted_user, "El usuario no debería existir después de eliminarlo");
    }

    /**
     * Test: Operaciones CRUD en taxonomías y términos
     */
    public function test_taxonomy_crud_operations() {
        // CREATE - Crear taxonomía personalizada
        $taxonomy_args = [
            'labels' => [
                'name' => 'Test Categories',
                'singular_name' => 'Test Category'
            ],
            'public' => true,
            'hierarchical' => true
        ];
        
        register_taxonomy('test_taxonomy', 'post', $taxonomy_args);
        
        // Verificar que la taxonomía se registró
        $this->assertTrue(taxonomy_exists('test_taxonomy'), "La taxonomía test_taxonomy debería existir");
        
        // CREATE - Crear términos
        $parent_term = wp_insert_term('Parent Category', 'test_taxonomy', [
            'description' => 'Categoría padre para testing'
        ]);
        
        $this->assertIsArray($parent_term, "Debería crear el término padre correctamente");
        $this->assertArrayHasKey('term_id', $parent_term);
        
        $child_term = wp_insert_term('Child Category', 'test_taxonomy', [
            'description' => 'Categoría hija para testing',
            'parent' => $parent_term['term_id']
        ]);
        
        $this->assertIsArray($child_term, "Debería crear el término hijo correctamente");
        
        // READ - Verificar términos
        $parent_term_obj = get_term($parent_term['term_id'], 'test_taxonomy');
        $this->assertNotNull($parent_term_obj, "El término padre debería existir");
        $this->assertEquals('Parent Category', $parent_term_obj->name);
        $this->assertEquals(0, $parent_term_obj->parent);
        
        $child_term_obj = get_term($child_term['term_id'], 'test_taxonomy');
        $this->assertEquals('Child Category', $child_term_obj->name);
        $this->assertEquals($parent_term['term_id'], $child_term_obj->parent);
        
        // CREATE post y asignar términos
        $test_post_id = $this->create_test_post([
            'post_title' => 'Post with Custom Taxonomy',
            'post_status' => 'publish'
        ]);
        
        $term_assignment = wp_set_post_terms($test_post_id, [$child_term['term_id']], 'test_taxonomy');
        $this->assertNotFalse($term_assignment, "Debería asignar el término al post");
        
        // READ - Verificar asignación de términos
        $post_terms = wp_get_post_terms($test_post_id, 'test_taxonomy');
        $this->assertNotEmpty($post_terms, "El post debería tener términos asignados");
        $this->assertEquals($child_term['term_id'], $post_terms[0]->term_id);
        
        // UPDATE - Actualizar término
        $update_term_result = wp_update_term($parent_term['term_id'], 'test_taxonomy', [
            'name' => 'Updated Parent Category',
            'description' => 'Descripción actualizada'
        ]);
        
        $this->assertIsArray($update_term_result, "La actualización del término debería ser exitosa");
        
        $updated_term = get_term($parent_term['term_id'], 'test_taxonomy');
        $this->assertEquals('Updated Parent Category', $updated_term->name);
        $this->assertEquals('Descripción actualizada', $updated_term->description);
        
        // DELETE - Eliminar términos y post
        wp_delete_post($test_post_id, true);
        
        $delete_child = wp_delete_term($child_term['term_id'], 'test_taxonomy');
        $this->assertTrue($delete_child, "Debería eliminar el término hijo");
        
        $delete_parent = wp_delete_term($parent_term['term_id'], 'test_taxonomy');
        $this->assertTrue($delete_parent, "Debería eliminar el término padre");
        
        // Verificar eliminación
        $deleted_parent = get_term($parent_term['term_id'], 'test_taxonomy');
        $this->assertNull($deleted_parent, "El término padre no debería existir");
        
        $deleted_child = get_term($child_term['term_id'], 'test_taxonomy');
        $this->assertNull($deleted_child, "El término hijo no debería existir");
    }

    /**
     * Test: Operaciones batch y transacciones
     */
    public function test_batch_operations() {
        global $wpdb;
        
        // Test operaciones batch con múltiples posts
        $batch_size = 10;
        $post_ids = [];
        
        // CREATE batch
        $start_time = microtime(true);
        for ($i = 0; $i < $batch_size; $i++) {
            $post_id = $this->create_test_post([
                'post_title' => "Batch Post {$i}",
                'post_content' => "Contenido del post batch número {$i}",
                'post_status' => 'publish'
            ]);
            $post_ids[] = $post_id;
            
            // Agregar metadatos
            add_post_meta($post_id, 'batch_index', $i);
            add_post_meta($post_id, 'batch_timestamp', current_time('mysql'));
        }
        $batch_create_time = microtime(true) - $start_time;
        
        $this->assertCount($batch_size, $post_ids, "Debería crear todos los posts del batch");
        $this->assertLessThan(2.0, $batch_create_time, "El batch de creación debería completarse en menos de 2 segundos");
        
        // READ batch - Verificar todos los posts
        $created_posts = get_posts([
            'post__in' => $post_ids,
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $this->assertCount($batch_size, $created_posts, "Debería encontrar todos los posts creados");
        
        // UPDATE batch
        $start_time = microtime(true);
        foreach ($post_ids as $index => $post_id) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => "Updated Batch Post {$index}",
                'post_status' => 'draft'
            ]);
            
            update_post_meta($post_id, 'batch_updated', true);
        }
        $batch_update_time = microtime(true) - $start_time;
        
        $this->assertLessThan(2.0, $batch_update_time, "El batch de actualización debería completarse en menos de 2 segundos");
        
        // Verificar actualizaciones
        foreach ($post_ids as $index => $post_id) {
            $updated_post = get_post($post_id);
            $this->assertEquals("Updated Batch Post {$index}", $updated_post->post_title);
            $this->assertEquals('draft', $updated_post->post_status);
            $this->assertTrue((bool) get_post_meta($post_id, 'batch_updated', true));
        }
        
        // DELETE batch
        $start_time = microtime(true);
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
        $batch_delete_time = microtime(true) - $start_time;
        
        $this->assertLessThan(2.0, $batch_delete_time, "El batch de eliminación debería completarse en menos de 2 segundos");
        
        // Verificar eliminación
        $remaining_posts = get_posts([
            'post__in' => $post_ids,
            'post_status' => 'any',
            'numberposts' => -1
        ]);
        
        $this->assertEmpty($remaining_posts, "No debería quedar ningún post después de la eliminación batch");
    }

    /**
     * Test: Operaciones con consultas SQL directas
     */
    public function test_direct_sql_operations() {
        global $wpdb;
        
        // CREATE con SQL directo
        $test_posts_data = [
            ['Post SQL 1', 'Contenido SQL 1'],
            ['Post SQL 2', 'Contenido SQL 2'],
            ['Post SQL 3', 'Contenido SQL 3']
        ];
        
        $insert_sql = "INSERT INTO {$wpdb->posts} (post_title, post_content, post_status, post_type, post_date, post_date_gmt) VALUES ";
        $values = [];
        $current_time = current_time('mysql');
        
        foreach ($test_posts_data as $data) {
            $values[] = $wpdb->prepare("(%s, %s, 'publish', 'post', %s, %s)", 
                $data[0], $data[1], $current_time, $current_time);
        }
        
        $insert_sql .= implode(', ', $values);
        $insert_result = $wpdb->query($insert_sql);
        
        $this->assertEquals(3, $insert_result, "Debería insertar 3 posts con SQL directo");
        
        // READ con SQL directo
        $select_sql = "SELECT ID, post_title, post_content FROM {$wpdb->posts} 
                       WHERE post_title LIKE 'Post SQL%' AND post_status = 'publish'";
        $selected_posts = $wpdb->get_results($select_sql);
        
        $this->assertCount(3, $selected_posts, "Debería encontrar 3 posts con SQL directo");
        
        foreach ($selected_posts as $post) {
            $this->assertStringContainsString('Post SQL', $post->post_title);
            $this->assertStringContainsString('Contenido SQL', $post->post_content);
        }
        
        // UPDATE con SQL directo
        $update_sql = $wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_content = %s WHERE post_title LIKE 'Post SQL%'",
            'Contenido actualizado con SQL directo'
        );
        $update_result = $wpdb->query($update_sql);
        
        $this->assertEquals(3, $update_result, "Debería actualizar 3 posts con SQL directo");
        
        // Verificar actualización
        $updated_posts = $wpdb->get_results($select_sql);
        foreach ($updated_posts as $post) {
            $this->assertEquals('Contenido actualizado con SQL directo', $post->post_content);
        }
        
        // DELETE con SQL directo
        $delete_sql = "DELETE FROM {$wpdb->posts} WHERE post_title LIKE 'Post SQL%'";
        $delete_result = $wpdb->query($delete_sql);
        
        $this->assertEquals(3, $delete_result, "Debería eliminar 3 posts con SQL directo");
        
        // Verificar eliminación
        $remaining_posts = $wpdb->get_results($select_sql);
        $this->assertEmpty($remaining_posts, "No debería quedar ningún post después de eliminar con SQL directo");
    }
}
