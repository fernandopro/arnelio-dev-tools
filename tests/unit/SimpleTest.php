<?php
/**
 * Test Simple - Verificación básica de PHPUnit
 * 
 * Test básico para verificar que el entorno de testing funciona correctamente
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Define constants for testing
if (!defined('DEV_TOOLS_PLUGIN_DIR')) {
    define('DEV_TOOLS_PLUGIN_DIR', dirname(__DIR__, 2));
}
if (!defined('DEV_TOOLS_TESTS_DIR')) {
    define('DEV_TOOLS_TESTS_DIR', dirname(__DIR__));
}
if (!defined('DEV_TOOLS_PLUGIN_FILE')) {
    define('DEV_TOOLS_PLUGIN_FILE', DEV_TOOLS_PLUGIN_DIR . '/loader.php');
}

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase {
    
    /**
     * @group Quick
     * Test básico de PHPUnit
     */
    public function test_phpunit_basic_functionality() {
        // Test de assertions básicas
        $this->assertTrue(true, 'True should be true');
        $this->assertFalse(false, 'False should be false');
        $this->assertEquals(1, 1, 'One should equal one');
        $this->assertNotEquals(1, 2, 'One should not equal two');
    }
    
    /**
     * @group Quick
     * Test de tipos de datos
     */
    public function test_data_types() {
        $string = 'Hello World';
        $number = 42;
        $array = ['a', 'b', 'c'];
        $object = new stdClass();
        
        $this->assertIsString($string, 'Should be a string');
        $this->assertIsInt($number, 'Should be an integer');
        $this->assertIsArray($array, 'Should be an array');
        $this->assertIsObject($object, 'Should be an object');
        
        $this->assertStringContainsString('World', $string, 'String should contain "World"');
        $this->assertArrayHasKey(0, $array, 'Array should have key 0');
        $this->assertCount(3, $array, 'Array should have 3 elements');
    }
    
    /**
     * @group Quick
     * Test de constantes de Dev-Tools
     */
    public function test_dev_tools_constants() {
        // Verificar que las constantes están definidas
        $this->assertTrue(defined('DEV_TOOLS_PLUGIN_DIR'), 'DEV_TOOLS_PLUGIN_DIR should be defined');
        $this->assertTrue(defined('DEV_TOOLS_TESTS_DIR'), 'DEV_TOOLS_TESTS_DIR should be defined');
        $this->assertTrue(defined('DEV_TOOLS_PLUGIN_FILE'), 'DEV_TOOLS_PLUGIN_FILE should be defined');
        
        // Verificar que las rutas existen
        $this->assertDirectoryExists(DEV_TOOLS_PLUGIN_DIR, 'Plugin directory should exist');
        $this->assertDirectoryExists(DEV_TOOLS_TESTS_DIR, 'Tests directory should exist');
        $this->assertFileExists(DEV_TOOLS_PLUGIN_FILE, 'Plugin file should exist');
    }
    
    /**
     * @group Quick
     * Test de entorno WordPress (si está disponible)
     */
    public function test_wordpress_environment() {
        if (function_exists('wp_get_environment_type')) {
            $env_type = wp_get_environment_type();
            $this->assertIsString($env_type, 'Environment type should be a string');
            $this->assertNotEmpty($env_type, 'Environment type should not be empty');
        } else {
            $this->markTestSkipped('WordPress functions not available');
        }
    }
    
    /**
     * @group Quick
     * Test de PHP version
     */
    public function test_php_version() {
        $php_version = PHP_VERSION;
        
        $this->assertIsString($php_version, 'PHP version should be a string');
        $this->assertTrue(version_compare($php_version, '7.4', '>='), 'PHP version should be 7.4 or higher');
    }
    
    /**
     * @group Quick
     * Test de carga de archivos del sistema
     */
    public function test_system_files_exist() {
        $required_files = [
            DEV_TOOLS_PLUGIN_DIR . '/loader.php',
            DEV_TOOLS_PLUGIN_DIR . '/config/config.php',
            DEV_TOOLS_TESTS_DIR . '/includes/class-dev-tools-test-case.php',
            DEV_TOOLS_TESTS_DIR . '/includes/test-helpers.php'
        ];
        
        foreach ($required_files as $file) {
            $this->assertFileExists($file, "Required file should exist: {$file}");
        }
    }
    
    /**
     * @group Quick
     * Test de creación de directorios temporales
     */
    public function test_temp_directory_creation() {
        $temp_dir = DEV_TOOLS_TESTS_DIR . '/temp';
        
        // Crear directorio temporal
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $this->assertDirectoryExists($temp_dir, 'Temp directory should be created');
        $this->assertTrue(is_writable($temp_dir), 'Temp directory should be writable');
        
        // Limpiar
        if (is_dir($temp_dir)) {
            rmdir($temp_dir);
        }
    }
    
    /**
     * @group Quick
     * Test de memoria y performance básica
     */
    public function test_basic_performance() {
        $start_memory = memory_get_usage();
        $start_time = microtime(true);
        
        // Operación simple
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = $i;
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = ($end_time - $start_time) * 1000; // En milisegundos
        $memory_used = $end_memory - $start_memory;
        
        $this->assertLessThan(100, $execution_time, 'Simple operation should take less than 100ms');
        $this->assertLessThan(1024 * 1024, $memory_used, 'Simple operation should use less than 1MB');
        $this->assertCount(1000, $data, 'Array should contain 1000 elements');
    }
}
