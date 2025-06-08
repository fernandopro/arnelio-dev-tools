<?php
/**
 * Test Unitario: Dev Tools - Funcionalidades Básicas
 * 
 * Tests de lógica pura sin dependencias complejas de WordPress.
 * Ideal para: validaciones, algoritmos, utilidades básicas.
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 1.0.0
 */

class DevToolsBasicTest extends DevToolsTestCase {

    /**
     * Test: Verificar que PHPUnit funciona correctamente
     */
    public function test_phpunit_basic_functionality() {
        // Tests básicos de assertions
        $this->assertTrue(true, 'True debe ser true');
        $this->assertFalse(false, 'False debe ser false');
        $this->assertEquals(1, 1, 'Igualdad numérica');
        $this->assertNotEquals(1, 2, 'Desigualdad numérica');
    }

    /**
     * Test: Verificar operaciones matemáticas básicas
     */
    public function test_basic_math_operations() {
        // Suma
        $this->assertEquals(5, 2 + 3, 'Suma básica');
        
        // Multiplicación
        $this->assertEquals(15, 3 * 5, 'Multiplicación básica');
        
        // División
        $this->assertEquals(4, 12 / 3, 'División básica');
        
        // Módulo
        $this->assertEquals(1, 5 % 2, 'Operación módulo');
    }

    /**
     * Test: Verificar manipulación de strings
     */
    public function test_string_manipulation() {
        $test_string = 'DevTools Testing';
        
        // Longitud
        $this->assertEquals(16, strlen($test_string), 'Longitud del string');
        
        // Conversión a mayúsculas
        $this->assertEquals('DEVTOOLS TESTING', strtoupper($test_string), 'Conversión a mayúsculas');
        
        // Conversión a minúsculas
        $this->assertEquals('devtools testing', strtolower($test_string), 'Conversión a minúsculas');
        
        // Búsqueda en string
        $this->assertStringContainsString('Tools', $test_string, 'Búsqueda en string');
        $this->assertStringStartsWith('Dev', $test_string, 'Inicio del string');
        $this->assertStringEndsWith('Testing', $test_string, 'Final del string');
    }

    /**
     * Test: Verificar manipulación de arrays
     */
    public function test_array_manipulation() {
        $test_array = ['dev', 'tools', 'testing', 'php'];
        
        // Conteo
        $this->assertCount(4, $test_array, 'Conteo de elementos del array');
        
        // Contenido
        $this->assertContains('dev', $test_array, 'Array debe contener elemento');
        $this->assertNotContains('wordpress', $test_array, 'Array no debe contener elemento');
        
        // Array vacío
        $empty_array = [];
        $this->assertEmpty($empty_array, 'Array vacío debe estar vacío');
        $this->assertNotEmpty($test_array, 'Array con elementos no debe estar vacío');
    }

    /**
     * Test: Verificar validación de tipos de datos
     */
    public function test_data_type_validation() {
        // Enteros
        $integer = 42;
        $this->assertIsInt($integer, 'Variable debe ser entero');
        $this->assertGreaterThan(40, $integer, 'Entero debe ser mayor que 40');
        $this->assertLessThan(50, $integer, 'Entero debe ser menor que 50');
        
        // Strings
        $string = 'test string';
        $this->assertIsString($string, 'Variable debe ser string');
        
        // Arrays
        $array = [1, 2, 3];
        $this->assertIsArray($array, 'Variable debe ser array');
        
        // Booleanos
        $boolean = true;
        $this->assertIsBool($boolean, 'Variable debe ser booleano');
        
        // Null
        $null_var = null;
        $this->assertNull($null_var, 'Variable debe ser null');
    }

    /**
     * Test: Verificar funciones básicas de PHP
     */
    public function test_php_basic_functions() {
        // Fecha actual
        $timestamp = time();
        $this->assertIsInt($timestamp, 'Timestamp debe ser entero');
        $this->assertGreaterThan(0, $timestamp, 'Timestamp debe ser positivo');
        
        // Generación de números aleatorios
        $random = rand(1, 100);
        $this->assertIsInt($random, 'Número aleatorio debe ser entero');
        $this->assertGreaterThanOrEqual(1, $random, 'Número aleatorio en rango mínimo');
        $this->assertLessThanOrEqual(100, $random, 'Número aleatorio en rango máximo');
        
        // JSON
        $data = ['key' => 'value', 'number' => 123];
        $json = json_encode($data);
        $this->assertIsString($json, 'JSON debe ser string');
        
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded, 'Decodificación JSON debe ser exacta');
    }

    /**
     * Test: Verificar que el entorno de testing esté configurado
     */
    public function test_testing_environment() {
        // Verificar que estamos en modo test
        $this->assertTrue(defined('WP_TESTS_DOMAIN'), 'WP_TESTS_DOMAIN debe estar definida');
        
        // Verificar versión de PHP
        $php_version = PHP_VERSION;
        $this->assertIsString($php_version, 'Versión de PHP debe ser string');
        $this->assertNotEmpty($php_version, 'Versión de PHP no debe estar vacía');
        
        // Verificar que PHPUnit está funcionando
        $this->assertInstanceOf('PHPUnit\Framework\TestCase', $this, 'Esta clase debe extender TestCase');
    }
}
