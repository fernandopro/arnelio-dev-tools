<?php
/**
 * Dev-Tools Arquitectura 3.0 - Configuración de Rutas Global
 * 
 * Sistema agnóstico de rutas que funciona independientemente de la ubicación
 * del dev-tools dentro del plugin host. Diseñado para ser submódulo universal.
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

// Seguridad - No acceso directo
if (!defined('ABSPATH') && !defined('DEV_TOOLS_DIRECT_ACCESS')) {
    exit('Direct access not allowed');
}

/**
 * Clase para manejo agnóstico de rutas del sistema dev-tools
 * Detecta automáticamente la ubicación y genera rutas dinámicas
 */
class DevToolsPaths {
    
    private static $instance = null;
    private $base_path = null;
    private $base_url = null;
    private $plugin_path = null;
    private $plugin_url = null;
    
    private function __construct() {
        $this->detect_paths();
    }
    
    /**
     * Singleton pattern para acceso global
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Detecta automáticamente las rutas base del sistema
     */
    private function detect_paths() {
        // Detectar ruta base de dev-tools
        $this->base_path = dirname(__DIR__) . '/';
        
        // Detectar ruta del plugin host
        $this->plugin_path = dirname($this->base_path) . '/';
        
        // Generar URLs si estamos en entorno WordPress
        if (function_exists('plugin_dir_url')) {
            // Método WordPress: usar plugin_dir_url() para URLs dinámicas
            $this->plugin_url = plugin_dir_url($this->plugin_path . 'index.php');
            $this->base_url = $this->plugin_url . 'dev-tools/';
        } else {
            // Fallback: construir URLs manualmente
            $this->base_url = $this->construct_base_url();
            $this->plugin_url = dirname($this->base_url) . '/';
        }
        
        // Limpiar rutas (asegurar trailing slash)
        $this->base_path = rtrim($this->base_path, '/') . '/';
        $this->base_url = rtrim($this->base_url, '/') . '/';
        $this->plugin_path = rtrim($this->plugin_path, '/') . '/';
        $this->plugin_url = rtrim($this->plugin_url, '/') . '/';
    }
    
    /**
     * Construye la URL base cuando no hay funciones WordPress disponibles
     */
    private function construct_base_url() {
        $scheme = $this->get_scheme();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Detectar path relativo desde DOCUMENT_ROOT
        $doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($doc_root && strpos($this->base_path, $doc_root) === 0) {
            $relative_path = substr($this->base_path, strlen($doc_root));
            return $scheme . '://' . $host . $relative_path;
        }
        
        // Fallback: intentar detectar desde REQUEST_URI
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if ($request_uri) {
            $current_dir = dirname($request_uri);
            return $scheme . '://' . $host . $current_dir . '/';
        }
        
        // Último fallback
        return $scheme . '://' . $host . '/wp-content/plugins/' . basename($this->plugin_path) . '/dev-tools/';
    }
    
    /**
     * Detecta el esquema (http/https)
     */
    private function get_scheme() {
        // HTTPS detection
        if (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        ) {
            return 'https';
        }
        
        return 'http';
    }
    
    /**
     * Obtiene la ruta absoluta base de dev-tools
     */
    public function get_base_path() {
        return $this->base_path;
    }
    
    /**
     * Obtiene la URL base de dev-tools
     */
    public function get_base_url() {
        return $this->base_url;
    }
    
    /**
     * Obtiene la ruta absoluta del plugin host
     */
    public function get_plugin_path() {
        return $this->plugin_path;
    }
    
    /**
     * Obtiene la URL del plugin host
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }
    
    /**
     * Obtiene ruta a subdirectorio específico de dev-tools
     */
    public function get_path($subdir = '') {
        $path = $this->base_path;
        if ($subdir) {
            $path .= ltrim($subdir, '/') . '/';
        }
        return rtrim($path, '/') . '/';
    }
    
    /**
     * Obtiene URL a subdirectorio específico de dev-tools
     */
    public function get_url($subdir = '') {
        $url = $this->base_url;
        if ($subdir) {
            $url .= ltrim($subdir, '/') . '/';
        }
        return rtrim($url, '/') . '/';
    }
    
    /**
     * Información de debugging del sistema de rutas
     */
    public function get_debug_info() {
        return [
            'base_path' => $this->base_path,
            'base_url' => $this->base_url,
            'plugin_path' => $this->plugin_path,
            'plugin_url' => $this->plugin_url,
            'wordpress_available' => function_exists('plugin_dir_url'),
            'server_info' => [
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
                'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A'
            ]
        ];
    }
}

// Inicialización automática de constantes globales
$dev_paths = DevToolsPaths::getInstance();

// Constantes globales de dev-tools (agnósticas)
if (!defined('DEV_TOOLS_BASE_PATH')) {
    define('DEV_TOOLS_BASE_PATH', $dev_paths->get_base_path());
}

if (!defined('DEV_TOOLS_BASE_URL')) {
    define('DEV_TOOLS_BASE_URL', $dev_paths->get_base_url());
}

if (!defined('DEV_TOOLS_PLUGIN_PATH')) {
    define('DEV_TOOLS_PLUGIN_PATH', $dev_paths->get_plugin_path());
}

if (!defined('DEV_TOOLS_PLUGIN_URL')) {
    define('DEV_TOOLS_PLUGIN_URL', $dev_paths->get_plugin_url());
}


/**
 * Función helper para obtener rutas personalizadas
 * 
 * @param string $subdir Subdirectorio relativo a dev-tools
 * @param bool $is_url Si true, retorna URL; si false, retorna PATH
 * @return string Ruta completa
 */
function dev_tools_path($subdir = '', $is_url = false) {
    $paths = DevToolsPaths::getInstance();
    return $is_url ? $paths->get_url($subdir) : $paths->get_path($subdir);
}

/**
 * Función helper específica para URLs
 */
function dev_tools_url($subdir = '') {
    return dev_tools_path($subdir, true);
}

// Log de inicialización si está en modo debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('🔧 Dev-Tools Paths initialized - Base: ' . DEV_TOOLS_BASE_PATH);
}
