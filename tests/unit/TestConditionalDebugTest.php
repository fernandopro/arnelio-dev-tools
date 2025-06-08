<?php
/**
 * Test para verificar el sistema de debug condicional
 * 
 * Este test incluye métodos que fallan intencionalmente para verificar
 * que el sistema de debug condicional se activa solo cuando hay fallos.
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 1.0.0
 */

class TestConditionalDebugTest extends WP_UnitTestCase {
    
    /**
     * Test que pasa - no debería mostrar debug
     */
    public function testPassingTest(): void {
        $this->assertTrue(true, 'Este test siempre pasa');
        $this->assertEquals('expected', 'expected', 'Valores iguales');
        $this->assertNotEmpty('value', 'Valor no vacío');
    }
    
    /**
     * Test que falla intencionalmente - debería activar debug condicional
     */
    public function testFailingTest(): void {
        $this->assertTrue(false, 'Este test siempre falla para probar el debug condicional');
    }
    
    /**
     * Test con assertion más compleja que falla
     * DESCOMENTA ESTE TEST PARA PROBAR EL DEBUG CONDICIONAL
     */
    /*
    public function testComplexFailingTest(): void {
        $expected = ['key' => 'expected_value'];
        $actual = ['key' => 'different_value'];
        
        $this->assertEquals($expected, $actual, 'Los arrays no coinciden - esto debería activar debug');
    }
    */
}
