<?php
/**
 * Configuración Global de Dev Tools
 * Sistema plugin-agnóstico que detecta automáticamente el plugin host
 * CON SISTEMA DE OVERRIDE tipo Child Theme
 * 
 * @package DevTools
 * @version 3.0.0
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar sistema de override
require_once __DIR__ . '/core/FileOverrideSystem.php';

/**
 * Clase principal de configuración para Dev Tools
 * Detecta automáticamente el plugin host y configura todo dinámicamente
 * Incluye sistema de override de archivos tipo child theme
 */
class DevToolsConfig {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Configuración del plugin host
     */
    private $host_plugin = null;
    
    /**
     * Configuración dinámica
     */
    private $config = [];
    
    /**
     * Sistema de override de archivos
     */
    private $override_system = null;
    
    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        $this->detect_host_plugin();
        $this->init_override_system();
        $this->setup_config();
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener sistema de override
     */
    public function getOverrideSystem() {
        return $this->override_system;
    }
    
    /**
     * Inicializar sistema de override
     */
    private function init_override_system() {
        if ($this->host_plugin) {
            $this->override_system = new DevToolsFileOverrideSystem($this->host_plugin);
        }
    }
    
    /**
     * Detectar automáticamente el plugin host
     */
    private function detect_host_plugin() {
        $dev_tools_dir = __DIR__;
        $plugin_dir = dirname($dev_tools_dir);
        
        // Buscar archivos de plugin principales
        $plugin_files = glob($plugin_dir . '/*.php');
        
        foreach ($plugin_files as $file) {
            $plugin_data = get_file_data($file, [
                'Name' => 'Plugin Name',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author',
                'TextDomain' => 'Text Domain'
            ]);
            
            // Si encontramos datos válidos del plugin
            if (!empty($plugin_data['Name'])) {
                $this->host_plugin = [
                    'file' => $file,
                    'basename' => plugin_basename($file),
                    'dir_path' => $plugin_dir,
                    'dir_url' => plugin_dir_url($file),
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'] ?: '1.0.0',
                    'description' => $plugin_data['Description'],
                    'author' => $plugin_data['Author'],
                    'text_domain' => $plugin_data['TextDomain'] ?: 'dev-tools',
                    'slug' => basename($plugin_dir),
                    'namespace' => $this->generate_namespace($plugin_data['Name'])
                ];
                break;
            }
        }
        
        // Fallback si no se detecta plugin
        if (!$this->host_plugin) {
            $this->host_plugin = [
                'file' => $plugin_dir . '/plugin.php',
                'basename' => basename($plugin_dir) . '/plugin.php',
                'dir_path' => $plugin_dir,
                'dir_url' => plugins_url('/', $plugin_dir . '/plugin.php'),
                'name' => 'Unknown Plugin',
                'version' => '1.0.0',
                'description' => 'Host plugin for Dev Tools',
                'author' => 'Dev Tools',
                'text_domain' => 'dev-tools',
                'slug' => basename($plugin_dir),
                'namespace' => 'DevTools'
            ];
        }
    }
    
    /**
     * Configurar toda la configuración dinámica
     */
    private function setup_config() {
        $namespace = $this->host_plugin['namespace'];
        $slug = $this->host_plugin['slug'];
        
        $this->config = [
            // Información del plugin host
            'host' => $this->host_plugin,
            
            // Configuración de dev-tools
            'dev_tools' => [
                'dir_path' => __DIR__,
                'dir_url' => plugins_url('/', __FILE__),
                'version' => '2.0.0',
                'menu_slug' => $slug . '-dev-tools',
                'page_title' => $this->host_plugin['name'] . ' - Dev Tools',
                'menu_title' => 'Dev Tools',
                'capability' => 'manage_options',
                'ajax_action' => $slug . '_dev_tools',
                'nonce_key' => $slug . '_dev_tools_nonce',
                'js_config_var' => $this->sanitize_js_var($slug) . '_dev_tools_config'
            ],
            
            // Rutas dinámicas
            'paths' => [
                'plugin_root' => $this->host_plugin['dir_path'],
                'plugin_url' => $this->host_plugin['dir_url'],
                'dev_tools_root' => __DIR__,
                'dev_tools_url' => $this->host_plugin['dir_url'] . 'dev-tools/',
                'tests' => __DIR__ . '/tests',
                'docs' => __DIR__ . '/docs',
                'src_js' => __DIR__ . '/src/js',
                'dist' => __DIR__ . '/dist'
            ],
            
            // Configuración de testing
            'testing' => [
                'plugin_file' => $this->host_plugin['file'],
                'test_prefix' => $namespace . 'Test',
                'test_config_var' => $this->sanitize_js_var($slug) . '_test_config'
            ],
            
            // Configuración AJAX (CRÍTICO: faltaba esta sección)
            'ajax' => [
                'action_prefix' => $slug,
                'nonce_action' => $slug . '_dev_tools_nonce'
            ],
            
            // Assets dinámicos
            'assets' => [
                'css_handle' => $slug . '-dev-tools-css',
                'js_handle' => $slug . '-dev-tools-js',
                'js_settings_handle' => $slug . '-dev-tools-settings-js'
            ]
        ];
    }
    
    /**
     * Generar namespace desde el nombre del plugin
     */
    private function generate_namespace($plugin_name) {
        // Convertir a CamelCase y limpiar
        $namespace = str_replace([' ', '-', '_'], '', ucwords($plugin_name, ' -_'));
        // Remover caracteres no alfanuméricos
        $namespace = preg_replace('/[^a-zA-Z0-9]/', '', $namespace);
        return $namespace ?: 'DevTools';
    }
    
    /**
     * Sanitizar variable JavaScript
     */
    private function sanitize_js_var($string) {
        // Convertir a snake_case válido para JS
        $clean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $string));
        return trim($clean, '_');
    }
    
    /**
     * Obtener toda la configuración
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Obtener configuración específica
     */
    public function get($key, $default = null) {
        return $this->get_nested($this->config, $key, $default);
    }
    
    /**
     * Helper para obtener valores anidados con notación de puntos
     */
    private function get_nested($array, $key, $default = null) {
        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : $default;
        }
        
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    /**
     * Obtener URL del admin del plugin host
     */
    public function get_admin_url($path = '') {
        return admin_url($path);
    }
    
    /**
     * Obtener URL de la página actual del admin
     */
    public function get_current_page_url() {
        global $pagenow;
        
        // Construir URL base
        $base_url = admin_url($pagenow);
        
        // Agregar parámetros de consulta si existen
        $query_params = [];
        
        // Parámetro 'page' para páginas de administración personalizadas
        if (isset($_GET['page'])) {
            $query_params['page'] = sanitize_text_field($_GET['page']);
        }
        
        // Otros parámetros relevantes
        $relevant_params = ['tab', 'section', 'module'];
        foreach ($relevant_params as $param) {
            if (isset($_GET[$param])) {
                $query_params[$param] = sanitize_text_field($_GET[$param]);
            }
        }
        
        // Construir URL final
        if (!empty($query_params)) {
            $base_url = add_query_arg($query_params, $base_url);
        }
        
        return $base_url;
    }
    
    /**
     * Obtener configuración para JavaScript
     */
    public function get_js_config() {
        return [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->get('ajax.nonce_action')),
            'ajaxAction' => $this->get('dev_tools.ajax_action'),
            'actionPrefix' => $this->get('ajax.action_prefix'), // Usar configuración AJAX específica
            'menuSlug' => $this->get('dev_tools.menu_slug'), // Slug dinámico del menú
            'pluginName' => $this->get('host.name'),
            'pluginSlug' => $this->get('host.slug'),
            'devToolsUrl' => $this->get('paths.dev_tools_url'),
            'debugMode' => $this->is_debug_mode(),
            'verboseMode' => $this->is_verbose_mode(),
            // URLs dinámicas para JavaScript
            'baseAdminUrl' => get_admin_url(),
            'currentPageUrl' => $this->get_current_page_url()
        ];
    }
    
    /**
     * Verificar si está en modo debug
     */
    public function is_debug_mode() {
        return (
            defined('WP_DEBUG') && WP_DEBUG ||
            getenv('DEV_TOOLS_TESTS_DEBUG') === 'true' ||
            getenv('DEV_TOOLS_FORCE_DEBUG') === 'true' ||
            (defined('TAROKINA_PRODUCTION_MODE') && !TAROKINA_PRODUCTION_MODE) // true si no está en producción
        );
    }
    
    /**
     * Verificar si está en modo verbose
     */
    public function is_verbose_mode() {
        return (
            getenv('DEV_TOOLS_TESTS_VERBOSE') === 'true' ||
            $this->is_debug_mode()
        );
    }
    
    /**
     * Registrar constantes dinámicas para compatibilidad
     */
    public function register_constants() {
        $prefix = strtoupper($this->get('host.namespace'));
        
        // Constantes básicas del plugin
        if (!defined($prefix . '_PLUGIN_FILE')) {
            define($prefix . '_PLUGIN_FILE', $this->get('host.file'));
        }
        
        if (!defined($prefix . '_PLUGIN_DIR_PATH')) {
            define($prefix . '_PLUGIN_DIR_PATH', $this->get('host.dir_path'));
        }
        
        if (!defined($prefix . '_PLUGIN_DIR_URL')) {
            define($prefix . '_PLUGIN_DIR_URL', $this->get('host.dir_url'));
        }
        
        if (!defined($prefix . '_VERSION')) {
            define($prefix . '_VERSION', $this->get('host.version'));
        }
        
        // Constantes de modo de desarrollo/producción (compatibilidad)
        if (!defined($prefix . '_PRODUCTION_MODE')) {
            define($prefix . '_PRODUCTION_MODE', !$this->is_debug_mode());
        }
        
        if (!defined($prefix . '_DEV_MODE')) {
            define($prefix . '_DEV_MODE', $this->is_debug_mode());
        }
        
        // Constantes de Dev Tools
        if (!defined('DEV_TOOLS_PLUGIN_FILE')) {
            define('DEV_TOOLS_PLUGIN_FILE', $this->get('host.file'));
        }
        
        if (!defined('DEV_TOOLS_DIR_PATH')) {
            define('DEV_TOOLS_DIR_PATH', $this->get('paths.dev_tools_root'));
        }
        
        if (!defined('DEV_TOOLS_DIR_URL')) {
            define('DEV_TOOLS_DIR_URL', $this->get('paths.dev_tools_url'));
        }
        
        if (!defined('DEV_TOOLS_VERSION')) {
            define('DEV_TOOLS_VERSION', $this->get('dev_tools.version'));
        }
    }
    
    /**
     * Log interno para debugging
     */
    public function log($message, $data = null) {
        if ($this->is_debug_mode()) {
            error_log('[DEV-TOOLS-CONFIG] ' . $message);
            if ($data !== null) {
                error_log('[DEV-TOOLS-CONFIG-DATA] ' . print_r($data, true));
            }
        }
    }
    
    /**
     * Cargar archivo con sistema de override
     * 
     * @param string $relative_path Ruta relativa desde dev-tools/
     * @param bool $once Si usar include_once
     * @return mixed Resultado del include
     */
    public function include_file($relative_path, $once = true) {
        if ($this->override_system) {
            return $this->override_system->include_file($relative_path, $once);
        }
        
        // Fallback sin override system
        $file_path = __DIR__ . '/' . $relative_path;
        if (file_exists($file_path)) {
            return $once ? include_once $file_path : include $file_path;
        }
        
        return false;
    }
    
    /**
     * Cargar configuración específica con override
     * 
     * @param string $config_name Nombre del archivo de configuración
     * @return array Configuración mergeada
     */
    public function load_override_config($config_name = 'config-local.php') {
        if ($this->override_system) {
            return $this->override_system->load_config($config_name);
        }
        
        return [];
    }
    
    /**
     * Cargar template con override
     * 
     * @param string $template_name Nombre del template
     * @param array $vars Variables para el template
     * @return bool
     */
    public function load_template($template_name, $vars = []) {
        if ($this->override_system) {
            return $this->override_system->load_template($template_name, $vars);
        }
        
        // Fallback sin override system
        $template_file = __DIR__ . "/templates/{$template_name}";
        if (file_exists($template_file)) {
            if (!empty($vars)) {
                extract($vars, EXTR_SKIP);
            }
            include $template_file;
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener URL de archivo con override
     * 
     * @param string $relative_path Ruta relativa
     * @return string URL del archivo
     */
    public function get_file_url($relative_path) {
        if ($this->override_system) {
            return $this->override_system->get_file_url($relative_path);
        }
        
        // Fallback sin override system
        return plugins_url($relative_path, __FILE__);
    }
    
    /**
     * Verificar si existe override para un archivo
     * 
     * @param string $relative_path Ruta relativa
     * @return bool
     */
    public function has_override($relative_path) {
        if ($this->override_system) {
            return $this->override_system->has_override($relative_path);
        }
        
        return false;
    }
    
    /**
     * Migrar archivo a plugin-dev-tools para customización
     * 
     * @param string $relative_path Ruta relativa del archivo
     * @return bool
     */
    public function create_override($relative_path) {
        if ($this->override_system) {
            return $this->override_system->migrate_to_override($relative_path);
        }
        
        return false;
    }
    
    /**
     * Obtener información del sistema de override
     * 
     * @return array
     */
    public function get_override_info() {
        if ($this->override_system) {
            return $this->override_system->get_directory_info();
        }
        
        return [
            'parent_dir' => __DIR__,
            'child_dir' => $this->host_plugin['dir_path'] . '/plugin-dev-tools',
            'parent_exists' => true,
            'child_exists' => false,
            'overrides_count' => 0
        ];
    }
}

// Inicializar configuración global
function dev_tools_config() {
    return DevToolsConfig::getInstance();
}

// Registrar constantes automáticamente
dev_tools_config()->register_constants();
