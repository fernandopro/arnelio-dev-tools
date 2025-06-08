<?php
/**
 * Template de verificación del sistema - System Check
 * Se usa cuando el sistema modular no está disponible
 * 
 * @package DevTools
 * @version 3.0.0
 */

// Verificar que estamos en contexto correcto
if (!defined('ABSPATH')) {
    exit;
}

$config = dev_tools_config();
$module_manager = dev_tools_get_module_manager();
?>

<div class="system-check-container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            
            <!-- Estado del Sistema -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-gear-fill me-2"></i>
                        Estado del Sistema - Arquitectura 3.0
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Componente</th>
                                    <th>Estado</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>DevToolsConfig</strong></td>
                                    <td>
                                        <?php if (class_exists('DevToolsConfig')): ?>
                                            <span class="badge bg-success">✓ Cargado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Sistema de configuración global</td>
                                </tr>
                                <tr>
                                    <td><strong>DevToolsAjaxHandler</strong></td>
                                    <td>
                                        <?php if (class_exists('DevToolsAjaxHandler')): ?>
                                            <span class="badge bg-success">✓ Cargado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Manejador centralizado de AJAX</td>
                                </tr>
                                <tr>
                                    <td><strong>DevToolsModuleManager</strong></td>
                                    <td>
                                        <?php if (class_exists('DevToolsModuleManager')): ?>
                                            <span class="badge bg-success">✓ Cargado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Gestor de módulos del sistema</td>
                                </tr>
                                <tr>
                                    <td><strong>Module Manager Inicializado</strong></td>
                                    <td>
                                        <?php if ($module_manager && $module_manager->isInitialized()): ?>
                                            <span class="badge bg-success">✓ Operativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">⚠ No inicializado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Estado de inicialización del gestor</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Módulos Disponibles -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-puzzle-fill me-2"></i>
                        Módulos del Sistema
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($module_manager && $module_manager->isInitialized()): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped">
                                <thead>
                                    <tr>
                                        <th>Módulo</th>
                                        <th>Estado</th>
                                        <th>Archivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $modules_to_check = [
                                        'dashboard' => 'DashboardModule.php',
                                        'systeminfo' => 'SystemInfoModule.php',
                                        'cache' => 'CacheModule.php',
                                        'ajaxtester' => 'AjaxTesterModule.php',
                                        'logs' => 'LogsModule.php',
                                        'performance' => 'PerformanceModule.php'
                                    ];
                                    
                                    foreach ($modules_to_check as $module_key => $module_file): 
                                        $module = $module_manager->getModule($module_key);
                                        $file_exists = file_exists(__DIR__ . '/../modules/' . $module_file);
                                    ?>
                                        <tr>
                                            <td><strong><?php echo ucfirst($module_key); ?></strong></td>
                                            <td>
                                                <?php if ($module): ?>
                                                    <span class="badge bg-success">✓ Disponible</span>
                                                <?php elseif ($file_exists): ?>
                                                    <span class="badge bg-warning">⚠ Archivo encontrado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">➤ Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code><?php echo $module_file; ?></code>
                                                <?php if (!$file_exists): ?>
                                                    <small class="text-muted">(no implementado)</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Module Manager no disponible</strong><br>
                            No se puede verificar el estado de los módulos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones de Diagnóstico -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-tools me-2"></i>
                        Acciones de Diagnóstico
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 d-md-flex">
                        <button class="btn btn-primary" onclick="testSystemStatus()">
                            <i class="bi bi-play-circle"></i> Test Sistema
                        </button>
                        <button class="btn btn-info" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise"></i> Refrescar
                        </button>
                        <button class="btn btn-secondary" onclick="showConsoleInfo()">
                            <i class="bi bi-terminal"></i> Info Consola
                        </button>
                        <?php if ($config->is_debug_mode()): ?>
                        <button class="btn btn-warning" onclick="testAjaxEndpoint()">
                            <i class="bi bi-wifi"></i> Test AJAX
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Información del Entorno -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Información del Entorno
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></li>
                                <li><strong>PHP:</strong> <?php echo PHP_VERSION; ?></li>
                                <li><strong>Debug Mode:</strong> 
                                    <?php echo $config->is_debug_mode() ? 
                                        '<span class="text-success">Activado</span>' : 
                                        '<span class="text-warning">Desactivado</span>'; ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><strong>Plugin URL:</strong> 
                                    <code><?php echo $config->get('paths.dev_tools_url'); ?></code>
                                </li>
                                <li><strong>Menu Slug:</strong> 
                                    <code><?php echo $config->get('dev_tools.menu_slug'); ?></code>
                                </li>
                                <li><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function testSystemStatus() {
    console.log('🔍 Testing Dev-Tools System Status...');
    
    // Test básico de clases
    const tests = [
        { name: 'DevToolsConfig', exists: <?php echo class_exists('DevToolsConfig') ? 'true' : 'false'; ?> },
        { name: 'DevToolsAjaxHandler', exists: <?php echo class_exists('DevToolsAjaxHandler') ? 'true' : 'false'; ?> },
        { name: 'DevToolsModuleManager', exists: <?php echo class_exists('DevToolsModuleManager') ? 'true' : 'false'; ?> }
    ];
    
    tests.forEach(test => {
        console.log(`${test.exists ? '✅' : '❌'} ${test.name}: ${test.exists ? 'Disponible' : 'No encontrado'}`);
    });
    
    // Test de configuración JavaScript
    if (typeof devToolsConfig !== 'undefined') {
        console.log('✅ Config JavaScript disponible:', devToolsConfig);
    } else {
        console.log('❌ Config JavaScript no disponible');
    }
    
    alert('Test de sistema completado. Revisa la consola para más detalles.');
}

function refreshPage() {
    window.location.reload();
}

function showConsoleInfo() {
    console.log('📊 Dev-Tools System Information');
    console.log('='.repeat(40));
    console.log('Arquitectura: 3.0');
    console.log('Panel: Modo System Check');
    console.log('URL actual:', window.location.href);
    console.log('User Agent:', navigator.userAgent);
    console.log('Timestamp:', new Date().toISOString());
    
    alert('Información del sistema mostrada en la consola.');
}

<?php if ($config->is_debug_mode()): ?>
function testAjaxEndpoint() {
    if (typeof devToolsConfig === 'undefined') {
        alert('Config JavaScript no disponible para test AJAX');
        return;
    }
    
    console.log('🧪 Testing AJAX endpoint...');
    
    const formData = new FormData();
    formData.append('action', devToolsConfig.actionPrefix + '_dev_tools');
    formData.append('action_type', 'ping');
    formData.append('nonce', devToolsConfig.nonce);
    
    fetch(devToolsConfig.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ AJAX Response:', data);
        alert('AJAX test exitoso. Ver consola para detalles.');
    })
    .catch(error => {
        console.error('❌ AJAX Error:', error);
        alert('Error en AJAX test. Ver consola para detalles.');
    });
}
<?php endif; ?>

// Auto-load en desarrollo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 System Check Template cargado');
    
    <?php if ($config->is_debug_mode()): ?>
    // En modo debug, mostrar información adicional
    setTimeout(() => {
        console.log('🐛 Debug Mode: Mostrando información adicional...');
        showConsoleInfo();
    }, 1000);
    <?php endif; ?>
});
</script>

<style>
.system-check-container {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.table-dark {
    --bs-table-bg: var(--dev-tools-bg-secondary);
}

.card {
    border: 1px solid var(--dev-tools-border);
    background: var(--dev-tools-bg-secondary);
}

.card-header {
    background: var(--dev-tools-bg-accent);
    border-bottom: 1px solid var(--dev-tools-border);
}

code {
    background: var(--dev-tools-bg-dark);
    color: var(--dev-tools-primary);
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-size: 0.875rem;
}
</style>
