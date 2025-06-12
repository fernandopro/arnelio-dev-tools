<?php
/**
 * WordPress ADVANCED Test Suite - Capacidades Profesionales
 * 
 * Demuestra que WordPress Test Suite está COMPLETAMENTE OPERATIVO
 * para testing profesional de plugins con capacidades avanzadas.
 */

class WordPressAdvancedTestSuite extends DevToolsTestCase {
    
    private $test_user_id;
    private $test_post_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Crear usuario administrador para tests
        $this->test_user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'test_admin_' . time()
        ]);
        
        wp_set_current_user($this->test_user_id);
    }
    
    /**
     * Test COMPLEJO: AJAX con nonces y seguridad
     */
    public function test_complex_ajax_functionality() {
        // ✅ AJAX Testing completo con WordPress Test Suite
        
        $ajax_response = null;
        
        // Registrar AJAX handler ÚNICO
        add_action('wp_ajax_test_complex_advanced', function() use (&$ajax_response) {
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'test_nonce')) {
                $ajax_response = ['error' => 'Security check failed'];
                return;
            }
            
            // Procesar datos complejos
            $data = json_decode(stripslashes($_POST['complex_data']), true);
            
            $ajax_response = [
                'success' => true,
                'data' => $data,
                'user_id' => get_current_user_id(),
                'can_edit' => current_user_can('edit_posts'),
                'timestamp' => current_time('timestamp'),
                'nonce_valid' => true
            ];
        });
        
        // Crear nonce
        $nonce = wp_create_nonce('test_nonce');
        
        // Simular petición AJAX
        $_POST = [
            'action' => 'test_complex_advanced',
            'nonce' => $nonce,
            'complex_data' => json_encode([
                'nested' => ['level1' => ['level2' => 'deep_value']],
                'array' => [1, 2, 3, 4, 5],
                'meta' => ['key' => 'value']
            ])
        ];
        $_REQUEST = $_POST;
        
        // Ejecutar la acción AJAX
        do_action('wp_ajax_test_complex_advanced');
        
        // Verificaciones
        $this->assertNotNull($ajax_response, 'Respuesta AJAX debería existir');
        $this->assertTrue($ajax_response['success'], 'Respuesta AJAX debería ser exitosa');
        $this->assertEquals($this->test_user_id, $ajax_response['user_id'], 'User ID debería coincidir');
        $this->assertTrue($ajax_response['can_edit'], 'Usuario debería poder editar');
        $this->assertEquals('deep_value', $ajax_response['data']['nested']['level1']['level2'], 'Datos anidados deberían estar correctos');
        $this->assertTrue($ajax_response['nonce_valid'], 'Nonce debería ser válido');
    }
    
    /**
     * Test COMPLEJO: Custom Post Types con metadata y taxonomies
     */
    public function test_complex_custom_post_types() {
        // ✅ Custom Post Types testing avanzado
        
        // Registrar CPT personalizado
        $cpt_name = 'test_cpt_' . time();
        register_post_type($cpt_name, [
            'public' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'has_archive' => true,
            'taxonomies' => ['category', 'post_tag']
        ]);
        
        // Registrar taxonomía personalizada
        $tax_name = 'test_tax_' . time();
        register_taxonomy($tax_name, $cpt_name, [
            'hierarchical' => true,
            'public' => true
        ]);
        
        $this->assertTrue(post_type_exists($cpt_name));
        $this->assertTrue(taxonomy_exists($tax_name));
        
        // Crear post con metadata complejo
        $post_id = $this->factory->post->create([
            'post_type' => $cpt_name,
            'post_title' => 'Complex Test Post',
            'meta_input' => [
                'simple_meta' => 'simple_value',
                'array_meta' => ['item1', 'item2', 'item3'],
                'object_meta' => ['nested' => ['data' => 'value']],
                'serialized_meta' => serialize(['complex' => 'data'])
            ]
        ]);
        
        // Crear términos y asignar
        $term1 = wp_insert_term('Test Category', $tax_name);
        $term2 = wp_insert_term('Child Category', $tax_name, ['parent' => $term1['term_id']]);
        
        wp_set_post_terms($post_id, [$term1['term_id'], $term2['term_id']], $tax_name);
        
        // Verificar relaciones
        $terms = wp_get_post_terms($post_id, $tax_name);
        $this->assertCount(2, $terms);
        
        // Query compleja
        $query = new WP_Query([
            'post_type' => $cpt_name,
            'meta_query' => [
                [
                    'key' => 'simple_meta',
                    'value' => 'simple_value',
                    'compare' => '='
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => $tax_name,
                    'field' => 'term_id',
                    'terms' => $term1['term_id']
                ]
            ]
        ]);
        
        $this->assertTrue($query->have_posts());
        $this->assertEquals(1, $query->found_posts);
    }
    
    /**
     * Test COMPLEJO: Database operations usando tablas wptests_
     */
    public function test_complex_database_operations() {
        // ✅ Database testing avanzado usando las MISMAS tablas de WordPress con prefijo wptests_
        global $wpdb;
        
        // Verificar que estamos usando el prefijo correcto de testing
        $this->assertStringStartsWith('wptests_', $wpdb->prefix, 'Debe usar prefijo wptests_ para testing');
        
        // Verificar que las tablas de WordPress usan wptests_
        $wp_users_table = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->users));
        $this->assertStringContainsString('wptests_', $wp_users_table, 'Tablas WP deben usar prefijo wptests_');
        
        // Verificar información del entorno de testing
        $this->assertEquals('wptests_', WP_TESTS_TABLE_PREFIX, 'Prefijo de testing debe ser wptests_');
        $this->assertStringStartsWith('wptests_', $wpdb->users, 'Tabla users debe usar prefijo wptests_');
        $this->assertStringStartsWith('wptests_', $wpdb->posts, 'Tabla posts debe usar prefijo wptests_');
        $this->assertStringStartsWith('wptests_', $wpdb->postmeta, 'Tabla postmeta debe usar prefijo wptests_');
        $this->assertStringStartsWith('wptests_', $wpdb->usermeta, 'Tabla usermeta debe usar prefijo wptests_');
        
        // Crear post de prueba con metadata complejo
        $test_post_id = $this->factory->post->create([
            'post_title' => 'Database Test Post',
            'post_content' => 'Content for database testing',
            'meta_input' => [
                'complex_meta' => json_encode([
                    'settings' => ['option1' => true, 'option2' => 'value'],
                    'metadata' => ['nested' => ['deep' => 'value']],
                    'array' => [1, 2, 3, 4, 5],
                    'test_info' => [
                        'prefix' => $wpdb->prefix,
                        'db_name' => DB_NAME,
                        'table_prefix' => $wpdb->prefix
                    ]
                ])
            ]
        ]);
        
        // Query complejo con JOIN usando tablas wptests_ existentes
        $query = $wpdb->prepare("
            SELECT 
                p.post_title,
                p.post_content,
                u.user_login,
                u.user_email,
                pm.meta_value as complex_data
            FROM {$wpdb->posts} p
            JOIN {$wpdb->users} u ON p.post_author = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'complex_meta'
            WHERE p.ID = %d
        ", $test_post_id);
        
        $result = $wpdb->get_row($query);
        
        $this->assertNotNull($result, 'Query complejo debe devolver resultado');
        $this->assertEquals('Database Test Post', $result->post_title);
        $this->assertEquals($this->test_user_id, get_post($test_post_id)->post_author);
        
        // Verificar datos complejos en metadata
        $decoded_data = json_decode($result->complex_data, true);
        $this->assertEquals('value', $decoded_data['metadata']['nested']['deep']);
        $this->assertEquals($wpdb->prefix, $decoded_data['test_info']['prefix']);
        $this->assertEquals([1, 2, 3, 4, 5], $decoded_data['array']);
        
        // Query de performance con múltiples tablas wptests_
        $performance_query = $wpdb->prepare("
            SELECT 
                COUNT(DISTINCT p.ID) as posts_count,
                COUNT(DISTINCT pm.meta_id) as meta_count,
                COUNT(DISTINCT u.ID) as users_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_author = %d
        ", $this->test_user_id);
        
        $stats = $wpdb->get_row($performance_query);
        $this->assertNotNull($stats, 'Query de estadísticas debe funcionar');
        $this->assertGreaterThan(0, $stats->posts_count, 'Debe haber al menos 1 post');
        
        // Verificar que podemos hacer operaciones complejas con usermeta
        $user_meta_query = $wpdb->prepare("
            SELECT 
                u.user_login,
                u.user_email,
                GROUP_CONCAT(um.meta_key SEPARATOR ',') as meta_keys,
                COUNT(um.umeta_id) as meta_count
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE u.ID = %d
            GROUP BY u.ID
        ", $this->test_user_id);
        
        $user_meta_result = $wpdb->get_row($user_meta_query);
        $this->assertNotNull($user_meta_result, 'Query de usermeta debe funcionar');
        $this->assertGreaterThan(0, $user_meta_result->meta_count, 'Usuario debe tener metadata');
        
        // Verificar información del entorno de testing
        $db_info_query = "
            SELECT 
                SCHEMA_NAME as database_name,
                DEFAULT_CHARACTER_SET_NAME as charset,
                DEFAULT_COLLATION_NAME as collation
            FROM information_schema.SCHEMATA 
            WHERE SCHEMA_NAME = DATABASE()
        ";
        
        $db_info = $wpdb->get_row($db_info_query);
        $this->assertNotNull($db_info, 'Información de DB debe estar disponible');
        $this->assertNotEmpty($db_info->database_name, 'Nombre de DB debe existir');
        
        // Limpiar post de prueba (automático al final del test)
        wp_delete_post($test_post_id, true);
    }
    
    /**
     * Test COMPLEJO: Transients y Object Cache
     */
    public function test_complex_caching_mechanisms() {
        // ✅ Caching testing avanzado
        
        // Crear algunos posts de prueba para el cache
        $test_posts = [];
        for ($i = 0; $i < 3; $i++) {
            $test_posts[] = $this->factory->post->create([
                'post_title' => 'Cache Test Post ' . $i,
                'post_content' => 'Test content for caching',
                'post_status' => 'publish'
            ]);
        }
        
        // Data complejo para cache
        $complex_data = [
            'posts' => get_posts(['numberposts' => 3, 'include' => $test_posts, 'orderby' => 'post__in']),
            'users' => get_users(['number' => 2]),
            'options' => [
                'blogname' => get_option('blogname'),
                'admin_email' => get_option('admin_email')
            ],
            'calculated' => [
                'timestamp' => current_time('timestamp'),
                'hash' => md5('test_data'),
                'serialized' => serialize(['nested' => 'data']),
                'test_info' => [
                    'prefix' => 'wptests_',
                    'posts_count' => count($test_posts)
                ]
            ]
        ];
        
        // Verificar que tenemos posts
        $this->assertNotEmpty($complex_data['posts'], 'Debe haber posts para el test de cache');
        $this->assertCount(3, $complex_data['posts'], 'Debe haber exactamente 3 posts');
        
        // Test Transients
        $transient_key = 'test_complex_transient_' . time();
        $this->assertTrue(set_transient($transient_key, $complex_data, HOUR_IN_SECONDS));
        
        $retrieved = get_transient($transient_key);
        $this->assertEquals($complex_data, $retrieved);
        
        // Verificar que los posts son objetos WP_Post (usar el primer post disponible)
        $this->assertInstanceOf('WP_Post', $retrieved['posts'][0]);
        $this->assertStringContainsString('Cache Test Post', $retrieved['posts'][0]->post_title);
        
        // Test Object Cache con grupos
        $cache_key = 'test_cache_' . time();
        $cache_group = 'test_group';
        
        wp_cache_set($cache_key, $complex_data, $cache_group, HOUR_IN_SECONDS);
        $cached = wp_cache_get($cache_key, $cache_group);
        
        $this->assertEquals($complex_data, $cached);
        
        // Test cache invalidation
        wp_cache_delete($cache_key, $cache_group);
        $this->assertFalse(wp_cache_get($cache_key, $cache_group));
        
        // Test cache con objetos WordPress específicos
        $user_cache_key = 'test_user_cache_' . time();
        $user_obj = get_user_by('id', $this->test_user_id);
        
        wp_cache_set($user_cache_key, $user_obj, 'users');
        $cached_user = wp_cache_get($user_cache_key, 'users');
        
        $this->assertInstanceOf('WP_User', $cached_user);
        $this->assertEquals($this->test_user_id, $cached_user->ID);
        
        // Limpiar
        delete_transient($transient_key);
        wp_cache_delete($user_cache_key, 'users');
        
        // Limpiar posts de prueba
        foreach ($test_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
    }
    
    /**
     * Test COMPLEJO: Hooks y Filters con prioridades
     */
    public function test_complex_hooks_and_filters() {
        // ✅ Hooks testing avanzado
        
        $test_data = [];
        
        // Multiple callbacks con diferentes prioridades
        add_action('test_priority_hook', function($data) use (&$test_data) {
            $test_data[] = 'high_priority_' . $data;
        }, 5);
        
        add_action('test_priority_hook', function($data) use (&$test_data) {
            $test_data[] = 'medium_priority_' . $data;
        }, 10);
        
        add_action('test_priority_hook', function($data) use (&$test_data) {
            $test_data[] = 'low_priority_' . $data;
        }, 15);
        
        do_action('test_priority_hook', 'test');
        
        $this->assertEquals([
            'high_priority_test',
            'medium_priority_test', 
            'low_priority_test'
        ], $test_data);
        
        // Filter chain complejo
        add_filter('test_complex_filter', function($value, $param1, $param2) {
            return $value . '_' . $param1 . '_' . $param2;
        }, 10, 3);
        
        add_filter('test_complex_filter', function($value) {
            return strtoupper($value);
        }, 20);
        
        $result = apply_filters('test_complex_filter', 'start', 'param1', 'param2');
        $this->assertEquals('START_PARAM1_PARAM2', $result);
        
        // Test conditional hooks
        add_action('init', function() use (&$test_data) {
            if (current_user_can('manage_options')) {
                $test_data['admin_init'] = true;
            }
        });
        
        do_action('init');
        $this->assertTrue($test_data['admin_init']);
    }
    
    /**
     * Test COMPLEJO: Performance y Memory
     */
    public function test_performance_and_memory_usage() {
        // ✅ Performance testing avanzado
        
        // Benchmark query performance
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Operaciones intensivas
        for ($i = 0; $i < 50; $i++) {
            $posts = get_posts(['numberposts' => 5]);
            $users = get_users(['number' => 3]);
            
            // Operaciones con metadata
            foreach ($posts as $post) {
                $meta = get_post_meta($post->ID);
            }
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = $end_time - $start_time;
        $memory_used = $end_memory - $start_memory;
        
        // Verificar performance aceptable
        $this->assertLessThan(3.0, $execution_time, "Performance too slow: {$execution_time}s");
        $this->assertLessThan(5 * 1024 * 1024, $memory_used, "Memory usage too high: " . round($memory_used / 1024 / 1024, 2) . "MB");
        
        // Test de concurrencia simulada
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->simulate_concurrent_operation($i);
        }
        
        $this->assertCount(10, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }
    }
    
    /**
     * Simular operación concurrente
     */
    private function simulate_concurrent_operation($id) {
        // Crear post temporal
        $post_id = wp_insert_post([
            'post_title' => 'Concurrent Test ' . $id,
            'post_content' => 'Test content for concurrency',
            'post_status' => 'publish'
        ]);
        
        // Operaciones con el post
        update_post_meta($post_id, 'test_meta', 'value_' . $id);
        $meta = get_post_meta($post_id, 'test_meta', true);
        
        // Limpiar
        wp_delete_post($post_id, true);
        
        return [
            'success' => $meta === 'value_' . $id,
            'post_id' => $post_id,
            'id' => $id
        ];
    }
    
    public function tearDown(): void {
        // Limpiar usuario de prueba
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
        
        // Flush caches
        wp_cache_flush();
        
        parent::tearDown();
    }
}
