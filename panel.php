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

// Log de inicio del panel
error_log('[DEV-TOOLS] Panel iniciando...');

// Obtener configuración y module manager
$config = dev_tools_config();
error_log('[DEV-TOOLS] Config obtenido en panel: ' . ($config ? 'SÍ' : 'NO'));

$module_manager = dev_tools_get_module_manager();
error_log('[DEV-TOOLS] ModuleManager obtenido en panel: ' . ($module_manager ? 'SÍ' : 'NO'));

$current_page = isset($_GET['page_section']) ? sanitize_text_field($_GET['page_section']) : 'dashboard';
error_log('[DEV-TOOLS] Página actual: ' . $current_page);

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

<!-- CSS INMEDIATO PARA FORZAR TEMA OSCURO SOBRE WORDPRESS ADMIN -->
<style>
/* Forzar tema oscuro para Dev-Tools */
html, body, #wpwrap, #wpcontent, #wpbody, #wpbody-content, .wrap {
    background: #1a1d23 !important;
    background-color: #1a1d23 !important;
    color: #ffffff !important;
}

/* Indicador visual de tema activo */
body.dev-tools-active::before {
    content: "🌙 Dev-Tools Dark";
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 999999;
    font-size: 12px;
    color: #a0aec0;
    opacity: 0.7;
    pointer-events: none;
}
</style>

<script>
// FORZAR TEMA OSCURO INMEDIATAMENTE AL CARGAR
(function() {
    'use strict';
    
    function applyDevToolsDarkTheme() {
        // Elementos principales a modificar
        const selectors = [
            'html', 'body', '#wpwrap', '#wpcontent', 
            '#wpbody', '#wpbody-content', '.wrap'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (el) {
                    el.style.setProperty('background', '#1a1d23', 'important');
                    el.style.setProperty('background-color', '#1a1d23', 'important');
                    el.style.setProperty('color', '#ffffff', 'important');
                }
            });
        });
        
        // Marcar como activo
        document.documentElement.classList.add('dev-tools-dark-theme');
        document.body.classList.add('dev-tools-active');
        
        console.log('🌙 Dev-Tools: Tema oscuro aplicado');
    }
    
    // Aplicar inmediatamente
    applyDevToolsDarkTheme();
    
    // Aplicar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyDevToolsDarkTheme);
    }
    
    // Re-aplicar después de cambios dinámicos (AJAX de WordPress)
    const observer = new MutationObserver(() => {
        applyDevToolsDarkTheme();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
    });
})();
</script>

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
                error_log('[DEV-TOOLS] Verificando module_manager...');
                error_log('[DEV-TOOLS] $module_manager existe: ' . ($module_manager ? 'SÍ' : 'NO'));
                if ($module_manager) {
                    error_log('[DEV-TOOLS] $module_manager->isInitialized(): ' . ($module_manager->isInitialized() ? 'SÍ' : 'NO'));
                }
                
                if ($module_manager && $module_manager->isInitialized()) {
                    error_log('[DEV-TOOLS] Usando Arquitectura 3.0 - Sistema Modular');
                    // Usar Arquitectura 3.0 - Sistema Modular
                    switch ($current_page) {
                        case 'dashboard':
                            /** @var DashboardModule|null $dashboard_module */
                            $dashboard_module = $module_manager->getModule('dashboard');
                            if ($dashboard_module && method_exists($dashboard_module, 'renderDashboardPage')) {
                                $dashboard_module->renderDashboardPage();
                            } else {
                                // Fallback: renderizar dashboard básico
                                echo '<div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Dashboard Dev-Tools - Arquitectura 3.0</strong>
                                </div>';
                                include __DIR__ . '/templates/dashboard-fallback.php';
                            }
                            break;
                            
                        case 'system-info':
                            /** @var SystemInfoModule|null $systeminfo_module */
                            $systeminfo_module = $module_manager->getModule('systeminfo');
                            if ($systeminfo_module && method_exists($systeminfo_module, 'render_panel')) {
                                echo $systeminfo_module->render_panel();
                            } else {
                                // Fallback: mostrar información básica del sistema
                                include __DIR__ . '/templates/system-info-fallback.php';
                            }
                            break;
                            
                        case 'cache':
                            /** @var CacheModule|null $cache_module */
                            $cache_module = $module_manager->getModule('cache');
                            if ($cache_module && method_exists($cache_module, 'render_panel')) {
                                echo $cache_module->render_panel();
                            } else {
                                // Fallback: panel de cache básico
                                echo '<div class="alert alert-info">
                                    <i class="bi bi-lightning"></i>
                                    <strong>Cache Management</strong><br>
                                    Módulo en desarrollo. Próximamente disponible.
                                </div>';
                            }
                            break;
                            
                        case 'ajax-tester':
                            /** @var AjaxTesterModule|null $ajaxtester_module */
                            $ajaxtester_module = $module_manager->getModule('ajaxtester');
                            if ($ajaxtester_module && method_exists($ajaxtester_module, 'render_panel')) {
                                echo $ajaxtester_module->render_panel();
                            } else {
                                // Fallback: tester AJAX básico
                                echo '<div class="alert alert-info">
                                    <i class="bi bi-code-slash"></i>
                                    <strong>AJAX Tester</strong><br>
                                    Herramienta de testing AJAX en desarrollo.
                                </div>';
                            }
                            break;
                            
                        case 'logs':
                            /** @var LogsModule|null $logs_module */
                            $logs_module = $module_manager->getModule('logs');
                            if ($logs_module && method_exists($logs_module, 'render_panel')) {
                                echo $logs_module->render_panel();
                            } else {
                                // Fallback: visor de logs básico
                                echo '<div class="alert alert-info">
                                    <i class="bi bi-journal-text"></i>
                                    <strong>Logs Viewer</strong><br>
                                    Sistema de logs en desarrollo.
                                </div>';
                            }
                            break;
                            
                        case 'performance':
                            /** @var PerformanceModule|null $performance_module */
                            $performance_module = $module_manager->getModule('performance');
                            if ($performance_module && method_exists($performance_module, 'render')) {
                                echo $performance_module->render();
                            } else {
                                // Fallback: métricas de performance básicas
                                echo '<div class="alert alert-info">
                                    <i class="bi bi-graph-up"></i>
                                    <strong>Performance Metrics</strong><br>
                                    Monitor de rendimiento en desarrollo.
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
                                count($module_manager->getModules()) : '0'; ?> módulos disponibles
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

<!-- Estilos personalizados para tema oscuro - FORZAR SOBRE WORDPRESS ADMIN -->
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

/* FORZAR TEMA OSCURO SOBRE WORDPRESS ADMIN */
html, body {
    background: var(--dev-tools-bg-dark) !important;
    color: var(--dev-tools-text-primary) !important;
}

#wpwrap, #wpcontent, #wpbody, #wpbody-content {
    background: var(--dev-tools-bg-dark) !important;
    color: var(--dev-tools-text-primary) !important;
}

.wrap {
    background: var(--dev-tools-bg-dark) !important;
    color: var(--dev-tools-text-primary) !important;
}

.dev-tools-body {
    background: var(--dev-tools-bg-dark) !important;
    color: var(--dev-tools-text-primary) !important;
    min-height: 100vh;
}

.dev-tools-wrap {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
    background: var(--dev-tools-bg-dark) !important;
}

.dev-tools-panel-v3 {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--dev-tools-bg-dark) !important;
}

/* ELIMINAR COMPLETAMENTE EL FONDO BLANCO DE WORDPRESS */
#wpadminbar, #adminmenumain, #adminmenuback, #adminmenuwrap {
    background: var(--dev-tools-bg-secondary) !important;
}

.wp-admin select, .wp-admin input[type="text"], .wp-admin input[type="email"], 
.wp-admin input[type="number"], .wp-admin input[type="password"], 
.wp-admin input[type="search"], .wp-admin input[type="tel"], 
.wp-admin input[type="url"], .wp-admin textarea {
    background-color: var(--dev-tools-bg-secondary) !important;
    color: var(--dev-tools-text-primary) !important;
    border-color: var(--dev-tools-border) !important;
}

/* Corregir WordPress Admin notices para tema oscuro */
.notice, .error, .updated {
    background: var(--dev-tools-bg-secondary) !important;
    border-left-color: var(--dev-tools-primary) !important;
    color: var(--dev-tools-text-primary) !important;
}

.notice-success {
    border-left-color: var(--dev-tools-success) !important;
}

.notice-error {
    border-left-color: var(--dev-tools-danger) !important;
}

.notice-warning {
    border-left-color: var(--dev-tools-warning) !important;
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
    --bs-body-bg: var(--dev-tools-bg-dark) !important;
    --bs-body-color: var(--dev-tools-text-primary) !important;
}

/* FORZAR TEMA OSCURO EN TODOS LOS ELEMENTOS DE WORDPRESS */
* {
    scrollbar-width: thin;
    scrollbar-color: var(--dev-tools-border) var(--dev-tools-bg-dark);
}

*::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

*::-webkit-scrollbar-track {
    background: var(--dev-tools-bg-dark);
}

*::-webkit-scrollbar-thumb {
    background: var(--dev-tools-border);
    border-radius: 4px;
}

*::-webkit-scrollbar-thumb:hover {
    background: var(--dev-tools-secondary);
}

/* Clase específica para asegurar tema oscuro */
.dev-tools-active {
    background: var(--dev-tools-bg-dark) !important;
    color: var(--dev-tools-text-primary) !important;
}

.dev-tools-dark-theme body,
.dev-tools-dark-theme #wpwrap,
.dev-tools-dark-theme #wpcontent,
.dev-tools-dark-theme #wpbody,
.dev-tools-dark-theme #wpbody-content,
.dev-tools-dark-theme .wrap {
    background: var(--dev-tools-bg-dark) !important;
    background-color: var(--dev-tools-bg-dark) !important;
    color: var(--dev-tools-text-primary) !important;
}

/* Asegurar que ningún elemento padre tenga fondo blanco */
.dev-tools-dark-theme * {
    box-shadow: none !important;
}

.dev-tools-dark-theme .wrap::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: var(--dev-tools-bg-dark);
    z-index: -1;
    pointer-events: none;
}
</style>

<!-- JavaScript para funcionalidad adicional y forzar tema oscuro -->
<script>
// FORZAR TEMA OSCURO INMEDIATAMENTE
(function() {
    'use strict';
    
    // Aplicar tema oscuro a elementos de WordPress Admin inmediatamente
    function forceDevToolsDarkTheme() {
        const elementsToStyle = [
            'html', 'body', '#wpwrap', '#wpcontent', '#wpbody', '#wpbody-content', '.wrap'
        ];
        
        elementsToStyle.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (el) {
                    el.style.setProperty('background', '#1a1d23', 'important');
                    el.style.setProperty('background-color', '#1a1d23', 'important');
                    el.style.setProperty('color', '#ffffff', 'important');
                }
            });
        });
        
        // Añadir clase para identificar que Dev-Tools está activo
        document.documentElement.classList.add('dev-tools-dark-theme');
        document.body.classList.add('dev-tools-active');
    }
    
    // Ejecutar inmediatamente
    forceDevToolsDarkTheme();
    
    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceDevToolsDarkTheme);
    }
    
    // Ejecutar cuando todo esté cargado
    window.addEventListener('load', forceDevToolsDarkTheme);
})();

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
    console.log('📦 Módulos cargados:', <?php echo json_encode(array_keys($module_manager->getModules())); ?>);
    <?php else: ?>
    console.log('⚠️ Sistema Modular: No disponible');
    <?php endif; ?>
});
</script>

