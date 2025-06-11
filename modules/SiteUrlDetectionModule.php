<?php
/**
 * SiteUrlDetection Module - Dev-Tools Arquitectura 3.0
 * 
 * Módulo para detectar la URL del sitio WordPress en Local by WP Engine
 * sin depender del entorno de WordPress cargado
 * 
 * @package DevTools
 * @version 3.0
 * @author Tarokina Pro Plugin
 */

if (!defined('ABSPATH')) {
    // Si no está en contexto WordPress, permitir ejecución standalone
}

class SiteUrlDetectionModule {
    
    private $site_url = null;
    private $detection_methods = [];
    private $debug_mode = false;
    private $wp_config_path = null;
    
    public function __construct($debug = false) {
        $this->debug_mode = $debug;
        $this->detect_site_url();
    }
    
    /**
     * Detecta la URL del sitio usando múltiples métodos
     */
    private function detect_site_url() {
        $this->detection_methods = [
            'wordpress_function' => $this->try_wordpress_function(),
            'wp_config_analysis' => $this->try_wp_config_analysis(),
            'local_wp_detection' => $this->try_local_wp_detection(),
            'server_analysis' => $this->try_server_analysis(),
            'path_analysis' => $this->try_path_analysis()
        ];
        
        // Usar el primer método exitoso
        foreach ($this->detection_methods as $method => $result) {
            if ($result['success'] && $result['url']) {
                $this->site_url = $result['url'];
                if ($this->debug_mode) {
                    error_log("🌐 SiteUrlDetection - URL detected via {$method}: {$this->site_url}");
                }
                break;
            }
        }
        
        if (!$this->site_url && $this->debug_mode) {
            error_log("⚠️ SiteUrlDetection - No URL could be detected");
        }
    }
    
    /**
     * Método 1: Usar función WordPress si está disponible
     */
    private function try_wordpress_function() {
        if (function_exists('get_site_url')) {
            return [
                'success' => true,
                'url' => get_site_url(),
                'method' => 'WordPress get_site_url()'
            ];
        }
        
        if (function_exists('home_url')) {
            return [
                'success' => true,
                'url' => home_url(),
                'method' => 'WordPress home_url()'
            ];
        }
        
        return ['success' => false, 'url' => null, 'method' => 'WordPress functions not available'];
    }
    
    /**
     * Método 2: Analizar wp-config.php
     */
    private function try_wp_config_analysis() {
        $wp_config_path = $this->find_wp_config();
        if (!$wp_config_path) {
            return ['success' => false, 'url' => null, 'method' => 'wp-config.php not found'];
        }
        
        $this->wp_config_path = $wp_config_path;
        $config_content = file_get_contents($wp_config_path);
        
        // Buscar WP_HOME o WP_SITEURL
        if (preg_match("/define\s*\(\s*['\"]WP_HOME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $config_content, $matches)) {
            return [
                'success' => true,
                'url' => rtrim($matches[1], '/'),
                'method' => 'wp-config.php WP_HOME'
            ];
        }
        
        if (preg_match("/define\s*\(\s*['\"]WP_SITEURL['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $config_content, $matches)) {
            return [
                'success' => true,
                'url' => rtrim($matches[1], '/'),
                'method' => 'wp-config.php WP_SITEURL'
            ];
        }
        
        return ['success' => false, 'url' => null, 'method' => 'No URL constants in wp-config.php'];
    }
    
    /**
     * Método 3: Detección específica de Local by WP Engine
     */
    private function try_local_wp_detection() {
        // Buscar archivo de configuración de Local by WP Engine
        $current_path = __FILE__;
        $path_parts = explode('/', $current_path);
        
        // Buscar "Local Sites" en el path
        $local_sites_index = array_search('Local Sites', $path_parts);
        if ($local_sites_index !== false && isset($path_parts[$local_sites_index + 1])) {
            $site_name = $path_parts[$local_sites_index + 1];
            
            // Patrones típicos de Local by WP Engine
            $possible_urls = [
                "http://{$site_name}.local",
                "https://{$site_name}.local",
                "http://{$site_name}.localhost",
                "https://{$site_name}.localhost"
            ];
            
            // Verificar cuál responde
            foreach ($possible_urls as $url) {
                if ($this->test_url_reachable($url)) {
                    return [
                        'success' => true,
                        'url' => $url,
                        'method' => 'Local by WP Engine pattern detection'
                    ];
                }
            }
            
            // Si no responde, usar el patrón más común
            return [
                'success' => true,
                'url' => "http://{$site_name}.local",
                'method' => 'Local by WP Engine pattern (assumed)'
            ];
        }
        
        return ['success' => false, 'url' => null, 'method' => 'Not in Local Sites path'];
    }
    
    /**
     * Método 4: Análisis de servidor web
     */
    private function try_server_analysis() {
        // Si estamos en contexto web, usar headers HTTP
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $url = "{$protocol}://{$host}";
            
            return [
                'success' => true,
                'url' => $url,
                'method' => 'HTTP server headers'
            ];
        }
        
        return ['success' => false, 'url' => null, 'method' => 'No HTTP context'];
    }
    
    /**
     * Método 5: Análisis de estructura de paths
     */
    private function try_path_analysis() {
        $current_path = __FILE__;
        
        // Buscar patrones en el path que indiquen el nombre del sitio
        if (preg_match('/\/([^\/]+)\/app\/public\/wp-content/', $current_path, $matches)) {
            $site_name = $matches[1];
            return [
                'success' => true,
                'url' => "http://{$site_name}.local",
                'method' => 'Path structure analysis'
            ];
        }
        
        return ['success' => false, 'url' => null, 'method' => 'No recognizable path pattern'];
    }
    
    /**
     * Busca wp-config.php en ubicaciones típicas
     */
    private function find_wp_config() {
        $possible_paths = [
            // Desde la ubicación actual del módulo
            __DIR__ . '/../../../../../wp-config.php',
            __DIR__ . '/../../../../wp-config.php',
            __DIR__ . '/../../../wp-config.php',
            
            // Patrones de Local by WP Engine
            dirname(__DIR__, 6) . '/wp-config.php',
            dirname(__DIR__, 5) . '/wp-config.php',
            dirname(__DIR__, 4) . '/wp-config.php',
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Prueba si una URL es accesible
     */
    private function test_url_reachable($url) {
        // Test básico usando cURL si está disponible
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $http_code >= 200 && $http_code < 400;
        }
        
        // Fallback: asumir que .local está disponible
        return strpos($url, '.local') !== false;
    }
    
    /**
     * Obtiene la URL del sitio detectada
     */
    public function get_site_url() {
        return $this->site_url;
    }
    
    /**
     * Obtiene información detallada de la detección
     */
    public function get_detection_info() {
        return [
            'detected_url' => $this->site_url,
            'wp_config_path' => $this->wp_config_path,
            'methods_tried' => $this->detection_methods,
            'current_file_path' => __FILE__,
            'server_info' => [
                'http_host' => $_SERVER['HTTP_HOST'] ?? 'Not available',
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'Not available',
                'https' => $_SERVER['HTTPS'] ?? 'Not set'
            ]
        ];
    }
    
    /**
     * Construye URL completa agregando path
     */
    public function build_url($path = '') {
        if (!$this->site_url) {
            return null;
        }
        
        $base_url = rtrim($this->site_url, '/');
        $path = ltrim($path, '/');
        
        return $path ? "{$base_url}/{$path}" : $base_url;
    }
    
    /**
     * Obtiene la URL de un archivo del plugin
     */
    public function get_plugin_url($file_path = '') {
        $plugin_path = 'wp-content/plugins/tarokina-2025';
        return $this->build_url($plugin_path . ($file_path ? '/' . ltrim($file_path, '/') : ''));
    }
    
    /**
     * Test completo del módulo
     */
    public function test_detection() {
        return [
            'success' => !empty($this->site_url),
            'detected_url' => $this->site_url,
            'detection_methods' => $this->detection_methods,
            'plugin_url_example' => $this->get_plugin_url('dev-tools/modules/test.php'),
            'build_url_example' => $this->build_url('wp-admin'),
            'wp_config_found' => !empty($this->wp_config_path),
            'wp_config_path' => $this->wp_config_path
        ];
    }
}
