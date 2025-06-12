<?php
/**
 * Test básico de conexión a base de datos
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 */

class DatabaseTest extends DevToolsTestCase {

    /**
     * Test que verifica la conexión a la base de datos
     */
    public function test_database_connection() {
        global $wpdb;
        
        // Verificar que la conexión existe
        $this->assertInstanceOf( 'wpdb', $wpdb );
        
        // Verificar que podemos hacer una consulta básica
        $result = $wpdb->get_var( "SELECT 1" );
        $this->assertEquals( 1, $result );
    }

    /**
     * Test que verifica el prefijo de tablas de testing
     */
    public function test_table_prefix() {
        global $wpdb;
        
        // Verificar que estamos usando el prefijo de testing
        $this->assertEquals( 'wptests_', $wpdb->prefix );
    }

    /**
     * Test que verifica que las tablas de WordPress están disponibles
     */
    public function test_wordpress_tables_exist() {
        global $wpdb;
        
        // Verificar tablas básicas de WordPress
        $tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->options
        ];
        
        foreach ( $tables as $table ) {
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
            $this->assertEquals( $table, $exists, "La tabla {$table} debería existir" );
        }
    }

    /**
     * Test que verifica que podemos crear y usar datos de testing
     */
    public function test_create_test_data() {
        // Crear un post de prueba
        $post_id = static::factory()->post->create( [
            'post_title' => 'Test Post for Dev-Tools',
            'post_content' => 'Test content',
            'post_status' => 'publish'
        ] );
        
        $this->assertIsInt( $post_id );
        $this->assertGreaterThan( 0, $post_id );
        
        // Verificar que el post se creó correctamente
        $post = get_post( $post_id );
        $this->assertEquals( 'Test Post for Dev-Tools', $post->post_title );
    }

    /**
     * Test que verifica el aislamiento de datos de testing
     */
    public function test_data_isolation() {
        global $wpdb;
        
        // Verificar que no hay datos de producción mezclados
        $production_tables = $wpdb->get_results( 
            "SHOW TABLES LIKE '{$wpdb->base_prefix}%'" 
        );
        
        $test_tables = $wpdb->get_results( 
            "SHOW TABLES LIKE 'wptests_%'" 
        );
        
        // Debería haber tablas de testing
        $this->assertNotEmpty( $test_tables );
        
        // Verificar que las tablas de testing están separadas
        foreach ( $test_tables as $table ) {
            $table_name = array_values( (array) $table )[0];
            $this->assertStringStartsWith( 'wptests_', $table_name );
        }
    }
}
