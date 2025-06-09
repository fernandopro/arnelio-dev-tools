<?php
/**
 * Performance Module - Dev-Tools Arquitectura 3.0
 * 
 * Módulo para monitoreo y análisis de rendimiento del sitio WordPress.
 * Proporciona métricas de velocidad, consultas de base de datos, uso de memoria,
 * análisis de plugins y herramientas de optimización.
 * 
 * @package DevTools
 * @subpackage Modules
 * @version 3.0
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar dependencias
require_once dirname(__DIR__) . '/core/DevToolsModuleBase.php';

/**
 * Módulo de Performance
 * 
 * Características principales:
 * - Métricas de rendimiento en tiempo real
 * - Análisis de consultas de base de datos
 * - Monitoreo de uso de memoria
 * - Evaluación de rendimiento de plugins
 * - Herramientas de optimización
 * - Reportes de velocidad de carga
 */
class PerformanceModule extends DevToolsModuleBase {
    
    /**
     * Nombre del módulo
     */
    protected string $module_name = 'performance';
    
    /**
     * Configuración específica del módulo de rendimiento
     */
    protected array $performance_config = [];
    
    /**
     * Inicializar el módulo
     */
    public function init(): void {
        add_action('wp_footer', [$this, 'inject_performance_monitoring']);
        add_action('admin_footer', [$this, 'inject_performance_monitoring']);
        
        // Hooks para monitoreo de rendimiento
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('shutdown', [$this, 'collect_performance_data']);
        }
    }
    
    /**
     * Registrar comandos AJAX
     */
    public function registerAjaxCommands(DevToolsAjaxHandler $ajaxHandler): void {
        $ajaxHandler->registerCommand('get_performance_data', [$this, 'getPerformanceData']);
        $ajaxHandler->registerCommand('get_database_queries', [$this, 'getDatabaseQueries']);
        $ajaxHandler->registerCommand('get_memory_usage', [$this, 'getMemoryUsage']);
        $ajaxHandler->registerCommand('get_plugin_performance', [$this, 'getPluginPerformance']);
        $ajaxHandler->registerCommand('run_performance_test', [$this, 'runPerformanceTest']);
        $ajaxHandler->registerCommand('get_page_speed_metrics', [$this, 'getPageSpeedMetrics']);
        $ajaxHandler->registerCommand('optimize_database', [$this, 'optimizeDatabase']);
        $ajaxHandler->registerCommand('clear_performance_cache', [$this, 'clearPerformanceCache']);
    }
    
    /**
     * Renderizar el contenido del módulo
     */
    public function render(): string {
        ob_start();
        ?>
        <div id="performance-module" class="dev-tools-module">
            <!-- Header del módulo -->
            <div class="module-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="module-title">
                            <i class="bi bi-speedometer2"></i>
                            Análisis de Rendimiento
                        </h2>
                        <p class="module-description text-muted">
                            Monitoreo y optimización del rendimiento del sitio
                        </p>
                    </div>
                    <div class="module-actions">
                        <button class="btn btn-outline-primary btn-sm" id="refresh-performance">
                            <i class="bi bi-arrow-clockwise"></i>
                            Actualizar
                        </button>
                        <button class="btn btn-primary btn-sm" id="run-performance-test">
                            <i class="bi bi-play-circle"></i>
                            Ejecutar Test
                        </button>
                    </div>
                </div>
            </div>

            <!-- Métricas principales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Tiempo de Carga</h5>
                            <h3 class="card-text" id="load-time">--</h3>
                            <small class="text-muted">segundos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success">Memoria Usada</h5>
                            <h3 class="card-text" id="memory-usage">--</h3>
                            <small class="text-muted">MB</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Consultas DB</h5>
                            <h3 class="card-text" id="db-queries">--</h3>
                            <small class="text-muted">queries</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info">Puntuación</h5>
                            <h3 class="card-text" id="performance-score">--</h3>
                            <small class="text-muted">/ 100</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de análisis -->
            <ul class="nav nav-tabs mb-3" id="performance-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview">
                        <i class="bi bi-graph-up"></i> Resumen
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="database-tab" data-bs-toggle="tab" href="#database">
                        <i class="bi bi-database"></i> Base de Datos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="plugins-tab" data-bs-toggle="tab" href="#plugins">
                        <i class="bi bi-puzzle"></i> Plugins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="optimization-tab" data-bs-toggle="tab" href="#optimization">
                        <i class="bi bi-tools"></i> Optimización
                    </a>
                </li>
            </ul>

            <!-- Contenido de los tabs -->
            <div class="tab-content">
                <!-- Tab de Resumen -->
                <div class="tab-pane fade show active" id="overview">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-chart-line"></i> Gráfico de Rendimiento</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="performance-chart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-exclamation-triangle"></i> Problemas Detectados</h5>
                                </div>
                                <div class="card-body">
                                    <div id="performance-issues">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-hourglass-split"></i>
                                            <p>Analizando...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab de Base de Datos -->
                <div class="tab-pane fade" id="database">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="bi bi-database"></i> Análisis de Consultas</h5>
                            <button class="btn btn-outline-danger btn-sm" id="optimize-db">
                                <i class="bi bi-gear"></i> Optimizar DB
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="queries-table">
                                    <thead>
                                        <tr>
                                            <th>Query</th>
                                            <th>Tiempo (ms)</th>
                                            <th>Llamadas</th>
                                            <th>Función</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">
                                                Cargando consultas...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab de Plugins -->
                <div class="tab-pane fade" id="plugins">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-puzzle"></i> Rendimiento de Plugins</h5>
                        </div>
                        <div class="card-body">
                            <div id="plugins-performance">
                                <div class="text-center text-muted">
                                    <i class="bi bi-hourglass-split"></i>
                                    <p>Analizando plugins...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab de Optimización -->
                <div class="tab-pane fade" id="optimization">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-lightning"></i> Optimizaciones Disponibles</h5>
                                </div>
                                <div class="card-body">
                                    <div id="optimization-suggestions">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-search"></i>
                                            <p>Buscando optimizaciones...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="bi bi-tools"></i> Herramientas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" id="clear-all-cache">
                                            <i class="bi bi-trash"></i> Limpiar Todo el Cache
                                        </button>
                                        <button class="btn btn-outline-warning" id="run-cleanup">
                                            <i class="bi bi-broom"></i> Limpieza de Base de Datos
                                        </button>
                                        <button class="btn btn-outline-info" id="generate-report">
                                            <i class="bi bi-file-earmark-text"></i> Generar Reporte
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener datos de rendimiento
     */
    public function getPerformanceData(): array {
        $start_time = microtime(true);
        
        // Datos de rendimiento básicos
        $performance_data = [
            'load_time' => $this->getPageLoadTime(),
            'memory_usage' => $this->getCurrentMemoryUsage(),
            'memory_peak' => $this->getPeakMemoryUsage(),
            'db_queries' => $this->getDatabaseQueriesCount(),
            'db_query_time' => $this->getDatabaseQueryTime(),
            'performance_score' => $this->calculatePerformanceScore(),
            'issues' => $this->detectPerformanceIssues(),
            'recommendations' => $this->getOptimizationRecommendations()
        ];
        
        $processing_time = (microtime(true) - $start_time) * 1000;
        $performance_data['processing_time'] = round($processing_time, 2);
        
        return $performance_data;
    }
    
    /**
     * Obtener consultas de base de datos
     */
    public function getDatabaseQueries(): array {
        global $wpdb;
        
        $queries = [];
        
        if (defined('SAVEQUERIES') && SAVEQUERIES && !empty($wpdb->queries)) {
            foreach ($wpdb->queries as $query) {
                $queries[] = [
                    'sql' => $query[0],
                    'time' => round($query[1] * 1000, 2), // Convertir a milisegundos
                    'function' => $query[2] ?? 'unknown',
                    'calls' => 1
                ];
            }
        }
        
        // Agrupar consultas similares
        $grouped_queries = $this->groupSimilarQueries($queries);
        
        // Ordenar por tiempo total
        usort($grouped_queries, function($a, $b) {
            return $b['total_time'] <=> $a['total_time'];
        });
        
        return array_slice($grouped_queries, 0, 50); // Top 50 consultas
    }
    
    /**
     * Obtener uso de memoria
     */
    public function getMemoryUsage(): array {
        return [
            'current' => $this->getCurrentMemoryUsage(),
            'peak' => $this->getPeakMemoryUsage(),
            'limit' => $this->getMemoryLimit(),
            'percentage' => $this->getMemoryUsagePercentage(),
            'breakdown' => $this->getMemoryBreakdown()
        ];
    }
    
    /**
     * Obtener rendimiento de plugins
     */
    public function getPluginPerformance(): array {
        $plugins = [];
        
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        foreach ($active_plugins as $plugin_path) {
            if (isset($all_plugins[$plugin_path])) {
                $plugin_data = $all_plugins[$plugin_path];
                $plugins[] = [
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'path' => $plugin_path,
                    'load_time' => $this->getPluginLoadTime($plugin_path),
                    'memory_usage' => $this->getPluginMemoryUsage($plugin_path),
                    'hooks_count' => $this->getPluginHooksCount($plugin_path),
                    'impact_score' => $this->calculatePluginImpact($plugin_path)
                ];
            }
        }
        
        // Ordenar por impacto
        usort($plugins, function($a, $b) {
            return $b['impact_score'] <=> $a['impact_score'];
        });
        
        return $plugins;
    }
    
    /**
     * Ejecutar test de rendimiento
     */
    public function runPerformanceTest(): array {
        $start_time = microtime(true);
        
        $test_results = [
            'test_timestamp' => current_time('mysql'),
            'url_tested' => home_url(),
            'metrics' => []
        ];
        
        // Test de velocidad de respuesta
        $test_results['metrics']['response_time'] = $this->testResponseTime();
        
        // Test de base de datos
        $test_results['metrics']['database_performance'] = $this->testDatabasePerformance();
        
        // Test de memoria
        $test_results['metrics']['memory_efficiency'] = $this->testMemoryEfficiency();
        
        // Test de cache
        $test_results['metrics']['cache_effectiveness'] = $this->testCacheEffectiveness();
        
        $total_time = (microtime(true) - $start_time) * 1000;
        $test_results['test_duration'] = round($total_time, 2);
        
        // Calcular puntuación general
        $test_results['overall_score'] = $this->calculateOverallScore($test_results['metrics']);
        
        return $test_results;
    }
    
    /**
     * Obtener métricas de PageSpeed
     */
    public function getPageSpeedMetrics(): array {
        // Simulación de métricas de PageSpeed (en un entorno real se integraría con Google PageSpeed Insights API)
        return [
            'performance_score' => rand(70, 95),
            'first_contentful_paint' => rand(800, 2000) . 'ms',
            'largest_contentful_paint' => rand(1500, 3000) . 'ms',
            'first_input_delay' => rand(50, 200) . 'ms',
            'cumulative_layout_shift' => '0.' . rand(1, 25),
            'time_to_interactive' => rand(2000, 5000) . 'ms',
            'suggestions' => $this->getPageSpeedSuggestions()
        ];
    }
    
    /**
     * Optimizar base de datos
     */
    public function optimizeDatabase(): array {
        global $wpdb;
        
        $results = [
            'optimized_tables' => 0,
            'space_saved' => 0,
            'errors' => []
        ];
        
        try {
            // Obtener todas las tablas
            $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
            
            foreach ($tables as $table) {
                $table_name = $table[0];
                
                // Optimizar tabla
                $result = $wpdb->query("OPTIMIZE TABLE `$table_name`");
                
                if ($result !== false) {
                    $results['optimized_tables']++;
                } else {
                    $results['errors'][] = "Error optimizando tabla: $table_name";
                }
            }
            
            // Limpiar opciones autoload
            $this->cleanAutoloadOptions();
            
            // Limpiar revisiones antiguas
            $this->cleanOldRevisions();
            
            // Limpiar spam y trash
            $this->cleanSpamAndTrash();
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Limpiar cache de rendimiento
     */
    public function clearPerformanceCache(): array {
        $cleared = [
            'object_cache' => false,
            'page_cache' => false,
            'opcode_cache' => false,
            'database_cache' => false
        ];
        
        // Limpiar object cache
        if (function_exists('wp_cache_flush')) {
            $cleared['object_cache'] = wp_cache_flush();
        }
        
        // Limpiar caché de opciones
        wp_cache_delete('alloptions', 'options');
        
        // Limpiar OPcache si está disponible
        if (function_exists('opcache_reset')) {
            $cleared['opcode_cache'] = opcache_reset();
        }
        
        return $cleared;
    }
    
    // Métodos auxiliares privados
    
    private function getPageLoadTime(): float {
        if (defined('ABSPATH')) {
            $load_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            return round($load_time, 3);
        }
        return 0;
    }
    
    private function getCurrentMemoryUsage(): float {
        return round(memory_get_usage() / 1024 / 1024, 2);
    }
    
    private function getPeakMemoryUsage(): float {
        return round(memory_get_peak_usage() / 1024 / 1024, 2);
    }
    
    private function getMemoryLimit(): int {
        $limit = ini_get('memory_limit');
        return (int) $limit;
    }
    
    private function getMemoryUsagePercentage(): float {
        $current = memory_get_usage();
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        if ($limit > 0) {
            return round(($current / $limit) * 100, 2);
        }
        
        return 0;
    }
    
    private function parseMemoryLimit(string $limit): int {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int) $limit;
        
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    private function getDatabaseQueriesCount(): int {
        global $wpdb;
        return isset($wpdb->num_queries) ? $wpdb->num_queries : 0;
    }
    
    private function getDatabaseQueryTime(): float {
        global $wpdb;
        
        if (defined('SAVEQUERIES') && SAVEQUERIES && !empty($wpdb->queries)) {
            $total_time = 0;
            foreach ($wpdb->queries as $query) {
                $total_time += $query[1];
            }
            return round($total_time * 1000, 2); // Convertir a milisegundos
        }
        
        return 0;
    }
    
    private function calculatePerformanceScore(): int {
        $score = 100;
        
        // Penalizar por tiempo de carga alto
        $load_time = $this->getPageLoadTime();
        if ($load_time > 3) $score -= 30;
        elseif ($load_time > 2) $score -= 20;
        elseif ($load_time > 1) $score -= 10;
        
        // Penalizar por uso alto de memoria
        $memory_percentage = $this->getMemoryUsagePercentage();
        if ($memory_percentage > 80) $score -= 20;
        elseif ($memory_percentage > 60) $score -= 10;
        
        // Penalizar por muchas consultas de BD
        $queries_count = $this->getDatabaseQueriesCount();
        if ($queries_count > 100) $score -= 25;
        elseif ($queries_count > 50) $score -= 15;
        elseif ($queries_count > 25) $score -= 5;
        
        return max(0, $score);
    }
    
    private function detectPerformanceIssues(): array {
        $issues = [];
        
        if ($this->getPageLoadTime() > 3) {
            $issues[] = [
                'type' => 'warning',
                'title' => 'Tiempo de carga lento',
                'description' => 'La página tarda más de 3 segundos en cargar',
                'recommendation' => 'Considera optimizar consultas de base de datos y usar cache'
            ];
        }
        
        if ($this->getMemoryUsagePercentage() > 80) {
            $issues[] = [
                'type' => 'danger',
                'title' => 'Alto uso de memoria',
                'description' => 'El uso de memoria supera el 80% del límite',
                'recommendation' => 'Revisa plugins que consuman mucha memoria'
            ];
        }
        
        if ($this->getDatabaseQueriesCount() > 50) {
            $issues[] = [
                'type' => 'warning',
                'title' => 'Muchas consultas de BD',
                'description' => 'Se están ejecutando más de 50 consultas por página',
                'recommendation' => 'Optimiza consultas y usa cache de objetos'
            ];
        }
        
        return $issues;
    }
    
    private function getOptimizationRecommendations(): array {
        $recommendations = [];
        
        // Verificar cache
        if (!wp_using_ext_object_cache()) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Implementar cache de objetos',
                'description' => 'Un cache de objetos como Redis mejorará significativamente el rendimiento'
            ];
        }
        
        // Verificar compresión
        if (!$this->isGzipEnabled()) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Habilitar compresión GZIP',
                'description' => 'La compresión GZIP reducirá el tamaño de transferencia'
            ];
        }
        
        return $recommendations;
    }
    
    private function isGzipEnabled(): bool {
        return extension_loaded('zlib') && ini_get('zlib.output_compression');
    }
    
    private function groupSimilarQueries(array $queries): array {
        $grouped = [];
        
        foreach ($queries as $query) {
            // Normalizar la consulta para agrupar similares
            $normalized = preg_replace('/\d+/', 'N', $query['sql']);
            $normalized = preg_replace("/'[^']*'/", "'X'", $normalized);
            
            $key = md5($normalized);
            
            if (isset($grouped[$key])) {
                $grouped[$key]['calls']++;
                $grouped[$key]['total_time'] += $query['time'];
            } else {
                $grouped[$key] = [
                    'sql' => $query['sql'],
                    'calls' => 1,
                    'total_time' => $query['time'],
                    'avg_time' => $query['time'],
                    'function' => $query['function']
                ];
            }
        }
        
        // Calcular promedio
        foreach ($grouped as &$group) {
            $group['avg_time'] = round($group['total_time'] / $group['calls'], 2);
        }
        
        return array_values($grouped);
    }
    
    private function getMemoryBreakdown(): array {
        // Simulación de desglose de memoria
        return [
            'wordpress_core' => rand(10, 20),
            'active_theme' => rand(5, 15),
            'plugins' => rand(20, 40),
            'database' => rand(5, 10),
            'other' => rand(10, 20)
        ];
    }
    
    private function getPluginLoadTime(string $plugin_path): float {
        // Simulación - en un entorno real se mediría el tiempo real
        return rand(1, 50) / 100;
    }
    
    private function getPluginMemoryUsage(string $plugin_path): float {
        // Simulación - en un entorno real se mediría la memoria real
        return rand(1, 10);
    }
    
    private function getPluginHooksCount(string $plugin_path): int {
        // Simulación - en un entorno real se contarían los hooks reales
        return rand(5, 50);
    }
    
    private function calculatePluginImpact(string $plugin_path): int {
        // Cálculo simple de impacto basado en métricas simuladas
        $load_time = $this->getPluginLoadTime($plugin_path);
        $memory = $this->getPluginMemoryUsage($plugin_path);
        $hooks = $this->getPluginHooksCount($plugin_path);
        
        return (int) (($load_time * 10) + ($memory * 5) + ($hooks * 0.5));
    }
    
    private function testResponseTime(): array {
        $start = microtime(true);
        
        // Simular test de respuesta
        $test_url = home_url();
        $response_time = (microtime(true) - $start) * 1000;
        
        return [
            'url' => $test_url,
            'response_time' => round($response_time, 2),
            'status' => $response_time < 1000 ? 'good' : ($response_time < 3000 ? 'average' : 'poor')
        ];
    }
    
    private function testDatabasePerformance(): array {
        global $wpdb;
        
        $start = microtime(true);
        
        // Ejecutar consulta de prueba
        $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish'");
        
        $query_time = (microtime(true) - $start) * 1000;
        
        return [
            'query_time' => round($query_time, 2),
            'status' => $query_time < 50 ? 'excellent' : ($query_time < 100 ? 'good' : 'needs_optimization')
        ];
    }
    
    private function testMemoryEfficiency(): array {
        $start_memory = memory_get_usage();
        
        // Simular operación que consume memoria
        $dummy_data = str_repeat('x', 1024 * 100); // 100KB
        
        $memory_used = memory_get_usage() - $start_memory;
        
        return [
            'memory_used' => round($memory_used / 1024, 2),
            'efficiency' => $memory_used < 102400 ? 'good' : 'poor' // 100KB
        ];
    }
    
    private function testCacheEffectiveness(): array {
        // Test básico de cache
        $cache_key = 'dev_tools_cache_test_' . time();
        $test_data = 'cache_test_data';
        
        // Test de escritura
        $write_start = microtime(true);
        wp_cache_set($cache_key, $test_data, 'dev_tools_test', 3600);
        $write_time = (microtime(true) - $write_start) * 1000;
        
        // Test de lectura
        $read_start = microtime(true);
        $cached_data = wp_cache_get($cache_key, 'dev_tools_test');
        $read_time = (microtime(true) - $read_start) * 1000;
        
        // Limpiar
        wp_cache_delete($cache_key, 'dev_tools_test');
        
        return [
            'write_time' => round($write_time, 2),
            'read_time' => round($read_time, 2),
            'cache_hit' => $cached_data === $test_data,
            'effectiveness' => ($cached_data === $test_data && $read_time < 1) ? 'excellent' : 'poor'
        ];
    }
    
    private function calculateOverallScore(array $metrics): int {
        $score = 100;
        
        // Evaluar cada métrica
        if (isset($metrics['response_time']['status'])) {
            switch ($metrics['response_time']['status']) {
                case 'poor': $score -= 30; break;
                case 'average': $score -= 15; break;
            }
        }
        
        if (isset($metrics['database_performance']['status'])) {
            switch ($metrics['database_performance']['status']) {
                case 'needs_optimization': $score -= 25; break;
                case 'good': $score -= 5; break;
            }
        }
        
        if (isset($metrics['memory_efficiency']['efficiency']) && $metrics['memory_efficiency']['efficiency'] === 'poor') {
            $score -= 20;
        }
        
        if (isset($metrics['cache_effectiveness']['effectiveness']) && $metrics['cache_effectiveness']['effectiveness'] === 'poor') {
            $score -= 15;
        }
        
        return max(0, $score);
    }
    
    private function getPageSpeedSuggestions(): array {
        return [
            'Optimizar imágenes - Usar formatos WebP y compresión',
            'Minimizar CSS y JavaScript',
            'Habilitar compresión GZIP',
            'Usar cache del navegador',
            'Optimizar consultas de base de datos',
            'Reducir el tiempo de respuesta del servidor'
        ];
    }
    
    private function cleanAutoloadOptions(): int {
        global $wpdb;
        
        // Buscar opciones autoload grandes
        $large_autoload = $wpdb->get_results(
            "SELECT option_name, LENGTH(option_value) as size 
             FROM {$wpdb->options} 
             WHERE autoload = 'yes' 
             AND LENGTH(option_value) > 1024 
             ORDER BY size DESC"
        );
        
        $cleaned = 0;
        foreach ($large_autoload as $option) {
            // Solo limpiar opciones específicas que sabemos que pueden ser grandes
            if (strpos($option->option_name, '_transient_') === 0) {
                delete_option($option->option_name);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    private function cleanOldRevisions(): int {
        global $wpdb;
        
        // Eliminar revisiones más antiguas de 30 días
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->posts} 
                 WHERE post_type = 'revision' 
                 AND post_date < %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        return $result ?: 0;
    }
    
    private function cleanSpamAndTrash(): int {
        global $wpdb;
        
        $cleaned = 0;
        
        // Limpiar comentarios spam
        $spam_comments = $wpdb->query(
            "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
        );
        $cleaned += $spam_comments ?: 0;
        
        // Limpiar posts en papelera
        $trash_posts = $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );
        $cleaned += $trash_posts ?: 0;
        
        return $cleaned;
    }
    
    /**
     * Inyectar monitoreo de rendimiento
     */
    public function inject_performance_monitoring(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <script>
        // Monitoreo básico de rendimiento del navegador
        if ('performance' in window) {
            window.devToolsPerformance = {
                navigationStart: performance.timing.navigationStart,
                loadComplete: performance.timing.loadEventEnd,
                domReady: performance.timing.domContentLoadedEventEnd,
                
                getMetrics: function() {
                    return {
                        pageLoadTime: this.loadComplete - this.navigationStart,
                        domReadyTime: this.domReady - this.navigationStart,
                        resourceCount: performance.getEntriesByType('resource').length
                    };
                }
            };
        }
        </script>
        <?php
    }
    
    /**
     * Recopilar datos de rendimiento al final de la ejecución
     */
    public function collect_performance_data(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Guardar datos de rendimiento en un transient para análisis posterior
        $performance_data = [
            'timestamp' => current_time('mysql'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'load_time' => $this->getPageLoadTime(),
            'memory_usage' => $this->getCurrentMemoryUsage(),
            'memory_peak' => $this->getPeakMemoryUsage(),
            'db_queries' => $this->getDatabaseQueriesCount(),
            'db_query_time' => $this->getDatabaseQueryTime()
        ];
        
        // Guardar los últimos 100 registros
        $stored_data = get_transient('dev_tools_performance_log') ?: [];
        array_unshift($stored_data, $performance_data);
        $stored_data = array_slice($stored_data, 0, 100);
        
        set_transient('dev_tools_performance_log', $stored_data, DAY_IN_SECONDS);
    }
    
    // ========================================================================
    // MÉTODOS ABSTRACTOS REQUERIDOS POR DevToolsModuleBase
    // ========================================================================
    
    /**
     * Obtener información del módulo
     */
    public function getModuleInfo(): array {
        return [
            'name' => 'Performance',
            'version' => '3.0.0',
            'description' => 'Módulo de monitoreo y análisis de rendimiento del sitio WordPress',
            'dependencies' => [
                'function:memory_get_usage',
                'function:microtime'
            ],
            'capabilities' => ['manage_options'],
            'author' => 'Dev-Tools Team',
            'tags' => ['performance', 'monitoring', 'optimization']
        ];
    }
    
    /**
     * Inicializar módulo específico
     */
    protected function initializeModule(): bool {
        try {
            // Verificar dependencias específicas del módulo
            if (!function_exists('memory_get_usage') || !function_exists('microtime')) {
                throw new Exception('Required PHP functions not available');
            }
            
            // Inicializar configuración específica
            $this->performance_config = [
                'monitor_queries' => true,
                'monitor_memory' => true,
                'monitor_load_time' => true,
                'cache_duration' => 300,
                'max_data_points' => 50,
                'alert_thresholds' => [
                    'memory_limit' => 90, // % del límite
                    'query_count' => 50,
                    'load_time' => 3.0 // segundos
                ]
            ];
            
            $this->log_external('PerformanceModule initialized successfully', 'info');
            return true;
            
        } catch (Exception $e) {
            $this->log_external('PerformanceModule initialization failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Activar módulo específico
     */
    protected function activateModule(): bool {
        try {
            // Crear tablas o configuraciones necesarias
            $this->createPerformanceLogTable();
            
            // Programar tareas de limpieza
            if (!wp_next_scheduled('dev_tools_performance_cleanup')) {
                wp_schedule_event(time(), 'daily', 'dev_tools_performance_cleanup');
            }
            
            $this->log_external('PerformanceModule activated successfully', 'info');
            return true;
            
        } catch (Exception $e) {
            $this->log_external('PerformanceModule activation failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Desactivar módulo específico
     */
    protected function deactivateModule(): bool {
        try {
            // Cancelar tareas programadas
            wp_clear_scheduled_hook('dev_tools_performance_cleanup');
            
            $this->log_external('PerformanceModule deactivated successfully', 'info');
            return true;
            
        } catch (Exception $e) {
            $this->log_external('PerformanceModule deactivation failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Limpiar recursos del módulo
     */
    protected function cleanupModule(): void {
        try {
            // Limpiar cache y datos temporales
            delete_transient('dev_tools_performance_cache');
            delete_transient('dev_tools_performance_log');
            
            // Limpiar opciones del módulo
            delete_option('dev_tools_performance_settings');
            
            $this->log_external('PerformanceModule cleanup completed', 'info');
            
        } catch (Exception $e) {
            $this->log_external('PerformanceModule cleanup failed: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Validar configuración específica del módulo
     */
    protected function validateModuleConfig(array $config): bool {
        // Validar configuraciones específicas del módulo
        $required_keys = [
            'monitor_queries',
            'monitor_memory', 
            'monitor_load_time',
            'cache_duration',
            'max_data_points'
        ];
        
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $config)) {
                return false;
            }
        }
        
        // Validar tipos y rangos
        if (!is_bool($config['monitor_queries']) ||
            !is_bool($config['monitor_memory']) ||
            !is_bool($config['monitor_load_time']) ||
            !is_int($config['cache_duration']) ||
            !is_int($config['max_data_points']) ||
            $config['cache_duration'] < 60 ||
            $config['max_data_points'] < 10) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtener campos de configuración requeridos
     */
    protected function getRequiredConfigFields(): array {
        return [
            'monitor_queries',
            'monitor_memory',
            'monitor_load_time',
            'cache_duration',
            'max_data_points'
        ];
    }
    
    /**
     * Registrar hooks específicos del módulo
     */
    public function registerHooks(): void {
        // Hook para monitoreo automático
        add_action('wp_footer', [$this, 'logPerformanceData']);
        add_action('admin_footer', [$this, 'logPerformanceData']);
        
        // Hook para limpieza automática
        add_action('dev_tools_performance_cleanup', [$this, 'cleanupOldData']);
        
        // Hook para shutdown del módulo
        register_shutdown_function([$this, 'onShutdown']);
        
        $this->log_external('PerformanceModule hooks registered', 'debug');
    }
    
    /**
     * Crear tabla de log de rendimiento si no existe
     */
    private function createPerformanceLogTable(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dev_tools_performance_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            url varchar(255) NOT NULL,
            load_time decimal(10,4) DEFAULT NULL,
            memory_usage decimal(10,2) DEFAULT NULL,
            memory_peak decimal(10,2) DEFAULT NULL,
            db_queries int(11) DEFAULT NULL,
            db_query_time decimal(10,4) DEFAULT NULL,
            user_agent text,
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY url (url),
            KEY load_time (load_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Limpiar datos antiguos
     */
    public function cleanupOldData(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dev_tools_performance_log';
        
        // Eliminar registros más antiguos de 30 días
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Mantener solo los últimos 10000 registros
        $wpdb->query(
            "DELETE FROM $table_name 
             WHERE id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM $table_name 
                     ORDER BY timestamp DESC 
                     LIMIT 10000
                 ) t
             )"
        );
        
        $this->log_external('Performance data cleanup completed', 'info');
    }
    
    /**
     * Registrar datos de rendimiento actuales en la base de datos
     */
    private function logCurrentPerformance(): void {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'dev_tools_performance_log';
            
            // Calcular tiempo de carga
            $load_time = null;
            if (defined('DEV_TOOLS_START_TIME')) {
                $load_time = microtime(true) - constant('DEV_TOOLS_START_TIME');
            }
            
            // Obtener datos de memoria
            $memory_usage = memory_get_usage(true) / 1024 / 1024; // MB
            $memory_peak = memory_get_peak_usage(true) / 1024 / 1024; // MB
            
            // Obtener datos de consultas de base de datos
            $db_queries = null;
            $db_query_time = null;
            if (defined('SAVEQUERIES') && constant('SAVEQUERIES')) {
                $db_queries = get_num_queries();
                if (isset($GLOBALS['wpdb']->queries)) {
                    $total_time = 0;
                    foreach ($GLOBALS['wpdb']->queries as $query) {
                        $total_time += $query[1];
                    }
                    $db_query_time = $total_time;
                }
            }
            
            // Obtener URL actual
            $url = '';
            if (isset($_SERVER['REQUEST_URI'])) {
                $url = sanitize_text_field($_SERVER['REQUEST_URI']);
                $url = substr($url, 0, 255); // Limitar longitud
            }
            
            // Obtener User Agent
            $user_agent = '';
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
            }
            
            // Obtener IP
            $ip_address = '';
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            }
            
            // Insertar datos en la base de datos
            $result = $wpdb->insert(
                $table_name,
                [
                    'url' => $url,
                    'load_time' => $load_time,
                    'memory_usage' => $memory_usage,
                    'memory_peak' => $memory_peak,
                    'db_queries' => $db_queries,
                    'db_query_time' => $db_query_time,
                    'user_agent' => $user_agent,
                    'ip_address' => $ip_address,
                ],
                [
                    '%s', // url
                    '%f', // load_time
                    '%f', // memory_usage
                    '%f', // memory_peak
                    '%d', // db_queries
                    '%f', // db_query_time
                    '%s', // user_agent
                    '%s', // ip_address
                ]
            );
            
            if ($result === false) {
                error_log('Dev-Tools Performance: Failed to log performance data - ' . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            error_log('Dev-Tools Performance: Error logging performance data - ' . $e->getMessage());
        }
    }
    
    /**
     * Ejecutar al finalizar la request
     */
    public function onShutdown(): void {
        // Log final de rendimiento si está habilitado
        if (defined('DEV_TOOLS_PERFORMANCE_LOG') && constant('DEV_TOOLS_PERFORMANCE_LOG')) {
            $this->logCurrentPerformance();
        }
    }
}
