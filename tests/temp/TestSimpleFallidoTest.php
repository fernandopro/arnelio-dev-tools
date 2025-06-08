<?php
/**
 * Test Simple Fallido - Para verificar colores rojos
 * 
 * @package TarokinaPro\DevTools\Tests
 */

// Cargar el framework de testing si no está ya cargado
if (!class_exists('DevToolsTestCase')) {
    require_once dirname(__DIR__) . '/DevToolsTestCase.php';
}

class TestSimpleFallidoTest extends DevToolsTestCase
{
    private $verbose_mode = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
    }

    /**
     * Test simple que falla para verificar colores
     */
    public function testSimpleFallo(): void
    {
        if ($this->verbose_mode) {
            echo "\n🔴 Test simple que debe fallar\n";
        }
        
        // Este assertion siempre fallará
        $this->assertEquals('verde', 'rojo', 'Los colores no coinciden - esto es intencional');
    }
}
