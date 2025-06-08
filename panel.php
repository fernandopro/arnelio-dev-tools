<?php
/**
 * Dev Tools Panel Principal
 * Sistema de herramientas de desarrollo plugin-agnóstico
 * Utiliza Bootstrap para la interfaz y JavaScript moderno (ES6+)
 * 
 * @package DevTools
 * @version 2.0.0
 * @since 1.0.0
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
}

// Obtener configuración dinámica
$config = dev_tools_config();

// Obtener la URL base del admin usando nuestra función consistente
$admin_base_url = dev_tools_get_admin_url();
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Definir las pestañas disponibles
$tabs = [
    'dashboard' => ['title' => '🏠 Dashboard', 'icon' => 'dashboard'],
    'tests' => ['title' => '🧪 Tests', 'icon' => 'admin-tools'],
    'simulators' => ['title' => '⚡ Simuladores', 'icon' => 'performance'],
    'docs' => ['title' => '📚 Documentación', 'icon' => 'media-document'],
    'maintenance' => ['title' => '🔧 Mantenimiento', 'icon' => 'admin-settings'],
    'settings' => ['title' => '⚙️ Configuración', 'icon' => 'admin-generic']
];
?>

<div class="wrap">
    <div id="dev-tools-panel" class="dev-tools-container">
        <div class="container">
            <!-- Header dinámico -->
            <div class="dev-tools-header <?php echo $config->is_debug_mode() ? '' : 'production-warning'; ?>">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="h2 mb-2">
                                <i class="bi bi-tools"></i>
                                <?php echo esc_html($config->get('dev_tools.page_title')); ?>
                            </div>
                            <p class="mb-0 opacity-75">
                                Sistema de herramientas de desarrollo y testing
                                <?php if ($config->is_debug_mode()): ?>
                                    <span class="badge bg-secondary ms-2">🛠️ MODO DESARROLLO</span>
                                <?php else: ?>
                                    <span class="badge bg-danger ms-2">⚠️ MODO PRODUCCIÓN</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-white-50">
                                <small>
                                    <i class="bi bi-clock"></i>
                                    <?php echo date('d/m/Y H:i:s'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="container">
                <ul class="nav nav-pills justify-content-center mb-4" role="tablist">
                    <?php foreach ($tabs as $tab_key => $tab_info): ?>
                        <li style="margin-right: 5px" class="nav-item" role="presentation">
                            <a class="nav-link <?php echo $current_tab === $tab_key ? 'active' : ''; ?>"
                               href="<?php echo dev_tools_get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug') . '&tab=' . $tab_key); ?>"
                               role="tab">
                                <?php echo $tab_info['title']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <?php
                    // Cargar el contenido de la pestaña actual
                    switch ($current_tab) {
                        case 'dashboard':
                            include __DIR__ . '/tabs/dashboard.php';
                            break;
                        case 'tests':
                            include __DIR__ . '/tabs/tests.php';
                            break;
                        case 'simulators':
                            include __DIR__ . '/tabs/simulators.php';
                            break;
                        case 'docs':
                            include __DIR__ . '/tabs/docs.php';
                            break;
                        case 'maintenance':
                            include __DIR__ . '/tabs/maintenance.php';
                            break;
                        case 'settings':
                            include __DIR__ . '/tabs/settings.php';
                            break;
                        default:
                            echo '<div class="alert alert-danger">Pestaña no encontrada.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Los scripts se cargan automáticamente mediante wp_enqueue_script en loader.php
    // Esto sigue las mejores prácticas de WordPress y permite usar wp_localize_script
    // para pasar configuración y traducciones al JavaScript
    ?>
</div>
