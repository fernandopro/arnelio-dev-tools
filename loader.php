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

// // Seguridad - No acceso directo
// if (!defined('ABSPATH')) {
//     exit('Direct access not allowed');
// }

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
        
        // Cargar autoloader de Composer para PSR-4
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
        
        // Cargar configuración principal
        $this->load_config();
        
        // Cargar clases principales inmediatamente
        $this->load_core_classes();
        
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
     * Carga las clases principales del sistema
     */
    private function load_core_classes() {
        // Cargar clase admin panel
        require_once __DIR__ . '/includes/class-admin-panel.php';
        
        // Cargar clase AJAX handler
        require_once __DIR__ . '/includes/class-ajax-handler.php';
        
        // NO inicializar admin panel aquí - se hace después en init_system
    }
    
    /**
     * Carga la configuración del sistema
     */
    private function load_config() {
        $config_file = __DIR__ . '/config/config.php';
        if (file_exists($config_file)) {
            $config = include $config_file;
            
            // Aplanar estructura del menú para compatibilidad
            if (isset($config['menu'])) {
                $config['menu_slug'] = $config['menu']['slug'] ?? 'dev-tools';
                $config['capability'] = $config['menu']['capability'] ?? 'manage_options';
                $config['menu_icon'] = $config['menu']['icon'] ?? 'dashicons-admin-tools';
                $config['menu_position'] = $config['menu']['position'] ?? 80;
            }
            
            $this->config = $config;
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
        
        // Actualizar admin panel con módulos cargados
        $this->admin_panel = new DevToolsAdminPanel($this->config, $this->modules);
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
        $this->ajax_handler = new DevToolsAjaxHandler($this->modules);
    }
    
    /**
     * Registra el menú de administración en Tools
     */
    public function register_admin_menu() {
        // Asegurar que la configuración esté cargada
        if ($this->config === null) {
            $this->load_config();
        }
        
        // Asegurar que el admin panel esté inicializado
        if ($this->admin_panel === null) {
            $this->admin_panel = new DevToolsAdminPanel($this->config, $this->modules);
        }
        
        add_management_page(
            $this->config['name'] ?? 'Dev-Tools',
            'Dev-Tools',
            $this->config['capability'] ?? 'manage_options',
            $this->config['menu_slug'] ?? 'dev-tools',
            [$this->admin_panel, 'render_dashboard']
        );
    }
    
    /**
     * Encola assets de administración
     */
    public function enqueue_admin_assets($hook) {
        // Obtener menu_slug con fallback
        $menu_slug = $this->config['menu_slug'] ?? 'dev-tools';
        
        // Solo cargar en páginas de dev-tools
        if (strpos($hook, $menu_slug) === false) {
            return;
        }
        
        $paths = DevToolsPaths::getInstance();
        
        // CSS compilado con Webpack (incluye Bootstrap 5 + estilos personalizados)
        wp_enqueue_style(
            'dev-tools-styles',
            $paths->get_url('dist/css/dev-tools-styles.min.css'),
            [],
            DEV_TOOLS_VERSION
        );
        
        // Vendors JS (Bootstrap 5 y otras dependencias compiladas)
        wp_enqueue_script(
            'dev-tools-vendors',
            $paths->get_url('dist/js/vendors.min.js'),
            [],
            DEV_TOOLS_VERSION,
            true
        );
        
        // JavaScript principal compilado (ES6+ con módulos)
        wp_enqueue_script(
            'dev-tools-main',
            $paths->get_url('dist/js/dev-tools.min.js'),
            ['dev-tools-vendors'],
            DEV_TOOLS_VERSION,
            true
        );
        
        // Localizar script para AJAX - pasar datos al JavaScript moderno
        wp_localize_script('dev-tools-main', 'devToolsConfig', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dev_tools_nonce'),
            'action' => 'dev_tools_ajax',
            'baseUrl' => $paths->get_url(''),
            'version' => DEV_TOOLS_VERSION,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
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
