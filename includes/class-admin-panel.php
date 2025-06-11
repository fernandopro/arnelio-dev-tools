<?php
/**
 * Dev-Tools Admin Panel - Bootstrap 5 Interface
 * Panel de administración agnóstico con Bootstrap 5 y sistema de pestañas
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DevToolsAdminPanel {
    
    private $config;
    private $modules;
    private $paths;
    
    public function __construct($config, $modules) {
        $this->config = $config;
        $this->modules = $modules;
        $this->paths = DevToolsPaths::getInstance();
    }
    
    /**
     * Renderiza el header común de todas las páginas
     */
    private function render_header($title = 'Dashboard') {

        ?>
        <div class="wrap">
            <div class="container-fluid">
                <!-- Header Principal -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center bg-primary text-white p-3 rounded">
                            <div>
                                <h1 class="h3 mb-1">🔧 <?php echo esc_html($this->config['name']); ?></h1>
                                <p class="mb-0 opacity-75">v<?php echo esc_html($this->config['version']); ?> - <?php echo esc_html($title); ?></p>
                            </div>
                            <div class="text-end">
                                <small class="d-block">Entorno: <?php echo $this->get_environment_badge(); ?></small>
                                <small>WordPress <?php echo get_bloginfo('version'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navegación -->
                <div class="row mb-4">
                    <div class="col-12">
                        <?php $this->render_navigation(); ?>
                    </div>
                </div>
        <?php
    }
    
    /**
     * Renderiza el footer común
     */
    private function render_footer() {
        ?>
                <!-- Footer -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="border-top pt-3 text-muted text-center">
                            <small>
                                <?php echo esc_html($this->config['name']); ?> - 
                                Framework agnóstico para desarrollo WordPress
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza la navegación de pestañas
     */
    private function render_navigation() {
        $nav_items = [
            'dashboard' => ['Dashboard', 'dashicons-dashboard', true],
            'system-info' => ['System Info', 'dashicons-info', false],
            'database' => ['Database', 'dashicons-database', false],
            'ajax-tester' => ['AJAX Tester', 'dashicons-rest-api', false],
            'tests' => ['Tests', 'dashicons-yes-alt', false]
        ];
        ?>
        <ul class="nav nav-tabs nav-fill bg-light rounded" role="tablist">
            <?php foreach ($nav_items as $tab_id => $item): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $item[2] ? 'active' : ''; ?>" 
                            id="<?php echo $tab_id; ?>-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#<?php echo $tab_id; ?>" 
                            type="button" 
                            role="tab" 
                            aria-controls="<?php echo $tab_id; ?>" 
                            aria-selected="<?php echo $item[2] ? 'true' : 'false'; ?>">
                        <span class="dashicons <?php echo $item[1]; ?>"></span>
                        <?php echo esc_html($item[0]); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
    
    /**
     * Obtiene el badge del entorno actual
     */
    private function get_environment_badge() {
        $site_detector = $this->modules['SiteUrlDetectionModule'] ?? null;
        if ($site_detector) {
            $env_info = $site_detector->get_environment_info();
            if ($env_info['is_local_wp']) {
                return '<span class="badge bg-warning text-dark">Local by WP Engine</span>';
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return '<span class="badge bg-info">Development</span>';
        }
        
        return '<span class="badge bg-success">Production</span>';
    }
    
    /**
     * Renderiza la página principal con pestañas
     */
    public function render_dashboard() {
        $this->render_header('Dev-Tools Console');
        ?>
        
        <!-- Contenido con pestañas -->
        <div class="tab-content" id="devToolsTabContent">
            
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <?php $this->render_dashboard_content(); ?>
            </div>
            
            <!-- System Info Tab -->
            <div class="tab-pane fade" id="system-info" role="tabpanel" aria-labelledby="system-info-tab">
                <?php $this->render_system_info_content(); ?>
            </div>
            
            <!-- Database Tab -->
            <div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
                <?php $this->render_database_content(); ?>
            </div>
            
            <!-- AJAX Tester Tab -->
            <div class="tab-pane fade" id="ajax-tester" role="tabpanel" aria-labelledby="ajax-tester-tab">
                <?php $this->render_ajax_tester_content(); ?>
            </div>
            
            <!-- Tests Tab -->
            <div class="tab-pane fade" id="tests" role="tabpanel" aria-labelledby="tests-tab">
                <?php $this->render_tests_content(); ?>
            </div>
            
        </div>
        
        <?php
        $this->render_footer();
    }
    
    /**
     * Contenido del Dashboard
     */
    private function render_dashboard_content() {
        ?>
        <div class="row">
            <!-- Columna principal -->
            <div class="col-lg-8">
                <!-- Estado del sistema -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📊 Estado del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_system_status(); ?>
                    </div>
                </div>
                
                <!-- Módulos cargados -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">🔧 Módulos Activos</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_modules_status(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Columna lateral -->
            <div class="col-lg-4">
                <!-- Acciones rápidas -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">⚡ Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_quick_actions(); ?>
                    </div>
                </div>
                
                <!-- Información del entorno -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">🌍 Entorno</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_environment_info(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza el estado del sistema
     */
    private function render_system_status() {
        $db_module = $this->modules['DatabaseConnectionModule'] ?? null;
        $site_module = $this->modules['SiteUrlDetectionModule'] ?? null;
        
        $checks = [
            'WordPress' => ['success', get_bloginfo('version')],
            'PHP' => ['success', phpversion()],
            'Database' => $db_module ? $this->test_database_status($db_module) : ['warning', 'Módulo no cargado'],
            'Site URL' => $site_module ? $this->test_site_url_status($site_module) : ['warning', 'Módulo no cargado'],
            'Dev-Tools' => ['success', $this->config['version']]
        ];
        ?>
        <div class="row">
            <?php foreach ($checks as $name => $status): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-<?php echo $status[0] === 'success' ? 'success' : ($status[0] === 'warning' ? 'warning' : 'danger'); ?> me-2">
                            <?php echo $status[0] === 'success' ? '✅' : ($status[0] === 'warning' ? '⚠️' : '❌'); ?>
                        </span>
                        <div>
                            <strong><?php echo esc_html($name); ?></strong><br>
                            <small class="text-muted"><?php echo esc_html($status[1]); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el estado de los módulos
     */
    private function render_modules_status() {
        ?>
        <div class="row">
            <?php foreach ($this->modules as $name => $module): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2">📦</span>
                        <div>
                            <strong><?php echo esc_html($name); ?></strong><br>
                            <small class="text-muted">
                                <?php 
                                if (method_exists($module, 'get_status')) {
                                    echo esc_html($module->get_status());
                                } else {
                                    echo 'Activo';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza acciones rápidas
     */
    private function render_quick_actions() {
        ?>
        <div class="d-grid gap-2">
            <button class="btn btn-outline-primary" onclick="devTools.testDatabase()">
                🔌 Test Database
            </button>
            <button class="btn btn-outline-info" onclick="devTools.testSiteUrl()">
                🌐 Test Site URL
            </button>
            <button class="btn btn-outline-success" onclick="devTools.runTests()">
                🧪 Run Tests
            </button>
            <button class="btn btn-outline-warning" onclick="devTools.clearCache()">
                🗑️ Clear Cache
            </button>
        </div>
        <?php
    }
    
    /**
     * Renderiza información del entorno
     */
    private function render_environment_info() {
        $site_detector = $this->modules['SiteUrlDetectionModule'] ?? null;
        $db_module = $this->modules['DatabaseConnectionModule'] ?? null;
        
        if ($site_detector) {
            $env_info = $site_detector->get_environment_info();
            ?>
            <div class="mb-3">
                <h6>🌍 Tipo de Entorno</h6>
                <?php if ($env_info['is_local_wp']): ?>
                    <span class="badge bg-warning text-dark">Local by WP Engine</span>
                    <?php if ($db_module): ?>
                        <?php 
                        $db_env_info = $db_module->get_environment_info();
                        $socket_path = $db_env_info['socket_path'] ?? 'N/A';
                        ?>
                        <p class="small mt-1 mb-0">Socket: <?php echo esc_html($socket_path); ?></p>
                    <?php else: ?>
                        <p class="small mt-1 mb-0">Socket: <em>DatabaseModule no disponible</em></p>
                    <?php endif; ?>
                <?php elseif (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <span class="badge bg-info">Development</span>
                <?php else: ?>
                    <span class="badge bg-success">Production</span>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <h6>🔗 URLs Detectadas</h6>
                <p class="small mb-1"><strong>Site URL:</strong> <?php echo esc_html($site_detector->get_site_url()); ?></p>
                <p class="small mb-0"><strong>Admin URL:</strong> <?php echo esc_html($site_detector->get_admin_url()); ?></p>
            </div>
            <?php
        }
        
        ?>
        <div class="mb-3">
            <h6>📊 Recursos</h6>
            <p class="small mb-1"><strong>Memory Usage:</strong> <?php echo size_format(memory_get_usage(true)); ?></p>
            <p class="small mb-0"><strong>Peak Memory:</strong> <?php echo size_format(memory_get_peak_usage(true)); ?></p>
        </div>
        <?php
    }
    
    /**
     * Test del estado de la base de datos
     */
    private function test_database_status($db_module) {
        try {
            $test_result = $db_module->test_connection();
            return $test_result['success'] ? ['success', 'Conectado'] : ['danger', 'Error de conexión'];
        } catch (Exception $e) {
            return ['danger', 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test del estado de detección de URL
     */
    private function test_site_url_status($site_module) {
        $url = $site_module->get_site_url();
        return $url ? ['success', $url] : ['warning', 'No detectado'];
    }
    
    /**
     * Contenido de System Info
     */
    private function render_system_info_content() {
        ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">ℹ️ Información del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_system_info_details(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Contenido de Database
     */
    private function render_database_content() {
        ?>
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">💾 Estado de Conexión</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_database_status(); ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">🔧 Pruebas de Conexión</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="devTools.testDatabase()">
                                🔌 Test Database Connection
                            </button>
                            <button class="btn btn-outline-info" onclick="devTools.testSocket()">
                                🔗 Test Socket Connection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Contenido de AJAX Tester
     */
    private function render_ajax_tester_content() {
        ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">🌐 AJAX Request Tester</h5>
                    </div>
                    <div class="card-body">
                        <form id="ajaxTestForm">
                            <div class="mb-3">
                                <label for="ajaxCommand" class="form-label">Comando AJAX</label>
                                <select class="form-select" id="ajaxCommand">
                                    <option value="test_connection">test_connection</option>
                                    <option value="get_system_info">get_system_info</option>
                                    <option value="run_test">run_test</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="ajaxData" class="form-label">Datos (JSON)</label>
                                <textarea class="form-control" id="ajaxData" rows="3">{}</textarea>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="devTools.testAjax()">
                                🚀 Enviar Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📊 Resultado</h5>
                    </div>
                    <div class="card-body">
                        <pre id="ajaxResult" class="bg-light p-3 rounded">No hay resultados aún...</pre>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Contenido de Tests
     */
    private function render_tests_content() {
        ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">🧪 Test Runner</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipos de Test</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="unitTests" checked>
                                        <label class="form-check-label" for="unitTests">
                                            Unit Tests
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="integrationTests">
                                        <label class="form-check-label" for="integrationTests">
                                            Integration Tests
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="environmentTests">
                                        <label class="form-check-label" for="environmentTests">
                                            Environment Tests
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Opciones</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="verboseOutput">
                                        <label class="form-check-label" for="verboseOutput">
                                            Verbose Output
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="generateCoverage">
                                        <label class="form-check-label" for="generateCoverage">
                                            Generate Coverage
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-success" onclick="devTools.runTests()">
                                🚀 Run Selected Tests
                            </button>
                            <button class="btn btn-outline-info" onclick="devTools.runQuickTest()">
                                ⚡ Quick Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📈 Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="testResults" class="bg-light p-3 rounded">
                            <p class="text-muted mb-0">No tests executed yet...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Detalles de información del sistema
     */
    private function render_system_info_details() {
        ?>
        <div class="row">
            <div class="col-md-6">
                <h6>WordPress</h6>
                <ul class="list-unstyled">
                    <li><strong>Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong>Site URL:</strong> <?php echo get_site_url(); ?></li>
                    <li><strong>Home URL:</strong> <?php echo get_home_url(); ?></li>
                    <li><strong>Admin URL:</strong> <?php echo admin_url(); ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>PHP</h6>
                <ul class="list-unstyled">
                    <li><strong>Version:</strong> <?php echo PHP_VERSION; ?></li>
                    <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
                    <li><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s</li>
                    <li><strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <h6>Database</h6>
                <?php global $wpdb; ?>
                <ul class="list-unstyled">
                    <li><strong>Version:</strong> <?php echo $wpdb->db_version(); ?></li>
                    <li><strong>Charset:</strong> <?php echo $wpdb->charset; ?></li>
                    <li><strong>Collate:</strong> <?php echo $wpdb->collate; ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Dev-Tools</h6>
                <ul class="list-unstyled">
                    <li><strong>Version:</strong> <?php echo $this->config['version']; ?></li>
                    <li><strong>Modules:</strong> <?php echo count($this->modules); ?></li>
                    <li><strong>Path:</strong> <?php echo $this->paths->get_path(); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Estado de la base de datos
     */
    private function render_database_status() {
        $db_module = $this->modules['DatabaseConnectionModule'] ?? null;
        if (!$db_module) {
            ?>
            <div class="alert alert-warning">
                <strong>Módulo Database no disponible</strong>
            </div>
            <?php
            return;
        }
        
        try {
            $test_result = $db_module->test_connection();
            $status_class = $test_result['success'] ? 'success' : 'danger';
            $status_icon = $test_result['success'] ? '✅' : '❌';
            
            // Construir mensaje basado en el resultado
            if ($test_result['success']) {
                $message = 'Conexión exitosa a la base de datos';
                if (isset($test_result['server_info'])) {
                    $message .= ' - ' . $test_result['server_info'];
                }
            } else {
                $message = $test_result['error'] ?? 'Error de conexión desconocido';
            }
            ?>
            <div class="alert alert-<?php echo $status_class; ?>">
                <h6><?php echo $status_icon; ?> Estado de Conexión</h6>
                <p class="mb-0"><?php echo esc_html($message); ?></p>
                <?php if (isset($test_result['dsn_used'])): ?>
                    <small class="text-muted d-block mt-1">
                        DSN: <?php echo esc_html($test_result['dsn_used']); ?>
                    </small>
                <?php endif; ?>
                <?php if (isset($test_result['test_query'])): ?>
                    <small class="text-muted d-block mt-1">
                        MySQL Version: <?php echo esc_html($test_result['test_query']['version'] ?? 'N/A'); ?>
                    </small>
                <?php endif; ?>
            </div>
            <?php
        } catch (Exception $e) {
            ?>
            <div class="alert alert-danger">
                <h6>❌ Error de Conexión</h6>
                <p class="mb-0"><?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        }
    }
}
