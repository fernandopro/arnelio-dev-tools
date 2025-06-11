<?php
/**
 * Dev-Tools Arquitectura 3.0 - Loader Principal
 * 
 * Sistema agnóstico de carga que funciona independientemente del plugin host
 * Diseñado para ser submódulo universal de Git
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

// Seguridad - No acceso directo
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

// Constante para identificar dev-tools
if (!defined('DEV_TOOLS_LOADED')) {
    define('DEV_TOOLS_LOADED', true);
    define('DEV_TOOLS_VERSION', '3.0.0');
}

/**
 * Clase principal del sistema Dev-Tools
 * Implementa patrón Singleton para una sola instancia global
 */
class DevToolsLoader {
    
    private static $instance = null;
    private $config = null;
    private $modules = [];
    private $ajax_handler = null;
    private $admin_panel = null;
    
    private function __construct() {
        $this->init();
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicialización del sistema
     */
    private function init() {
        // Cargar configuración de rutas
        require_once __DIR__ . '/config/paths.php';
        
        // Cargar configuración principal
        $this->load_config();
        
        // Registrar hooks de WordPress
        add_action('init', [$this, 'init_system'], 5);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handler
        add_action('wp_ajax_dev_tools_ajax', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_dev_tools_ajax', [$this, 'handle_ajax']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔧 Dev-Tools Loader initialized');
        }
    }
    
    /**
     * Carga la configuración del sistema
     */
    private function load_config() {
        $config_file = __DIR__ . '/config/config.php';
        if (file_exists($config_file)) {
            $this->config = include $config_file;
        } else {
            $this->config = $this->get_default_config();
        }
    }
    
    /**
     * Configuración por defecto del sistema
     */
    private function get_default_config() {
        return [
            'name' => 'Dev-Tools Arquitectura 3.0',
            'version' => DEV_TOOLS_VERSION,
            'menu_slug' => 'dev-tools',
            'capability' => 'manage_options',
            'modules_enabled' => [
                'DashboardModule',
                'SystemInfoModule',
                'DatabaseConnectionModule',
                'SiteUrlDetectionModule',
                'CacheModule',
                'AjaxTesterModule',
                'LogsModule',
                'PerformanceModule'
            ]
        ];
    }
    
    /**
     * Inicialización del sistema en hook 'init'
     */
    public function init_system() {
        // Cargar módulos
        $this->load_modules();
        
        // Inicializar AJAX handler
        $this->init_ajax_handler();
        
        // Inicializar admin panel
        $this->init_admin_panel();
    }
    
    /**
     * Carga automática de módulos
     */
    private function load_modules() {
        $modules_dir = __DIR__ . '/modules/';
        
        // Auto-discovery de módulos
        $module_files = glob($modules_dir . '*Module.php');
        
        foreach ($module_files as $module_file) {
            $module_name = basename($module_file, '.php');
            
            // Verificar si el módulo está habilitado
            if (in_array($module_name, $this->config['modules_enabled'])) {
                require_once $module_file;
                
                if (class_exists($module_name)) {
                    $this->modules[$module_name] = new $module_name();
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("🔧 Dev-Tools Module loaded: {$module_name}");
                    }
                }
            }
        }
    }
    
    /**
     * Inicializa el manejador AJAX
     */
    private function init_ajax_handler() {
        require_once __DIR__ . '/includes/class-ajax-handler.php';
        $this->ajax_handler = new DevToolsAjaxHandler($this->modules);
    }
    
    /**
     * Inicializa el panel de administración
     */
    private function init_admin_panel() {
        require_once __DIR__ . '/includes/class-admin-panel.php';
        $this->admin_panel = new DevToolsAdminPanel($this->config, $this->modules);
    }
    
    /**
     * Registra el menú de administración
     */
    public function register_admin_menu() {
        add_menu_page(
            $this->config['name'],
            'Dev-Tools',
            $this->config['capability'],
            $this->config['menu_slug'],
            [$this->admin_panel, 'render_dashboard'],
            'dashicons-admin-tools',
            80
        );
        
        // Submenús
        add_submenu_page(
            $this->config['menu_slug'],
            'System Info',
            'System Info',
            $this->config['capability'],
            $this->config['menu_slug'] . '-system-info',
            [$this->admin_panel, 'render_system_info']
        );
        
        add_submenu_page(
            $this->config['menu_slug'],
            'Database',
            'Database',
            $this->config['capability'],
            $this->config['menu_slug'] . '-database',
            [$this->admin_panel, 'render_database']
        );
        
        add_submenu_page(
            $this->config['menu_slug'],
            'AJAX Tester',
            'AJAX Tester',
            $this->config['capability'],
            $this->config['menu_slug'] . '-ajax-tester',
            [$this->admin_panel, 'render_ajax_tester']
        );
        
        add_submenu_page(
            $this->config['menu_slug'],
            'Tests',
            'Tests',
            $this->config['capability'],
            $this->config['menu_slug'] . '-tests',
            [$this->admin_panel, 'render_tests']
        );
    }
    
    /**
     * Encola assets de administración
     */
    public function enqueue_admin_assets($hook) {
        // Solo cargar en páginas de dev-tools
        if (strpos($hook, $this->config['menu_slug']) === false) {
            return;
        }
        
        $paths = DevToolsPaths::getInstance();
        
        // Bootstrap 5 CSS
        wp_enqueue_style(
            'dev-tools-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        // CSS personalizado
        wp_enqueue_style(
            'dev-tools-admin',
            $paths->get_url('assets/css/admin.css'),
            ['dev-tools-bootstrap'],
            DEV_TOOLS_VERSION
        );
        
        // Bootstrap 5 JS
        wp_enqueue_script(
            'dev-tools-bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.0',
            true
        );
        
        // JavaScript principal
        wp_enqueue_script(
            'dev-tools-admin',
            $paths->get_url('assets/js/admin.js'),
            ['jquery', 'dev-tools-bootstrap-js'],
            DEV_TOOLS_VERSION,
            true
        );
        
        // Localizar script para AJAX
        wp_localize_script('dev-tools-admin', 'devToolsAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dev_tools_nonce'),
            'action' => 'dev_tools_ajax'
        ]);
    }
    
    /**
     * Maneja peticiones AJAX
     */
    public function handle_ajax() {
        if ($this->ajax_handler) {
            $this->ajax_handler->handle_request();
        }
    }
    
    /**
     * Obtiene un módulo específico
     */
    public function get_module($name) {
        return isset($this->modules[$name]) ? $this->modules[$name] : null;
    }
    
    /**
     * Obtiene todos los módulos
     */
    public function get_modules() {
        return $this->modules;
    }
    
    /**
     * Obtiene la configuración
     */
    public function get_config() {
        return $this->config;
    }
}

// Inicializar el sistema automáticamente
DevToolsLoader::getInstance();
