<?php
/**
 * Dev-Tools Admin Panel - Bootstrap 5 Interface
 * 
 * Panel de administración agnóstico con Bootstrap 5
 * Funciona independientemente del plugin host
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
                                <small class="d-block">Entorno: <strong><?php echo $this->get_environment_badge(); ?></strong></small>
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
                                Framework agnóstico para desarrollo WordPress | 
                                <a href="<?php echo esc_url($this->config['developer']['website']); ?>" target="_blank">
                                    Documentación
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza la navegación principal
     */
    private function render_navigation() {
        $current_page = $_GET['page'] ?? $this->config['menu']['slug'];
        
        $nav_items = [
            $this->config['menu']['slug'] => ['Dashboard', 'dashicons-dashboard'],
            $this->config['menu']['slug'] . '-system-info' => ['System Info', 'dashicons-info'],
            $this->config['menu']['slug'] . '-database' => ['Database', 'dashicons-database'],
            $this->config['menu']['slug'] . '-ajax-tester' => ['AJAX Tester', 'dashicons-rest-api'],
            $this->config['menu']['slug'] . '-tests' => ['Tests', 'dashicons-yes-alt']
        ];
        ?>
        <nav class="nav nav-pills nav-fill bg-light p-2 rounded">
            <?php foreach ($nav_items as $slug => $item): ?>
                <a class="nav-link <?php echo $current_page === $slug ? 'active' : ''; ?>" 
                   href="<?php echo admin_url('admin.php?page=' . $slug); ?>">
                    <span class="dashicons <?php echo $item[1]; ?>"></span>
                    <?php echo esc_html($item[0]); ?>
                </a>
            <?php endforeach; ?>
        </nav>
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
     * Dashboard principal
     */
    public function render_dashboard() {
        $this->render_header('Dashboard');
        ?>
        <!-- Content Dashboard -->
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
                    <div class="card-header">
                        <h5 class="mb-0">🧩 Módulos Cargados</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_modules_list(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Acciones rápidas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">⚡ Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_quick_actions(); ?>
                    </div>
                </div>
                
                <!-- Información del entorno -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">🌍 Entorno</h5>
                    </div>
                    <div class="card-body">
                        <?php $this->render_environment_info(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $this->render_footer();
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
     * Renderiza la lista de módulos
     */
    private function render_modules_list() {
        ?>
        <div class="row">
            <?php foreach ($this->modules as $name => $module): ?>
                <div class="col-md-6 mb-2">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2">📦</span>
                        <span><?php echo esc_html($name); ?></span>
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
        
        <!-- Modal para resultados -->
        <div class="modal fade" id="quickActionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Resultado de la Acción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="quickActionResult">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza información del entorno
     */
    private function render_environment_info() {
        $paths_info = $this->paths->get_debug_info();
        ?>
        <div class="small">
            <div class="mb-2">
                <strong>Base Path:</strong><br>
                <code class="small"><?php echo esc_html($paths_info['base_path']); ?></code>
            </div>
            <div class="mb-2">
                <strong>Base URL:</strong><br>
                <code class="small"><?php echo esc_html($paths_info['base_url']); ?></code>
            </div>
            <div class="mb-2">
                <strong>WordPress:</strong><br>
                <span class="badge bg-<?php echo $paths_info['wordpress_available'] ? 'success' : 'warning'; ?>">
                    <?php echo $paths_info['wordpress_available'] ? 'Disponible' : 'Limitado'; ?>
                </span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Página de System Info
     */
    public function render_system_info() {
        $this->render_header('System Info');
        ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📋 Información Completa del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div id="systemInfoContent">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Cargando información del sistema...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            devTools.loadSystemInfo();
        });
        </script>
        <?php
        $this->render_footer();
    }
    
    /**
     * Página de Database
     */
    public function render_database() {
        $this->render_header('Database Connection');
        ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🔌 Test de Conexión a Base de Datos</h5>
                    </div>
                    <div class="card-body">
                        <div id="databaseTestContent">
                            <button class="btn btn-primary" onclick="devTools.testDatabaseDetailed()">
                                Ejecutar Test Completo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">ℹ️ Información</h5>
                    </div>
                    <div class="card-body">
                        <p class="small">Este test verifica la conexión a MySQL en entornos Local by WP Engine usando Unix socket.</p>
                        <p class="small">También funciona con TCP/IP estándar en otros entornos.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $this->render_footer();
    }
    
    /**
     * Página de AJAX Tester
     */
    public function render_ajax_tester() {
        $this->render_header('AJAX Tester');
        ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🚀 AJAX Testing Interface</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Comandos Disponibles:</h6>
                                <div class="list-group mb-3">
                                    <button class="list-group-item list-group-item-action" onclick="devTools.ajaxTest('test_connection')">
                                        test_connection - Test database connection
                                    </button>
                                    <button class="list-group-item list-group-item-action" onclick="devTools.ajaxTest('system_info')">
                                        system_info - Get system information
                                    </button>
                                    <button class="list-group-item list-group-item-action" onclick="devTools.ajaxTest('site_url_detection')">
                                        site_url_detection - Test URL detection
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Resultado:</h6>
                                <div id="ajaxTestResult" class="border p-3" style="min-height: 200px; background-color: #f8f9fa;">
                                    <em>Selecciona un comando para ver el resultado...</em>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $this->render_footer();
    }
    
    /**
     * Página de Tests (PHPUnit)
     */
    public function render_tests() {
        $this->render_header('Test Suite');
        ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🧪 PHPUnit Test Suite</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button class="btn btn-success me-2" onclick="devTools.runTestSuite('all')">
                                ▶️ Run All Tests
                            </button>
                            <button class="btn btn-primary me-2" onclick="devTools.runTestSuite('unit')">
                                🔬 Unit Tests
                            </button>
                            <button class="btn btn-info me-2" onclick="devTools.runTestSuite('integration')">
                                🔗 Integration Tests
                            </button>
                            <button class="btn btn-warning" onclick="devTools.runTestSuite('environment')">
                                🌍 Environment Tests
                            </button>
                        </div>
                        
                        <div id="testResults" class="mt-4">
                            <!-- Los resultados se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📊 Test Coverage</h5>
                    </div>
                    <div class="card-body">
                        <div id="testCoverage">
                            <p class="small text-muted">Ejecuta los tests para ver el coverage...</p>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">⚙️ Test Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="verboseOutput" checked>
                            <label class="form-check-label" for="verboseOutput">
                                Verbose Output
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="stopOnFailure">
                            <label class="form-check-label" for="stopOnFailure">
                                Stop on Failure
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
        </div>
        <?php
        $this->render_footer();
    }
}
