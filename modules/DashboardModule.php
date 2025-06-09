<?php
/**
 * Módulo Dashboard para Dev Tools - Arquitectura 3.0
 * Panel principal de administración y estado del sistema
 * 
 * @package DevTools\Modules
 * @version 3.0.0
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar dependencias
require_once dirname(__DIR__) . '/core/DevToolsModuleBase.php';
require_once dirname(__DIR__) . '/core/interfaces/DevToolsModuleInterface.php';

/**
 * Módulo Dashboard - Panel principal del sistema dev-tools
 * Proporciona interfaz principal y estado general del sistema
 */
class DashboardModule extends DevToolsModuleBase {
    
    /**
     * Información del módulo
     */
    public function getModuleInfo(): array {
        return [
            'name' => 'dashboard',
            'version' => '3.0.0',
            'description' => 'Panel principal de administración y estado del sistema dev-tools',
            'dependencies' => [
                'function:add_menu_page',
                'function:wp_enqueue_script'
            ],
            'capabilities' => ['manage_options']
        ];
    }
    
    /**
     * Inicialización específica del módulo
     */
    protected function initializeModule(): bool {
        // Verificar que Bootstrap esté disponible
        if (!$this->isBootstrapAvailable()) {
            $this->enqueueBootstrap();
        }
        
        return true;
    }
    
    /**
     * Activación específica del módulo
     */
    protected function activateModule(): bool {
        return true;
    }
    
    /**
     * Desactivación específica del módulo
     */
    protected function deactivateModule(): bool {
        return true;
    }
    
    /**
     * Limpieza específica del módulo
     */
    protected function cleanupModule(): void {
        // Limpiar transients del dashboard
        delete_transient('dev_tools_dashboard_cache');
    }
    
    /**
     * Validación específica de configuración
     */
    protected function validateModuleConfig(array $config): bool {
        return true; // Dashboard no requiere configuración especial
    }
    
    /**
     * Campos de configuración requeridos
     */
    protected function getRequiredConfigFields(): array {
        return []; // No hay campos obligatorios
    }
    
    /**
     * Registrar hooks de WordPress
     */
    public function registerHooks(): void {
        // Agregar página de administración
        add_action('admin_menu', [$this, 'addAdminPage']);
        
        // Enqueue scripts y styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // AJAX hooks para el dashboard
        add_action('admin_init', [$this, 'registerAdminAjax']);
    }
    
    /**
     * Registrar comandos AJAX del módulo
     */
    public function registerAjaxCommands(DevToolsAjaxHandler $ajaxHandler): void {
        $ajaxHandler->registerCommand('dashboard_get_stats', [$this, 'getDashboardStats']);
        $ajaxHandler->registerCommand('dashboard_refresh_data', [$this, 'refreshDashboardData']);
        $ajaxHandler->registerCommand('dashboard_get_modules', [$this, 'getModulesStatus']);
        $ajaxHandler->registerCommand('dashboard_toggle_module', [$this, 'toggleModule']);
    }
    
    /**
     * Agregar página de administración
     */
    public function addAdminPage(): void {
        // Defensive check: Handle missing config during test environment
        if (!$this->config || !method_exists($this->config, 'get')) {
            // During tests, config might not be available
            // Use fallback slug that won't break WordPress admin
            $menu_slug = 'dev-tools-dashboard-test';
            $this->log_external('Config not available during initialization (test environment), using fallback menu slug', 'warning');
        } else {
            $menu_slug = $this->config->get('dev_tools.menu_slug');
        }
        
        $capability = 'manage_options';
        
        add_management_page(
            'Dev Tools Dashboard',
            'Dev Tools',
            $capability,
            $menu_slug,
            [$this, 'renderDashboardPage']
        );
    }
    
    /**
     * Enqueue assets del dashboard
     */
    public function enqueueAssets($hook): void {
        // CORRECCIÓN: El loader.php ya maneja toda la carga de assets
        // Este método se mantiene vacío para evitar duplicación de scripts
        // Solo el loader.php debe encolar JavaScript y CSS para mantener consistencia
        
        // Protección para entorno de tests donde config puede no estar inicializado
        if (!$this->config || !method_exists($this->config, 'get')) {
            return;
        }
        
        $menu_slug = $this->config->get('dev_tools.menu_slug');
        if (!$menu_slug) {
            return;
        }
        
        // Solo cargar en la página de dev-tools
        if ($hook !== "tools_page_{$menu_slug}") {
            return;
        }
        
        // ELIMINADO: No duplicar carga de assets que ya maneja loader.php
        // Los scripts se cargan automáticamente desde loader.php con handles consistentes
        
        // Nota: La configuración JavaScript ya se aplica en loader.php
        // wp_localize_script() se ejecuta allí con la configuración centralizada
    }
    
    /**
     * Renderizar página del dashboard
     */
    public function renderDashboardPage(): void {
        $system_info = $this->getSystemInfo();
        $modules_status = $this->getModulesStatusForDisplay();
        $recent_activity = $this->getRecentActivity();
        
        ?>
        <!-- Dashboard Content with Dark Theme -->
        <div class="dev-tools-dashboard">
            <!-- Sistema Status Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-dark border-success text-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success">
                                <i class="bi bi-check-circle"></i> Sistema
                            </h5>
                            <p class="card-text display-6 text-success">✓</p>
                            <small class="text-light-emphasis">Operativo</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-dark border-info text-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info">
                                <i class="bi bi-puzzle"></i> Módulos
                            </h5>
                            <p class="card-text display-6 text-info"><?php echo count($modules_status['active']); ?></p>
                            <small class="text-light-emphasis">Activos</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-dark border-warning text-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-warning">
                                <i class="bi bi-memory"></i> Memoria
                            </h5>
                            <p class="card-text display-6 text-warning"><?php echo $system_info['memory']['current']; ?></p>
                            <small class="text-light-emphasis">Uso actual</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-dark border-primary text-light">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">
                                <i class="bi bi-wordpress"></i> WordPress
                            </h5>
                            <p class="card-text display-6 text-primary"><?php echo $system_info['wp_version']; ?></p>
                            <small class="text-light-emphasis">Versión</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions Panel -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-dark text-light border-secondary">
                        <div class="card-header bg-secondary border-secondary">
                            <h5 class="mb-0 text-light">
                                <i class="bi bi-lightning"></i> Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary" id="btn-test-system">
                                    <i class="bi bi-check-all"></i> Test Sistema
                                </button>
                                <button type="button" class="btn btn-outline-warning" id="btn-clear-cache">
                                    <i class="bi bi-arrow-clockwise"></i> Limpiar Cache
                                </button>
                                <button type="button" class="btn btn-outline-info" id="btn-refresh-data">
                                    <i class="bi bi-arrow-repeat"></i> Actualizar Datos
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-export-logs">
                                    <i class="bi bi-download"></i> Exportar Logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row">
                <!-- Modules Status -->
                <div class="col-md-6">
                    <div class="card bg-dark text-light border-secondary">
                        <div class="card-header bg-secondary border-secondary">
                            <h5 class="mb-0 text-light">
                                <i class="bi bi-puzzle"></i> Estado de Módulos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="modules-status">
                                <?php $this->renderModulesStatus($modules_status); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="col-md-6">
                    <div class="card bg-dark text-light border-secondary">
                        <div class="card-header bg-secondary border-secondary">
                            <h5 class="mb-0 text-light">
                                <i class="bi bi-info-circle"></i> Información del Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4 text-light-emphasis">Plugin Host:</dt>
                                <dd class="col-sm-8 text-light"><?php echo esc_html($system_info['plugin_host']); ?></dd>
                                
                                <dt class="col-sm-4 text-light-emphasis">Dev-Tools:</dt>
                                <dd class="col-sm-8 text-light">v<?php echo esc_html($system_info['dev_tools_version']); ?></dd>
                                
                                <dt class="col-sm-4 text-light-emphasis">WordPress:</dt>
                                <dd class="col-sm-8 text-light">v<?php echo esc_html($system_info['wp_version']); ?></dd>
                                
                                <dt class="col-sm-4 text-light-emphasis">PHP:</dt>
                                <dd class="col-sm-8 text-light">v<?php echo esc_html($system_info['php_version']); ?></dd>
                                
                                <dt class="col-sm-4 text-light-emphasis">Memoria Pico:</dt>
                                <dd class="col-sm-8 text-light"><?php echo esc_html($system_info['memory']['peak']); ?></dd>
                                
                                <dt class="col-sm-4 text-light-emphasis">Debug:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?php echo $system_info['debug'] ? 'warning' : 'success'; ?>">
                                        <?php echo $system_info['debug'] ? 'Desactivado' : 'Activado'; ?>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-dark text-light border-secondary">
                        <div class="card-header bg-secondary border-secondary">
                            <h5 class="mb-0 text-light">
                                <i class="bi bi-clock-history"></i> Actividad Reciente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="recent-activity">
                                <?php $this->renderRecentActivity($recent_activity); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>
        
        <style>
        /* Dashboard Dark Theme Enhancements */
        .dev-tools-dashboard .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .dev-tools-dashboard .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        
        .dev-tools-dashboard .card-header {
            border-bottom: 1px solid var(--dev-tools-border);
            border-radius: 12px 12px 0 0 !important;
        }
        
        .dev-tools-dashboard .list-group-item-dark {
            background-color: var(--dev-tools-bg-secondary);
            color: var(--dev-tools-text-primary);
        }
        
        .dev-tools-dashboard .list-group-item-dark:hover {
            background-color: var(--dev-tools-bg-accent);
        }
        
        .dev-tools-dashboard .btn-outline-primary:hover {
            background-color: var(--dev-tools-primary);
            border-color: var(--dev-tools-primary);
        }
        
        .dev-tools-dashboard .timeline-item {
            border-left: 3px solid var(--dev-tools-border);
            padding-left: 1rem;
            margin-left: 0.5rem;
        }
        
        .dev-tools-dashboard .text-light-emphasis {
            color: var(--dev-tools-text-secondary) !important;
        }
        
        .dev-tools-dashboard .display-6 {
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        </style>
        
        <script>
        // JavaScript específico del dashboard - SIN AUTO-INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎨 Dev-Tools Dashboard Dark Theme cargado');
            
            // CORRECCIÓN: NO inicializar automáticamente para evitar ejecuciones no deseadas
            // El dashboard se inicializará SOLO cuando el usuario interactúe con botones
            // if (typeof DevToolsDashboard !== 'undefined') {
            //     const dashboard = new DevToolsDashboard();
            //     dashboard.init(); // Esto causaba ejecuciones automáticas
            // }
            
            // Funcionalidad de botones de acción rápida - SOLO CONSOLE LOGS
            const initQuickActions = () => {
                const testSystemBtn = document.getElementById('btn-test-system');
                const clearCacheBtn = document.getElementById('btn-clear-cache');
                const refreshDataBtn = document.getElementById('btn-refresh-data');
                const exportLogsBtn = document.getElementById('btn-export-logs');
                
                if (testSystemBtn) {
                    testSystemBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log('🔧 Test Sistema iniciado por usuario...');
                        
                        // Inicializar dashboard solo cuando se necesite
                        if (typeof DevToolsDashboard !== 'undefined') {
                            const dashboard = new DevToolsDashboard();
                            dashboard.runSystemTest();
                        }
                    });
                }
                
                if (clearCacheBtn) {
                    clearCacheBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log('🗑️ Limpieza de cache iniciada por usuario...');
                        
                        // Inicializar dashboard solo cuando se necesite
                        if (typeof DevToolsDashboard !== 'undefined') {
                            const dashboard = new DevToolsDashboard();
                            dashboard.clearCache();
                        }
                    });
                }
                
                if (refreshDataBtn) {
                    refreshDataBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log('🔄 Actualización de datos iniciada por usuario...');
                        location.reload();
                    });
                }
                
                if (exportLogsBtn) {
                    exportLogsBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log('📥 Exportación de logs iniciada por usuario...');
                        
                        // Inicializar dashboard solo cuando se necesite
                        if (typeof DevToolsDashboard !== 'undefined') {
                            const dashboard = new DevToolsDashboard();
                            dashboard.exportLogs();
                        }
                    });
                }
            };
            
            initQuickActions();
        });
        </script>
        <?php
    }
    
    /**
     * Renderizar estado de módulos
     */
    private function renderModulesStatus(array $modules_status): void {
        ?>
        <div class="list-group">
            <?php foreach ($modules_status['all'] as $name => $module): ?>
            <div class="list-group-item list-group-item-dark d-flex justify-content-between align-items-center border-secondary">
                <div>
                    <h6 class="mb-1 text-light"><?php echo esc_html($module['info']['name'] ?? $name); ?></h6>
                    <p class="mb-1 text-light-emphasis small"><?php echo esc_html($module['info']['description'] ?? ''); ?></p>
                </div>
                <div>
                    <span class="badge bg-<?php echo $module['active'] ? 'success' : 'secondary'; ?> me-2">
                        <?php echo $module['active'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                    <button class="btn btn-sm btn-outline-primary toggle-module" 
                            data-module="<?php echo esc_attr($name); ?>">
                        <?php echo $module['active'] ? 'Desactivar' : 'Activar'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Renderizar actividad reciente
     */
    private function renderRecentActivity(array $activity): void {
        if (empty($activity)) {
            echo '<p class="text-light-emphasis">No hay actividad reciente.</p>';
            return;
        }
        
        ?>
        <div class="timeline">
            <?php foreach ($activity as $item): ?>
            <div class="timeline-item mb-3">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <span class="badge bg-<?php echo esc_attr($item['type']); ?> rounded-pill">
                            <?php echo esc_html($item['icon']); ?>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1 text-light"><?php echo esc_html($item['message']); ?></p>
                        <small class="text-light-emphasis"><?php echo esc_html($item['time']); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Obtener información del sistema
     */
    private function getSystemInfo(): array {
        return [
            'plugin_host' => $this->config->get('host_plugin.name'),
            'dev_tools_version' => $this->config->get('version'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory' => [
                'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
                'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
                'limit' => ini_get('memory_limit')
            ],
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ];
    }
    
    /**
     * Obtener estado de módulos para mostrar
     */
    private function getModulesStatusForDisplay(): array {
        $manager = DevToolsModuleManager::getInstance();
        $all_modules = $manager->getModulesStatus();
        $active_modules = array_filter($all_modules, function($module) {
            return $module['active'];
        });
        
        return [
            'all' => $all_modules,
            'active' => $active_modules,
            'total' => count($all_modules),
            'active_count' => count($active_modules)
        ];
    }
    
    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity(): array {
        // Por ahora devolvemos actividad simulada
        // En el futuro esto vendrá de un sistema de logs
        return [
            [
                'type' => 'success',
                'icon' => '✓',
                'message' => 'Sistema iniciado correctamente',
                'time' => 'Hace 5 minutos'
            ],
            [
                'type' => 'info',
                'icon' => 'i',
                'message' => 'Módulos cargados: dashboard, system_info, cache',
                'time' => 'Hace 5 minutos'
            ],
            [
                'type' => 'warning',
                'icon' => '!',
                'message' => 'Cache limpiado automáticamente',
                'time' => 'Hace 1 hora'
            ]
        ];
    }
    
    /**
     * AJAX: Obtener estadísticas del dashboard
     */
    public function getDashboardStats($data): array {
        return [
            'system_info' => $this->getSystemInfo(),
            'modules_status' => $this->getModulesStatusForDisplay(),
            'recent_activity' => $this->getRecentActivity(),
            'timestamp' => current_time('c')
        ];
    }
    
    /**
     * AJAX: Refrescar datos del dashboard
     */
    public function refreshDashboardData($data): array {
        // Limpiar cache del dashboard
        delete_transient('dev_tools_dashboard_cache');
        
        return $this->getDashboardStats($data);
    }
    
    /**
     * AJAX: Obtener estado de módulos
     */
    public function getModulesStatus($data): array {
        return $this->getModulesStatusForDisplay();
    }
    
    /**
     * AJAX: Alternar estado de módulo
     */
    public function toggleModule($data): array {
        $module_name = sanitize_text_field($data['module_name'] ?? '');
        
        if (empty($module_name)) {
            throw new Exception('Module name required');
        }
        
        $manager = DevToolsModuleManager::getInstance();
        $active_modules = $manager->getActiveModules();
        
        if (isset($active_modules[$module_name])) {
            // Desactivar módulo
            $success = $manager->disableModule($module_name);
            $action = 'disabled';
        } else {
            // Activar módulo
            $success = $manager->enableModule($module_name);
            $action = 'enabled';
        }
        
        if (!$success) {
            throw new Exception("Failed to {$action} module: {$module_name}");
        }
        
        return [
            'module' => $module_name,
            'action' => $action,
            'success' => true,
            'new_status' => $this->getModulesStatusForDisplay()
        ];
    }
    
    /**
     * Registrar AJAX para admin
     */
    public function registerAdminAjax(): void {
        // Los comandos AJAX se registran automáticamente a través del AjaxHandler
    }
    
    /**
     * Verificar si Bootstrap está disponible
     */
    private function isBootstrapAvailable(): bool {
        global $wp_styles;
        return isset($wp_styles->registered['bootstrap']);
    }
    
    /**
     * Enqueue Bootstrap si no está disponible
     */
    private function enqueueBootstrap(): void {
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.0',
            true
        );
    }
}