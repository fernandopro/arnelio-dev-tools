<?php
/**
 * SystemInfo Module - Información detallada del sistema
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class SystemInfoModule extends DevToolsModuleBase {
    
    /**
     * Obtener información del módulo
     */
    public function getModuleInfo(): array {
        return [
            'name' => 'SystemInfo',
            'version' => '3.0.0',
            'description' => 'Información detallada del sistema WordPress, PHP y servidor',
            'dependencies' => [],
            'capabilities' => ['manage_options']
        ];
    }

    /**
     * Inicialización específica del módulo
     */
    protected function initializeModule(): bool {
        // Registrar comandos AJAX específicos
        $this->register_ajax_command('get_system_info', [$this, 'handle_get_system_info']);
        $this->register_ajax_command('export_system_info', [$this, 'handle_export_system_info']);
        $this->register_ajax_command('run_diagnostic', [$this, 'handle_run_diagnostic']);
        
        $this->log_internal('SystemInfoModule initialized');
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
        $ajaxHandler->registerCommand('get_system_info', [$this, 'handle_get_system_info']);
        $ajaxHandler->registerCommand('export_system_info', [$this, 'handle_export_system_info']);
        $ajaxHandler->registerCommand('run_diagnostic', [$this, 'handle_run_diagnostic']);
    }
    
    /**
     * Activación específica del módulo
     */
    protected function activateModule(): bool {
        $this->log_external('SystemInfoModule activated');
        return true;
    }
    
    /**
     * Desactivación específica del módulo
     */
    protected function deactivateModule(): bool {
        $this->log_external('SystemInfoModule deactivated');
        return true;
    }
    
    /**
     * Limpieza específica del módulo
     */
    protected function cleanupModule(): void {
        $this->log_external('SystemInfoModule cleaned up');
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
            'id' => 'system_info',
            'name' => 'System Information',
            'description' => 'Información detallada del sistema WordPress, PHP y servidor',
            'version' => '3.0.0',
            'author' => 'Dev-Tools Team',
            'icon' => 'fas fa-info-circle',
            'priority' => 20,
            'dependencies' => [],
            'ajax_actions' => [
                'get_system_info',
                'export_system_info',
                'run_diagnostic'
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
        <div class="dev-tools-module" id="system-info-module">
            <div class="module-header d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-info-circle me-2"></i>System Information</h4>
                <div class="module-actions">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="DevToolsSystemInfo.refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="DevToolsSystemInfo.exportInfo()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="DevToolsSystemInfo.runDiagnostic()">
                        <i class="fas fa-stethoscope"></i> Diagnostic
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="system-info-loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando información del sistema...</span>
                </div>
                <p class="mt-2 text-muted">Recopilando información del sistema...</p>
            </div>

            <!-- System Info Content -->
            <div id="system-info-content" class="d-none">
                <div class="row">
                    <!-- WordPress Info -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fab fa-wordpress me-2"></i>WordPress</h6>
                            </div>
                            <div class="card-body">
                                <div id="wordpress-info"></div>
                            </div>
                        </div>
                    </div>

                    <!-- PHP Info -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fab fa-php me-2"></i>PHP</h6>
                            </div>
                            <div class="card-body">
                                <div id="php-info"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Server Info -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-server me-2"></i>Server</h6>
                            </div>
                            <div class="card-body">
                                <div id="server-info"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Database Info -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-database me-2"></i>Database</h6>
                            </div>
                            <div class="card-body">
                                <div id="database-info"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Plugins Info -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-plug me-2"></i>Active Plugins</h6>
                            </div>
                            <div class="card-body">
                                <div id="plugins-info"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Theme Info -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-palette me-2"></i>Theme Information</h6>
                            </div>
                            <div class="card-body">
                                <div id="theme-info"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnostic Results -->
            <div id="diagnostic-results" class="d-none">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-stethoscope me-2"></i>System Diagnostic</h6>
                    </div>
                    <div class="card-body">
                        <div id="diagnostic-content"></div>
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
            'system-info' => [
                'file' => 'system-info.js',
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
            'system-info' => [
                'file' => 'system-info.css',
                'deps' => ['dev-tools-core'],
                'version' => '3.0.0'
            ]
        ];
    }

    /**
     * Maneja petición de información del sistema
     */
    public function handle_get_system_info(): array {
        $this->log_internal('Getting system information');

        try {
            $system_info = [
                'wordpress' => $this->get_wordpress_info(),
                'php' => $this->get_php_info(),
                'server' => $this->get_server_info(),
                'database' => $this->get_database_info(),
                'plugins' => $this->get_plugins_info(),
                'theme' => $this->get_theme_info(),
                'timestamp' => current_time('c')
            ];

            $this->log_external('System information collected successfully');

            return [
                'success' => true,
                'data' => $system_info,
                'message' => 'Información del sistema recopilada exitosamente'
            ];

        } catch (Exception $e) {
            $this->log_external('Error getting system info: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al recopilar información del sistema: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja exportación de información del sistema
     */
    public function handle_export_system_info(): array {
        $this->log_internal('Exporting system information');

        try {
            $system_info = $this->handle_get_system_info();
            
            if (!$system_info['success']) {
                return $system_info;
            }

            $export_data = [
                'export_info' => [
                    'generated_at' => current_time('c'),
                    'site_url' => get_site_url(),
                    'exported_by' => wp_get_current_user()->user_login
                ],
                'system_info' => $system_info['data']
            ];

            $filename = 'system-info-' . date('Y-m-d-H-i-s') . '.json';
            $file_content = json_encode($export_data, JSON_PRETTY_PRINT);

            $this->log_external('System info exported successfully');

            return [
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'content' => $file_content,
                    'size' => strlen($file_content)
                ],
                'message' => 'Información del sistema exportada exitosamente'
            ];

        } catch (Exception $e) {
            $this->log_external('Error exporting system info: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al exportar información del sistema: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Maneja diagnóstico del sistema
     */
    public function handle_run_diagnostic(): array {
        $this->log_internal('Running system diagnostic');

        try {
            $diagnostic = [
                'php_version' => $this->check_php_version(),
                'wordpress_version' => $this->check_wordpress_version(),
                'memory_limit' => $this->check_memory_limit(),
                'max_execution_time' => $this->check_max_execution_time(),
                'file_permissions' => $this->check_file_permissions(),
                'database_connection' => $this->check_database_connection(),
                'https_status' => $this->check_https_status(),
                'debug_mode' => $this->check_debug_mode(),
                'auto_updates' => $this->check_auto_updates()
            ];

            $overall_status = $this->calculate_overall_status($diagnostic);

            $this->log_external('System diagnostic completed', 'success');

            return [
                'success' => true,
                'data' => [
                    'diagnostic' => $diagnostic,
                    'overall_status' => $overall_status,
                    'timestamp' => current_time('c')
                ],
                'message' => 'Diagnóstico del sistema completado'
            ];

        } catch (Exception $e) {
            $this->log_external('Error running diagnostic: ' . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => 'Error al ejecutar diagnóstico: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene información de WordPress
     */
    private function get_wordpress_info(): array {
        global $wp_version;
        
        return [
            'version' => $wp_version,
            'multisite' => is_multisite(),
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'admin_url' => admin_url(),
            'language' => get_locale(),
            'timezone' => get_option('timezone_string') ?: 'UTC',
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
            'users_count' => count_users()['total_users'] ?? 0,
            'posts_count' => wp_count_posts()->publish ?? 0,
            'pages_count' => wp_count_posts('page')->publish ?? 0
        ];
    }

    /**
     * Obtiene información de PHP
     */
    private function get_php_info(): array {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
            'error_reporting' => error_reporting(),
            'extensions' => get_loaded_extensions()
        ];
    }

    /**
     * Obtiene información del servidor
     */
    private function get_server_info(): array {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_sapi' => php_sapi_name(),
            'operating_system' => PHP_OS,
            'architecture' => php_uname('m'),
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'https' => is_ssl() ? 'Yes' : 'No',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
    }

    /**
     * Obtiene información de la base de datos
     */
    private function get_database_info(): array {
        global $wpdb;
        
        $tables_info = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);
        $total_size = 0;
        
        foreach ($tables_info as $table) {
            $total_size += $table['Data_length'] + $table['Index_length'];
        }

        return [
            'version' => $wpdb->get_var("SELECT VERSION()"),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'prefix' => $wpdb->prefix,
            'tables_count' => count($tables_info),
            'total_size' => size_format($total_size),
            'max_allowed_packet' => $wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'") ?: 'Unknown'
        ];
    }

    /**
     * Obtiene información de plugins
     */
    private function get_plugins_info(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $plugins_data = [];
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $plugins_data[] = [
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'author' => $plugin_data['Author'],
                'description' => $plugin_data['Description'],
                'active' => in_array($plugin_file, $active_plugins),
                'file' => $plugin_file
            ];
        }

        return [
            'total_count' => count($all_plugins),
            'active_count' => count($active_plugins),
            'plugins' => $plugins_data
        ];
    }

    /**
     * Obtiene información del tema
     */
    private function get_theme_info(): array {
        $current_theme = wp_get_theme();
        
        return [
            'name' => $current_theme->get('Name'),
            'version' => $current_theme->get('Version'),
            'author' => $current_theme->get('Author'),
            'description' => $current_theme->get('Description'),
            'template' => $current_theme->get_template(),
            'stylesheet' => $current_theme->get_stylesheet(),
            'parent_theme' => $current_theme->parent() ? $current_theme->parent()->get('Name') : null,
            'screenshot' => $current_theme->get_screenshot(),
            'supports' => $this->get_theme_supports()
        ];
    }

    /**
     * Obtiene las características soportadas por el tema actual
     */
    private function get_theme_supports(): array {
        $features = [
            'post-thumbnails',
            'custom-logo',
            'custom-background',
            'custom-header',
            'menus',
            'widgets',
            'title-tag',
            'html5',
            'post-formats'
        ];
        
        $supports = [];
        foreach ($features as $feature) {
            $supports[$feature] = current_theme_supports($feature);
        }
        
        return $supports;
    }

    /**
     * Verifica versión de PHP
     */
    private function check_php_version(): array {
        $current_version = PHP_VERSION;
        $recommended_version = '8.0';
        $minimum_version = '7.4';
        
        $status = version_compare($current_version, $recommended_version, '>=') ? 'good' :
                 (version_compare($current_version, $minimum_version, '>=') ? 'warning' : 'error');
        
        return [
            'status' => $status,
            'current' => $current_version,
            'recommended' => $recommended_version,
            'message' => $this->get_status_message($status, 'PHP Version')
        ];
    }

    /**
     * Verifica versión de WordPress
     */
    private function check_wordpress_version(): array {
        global $wp_version;
        
        // Simular verificación (en producción usar API de WordPress)
        $status = version_compare($wp_version, '6.0', '>=') ? 'good' : 'warning';
        
        return [
            'status' => $status,
            'current' => $wp_version,
            'message' => $this->get_status_message($status, 'WordPress Version')
        ];
    }

    /**
     * Verifica límite de memoria
     */
    private function check_memory_limit(): array {
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $recommended_bytes = $this->convert_to_bytes('256M');
        
        $status = $memory_limit_bytes >= $recommended_bytes ? 'good' : 'warning';
        
        return [
            'status' => $status,
            'current' => $memory_limit,
            'recommended' => '256M',
            'message' => $this->get_status_message($status, 'Memory Limit')
        ];
    }

    /**
     * Verifica tiempo máximo de ejecución
     */
    private function check_max_execution_time(): array {
        $max_execution_time = ini_get('max_execution_time');
        $recommended_time = 60;
        
        $status = ($max_execution_time == 0 || $max_execution_time >= $recommended_time) ? 'good' : 'warning';
        
        return [
            'status' => $status,
            'current' => $max_execution_time == 0 ? 'Unlimited' : $max_execution_time . 's',
            'recommended' => $recommended_time . 's',
            'message' => $this->get_status_message($status, 'Max Execution Time')
        ];
    }

    /**
     * Verifica permisos de archivos
     */
    private function check_file_permissions(): array {
        $checks = [
            'wp-content writable' => is_writable(WP_CONTENT_DIR),
            'uploads writable' => is_writable(wp_upload_dir()['basedir']),
            'wp-config readable' => is_readable(ABSPATH . 'wp-config.php')
        ];
        
        $all_good = array_reduce($checks, function($carry, $item) { return $carry && $item; }, true);
        $status = $all_good ? 'good' : 'error';
        
        return [
            'status' => $status,
            'checks' => $checks,
            'message' => $this->get_status_message($status, 'File Permissions')
        ];
    }

    /**
     * Verifica conexión a base de datos
     */
    private function check_database_connection(): array {
        global $wpdb;
        
        $result = $wpdb->get_var("SELECT 1");
        $status = $result === '1' ? 'good' : 'error';
        
        return [
            'status' => $status,
            'message' => $this->get_status_message($status, 'Database Connection')
        ];
    }

    /**
     * Verifica estado HTTPS
     */
    private function check_https_status(): array {
        $is_ssl = is_ssl();
        $status = $is_ssl ? 'good' : 'warning';
        
        return [
            'status' => $status,
            'enabled' => $is_ssl,
            'message' => $this->get_status_message($status, 'HTTPS')
        ];
    }

    /**
     * Verifica modo debug
     */
    private function check_debug_mode(): array {
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        $status = $debug_enabled ? 'warning' : 'good';
        
        return [
            'status' => $status,
            'enabled' => $debug_enabled,
            'message' => $this->get_status_message($status, 'Debug Mode')
        ];
    }

    /**
     * Verifica actualizaciones automáticas
     */
    private function check_auto_updates(): array {
        $auto_updates = get_option('auto_update_core_major', false);
        $status = $auto_updates ? 'good' : 'warning';
        
        return [
            'status' => $status,
            'enabled' => $auto_updates,
            'message' => $this->get_status_message($status, 'Auto Updates')
        ];
    }

    /**
     * Calcula estado general del diagnóstico
     */
    private function calculate_overall_status(array $diagnostic): array {
        $statuses = array_column($diagnostic, 'status');
        $error_count = count(array_filter($statuses, fn($s) => $s === 'error'));
        $warning_count = count(array_filter($statuses, fn($s) => $s === 'warning'));
        $good_count = count(array_filter($statuses, fn($s) => $s === 'good'));
        
        if ($error_count > 0) {
            $overall = 'error';
            $message = "Se encontraron {$error_count} errores críticos";
        } elseif ($warning_count > 0) {
            $overall = 'warning';
            $message = "Se encontraron {$warning_count} advertencias";
        } else {
            $overall = 'good';
            $message = "Todos los checks pasaron exitosamente";
        }
        
        return [
            'status' => $overall,
            'message' => $message,
            'summary' => [
                'good' => $good_count,
                'warning' => $warning_count,
                'error' => $error_count,
                'total' => count($diagnostic)
            ]
        ];
    }

    /**
     * Convierte string de memoria a bytes
     */
    private function convert_to_bytes(string $value): int {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }

    /**
     * Obtiene mensaje según estado
     */
    private function get_status_message(string $status, string $item): string {
        switch ($status) {
            case 'good': return "{$item} está configurado correctamente";
            case 'warning': return "{$item} necesita atención";
            case 'error': return "{$item} tiene problemas críticos";
            default: return "{$item} estado desconocido";
        }
    }

    /**
     * Activación del módulo
     */
    public function activate(): bool {
        $this->log_external('SystemInfoModule activated');
        return true;
    }

    /**
     * Desactivación del módulo
     */
    public function deactivate(): bool {
        $this->log_external('SystemInfoModule deactivated');
        return true;
    }

    /**
     * Limpieza del módulo
     */
    public function cleanup(): void {
        // Limpiar transients específicos del módulo
        delete_transient('dev_tools_system_info_cache');
        $this->log_external('SystemInfoModule cleaned up');
    }
}
