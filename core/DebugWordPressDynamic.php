<?php
/**
 * Debug WordPress Dynamic Configuration System
 * Parte del núcleo de Dev-Tools Arquitectura 3.0
 * 
 * PROPÓSITO: Herramienta de debug para verificar URLs dinámicas y configuración
 * dentro del contexto de WordPress, disponible para todos los plugins que usen Dev-Tools.
 * 
 * INSTRUCCIONES DE USO:
 * 1. Agregar ?debug_config=1 a cualquier página del admin de WordPress
 * 2. Agregar ?debug_urls=1 para debug específico de generación de URLs
 * 3. Este script se ejecutará automáticamente y mostrará la configuración
 * 
 * FUNCIONES PRINCIPALES:
 * - get_debug_url_data() - Obtener datos para validación programática
 * - validate_url_consistency() - Validar consistencia de URLs
 * - log_url_issues() - Registrar problemas en error.log
 * - render_debug_output() - Mostrar debug visual en admin
 * - Endpoints AJAX para validación programática
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal para debug de configuración dinámica de WordPress
 * Integrada en el núcleo de Dev-Tools Arquitectura 3.0
 */
class DevToolsDebugWordPressDynamic {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        $this->init_hooks();
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
     * Inicializar hooks de WordPress
     */
    private function init_hooks() {
        // Hook para mostrar debug visual
        add_action('admin_init', [$this, 'handle_debug_requests']);
        
        // Hooks para validación automática en modo debug
        add_action('admin_init', [$this, 'auto_validate_urls']);
        
        // Endpoints AJAX para validación programática
        add_action('wp_ajax_debug_validate_urls', [$this, 'ajax_validate_urls']);
        add_action('wp_ajax_debug_url_generation', [$this, 'ajax_debug_url_generation']);
    }
    
    /**
     * Manejar peticiones de debug visual
     */
    public function handle_debug_requests() {
        // Debug general de configuración
        if (isset($_GET['debug_config']) && $_GET['debug_config'] === '1' && current_user_can('manage_options')) {
            $this->render_config_debug();
        }
        
        // Debug específico de generación de URLs
        if (isset($_GET['debug_urls']) && $_GET['debug_urls'] === '1' && current_user_can('manage_options')) {
            $this->render_url_generation_debug();
        }
    }
    
    /**
     * Función para obtener datos de debug sin HTML (uso programático)
     * REFACTORIZADO: Ahora usa DevToolsConfig consolidado
     * 
     * @return array Datos de configuración y URLs para validación
     */
    public function get_debug_url_data() {
        $data = [
            'success' => false,
            'dev_tools_loaded' => function_exists('dev_tools_config'),
            'urls' => [],
            'config' => [],
            'issues' => []
        ];
        
        if (function_exists('dev_tools_config')) {
            $config = dev_tools_config();
            
            // Usar método consolidado de la clase config
            $data = $config->get_debug_data();
        }
        
        return $data;
    }
    
    /**
     * Validar consistencia de URLs y configuración
     * REFACTORIZADO: Ahora usa DevToolsConfig consolidado
     * 
     * @param array $urls URLs detectadas
     * @param array $config Configuración detectada
     * @return array Lista de issues encontrados
     */
    public function validate_url_consistency($urls, $config) {
        if (function_exists('dev_tools_config')) {
            $dev_config = dev_tools_config();
            return $dev_config->validate_configuration_consistency($urls, $config);
        }
        
        // Fallback legacy si config no está disponible
        return [];
    }
    
    /**
     * Registrar issues en el error log de WordPress
     * REFACTORIZADO: Ahora usa DevToolsConfig consolidado
     * 
     * @param array $issues Lista de problemas encontrados
     * @param string $context Contexto del debug (opcional)
     */
    public function log_url_issues($issues, $context = 'MANUAL_DEBUG') {
        if (function_exists('dev_tools_config')) {
            $config = dev_tools_config();
            $config->log_configuration_issues($issues, $context);
        } else {
            // Fallback legacy si config no está disponible
            if (!empty($issues)) {
                error_log("🔧 DEV-TOOLS {$context} ISSUES:");
                foreach ($issues as $issue) {
                    error_log("   - {$issue}");
                }
            }
        }
    }
    
    /**
     * Renderizar debug de configuración general
     */
    private function render_config_debug() {
        // Cargar configuración de DevTools si está disponible
        if (function_exists('dev_tools_config')) {
            $config = dev_tools_config();
            
            // Usar método consolidado para generar output de debug
            $config->render_debug_output();
            
            // Script JavaScript para copiar a consola
            $this->render_console_debug_script($config);
            
        } else {
            $this->render_fallback_debug();
        }
    }
    
    /**
     * Renderizar script de debug para consola
     */
    private function render_console_debug_script($config) {
        echo '<script>
        console.log("🔧 === CONFIGURACIÓN DETECTADA DESDE PHP ===");
        ';
        
        $js_config = $config->get_js_config();
        $js_var = $config->get('dev_tools.js_config_var');
        
        echo 'console.log("Variable esperada: window.' . $js_var . '");';
        echo 'console.log("Configuración PHP:", ' . json_encode($js_config) . ');';
        echo 'console.log("Verificando si existe en window...");';
        echo 'if (window["' . $js_var . '"]) {';
        echo '    console.log("✅ Variable encontrada:", window["' . $js_var . '"]);';
        echo '} else {';
        echo '    console.log("❌ Variable NO encontrada");';
        echo '    console.log("Variables disponibles:", Object.keys(window).filter(k => k.includes("config")));';
        echo '}';
        
        echo '
        </script>';
    }
    
    /**
     * Renderizar debug fallback cuando DevTools no está cargado
     */
    private function render_fallback_debug() {
        echo '<div id="wpcontent"><pre style="background:rgb(39, 39, 39); color:#fff; padding: 20px; font-family: monospace; border: 1px solid #ccc; margin: 20px;">';
        echo "❌ DevTools NO está cargado\n";
        echo "Intentando detectar problema...\n\n";
        
        // Verificar si el archivo loader existe
        $loader_path = dirname(__DIR__) . '/loader.php';
        echo "Loader Path: $loader_path\n";
        echo "Loader Exists: " . (file_exists($loader_path) ? 'SI' : 'NO') . "\n";
        
        // Mostrar URLs básicas de WordPress
        echo "\n📋 URLs BÁSICAS DE WORDPRESS:\n";
        echo "─────────────────────────────\n";
        echo "Site URL: " . get_site_url() . "\n";
        echo "Home URL: " . get_home_url() . "\n";
        echo "Admin URL: " . get_admin_url() . "\n";
        echo "Admin AJAX URL: " . admin_url('admin-ajax.php') . "\n";
        echo "Plugins URL: " . plugins_url() . "\n";
        echo '</pre></div>';
    }
    
    /**
     * Validar URLs en cada carga del admin (solo en modo debug)
     */
    public function auto_validate_urls() {
        // Solo ejecutar si está en modo debug y es petición del dev-tools
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['page']) && strpos($_GET['page'], 'dev_tools') !== false) {
            $debug_data = $this->get_debug_url_data();
            if (!empty($debug_data['issues'])) {
                $this->log_url_issues($debug_data['issues']);
            }
        }
    }
    
    /**
     * Endpoint AJAX para validación programática
     */
    public function ajax_validate_urls() {
        check_ajax_referer('debug_validate_urls_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        $debug_data = $this->get_debug_url_data();
        wp_send_json_success($debug_data);
    }
    
    /**
     * Endpoint AJAX para debug de generación de URLs
     */
    public function ajax_debug_url_generation() {
        check_ajax_referer('debug_validate_urls_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        $url_generation_data = $this->get_url_generation_debug();
        wp_send_json_success($url_generation_data);
    }
    
    /**
     * Función consolidada para debug de generación de URLs
     * 
     * @return array Datos de diferentes métodos de generación de URLs
     */
    public function get_url_generation_debug() {
        $data = [
            'success' => false,
            'dev_tools_loaded' => function_exists('dev_tools_config'),
            'file_paths' => [],
            'url_methods' => [],
            'recommended_method' => null
        ];
        
        if (function_exists('dev_tools_config')) {
            $config = dev_tools_config();
            $data['success'] = true;
            
            // Información de archivos y rutas
            $data['file_paths'] = [
                '__FILE__' => __FILE__,
                '__DIR__' => __DIR__,
                'dev_tools_dir' => dirname(__DIR__),
                'dev_tools_core_dir' => __DIR__
            ];
            
            // Método 1: plugin_dir_url() desde dev-tools
            $dev_tools_file = dirname(__DIR__) . '/config.php';
            $url_method1 = plugin_dir_url($dev_tools_file);
            
            // Método 2: Construcción manual desde plugin padre
            $plugin_dir = dirname(dirname(__DIR__));
            $url_method2 = plugins_url('', $plugin_dir . '/dummy.php') . '/dev-tools/';
            
            // Método 3: Configuración dinámica consolidada (RECOMENDADO)
            $url_method3 = $config->get('paths.dev_tools_url');
            
            $data['url_methods'] = [
                'method_1_plugin_dir_url' => [
                    'name' => 'plugin_dir_url()',
                    'base_url' => $url_method1,
                    'css_url' => $url_method1 . 'dist/css/dev-tools-styles.min.css',
                    'js_url' => $url_method1 . 'dist/js/dashboard.min.js',
                    'pros' => ['Simple', 'Directo'],
                    'cons' => ['Dependiente del archivo actual']
                ],
                'method_2_manual_construction' => [
                    'name' => 'Construcción manual',
                    'base_url' => $url_method2,
                    'css_url' => $url_method2 . 'dist/css/dev-tools-styles.min.css',
                    'js_url' => $url_method2 . 'dist/js/dashboard.min.js',
                    'pros' => ['Flexible'],
                    'cons' => ['Complejo', 'Propenso a errores']
                ],
                'method_3_consolidated_config' => [
                    'name' => 'Configuración consolidada (RECOMENDADO)',
                    'base_url' => $url_method3,
                    'css_url' => $url_method3 . 'dist/css/dev-tools-styles.min.css',
                    'js_url' => $url_method3 . 'dist/js/dashboard.min.js',
                    'pros' => ['Dinámico', 'Centralizado', 'Mantenible', 'Consolidado'],
                    'cons' => ['Ninguno']
                ]
            ];
            
            $data['recommended_method'] = 'method_3_consolidated_config';
            
            // Validar consistencia entre métodos
            $urls = [$url_method1, $url_method2, $url_method3];
            $unique_urls = array_unique($urls);
            $data['methods_consistent'] = count($unique_urls) === 1;
            $data['url_differences'] = count($unique_urls) > 1 ? array_values($unique_urls) : null;
        }
        
        return $data;
    }
    
    /**
     * Renderizar debug de generación de URLs en formato HTML
     * 
     * @param bool $return_html Si retornar HTML en lugar de imprimirlo
     * @return string|void HTML de debug o imprime directamente
     */
    public function render_url_generation_debug($return_html = false) {
        $url_data = $this->get_url_generation_debug();
        
        $output = '<div id="wpcontent"><pre style="background:rgb(39, 39, 39); color:#fff; padding: 20px; font-family: monospace; border: 1px solid #ccc; margin: 20px;">';
        $output .= "🔧 === DEBUG GENERACIÓN DE URLs (DEV-TOOLS CORE) ===\n\n";
        
        if ($url_data['success']) {
            $output .= "📁 INFORMACIÓN DE ARCHIVOS:\n";
            $output .= "──────────────────────────\n";
            foreach ($url_data['file_paths'] as $key => $path) {
                $output .= "{$key}: {$path}\n";
            }
            
            $output .= "\n🌐 MÉTODOS DE GENERACIÓN DE URLs:\n";
            $output .= "─────────────────────────────────\n";
            
            foreach ($url_data['url_methods'] as $method_key => $method) {
                $recommended = $method_key === $url_data['recommended_method'] ? ' ⭐ RECOMENDADO' : '';
                $output .= "\n{$method['name']}{$recommended}:\n";
                $output .= "  Base URL: {$method['base_url']}\n";
                $output .= "  CSS URL: {$method['css_url']}\n";
                $output .= "  JS URL: {$method['js_url']}\n";
                $output .= "  Pros: " . implode(', ', $method['pros']) . "\n";
                $output .= "  Cons: " . implode(', ', $method['cons']) . "\n";
            }
            
            $output .= "\n✅ ANÁLISIS DE CONSISTENCIA:\n";
            $output .= "──────────────────────────\n";
            if ($url_data['methods_consistent']) {
                $output .= "✅ Todos los métodos generan URLs consistentes\n";
            } else {
                $output .= "⚠️ Diferencias detectadas entre métodos:\n";
                foreach ($url_data['url_differences'] as $i => $url) {
                    $output .= "  Variante " . ($i + 1) . ": {$url}\n";
                }
            }
            
            $output .= "\n💡 RECOMENDACIÓN:\n";
            $output .= "───────────────\n";
            $recommended_method = $url_data['url_methods'][$url_data['recommended_method']];
            $output .= "Usar: {$recommended_method['name']}\n";
            $output .= "Razón: " . implode(', ', $recommended_method['pros']) . "\n";
            $output .= "URL Base: {$recommended_method['base_url']}\n";
            
        } else {
            $output .= "❌ DevTools no está cargado - No se puede realizar debug de URLs\n";
        }
        
        $output .= "\n🔧 === FIN DEBUG GENERACIÓN DE URLs ===\n";
        $output .= '</pre></div>';
        
        if ($return_html) {
            return $output;
        } else {
            echo $output;
        }
    }
    
    /**
     * Generar nonce para validación AJAX
     */
    public static function get_debug_validation_nonce() {
        return wp_create_nonce('debug_validate_urls_nonce');
    }
}

// Funciones helper para mantener compatibilidad con código existente
if (!function_exists('get_debug_url_data')) {
    function get_debug_url_data() {
        return DevToolsDebugWordPressDynamic::getInstance()->get_debug_url_data();
    }
}

if (!function_exists('validate_url_consistency')) {
    function validate_url_consistency($urls, $config) {
        return DevToolsDebugWordPressDynamic::getInstance()->validate_url_consistency($urls, $config);
    }
}

if (!function_exists('log_url_issues')) {
    function log_url_issues($issues, $context = 'MANUAL_DEBUG') {
        return DevToolsDebugWordPressDynamic::getInstance()->log_url_issues($issues, $context);
    }
}

if (!function_exists('get_debug_validation_nonce')) {
    function get_debug_validation_nonce() {
        return DevToolsDebugWordPressDynamic::get_debug_validation_nonce();
    }
}

// Inicializar la clase automáticamente cuando se carga el archivo
if (defined('ABSPATH')) {
    DevToolsDebugWordPressDynamic::getInstance();
}
