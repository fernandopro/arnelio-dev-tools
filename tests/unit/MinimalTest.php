<?php
/**
 * Test Mínimo - Sin dependencias de WordPress
 * 
 * Test básico para verificar que PHPUnit funciona
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class MinimalTest extends TestCase {
    
    /**
     * Test básico de PHPUnit
     */
    public function test_phpunit_works() {
        $this->assertTrue(true, 'True should be true');
        $this->assertEquals(1, 1, 'One should equal one');
        $this->assertIsString('hello', 'Should be a string');
    }
    
    /**
     * Test de PHP version
     */
    public function test_php_version() {
        $php_version = PHP_VERSION;
        $this->assertIsString($php_version);
        $this->assertTrue(version_compare($php_version, '7.4', '>='));
    }
    
    /**
     * Test de matemáticas básicas
     */
    public function test_basic_math() {
        $this->assertEquals(4, 2 + 2);
        $this->assertEquals(10, 5 * 2);
        $this->assertEquals(3, 9 / 3);
    }
    
    /**
     * Test de arrays
     */
    public function test_array_operations() {
        $array = ['a', 'b', 'c'];
        
        $this->assertIsArray($array);
        $this->assertCount(3, $array);
        $this->assertContains('b', $array);
        $this->assertEquals('a', $array[0]);
    }
}
