<?php
/**
 * SiteUrlDetection Module - Dev-Tools Arquitectura 3.0
 * 
 * Módulo agnóstico para detectar el dominio actual del sitio WordPress
 * Funciona tanto dentro como fuera del entorno WordPress
 * Maneja Router Mode de Local by WP Engine: Site Domains vs localhost
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */


class SiteUrlDetectionModule {
    
    private $detected_url = null;
    private $environment_info = [];
    private $debug_mode = false;
    private $wp_available = false;
    
    public function __construct($debug = false) {
        $this->debug_mode = $debug;
        $this->wp_available = function_exists('get_site_url');
        $this->detect_site_url();
    }
    
    /**
     * Detecta el URL del sitio usando múltiples métodos
     */
    private function detect_site_url() {
        // Inicializar información básica primero
        $is_local_wp = $this->is_local_wp_engine();
        
        $this->environment_info = [
            'wp_available' => $this->wp_available,
            'is_local_wp' => $is_local_wp,
            'router_mode' => $this->detect_router_mode($is_local_wp),
            'detection_method' => null,
            'server_info' => $this->get_server_info()
        ];
        
        // Método 1: WordPress function (si está disponible)
        if ($this->wp_available && function_exists('get_site_url')) {
            $this->detected_url = get_site_url();
            $this->environment_info['detection_method'] = 'wordpress_function';
        }
        // Método 2: wp-config.php parsing
        elseif ($wp_config_url = $this->get_url_from_wp_config()) {
            $this->detected_url = $wp_config_url;
            $this->environment_info['detection_method'] = 'wp_config_parsing';
        }
        // Método 3: Local by WP Engine detection
        elseif ($local_url = $this->detect_local_wp_url()) {
            $this->detected_url = $local_url;
            $this->environment_info['detection_method'] = 'local_wp_detection';
        }
        // Método 4: Server variables fallback
        elseif ($server_url = $this->get_url_from_server()) {
            $this->detected_url = $server_url;
            $this->environment_info['detection_method'] = 'server_variables';
        }
        
        if ($this->debug_mode) {
            error_log('🔧 DevTools SiteUrlDetection - URL detected: ' . $this->detected_url);
            error_log('🔧 DevTools SiteUrlDetection - Environment: ' . json_encode($this->environment_info));
        }
    }
    
    /**
     * Detecta si estamos en Local by WP Engine
     */
    private function is_local_wp_engine() {
        $indicators = [
            // Path típico de Local by WP Engine
            strpos(__FILE__, '/Local Sites/') !== false,
            // Variables de entorno específicas
            isset($_SERVER['LOCAL_WP']) || isset($_ENV['LOCAL_WP']),
            // Verificar si existe el directorio característico
            is_dir('/Users/' . get_current_user() . '/Library/Application Support/Local'),
            // Verificar hostname local
            in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
            strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false ||
            preg_match('/localhost:\d+/', $_SERVER['HTTP_HOST'] ?? '')
        ];
        
        return count(array_filter($indicators)) > 0;
    }
    
    /**
     * Detecta el Router Mode de Local by WP Engine
     */
    private function detect_router_mode($is_local_wp = null) {
        if ($is_local_wp === null) {
            $is_local_wp = $this->is_local_wp_engine();
        }
        
        if (!$is_local_wp) {
            return 'not_local';
        }
        
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // localhost Router Mode: localhost:port
        if (preg_match('/localhost:\d+/', $host)) {
            return 'localhost_mode';
        }
        
        // Site Domains Router Mode: sitename.local
        if (strpos($host, '.local') !== false) {
            return 'site_domains_mode';
        }
        
        return 'unknown';
    }
    
    /**
     * Detecta URL específico de Local by WP Engine
     */
    private function detect_local_wp_url() {
        if (!$this->is_local_wp_engine()) {
            return null;
        }
        
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = $this->get_scheme();
        
        // Router Mode: localhost con puerto
        if (preg_match('/localhost:(\d+)/', $host, $matches)) {
            return $scheme . '://' . $host;
        }
        
        // Router Mode: site domains (.local)
        if (strpos($host, '.local') !== false) {
            return $scheme . '://' . $host;
        }
        
        // Fallback: intentar detectar desde configuración Local
        return $this->detect_from_local_config();
    }
    
    /**
     * Intenta detectar URL desde configuración de Local by WP Engine
     */
    private function detect_from_local_config() {
        // Intentar encontrar archivos de configuración de Local
        $possible_configs = [
            // Configuración específica del sitio
            dirname(__FILE__, 6) . '/conf/nginx/site.conf',
            dirname(__FILE__, 6) . '/conf/nginx/nginx.conf',
            // Configuración global de Local
            '/Users/' . get_current_user() . '/Library/Application Support/Local/sites.json'
        ];
        
        foreach ($possible_configs as $config_file) {
            if (file_exists($config_file)) {
                $url = $this->parse_local_config($config_file);
                if ($url) {
                    return $url;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Parsea archivos de configuración de Local
     */
    private function parse_local_config($config_file) {
        $content = file_get_contents($config_file);
        
        // Buscar server_name en nginx.conf
        if (preg_match('/server_name\s+([^;]+);/', $content, $matches)) {
            $server_name = trim($matches[1]);
            $scheme = $this->get_scheme();
            return $scheme . '://' . $server_name;
        }
        
        // Buscar configuración JSON (sites.json)
        if (strpos($config_file, '.json') !== false) {
            $json = json_decode($content, true);
            if ($json && isset($json['sites'])) {
                // Buscar sitio actual basado en path
                $current_path = dirname(__FILE__, 6);
                foreach ($json['sites'] as $site) {
                    if (isset($site['path']) && strpos($current_path, $site['path']) !== false) {
                        return $site['url'] ?? null;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Obtiene URL desde wp-config.php
     */
    private function get_url_from_wp_config() {
        $wp_config_path = $this->find_wp_config();
        if (!$wp_config_path) {
            return null;
        }
        
        $wp_config = file_get_contents($wp_config_path);
        
        // Buscar WP_HOME o WP_SITEURL
        if (preg_match("/define\s*\(\s*['\"]WP_HOME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $wp_config, $matches)) {
            return $matches[1];
        }
        
        if (preg_match("/define\s*\(\s*['\"]WP_SITEURL['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $wp_config, $matches)) {
            return $matches[1];
        }
        
        // Si no hay constantes definidas, construir URL
        $scheme = $this->get_scheme();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $scheme . '://' . $host;
    }
    
    /**
     * Busca wp-config.php en ubicaciones típicas
     */
    private function find_wp_config() {
        $possible_paths = [
            dirname(__FILE__, 6) . '/wp-config.php',
            dirname(__FILE__, 7) . '/wp-config.php',
            ABSPATH . 'wp-config.php',
            dirname(ABSPATH) . '/wp-config.php'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Obtiene URL desde variables del servidor
     */
    private function get_url_from_server() {
        $scheme = $this->get_scheme();
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        return $scheme . '://' . $host;
    }
    
    /**
     * Determina el esquema (http/https)
     */
    private function get_scheme() {
        // Verificar si es Local by WP Engine en modo localhost
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/localhost:\d+/', $host)) {
            return 'http';
        }
        
        // Verificar HTTPS
        if (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            return 'https';
        }
        
        return 'http';
    }
    
    /**
     * Obtiene información del servidor
     */
    private function get_server_info() {
        return [
            'http_host' => $_SERVER['HTTP_HOST'] ?? null,
            'server_name' => $_SERVER['SERVER_NAME'] ?? null,
            'server_port' => $_SERVER['SERVER_PORT'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? null
        ];
    }
    
    /**
     * Obtiene el URL detectado
     */
    public function get_site_url() {
        return $this->detected_url;
    }
    
    /**
     * Obtiene información del entorno
     */
    public function get_environment_info() {
        return $this->environment_info;
    }
    
    /**
     * Obtiene el URL del admin
     */
    public function get_admin_url() {
        if ($this->wp_available && function_exists('admin_url')) {
            return admin_url();
        }
        
        // Fallback: construir admin URL basado en site URL
        if ($this->detected_url) {
            return rtrim($this->detected_url, '/') . '/wp-admin/';
        }
        
        return null;
    }
    
    /**
     * Test completo de detección
     */
    public function test_detection() {
        return [
            'detected_url' => $this->detected_url,
            'environment' => $this->environment_info,
            'wp_available' => $this->wp_available,
            'all_methods' => [
                'wordpress_function' => $this->wp_available ? get_site_url() : null,
                'wp_config_parsing' => $this->get_url_from_wp_config(),
                'local_wp_detection' => $this->detect_local_wp_url(),
                'server_variables' => $this->get_url_from_server()
            ]
        ];
    }
    
    /**
     * Función estática para uso rápido
     */
    public static function get_current_site_url($debug = false) {
        $detector = new self($debug);
        return $detector->get_site_url();
    }
}
