<?php
/**
 * Maintenance Tab - Herramientas de mantenimiento del sistema
 */
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Limpieza del Sistema -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="bi bi-broom"></i>
                    Limpieza del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="bi bi-folder-x text-warning" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Limpiar Cache</h6>
                                <p class="text-muted small">Elimina archivos de cache temporales</p>
                                <button class="btn btn-outline-warning btn-action"
                                        data-action="clear_cache"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                                    <i class="bi bi-trash"></i>
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-x text-danger" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Limpiar Logs</h6>
                                <p class="text-muted small">Elimina archivos de log antiguos</p>
                                <button class="btn btn-outline-danger btn-action"
                                        data-action="clear_logs"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                                    <i class="bi bi-trash"></i>
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="bi bi-arrow-clockwise text-info" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Regenerar Assets</h6>
                                <p class="text-muted small">Recompila CSS y JavaScript</p>
                                <button class="btn btn-outline-info btn-action"
                                        data-action="regenerate_assets"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Regenerar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                <h6 class="mt-2">Validar Sistema</h6>
                                <p class="text-muted small">Verifica integridad del plugin</p>
                                <button class="btn btn-outline-success btn-action"
                                        data-action="validate_system"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                                    <i class="bi bi-check-circle"></i>
                                    Validar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Base de Datos -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-server"></i>
                    Mantenimiento de Base de Datos
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <button class="btn btn-outline-secondary w-100 btn-action"
                                data-action="optimize_db"
                                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                            <i class="bi bi-speedometer"></i>
                            Optimizar BD
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-warning w-100 btn-action"
                                data-action="backup_db"
                                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                            <i class="bi bi-download"></i>
                            Backup BD
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-danger w-100"
                                onclick="return confirm('¿Estás seguro? Esta acción eliminará todos los datos de prueba.')">
                            <i class="bi bi-trash"></i>
                            Limpiar Datos Test
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs del Sistema -->
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-file-text"></i>
                    Logs del Sistema
                </h5>
                <div>
                    <select class="form-select form-select-sm" id="log-level-filter">
                        <option value="">Todos los niveles</option>
                        <option value="error">Errores</option>
                        <option value="warning">Advertencias</option>
                        <option value="info">Información</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div id="system-logs" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 1rem; border-radius: 0.375rem; font-family: 'Courier New', monospace; font-size: 0.875rem;">
                    <div class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Cargando logs del sistema...
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-outline-secondary btn-sm btn-action"
                            data-action="refresh_logs"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-arrow-clockwise"></i>
                        Refrescar
                    </button>
                    <button class="btn btn-outline-secondary btn-sm btn-action"
                            data-action="download_logs"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-download"></i>
                        Descargar
                    </button>
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
                    Estado del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Espacio en Disco:</span>
                        <span class="badge bg-success">85% libre</span>
                    </div>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: 85%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Memoria PHP:</span>
                        <span class="badge bg-warning">70% usada</span>
                    </div>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: 70%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Base de Datos:</span>
                        <span class="badge bg-primary"><?php echo size_format(get_option('db_size', 0)); ?></span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Cache:</span>
                        <span class="badge bg-secondary">12.5 MB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tareas Programadas -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock"></i>
                    Tareas Programadas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary btn-action"
                            data-action="schedule_maintenance"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-calendar-plus"></i>
                        Programar Mantenimiento
                    </button>
                    <button class="btn btn-outline-success btn-action"
                            data-action="run_cron"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-play-circle"></i>
                        Ejecutar Cron
                    </button>
                </div>
                
                <hr>
                
                <div class="small">
                    <strong>Próximas tareas:</strong>
                    <ul class="list-unstyled mt-2">
                        <li class="text-muted">
                            <i class="bi bi-clock"></i>
                            Backup automático: 02:00
                        </li>
                        <li class="text-muted">
                            <i class="bi bi-clock"></i>
                            Limpieza de cache: 06:00
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Herramientas de Diagnóstico -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bug"></i>
                    Diagnóstico
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-danger btn-action"
                            data-action="run_diagnostics"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-search"></i>
                        Diagnóstico Completo
                    </button>
                    <button class="btn btn-outline-warning btn-action"
                            data-action="check_permissions"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-shield-check"></i>
                        Verificar Permisos
                    </button>
                    <button class="btn btn-outline-info btn-action"
                            data-action="test_connections"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-wifi"></i>
                        Probar Conexiones
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


