<?php
/**
 * Logs Module - Visualización y gestión de logs
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class LogsModule extends DevToolsModuleBase {
    
    private $log_sources = [];
    private $max_log_entries = 1000;
    
    /**
     * Obtener información del módulo
     */
    public function getModuleInfo(): array {
        return [
            'name' => 'Logs',
            'version' => '3.0.0',
            'description' => 'Visualización y gestión avanzada de logs del sistema',
            'dependencies' => [],
            'capabilities' => ['manage_options']
        ];
    }

    /**
     * Inicialización específica del módulo
     */
    protected function initializeModule(): bool {
        // Registrar comandos AJAX específicos
        $this->register_ajax_command('get_logs', [$this, 'handle_get_logs']);
        $this->register_ajax_command('clear_logs', [$this, 'handle_clear_logs']);
        $this->register_ajax_command('export_logs', [$this, 'handle_export_logs']);
        $this->register_ajax_command('get_log_sources', [$this, 'handle_get_log_sources']);
        $this->register_ajax_command('tail_logs', [$this, 'handle_tail_logs']);
        $this->register_ajax_command('search_logs', [$this, 'handle_search_logs']);
        
        // Configurar fuentes de logs
        $this->setup_log_sources();
        
        $this->log_internal('LogsModule initialized');
        return true;
    }
    
    /**
     * Registrar hooks de WordPress
     */
    public function registerHooks(): void {
        // Hook para capturar errores PHP
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_log', [$this, 'capture_wp_log'], 10, 2);
        }
    }
    
    /**
     * Registrar comandos AJAX del módulo
     */
    public function registerAjaxCommands(DevToolsAjaxHandler $ajaxHandler): void {
        $ajaxHandler->registerCommand('get_logs', [$this, 'handle_get_logs']);
        $ajaxHandler->registerCommand('clear_logs', [$this, 'handle_clear_logs']);
        $ajaxHandler->registerCommand('export_logs', [$this, 'handle_export_logs']);
        $ajaxHandler->registerCommand('get_log_sources', [$this, 'handle_get_log_sources']);
        $ajaxHandler->registerCommand('tail_logs', [$this, 'handle_tail_logs']);
        $ajaxHandler->registerCommand('search_logs', [$this, 'handle_search_logs']);
    }
    
    /**
     * Configuración del módulo
     */
    protected function get_module_config(): array {
        return [
            'id' => 'logs',
            'name' => 'Logs Manager',
            'description' => 'Visualización y gestión avanzada de logs del sistema',
            'version' => '3.0.0',
            'author' => 'Dev-Tools Team',
            'icon' => 'fas fa-file-alt',
            'priority' => 40,
            'dependencies' => [],
            'ajax_actions' => [
                'get_logs',
                'clear_logs',
                'export_logs',
                'get_log_sources',
                'tail_logs',
                'search_logs'
            ]
        ];
    }

    /**
     * Renderizado del panel del módulo
     */
    public function render_panel(): string {
        ob_start();
        ?>
        <div class="dev-tools-module" id="logs-module">
            <div class="module-header d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-file-alt me-2"></i>Logs Manager</h4>
                <div class="module-actions">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="DevToolsLogs.refreshLogs()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="DevToolsLogs.exportLogs()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="DevToolsLogs.tailLogs()">
                            <i class="fas fa-eye"></i> Tail
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="DevToolsLogs.clearLogs()">
                            <i class="fas fa-trash"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Controls Panel -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Log Source -->
                                <div class="col-md-3">
                                    <label for="log-source" class="form-label">Log Source</label>
                                    <select class="form-select" id="log-source" onchange="DevToolsLogs.changeSource()">
                                        <option value="all">All Logs</option>
                                        <option value="dev-tools">Dev-Tools</option>
                                        <option value="wordpress">WordPress</option>
                                        <option value="php">PHP Errors</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <!-- Log Level -->
                                <div class="col-md-2">
                                    <label for="log-level" class="form-label">Level</label>
                                    <select class="form-select" id="log-level" onchange="DevToolsLogs.filterLogs()">
                                        <option value="all">All Levels</option>
                                        <option value="error">Error</option>
                                        <option value="warning">Warning</option>
                                        <option value="info">Info</option>
                                        <option value="debug">Debug</option>
                                    </select>
                                </div>

                                <!-- Time Range -->
                                <div class="col-md-3">
                                    <label for="time-range" class="form-label">Time Range</label>
                                    <select class="form-select" id="time-range" onchange="DevToolsLogs.filterLogs()">
                                        <option value="1h">Last Hour</option>
                                        <option value="6h">Last 6 Hours</option>
                                        <option value="24h">Last 24 Hours</option>
                                        <option value="7d">Last 7 Days</option>
                                        <option value="all">All Time</option>
                                    </select>
                                </div>

                                <!-- Search -->
                                <div class="col-md-4">
                                    <label for="log-search" class="form-label">Search</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="log-search" 
                                               placeholder="Search in logs..." onkeyup="DevToolsLogs.searchLogs()">
                                        <button class="btn btn-outline-secondary" type="button" onclick="DevToolsLogs.clearSearch()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto-refresh toggle -->
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto-refresh" onchange="DevToolsLogs.toggleAutoRefresh()">
                                        <label class="form-check-label" for="auto-refresh">
                                            Auto-refresh (5s)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 id="total-logs" class="card-title text-primary">0</h5>
                            <p class="card-text small">Total Logs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 id="error-logs" class="card-title text-danger">0</h5>
                            <p class="card-text small">Errors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 id="warning-logs" class="card-title text-warning">0</h5>
                            <p class="card-text small">Warnings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 id="info-logs" class="card-title text-info">0</h5>
                            <p class="card-text small">Info/Debug</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Display -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Log Entries</h6>
                            <div class="logs-info">
                                <small class="text-muted">
                                    Showing <span id="visible-logs">0</span> of <span id="filtered-logs">0</span> entries
                                </small>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Loading State -->
                            <div id="logs-loading" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading logs...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading log entries...</p>
                            </div>

                            <!-- Logs Content -->
                            <div id="logs-content" class="d-none">
                                <div id="logs-table-container" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th style="width: 140px;">Timestamp</th>
                                                <th style="width: 80px;">Level</th>
                                                <th style="width: 100px;">Source</th>
                                                <th>Message</th>
                                                <th style="width: 60px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="logs-table-body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div id="logs-empty" class="text-center py-4 text-muted d-none">
                                <i class="fas fa-file-alt fa-2x mb-3"></i>
                                <p>No log entries found matching the current filters.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log Detail Modal -->
            <div class="modal fade" id="logDetailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Log Entry Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="log-detail-content"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tail Modal -->
            <div class="modal fade" id="tailModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Live Log Tail</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="tail-content" style="height: 500px; overflow-y: auto; background: #000; color: #fff; padding: 1rem; font-family: monospace;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Connecting to log tail...
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
     * Configurar fuentes de logs
     */
    private function setup_log_sources(): void {
        $this->log_sources = [
            'dev-tools' => [
                'name' => 'Dev-Tools',
                'file' => WP_CONTENT_DIR . '/debug-dev-tools.log',
                'format' => 'dev_tools'
            ],
            'wordpress' => [
                'name' => 'WordPress',
                'file' => WP_CONTENT_DIR . '/debug.log',
                'format' => 'wordpress'
            ],
            'php' => [
                'name' => 'PHP Errors',
                'file' => ini_get('error_log'),
                'format' => 'php'
            ]
        ];
    }

    /**
     * Obtener logs
     */
    public function handle_get_logs(): array {
        try {
            $source = sanitize_text_field($_POST['source'] ?? 'all');
            $level = sanitize_text_field($_POST['level'] ?? 'all');
            $time_range = sanitize_text_field($_POST['time_range'] ?? '24h');
            $search = sanitize_text_field($_POST['search'] ?? '');
            $limit = intval($_POST['limit'] ?? 100);

            $logs = $this->collect_logs($source, $level, $time_range, $search, $limit);
            $stats = $this->calculate_log_stats($logs);

            return [
                'success' => true,
                'logs' => $logs,
                'stats' => $stats,
                'total' => count($logs)
            ];

        } catch (Exception $e) {
            $this->log_external('Failed to get logs: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Failed to retrieve logs: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recopilar logs de diferentes fuentes
     */
    private function collect_logs($source, $level, $time_range, $search, $limit): array {
        $logs = [];

        // Determinar fuentes a consultar
        $sources_to_check = ($source === 'all') ? array_keys($this->log_sources) : [$source];

        foreach ($sources_to_check as $source_key) {
            if (!isset($this->log_sources[$source_key])) continue;

            $source_logs = $this->read_log_file($source_key, $time_range, $limit);
            $logs = array_merge($logs, $source_logs);
        }

        // Aplicar filtros
        $logs = $this->filter_logs($logs, $level, $search);

        // Ordenar por timestamp (más recientes primero)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Limitar resultados
        if (count($logs) > $limit) {
            $logs = array_slice($logs, 0, $limit);
        }

        return $logs;
    }

    /**
     * Leer archivo de log
     */
    private function read_log_file($source_key, $time_range, $limit): array {
        $source = $this->log_sources[$source_key];
        $log_file = $source['file'];

        if (!file_exists($log_file) || !is_readable($log_file)) {
            return [];
        }

        $logs = [];
        $cutoff_time = $this->get_cutoff_time($time_range);

        // Leer archivo línea por línea (desde el final para optimizar)
        $file = new SplFileObject($log_file);
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        // Leer las últimas N líneas
        $start_line = max(0, $total_lines - ($limit * 2)); // Buffer extra
        $file->seek($start_line);

        while (!$file->eof() && count($logs) < $limit) {
            $line = trim($file->fgets());
            if (empty($line)) continue;

            $parsed = $this->parse_log_line($line, $source['format'], $source_key);
            if (!$parsed) continue;

            // Filtro de tiempo
            if ($cutoff_time && strtotime($parsed['timestamp']) < $cutoff_time) {
                continue;
            }

            $logs[] = $parsed;
        }

        return $logs;
    }

    /**
     * Parsear línea de log
     */
    private function parse_log_line($line, $format, $source): ?array {
        switch ($format) {
            case 'dev_tools':
                return $this->parse_dev_tools_log($line, $source);
            case 'wordpress':
                return $this->parse_wordpress_log($line, $source);
            case 'php':
                return $this->parse_php_log($line, $source);
            default:
                return $this->parse_generic_log($line, $source);
        }
    }

    /**
     * Parsear log de dev-tools
     */
    private function parse_dev_tools_log($line, $source): ?array {
        // Formato: [2025-01-08 15:30:45] [INFO] Module loaded: DashboardModule
        if (preg_match('/^\[([^\]]+)\]\s*\[([^\]]+)\]\s*(.+)$/', $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => strtolower($matches[2]),
                'message' => $matches[3],
                'source' => $source,
                'raw' => $line
            ];
        }
        return null;
    }

    /**
     * Parsear log de WordPress
     */
    private function parse_wordpress_log($line, $source): ?array {
        // Formato: [08-Jan-2025 15:30:45 UTC] PHP Warning: ...
        if (preg_match('/^\[([^\]]+)\]\s*(.+)$/', $line, $matches)) {
            $message = $matches[2];
            $level = 'info';

            // Detectar nivel del mensaje
            if (stripos($message, 'error') !== false || stripos($message, 'fatal') !== false) {
                $level = 'error';
            } elseif (stripos($message, 'warning') !== false) {
                $level = 'warning';
            } elseif (stripos($message, 'notice') !== false) {
                $level = 'debug';
            }

            return [
                'timestamp' => $matches[1],
                'level' => $level,
                'message' => $message,
                'source' => $source,
                'raw' => $line
            ];
        }
        return null;
    }

    /**
     * Parsear log de PHP
     */
    private function parse_php_log($line, $source): ?array {
        // Similar a WordPress log
        return $this->parse_wordpress_log($line, $source);
    }

    /**
     * Parsear log genérico
     */
    private function parse_generic_log($line, $source): array {
        return [
            'timestamp' => current_time('mysql'),
            'level' => 'info',
            'message' => $line,
            'source' => $source,
            'raw' => $line
        ];
    }

    /**
     * Filtrar logs
     */
    private function filter_logs($logs, $level, $search): array {
        return array_filter($logs, function($log) use ($level, $search) {
            // Filtro de nivel
            if ($level !== 'all' && $log['level'] !== $level) {
                return false;
            }

            // Filtro de búsqueda
            if (!empty($search)) {
                $search_lower = strtolower($search);
                $message_lower = strtolower($log['message']);
                if (strpos($message_lower, $search_lower) === false) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Calcular estadísticas de logs
     */
    private function calculate_log_stats($logs): array {
        $stats = [
            'total' => count($logs),
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'debug' => 0
        ];

        foreach ($logs as $log) {
            $level = $log['level'];
            if (isset($stats[$level])) {
                $stats[$level]++;
            } else {
                $stats['info']++; // Default
            }
        }

        return $stats;
    }

    /**
     * Obtener tiempo de corte para filtro temporal
     */
    private function get_cutoff_time($time_range): ?int {
        $now = time();

        switch ($time_range) {
            case '1h':
                return $now - 3600;
            case '6h':
                return $now - (6 * 3600);
            case '24h':
                return $now - (24 * 3600);
            case '7d':
                return $now - (7 * 24 * 3600);
            case 'all':
            default:
                return null;
        }
    }

    /**
     * Limpiar logs
     */
    public function handle_clear_logs(): array {
        try {
            $source = sanitize_text_field($_POST['source'] ?? 'all');
            $confirm = $_POST['confirm'] ?? false;

            if (!$confirm) {
                return [
                    'success' => false,
                    'message' => 'Confirmation required'
                ];
            }

            $sources_to_clear = ($source === 'all') ? array_keys($this->log_sources) : [$source];
            $cleared = [];

            foreach ($sources_to_clear as $source_key) {
                if (!isset($this->log_sources[$source_key])) continue;

                $log_file = $this->log_sources[$source_key]['file'];
                if (file_exists($log_file) && is_writable($log_file)) {
                    file_put_contents($log_file, '');
                    $cleared[] = $source_key;
                }
            }

            $this->log_external('Logs cleared: ' . implode(', ', $cleared));

            return [
                'success' => true,
                'message' => 'Logs cleared successfully',
                'cleared' => $cleared
            ];

        } catch (Exception $e) {
            $this->log_external('Failed to clear logs: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Failed to clear logs: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Exportar logs
     */
    public function handle_export_logs(): array {
        try {
            $source = sanitize_text_field($_POST['source'] ?? 'all');
            $format = sanitize_text_field($_POST['format'] ?? 'json');

            $logs = $this->collect_logs($source, 'all', 'all', '', 10000);

            $filename = "dev-tools-logs-" . date('Y-m-d-H-i-s') . ".{$format}";
            $content = '';

            switch ($format) {
                case 'json':
                    $content = json_encode($logs, JSON_PRETTY_PRINT);
                    break;
                case 'csv':
                    $content = $this->logs_to_csv($logs);
                    break;
                case 'txt':
                default:
                    $content = $this->logs_to_text($logs);
                    break;
            }

            return [
                'success' => true,
                'filename' => $filename,
                'content' => base64_encode($content),
                'mime_type' => $this->get_mime_type($format)
            ];

        } catch (Exception $e) {
            $this->log_external('Failed to export logs: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Failed to export logs: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convertir logs a CSV
     */
    private function logs_to_csv($logs): string {
        $csv = "Timestamp,Level,Source,Message\n";
        foreach ($logs as $log) {
            $csv .= sprintf('"%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $log['timestamp']),
                str_replace('"', '""', $log['level']),
                str_replace('"', '""', $log['source']),
                str_replace('"', '""', $log['message'])
            );
        }
        return $csv;
    }

    /**
     * Convertir logs a texto
     */
    private function logs_to_text($logs): string {
        $text = '';
        foreach ($logs as $log) {
            $text .= sprintf("[%s] [%s] [%s] %s\n",
                $log['timestamp'],
                strtoupper($log['level']),
                $log['source'],
                $log['message']
            );
        }
        return $text;
    }

    /**
     * Obtener MIME type
     */
    private function get_mime_type($format): string {
        switch ($format) {
            case 'json':
                return 'application/json';
            case 'csv':
                return 'text/csv';
            case 'txt':
            default:
                return 'text/plain';
        }
    }

    /**
     * Obtener fuentes de logs
     */
    public function handle_get_log_sources(): array {
        $sources = [];
        foreach ($this->log_sources as $key => $source) {
            $sources[$key] = [
                'name' => $source['name'],
                'exists' => file_exists($source['file']),
                'readable' => file_exists($source['file']) && is_readable($source['file']),
                'size' => file_exists($source['file']) ? filesize($source['file']) : 0
            ];
        }

        return [
            'success' => true,
            'sources' => $sources
        ];
    }

    /**
     * Tail de logs en tiempo real
     */
    public function handle_tail_logs(): array {
        try {
            $source = sanitize_text_field($_POST['source'] ?? 'dev-tools');
            $lines = intval($_POST['lines'] ?? 50);

            if (!isset($this->log_sources[$source])) {
                return [
                    'success' => false,
                    'message' => 'Invalid log source'
                ];
            }

            $log_file = $this->log_sources[$source]['file'];
            if (!file_exists($log_file)) {
                return [
                    'success' => true,
                    'content' => "Log file does not exist: {$log_file}"
                ];
            }

            // Leer últimas N líneas
            $content = $this->tail_file($log_file, $lines);

            return [
                'success' => true,
                'content' => $content,
                'file' => $log_file
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to tail logs: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Función tail para archivos
     */
    private function tail_file($file, $lines): string {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return "Cannot open file: {$file}";
        }

        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $buffer[] = rtrim($line);
            if (count($buffer) > $lines) {
                array_shift($buffer);
            }
        }

        fclose($handle);
        return implode("\n", $buffer);
    }

    /**
     * Búsqueda en logs
     */
    public function handle_search_logs(): array {
        try {
            $query = sanitize_text_field($_POST['query'] ?? '');
            $source = sanitize_text_field($_POST['source'] ?? 'all');

            if (empty($query)) {
                return [
                    'success' => false,
                    'message' => 'Search query is required'
                ];
            }

            $logs = $this->collect_logs($source, 'all', 'all', $query, 500);

            return [
                'success' => true,
                'logs' => $logs,
                'total' => count($logs),
                'query' => $query
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Capturar logs de WordPress
     */
    public function capture_wp_log($message, $level = 'info'): void {
        // Este método puede ser usado para interceptar logs de WordPress
        $this->log_external("WP Log: {$message}", $level);
    }

    /**
     * Activación específica del módulo
     */
    protected function activateModule(): bool {
        $this->log_external('LogsModule activated');
        return true;
    }

    /**
     * Desactivación específica del módulo
     */
    protected function deactivateModule(): bool {
        $this->log_external('LogsModule deactivated');
        return true;
    }

    /**
     * Limpieza específica del módulo
     */
    protected function cleanupModule(): void {
        $this->log_external('LogsModule cleaned up');
    }

    /**
     * Validación específica de configuración
     */
    protected function validateModuleConfig(array $config): bool {
        return true;
    }

    /**
     * Campos de configuración requeridos
     */
    protected function getRequiredConfigFields(): array {
        return [];
    }
}
