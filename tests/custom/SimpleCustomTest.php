<?php

/**
 * Test simple para probar la detección automática de directorios
 */

require_once __DIR__ . '/../DevToolsTestCase.php';

class SimpleCustomTest extends DevToolsTestCase
{
    /**
     * Test de prueba en directorio custom
     */
    public function testCustomDirectoryDetection(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TEST EN DIRECTORIO CUSTOM - DETECCIÓN AUTOMÁTICA\n";
        echo str_repeat("=", 80) . "\n\n";

        echo "✅ Este test está en el directorio: tests/custom/\n";
        echo "✅ Debería ser detectado automáticamente como test 'otros'\n";
        echo "✅ Sin necesidad de configurar manualmente el directorio\n\n";

        echo "📍 INFORMACIÓN DEL TEST:\n";
        echo "• Archivo: " . __FILE__ . "\n";
        echo "• Clase: " . __CLASS__ . "\n";
        echo "• Método: " . __METHOD__ . "\n";
        echo "• Directorio: tests/custom/\n";
        echo "• Tipo: Otros (detectado automáticamente)\n\n";

        echo "🎯 OBJETIVO:\n";
        echo "Demostrar que cualquier directorio nuevo bajo tests/\n";
        echo "es automáticamente incluido en la categoría 'otros'\n";
        echo "sin necesidad de modificar phpunit.xml\n\n";

        echo str_repeat("=", 80) . "\n\n";

        $this->assertTrue(true, 'Test en directorio custom ejecutado correctamente');
    }
}
