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

// Solo cargar en el admin
if (!is_admin()) {
    return;
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
// FUNCIONES DEL SISTEMA LEGACY (COMPATIBILIDAD)
// ========================================

/**
 * Registra el menú de dev-tools en el admin de WordPress (LEGACY)
 * NOTA: En arquitectura 3.0 esto lo maneja el DashboardModule
 * Se mantiene por compatibilidad hasta migración completa
 */
function dev_tools_admin_menu() {
    $config = dev_tools_config();
    
    // Verificar si ya existe el menú (creado por DashboardModule)
    global $_registered_pages;
    $menu_slug = $config->get('dev_tools.menu_slug');
    $page_hook = "tools_page_{$menu_slug}";
    
    if (isset($_registered_pages[$page_hook])) {
        // El DashboardModule ya registró el menú
        return;
    }
    
    // Verificar si el DashboardModule está disponible
    if (class_exists('DevToolsModuleManager')) {
        $manager = DevToolsModuleManager::getInstance();
        $dashboard_module = $manager->getModule('dashboard');
        
        if ($dashboard_module && $manager->isInitialized()) {
            // El sistema modular está operativo, no usar legacy
            return;
        }
    }
    
    // Crear menú legacy solo si el sistema modular no está disponible
    add_management_page(
        $config->get('dev_tools.page_title'),    // Título dinámico
        $config->get('dev_tools.menu_title'),    // Texto del menú
        $config->get('dev_tools.capability'),    // Capacidad requerida
        $config->get('dev_tools.menu_slug'),     // Slug dinámico
        'dev_tools_legacy_page'                  // Función callback legacy
    );
}

/**
 * Página legacy de dev-tools
 * Se usa solo si el DashboardModule no está disponible
 */
function dev_tools_legacy_page() {
    ?>
    <div class="wrap">
        <h1>Dev Tools - Modo Compatibilidad</h1>
        <div class="notice notice-warning">
            <p><strong>Arquitectura 3.0 en transición:</strong> El sistema está cargando en modo compatibilidad. 
            Los módulos se están inicializando...</p>
        </div>
        
        <div class="card">
            <h2>Estado del Sistema</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Configuración:</strong></td>
                    <td><?php echo class_exists('DevToolsConfig') ? '✓ Cargada' : '✗ Error'; ?></td>
                </tr>
                <tr>
                    <td><strong>AJAX Handler:</strong></td>
                    <td><?php echo class_exists('DevToolsAjaxHandler') ? '✓ Cargado' : '✗ Error'; ?></td>
                </tr>
                <tr>
                    <td><strong>Module Manager:</strong></td>
                    <td><?php echo class_exists('DevToolsModuleManager') ? '✓ Cargado' : '✗ Error'; ?></td>
                </tr>
                <tr>
                    <td><strong>Dashboard Module:</strong></td>
                    <td>
                        <?php 
                        if (class_exists('DevToolsModuleManager')) {
                            $manager = DevToolsModuleManager::getInstance();
                            $dashboard = $manager->getModule('dashboard');
                            echo $dashboard ? '✓ Disponible' : '⚠ No encontrado';
                        } else {
                            echo '✗ Manager no disponible';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card mt-4">
            <h2>Acciones de Diagnóstico</h2>
            <p>
                <button class="button button-primary" onclick="testDevToolsSystem()">
                    Test Sistema
                </button>
                <button class="button" onclick="refreshPage()">
                    Refrescar Página
                </button>
            </p>
        </div>
    </div>
    
    <script>
    function testDevToolsSystem() {
        console.log('Testing dev-tools system...');
        
        // Test configuración
        if (typeof devToolsConfig !== 'undefined') {
            console.log('✓ Config disponible:', devToolsConfig);
        } else {
            console.log('✗ Config no disponible');
        }
        
        // Test AJAX
        if (typeof devToolsConfig !== 'undefined' && devToolsConfig.ajaxUrl) {
            fetch(devToolsConfig.ajaxUrl, {
                method: 'POST',
                body: new FormData(Object.assign(document.createElement('form'), {
                    innerHTML: `<input name="action" value="${devToolsConfig.actionPrefix}_dev_tools">
                               <input name="action_type" value="ping">
                               <input name="nonce" value="${devToolsConfig.nonce}">`
                }))
            })
            .then(r => r.json())
            .then(data => {
                console.log('✓ AJAX Response:', data);
                alert('Test completado. Ver consola para detalles.');
            })
            .catch(e => {
                console.log('✗ AJAX Error:', e);
                alert('Error en test AJAX. Ver consola.');
            });
        } else {
            alert('No se puede realizar test AJAX: configuración no disponible');
        }
    }
    
    function refreshPage() {
        window.location.reload();
    }
    </script>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    .mt-4 {
        margin-top: 20px;
    }
    </style>
    <?php
}

// Registrar menú con prioridad alta para permitir que DashboardModule lo sobrescriba
add_action('admin_menu', 'dev_tools_admin_menu', 30); // Prioridad 30 - después del sistema modular

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

