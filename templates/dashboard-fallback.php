<?php
/**
 * Dashboard Fallback Template
 * Se muestra cuando el DashboardModule no está disponible
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
}

// Obtener información básica del sistema
$wp_version = get_bloginfo('version');
$php_version = PHP_VERSION;
$memory_usage = round(memory_get_usage() / 1024 / 1024, 2);
$memory_peak = round(memory_get_peak_usage() / 1024 / 1024, 2);
$memory_limit = ini_get('memory_limit');
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2"></i> Dashboard Dev-Tools (Modo Básico)
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">El sistema está funcionando en modo básico. Los módulos avanzados están en proceso de carga.</p>
            </div>
        </div>
    </div>
</div>

<!-- Estado del Sistema -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h5 class="card-title text-success">WordPress</h5>
                <p class="card-text display-6"><?php echo esc_html($wp_version); ?></p>
                <small class="text-muted">Versión</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">PHP</h5>
                <p class="card-text display-6"><?php echo esc_html($php_version); ?></p>
                <small class="text-muted">Versión</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h5 class="card-title text-warning">Memoria</h5>
                <p class="card-text display-6"><?php echo $memory_usage; ?>MB</p>
                <small class="text-muted">Uso actual</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h5 class="card-title text-info">Límite</h5>
                <p class="card-text display-6"><?php echo esc_html($memory_limit); ?></p>
                <small class="text-muted">Memoria PHP</small>
            </div>
        </div>
    </div>
</div>

<!-- Información del Sistema -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Estado del Sistema</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        WordPress Version
                        <span class="badge bg-success"><?php echo esc_html($wp_version); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PHP Version
                        <span class="badge bg-primary"><?php echo esc_html($php_version); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Debug Mode
                        <span class="badge bg-<?php echo (defined('WP_DEBUG') && WP_DEBUG) ? 'warning' : 'success'; ?>">
                            <?php echo (defined('WP_DEBUG') && WP_DEBUG) ? 'Activado' : 'Desactivado'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Memoria Pico
                        <span class="badge bg-info"><?php echo $memory_peak; ?>MB</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Recargar Página
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="console.log('Dev-Tools Test')">
                        <i class="bi bi-terminal"></i> Test Console
                    </button>
                    <a href="<?php echo admin_url('site-health.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-heart-pulse"></i> Site Health
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
