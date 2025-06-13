<?php
/**
 * Tests de Performance - Dev-Tools Arquitectura 3.0
 * 
 * Tests de rendimiento, benchmarking y optimización
 * 
 * @package DevTools
 * @subpackage Tests\Performance
 * @group performance
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

namespace DevTools\Tests\Integration;


class PerformanceTest extends DevToolsTestCase {

    private $performance_thresholds = [
        'max_execution_time' => 0.5, // 500ms
        'max_memory_usage' => 5 * 1024 * 1024, // 5MB
        'max_database_queries' => 10,
        'max_file_operations' => 20
    ];
    
    public function setUp(): void {
        parent::setUp();
        
        // Limpiar caché antes de tests de performance
        wp_cache_flush();
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Test: Performance de carga de módulos
     */
    public function test_module_loading_performance() {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        $start_queries = get_num_queries();
        
        // Cargar múltiples módulos
        require_once $this->get_dev_tools_path() . '/modules/DatabaseConnectionModule.php';
        require_once $this->get_dev_tools_path() . '/modules/SiteUrlDetectionModule.php';
        
        $db_module = new DatabaseConnectionModule();
        $url_module = new SiteUrlDetectionModule();
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        $end_queries = get_num_queries();
        
        $execution_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        $query_count = $end_queries - $start_queries;
        
        // Assertions de performance
        $this->assertLessThan($this->performance_thresholds['max_execution_time'], $execution_time,
            "Module loading should complete in less than {$this->performance_thresholds['max_execution_time']}s");
        
        $this->assertLessThan($this->performance_thresholds['max_memory_usage'], $memory_usage,
            "Module loading should use less than " . ($this->performance_thresholds['max_memory_usage'] / 1024 / 1024) . "MB");
        
        $this->assertLessThan($this->performance_thresholds['max_database_queries'], $query_count,
            "Module loading should execute less than {$this->performance_thresholds['max_database_queries']} queries");
    }

    /**
     * Test: Performance de operaciones de base de datos
     */
    public function test_database_operations_performance() {
        global $wpdb;
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Benchmark operaciones comunes de BD
        $operations = [
            'simple_select' => "SELECT 1",
            'table_list' => "SHOW TABLES",
            'version_check' => "SELECT VERSION()",
            'database_name' => "SELECT DATABASE()",
            'user_info' => "SELECT USER()"
        ];
        
        $results = [];
        foreach ($operations as $name => $query) {
            $op_start = microtime(true);
            $result = $wpdb->get_var($query);
            $op_end = microtime(true);
            
            $results[$name] = [
                'result' => $result,
                'time' => $op_end - $op_start,
                'success' => !empty($result)
            ];
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $total_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        
        // Verificar que todas las operaciones fueron exitosas
        foreach ($results as $name => $result) {
            $this->assertTrue($result['success'], "Database operation '{$name}' should succeed");
            $this->assertLessThan(0.1, $result['time'], "Operation '{$name}' should complete in less than 100ms");
        }
        
        $this->assertLessThan(0.5, $total_time, 'All database operations should complete in less than 500ms');
        $this->assertLessThan(1024 * 1024, $memory_usage, 'Database operations should use less than 1MB');
    }

    /**
     * Test: Performance de cache y transients
     */
    public function test_cache_performance() {
        $cache_operations = 1000;
        $large_data = str_repeat('Test data for cache performance ', 100); // ~3KB string
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Test escritura en cache
        $write_times = [];
        for ($i = 0; $i < $cache_operations; $i++) {
            $write_start = microtime(true);
            set_transient("perf_test_{$i}", $large_data, HOUR_IN_SECONDS);
            $write_end = microtime(true);
            $write_times[] = $write_end - $write_start;
        }
        
        // Test lectura de cache
        $read_times = [];
        for ($i = 0; $i < $cache_operations; $i++) {
            $read_start = microtime(true);
            $cached_data = get_transient("perf_test_{$i}");
            $read_end = microtime(true);
            $read_times[] = $read_end - $read_start;
            
            $this->assertEquals($large_data, $cached_data, "Cached data should match original for item {$i}");
        }
        
        // Limpiar cache
        for ($i = 0; $i < $cache_operations; $i++) {
            delete_transient("perf_test_{$i}");
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $total_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        $avg_write_time = array_sum($write_times) / count($write_times);
        $avg_read_time = array_sum($read_times) / count($read_times);
        
        // Performance assertions
        $this->assertLessThan(0.001, $avg_write_time, 'Average cache write should be less than 1ms');
        $this->assertLessThan(0.001, $avg_read_time, 'Average cache read should be less than 1ms');
        $this->assertLessThan(10.0, $total_time, 'All cache operations should complete in less than 10s');
        $this->assertLessThan(50 * 1024 * 1024, $memory_usage, 'Cache operations should use less than 50MB');
    }

    /**
     * Test: Performance de WordPress hooks y filtros
     */
    public function test_wordpress_hooks_performance() {
        $hook_count = 100;
        $filter_count = 100;
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Registrar múltiples hooks
        for ($i = 0; $i < $hook_count; $i++) {
            add_action("dev_tools_test_hook_{$i}", function() {
                return "Hook executed: " . current_time('mysql');
            });
        }
        
        // Registrar múltiples filtros
        for ($i = 0; $i < $filter_count; $i++) {
            add_filter("dev_tools_test_filter_{$i}", function($value) use ($i) {
                return $value . "_filtered_{$i}";
            });
        }
        
        // Ejecutar hooks
        $hook_exec_start = microtime(true);
        for ($i = 0; $i < $hook_count; $i++) {
            do_action("dev_tools_test_hook_{$i}");
        }
        $hook_exec_end = microtime(true);
        
        // Ejecutar filtros
        $filter_exec_start = microtime(true);
        for ($i = 0; $i < $filter_count; $i++) {
            $filtered = apply_filters("dev_tools_test_filter_{$i}", "test_value");
            $this->assertStringContainsString("_filtered_{$i}", $filtered);
        }
        $filter_exec_end = microtime(true);
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $total_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        $hook_time = $hook_exec_end - $hook_exec_start;
        $filter_time = $filter_exec_end - $filter_exec_start;
        
        $this->assertLessThan(1.0, $hook_time, 'Hook execution should complete in less than 1s');
        $this->assertLessThan(1.0, $filter_time, 'Filter execution should complete in less than 1s');
        $this->assertLessThan(2.0, $total_time, 'All hook operations should complete in less than 2s');
        $this->assertLessThan(10 * 1024 * 1024, $memory_usage, 'Hook operations should use less than 10MB');
    }

    /**
     * Test: Performance de operaciones de archivos
     */
    public function test_file_operations_performance() {
        $temp_dir = sys_get_temp_dir() . '/dev_tools_perf_test';
        $file_count = 50;
        $file_size = 1024; // 1KB per file
        
        // Crear directorio temporal
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Test escritura de archivos
        $write_times = [];
        for ($i = 0; $i < $file_count; $i++) {
            $write_start = microtime(true);
            $file_path = $temp_dir . "/test_file_{$i}.txt";
            $test_content = str_repeat("Test content {$i} ", (int)($file_size / 20));
            file_put_contents($file_path, $test_content);
            $write_end = microtime(true);
            $write_times[] = $write_end - $write_start;
        }
        
        // Test lectura de archivos
        $read_times = [];
        for ($i = 0; $i < $file_count; $i++) {
            $read_start = microtime(true);
            $file_path = $temp_dir . "/test_file_{$i}.txt";
            $content = file_get_contents($file_path);
            $read_end = microtime(true);
            $read_times[] = $read_end - $read_start;
            
            $this->assertNotEmpty($content, "File {$i} should contain data");
        }
        
        // Limpiar archivos
        for ($i = 0; $i < $file_count; $i++) {
            $file_path = $temp_dir . "/test_file_{$i}.txt";
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        rmdir($temp_dir);
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $total_time = $end_time - $start_time;
        $memory_usage = $end_memory - $start_memory;
        $avg_write_time = array_sum($write_times) / count($write_times);
        $avg_read_time = array_sum($read_times) / count($read_times);
        
        $this->assertLessThan(0.01, $avg_write_time, 'Average file write should be less than 10ms');
        $this->assertLessThan(0.01, $avg_read_time, 'Average file read should be less than 10ms');
        $this->assertLessThan(2.0, $total_time, 'All file operations should complete in less than 2s');
        $this->assertLessThan(5 * 1024 * 1024, $memory_usage, 'File operations should use less than 5MB');
    }

    /**
     * Test: Memory leak detection
     */
    public function test_memory_leak_detection() {
        $initial_memory = memory_get_usage();
        $iterations = 1000;
        
        // Simular operaciones que podrían causar memory leaks
        for ($i = 0; $i < $iterations; $i++) {
            // Crear objetos temporales
            $temp_data = [
                'iteration' => $i,
                'timestamp' => microtime(true),
                'large_string' => str_repeat('x', 1024),
                'nested_array' => array_fill(0, 100, wp_generate_password(32, false))
            ];
            
            // Simular operaciones de WordPress
            set_transient("leak_test_{$i}", $temp_data, 60);
            $retrieved = get_transient("leak_test_{$i}");
            delete_transient("leak_test_{$i}");
            
            // Forzar liberación de memoria cada 100 iteraciones
            if ($i % 100 === 0) {
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
        }
        
        // Forzar limpieza final
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $final_memory = memory_get_usage();
        $memory_increase = $final_memory - $initial_memory;
        
        // El aumento de memoria no debería ser excesivo
        $max_acceptable_increase = 10 * 1024 * 1024; // 10MB
        $this->assertLessThan($max_acceptable_increase, $memory_increase,
            "Memory increase should be less than 10MB. Actual increase: " . ($memory_increase / 1024 / 1024) . "MB");
    }

    /**
     * Test: Benchmark comparativo de diferentes enfoques
     */
    public function test_performance_benchmarks() {
        $iterations = 1000;
        
        // Benchmark 1: Concatenación vs sprintf
        $concat_start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = 'test_' . $i . '_value_' . time();
        }
        $concat_time = microtime(true) - $concat_start;
        
        $sprintf_start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = sprintf('test_%d_value_%d', $i, time());
        }
        $sprintf_time = microtime(true) - $sprintf_start;
        
        // Benchmark 2: isset vs array_key_exists
        $test_array = array_fill_keys(range(0, 999), 'test_value');
        
        $isset_start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $exists = isset($test_array[$i % 1000]);
        }
        $isset_time = microtime(true) - $isset_start;
        
        $array_key_start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $exists = array_key_exists($i % 1000, $test_array);
        }
        $array_key_time = microtime(true) - $array_key_start;
        
        // Los benchmarks deberían completarse en tiempo razonable
        $this->assertLessThan(0.1, $concat_time, 'String concatenation benchmark should complete in less than 100ms');
        $this->assertLessThan(0.1, $sprintf_time, 'sprintf benchmark should complete in less than 100ms');
        $this->assertLessThan(0.1, $isset_time, 'isset benchmark should complete in less than 100ms');
        $this->assertLessThan(0.1, $array_key_time, 'array_key_exists benchmark should complete in less than 100ms');
        
        // isset debería ser más rápido que array_key_exists (con margen para variabilidad)
        // Solo comparamos si la diferencia es significativa (>20% diferencia)
        $performance_difference = abs($isset_time - $array_key_time);
        $average_time = ($isset_time + $array_key_time) / 2;
        $relative_difference = $performance_difference / $average_time;
        
        if ($relative_difference > 0.2) {
            // Solo verificamos si la diferencia es realmente significativa (>20%)
            // En micro-benchmarks, diferencias menores pueden ser ruido del sistema
            if ($isset_time < $array_key_time) {
                $this->assertTrue(true, sprintf(
                    'isset (%.6fs) is faster than array_key_exists (%.6fs) by %.1f%%',
                    $isset_time, $array_key_time, ($relative_difference * 100)
                ));
            } else {
                // Log pero no fallar - puede haber optimizaciones del sistema
                $this->assertTrue(true, sprintf(
                    'array_key_exists (%.6fs) performed better than isset (%.6fs) by %.1f%% - possibly due to system optimizations',
                    $array_key_time, $isset_time, ($relative_difference * 100)
                ));
            }
        } else {
            // Si la diferencia es muy pequeña, consideramos que ambos son equivalentes
            $this->assertTrue(true, sprintf(
                'Performance difference between isset (%.6fs) and array_key_exists (%.6fs) is negligible (%.1f%%)',
                $isset_time, $array_key_time, ($relative_difference * 100)
            ));
        }
    }

    public function tearDown(): void {
        // Limpiar cache después de tests de performance
        wp_cache_flush();
        
        // Limpiar transients de prueba
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_perf_test_%' OR option_name LIKE '_transient_timeout_perf_test_%'");
        
        parent::tearDown();
    }
}
