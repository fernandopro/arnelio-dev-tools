<?php
/**
 * Test Simple de Performance
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

namespace DevTools\Tests\Integration;


class SimplePerformanceTest extends DevToolsTestCase {

    /**
     * Test básico de performance
     */
    public function test_basic_performance() {
        $start_time = microtime(true);
        
        // Operación simple
        $result = [];
        for ($i = 0; $i < 100; $i++) {
            $result[] = 'test_' . $i;
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $this->assertLessThan(0.1, $execution_time);
        $this->assertCount(100, $result);
    }
    
    /**
     * Test de memoria
     */
    public function test_memory_usage() {
        $start_memory = memory_get_usage();
        
        // Crear datos
        $data = array_fill(0, 1000, 'test_data');
        
        $end_memory = memory_get_usage();
        $memory_used = $end_memory - $start_memory;
        
        $this->assertLessThan(1024 * 1024, $memory_used); // Menos de 1MB
        $this->assertCount(1000, $data);
    }
}
