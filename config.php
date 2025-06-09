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
                'action_name' => $slug . '_dev_tools', // Acción completa de WordPress
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
            'ajaxAction' => $this->get('ajax.action_name'), // CORRECCIÓN: usar ajax.action_name consistente
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
            // INDEPENDIENTE: usar constantes de dev-tools, no del plugin principal
            (defined('DEV_TOOLS_PRODUCTION_MODE') && !DEV_TOOLS_PRODUCTION_MODE) || // true si no está en producción
            // Detección automática del modo del plugin host (sin dependencias específicas)
            $this->detect_host_development_mode()
        );
    }
    
    /**
     * Detectar automáticamente si el plugin host está en modo desarrollo
     * Sin depender de constantes específicas del plugin
     */
    private function detect_host_development_mode() {
        // Detección por patrones comunes de constantes de desarrollo
        $dev_patterns = [
            '_PRODUCTION_MODE',
            '_DEV_MODE',
            '_DEBUG_MODE',
            '_DEVELOPMENT_MODE'
        ];
        
        // Buscar constantes del plugin host con estos patrones
        $defined_constants = get_defined_constants();
        $host_namespace = strtoupper($this->get('host.namespace', 'UNKNOWN'));
        
        foreach ($dev_patterns as $pattern) {
            $constant_name = $host_namespace . $pattern;
            
            if (isset($defined_constants[$constant_name])) {
                // Si termina en PRODUCTION_MODE y es false, estamos en desarrollo
                if (strpos($pattern, 'PRODUCTION') !== false) {
                    return !$defined_constants[$constant_name];
                }
                // Si termina en DEV_MODE/DEBUG_MODE/DEVELOPMENT_MODE y es true, estamos en desarrollo
                if (strpos($pattern, 'DEV') !== false || 
                    strpos($pattern, 'DEBUG') !== false || 
                    strpos($pattern, 'DEVELOPMENT') !== false) {
                    return $defined_constants[$constant_name];
                }
            }
        }
        
        // Fallback: detectar por entorno
        return (
            defined('WP_DEBUG') && WP_DEBUG ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'staging') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'dev') !== false
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
        // IMPORTANTE: Estas se basan en el modo de Dev-Tools, no en el plugin host
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
     * Obtener datos completos de debug para validación programática
     * Consolida funcionalidad de debug-wordpress-dynamic.php
     * 
     * @return array Datos de configuración y URLs para validación
     */
    public function get_debug_data() {
        $data = [
            'success' => true,
            'dev_tools_loaded' => true,
            'urls' => $this->get_all_urls(),
            'config' => $this->get_debug_config(),
            'host_plugin' => $this->host_plugin,
            'issues' => []
        ];
        
        // Validar consistencia automáticamente
        $data['issues'] = $this->validate_configuration_consistency($data['urls'], $data['config']);
        
        return $data;
    }
    
    /**
     * Obtener todas las URLs dinámicas del sistema
     * 
     * @return array URLs dinámicas detectadas
     */
    public function get_all_urls() {
        return [
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'admin_url' => get_admin_url(),
            'admin_ajax_url' => admin_url('admin-ajax.php'),
            'plugins_url' => plugins_url(),
            'dev_tools_url' => $this->get_admin_url('tools.php?page=' . $this->get('dev_tools.menu_slug')),
            'current_url' => isset($_SERVER['REQUEST_URI']) ? home_url($_SERVER['REQUEST_URI']) : null,
            'plugin_url' => $this->host_plugin['dir_url'],
            'dev_tools_base_url' => $this->host_plugin['dir_url'] . 'dev-tools/'
        ];
    }
    
    /**
     * Obtener configuración crítica para debug
     * 
     * @return array Configuración crítica del sistema
     */
    public function get_debug_config() {
        return [
            'js_config_var' => $this->get('dev_tools.js_config_var'),
            'menu_slug' => $this->get('dev_tools.menu_slug'),
            'ajax_action' => $this->get('ajax.action_name'),
            'nonce_action' => $this->get('ajax.nonce_action'),
            'js_handle' => $this->get('assets.js_handle'),
            'debug_mode' => $this->is_debug_mode(),
            'host_name' => $this->get('host.name'),
            'host_slug' => $this->get('host.slug'),
            'text_domain' => $this->get('host.text_domain')
        ];
    }
    
    /**
     * Validar consistencia de URLs y configuración
     * Consolida funcionalidad de debug-wordpress-dynamic.php
     * 
     * @param array $urls URLs detectadas
     * @param array $config Configuración detectada
     * @return array Lista de issues encontrados
     */
    public function validate_configuration_consistency($urls, $config) {
        $issues = [];
        
        // Validar que todas las URLs tengan el mismo protocolo y dominio base
        $base_patterns = [];
        foreach ($urls as $key => $url) {
            if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                $parsed = parse_url($url);
                $base = $parsed['scheme'] . '://' . $parsed['host'];
                $base_patterns[$key] = $base;
            } else {
                $issues[] = "URL inválida detectada: {$key} = {$url}";
            }
        }
        
        // Verificar consistencia de dominios
        $unique_bases = array_unique($base_patterns);
        if (count($unique_bases) > 1) {
            $issues[] = "Inconsistencia de dominios detectada: " . implode(', ', $unique_bases);
        }
        
        // Validar configuración crítica
        if (empty($config['ajax_action'])) {
            $issues[] = "AJAX action no configurado";
        }
        
        if (empty($config['nonce_action'])) {
            $issues[] = "Nonce action no configurado";
        }
        
        if (empty($config['js_config_var'])) {
            $issues[] = "Variable JavaScript no configurada";
        }
        
        // Validar configuración del host
        if (empty($config['host_name'])) {
            $issues[] = "Plugin host no detectado correctamente";
        }
        
        return $issues;
    }
    
    /**
     * Registrar issues en el error log con formato consistente
     * Consolida funcionalidad de debug-wordpress-dynamic.php
     * 
     * @param array $issues Lista de problemas encontrados
     * @param string $context Contexto del debug (opcional)
     */
    public function log_configuration_issues($issues, $context = 'URL_CONSISTENCY') {
        if (!empty($issues)) {
            $this->log("🔧 DEV-TOOLS {$context} ISSUES:");
            foreach ($issues as $issue) {
                $this->log("   - {$issue}");
            }
        }
    }
    
    /**
     * Generar output HTML de debug para desarrollo
     * Consolida funcionalidad de debug-wordpress-dynamic.php
     * 
     * @param bool $return_html Si retornar HTML en lugar de imprimirlo
     * @return string|void HTML de debug o imprime directamente
     */
    public function render_debug_output($return_html = false) {
        $debug_data = $this->get_debug_data();
        
        $output = '<div id="wpcontent"><pre style="background:rgb(39, 39, 39); color:#fff; padding: 20px; font-family: monospace; border: 1px solid #ccc; margin: 20px;">';
        $output .= "🔧 === DEBUG CONFIGURACIÓN DEV-TOOLS (WORDPRESS REAL) ===\n\n";
        
        $output .= "✅ DevTools cargado correctamente\n\n";
        
        $output .= "📋 INFORMACIÓN DEL HOST PLUGIN:\n";
        $output .= "────────────────────────────\n";
        $output .= "Name: " . $this->get('host.name') . "\n";
        $output .= "Slug: " . $this->get('host.slug') . "\n";
        $output .= "Text Domain: " . $this->get('host.text_domain') . "\n";
        $output .= "Dir Path: " . $this->get('host.dir_path') . "\n";
        $output .= "Dir URL: " . $this->get('host.dir_url') . "\n";
        
        $output .= "\n📋 URLs DINÁMICAS REALES:\n";
        $output .= "─────────────────────────\n";
        foreach ($debug_data['urls'] as $key => $url) {
            $output .= ucfirst(str_replace('_', ' ', $key)) . ": " . ($url ?: 'N/A') . "\n";
        }
        
        $output .= "\n📋 CONFIGURACIÓN JAVASCRIPT REAL:\n";
        $output .= "─────────────────────────────────\n";
        $js_config = $this->get_js_config();
        foreach ($js_config as $key => $value) {
            $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            $output .= "  {$key}: {$display_value}\n";
        }
        
        // Mostrar issues si los hay
        if (!empty($debug_data['issues'])) {
            $output .= "\n⚠️  ISSUES DETECTADOS:\n";
            $output .= "───────────────────\n";
            foreach ($debug_data['issues'] as $issue) {
                $output .= "❌ {$issue}\n";
            }
        } else {
            $output .= "\n✅ NO SE DETECTARON ISSUES\n";
        }
        
        $output .= "\n📋 MODO DEBUG:\n";
        $output .= "─────────────\n";
        $output .= "Debug Mode: " . ($this->is_debug_mode() ? 'ACTIVADO' : 'DESACTIVADO') . "\n";
        $output .= "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . "\n";
        
        $output .= '</pre></div>';
        
        if ($return_html) {
            return $output;
        } else {
            echo $output;
        }
    }
}

// Inicializar configuración global
function dev_tools_config() {
    return DevToolsConfig::getInstance();
}

// Registrar constantes automáticamente
dev_tools_config()->register_constants();
