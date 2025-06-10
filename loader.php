<?php
/**
 * Dev Tools Loader - Arquitectura 3.0
 * Sistema plugin-agnóstico con arquitectura modular
 * RECONSTRUIDO para eliminar bucles infinitos y errores de timing
 * 
 * @package DevTools
 * @version 3.0.1
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// FUNCIONES DE VERIFICACIÓN SEGURAS
// ========================================

/**
 * Verificar si WordPress está completamente inicializado
 * Evita errores de timing al verificar funciones del core
 */
function dev_tools_is_wp_ready() {
    return function_exists('wp_get_current_user') && 
           function_exists('current_user_can') && 
           function_exists('is_admin');
}

/**
 * Determinar si Dev-Tools debe activar funcionalidades completas
 * VERSIÓN SEGURA: Solo verifica permisos cuando WordPress está listo
 */
function dev_tools_should_activate_full_features() {
    // Siempre activar durante peticiones AJAX para evitar errores 500
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return true;
    }
    
    // Debug especial: siempre activar si se solicita debug de configuración
    if (isset($_GET['debug_config']) && $_GET['debug_config'] === '1') {
        return true;
    }
    
    // Solo verificar permisos si WordPress está completamente listo
    if (dev_tools_is_wp_ready()) {
        if (current_user_can('manage_options')) {
            return true;
        }
    }
    
    return false;
}

/**
 * Determinar si debe cargar assets del admin (CSS/JS)
 * VERSIÓN SEGURA: Verificaciones de timing mejoradas
 */
function dev_tools_should_load_admin_assets() {
    // No cargar assets durante AJAX para optimización
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return false;
    }
    
    // Verificar que WordPress esté listo antes de usar is_admin()
    if (!dev_tools_is_wp_ready()) {
        return false;
    }
    
    // Solo cargar en admin
    if (!is_admin()) {
        return false;
    }
    
    // Solo para administradores
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    return true;
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

// 5. Endpoint generador de nonces (para debugging)
require_once __DIR__ . '/nonce-generator-endpoint.php';

// 6. Gestor de módulos
require_once __DIR__ . '/core/DevToolsModuleManager.php';

// 7. Sistema de debug de WordPress dinámico (parte del núcleo)
require_once __DIR__ . '/core/DebugWordPressDynamic.php';

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
 * VERSIÓN SEGURA: Con verificación de disponibilidad
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
 * VERSIÓN OPTIMIZADA: Verificaciones de seguridad mejoradas
 */
function dev_tools_enqueue_assets($hook) {
    // Verificar que WordPress esté listo
    if (!dev_tools_is_wp_ready()) {
        return;
    }
    
    $config = dev_tools_config();
    $menu_slug = $config->get('dev_tools.menu_slug');
    
    // Solo cargar en la página de dev-tools (slug dinámico)
    if ($hook !== 'tools_page_' . $menu_slug) {
        return;
    }

    // CORRECCIÓN: Generar URL base correcta del directorio dev-tools
    $host_plugin_url = plugin_dir_url($config->get('host.file'));
    $plugin_url = $host_plugin_url . 'dev-tools/';
    $plugin_version = $config->get('host.version');
    
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
        
        return true;
    };
    
    // Dev Tools CSS compilado
    $enqueue_asset('style', 
        $config->get('assets.css_handle'),
        'dist/css/dev-tools-styles.min.css'
    );

    // Dev Tools JavaScript principal
    $main_js_enqueued = $enqueue_asset('script',
        $config->get('assets.js_handle'),
        'dist/js/dev-tools.min.js'
    );
    
    // Solo continuar si el JS principal se encoló correctamente
    if (!$main_js_enqueued) {
        return;
    }
    
    // Detectar página actual de forma segura
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    $is_dev_tools_page = (strpos($current_page, 'dev-tools') !== false || $current_page === $menu_slug);
    
    // JavaScript de utilidades (solo en páginas dev-tools)
    if ($is_dev_tools_page) {
        $enqueue_asset('script',
            $config->get('assets.js_handle') . '-utils',
            'dist/js/dev-utils.min.js',
            array($config->get('assets.js_handle'))
        );
    }

    // Cargar módulos JavaScript según contexto
    $modules_to_load = ['dashboard']; // Dashboard siempre disponible
    
    // Detectar módulo específico basado en tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
    if ($current_tab !== 'dashboard' && in_array($current_tab, ['system-info', 'cache', 'ajax-tester', 'logs', 'performance'])) {
        $modules_to_load[] = $current_tab;
    }
    
    // Encolar módulos JavaScript necesarios
    foreach ($modules_to_load as $module) {
        $module_file = "dist/js/{$module}.min.js";
        $enqueue_asset('script',
            $config->get('assets.js_handle') . '-' . $module,
            $module_file,
            array($config->get('assets.js_handle'))
        );
    }
    
    // Configuración JavaScript para el frontend
    wp_localize_script($config->get('assets.js_handle'), 'devToolsConfig', $config->get_js_config());
}

/**
 * Añade enlaces de acceso rápido en la página de plugins
 * VERSIÓN SEGURA: Con verificación de estado de WordPress
 */
function dev_tools_plugin_action_links($links) {
    if (!dev_tools_is_wp_ready() || !is_admin()) {
        return $links;
    }
    
    $config = dev_tools_config();
    $dev_tools_link = dev_tools_get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug'));
    $color = $config->is_debug_mode() ? '#0073aa' : '#d63638';
    $text = $config->is_debug_mode() ? '🔧 Dev Tools' : '⚠️ Dev Tools (PROD)';
    $links[] = '<a href="' . esc_url($dev_tools_link) . '" style="color: ' . esc_attr($color) . '; font-weight: bold;">' . esc_html($text) . '</a>';
    
    return $links;
}

/**
 * Genera URLs del admin de WordPress de forma segura
 * VERSIÓN OPTIMIZADA: Múltiples fallbacks para mayor confiabilidad
 */
function dev_tools_get_admin_url($page = '') {
    // Método 1: get_site_url() + admin path
    if (function_exists('get_site_url')) {
        $site_url = get_site_url();
        if ($site_url) {
            return rtrim($site_url, '/') . '/wp-admin/' . ltrim($page, '/');
        }
    }
    
    // Método 2: admin_url() nativo
    if (function_exists('admin_url')) {
        return admin_url($page);
    }
    
    // Método 3: Construcción manual para casos de emergencia
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . $host . '/wp-admin/' . ltrim($page, '/');
}

/**
 * Notificación en el dashboard cuando dev-tools están activas
 * VERSIÓN SEGURA: Solo se ejecuta cuando WordPress está completamente listo
 */
function dev_tools_admin_notice() {
    if (!dev_tools_is_wp_ready() || !is_admin()) {
        return;
    }
    
    $config = dev_tools_config();
    $current_screen = get_current_screen();
    
    if (!$current_screen) {
        return;
    }
    
    $menu_slug = $config->get('dev_tools.menu_slug');
    
    // Solo mostrar en páginas principales del admin
    if (in_array($current_screen->id, ['dashboard', 'plugins', 'tools_page_' . $menu_slug])) {
        $notice_class = $config->is_debug_mode() ? 'notice-info' : 'notice-warning';
        $mode_text = $config->is_debug_mode() ? 'desarrollo' : 'PRODUCCIÓN';
        $warning = $config->is_debug_mode() ? '' : '⚠️ <strong>ATENCIÓN:</strong> ';
        
        echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible">';
        echo '<p>' . $warning . '<strong>' . esc_html($config->get('host.name')) . ':</strong> Modo ' . esc_html($mode_text) . ' activo. ';
        echo '<a href="' . esc_url(dev_tools_get_admin_url('tools.php?page=' . $menu_slug)) . '">Acceder a Dev Tools</a>';
        echo '</p></div>';
    }
}

// ========================================
// REGISTRO DE HOOKS CON CONTROL DE TIMING
// ========================================

/**
 * Inicializar gestor de módulos de forma segura
 * Evita inicializaciones duplicadas
 */
function dev_tools_init_module_manager() {
    if (!function_exists('dev_tools_get_module_manager')) {
        return false;
    }
    
    $module_manager = dev_tools_get_module_manager();
    if (!$module_manager) {
        return false;
    }
    
    if ($module_manager->isInitialized()) {
        return true; // Ya inicializado
    }
    
    return $module_manager->initialize();
}

/**
 * Inicializar Dev-Tools de forma segura usando hooks de WordPress
 * VERSIÓN MEJORADA: Evita problemas de timing y bucles infinitos
 */
function dev_tools_safe_init() {
    // Solo ejecutar si WordPress está completamente listo
    if (!dev_tools_is_wp_ready()) {
        return;
    }
    
    // Verificar si se debe activar completamente
    $should_activate = dev_tools_should_activate_full_features();
    
    if ($should_activate) {
        // Hooks de administración
        add_action('admin_enqueue_scripts', 'dev_tools_enqueue_assets');
        add_action('admin_notices', 'dev_tools_admin_notice');
        
        // Hook dinámico para enlaces en página de plugins
        $config = dev_tools_config();
        $plugin_basename = $config->get('host.basename');
        if ($plugin_basename) {
            add_filter('plugin_action_links_' . $plugin_basename, 'dev_tools_plugin_action_links');
        }
        
        // Inicializar gestor de módulos de forma segura
        dev_tools_init_module_manager();
    }
}

// Registrar la inicialización segura en el hook 'init' con prioridad baja
// Esto asegura que WordPress esté completamente cargado
add_action('init', 'dev_tools_safe_init', 25);

// Los hooks AJAX se registran inmediatamente pero sin verificaciones que puedan fallar
// Esto evita errores 500 en peticiones AJAX pero mantiene la funcionalidad
if (defined('DOING_AJAX') && DOING_AJAX) {
    // Durante AJAX, activar inmediatamente sin verificaciones complejas
    add_action('admin_enqueue_scripts', 'dev_tools_enqueue_assets');
    
    // Inicializar gestor de módulos para AJAX de forma segura
    add_action('wp_loaded', function() {
        dev_tools_init_module_manager();
    }, 10);
}

// Debug de configuración (disponible inmediatamente para troubleshooting)
if (isset($_GET['debug_config']) && $_GET['debug_config'] === '1') {
    add_action('admin_init', function() {
        if (dev_tools_is_wp_ready() && current_user_can('manage_options')) {
            $config = dev_tools_config();
            if (method_exists($config, 'render_debug_output')) {
                $config->render_debug_output();
            }
        }
    });
}

