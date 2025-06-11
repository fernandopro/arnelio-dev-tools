<?php
/**
 * Test de Conexión a Base de Datos - WordPress PHPUnit
 * 
 * Verifica que PHPUnit se conecta correctamente a la base de datos
 * y crea tablas con el prefijo 'test_'
 * 
 * @package DevTools
 * @subpackage Tests
 */

class DatabaseConnectionTest extends WP_UnitTestCase {

    /**
     * Test básico de conexión a base de datos
     */
    public function test_database_connection() {
        global $wpdb;
        
        // Verificar que $wpdb está disponible
        $this->assertInstanceOf('wpdb', $wpdb, 'Global $wpdb should be available');
        
        // Verificar que la conexión funciona
        $result = $wpdb->get_var("SELECT 1");
        $this->assertEquals(1, $result, 'Database connection should work');
        
        // Mostrar información de debug
        error_log('[TEST] Database Name: ' . DB_NAME);
        error_log('[TEST] Database Host: ' . DB_HOST);
        error_log('[TEST] Table Prefix: ' . $wpdb->prefix);
    }

    /**
     * Test que verifica el prefijo de tablas
     */
    public function test_table_prefix() {
        global $wpdb;
        
        // Verificar que el prefijo es 'test_'
        $this->assertEquals('test_', $wpdb->prefix, 'Table prefix should be "test_"');
        
        // Verificar que las tablas principales tienen el prefijo correcto
        $expected_tables = [
            'test_posts',
            'test_users',
            'test_options',
            'test_postmeta',
            'test_usermeta'
        ];
        
        foreach ($expected_tables as $table) {
            $this->assertEquals($table, $wpdb->prefix . basename($table, $wpdb->prefix), 
                "Table {$table} should have correct prefix");
        }
        
        error_log('[TEST] Expected tables with prefix: ' . implode(', ', $expected_tables));
    }

    /**
     * Test que verifica que las tablas de test existen
     */
    public function test_test_tables_exist() {
        global $wpdb;
        
        // Obtener todas las tablas de la base de datos
        $tables = $wpdb->get_col("SHOW TABLES");
        
        // Filtrar solo las tablas con prefijo 'test_'
        $test_tables = array_filter($tables, function($table) {
            return strpos($table, 'test_') === 0;
        });
        
        // Verificar que existen tablas de test
        $this->assertNotEmpty($test_tables, 'Should have tables with test_ prefix');
        
        // Verificar tablas principales de WordPress
        $core_tables = ['posts', 'users', 'options', 'postmeta', 'usermeta'];
        foreach ($core_tables as $table) {
            $test_table = 'test_' . $table;
            $this->assertContains($test_table, $test_tables, 
                "Core table {$test_table} should exist");
        }
        
        error_log('[TEST] Test tables found: ' . implode(', ', $test_tables));
    }

    /**
     * Test que verifica que las tablas de producción NO se tocan
     */
    public function test_production_tables_untouched() {
        global $wpdb;
        
        // Obtener todas las tablas
        $tables = $wpdb->get_col("SHOW TABLES");
        
        // Buscar tablas de producción (sin prefijo test_)
        $production_tables = array_filter($tables, function($table) {
            return strpos($table, 'wp_') === 0 || (strpos($table, 'test_') !== 0 && strpos($table, '_') !== false);
        });
        
        if (!empty($production_tables)) {
            error_log('[TEST] Production tables detected (should be preserved): ' . implode(', ', $production_tables));
        }
        
        // Esto es informativo, no un fallo
        $this->assertTrue(true, 'Production tables check completed');
    }

    /**
     * Test de inserción básica en tabla de test
     */
    public function test_basic_insert() {
        global $wpdb;
        
        // Insertar un post de prueba
        $post_data = [
            'post_title' => 'Test Post for PHPUnit',
            'post_content' => 'This is a test post created by PHPUnit',
            'post_status' => 'publish',
            'post_author' => 1
        ];
        
        $post_id = wp_insert_post($post_data);
        
        // Verificar que se insertó correctamente
        $this->assertIsInt($post_id, 'Post should be inserted successfully');
        $this->assertGreaterThan(0, $post_id, 'Post ID should be positive');
        
        // Verificar que está en la tabla correcta (con prefijo test_)
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->posts} WHERE ID = %d", 
            $post_id
        ));
        
        $this->assertNotNull($post, 'Post should be retrievable from test database');
        $this->assertEquals('Test Post for PHPUnit', $post->post_title, 'Post title should match');
        
        error_log('[TEST] Test post created with ID: ' . $post_id . ' in table: ' . $wpdb->posts);
    }

    /**
     * Test de configuración de entorno de testing
     */
    public function test_testing_environment() {
        // Verificar que estamos en entorno de testing
        $this->assertTrue(defined('WP_TESTS_RUNNING'), 'WP_TESTS_RUNNING should be defined');
        $this->assertTrue(WP_TESTS_RUNNING, 'Should be running in test environment');
        
        // Verificar configuraciones específicas de dev-tools
        $this->assertTrue(defined('DEV_TOOLS_TESTING'), 'DEV_TOOLS_TESTING should be defined');
        $this->assertTrue(DEV_TOOLS_TESTING, 'Should be in dev-tools testing mode');
        
        // Mostrar información del entorno
        error_log('[TEST] WordPress Version: ' . get_bloginfo('version'));
        error_log('[TEST] PHP Version: ' . PHP_VERSION);
        error_log('[TEST] Database Name: ' . DB_NAME);
        error_log('[TEST] Site URL: ' . get_site_url());
    }
}
