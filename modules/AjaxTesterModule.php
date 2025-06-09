<?php
/**
 * AjaxTester Module - Herramientas de testing AJAX
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class AjaxTesterModule extends DevToolsModuleBase {
    
    private $test_history = [];
    private $preset_tests = [];
    
    /**
     * Obtener información del módulo
     */
    public function getModuleInfo(): array {
        return [
            'name' => 'AjaxTester',
            'version' => '3.0.0',
            'description' => 'Herramientas avanzadas para testing y debugging de AJAX',
            'dependencies' => [],
            'capabilities' => ['manage_options']
        ];
    }

    /**
     * Inicialización específica del módulo
     */
    protected function initializeModule(): bool {
        // DEBUG: Log que este método se está ejecutando
        error_log('[DEV-TOOLS-DEBUG] AjaxTesterModule::initializeModule() ejecutándose');
        
        // Registrar comandos AJAX específicos
        $this->register_ajax_command('test_ajax_endpoint', [$this, 'handle_test_ajax_endpoint']);
        $this->register_ajax_command('get_test_history', [$this, 'handle_get_test_history']);
        $this->register_ajax_command('clear_test_history', [$this, 'handle_clear_test_history']);
        $this->register_ajax_command('save_test_preset', [$this, 'handle_save_test_preset']);
        $this->register_ajax_command('load_test_presets', [$this, 'handle_load_test_presets']);
        $this->register_ajax_command('get_wordpress_ajax_actions', [$this, 'handle_get_wordpress_ajax_actions']);
        
        // DEBUG: Log comandos registrados
        error_log('[DEV-TOOLS-DEBUG] AjaxTesterModule: 6 comandos AJAX registrados');
        
        // Cargar presets predeterminados
        $this->load_default_presets();
        
        $this->log_internal('AjaxTesterModule initialized');
        return true;
    }
    
    /**
     * Registrar hooks de WordPress
     */
    public function registerHooks(): void {
        // CORREGIDO: WordPress no soporta wildcards en hooks
        // Los wildcards causan errores 500 y comportamiento impredecible
        
        // Si queremos logging específico, debemos registrar hooks individuales
        // Por ahora comentamos este feature hasta implementar correctamente
        
        /* TODO: Implementar logging AJAX específico sin wildcards
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Registrar hooks para acciones específicas que queremos monitorear
            $monitored_actions = ['heartbeat', 'admin_color_scheme_picker'];
            foreach ($monitored_actions as $action) {
                add_action("wp_ajax_{$action}", [$this, 'log_ajax_request'], 1);
                add_action("wp_ajax_nopriv_{$action}", [$this, 'log_ajax_request'], 1);
            }
        }
        */
    }
    
    /**
     * Registrar comandos AJAX del módulo
     */
    public function registerAjaxCommands(DevToolsAjaxHandler $ajaxHandler): void {
        $ajaxHandler->registerCommand('test_ajax_endpoint', [$this, 'handle_test_ajax_endpoint']);
        $ajaxHandler->registerCommand('get_test_history', [$this, 'handle_get_test_history']);
        $ajaxHandler->registerCommand('clear_test_history', [$this, 'handle_clear_test_history']);
        $ajaxHandler->registerCommand('save_test_preset', [$this, 'handle_save_test_preset']);
        $ajaxHandler->registerCommand('load_test_presets', [$this, 'handle_load_test_presets']);
        $ajaxHandler->registerCommand('get_wordpress_ajax_actions', [$this, 'handle_get_wordpress_ajax_actions']);
    }
    
    /**
     * Configuración del módulo
     */
    protected function get_module_config(): array {
        return [
            'id' => 'ajax_tester',
            'name' => 'AJAX Tester',
            'description' => 'Herramientas avanzadas para testing y debugging de AJAX',
            'version' => '3.0.0',
            'author' => 'Dev-Tools Team',
            'icon' => 'fas fa-network-wired',
            'priority' => 30,
            'dependencies' => [],
            'ajax_actions' => [
                'test_ajax_endpoint',
                'get_test_history',
                'clear_test_history',
                'save_test_preset',
                'load_test_presets',
                'get_wordpress_ajax_actions'
            ]
        ];
    }

    /**
     * Renderizado del panel del módulo
     */
    public function render_panel(): string {
        ob_start();
        ?>
        <div class="dev-tools-module" id="ajax-tester-module">
            <div class="module-header d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-network-wired me-2"></i>AJAX Tester</h4>
                <div class="module-actions">
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="DevToolsAjaxTester.loadPresets()">
                        <i class="fas fa-list"></i> Presets
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="DevToolsAjaxTester.showHistory()">
                        <i class="fas fa-history"></i> History
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="DevToolsAjaxTester.clearHistory()">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Test Configuration Panel -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Test Configuration</h6>
                        </div>
                        <div class="card-body">
                            <form id="ajax-test-form">
                                <!-- Endpoint URL -->
                                <div class="mb-3">
                                    <label for="ajax-endpoint" class="form-label">Endpoint URL</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="ajax-endpoint" 
                                               value="<?php echo admin_url('admin-ajax.php'); ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="DevToolsAjaxTester.detectWordPressActions()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Action -->
                                <div class="mb-3">
                                    <label for="ajax-action" class="form-label">Action</label>
                                    <input type="text" class="form-control" id="ajax-action" 
                                           placeholder="wp_ajax_action_name" required>
                                </div>

                                <!-- Method -->
                                <div class="mb-3">
                                    <label for="ajax-method" class="form-label">Method</label>
                                    <select class="form-select" id="ajax-method">
                                        <option value="POST">POST</option>
                                        <option value="GET">GET</option>
                                    </select>
                                </div>

                                <!-- Data -->
                                <div class="mb-3">
                                    <label for="ajax-data" class="form-label">Data (JSON)</label>
                                    <textarea class="form-control" id="ajax-data" rows="5" 
                                              placeholder='{"key": "value", "nonce": "auto"}'>{}</textarea>
                                    <div class="form-text">
                                        Use "nonce": "auto" para generar automáticamente el nonce de dev-tools
                                    </div>
                                </div>

                                <!-- Headers -->
                                <div class="mb-3">
                                    <label for="ajax-headers" class="form-label">Headers (JSON)</label>
                                    <textarea class="form-control" id="ajax-headers" rows="3" 
                                              placeholder='{"Content-Type": "application/json"}'>{}</textarea>
                                </div>

                                <!-- Test Options -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="auto-nonce" checked>
                                        <label class="form-check-label" for="auto-nonce">
                                            Auto-generate nonce
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="log-request">
                                        <label class="form-check-label" for="log-request">
                                            Log request details
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="measure-time" checked>
                                        <label class="form-check-label" for="measure-time">
                                            Measure response time
                                        </label>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Execute Test
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="DevToolsAjaxTester.saveAsPreset()">
                                        <i class="fas fa-save"></i> Save as Preset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Results Panel -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Test Results</h6>
                            <div class="test-status">
                                <span id="test-status" class="badge bg-secondary">Ready</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Loading State -->
                            <div id="test-loading" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Executing test...</span>
                                </div>
                                <p class="mt-2 text-muted">Executing AJAX test...</p>
                            </div>

                            <!-- Results Content -->
                            <div id="test-results" class="d-none">
                                <!-- Response Status -->
                                <div class="mb-3">
                                    <h6>Response Status</h6>
                                    <div class="d-flex justify-content-between">
                                        <span>Status Code:</span>
                                        <span id="response-status" class="badge"></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Response Time:</span>
                                        <span id="response-time" class="badge bg-info"></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Content Type:</span>
                                        <span id="response-content-type" class="text-muted"></span>
                                    </div>
                                </div>

                                <!-- Response Headers -->
                                <div class="mb-3">
                                    <h6>Response Headers</h6>
                                    <pre id="response-headers" class="bg-light p-2 rounded" style="font-size: 0.8rem; max-height: 150px; overflow-y: auto;"></pre>
                                </div>

                                <!-- Response Body -->
                                <div class="mb-3">
                                    <h6>Response Body</h6>
                                    <pre id="response-body" class="bg-light p-2 rounded" style="font-size: 0.8rem; max-height: 200px; overflow-y: auto;"></pre>
                                </div>

                                <!-- Error Details (if any) -->
                                <div id="error-details" class="d-none">
                                    <h6 class="text-danger">Error Details</h6>
                                    <div class="alert alert-danger">
                                        <pre id="error-message" class="mb-0"></pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Welcome Message -->
                            <div id="test-welcome" class="text-center py-4 text-muted">
                                <i class="fas fa-rocket fa-2x mb-3"></i>
                                <p>Configure your AJAX test and click "Execute Test" to see results here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test History -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Test History</h6>
                        </div>
                        <div class="card-body">
                            <div id="test-history-content">
                                <p class="text-muted text-center">No tests executed yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Presets Modal -->
            <div class="modal fade" id="presetsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">AJAX Test Presets</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="presets-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Manejar test de endpoint AJAX
     */
    public function handle_test_ajax_endpoint(): array {
        try {
            $endpoint = sanitize_url($_POST['endpoint'] ?? '');
            $action = sanitize_text_field($_POST['test_action'] ?? '');
            $method = sanitize_text_field($_POST['method'] ?? 'POST');
            $data = json_decode(stripslashes($_POST['data'] ?? '{}'), true);
            $headers = json_decode(stripslashes($_POST['headers'] ?? '{}'), true);
            
            if (empty($endpoint) || empty($action)) {
                return [
                    'success' => false,
                    'message' => 'Endpoint and action are required'
                ];
            }

            // Ejecutar test
            $start_time = microtime(true);
            $result = $this->execute_ajax_test($endpoint, $action, $method, $data, $headers);
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);

            // Guardar en historial
            $this->save_test_to_history($action, $method, $data, $result, $execution_time);

            return [
                'success' => true,
                'result' => $result,
                'execution_time' => $execution_time,
                'timestamp' => current_time('mysql')
            ];

        } catch (Exception $e) {
            $this->log_external('AJAX test failed: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Test execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar test AJAX
     */
    private function execute_ajax_test($endpoint, $action, $method, $data, $headers): array {
        // Preparar datos
        if (!isset($data['action'])) {
            $data['action'] = $action;
        }

        // Auto-generar nonce si es necesario
        if (isset($data['nonce']) && $data['nonce'] === 'auto') {
            $data['nonce'] = wp_create_nonce('dev_tools_ajax');
        }

        // Configurar argumentos para wp_remote_request
        $args = [
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'headers' => array_merge([
                'User-Agent' => 'Dev-Tools AJAX Tester'
            ], $headers)
        ];

        if ($method === 'POST') {
            $args['body'] = $data;
        } else {
            $endpoint = add_query_arg($data, $endpoint);
        }

        // Ejecutar petición
        $response = wp_remote_request($endpoint, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        return [
            'status_code' => wp_remote_retrieve_response_code($response),
            'headers' => wp_remote_retrieve_headers($response)->getAll(),
            'body' => wp_remote_retrieve_body($response),
            'content_type' => wp_remote_retrieve_header($response, 'content-type')
        ];
    }

    /**
     * Guardar test en historial
     */
    private function save_test_to_history($action, $method, $data, $result, $execution_time): void {
        $this->test_history[] = [
            'timestamp' => current_time('mysql'),
            'action' => $action,
            'method' => $method,
            'data' => $data,
            'result' => $result,
            'execution_time' => $execution_time,
            'success' => isset($result['status_code']) && $result['status_code'] < 400
        ];

        // Mantener solo los últimos 50 tests
        if (count($this->test_history) > 50) {
            $this->test_history = array_slice($this->test_history, -50);
        }

        // Guardar en option (temporal, en producción usaríamos una tabla)
        update_option('dev_tools_ajax_test_history', $this->test_history);
    }

    /**
     * Obtener historial de tests
     */
    public function handle_get_test_history(): array {
        $history = get_option('dev_tools_ajax_test_history', []);
        
        return [
            'success' => true,
            'history' => array_reverse($history) // Más recientes primero
        ];
    }

    /**
     * Limpiar historial de tests
     */
    public function handle_clear_test_history(): array {
        delete_option('dev_tools_ajax_test_history');
        $this->test_history = [];
        
        $this->log_external('AJAX test history cleared');
        
        return [
            'success' => true,
            'message' => 'Test history cleared successfully'
        ];
    }

    /**
     * Guardar preset de test
     */
    public function handle_save_test_preset(): array {
        try {
            $name = sanitize_text_field($_POST['preset_name'] ?? '');
            $config = json_decode(stripslashes($_POST['config'] ?? '{}'), true);
            
            if (empty($name)) {
                return [
                    'success' => false,
                    'message' => 'Preset name is required'
                ];
            }

            $presets = get_option('dev_tools_ajax_presets', []);
            $presets[$name] = [
                'name' => $name,
                'config' => $config,
                'created' => current_time('mysql'),
                'author' => wp_get_current_user()->display_name
            ];

            update_option('dev_tools_ajax_presets', $presets);

            return [
                'success' => true,
                'message' => 'Preset saved successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to save preset: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cargar presets de test
     */
    public function handle_load_test_presets(): array {
        $presets = get_option('dev_tools_ajax_presets', []);
        
        // Agregar presets por defecto si no existen
        if (empty($presets)) {
            $presets = $this->get_default_presets();
        }

        return [
            'success' => true,
            'presets' => $presets
        ];
    }

    /**
     * Obtener acciones AJAX de WordPress
     */
    public function handle_get_wordpress_ajax_actions(): array {
        global $wp_filter;
        
        $ajax_actions = [];
        
        // Buscar acciones wp_ajax_*
        foreach ($wp_filter as $hook => $filters) {
            if (strpos($hook, 'wp_ajax_') === 0) {
                $action = str_replace('wp_ajax_', '', $hook);
                if (!in_array($action, ['nopriv_*', '*'])) {
                    $ajax_actions[] = $action;
                }
            }
        }
        
        sort($ajax_actions);
        
        return [
            'success' => true,
            'actions' => array_unique($ajax_actions)
        ];
    }

    /**
     * Cargar presets por defecto
     */
    private function load_default_presets(): void {
        $this->preset_tests = $this->get_default_presets();
    }

    /**
     * Obtener presets por defecto
     */
    private function get_default_presets(): array {
        return [
            'dev_tools_test' => [
                'name' => 'Dev-Tools Test',
                'config' => [
                    'action' => 'dev_tools_ajax',
                    'method' => 'POST',
                    'data' => [
                        'command' => 'get_system_info',
                        'nonce' => 'auto'
                    ]
                ],
                'created' => current_time('mysql'),
                'author' => 'System'
            ],
            'heartbeat_test' => [
                'name' => 'WordPress Heartbeat',
                'config' => [
                    'action' => 'heartbeat',
                    'method' => 'POST',
                    'data' => [
                        'screen_id' => 'dashboard',
                        'nonce' => 'auto'
                    ]
                ],
                'created' => current_time('mysql'),
                'author' => 'System'
            ]
        ];
    }

    /**
     * Log de peticiones AJAX (para debugging)
     */
    public function log_ajax_request(): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? 'unknown';
        $this->log_internal("AJAX request intercepted: {$action}");
    }

    /**
     * Activación específica del módulo
     */
    protected function activateModule(): bool {
        $this->log_external('AjaxTesterModule activated');
        return true;
    }

    /**
     * Desactivación específica del módulo
     */
    protected function deactivateModule(): bool {
        $this->log_external('AjaxTesterModule deactivated');
        return true;
    }

    /**
     * Limpieza específica del módulo
     */
    protected function cleanupModule(): void {
        // Limpiar opciones temporales
        delete_option('dev_tools_ajax_test_history');
        delete_option('dev_tools_ajax_presets');
        
        $this->log_external('AjaxTesterModule cleaned up');
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
}
