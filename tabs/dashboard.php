<?php
/**
 * Dashboard Tab - Panel principal de dev-tools
 */

// Obtener información del sistema
$php_version = PHP_VERSION;
$wp_version = get_bloginfo('version');
$plugin_data = get_plugin_data(dirname(dirname(__DIR__)) . '/tarokina-pro.php');
$plugin_version = $plugin_data['Version'] ?? 'N/A';

// Estadísticas del sistema
$stats = [
    'tests_available' => count(glob(__DIR__ . '/../tests/**/*.php')),
    'simulators_available' => count(glob(__DIR__ . '/../simulators/**/*.php')),
    'docs_available' => count(glob(__DIR__ . '/../docs/**/*.md')),
    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
    'memory_limit' => ini_get('memory_limit'),
];
?>

<div class="row">
    <!-- Información del Sistema -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i>
                    Información del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>WordPress:</strong></td>
                                <td><span class="badge bg-secondary"><?php echo $wp_version; ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>PHP:</strong></td>
                                <td><span class="badge bg-secondary"><?php echo $php_version; ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Plugin:</strong></td>
                                <td><span class="badge bg-secondary"><?php echo $plugin_version; ?></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Memoria en uso:</strong></td>
                                <td><?php echo $stats['memory_usage']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Límite de memoria:</strong></td>
                                <td><?php echo $stats['memory_limit']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Modo:</strong></td>
                                <td>
                                    <?php if ($config->is_debug_mode()): ?>
                                        <span class="badge text-bg-info">DESARROLLO</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">PRODUCCIÓN</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i>
                    Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <button class="btn btn-outline-secondary w-100 btn-action" 
                                data-action="run_all_tests"
                                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                data-bs-toggle="tooltip" 
                                title="Ejecuta todos los tests disponibles">
                            <i class="bi bi-play-circle"></i>
                            Ejecutar Todos los Tests
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-outline-secondary w-100 btn-action"
                                data-action="clear_cache"
                                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                data-bs-toggle="tooltip" 
                                title="Limpia todos los caches del sistema">
                            <i class="bi bi-trash"></i>
                            Limpiar Cache
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-outline-secondary w-100 btn-action"
                                data-action="generate_test_data"
                                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                data-bs-toggle="tooltip" 
                                title="Genera datos de prueba para testing">
                            <i class="bi bi-database-add"></i>
                            Generar Datos de Prueba
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-outline-secondary w-100 btn-action"
                                data-action="export_logs"
                                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                data-bs-toggle="tooltip" 
                                title="Exporta los logs del sistema">
                            <i class="bi bi-download"></i>
                            Exportar Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Lateral -->
    <div class="col-lg-4">
        <!-- Estadísticas -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up"></i>
                    Estadísticas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Tests Disponibles:</span>
                    <span class="badge bg-secondary fs-6"><?php echo $stats['tests_available']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Simuladores:</span>
                    <span class="badge bg-warning fs-6"><?php echo $stats['simulators_available']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Documentos:</span>
                    <span class="badge bg-secondary fs-6"><?php echo $stats['docs_available']; ?></span>
                </div>
            </div>
        </div>

        <!-- Estado del Sistema -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-activity"></i>
                    Estado del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>WordPress:</span>
                    <span class="status-indicator badge bg-secondary" data-status="wordpress">Activo</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>PHPUnit:</span>
                    <span class="status-indicator badge bg-secondary" data-status="phpunit">Activo</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Composer:</span>
                    <span class="status-indicator badge bg-secondary" data-status="composer">Activo</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Dev Tools:</span>
                    <span class="status-indicator badge bg-secondary" data-status="dev_tools">Activo</span>
                </div>
            </div>
        </div>

        <!-- Enlaces Rápidos -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-link-45deg"></i>
                    Enlaces Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="<?php echo dev_tools_get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug') . '&tab=tests'); ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="bi bi-flask text-primary"></i>
                        Gestión de Tests
                    </a>
                    <a href="<?php echo dev_tools_get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug') . '&tab=simulators'); ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="bi bi-cpu text-warning"></i>
                        Simuladores
                    </a>
                    <a href="<?php echo dev_tools_get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug') . '&tab=docs'); ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="bi bi-book text-success"></i>
                        Documentación
                    </a>
                    <a href="<?php echo dev_tools_get_admin_url('tools.php?page=' . $config->get('dev_tools.menu_slug') . '&tab=maintenance'); ?>" 
                       class="list-group-item list-group-item-action">
                        <i class="bi bi-tools text-info"></i>
                        Mantenimiento
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Recientes -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-file-text"></i>
                    Logs Recientes
                </h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="auto-refresh">
                    <label class="form-check-label" for="auto-refresh">
                        Auto-refresh
                    </label>
                </div>
            </div>
            <div class="card-body">
                <div id="log-content" style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 1rem; border-radius: 0.375rem; font-family: 'Courier New', monospace; font-size: 0.875rem;">
                    <div class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Los logs aparecerán aquí automáticamente...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
