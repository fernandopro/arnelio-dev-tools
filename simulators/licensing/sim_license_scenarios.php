<?php
/**
 * Simulador: Escenarios de Licencia
 * Descripción: Simula diferentes estados de licencia para testing manual y debugging
 * Módulo: licensing
 * Autor: Tarokina Pro Team
 * Versión: 1.0.0
 * Fecha: 2025-01-17
 */

// Protección de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simula diferentes escenarios de licencia
 * 
 * @param string $scenario Tipo de escenario a simular
 * @return array Resultado de la simulación
 */
function simulate_license_scenarios($scenario = 'valid') {
    $scenarios = [
        'valid' => [
            'status' => 'active',
            'expires' => time() + (30 * 24 * 3600), // 30 días
            'activations' => 1,
            'max_activations' => 5,
            'license_key' => 'TK-VALID-' . wp_generate_password(12, false),
            'product_id' => 'tarokina-pro',
            'customer_email' => 'usuario@ejemplo.com'
        ],
        'expired' => [
            'status' => 'expired',
            'expires' => time() - (5 * 24 * 3600), // 5 días atrás
            'activations' => 1,
            'max_activations' => 5,
            'license_key' => 'TK-EXPIRED-' . wp_generate_password(12, false),
            'product_id' => 'tarokina-pro',
            'customer_email' => 'usuario@ejemplo.com'
        ],
        'maxed_out' => [
            'status' => 'active',
            'expires' => time() + (30 * 24 * 3600),
            'activations' => 5,
            'max_activations' => 5,
            'license_key' => 'TK-MAXED-' . wp_generate_password(12, false),
            'product_id' => 'tarokina-pro',
            'customer_email' => 'usuario@ejemplo.com'
        ],
        'suspended' => [
            'status' => 'suspended',
            'expires' => time() + (15 * 24 * 3600),
            'activations' => 2,
            'max_activations' => 5,
            'license_key' => 'TK-SUSPENDED-' . wp_generate_password(12, false),
            'product_id' => 'tarokina-pro',
            'customer_email' => 'usuario@ejemplo.com',
            'suspension_reason' => 'Pago pendiente'
        ],
        'trial' => [
            'status' => 'trial',
            'expires' => time() + (7 * 24 * 3600), // 7 días
            'activations' => 1,
            'max_activations' => 1,
            'license_key' => 'TK-TRIAL-' . wp_generate_password(12, false),
            'product_id' => 'tarokina-trial',
            'customer_email' => 'trial@ejemplo.com',
            'trial_days_left' => 7
        ]
    ];
    
    if (!isset($scenarios[$scenario])) {
        return [
            'error' => 'Escenario no válido',
            'available_scenarios' => array_keys($scenarios)
        ];
    }
    
    $license_data = $scenarios[$scenario];
    
    // Generar transient de licencia
    $transient_key = 'lic_tarokina_' . $scenario;
    set_transient($transient_key, $license_data, 3600);
    
    // Actualizar opción de estado de licencia
    update_option('tarokina_license_status', $license_data);
    update_option('tarokina_license_scenario', $scenario);
    
    // Log del evento
    error_log("Tarokina Simulator: License scenario '{$scenario}' simulated");
    
    return [
        'scenario' => $scenario,
        'data' => $license_data,
        'transient_key' => $transient_key,
        'transient_set' => get_transient($transient_key) !== false,
        'option_updated' => get_option('tarokina_license_status') === $license_data,
        'expires_human' => human_time_diff(time(), $license_data['expires']),
        'message' => "Escenario '{$scenario}' simulado correctamente",
        'timestamp' => current_time('mysql')
    ];
}

/**
 * Limpia todos los datos de simulación de licencia
 * 
 * @return array Resultado de la limpieza
 */
function cleanup_license_simulation() {
    $scenarios = ['valid', 'expired', 'maxed_out', 'suspended', 'trial'];
    $cleaned = [];
    
    foreach ($scenarios as $scenario) {
        $transient_key = 'lic_tarokina_' . $scenario;
        if (delete_transient($transient_key)) {
            $cleaned[] = $transient_key;
        }
    }
    
    delete_option('tarokina_license_status');
    delete_option('tarokina_license_scenario');
    
    return [
        'cleaned_transients' => $cleaned,
        'options_deleted' => ['tarokina_license_status', 'tarokina_license_scenario'],
        'message' => 'Simulación de licencia limpiada correctamente'
    ];
}

/**
 * Obtiene el estado actual de la simulación
 * 
 * @return array Estado actual
 */
function get_license_simulation_status() {
    $current_scenario = get_option('tarokina_license_scenario', 'none');
    $license_data = get_option('tarokina_license_status', []);
    
    $active_transients = [];
    $scenarios = ['valid', 'expired', 'maxed_out', 'suspended', 'trial'];
    
    foreach ($scenarios as $scenario) {
        $transient_key = 'lic_tarokina_' . $scenario;
        if (get_transient($transient_key) !== false) {
            $active_transients[] = $transient_key;
        }
    }
    
    return [
        'current_scenario' => $current_scenario,
        'license_data' => $license_data,
        'active_transients' => $active_transients,
        'simulation_active' => !empty($license_data)
    ];
}

// Ejecución directa desde URL para testing rápido
if (isset($_GET['run_sim']) && $_GET['run_sim'] === 'license') {
    $action = $_GET['action'] ?? 'simulate';
    
    switch ($action) {
        case 'simulate':
            $scenario = $_GET['scenario'] ?? 'valid';
            $result = simulate_license_scenarios($scenario);
            break;
            
        case 'cleanup':
            $result = cleanup_license_simulation();
            break;
            
        case 'status':
            $result = get_license_simulation_status();
            break;
            
        default:
            $result = ['error' => 'Acción no válida'];
    }
    
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    
    echo "<div class='wrap'>";
    echo "<h2>Simulador de Licencias Tarokina</h2>";
    echo "<h3>Acción: {$action}</h3>";
    echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>";
    print_r($result);
    echo "</pre>";
    echo "</div>";
}
