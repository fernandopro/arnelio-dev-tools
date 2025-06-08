<?php

/**
 * Test para verificar que las constantes de DevTools están correctamente definidas
 * 
 * @package TarokinaPro\DevTools\Tests
 */

class DevToolsConstantsTest extends DevToolsTestCase
{
    /**
     * Test que verifica que todas las constantes de DevTools están definidas
     */
    public function testDevToolsConstantsAreDefined(): void
    {
        // Verificar que las constantes principales están definidas
        $this->assertTrue(defined('DEV_TOOLS_DISABLE_ANTI_DEADLOCK'), 'DEV_TOOLS_DISABLE_ANTI_DEADLOCK debe estar definida');
        $this->assertTrue(defined('DEV_TOOLS_FORCE_ANTI_DEADLOCK'), 'DEV_TOOLS_FORCE_ANTI_DEADLOCK debe estar definida');
        $this->assertTrue(defined('DEV_TOOLS_TESTS_VERBOSE'), 'DEV_TOOLS_TESTS_VERBOSE debe estar definida');
        $this->assertTrue(defined('DEV_TOOLS_TESTS_DEBUG'), 'DEV_TOOLS_TESTS_DEBUG debe estar definida');
        $this->assertTrue(defined('PHPUNIT_RUNNING'), 'PHPUNIT_RUNNING debe estar definida');
    }
    
    /**
     * Test que verifica los valores por defecto de las constantes
     */
    public function testDevToolsConstantsDefaultValues(): void
    {
        // Verificar valores por defecto (definidos en bootstrap.php)
        $this->assertFalse(DEV_TOOLS_DISABLE_ANTI_DEADLOCK, 'Anti-deadlock debe estar habilitado por defecto');
        $this->assertNull(DEV_TOOLS_FORCE_ANTI_DEADLOCK, 'Force anti-deadlock debe ser null por defecto (detección automática)');
        $this->assertFalse(DEV_TOOLS_TESTS_VERBOSE, 'Verbose debe estar deshabilitado por defecto');
        $this->assertFalse(DEV_TOOLS_TESTS_DEBUG, 'Debug debe estar deshabilitado por defecto');
        $this->assertTrue(PHPUNIT_RUNNING, 'PHPUNIT_RUNNING debe ser true en contexto de PHPUnit');
    }
    
    /**
     * Test que verifica el comportamiento del sistema anti-deadlock
     */
    public function testAntiDeadlockSystemBehavior(): void
    {
        // Verificar que el sistema detecta correctamente el comportamiento
        $this->assertTrue($this->isUsingAntiDeadlock(), 'Debería usar anti-deadlock en contexto de testing');
        
        // Obtener información de diagnóstico
        $diagnostic = $this->getDiagnosticInfo();
        
        // Verificar que las constantes están en el diagnóstico
        $this->assertArrayHasKey('constants_defined', $diagnostic);
        $this->assertArrayHasKey('DEV_TOOLS_DISABLE_ANTI_DEADLOCK', $diagnostic['constants_defined']);
        $this->assertArrayHasKey('DEV_TOOLS_FORCE_ANTI_DEADLOCK', $diagnostic['constants_defined']);
    }
    
    /**
     * Test para verificar que el override manual funciona
     */
    public function testManualOverrideBehavior(): void
    {
        // Cambiar a comportamiento estándar
        $this->useStandardWordPressBehavior();
        $this->assertFalse($this->isUsingAntiDeadlock(), 'Override manual a estándar debe funcionar');
        
        // Cambiar a comportamiento anti-deadlock
        $this->useAntiDeadlockBehavior();
        $this->assertTrue($this->isUsingAntiDeadlock(), 'Override manual a anti-deadlock debe funcionar');
        
        // Resetear a automático
        $this->useAutomaticBehavior();
        $this->assertTrue($this->isUsingAntiDeadlock(), 'Reset a automático debe funcionar');
    }
    
    /**
     * Test para mostrar información de contexto (solo en verbose)
     */
    public function testShowTestContext(): void
    {
        // Solo mostrar información si está en modo verbose
        $verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
        
        if ($verbose_mode) {
            $context = $this->getTestContext();
            
            echo "\n" . str_repeat("=", 50) . "\n";
            echo "🔍 CONTEXTO DEL TEST ACTUAL:\n";
            echo str_repeat("=", 50) . "\n";
            
            foreach ($context as $key => $value) {
                $display_value = is_bool($value) ? ($value ? 'true' : 'false') : 
                                (is_null($value) ? 'null' : $value);
                echo "  {$key}: {$display_value}\n";
            }
            
            echo str_repeat("=", 50) . "\n";
        }
        
        $this->assertTrue(true, 'Información de contexto mostrada');
    }
}
