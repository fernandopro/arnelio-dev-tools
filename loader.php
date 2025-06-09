<?php
/**
 * Dev Tools Loader - Arquitectura 3.0
 * Sistema plugin-agnóstico con arquitectura modular
 * 
 * @package DevTools
 * @version 3.0.0
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// CARGA DE COMPONENTES CORE
// ========================================

// 1. Configuración global (base del sistema)
require_once __DIR__ . '/config.php';

// 2. Sistema de logging y debug
require_once __DIR__ . '/debug-ajax.php';

// 3. Interfaces y clases base
require_once __DIR__ . '/core/interfaces/DevToolsModuleInterface.php';
require_once __DIR__ . '/core/DevToolsModuleBase.php';

// 4. Manejador AJAX centralizado
require_once __DIR__ . '/ajax-handler.php';

// 5. Gestor de módulos
require_once __DIR__ . '/core/DevToolsModuleManager.php';

// Obtener configuración dinámica
$config = dev_tools_config();

// Habilitar modo debug si está en desarrollo
if (defined('WP_DEBUG') && WP_DEBUG) {
    define('DEV_TOOLS_DEBUG', true);
}

// ========================================
// FUNCIONES CORE DEL SISTEMA
// ========================================

/**
 * Obtiene la instancia del Module Manager (Arquitectura 3.0)
 * 
 * @return DevToolsModuleManager|null
 */
function dev_tools_get_module_manager() {
    if (class_exists('DevToolsModuleManager')) {
        return DevToolsModuleManager::getInstance();
    }
    return null;
}

/**
 * Encola los estilos y scripts para dev-tools (dinámico)
 * Sigue las mejores prácticas de WordPress con verificación de archivos
 */
function dev_tools_enqueue_assets($hook) {
    $config = dev_tools_config();
    $menu_slug = $config->get('dev_tools.menu_slug');
    
    // Solo cargar en la página de dev-tools (slug dinámico)
    if ($hook !== 'tools_page_' . $menu_slug) {
        return;
    }

    // CORRECCIÓN: Generar URL base correcta del directorio dev-tools
    // Usar la ruta base del host plugin + dev-tools
    $host_plugin_url = plugin_dir_url($config->get('host.file'));
    $plugin_url = $host_plugin_url . 'dev-tools/';
    $plugin_version = $config->get('host.version');
    
    // Debug: Log de URLs para verificación
    if ($config->is_debug_mode()) {
        error_log('[DEV-TOOLS] Plugin URL generada: ' . $plugin_url);
    }
    
    // Función helper para verificar y encolar assets de forma segura
    $enqueue_asset = function($type, $handle, $file_path, $deps = array()) use ($plugin_url, $plugin_version, $config) {
        $file_system_path = __DIR__ . '/' . $file_path;
        $url = $plugin_url . $file_path;
        
        // Verificar que el archivo existe antes de encolarlo
        if (!file_exists($file_system_path)) {
            if ($config->is_debug_mode()) {
                error_log('[DEV-TOOLS] Asset no encontrado: ' . $file_system_path);
            }
            return false;
        }
        
        if ($type === 'style') {
            wp_enqueue_style($handle, $url, $deps, $plugin_version);
        } else {
            wp_enqueue_script($handle, $url, $deps, $plugin_version, true);
        }
        
        if ($config->is_debug_mode()) {
            error_log('[DEV-TOOLS] Asset encolado: ' . $url);
        }
        
        return true;
    };
    
    // Dev Tools CSS compilado (incluye Bootstrap y estilos personalizados)
    $enqueue_asset('style', 
        $config->get('assets.css_handle'),
        'dist/css/dev-tools-styles.min.css'
    );

    // Dev Tools JavaScript principal compilado (incluye Bootstrap JS compilado)
    $main_js_enqueued = $enqueue_asset('script',
        $config->get('assets.js_handle'),
        'dist/js/dev-tools.min.js'
    );
    
    // Solo continuar si el JS principal se encoló correctamente
    if (!$main_js_enqueued) {
        if ($config->is_debug_mode()) {
            error_log('[DEV-TOOLS] Error: No se pudo cargar el JS principal, cancelando carga de módulos');
        }
        return;
    }
    
    // JavaScript de utilidades del sistema
    $enqueue_asset('script',
        $config->get('assets.js_handle') . '-utils',
        'dist/js/dev-utils.min.js',
        array($config->get('assets.js_handle'))
    );

    // Módulos JavaScript de Arquitectura 3.0 (carga optimizada con verificación)
    $modules = [
        'dashboard' => 'dashboard.min.js',
        'system-info' => 'system-info.min.js',
        'cache' => 'cache.min.js',
        'ajax-tester' => 'ajax-tester.min.js',
        'logs' => 'logs.min.js',
        'performance' => 'performance.min.js'
    ];
    
    $modules_loaded = 0;
    foreach ($modules as $module => $file) {
        if ($enqueue_asset('script',
            $config->get('assets.js_handle') . '-' . $module,
            'dist/js/' . $file,
            array($config->get('assets.js_handle'))
        )) {
            $modules_loaded++;
        }
    }
    
    // Log resumen de módulos cargados en modo debug
    if ($config->is_debug_mode()) {
        error_log('[DEV-TOOLS] Módulos cargados: ' . $modules_loaded . '/' . count($modules));
    }

    // JavaScript de utilidades solo en desarrollo (evitar duplicación)
    // Ya se carga en la sección principal de módulos

    // Localizar script con configuración y traducciones (dinámico)
    wp_localize_script(
        $config->get('assets.js_handle'),
        $config->get('dev_tools.js_config_var'),
        $config->get_js_config()
    );
}
add_action('admin_enqueue_scripts', 'dev_tools_enqueue_assets');

/**
 * Configurar traducciones para JavaScript (dinámico)
 */
function dev_tools_setup_translations() {
    $config = dev_tools_config();
    
    if (function_exists('wp_set_script_translations')) {
        $text_domain = $config->get('host.text_domain');
        $languages_path = $config->get('host.dir_path') . '/languages';
        
        // Traducciones para los archivos JavaScript principales
        $js_handles = [
            $config->get('assets.js_handle'),
            $config->get('assets.js_handle') . '-utils'
        ];
        
        // Añadir handles de módulos existentes
        $modules = ['dashboard', 'system-info', 'cache', 'ajax-tester', 'logs', 'performance'];
        foreach ($modules as $module) {
            $js_handles[] = $config->get('assets.js_handle') . '-' . $module;
        }
        
        foreach ($js_handles as $handle) {
            wp_set_script_translations(
                $handle,
                $text_domain,
                $languages_path
            );
        }
    }
}
add_action('admin_enqueue_scripts', 'dev_tools_setup_translations');

/**
 * Añade enlaces de acceso rápido en la página de plugins (dinámico)
 */
function dev_tools_plugin_action_links($links) {
    if (is_admin()) {
        $config = dev_tools_config();
        $dev_tools_link = $config->get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug'));
        $color = $config->is_debug_mode() ? '#0073aa' : '#d63638'; // Azul en desarrollo, rojo en producción
        $text = $config->is_debug_mode() ? '🔧 Dev Tools' : '⚠️ Dev Tools (PROD)';
        $links[] = '<a href="' . $dev_tools_link . '" style="color: ' . $color . '; font-weight: bold;">' . $text . '</a>';
    }
    return $links;
}

// Hook dinámico basado en el plugin host detectado
$config = dev_tools_config();
add_filter('plugin_action_links_' . $config->get('host.basename'), 'dev_tools_plugin_action_links');

/**
 * Genera URLs del admin de WordPress usando get_site_url() nativo
 * Optimizado para entornos locales como http://localhost:10019/
 * Compatible con el sistema de configuración dinámico
 */
function dev_tools_get_admin_url($page = '') {
    // Siempre usar get_site_url() para mayor consistencia
    if (function_exists('get_site_url')) {
        $site_url = get_site_url();
        $admin_path = '/wp-admin/';
        
        // Limpiar la página de parámetros innecesarios
        $page = ltrim($page, '/');
        
        // Construir URL completa
        $full_url = rtrim($site_url, '/') . $admin_path . $page;
        
        return $full_url;
    }
    
    // Fallback a admin_url() si get_site_url() no está disponible
    if (function_exists('admin_url')) {
        return admin_url($page);
    }
    
    // Último recurso: construcción manual para localhost
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $fallback_url = $protocol . $host . '/wp-admin/' . ltrim($page, '/');
    
    return $fallback_url;
}

/**
 * Añade notificación en el dashboard cuando las dev-tools están activas (dinámico)
 */
function dev_tools_admin_notice() {
    if (is_admin()) {
        $config = dev_tools_config();
        $current_screen = get_current_screen();
        $menu_slug = $config->get('dev_tools.menu_slug');
        
        // Solo mostrar en páginas principales del admin
        if (in_array($current_screen->id, ['dashboard', 'plugins', 'tools_page_' . $menu_slug])) {
            $notice_class = $config->is_debug_mode() ? 'notice-info' : 'notice-warning';
            $mode_text = $config->is_debug_mode() ? 'desarrollo' : 'PRODUCCIÓN';
            $warning = $config->is_debug_mode() ? '' : '⚠️ <strong>ATENCIÓN:</strong> Usando herramientas de desarrollo en ';
            
            echo '<div class="notice ' . $notice_class . ' is-dismissible">';
            echo '<p>' . $warning . '<strong>' . $config->get('host.name') . ':</strong> Modo ' . $mode_text . ' activo. ';
            echo '<a href="' . $config->get_admin_url('tools.php?page=' . $menu_slug) . '">Acceder a Dev Tools</a>';
            echo '</p></div>';
        }
    }
}
add_action('admin_notices', 'dev_tools_admin_notice');

