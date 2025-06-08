<?php
/**
 * Cache Module - Gestión avanzada de caché
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class CacheModule extends DevToolsModuleBase {
    
    /**
     * Obtener información del módulo
     */
    public function getModuleInfo(): array {
        return [
            'name' => 'Cache',
            'version' => '3.0.0',
            'description' => 'Gestión avanzada de caché WordPress: transients, object cache, opciones',
            'dependencies' => [],
            'capabilities' => ['manage_options']
        ];
    }

    /**
     * Inicialización específica del módulo
     */
    protected function initializeModule(): bool {
        // Registrar comandos AJAX específicos
        $this->register_ajax_command('get_cache_stats', [$this, 'handle_get_cache_stats']);
        $this->register_ajax_command('clear_cache', [$this, 'handle_clear_cache']);
        $this->register_ajax_command('clear_transients', [$this, 'handle_clear_transients']);
        $this->register_ajax_command('clear_object_cache', [$this, 'handle_clear_object_cache']);
        $this->register_ajax_command('get_transients', [$this, 'handle_get_transients']);
        $this->register_ajax_command('delete_transient', [$this, 'handle_delete_transient']);
        $this->register_ajax_command('set_transient', [$this, 'handle_set_transient']);
        
        $this->log_internal('CacheModule initialized');
        return true;
    }
    
    /**
     * Registrar hooks de WordPress
     */
    public function registerHooks(): void {
        // No se requieren hooks específicos para este módulo
    }
    
    /**
     * Registrar comandos AJAX
     */
    public function registerAjaxCommands(DevToolsAjaxHandler $ajaxHandler): void {
        $ajaxHandler->registerCommand('get_cache_stats', [$this, 'handle_get_cache_stats']);
        $ajaxHandler->registerCommand('clear_cache', [$this, 'handle_clear_cache']);
        $ajaxHandler->registerCommand('clear_transients', [$this, 'handle_clear_transients']);
        $ajaxHandler->registerCommand('clear_object_cache', [$this, 'handle_clear_object_cache']);
        $ajaxHandler->registerCommand('get_transients', [$this, 'handle_get_transients']);
        $ajaxHandler->registerCommand('delete_transient', [$this, 'handle_delete_transient']);
        $ajaxHandler->registerCommand('set_transient', [$this, 'handle_set_transient']);
    }
    
    /**
     * Activación específica del módulo
     */
    protected function activateModule(): bool {
        $this->log_external('CacheModule activated');
        return true;
    }
    
    /**
     * Desactivación específica del módulo
     */
    protected function deactivateModule(): bool {
        $this->log_external('CacheModule deactivated');
        return true;
    }
    
    /**
     * Limpieza específica del módulo
     */
    protected function cleanupModule(): void {
        $this->log_external('CacheModule cleaned up');
    }
    
    /**
     * Validación específica de configuración
     */
    protected function validateModuleConfig(array $config): bool {
        return true; // No hay validaciones específicas
    }
    
    /**
     * Campos de configuración requeridos
     */
    protected function getRequiredConfigFields(): array {
        return []; // No hay campos requeridos
    }

    /**
     * Configuración del módulo (para compatibilidad)
     */
    protected function get_module_config(): array {
        return [
            'id' => 'cache',
            'name' => 'Cache Management',
            'description' => 'Gestión avanzada de caché WordPress: transients, object cache, opciones',
            'version' => '3.0.0',
            'author' => 'Dev-Tools Team',
            'icon' => 'fas fa-memory',
            'priority' => 30,
            'dependencies' => [],
            'ajax_actions' => [
                'get_cache_stats',
                'clear_cache',
                'clear_transients',
                'clear_object_cache',
                'get_transients',
                'delete_transient',
                'set_transient'
            ]
        ];
    }

    /**
     * Inicialización del módulo (mantenido para compatibilidad)
     */
    public function init(): void {
        // Delegado a initializeModule() via parent
    }

    /**
     * Renderizado del panel del módulo
     */
    public function render_panel(): string {
        ob_start();
        ?>
        <div class="dev-tools-module" id="cache-module">
            <div class="module-header d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-memory me-2"></i>Cache Management</h4>
                <div class="module-actions">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="DevToolsCache.refreshStats()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="DevToolsCache.clearAllCache()">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="cache-loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando estadísticas de caché...</span>
                </div>
                <p class="mt-2 text-muted">Analizando caché del sistema...</p>
            </div>

            <!-- Cache Stats Content -->
            <div id="cache-content" class="d-none">
                <!-- Cache Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary">
                                    <i class="fas fa-clock"></i>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">Transients</h6>
                                <h4 id="transients-count" class="text-primary">-</h4>
                                <button class="btn btn-outline-warning btn-sm" onclick="DevToolsCache.clearTransients()">
                                    <i class="fas fa-trash"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success">
                                    <i class="fas fa-server"></i>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">Object Cache</h6>
                                <h4 id="object-cache-status" class="text-success">-</h4>
                                <button class="btn btn-outline-warning btn-sm" onclick="DevToolsCache.clearObjectCache()">
                                    <i class="fas fa-refresh"></i> Flush
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-info">
                                    <i class="fas fa-database"></i>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">Options</h6>
                                <h4 id="autoload-options" class="text-info">-</h4>
                                <button class="btn btn-outline-info btn-sm" onclick="DevToolsCache.viewOptions()">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning">
                                    <i class="fas fa-chart-pie"></i>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">Total Size</h6>
                                <h4 id="total-cache-size" class="text-warning">-</h4>
                                <button class="btn btn-outline-primary btn-sm" onclick="DevToolsCache.analyzeSize()">
                                    <i class="fas fa-search"></i> Analyze
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transients Management -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Transients Management</h6>
                        <div>
                            <button class="btn btn-outline-success btn-sm" onclick="DevToolsCache.showAddTransient()">
                                <i class="fas fa-plus"></i> Add
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="DevToolsCache.refreshTransients()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search and Filter -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="transient-search" placeholder="Buscar transients..." onkeyup="DevToolsCache.filterTransients()">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="transient-filter" onchange="DevToolsCache.filterTransients()">
                                    <option value="">Todos</option>
                                    <option value="expired">Expirados</option>
                                    <option value="permanent">Permanentes</option>
                                    <option value="dev_tools">Dev-Tools</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show-expired" onchange="DevToolsCache.filterTransients()">
                                    <label class="form-check-label" for="show-expired">
                                        Mostrar expirados
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Transients Table -->
                        <div class="table-responsive">
                            <table class="table table-sm" id="transients-table">
                                <thead>
                                    <tr>
                                        <th>Transient</th>
                                        <th>Expiration</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transients-list">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Cache Analysis -->
                <div class="card mb-4" id="cache-analysis" style="display: none;">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Cache Analysis</h6>
                    </div>
                    <div class="card-body">
                        <div id="cache-analysis-content">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Transient Modal -->
            <div class="modal fade" id="addTransientModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Transient</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="add-transient-form">
                                <div class="mb-3">
                                    <label for="transient-name" class="form-label">Transient Name</label>
                                    <input type="text" class="form-control" id="transient-name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="transient-value" class="form-label">Value (JSON)</label>
                                    <textarea class="form-control" id="transient-value" rows="4" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="transient-expiration" class="form-label">Expiration (seconds, 0 = permanent)</label>
                                    <input type="number" class="form-control" id="transient-expiration" value="3600" min="0">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="DevToolsCache.saveTransient()">Save Transient</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Scripts específicos del módulo
     */
    public function get_module_scripts(): array {
        return [
            'cache' => [
                'file' => 'cache.js',
                'deps' => ['dev-tools-core'],
                'version' => '3.0.0'
            ]
        ];
    }

    /**
     * Estilos específicos del módulo
     */
    public function get_module_styles(): array {
        return [
            'cache' => [
                'file' => 'cache.css',
                'deps' => ['dev-tools-core'],
                'version' => '3.0.0'
            ]
        ];
    }

    /**
     * Maneja petición de estadísticas de caché
     */
    public function handle_get_cache_stats(): array {
        $this->log_internal('Getting cache statistics');

        try {
            $stats = [
                'transients' => $this->get_transients_stats(),
                'object_cache' => $this->get_object_cache_stats(),
                'options' => $this->get_options_stats(),
                'size_analysis' => $this->get_size_analysis(),
                'timestamp' => current_time('c')
            ];

            $this->log_external('Cache statistics collected successfully');

            return [
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de caché recopiladas exitosamente'
            ];

        } catch (Exception $e) {
            $this->log_external('Error getting cache stats: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al recopilar estadísticas de caché: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja limpieza de caché completa
     */
    public function handle_clear_cache(): array {
        $this->log_internal('Clearing all cache');

        try {
            $results = [];
            
            // Limpiar transients
            $transients_result = $this->clear_all_transients();
            $results['transients'] = $transients_result;
            
            // Limpiar object cache
            $object_cache_result = wp_cache_flush();
            $results['object_cache'] = $object_cache_result;
            
            // Limpiar opciones de autoload problemáticas
            $options_result = $this->optimize_autoload_options();
            $results['options'] = $options_result;

            $this->log_external('All cache cleared successfully');

            return [
                'success' => true,
                'data' => $results,
                'message' => 'Toda la caché limpiada exitosamente'
            ];

        } catch (Exception $e) {
            $this->log_external('Error clearing cache: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al limpiar caché: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja limpieza específica de transients
     */
    public function handle_clear_transients(): array {
        $this->log_internal('Clearing transients');

        try {
            $result = $this->clear_all_transients();

            $this->log_external('Transients cleared successfully');

            return [
                'success' => true,
                'data' => $result,
                'message' => "Transients limpiados: {$result['deleted']} eliminados"
            ];

        } catch (Exception $e) {
            $this->log_external('Error clearing transients: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al limpiar transients: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja limpieza de object cache
     */
    public function handle_clear_object_cache(): array {
        $this->log_internal('Clearing object cache');

        try {
            $result = wp_cache_flush();

            $this->log_external('Object cache cleared successfully');

            return [
                'success' => true,
                'data' => ['flushed' => $result],
                'message' => $result ? 'Object cache limpiado exitosamente' : 'Object cache no disponible o ya estaba limpio'
            ];

        } catch (Exception $e) {
            $this->log_external('Error clearing object cache: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al limpiar object cache: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja obtención de transients
     */
    public function handle_get_transients(): array {
        $this->log_internal('Getting transients list');

        try {
            $transients = $this->get_all_transients();

            $this->log_external('Transients list retrieved successfully');

            return [
                'success' => true,
                'data' => $transients,
                'message' => 'Lista de transients obtenida exitosamente'
            ];

        } catch (Exception $e) {
            $this->log_external('Error getting transients: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al obtener transients: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja eliminación de transient específico
     */
    public function handle_delete_transient(): array {
        $transient_name = sanitize_text_field($_POST['transient_name'] ?? '');
        
        if (empty($transient_name)) {
            return [
                'success' => false,
                'message' => 'Nombre de transient requerido'
            ];
        }

        $this->log_internal('Deleting transient: ' . $transient_name);

        try {
            $result = delete_transient($transient_name);

            $this->log_external('Transient deleted: ' . $transient_name);

            return [
                'success' => true,
                'data' => ['deleted' => $result],
                'message' => $result ? 'Transient eliminado exitosamente' : 'Transient no encontrado o ya eliminado'
            ];

        } catch (Exception $e) {
            $this->log_external('Error deleting transient: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al eliminar transient: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja creación de transient
     */
    public function handle_set_transient(): array {
        $transient_name = sanitize_text_field($_POST['transient_name'] ?? '');
        $transient_value = $_POST['transient_value'] ?? '';
        $expiration = intval($_POST['expiration'] ?? 3600);
        
        if (empty($transient_name) || empty($transient_value)) {
            return [
                'success' => false,
                'message' => 'Nombre y valor de transient requeridos'
            ];
        }

        $this->log_internal('Setting transient: ' . $transient_name);

        try {
            // Intentar decodificar JSON, si falla usar como string
            $decoded_value = json_decode($transient_value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $transient_value = $decoded_value;
            }

            $result = set_transient($transient_name, $transient_value, $expiration);

            $this->log_external('Transient set: ' . $transient_name);

            return [
                'success' => true,
                'data' => ['set' => $result],
                'message' => 'Transient creado exitosamente'
            ];

        } catch (Exception $e) {
            $this->log_external('Error setting transient: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al crear transient: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene estadísticas de transients
     */
    private function get_transients_stats(): array {
        global $wpdb;
        
        $transients = $wpdb->get_results(
            "SELECT option_name, option_value 
             FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_%' 
             OR option_name LIKE '_transient_timeout_%'"
        );

        $count = 0;
        $expired_count = 0;
        $total_size = 0;
        
        foreach ($transients as $transient) {
            if (strpos($transient->option_name, '_transient_timeout_') === 0) {
                continue; // Skip timeout entries for counting
            }
            
            $count++;
            $total_size += strlen($transient->option_value);
            
            // Check if expired
            $timeout_name = str_replace('_transient_', '_transient_timeout_', $transient->option_name);
            $timeout = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                $timeout_name
            ));
            
            if ($timeout && $timeout < time()) {
                $expired_count++;
            }
        }

        return [
            'total' => $count,
            'expired' => $expired_count,
            'size' => $total_size,
            'size_formatted' => size_format($total_size)
        ];
    }

    /**
     * Obtiene estadísticas de object cache
     */
    private function get_object_cache_stats(): array {
        global $wp_object_cache;
        
        $status = 'Unknown';
        $info = [];
        
        // Verificar si hay object cache activo
        if (is_object($wp_object_cache)) {
            $status = 'Internal';
            $info['cache_hits'] = $wp_object_cache->cache_hits ?? 0;
            $info['cache_misses'] = $wp_object_cache->cache_misses ?? 0;
            
            // Detectar sistemas de cache externos
            if (function_exists('wp_cache_flush') && wp_cache_flush() !== false) {
                $status = 'External';
            }
        }

        return [
            'status' => $status,
            'info' => $info
        ];
    }

    /**
     * Obtiene estadísticas de options
     */
    private function get_options_stats(): array {
        global $wpdb;
        
        $autoload_options = $wpdb->get_results(
            "SELECT option_name, LENGTH(option_value) as size 
             FROM {$wpdb->options} 
             WHERE autoload = 'yes' 
             ORDER BY size DESC 
             LIMIT 20"
        );

        $total_autoload_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) 
             FROM {$wpdb->options} 
             WHERE autoload = 'yes'"
        );

        return [
            'autoload_count' => count($autoload_options),
            'autoload_size' => $total_autoload_size,
            'autoload_size_formatted' => size_format($total_autoload_size),
            'largest_options' => $autoload_options
        ];
    }

    /**
     * Obtiene análisis de tamaño
     */
    private function get_size_analysis(): array {
        $transients = $this->get_transients_stats();
        $options = $this->get_options_stats();
        
        $total_size = $transients['size'] + $options['autoload_size'];
        
        return [
            'total_size' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'breakdown' => [
                'transients' => $transients['size'],
                'autoload_options' => $options['autoload_size']
            ]
        ];
    }

    /**
     * Obtiene todos los transients
     */
    private function get_all_transients(): array {
        global $wpdb;
        
        $transients = $wpdb->get_results(
            "SELECT option_name, option_value, LENGTH(option_value) as size
             FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_%' 
             AND option_name NOT LIKE '_transient_timeout_%'
             ORDER BY option_name"
        );

        $result = [];
        
        foreach ($transients as $transient) {
            $name = str_replace('_transient_', '', $transient->option_name);
            
            // Get expiration
            $timeout_name = '_transient_timeout_' . $name;
            $timeout = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                $timeout_name
            ));
            
            $expiration = null;
            $is_expired = false;
            
            if ($timeout) {
                $expiration = $timeout;
                $is_expired = $timeout < time();
            }
            
            $result[] = [
                'name' => $name,
                'size' => $transient->size,
                'size_formatted' => size_format($transient->size),
                'expiration' => $expiration,
                'expiration_formatted' => $expiration ? date('Y-m-d H:i:s', $expiration) : 'Never',
                'is_expired' => $is_expired,
                'value_preview' => $this->get_value_preview($transient->option_value)
            ];
        }
        
        return $result;
    }

    /**
     * Limpia todos los transients
     */
    private function clear_all_transients(): array {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_%'"
        );

        return [
            'deleted' => $deleted,
            'message' => "Se eliminaron {$deleted} transients"
        ];
    }

    /**
     * Optimiza opciones autoload
     */
    private function optimize_autoload_options(): array {
        global $wpdb;
        
        // Buscar opciones grandes con autoload
        $large_options = $wpdb->get_results(
            "SELECT option_name, LENGTH(option_value) as size 
             FROM {$wpdb->options} 
             WHERE autoload = 'yes' 
             AND LENGTH(option_value) > 100000 
             ORDER BY size DESC"
        );

        $optimized = 0;
        foreach ($large_options as $option) {
            // Solo optimizar ciertas opciones conocidas como seguras
            if (in_array($option->option_name, ['_site_transient_browser_capabilities'])) {
                $wpdb->update(
                    $wpdb->options,
                    ['autoload' => 'no'],
                    ['option_name' => $option->option_name]
                );
                $optimized++;
            }
        }

        return [
            'found' => count($large_options),
            'optimized' => $optimized,
            'message' => "Se optimizaron {$optimized} opciones de autoload"
        ];
    }

    /**
     * Obtiene preview del valor
     */
    private function get_value_preview(string $value): string {
        if (strlen($value) > 100) {
            return substr($value, 0, 100) . '...';
        }
        return $value;
    }

    /**
     * Activación del módulo
     */
    public function activate(): bool {
        $this->log_external('CacheModule activated');
        return true;
    }

    /**
     * Desactivación del módulo
     */
    public function deactivate(): bool {
        $this->log_external('CacheModule deactivated');
        return true;
    }

    /**
     * Limpieza del módulo
     */
    public function cleanup(): void {
        // Limpiar transients específicos del módulo
        delete_transient('dev_tools_cache_stats');
        $this->log_external('CacheModule cleaned up');
    }
}
