<?php
/**
 * Test simple para verificar que PHPUnit funciona
 * Dev-Tools Arquitectura 3.0
 */

class SimpleCheckTest extends WP_UnitTestCase {

    /**
     * Test básico para verificar que WordPress Test Suite está funcionando
     */
    public function test_wordpress_is_loaded() {
        // Verificar que WordPress está cargado
        $this->assertTrue(function_exists('wp_head'), 'WordPress functions should be available');
        
        // Verificar que estamos en entorno de testing
        $this->assertTrue(defined('WP_TESTS_DOMAIN'), 'Should be in testing environment');
        
        // Verificar que tests_add_filter está disponible
        $this->assertTrue(function_exists('tests_add_filter'), 'tests_add_filter() should be available in test environment');
        
        echo "\n✅ WordPress Test Suite is working correctly!\n";
        echo "✅ tests_add_filter() function is available\n";
    }

    /**
     * Test para verificar la configuración de base de datos
     */
    public function test_database_config() {
        global $wpdb;
        
        // Verificar que tenemos conexión a la base de datos
        $this->assertNotNull($wpdb, 'WordPress database object should exist');
        
        // Verificar el prefijo de tablas de test
        $this->assertEquals('test_', $wpdb->prefix, 'Database prefix should be "test_"');
        
        // Verificar que podemos hacer una consulta simple
        $result = $wpdb->get_var("SELECT 1");
        $this->assertEquals('1', $result, 'Should be able to execute simple database query');
        
        echo "✅ Database configuration is correct\n";
        echo "✅ Table prefix: {$wpdb->prefix}\n";
        echo "✅ Database connection: working\n";
    }
}
