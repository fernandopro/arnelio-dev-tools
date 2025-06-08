<?php

/**
 * Tests Tab - Gestión de tests PHPUnit
 */

// Directorios de tests
$tests_dir = __DIR__ . '/../tests';
$phpunit_config = __DIR__ . '/../phpunit.xml';

// Obtener lista de tests disponibles (solo archivos Test.php válidos)
function get_available_tests($dir)
{
    $tests = [];
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filename = basename($file->getPathname());

                // Solo incluir archivos que terminen en 'Test.php' (tests válidos)
                if (!preg_match('/Test\.php$/', $filename)) {
                    continue;
                }

                // Excluir bootstrap.php por si acaso
                if ($filename === 'bootstrap.php') {
                    continue;
                }

                $relative_path = str_replace($dir . '/', '', $file->getPathname());
                $tests[] = [
                    'file' => $relative_path,
                    'path' => $file->getPathname(),
                    'name' => basename($file->getBasename('.php')),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }
    }
    return $tests;
}

$available_tests = get_available_tests($tests_dir);

// Separar tests por tipo
$unit_tests = array_filter($available_tests, function ($test) {
    return strpos($test['file'], 'unit/') !== false;
});

$integration_tests = array_filter($available_tests, function ($test) {
    return strpos($test['file'], 'integration/') !== false;
});

$other_tests = array_filter($available_tests, function ($test) {
    return strpos($test['file'], 'unit/') === false && strpos($test['file'], 'integration/') === false;
});

// Función para determinar el tipo de test y obtener badge
function get_test_type_badge($test_file)
{
    if (strpos($test_file, 'unit/') !== false) {
        return '<span class="badge bg-info me-2"><i class="bi bi-lightning"></i> Unitario</span>';
    } elseif (strpos($test_file, 'integration/') !== false) {
        return '<span class="badge bg-warning me-2"><i class="bi bi-diagram-3-fill"></i> Integración</span>';
    } else {
        return '<span class="badge bg-secondary me-2"><i class="bi bi-question-circle"></i> Otro</span>';
    }
}

// Función para renderizar tabla unificada de tests
function render_unified_tests_table($all_tests)
{
    if (empty($all_tests)):
?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
            <h5 class="text-muted mt-3">No hay tests disponibles</h5>
            <p class="text-muted">Coloca archivos de test en las carpetas <code>tests/unit/</code> o <code>tests/integration/</code> para verlos aquí.</p>
            <button class="btn btn-outline-primary mt-3 btn-action"
                data-action="create_wp_test"
                data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>">
                <i class="bi bi-plus-circle"></i>
                Crear Primer Test
            </button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">Tipo</th>
                        <th style="width: 35%;">Test</th>
                        <th style="width: 30%;">Archivo</th>
                        <th style="width: 10%;">Tamaño</th>
                        <th style="width: 20%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_tests as $test): ?>
                        <tr>
                            <td>
                                <?php echo get_test_type_badge($test['file']); ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <strong class="text-truncate" title="<?php echo esc_attr($test['name']); ?>">
                                        <?php echo esc_html($test['name']); ?>
                                    </strong>
                                </div>
                            </td>
                            <td>
                                <code class="small text-truncate d-block" title="<?php echo esc_attr($test['file']); ?>">
                                    <?php echo esc_html($test['file']); ?>
                                </code>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?php echo number_format($test['size'] / 1024, 1); ?> KB
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-success btn-action"
                                        data-action="run_single_test"
                                        data-test-file="<?php echo esc_attr($test['file']); ?>"
                                        data-nonce="<?php echo wp_create_nonce('run_single_test'); ?>"
                                        data-bs-toggle="tooltip"
                                        title="Ejecutar este test">
                                        <i class="bi bi-play"></i>
                                    </button>
                                    <button class="btn btn-danger btn-action"
                                        data-action="delete_test"
                                        data-test-file="<?php echo esc_attr($test['file']); ?>"
                                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                                        data-bs-toggle="tooltip"
                                        title="Eliminar test">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php endif;
}
?>

<!-- Panel de WP_UnitTestCase -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card border-primary">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-wordpress"></i>
                    WordPress PHPUnit Framework
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="text-primary">🧪 Framework Oficial de WordPress</h6>
                        <p class="mb-3">
                            <strong>WP_UnitTestCase</strong> es el framework oficial para testing en WordPress.
                            Proporciona acceso completo a todas las APIs de WordPress, factories, fixtures y un entorno de testing aislado.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span>Framework WordPress completo</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span>Base de datos de testing aislada</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span>Factories para usuarios, posts, etc.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span>Testing de hooks y filtros</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span>APIs y transients completos</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span>Fixtures y mocking avanzado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">





                                <div class="small">
                                    <strong>📚 Enlaces Útiles:</strong>
                                    <ul class="list-unstyled mt-2">
                                        <li>
                                            <a href="https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/"
                                                target="_blank" class="text-decoration-none">
                                                <i class="bi bi-link-45deg"></i>
                                                Documentación Oficial
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://github.com/sebastianbergmann/phpunit"
                                                target="_blank" class="text-decoration-none">
                                                <i class="bi bi-github"></i>
                                                GitHub wp-phpunit
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel Principal de Tests -->
<div id="tests-list">
    <div class="card">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">

                Tests Disponibles
            </h5>
            <div class="d-flex align-items-center gap-2">


                <div class="btn-group btn-group-sm" role="group">

                    <button class="btn btn-secondary btn-action" data-action="refresh_tests" data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>" data-bs-toggle="tooltip" title="Refrescar lista de tests">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                    <?php if (!empty($other_tests)): ?>
                        <button class="btn btn-secondary btn-action"
                            data-action="run_wp_tests"
                            data-args="--others"
                            data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                            data-bs-toggle="tooltip"
                            title="Ejecutar tests en otros directorios (excepto unit/ e integration/)">
                            Otros
                            <span class="badge text-bg-light"><?php echo count($other_tests); ?></span>
                        </button>
                    <?php endif; ?>

                    <button class="btn btn-info btn-action"
                        data-action="run_wp_tests"
                        data-args="--unit"
                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                        data-bs-toggle="tooltip"
                        title="Ejecutar todos los tests unitarios">
                        Unitarios
                        <span class="badge text-bg-light"><?php echo count($unit_tests); ?></span>
                    </button>

                    <button class="btn btn-warning btn-action"
                        data-action="run_wp_tests"
                        data-args="--integration"
                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                        data-bs-toggle="tooltip"
                        title="Ejecutar todos los tests de integración">
                        Integración
                        <span class="badge text-bg-light"><?php echo count($integration_tests); ?></span>
                    </button>

                    <button class="btn btn-dark btn-action"
                        data-action="run_wp_tests"
                        data-args="--all"
                        data-nonce="<?php echo wp_create_nonce('dev_tools_action'); ?>"
                        data-bs-toggle="tooltip"
                        title="Ejecutar todos los tests">
                        Todos
                        <span class="badge text-bg-light"><?php echo count($available_tests); ?></span>
                    </button>

                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Tabla unificada de tests -->
            <div class="p-3">
                <?php render_unified_tests_table($available_tests); ?>
            </div>
        </div>
    </div>
</div>

<!-- Info Panel para Ejecución en Consola -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="alert alert-info border-info">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <h6 class="alert-heading mb-2">📊 Resultados en Consola del Navegador</h6>
                    <p class="mb-1">
                        Los tests se ejecutan directamente en la <strong>consola del navegador</strong> para una experiencia más limpia y técnica.
                    </p>
                    <small class="text-muted">
                        <kbd>F12</kbd> → <strong>Console</strong> para ver resultados detallados con colores y formato.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Sistema de tests refactorizado - VERSIÓN SIN BOOTSTRAP TABS
// ============================================================
// El JavaScript específico para tests se carga automáticamente 
// mediante wp_enqueue_script en loader.php como 'tarokina-dev-tools-tests-js'
// 
// CAMBIOS EN ESTA VERSIÓN:
// ✅ Eliminadas las Bootstrap tabs que interferían con event listeners
// ✅ Tabla unificada con badges de tipo (Unitario/Integración/Otro)
// ✅ Event listeners simplificados sin código de tabs
// ✅ Tests se ejecutan directamente en console.log() del navegador
// ✅ Sin dependencias de progress-manager.js o modales complejos
//
// PARA VERIFICAR: Abre F12 → Console y ejecuta cualquier test
?>