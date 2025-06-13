<?php
/**
 * Tests de Esquema de Base de Datos - Dev-Tools Arquitectura 3.0
 * 
 * Tests específicos para validar el esquema y estructura de la base de datos
 * 
 * @package DevTools
 * @subpackage Tests\Database
 */

namespace DevTools\Tests\Database;

require_once dirname(__DIR__) . '/includes/TestCase.php';

class DatabaseSchemaTest extends \DevToolsTestCase {

    /**
     * Test: Verificar estructura de tablas WordPress core
     */
    public function test_wordpress_core_tables_structure() {
        global $wpdb;
        
        $core_tables = [
            'posts',
            'postmeta',
            'users',
            'usermeta',
            'options',
            'comments',
            'commentmeta',
            'termmeta',
            'terms',
            'term_relationships',
            'term_taxonomy'
        ];
        
        foreach ($core_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            
            // Verificar que la tabla existe
            $table_exists = $wpdb->get_var(
                $wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name)
            );
            $this->assertEquals($full_table_name, $table_exists, "La tabla {$table} debería existir");
            
            // Verificar que tiene columnas
            $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");
            $this->assertNotEmpty($columns, "La tabla {$table} debería tener columnas definidas");
            $this->assertGreaterThan(0, count($columns), "La tabla {$table} debería tener al menos una columna");
        }
    }

    /**
     * Test: Verificar índices y claves primarias
     */
    public function test_database_indexes_and_keys() {
        global $wpdb;
        
        // Test tabla posts - clave primaria
        $posts_indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->posts}");
        $has_primary_key = false;
        $has_post_name_index = false;
        
        foreach ($posts_indexes as $index) {
            if ($index->Key_name === 'PRIMARY') {
                $has_primary_key = true;
            }
            if ($index->Key_name === 'post_name') {
                $has_post_name_index = true;
            }
        }
        
        $this->assertTrue($has_primary_key, "La tabla posts debería tener clave primaria");
        $this->assertTrue($has_post_name_index, "La tabla posts debería tener índice en post_name");
        
        // Test tabla users - clave primaria y índices
        $users_indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->users}");
        $has_user_login_index = false;
        $has_user_email_index = false;
        
        foreach ($users_indexes as $index) {
            if ($index->Key_name === 'user_login_key') {
                $has_user_login_index = true;
            }
            if ($index->Key_name === 'user_email') {
                $has_user_email_index = true;
            }
        }
        
        $this->assertTrue($has_user_login_index, "La tabla users debería tener índice en user_login");
        $this->assertTrue($has_user_email_index, "La tabla users debería tener índice en user_email");
    }

    /**
     * Test: Verificar charset y collation de las tablas
     */
    public function test_database_charset_and_collation() {
        global $wpdb;
        
        $table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->posts}'");
        $this->assertNotEmpty($table_status, "Debería poder obtener el status de la tabla posts");
        
        $posts_table = $table_status[0];
        
        // Verificar charset (debería ser utf8mb4 en WordPress moderno)
        $this->assertNotEmpty($posts_table->Collation, "La tabla posts debería tener collation definida");
        $this->assertStringContainsString('utf8', $posts_table->Collation, "La tabla posts debería usar charset UTF8");
        
        // Verificar que no hay problemas de encoding
        $this->assertNotNull($posts_table->Engine, "La tabla posts debería tener engine definido");
        $this->assertNotEmpty($posts_table->Engine, "El engine de la tabla posts no debería estar vacío");
    }

    /**
     * Test: Verificar capacidades de transacciones
     */
    public function test_database_transaction_support() {
        global $wpdb;
        
        // Verificar que el engine soporta transacciones (InnoDB)
        $table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->posts}'");
        $posts_table = $table_status[0];
        
        // InnoDB soporta transacciones
        $transaction_engines = ['InnoDB'];
        $this->assertContains($posts_table->Engine, $transaction_engines, 
            "La tabla posts debería usar un engine que soporte transacciones");
        
        // Test básico de transacción usando SQL directo (WordPress cache puede interferir)
        $wpdb->query('START TRANSACTION');
        
        // Insertar un registro directamente con SQL
        $test_table = $wpdb->prefix . 'options';
        $option_name = 'dev_tools_transaction_test_' . uniqid();
        $option_value = 'test_value_' . time();
        
        $result = $wpdb->insert(
            $test_table,
            array(
                'option_name' => $option_name,
                'option_value' => $option_value,
                'autoload' => 'no'
            ),
            array('%s', '%s', '%s')
        );
        
        $this->assertNotFalse($result, "Debería poder insertar en transacción");
        
        // Verificar que el registro existe
        $exists_before = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$test_table} WHERE option_name = %s",
            $option_name
        ));
        $this->assertEquals($option_value, $exists_before, "El registro debería existir antes del rollback");
        
        // Hacer rollback
        $wpdb->query('ROLLBACK');
        
        // Verificar que el registro ya no existe después del rollback
        $exists_after = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$test_table} WHERE option_name = %s",
            $option_name
        ));
        $this->assertNull($exists_after, "El registro no debería existir después del rollback");
    }

    /**
     * Test: Verificar limitaciones y configuración de MySQL
     */
    public function test_mysql_configuration_limits() {
        global $wpdb;
        
        // Verificar versión de MySQL
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        $this->assertNotEmpty($mysql_version, "Debería poder obtener la versión de MySQL");
        $this->assertIsString($mysql_version, "La versión de MySQL debería ser string");
        
        // Verificar variables importantes de configuración
        $important_vars = [
            'max_connections',
            'max_allowed_packet',
            'innodb_buffer_pool_size',
            'query_cache_size'
        ];
        
        foreach ($important_vars as $var) {
            $value = $wpdb->get_var($wpdb->prepare("SHOW VARIABLES LIKE %s", $var));
            // No todos los MySQL tienen todas las variables, pero debería ejecutarse sin error
            $this->assertNotNull($wpdb->last_error === '', "No debería haber error al consultar variable {$var}");
        }
        
        // Verificar max_allowed_packet (importante para uploads grandes)
        $max_packet = $wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'");
        if ($max_packet) {
            $packet_value = $wpdb->get_var("SELECT @@max_allowed_packet");
            $this->assertGreaterThan(1024, $packet_value, "max_allowed_packet debería ser mayor a 1KB");
        }
    }

    /**
     * Test: Verificar capacidades de búsqueda full-text
     */
    public function test_fulltext_search_capabilities() {
        global $wpdb;
        
        // Crear algunos posts de prueba con contenido específico
        $post_ids = [];
        $test_content = [
            'Este es un post sobre desarrollo web con WordPress',
            'Tutorial de PHP y MySQL para principiantes',
            'DevTools arquitectura avanzada para testing'
        ];
        
        foreach ($test_content as $content) {
            $post_ids[] = $this->create_test_post([
                'post_content' => $content,
                'post_status' => 'publish'
            ]);
        }
        
        // Test búsqueda LIKE básica
        $like_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status = 'publish'",
                '%WordPress%'
            )
        );
        
        $this->assertNotEmpty($like_results, "Debería encontrar posts con búsqueda LIKE");
        $this->assertGreaterThanOrEqual(1, count($like_results), "Debería encontrar al menos 1 post");
        
        // Test búsqueda con múltiples términos
        $multi_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE (post_content LIKE %s OR post_content LIKE %s) AND post_status = 'publish'",
                '%PHP%',
                '%MySQL%'
            )
        );
        
        $this->assertNotEmpty($multi_results, "Debería encontrar posts con búsqueda múltiple");
        
        // Limpiar posts de prueba
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    /**
     * Test: Verificar integridad referencial básica
     */
    public function test_referential_integrity() {
        global $wpdb;
        
        // Crear un post y verificar relaciones
        $test_post_id = $this->create_test_post([
            'post_title' => 'Test Referential Integrity',
            'post_status' => 'publish'
        ]);
        
        // Agregar metadatos al post
        add_post_meta($test_post_id, 'test_meta_key', 'test_meta_value');
        
        // Verificar que el metadato existe
        $meta_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                $test_post_id,
                'test_meta_key'
            )
        );
        
        $this->assertEquals(1, $meta_exists, "Debería existir el metadato del post");
        
        // Eliminar el post
        wp_delete_post($test_post_id, true);
        
        // Verificar que los metadatos también se eliminaron (integridad referencial)
        $meta_after_delete = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d",
                $test_post_id
            )
        );
        
        $this->assertEquals(0, $meta_after_delete, "Los metadatos deberían eliminarse con el post");
    }

    /**
     * Test: Verificar performance de consultas básicas
     */
    public function test_query_performance() {
        global $wpdb;
        
        // Crear múltiples posts para testing de performance
        $post_ids = [];
        for ($i = 0; $i < 20; $i++) {
            $post_ids[] = $this->create_test_post([
                'post_title' => "Performance Test Post {$i}",
                'post_content' => "Content for performance testing post number {$i}",
                'post_status' => 'publish'
            ]);
        }
        
        // Test performance de consulta simple
        $start_time = microtime(true);
        $simple_query = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status = 'publish' LIMIT 10"
        );
        $simple_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.1, $simple_time, "Consulta simple debería ejecutarse en menos de 100ms");
        $this->assertNotEmpty($simple_query, "Consulta simple debería devolver resultados");
        
        // Test performance de consulta con JOIN
        $start_time = microtime(true);
        $join_query = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value 
             FROM {$wpdb->posts} p 
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_status = 'publish' 
             LIMIT 10"
        );
        $join_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.2, $join_time, "Consulta con JOIN debería ejecutarse en menos de 200ms");
        
        // Limpiar posts de prueba
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
    }
}
