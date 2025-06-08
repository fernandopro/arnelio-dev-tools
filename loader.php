<?php
/**
 * Dev Tools Loader
 * Carga las herramientas de desarrollo del plugin
 */

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

// Cargar el manejador AJAX
require_once __DIR__ . '/ajax-handler.php';

/**
 * Registra el menú de dev-tools en el admin de WordPress
 */
function tarokina_dev_tools_admin_menu() {
    add_management_page(
        'Tarokina Dev Tools',           // Título de la página
        'Tarokina Dev Tools',           // Texto del menú
        'manage_options',               // Capacidad requerida
        'tarokina-dev-tools',          // Slug del menú
        'tarokina_dev_tools_page'      // Función callback
    );
}
add_action('admin_menu', 'tarokina_dev_tools_admin_menu');

/**
 * Encola los estilos y scripts para dev-tools
 * Sigue las mejores prácticas de WordPress
 */
function tarokina_dev_tools_enqueue_assets($hook) {
    // Solo cargar en la página de dev-tools
    if ($hook !== 'tools_page_tarokina-dev-tools') {
        return;
    }

    $plugin_url = plugin_dir_url(__FILE__);
    
    // Obtener la versión del plugin desde el archivo principal o usar una por defecto
    $plugin_version = '1.0.0';
    if (defined('TKINA_TAROKINA_PRO_VERSION')) {
        $plugin_version = TKINA_TAROKINA_PRO_VERSION;
    } elseif (function_exists('get_plugin_data')) {
        $plugin_file = dirname(dirname(__FILE__)) . '/tarokina-pro.php';
        if (file_exists($plugin_file)) {
            $plugin_data = get_plugin_data($plugin_file);
            $plugin_version = $plugin_data['Version'] ?? '1.0.0';
        }
    }

    // Dev Tools CSS compilado (incluye Bootstrap y estilos personalizados)
    wp_enqueue_style(
        'tarokina-dev-tools',
        $plugin_url . 'dist/css/dev-tools-styles.min.css',
        array(),
        $plugin_version
    );

    // Dev Tools JavaScript principal compilado (incluye Bootstrap JS compilado)
    wp_enqueue_script(
        'tarokina-dev-tools',
        $plugin_url . 'dist/js/dev-tools.min.js',
        array(),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de tests compilado
    wp_enqueue_script(
        'tarokina-dev-tools-tests',
        $plugin_url . 'dist/js/dev-tools-tests.min.js',
        array('tarokina-dev-tools'),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de documentación
    wp_enqueue_script(
        'tarokina-dev-tools-docs',
        $plugin_url . 'dist/js/dev-tools-docs.min.js',
        array('tarokina-dev-tools'),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de mantenimiento
    wp_enqueue_script(
        'tarokina-dev-tools-maintenance',
        $plugin_url . 'dist/js/dev-tools-maintenance.min.js',
        array('tarokina-dev-tools'),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript específico para la pestaña de configuración
    wp_enqueue_script(
        'tarokina-dev-tools-settings',
        $plugin_url . 'dist/js/dev-tools-settings.min.js',
        array('tarokina-dev-tools'),
        $plugin_version,
        true // Cargar en el footer
    );

    // JavaScript de utilidades solo en desarrollo
    if (!defined('TAROKINA_PRODUCTION_MODE') || !TAROKINA_PRODUCTION_MODE) {
        wp_enqueue_script(
            'tarokina-dev-tools-utils',
            $plugin_url . 'dist/js/dev-utils.min.js',
            array('tarokina-dev-tools'),
            $plugin_version,
            true
        );
    }

    // Localizar script con configuración y traducciones
    wp_localize_script(
        'tarokina-dev-tools',
        'tkn_dev_tools_config',
        array(
            'ajax_url' => dev_tools_get_admin_url('admin-ajax.php'),
            'debug_mode' => (!defined('TAROKINA_PRODUCTION_MODE') || !TAROKINA_PRODUCTION_MODE),
            'nonces' => array(
                'dev_tools' => wp_create_nonce('dev_tools_action'),
                'dev_tools_status' => wp_create_nonce('dev_tools_status'),
                'dev_tools_logs' => wp_create_nonce('dev_tools_logs'),
                'run_single_test' => wp_create_nonce('run_single_test')
            ),
            'strings' => array(
                'processing' => __('Procesando...', 'tarokina-pro'),
                'error' => __('Error', 'tarokina-pro'),
                'success' => __('Éxito', 'tarokina-pro'),
                'invalid_response' => __('Respuesta del servidor inválida', 'tarokina-pro'),
                'form_processed' => __('Formulario procesado correctamente', 'tarokina-pro'),
                'action_executed' => __('Acción ejecutada correctamente', 'tarokina-pro'),
                'tests_updated' => __('Lista de tests actualizada', 'tarokina-pro'),
                'simulators_updated' => __('Lista de simuladores actualizada', 'tarokina-pro'),
                'auto_refresh_on' => __('Auto-refresh de logs activado', 'tarokina-pro'),
                'auto_refresh_off' => __('Auto-refresh de logs desactivado', 'tarokina-pro'),
                'reloading_page' => __('Recargando página...', 'tarokina-pro'),
                'test_results_welcome' => __('Los resultados de los tests aparecerán aquí...', 'tarokina-pro'),
                'test_running' => __('Ejecutando test...', 'tarokina-pro'),
                'test_completed' => __('Test completado', 'tarokina-pro'),
                'test_failed' => __('Test falló', 'tarokina-pro'),
                'test_error' => __('Error en test', 'tarokina-pro')
            )
        )
    );

    // Configurar traducciones para JavaScript
    if (function_exists('wp_set_script_translations')) {
        // Traducciones para el archivo principal
        wp_set_script_translations(
            'tarokina-dev-tools',
            'tarokina-pro',
            plugin_dir_path(__FILE__) . '../languages'
        );
        
        // Traducciones para el archivo de tests
        wp_set_script_translations(
            'tarokina-dev-tools-tests',
            'tarokina-pro',
            plugin_dir_path(__FILE__) . '../languages'
        );
        
        // Traducciones para el archivo de documentación
        wp_set_script_translations(
            'tarokina-dev-tools-docs',
            'tarokina-pro',
            plugin_dir_path(__FILE__) . '../languages'
        );
        
        // Traducciones para el archivo de mantenimiento
        wp_set_script_translations(
            'tarokina-dev-tools-maintenance',
            'tarokina-pro',
            plugin_dir_path(__FILE__) . '../languages'
        );
        
        // Traducciones para el archivo de configuración
        wp_set_script_translations(
            'tarokina-dev-tools-settings',
            'tarokina-pro',
            plugin_dir_path(__FILE__) . '../languages'
        );
    }
}
add_action('admin_enqueue_scripts', 'tarokina_dev_tools_enqueue_assets');

/**
 * Renderiza la página de dev-tools
 */
function tarokina_dev_tools_page() {
    $panel_file = plugin_dir_path(__FILE__) . 'panel.php';
    
    if (file_exists($panel_file)) {
        include $panel_file;
    } else {
        echo '<div class="notice notice-error"><p>';
        echo 'Error: No se pudo encontrar el archivo panel.php en dev-tools.';
        echo '</p></div>';
    }
}

/**
 * Añade enlaces de acceso rápido en la página de plugins
 */
function tarokina_dev_tools_plugin_action_links($links) {
    if (is_admin()) {
        $dev_tools_link = dev_tools_get_admin_url('tools.php?page=tarokina-dev-tools');
        $color = TAROKINA_PRODUCTION_MODE ? '#d63638' : '#0073aa'; // Rojo en producción, azul en desarrollo
        $text = TAROKINA_PRODUCTION_MODE ? '⚠️ Dev Tools (PROD)' : '🔧 Dev Tools';
        $links[] = '<a href="' . $dev_tools_link . '" style="color: ' . $color . '; font-weight: bold;">' . $text . '</a>';
    }
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/tarokina-pro.php'), 'tarokina_dev_tools_plugin_action_links');

// Puedes utilizar la funcion `dev_tools_get_admin_url()` para generar URLs del admin de WordPress de manera segura y consistente, asegurando que se utilice `get_site_url()` nativo si `admin_url()` no está disponible. Esto es útil en entornos donde WordPress puede no estar completamente cargado o cuando se necesita una URL personalizada para el admin.

/**
 * Genera URLs del admin de WordPress usando get_site_url() nativo
 * Optimizado para entornos locales como http://localhost:10019/
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
 * Añade notificación en el dashboard cuando las dev-tools están activas
 */
function tarokina_dev_tools_admin_notice() {
    if (is_admin()) {
        $current_screen = get_current_screen();
        
        // Solo mostrar en páginas principales del admin
        if (in_array($current_screen->id, ['dashboard', 'plugins', 'tools_page_tarokina-dev-tools'])) {
            $notice_class = TAROKINA_PRODUCTION_MODE ? 'notice-warning' : 'notice-info';
            $mode_text = TAROKINA_PRODUCTION_MODE ? 'PRODUCCIÓN' : 'desarrollo';
            $warning = TAROKINA_PRODUCTION_MODE ? '⚠️ <strong>ATENCIÓN:</strong> Usando herramientas de desarrollo en ' : '';
            
            echo '<div class="notice ' . $notice_class . ' is-dismissible">';
            echo '<p>' . $warning . '<strong>Tarokina Pro:</strong> Modo ' . $mode_text . ' activo. ';
            echo '<a href="' . dev_tools_get_admin_url('tools.php?page=tarokina-dev-tools') . '">Acceder a Dev Tools</a>';
            echo '</p></div>';
        }
    }
}
add_action('admin_notices', 'tarokina_dev_tools_admin_notice');

