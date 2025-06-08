<?php
/**
 * System Info Fallback Template
 * Se muestra cuando el SystemInfoModule no está disponible
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
}

// Recopilar información del sistema
$system_info = [
    'wordpress' => [
        'version' => get_bloginfo('version'),
        'multisite' => is_multisite(),
        'language' => get_locale(),
        'timezone' => get_option('timezone_string') ?: 'UTC',
    ],
    'server' => [
        'php_version' => PHP_VERSION,
        'web_server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'mysql_version' => function_exists('mysqli_get_server_info') ? mysqli_get_server_info(mysqli_connect()) : 'Unknown',
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
    ],
    'theme' => [
        'name' => wp_get_theme()->get('Name'),
        'version' => wp_get_theme()->get('Version'),
        'template' => get_template(),
        'stylesheet' => get_stylesheet(),
    ]
];
?>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Información del Sistema
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Información básica del servidor y WordPress.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- WordPress Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">WordPress</h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-6">Versión:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['wordpress']['version']); ?></dd>
                    
                    <dt class="col-sm-6">Multisitio:</dt>
                    <dd class="col-sm-6">
                        <span class="badge bg-<?php echo $system_info['wordpress']['multisite'] ? 'success' : 'secondary'; ?>">
                            <?php echo $system_info['wordpress']['multisite'] ? 'Sí' : 'No'; ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-6">Idioma:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['wordpress']['language']); ?></dd>
                    
                    <dt class="col-sm-6">Zona Horaria:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['wordpress']['timezone']); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- Server Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Servidor</h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-6">PHP:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['server']['php_version']); ?></dd>
                    
                    <dt class="col-sm-6">Servidor Web:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['server']['web_server']); ?></dd>
                    
                    <dt class="col-sm-6">Límite Memoria:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['server']['memory_limit']); ?></dd>
                    
                    <dt class="col-sm-6">Max Ejecución:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['server']['max_execution_time']); ?>s</dd>
                    
                    <dt class="col-sm-6">Upload Max:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['server']['upload_max_filesize']); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- Theme Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Tema Activo</h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-6">Nombre:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['theme']['name']); ?></dd>
                    
                    <dt class="col-sm-6">Versión:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['theme']['version']); ?></dd>
                    
                    <dt class="col-sm-6">Template:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['theme']['template']); ?></dd>
                    
                    <dt class="col-sm-6">Stylesheet:</dt>
                    <dd class="col-sm-6"><?php echo esc_html($system_info['theme']['stylesheet']); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Memory Usage -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Uso de Memoria</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h5 class="text-primary"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?>MB</h5>
                            <small class="text-muted">Memoria Actual</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h5 class="text-warning"><?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?>MB</h5>
                            <small class="text-muted">Memoria Pico</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h5 class="text-info"><?php echo esc_html(ini_get('memory_limit')); ?></h5>
                            <small class="text-muted">Límite PHP</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
