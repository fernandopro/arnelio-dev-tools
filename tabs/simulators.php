<?php
/**
 * Simulators Tab - Gestión de simuladores de funcionalidades
 */

$simulators_dir = __DIR__ . '/../simulators';

function get_available_simulators($dir) {
    $simulators = [];
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative_path = str_replace($dir . '/', '', $file->getPathname());
                $simulators[] = [
                    'file' => $relative_path,
                    'path' => $file->getPathname(),
                    'name' => basename($file->getBasename('.php')),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }
    }
    return $simulators;
}

$available_simulators = get_available_simulators($simulators_dir);
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="bi bi-cpu"></i>
                    Crear Nuevo Simulador
                </h5>
            </div>
            <div class="card-body">
                <form class="ajax-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <input type="hidden" name="action" value="tarokina_create_simulator">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('create_simulator'); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="sim-name" class="form-label">Nombre del Simulador</label>
                            <input type="text" class="form-control" id="sim-name" name="simulator_name" 
                                   placeholder="ej: PaymentGateway" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="sim-module" class="form-label">Módulo</label>
                            <select class="form-select" id="sim-module" name="simulator_module">
                                <option value="core">Core</option>
                                <option value="elementor">Elementor</option>
                                <option value="gutenberg">Gutenberg</option>
                                <option value="api">API</option>
                                <option value="payments">Payments</option>
                                <option value="auth">Authentication</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="sim-description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="sim-description" name="simulator_description" 
                                      rows="3" placeholder="Describe qué funcionalidad simula..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-plus-circle"></i>
                            Crear Simulador
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul"></i>
                    Simuladores Disponibles (<?php echo count($available_simulators); ?>)
                </h5>
                <div>
                    <button class="btn btn-light btn-sm btn-action" 
                            data-action="refresh_simulators"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                            data-bs-toggle="tooltip" 
                            title="Refrescar lista de simuladores">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($available_simulators)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-3">No hay simuladores disponibles</h6>
                        <p class="text-muted">Crea tu primer simulador usando el formulario de arriba.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Simulador</th>
                                    <th>Archivo</th>
                                    <th>Tamaño</th>
                                    <th>Modificado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_simulators as $sim): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($sim['name']); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo esc_html($sim['file']); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo number_format($sim['size'] / 1024, 1); ?> KB
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', $sim['modified']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-success btn-action"
                                                        data-action="run_simulator"
                                                        data-sim-file="<?php echo esc_attr($sim['file']); ?>"
                                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                                                    <i class="bi bi-play"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i>
                    Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning btn-action"
                            data-action="run_all_simulators"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                        <i class="bi bi-play-fill"></i>
                        Ejecutar Todos
                    </button>
                    <button class="btn btn-outline-info">
                        <i class="bi bi-download"></i>
                        Exportar Simuladores
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Resultados de Simuladores -->
<div class="modal fade" id="simulatorResultsModal" tabindex="-1" aria-labelledby="simulatorResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="simulatorResultsModalLabel">
                    <i class="bi bi-cpu"></i>
                    <span id="simulatorModalTitle">Resultados de Simuladores</span>
                </h5>
                <div class="ms-auto me-3">
                    <span id="simulatorExecutionTime" class="badge bg-info"></span>
                    <span id="simulatorStatus" class="badge bg-secondary">Esperando...</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Barra de progreso -->
                <div id="simulatorProgressContainer" class="p-3 border-bottom bg-light" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Ejecutando simulador...</small>
                        <small id="simulatorProgressText" class="text-muted">0%</small>
                    </div>
                    <div class="progress">
                        <div id="simulatorProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                             role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Resultados formateados -->
                <div id="simulator-results" class="p-3" style="min-height: 300px; max-height: 60vh; overflow-y: auto; background: #1e1e1e; color: #ffffff; font-family: 'Courier New', monospace; font-size: 0.875rem;">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-play-circle" style="font-size: 3rem;"></i>
                        <div class="mt-3">
                            <h6>Listo para ejecutar simuladores</h6>
                            <p class="mb-0">Haz clic en cualquier botón "Play" para comenzar</p>
                        </div>
                    </div>
                </div>

                <!-- Resumen de resultados -->
                <div id="simulatorSummary" class="border-top bg-light p-3" style="display: none;">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <div>
                                    <div class="fw-bold" id="simulatorsPassedCount">0</div>
                                    <small class="text-muted">Exitosos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                <div>
                                    <div class="fw-bold" id="simulatorsFailedCount">0</div>
                                    <small class="text-muted">Fallidos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-clock-fill text-info me-2"></i>
                                <div>
                                    <div class="fw-bold" id="simulatorsTotalTime">0s</div>
                                    <small class="text-muted">Tiempo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="clearSimulatorResults()">
                    <i class="bi bi-trash"></i>
                    Limpiar
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="copySimulatorResults()">
                    <i class="bi bi-clipboard"></i>
                    Copiar Resultados
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// El JavaScript específico para simuladores se carga automáticamente 
// mediante wp_enqueue_script en loader.php como 'tarokina-dev-tools-simulators-js'
// Esto sigue las mejores prácticas de WordPress
?>
