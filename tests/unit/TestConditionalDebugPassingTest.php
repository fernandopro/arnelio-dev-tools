<?php
/**
 * Test que siempre pasa para verificar que no se muestra debug innecesario
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 1.0.0
 */

class TestConditionalDebugPassingTest extends WP_UnitTestCase {
    
    /**
     * Test que pasa - no debería mostrar debug
     */
    public function testAlwaysPasses(): void {
        $this->assertTrue(true, 'Este test siempre pasa');
        $this->assertEquals('expected', 'expected', 'Valores iguales');
        $this->assertNotEmpty('value', 'Valor no vacío');
    }
    
    /**
     * Otro test que pasa
     */
    public function testAnotherPassingTest(): void {
        $data = ['key' => 'value'];
        $this->assertArrayHasKey('key', $data);
        $this->assertEquals('value', $data['key']);
    }
}
