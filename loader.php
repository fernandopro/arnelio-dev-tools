<?php
/**
 * Dev Tools Loader
 * Sistema plugin-agnóstico que detecta automáticamente el plugin host
 * 
 * @package DevTools
 * @version 2.0.0
 * @since 1.0.0
 */

// Cargar configuración global
require_once __DIR__ . '/config.php';

// Cargar el sistema de WordPress de forma segura
require_once __DIR__ . '/wp-load.php';

$wp_error = dev_tools_get_wp_error_safe();
if ($wp_error) {
    dev_tools_render_error_page($wp_error);
    exit;
}

// Solo cargar en el admin (ahora disponible en producción también)
if (!is_admin()) {
    return;
}

// Obtener configuración dinámica
$config = dev_tools_config();

// Cargar el manejador AJAX
require_once __DIR__ . '/ajax-handler.php';

/**
 * Registra el menú de dev-tools en el admin de WordPress (dinámico)
 */
function dev_tools_admin_menu() {
    $config = dev_tools_config();
    
    add_management_page(
        $config->get('dev_tools.page_title'),    // Título dinámico
        $config->get('dev_tools.menu_title'),    // Texto del menú
        $config->get('dev_tools.capability'),    // Capacidad requerida
        $config->get('dev_tools.menu_slug'),     // Slug dinámico
        'dev_tools_page'                         // Función callback
    );
}
add_action('admin_menu', 'dev_tools_admin_menu');

/**
 * Encola los estilos y scripts para dev-tools (dinámico)
 * Sigue las mejores prácticas de WordPress
 */
function dev_tools_enqueue_assets($hook) {
    $config = dev_tools_config();
    $menu_slug = $config->get('dev_tools.menu_slug');
    
    // Solo cargar en la página de dev-tools (slug dinámico)
    if ($hook !== 'tools_page_' . $menu_slug) {
        return;
    }

    $plugin_url = $config->get('paths.dev_tools_url');
    $plugin_version = $config->get('host.version');
    
    // Dev Tools CSS compilado (incluye Bootstrap y estilos personalizados)
    wp_enqueue_style(
        $config->get('assets.css_handle'),
        $plugin_url . 'dist/css/dev-tools-styles.min.css',
        array(),
        $plugin_version
    );

    // Dev Tools JavaScript principal compilado (incluye Bootstrap JS compilado)
    wp_enqueue_script(
        $config->get('assets.js_handle'),
        $plugin_url . 'dist/js/dev-tools.min.js',
        array(),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de tests compilado
    wp_enqueue_script(
        $config->get('assets.js_handle') . '-tests',
        $plugin_url . 'dist/js/dev-tools-tests.min.js',
        array($config->get('assets.js_handle')),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de documentación
    wp_enqueue_script(
        $config->get('assets.js_handle') . '-docs',
        $plugin_url . 'dist/js/dev-tools-docs.min.js',
        array($config->get('assets.js_handle')),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de mantenimiento
    wp_enqueue_script(
        $config->get('assets.js_handle') . '-maintenance',
        $plugin_url . 'dist/js/dev-tools-maintenance.min.js',
        array($config->get('assets.js_handle')),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de configuración
    wp_enqueue_script(
        $config->get('assets.js_settings_handle'),
        $plugin_url . 'dist/js/dev-tools-settings.min.js',
        array($config->get('assets.js_handle')),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript de utilidades solo en desarrollo
    if ($config->is_debug_mode()) {
        wp_enqueue_script(
            $config->get('assets.js_handle') . '-utils',
            $plugin_url . 'dist/js/dev-utils.min.js',
            array($config->get('assets.js_handle')),
            $plugin_version,
            true
        );
    }

    // Localizar script con configuración y traducciones (dinámico)
    wp_localize_script(
        $config->get('assets.js_handle'),
        $config->get('dev_tools.js_config_var'),
        $config->get_js_config()
    );
}
add_action('admin_enqueue_scripts', 'dev_tools_enqueue_assets');
/**
 * Función callback para renderizar la página de dev-tools (dinámico)
 */
function dev_tools_page() {
    $config = dev_tools_config();
    
    // Log de acceso a la página
    $config->log('Página dev-tools accedida por usuario: ' . wp_get_current_user()->user_login);
    
    // Cargar el panel
    require_once __DIR__ . '/panel.php';
}

/**
 * Configurar traducciones para JavaScript (dinámico)
 */
function dev_tools_setup_translations() {
    $config = dev_tools_config();
    
    if (function_exists('wp_set_script_translations')) {
        $text_domain = $config->get('host.text_domain');
        $languages_path = $config->get('host.dir_path') . '/languages';
        
        // Traducciones para los diferentes archivos JavaScript
        $js_files = [
            $config->get('assets.js_handle'),
            $config->get('assets.js_handle') . '-tests',
            $config->get('assets.js_handle') . '-docs',
            $config->get('assets.js_handle') . '-maintenance',
            $config->get('assets.js_settings_handle')
        ];
        
        foreach ($js_files as $handle) {
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

// Puedes utilizar la función `dev_tools_get_admin_url()` para generar URLs del admin de WordPress de manera segura y consistente, asegurando que se utilice `get_site_url()` nativo si `admin_url()` no está disponible. Esto es útil en entornos donde WordPress puede no estar completamente cargado o cuando se necesita una URL personalizada para el admin.

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

