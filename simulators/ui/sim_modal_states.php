<?php
/**
 * Simulador: Estados de Modal UI
 * Descripción: Simula diferentes estados de modales para testing de interfaz
 * Módulo: ui/modals
 * Autor: Tarokina Pro Team
 * Versión: 1.0.0
 * Fecha: 2025-01-17
 */

// Protección de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simula diferentes estados de modales
 * 
 * @param string $modal_type Tipo de modal
 * @param string $state Estado a simular
 * @return array HTML y configuración del modal
 */
function simulate_modal_states($modal_type = 'lectura', $state = 'loading') {
    $modal_configs = [
        'lectura' => [
            'title' => 'Lectura de Cartas',
            'size' => 'large',
            'states' => [
                'loading' => [
                    'title' => 'Preparando tu lectura...',
                    'content' => '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-3">Mezclando las cartas...</p></div>',
                    'buttons' => []
                ],
                'success' => [
                    'title' => 'Tu Lectura está Lista',
                    'content' => '<div class="alert alert-success"><strong>¡Éxito!</strong> Tu lectura ha sido completada.</div><div class="cartas-resultado">Aquí aparecerían las cartas...</div>',
                    'buttons' => [
                        ['text' => 'Ver Interpretación', 'class' => 'btn-primary'],
                        ['text' => 'Nueva Lectura', 'class' => 'btn-secondary']
                    ]
                ],
                'error' => [
                    'title' => 'Error en la Lectura',
                    'content' => '<div class="alert alert-danger"><strong>Error:</strong> No se pudo completar la lectura. Por favor, inténtalo nuevamente.</div>',
                    'buttons' => [
                        ['text' => 'Reintentar', 'class' => 'btn-primary'],
                        ['text' => 'Cancelar', 'class' => 'btn-secondary']
                    ]
                ]
            ]
        ],
        'license' => [
            'title' => 'Activación de Licencia',
            'size' => 'medium',
            'states' => [
                'input' => [
                    'title' => 'Ingresa tu Clave de Licencia',
                    'content' => '<form><div class="mb-3"><label for="license-key" class="form-label">Clave de Licencia</label><input type="text" class="form-control" id="license-key" placeholder="TK-XXXX-XXXX-XXXX"></div></form>',
                    'buttons' => [
                        ['text' => 'Activar', 'class' => 'btn-primary'],
                        ['text' => 'Cancelar', 'class' => 'btn-secondary']
                    ]
                ],
                'validating' => [
                    'title' => 'Validando Licencia...',
                    'content' => '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Verificando tu licencia...</p></div>',
                    'buttons' => []
                ],
                'success' => [
                    'title' => '¡Licencia Activada!',
                    'content' => '<div class="alert alert-success"><strong>¡Perfecto!</strong> Tu licencia ha sido activada correctamente.</div><ul class="list-group list-group-flush"><li class="list-group-item">Licencia: Tarokina Pro</li><li class="list-group-item">Expira: 30 días</li><li class="list-group-item">Activaciones: 1/5</li></ul>',
                    'buttons' => [
                        ['text' => 'Continuar', 'class' => 'btn-success']
                    ]
                ],
                'error' => [
                    'title' => 'Error de Activación',
                    'content' => '<div class="alert alert-danger"><strong>Error:</strong> La clave de licencia no es válida o ha expirado.</div>',
                    'buttons' => [
                        ['text' => 'Reintentar', 'class' => 'btn-primary'],
                        ['text' => 'Contactar Soporte', 'class' => 'btn-outline-primary']
                    ]
                ]
            ]
        ],
        'settings' => [
            'title' => 'Configuración',
            'size' => 'large',
            'states' => [
                'loading' => [
                    'title' => 'Cargando Configuración...',
                    'content' => '<div class="text-center"><div class="spinner-border" role="status"></div></div>',
                    'buttons' => []
                ],
                'form' => [
                    'title' => 'Configuración de Tarokina',
                    'content' => '<form><div class="row"><div class="col-md-6"><div class="mb-3"><label class="form-label">Tipo de Baraja</label><select class="form-select"><option>Rider-Waite</option><option>Marsella</option></select></div></div><div class="col-md-6"><div class="mb-3"><label class="form-label">Idioma</label><select class="form-select"><option>Español</option><option>English</option></select></div></div></div></form>',
                    'buttons' => [
                        ['text' => 'Guardar', 'class' => 'btn-primary'],
                        ['text' => 'Restablecer', 'class' => 'btn-outline-secondary']
                    ]
                ],
                'saving' => [
                    'title' => 'Guardando...',
                    'content' => '<div class="text-center"><div class="spinner-border text-success" role="status"></div><p class="mt-3">Guardando configuración...</p></div>',
                    'buttons' => []
                ],
                'saved' => [
                    'title' => 'Configuración Guardada',
                    'content' => '<div class="alert alert-success"><strong>¡Guardado!</strong> Tu configuración ha sido actualizada.</div>',
                    'buttons' => [
                        ['text' => 'Cerrar', 'class' => 'btn-success']
                    ]
                ]
            ]
        ]
    ];
    
    if (!isset($modal_configs[$modal_type])) {
        return ['error' => 'Tipo de modal no válido'];
    }
    
    $modal_config = $modal_configs[$modal_type];
    
    if (!isset($modal_config['states'][$state])) {
        return ['error' => 'Estado no válido para el modal ' . $modal_type];
    }
    
    $state_config = $modal_config['states'][$state];
    
    // Generar HTML del modal
    $html = generate_modal_html($modal_type, $modal_config, $state_config);
    
    return [
        'modal_type' => $modal_type,
        'state' => $state,
        'config' => $modal_config,
        'state_config' => $state_config,
        'html' => $html,
        'timestamp' => current_time('mysql')
    ];
}

/**
 * Genera el HTML completo del modal
 * 
 * @param string $modal_type Tipo de modal
 * @param array $modal_config Configuración del modal
 * @param array $state_config Configuración del estado
 * @return string HTML del modal
 */
function generate_modal_html($modal_type, $modal_config, $state_config) {
    $modal_id = 'modal-' . $modal_type . '-' . uniqid();
    $size_class = $modal_config['size'] === 'large' ? 'modal-lg' : ($modal_config['size'] === 'small' ? 'modal-sm' : '');
    
    $html = '<div class="modal fade" id="' . $modal_id . '" tabindex="-1" aria-hidden="true">';
    $html .= '<div class="modal-dialog ' . $size_class . '">';
    $html .= '<div class="modal-content">';
    
    // Header
    $html .= '<div class="modal-header">';
    $html .= '<h5 class="modal-title">' . esc_html($state_config['title']) . '</h5>';
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    $html .= '</div>';
    
    // Body
    $html .= '<div class="modal-body">';
    $html .= $state_config['content'];
    $html .= '</div>';
    
    // Footer con botones
    if (!empty($state_config['buttons'])) {
        $html .= '<div class="modal-footer">';
        foreach ($state_config['buttons'] as $button) {
            $html .= '<button type="button" class="btn ' . $button['class'] . '">' . esc_html($button['text']) . '</button>';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera CSS personalizado para los modales simulados
 * 
 * @return string CSS personalizado
 */
function get_modal_simulation_css() {
    return '
    <style>
    .modal-simulation-container {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .modal-preview {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 20px;
        margin: 10px 0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .modal-state-indicator {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
        margin-bottom: 10px;
    }
    
    .state-loading { background-color: #0d6efd; color: white; }
    .state-success { background-color: #198754; color: white; }
    .state-error { background-color: #dc3545; color: white; }
    .state-warning { background-color: #ffc107; color: black; }
    .state-info { background-color: #0dcaf0; color: black; }
    </style>';
}

// Ejecución directa desde URL
if (isset($_GET['run_sim']) && $_GET['run_sim'] === 'modal') {
    $modal_type = $_GET['modal_type'] ?? 'lectura';
    $state = $_GET['state'] ?? 'loading';
    
    $result = simulate_modal_states($modal_type, $state);
    
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    
    echo get_modal_simulation_css();
    echo "<div class='wrap modal-simulation-container'>";
    echo "<h2>Simulador de Modales UI</h2>";
    echo "<span class='modal-state-indicator state-" . $state . "'>Estado: " . ucfirst($state) . "</span>";
    echo "<h3>Modal: " . ucfirst($modal_type) . "</h3>";
    
    if (isset($result['error'])) {
        echo "<div class='alert alert-danger'>" . $result['error'] . "</div>";
    } else {
        echo "<div class='modal-preview'>";
        echo $result['html'];
        echo "</div>";
        
        echo "<details><summary>Configuración del Estado</summary>";
        echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;margin-top:10px;'>";
        print_r($result['state_config']);
        echo "</pre></details>";
    }
    
    echo "</div>";
}
