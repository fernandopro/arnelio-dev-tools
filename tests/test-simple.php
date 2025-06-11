<?php
/**
 * Test muy básico para verificar que PHPUnit funciona
 */

class SimpleTest extends PHPUnit\Framework\TestCase {
    
    public function test_basic_assertion() {
        echo "🧪 Test básico ejecutándose...\n";
        $this->assertTrue(true, 'This should always pass');
        echo "✅ Test básico completado\n";
    }
    
    public function test_database_constants() {
        echo "🔧 Verificando constantes de base de datos...\n";
        $this->assertTrue(defined('DB_NAME'), 'DB_NAME should be defined');
        $this->assertTrue(defined('DB_HOST'), 'DB_HOST should be defined');
        $this->assertTrue(defined('DB_USER'), 'DB_USER should be defined');
        
        echo "📊 DB_NAME: " . DB_NAME . "\n";
        echo "📊 DB_HOST: " . DB_HOST . "\n";
        echo "📊 DB_USER: " . DB_USER . "\n";
        
        // Verificar prefijo de tabla
        global $table_prefix;
        if (isset($table_prefix)) {
            echo "📊 Table Prefix: " . $table_prefix . "\n";
            $this->assertEquals('test_', $table_prefix, 'Table prefix should be test_');
        }
        
        echo "✅ Constantes verificadas\n";
    }
}
