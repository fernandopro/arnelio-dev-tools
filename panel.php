<?php
/**
 * Dev Tools Panel Principal - Arquitectura 3.0
 * Dashboard moderno con diseño dark theme y navegación completa
 * 
 * @package DevTools
 * @version 3.0.0
 * @since 1.0.0
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
}

// Obtener configuración y module manager
$config = dev_tools_config();
$module_manager = dev_tools_get_module_manager();
$current_page = isset($_GET['page_section']) ? sanitize_text_field($_GET['page_section']) : 'dashboard';

/**
 * Función helper para generar URLs de navegación dinámicas
 * Completamente plugin-agnóstica usando la configuración dinámica
 */
function dev_tools_get_nav_url($page_section = '') {
    $config = dev_tools_config();
    
    // Construir parámetros base dinámicamente
    $base_params = array(
        'page' => $config->get('dev_tools.menu_slug')
    );
    
    // Agregar page_section si se proporciona
    if (!empty($page_section)) {
        $base_params['page_section'] = $page_section;
    }
    
    // Construir query string
    $query_string = http_build_query($base_params);
    
    // Generar URL completa usando la función centralizada
    return dev_tools_get_admin_url('tools.php?' . $query_string);
}

// Definir todas las páginas/módulos disponibles
$pages = [
    'dashboard' => [
        'title' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'description' => 'Panel principal del sistema',
        'available' => true
    ],
    'system-info' => [
        'title' => 'System Info',
        'icon' => 'bi-info-circle',
        'description' => 'Información del servidor y WordPress',
        'available' => true
    ],
    'cache' => [
        'title' => 'Cache',
        'icon' => 'bi-lightning',
        'description' => 'Gestión de caché del sistema',
        'available' => true // Módulo implementado
    ],
    'ajax-tester' => [
        'title' => 'AJAX Tester',
        'icon' => 'bi-code-slash',
        'description' => 'Testing de endpoints AJAX',
        'available' => true // Módulo implementado
    ],
    'logs' => [
        'title' => 'Logs',
        'icon' => 'bi-journal-text',
        'description' => 'Visualización de logs del sistema',
        'available' => true // Módulo implementado
    ],
    'performance' => [
        'title' => 'Performance',
        'icon' => 'bi-graph-up',
        'description' => 'Métricas de rendimiento',
        'available' => true // Módulo implementado
    ]
];

// Verificar estado de módulos si el manager está disponible
if ($module_manager && $module_manager->isInitialized()) {
    foreach ($pages as $key => &$page) {
        $module_key = str_replace('-', '', $key); // dashboard, systeminfo, etc.
        $module = $module_manager->getModule($module_key);
        $page['module_available'] = $module !== null;
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($config->get('dev_tools.page_title')); ?></title>
</head>
<body class="dev-tools-body">

<div class="wrap dev-tools-wrap">
    <div id="dev-tools-panel" class="dev-tools-panel-v3">
        
        <!-- Header Principal -->
        <header class="dev-tools-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="header-brand">
                            <i class="bi bi-tools me-3"></i>
                            <div>
                                <h1 class="h3 mb-0"><?php echo esc_html($config->get('dev_tools.page_title')); ?></h1>
                                <small class="text-muted">Arquitectura 3.0 - Sistema Modular</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="header-info">
                            <?php if ($config->is_debug_mode()): ?>
                                <span class="badge bg-success me-2">
                                    <i class="bi bi-bug"></i> Modo Desarrollo
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning me-2">
                                    <i class="bi bi-shield-exclamation"></i> Modo Producción
                                </span>
                            <?php endif; ?>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i>
                                <?php echo date('d/m/Y H:i:s'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navegación Principal -->
        <nav class="dev-tools-nav">
            <div class="container-fluid">
                <div class="nav-container">
                    <?php foreach ($pages as $page_key => $page_info): ?>
                        <?php 
                        $is_active = $current_page === $page_key;
                        $is_available = $page_info['available'];
                        $module_status = isset($page_info['module_available']) ? $page_info['module_available'] : false;
                        $css_classes = ['nav-item'];
                        
                        if ($is_active) $css_classes[] = 'active';
                        if (!$is_available) $css_classes[] = 'disabled';
                        ?>
                        <a href="<?php echo dev_tools_get_nav_url($page_key); ?>" 
                           class="<?php echo implode(' ', $css_classes); ?>">
                            <div class="nav-content">
                                <i class="<?php echo $page_info['icon']; ?>"></i>
                                <span class="nav-title"><?php echo $page_info['title']; ?></span>
                                <div class="nav-status">
                                    <?php if ($is_available): ?>
                                        <?php if (isset($page_info['module_available']) && $page_info['module_available']): ?>
                                            <i class="bi bi-check-circle text-success" title="Módulo disponible"></i>
                                        <?php elseif (isset($page_info['module_available'])): ?>
                                            <i class="bi bi-exclamation-triangle text-warning" title="Módulo no encontrado"></i>
                                        <?php else: ?>
                                            <i class="bi bi-question-circle text-info" title="Estado desconocido"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="bi bi-hourglass text-secondary" title="Próximamente"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <small class="nav-description"><?php echo $page_info['description']; ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>

        <!-- Contenido Principal -->
        <main class="dev-tools-main">
            <div class="container-fluid">
                <?php
                // Renderizar contenido según la página actual
                if ($module_manager && $module_manager->isInitialized()) {
                    // Usar Arquitectura 3.0 - Sistema Modular
                    switch ($current_page) {
                        case 'dashboard':
                            $dashboard_module = $module_manager->getModule('dashboard');
                            if ($dashboard_module) {
                                $dashboard_module->renderDashboardPage();
                            } else {
                                echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Dashboard Module no encontrado. Verificando sistema...
                                </div>';
                                include __DIR__ . '/templates/system-check.php';
                            }
                            break;
                            
                        case 'system-info':
                            $systeminfo_module = $module_manager->getModule('systeminfo');
                            if ($systeminfo_module) {
                                echo $systeminfo_module->render_panel();
                            } else {
                                echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    System Info Module no encontrado.
                                </div>';
                            }
                            break;
                            
                        case 'cache':
                            $cache_module = $module_manager->getModule('cache');
                            if ($cache_module) {
                                echo $cache_module->render_panel();
                            } else {
                                echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Cache Module no encontrado.
                                </div>';
                            }
                            break;
                            
                        case 'ajax-tester':
                            $ajaxtester_module = $module_manager->getModule('ajaxtester');
                            if ($ajaxtester_module) {
                                echo $ajaxtester_module->render_panel();
                            } else {
                                echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    AJAX Tester Module no encontrado.
                                </div>';
                            }
                            break;
                            
                        case 'logs':
                            $logs_module = $module_manager->getModule('logs');
                            if ($logs_module) {
                                echo $logs_module->render_panel();
                            } else {
                                echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Logs Module no encontrado.
                                </div>';
                            }
                            break;
                            
                        case 'performance':
                            $performance_module = $module_manager->getModule('performance');
                            if ($performance_module) {
                                echo $performance_module->render();
                            } else {
                                echo '<div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Performance Module no encontrado.
                                </div>';
                            }
                            break;
                            
                        default:
                            echo '<div class="alert alert-danger">
                                <i class="bi bi-x-circle"></i>
                                Página no encontrada: ' . esc_html($current_page) . '
                            </div>';
                    }
                } else {
                    // Fallback: Sistema no disponible
                    echo '<div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Sistema Modular No Disponible</strong><br>
                        El sistema está cargando en modo compatibilidad.
                    </div>';
                    include __DIR__ . '/templates/system-check.php';
                }
                ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="dev-tools-footer">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            Dev-Tools Arquitectura 3.0 | 
                            <?php echo $module_manager && $module_manager->isInitialized() ? 
                                count($module_manager->getAvailableModules()) : '0'; ?> módulos disponibles
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            WordPress <?php echo get_bloginfo('version'); ?> | 
                            PHP <?php echo PHP_VERSION; ?>
                        </small>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Estilos personalizados para tema oscuro -->
<style>
:root {
    --dev-tools-primary: #0d6efd;
    --dev-tools-secondary: #6c757d;
    --dev-tools-success: #198754;
    --dev-tools-warning: #ffc107;
    --dev-tools-danger: #dc3545;
    --dev-tools-dark: #212529;
    --dev-tools-light: #f8f9fa;
    
    /* Dark theme colors */
    --dev-tools-bg-dark: #1a1d23;
    --dev-tools-bg-secondary: #2d3339;
    --dev-tools-bg-accent: #343a40;
    --dev-tools-text-primary: #ffffff;
    --dev-tools-text-secondary: #adb5bd;
    --dev-tools-border: #495057;
}

.dev-tools-body {
    background: var(--dev-tools-bg-dark);
    color: var(--dev-tools-text-primary);
    min-height: 100vh;
}

.dev-tools-wrap {
    margin: 0;
    padding: 0;
    max-width: none;
}

.dev-tools-panel-v3 {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--dev-tools-bg-dark);
}

/* Header */
.dev-tools-header {
    background: var(--dev-tools-bg-secondary);
    border-bottom: 2px solid var(--dev-tools-border);
    padding: 1.5rem 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.header-brand {
    display: flex;
    align-items: center;
}

.header-brand i {
    font-size: 2.5rem;
    color: var(--dev-tools-primary);
}

.header-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Navigation */
.dev-tools-nav {
    background: var(--dev-tools-bg-accent);
    border-bottom: 1px solid var(--dev-tools-border);
    padding: 0.5rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-container {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.5rem 0;
}

.nav-item {
    display: flex;
    flex-direction: column;
    padding: 1rem 1.5rem;
    background: var(--dev-tools-bg-secondary);
    border: 1px solid var(--dev-tools-border);
    border-radius: 8px;
    text-decoration: none;
    color: var(--dev-tools-text-secondary);
    transition: all 0.3s ease;
    min-width: 160px;
    white-space: nowrap;
}

.nav-item:hover {
    background: var(--dev-tools-bg-dark);
    color: var(--dev-tools-text-primary);
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

.nav-item.active {
    background: var(--dev-tools-primary);
    color: white;
    border-color: var(--dev-tools-primary);
}

.nav-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.nav-item.disabled:hover {
    transform: none;
    box-shadow: none;
}

.nav-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.nav-content i {
    font-size: 1.2rem;
}

.nav-title {
    font-weight: 600;
    flex: 1;
}

.nav-status i {
    font-size: 0.9rem;
}

.nav-description {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Main content */
.dev-tools-main {
    flex: 1;
    padding: 2rem 0;
    background: var(--dev-tools-bg-dark);
}

/* Cards */
.card {
    background: var(--dev-tools-bg-secondary);
    border: 1px solid var(--dev-tools-border);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.card-header {
    background: var(--dev-tools-bg-accent);
    border-bottom: 1px solid var(--dev-tools-border);
    border-radius: 12px 12px 0 0 !important;
}

/* Coming soon card */
.coming-soon-card {
    max-width: 600px;
    margin: 3rem auto;
}

.coming-soon-card .display-1 {
    font-size: 4rem;
}

/* Alerts */
.alert {
    border: none;
    border-radius: 8px;
}

.alert-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.alert-info {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    border: 1px solid rgba(13, 110, 253, 0.3);
}

.alert-success {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
    border: 1px solid rgba(25, 135, 84, 0.3);
}

/* Footer */
.dev-tools-footer {
    background: var(--dev-tools-bg-secondary);
    border-top: 1px solid var(--dev-tools-border);
    padding: 1rem 0;
    margin-top: auto;
}

/* Badges */
.badge {
    font-size: 0.75rem;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-container {
        flex-direction: column;
    }
    
    .nav-item {
        min-width: auto;
    }
    
    .header-brand {
        margin-bottom: 1rem;
    }
    
    .header-info {
        justify-content: flex-start;
    }
}

/* Bootstrap dark theme adjustments */
[data-bs-theme="dark"] {
    --bs-body-bg: var(--dev-tools-bg-dark);
    --bs-body-color: var(--dev-tools-text-primary);
}
</style>

<!-- JavaScript para funcionalidad adicional -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips si Bootstrap está disponible
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Log del sistema para debugging
    console.log('🚀 Dev-Tools Arquitectura 3.0 - Panel Cargado');
    console.log('📄 Página actual:', '<?php echo esc_js($current_page); ?>');
    console.log('🔧 Módulos disponibles:', <?php echo json_encode(array_keys($pages)); ?>);
    
    <?php if ($module_manager && $module_manager->isInitialized()): ?>
    console.log('✅ Sistema Modular: Operativo');
    console.log('📦 Módulos cargados:', <?php echo json_encode($module_manager->getAvailableModules()); ?>);
    <?php else: ?>
    console.log('⚠️ Sistema Modular: No disponible');
    <?php endif; ?>
});
</script>

</body>
</html>

