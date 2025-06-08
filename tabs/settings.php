<?php
/**
 * Settings Tab - Configuración del sistema dev-tools
 */

// Obtener configuraciones actuales
$current_settings = get_option('tarokina_dev_tools_settings', [
    'debug_mode' => false,
    'auto_refresh' => false,
    'log_level' => 'info',
    'max_log_size' => '10',
    'backup_retention' => '7',
    'test_timeout' => '300',
    'production_warning' => true,
    'email_notifications' => false,
    'notification_email' => get_option('admin_email')
]);
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Configuración General -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-gear"></i>
                    Configuración General
                </h5>
            </div>
            <div class="card-body">
                <form id="settings-form" class="ajax-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <input type="hidden" name="action" value="tarokina_save_settings">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_settings'); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="debug-mode" 
                                       name="debug_mode" <?php checked($current_settings['debug_mode']); ?>>
                                <label class="form-check-label" for="debug-mode">
                                    <strong>Modo Debug</strong>
                                    <br><small class="text-muted">Activa logging detallado</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto-refresh" 
                                       name="auto_refresh" <?php checked($current_settings['auto_refresh']); ?>>
                                <label class="form-check-label" for="auto-refresh">
                                    <strong>Auto-refresh</strong>
                                    <br><small class="text-muted">Actualiza logs automáticamente</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="production-warning" 
                                       name="production_warning" <?php checked($current_settings['production_warning']); ?>>
                                <label class="form-check-label" for="production-warning">
                                    <strong>Advertencia de Producción</strong>
                                    <br><small class="text-muted">Muestra alerta en modo producción</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email-notifications" 
                                       name="email_notifications" <?php checked($current_settings['email_notifications']); ?>>
                                <label class="form-check-label" for="email-notifications">
                                    <strong>Notificaciones por Email</strong>
                                    <br><small class="text-muted">Envía alertas por correo</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="log-level" class="form-label">Nivel de Log</label>
                            <select class="form-select" id="log-level" name="log_level">
                                <option value="debug" <?php selected($current_settings['log_level'], 'debug'); ?>>Debug</option>
                                <option value="info" <?php selected($current_settings['log_level'], 'info'); ?>>Info</option>
                                <option value="warning" <?php selected($current_settings['log_level'], 'warning'); ?>>Warning</option>
                                <option value="error" <?php selected($current_settings['log_level'], 'error'); ?>>Error</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="max-log-size" class="form-label">Tamaño Máximo de Log (MB)</label>
                            <input type="number" class="form-control" id="max-log-size" name="max_log_size" 
                                   value="<?php echo esc_attr($current_settings['max_log_size']); ?>" min="1" max="100">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="backup-retention" class="form-label">Retención de Backups (días)</label>
                            <input type="number" class="form-control" id="backup-retention" name="backup_retention" 
                                   value="<?php echo esc_attr($current_settings['backup_retention']); ?>" min="1" max="30">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="test-timeout" class="form-label">Timeout de Tests (segundos)</label>
                            <input type="number" class="form-control" id="test-timeout" name="test_timeout" 
                                   value="<?php echo esc_attr($current_settings['test_timeout']); ?>" min="30" max="3600">
                        </div>
                        
                        <div class="col-12">
                            <label for="notification-email" class="form-label">Email para Notificaciones</label>
                            <input type="email" class="form-control" id="notification-email" name="notification_email" 
                                   value="<?php echo esc_attr($current_settings['notification_email']); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i>
                            Guardar Configuración
                        </button>
                        <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetSettings()">
                            <i class="bi bi-arrow-clockwise"></i>
                            Restaurar Valores por Defecto
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Configuración de PHPUnit -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-flask"></i>
                    Configuración de PHPUnit
                </h5>
            </div>
            <div class="card-body">
                <form class="ajax-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <input type="hidden" name="action" value="tarokina_update_phpunit_config">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('update_phpunit_config'); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="test-suite" class="form-label">Test Suite por Defecto</label>
                            <select class="form-select" id="test-suite" name="default_test_suite">
                                <option value="unit">Unit Tests</option>
                                <option value="integration">Integration Tests</option>
                                <option value="all">Todos los Tests</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="memory-limit" class="form-label">Límite de Memoria</label>
                            <select class="form-select" id="memory-limit" name="memory_limit">
                                <option value="256M">256M</option>
                                <option value="512M" selected>512M</option>
                                <option value="1G">1G</option>
                                <option value="2G">2G</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="stop-on-failure" checked>
                                <label class="form-check-label" for="stop-on-failure">
                                    Parar en el primer fallo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="strict-coverage">
                                <label class="form-check-label" for="strict-coverage">
                                    Cobertura estricta
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="process-isolation">
                                <label class="form-check-label" for="process-isolation">
                                    Aislamiento de procesos
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i>
                            Actualizar Configuración PHPUnit
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Gestión de Datos -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="bi bi-database"></i>
                    Gestión de Datos
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-info h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-download text-info" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Exportar Configuración</h6>
                                <p class="text-muted small">Descarga la configuración actual</p>
                                <button class="btn btn-outline-info btn-action"
                                        data-action="export_settings"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                                    <i class="bi bi-download"></i>
                                    Exportar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-upload text-success" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Importar Configuración</h6>
                                <p class="text-muted small">Restaura configuración desde archivo</p>
                                <input type="file" class="form-control form-control-sm mb-2" accept=".json">
                                <button class="btn btn-outline-success">
                                    <i class="bi bi-upload"></i>
                                    Importar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-warning h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-arrow-clockwise text-warning" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Restaurar por Defecto</h6>
                                <p class="text-muted small">Vuelve a la configuración original</p>
                                <button class="btn btn-outline-warning btn-action"
                                        data-action="reset_settings"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                        onclick="return confirm('¿Estás seguro de que quieres restaurar la configuración por defecto?')">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Restaurar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-danger h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-trash text-danger" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Limpiar Todo</h6>
                                <p class="text-muted small">Elimina todos los datos de dev-tools</p>
                                <button class="btn btn-outline-danger btn-action"
                                        data-action="cleanup_all"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                        onclick="return confirm('¿ADVERTENCIA! Esta acción eliminará todos los datos de dev-tools. ¿Continuar?')">
                                    <i class="bi bi-trash"></i>
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Lateral -->
    <div class="col-lg-4">
        <!-- Información del Sistema -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i>
                    Información del Sistema
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Versión Plugin:</strong></td>
                        <td><?php echo get_plugin_data(dirname(dirname(__DIR__)) . '/tarokina-pro.php')['Version'] ?? 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress:</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHPUnit:</strong></td>
                        <td>
                            <?php 
                            if (file_exists(__DIR__ . '/../vendor/bin/phpunit')) {
                                echo 'Instalado';
                            } else {
                                echo '<span class="text-warning">No instalado</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Composer:</strong></td>
                        <td>
                            <?php 
                            if (file_exists(__DIR__ . '/../composer.json')) {
                                echo 'Configurado';
                            } else {
                                echo '<span class="text-warning">No configurado</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Shortcuts de Configuración -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i>
                    Configuraciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary btn-sm" onclick="applyDevProfile()">
                        <i class="bi bi-code-slash"></i>
                        Perfil Desarrollador
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="applyTestProfile()">
                        <i class="bi bi-bug"></i>
                        Perfil Testing
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="applyProdProfile()">
                        <i class="bi bi-shield-check"></i>
                        Perfil Producción
                    </button>
                </div>
            </div>
        </div>

        <!-- Estado de Configuración -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-check-circle"></i>
                    Estado de Configuración
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Dev Tools:</span>
                    <span class="badge bg-success">Configurado</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>PHPUnit:</span>
                    <span class="badge bg-success">Configurado</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Composer:</span>
                    <span class="badge bg-success">Configurado</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Permisos:</span>
                    <span class="badge bg-success">OK</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// El JavaScript específico para settings se carga automáticamente 
// mediante wp_enqueue_script en loader.php como 'tarokina-dev-tools-settings-js'
// Esto sigue las mejores prácticas de WordPress
?>
