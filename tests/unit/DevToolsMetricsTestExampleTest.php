<?php
/**
 * Test de ejemplo para verificar métricas del panel en Dev-Tools
 * 
 * Este test incluye intencionalmente:
 * - Tests que pasan
 * - Tests que fallan
 * - Tests omitidos (skipped)
 * - Errores fatales
 * - Tests incompletos
 * 
 * Para verificar que el modern-info-grid muestra correctamente todas las métricas en Dev-Tools Tests.
 */

namespace DevTools\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DevToolsMetricsTestExampleTest extends TestCase {
    
    /**
     * Test que pasa correctamente
     */
    public function test_passing_test_one() {
        $this->assertTrue(true, 'Este test debería pasar');
        $this->assertEquals(5 + 5, 10, 'La suma básica debería funcionar');
        $this->assertNotEmpty('dev-tools', 'Una cadena no vacía debería ser válida');
    }
    
    /**
     * Test que pasa con múltiples assertions
     */
    public function test_passing_test_two() {
        $config = ['name' => 'Dev-Tools', 'version' => '3.0.0'];
        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('version', $config);
        $this->assertEquals('Dev-Tools', $config['name']);
        $this->assertEquals('3.0.0', $config['version']);
    }
    
    /**
     * Test que pasa verificando funcionalidad básica
     */
    public function test_passing_test_three() {
        $numbers = [1, 2, 3, 4, 5];
        $this->assertCount(5, $numbers);
        $this->assertContains(3, $numbers);
        $this->assertIsArray($numbers);
        $this->assertNotEmpty($numbers);
    }
    
    /**
     * Test que falla intencionalmente para métricas
     */
    public function test_intentional_failure_one() {
        $this->assertTrue(false, 'Este test debería fallar para probar las métricas de dev-tools');
    }
    
    /**
     * Test que falla con assertion específica
     */
    public function test_intentional_failure_two() {
        $expected = 'dev-tools-expected';
        $actual = 'dev-tools-actual';
        $this->assertEquals($expected, $actual, 'Los valores deberían ser iguales pero no lo son');
    }
    
    /**
     * Test omitido por condición específica de dev-tools
     */
    public function test_skipped_dev_tools() {
        $this->markTestSkipped('Este test se omite intencionalmente para probar métricas en dev-tools');
    }
    
    /**
     * Test omitido por versión
     */
    public function test_skipped_by_version() {
        if (version_compare(PHP_VERSION, '9.0.0', '<')) {
            $this->markTestSkipped('Este test requiere PHP 9.0 (omitido para métricas)');
        }
        
        // Este código nunca se ejecutará
        $this->assertTrue(true);
    }
    
    /**
     * Test que causa un error fatal en dev-tools
     */
    public function test_error_in_dev_tools() {
        // Intentar acceder a propiedad de objeto null - Error fatal
        $devToolsObject = null;
        $devToolsObject->getConfiguration(); // Esto causará un Error
    }
    
    /**
     * Test incompleto para dev-tools
     */
    public function test_incomplete_dev_tools() {
        $this->markTestIncomplete('Este test de dev-tools está incompleto intencionalmente');
    }
    
    /**
     * Test riesgoso (sin assertions) para dev-tools
     */
    public function test_risky_dev_tools() {
        // Este test no tiene assertions, lo que lo hace riesgoso
        $devToolsVariable = 'dev-tools-risky-test';
        $calculation = 10 * 10;
        // Sin assertions = risky
    }
    
    /**
     * Test que pasa para equilibrar las métricas
     */
    public function test_final_passing_test() {
        $this->assertIsString('dev-tools-final-test');
        $this->assertGreaterThan(0, strlen('dev-tools'));
        $this->assertLessThan(100, strlen('short'));
    }
}
