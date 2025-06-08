<?php
/**
 * Test que falla intencionalmente para verificar colores rojos
 * 
 * Este test está diseñado para fallar y probar que los mensajes de error
 * se muestren correctamente en rojo en la consola del navegador.
 * 
 * @package TarokinaPro\DevTools\Tests
 */

// Cargar el framework de testing si no está ya cargado
if (!class_exists('DevToolsTestCase')) {
    require_once dirname(__DIR__) . '/DevToolsTestCase.php';
}

class TestFallidoIntencionalmente extends DevToolsTestCase
{
    private $verbose_mode = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
    }

    /**
     * Test que siempre falla para verificar colores de error
     */
    public function testQueSiempreFalla(): void
    {
        // Log de inicio
        if ($this->verbose_mode) {
            echo "\n🔴 INICIANDO TEST QUE DEBE FALLAR\n";
            echo "Este test está diseñado para fallar y mostrar colores rojos\n";
        }
        
        // Assertion que siempre falla
        $this->assertTrue(false, 'Este test está diseñado para fallar - verificando colores rojos');
    }
    
    /**
     * Test con múltiples fallos para ver diferentes tipos de errores
     */
    public function testConMultiplesFallos(): void
    {
        if ($this->verbose_mode) {
            echo "\n🔴 TEST CON MÚLTIPLES FALLOS\n";
        }
        
        // Fallo 1: Assertion falsa
        $this->assertFalse(true, 'Primera assertion que falla');
        
        // Fallo 2: Valores no iguales
        $this->assertEquals('esperado', 'actual', 'Valores no coinciden');
        
        // Fallo 3: Null assertion
        $this->assertNotNull(null, 'Variable es null cuando no debería serlo');
    }
    
    /**
     * Test que genera una excepción
     */
    public function testQueGeneraExcepcion(): void
    {
        if ($this->verbose_mode) {
            echo "\n💥 TEST QUE GENERA EXCEPCIÓN\n";
        }
        
        // Esto generará una excepción
        throw new Exception('Esta es una excepción de prueba para verificar colores de error');
    }
    
    /**
     * Test que falla con mensaje personalizado largo
     */
    public function testConMensajeLargo(): void
    {
        if ($this->verbose_mode) {
            echo "\n📝 TEST CON MENSAJE DE ERROR LARGO\n";
        }
        
        $mensaje_largo = "Este es un mensaje de error muy largo para verificar cómo se muestran " .
                        "los errores con texto extenso en la consola del navegador. " .
                        "Debería aparecer en color rojo y ser fácil de leer.";
        
        $this->assertTrue(false, $mensaje_largo);
    }
}
