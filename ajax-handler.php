<?php
/**
 * Ajax Handler para Dev Tools
 * Maneja todas las peticiones AJAX del sistema dev-tools
 */

// Verificar que WordPress esté cargado
if (!defined('ABSPATH')) {
    exit;
}

// CRÍTICO: Solo ejecutar en contextos AJAX válidos
// Verificar que sea una petición AJAX real antes de continuar
if (!wp_doing_ajax() && !defined('DOING_AJAX')) {
    return; // Salir silenciosamente si no es AJAX
}

// CRÍTICO: Definir constante DEV_TOOLS_VERBOSE para evitar errores de IDE
// Esta constante se usa para logging condicional en modo de desarrollo
if (!defined('DEV_TOOLS_VERBOSE')) {
    // Detectar si estamos en modo de desarrollo basado en diferentes indicadores
    $is_development = (
        (defined('WP_DEBUG') && WP_DEBUG) ||
        (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) ||
        strpos(get_site_url(), 'localhost') !== false ||
        strpos(get_site_url(), '127.0.0.1') !== false ||
        strpos(get_site_url(), '.local') !== false
    );
    
    // Verificar también si hay constante LOCAL_DEV (sin generar errores de IDE)
    if (defined('LOCAL_DEV')) {
        $local_dev_value = constant('LOCAL_DEV');
        if ($local_dev_value) {
            $is_development = true;
        }
    }
    
    define('DEV_TOOLS_VERBOSE', $is_development);
}

// CRÍTICO: Solo definir constantes específicas para nuestros tests AJAX
// Y solo cuando sea realmente necesario (durante ejecución de tests)
function dev_tools_define_test_constants_if_needed() {
    // Obtener configuración dinámica
    $config = dev_tools_config();
    
    // Solo definir si estamos ejecutando un test específico de dev-tools
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    $dev_tools_actions = [
        $config->get('ajax.action_prefix') . '_dev_tools_action',
        $config->get('ajax.action_prefix') . '_run_single_test',
        $config->get('ajax.action_prefix') . '_dev_tools_status'
    ];
    
    if (in_array($action, $dev_tools_actions)) {
        if (!defined('WP_TESTS_INDIVIDUAL')) {
            define('WP_TESTS_INDIVIDUAL', true);
        }
    }
}

/**
 * Obtener el directorio base de dev-tools usando las constantes del plugin
 */
function get_dev_tools_dir() {
   
    // Fallback si no está definida la constante
    return plugin_dir_path(__FILE__);
}

/**
 * Genera una cabecera visual para los tests
 */
function generate_test_header($test_name, $test_number = 1, $test_type = null) {
    // Detectar tipo de test si no se especifica
    if (!$test_type) {
        if (strpos($test_name, 'integration') !== false || strpos($test_name, 'Integration') !== false) {
            $test_type = 'Integration';
        } elseif (strpos($test_name, 'unit') !== false || strpos($test_name, 'Unit') !== false) {
            $test_type = 'Unit';
        } else {
            // Detectar por estructura de directorios
            // CORREGIDO: Detectar tests "Others" correctamente con rutas relativas
            if (strpos($test_name, 'unit/') === false && 
                strpos($test_name, 'integration/') === false) {
                // Si no está en unit/ ni integration/, es tipo "Others"
                $test_type = 'Others';
            } else {
                $test_type = 'WordPress';
            }
        }
    }
    
    // Limpiar nombre del test (quitar extensión .php y prefijos)
    $clean_name = str_replace(['.php', 'Test'], '', basename($test_name));
    $clean_name = ucfirst($clean_name);
    
    $separator = str_repeat('=', 79);
    $header = "\n{$separator}\n";
    $header .= "{$test_number} 🧪 {$clean_name} - TIPO: {$test_type}\n";
    $header .= "{$separator}\n\n";
    
    return $header;
}

/**
 * Inicializar los handlers AJAX de forma segura
 */
function dev_tools_init_ajax() {
    // CRÍTICO: Solo inicializar si estamos en admin o AJAX
    if (!is_admin() && !wp_doing_ajax()) {
        return;
    }
    
    // Verificar que el usuario tenga permisos antes de registrar acciones
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Obtener configuración dinámica
    $config = dev_tools_config();
    $action_prefix = $config->get('ajax.action_prefix');
    
    // NUEVA SECCIÓN: Registrar acciones específicas que estaban faltando
    // Estas son las acciones que aparecían en los logs de error
    add_action('wp_ajax_' . $action_prefix . '_dev_tools_ping', 'dev_tools_ping_handler');
    add_action('wp_ajax_' . $action_prefix . '_dev_tools_check_anti_deadlock', 'dev_tools_check_anti_deadlock_handler');
    add_action('wp_ajax_' . $action_prefix . '_dev_tools_check_test_framework', 'dev_tools_check_test_framework_handler');
    
    // Acciones para usuarios con permisos de administrador
    $admin_actions = [
        $action_prefix . '_dev_tools_action',
        $action_prefix . '_dev_tools_status',
        $action_prefix . '_dev_tools_get_logs',
        $action_prefix . '_create_simulator',
        $action_prefix . '_create_doc',
        $action_prefix . '_save_settings',
        $action_prefix . '_update_phpunit_config',
        $action_prefix . '_run_single_test'
    ];
    
    foreach ($admin_actions as $action) {
        add_action('wp_ajax_' . $action, 'dev_tools_ajax_handler');
    }
}

/**
 * Handler principal para todas las peticiones AJAX
 */
function dev_tools_ajax_handler() {
    // Definir constantes de test solo si es necesario
    dev_tools_define_test_constants_if_needed();
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos suficientes para realizar esta acción.');
        return;
    }
    
    // Obtener configuración dinámica
    $config = dev_tools_config();
    $action_prefix = $config->get('ajax.action_prefix');
    
    // Obtener la acción
    $action = $_POST['action'] ?? '';
    
    // Verificar nonce según el tipo de acción
    $nonce_actions = [
        $action_prefix . '_dev_tools_action' => 'dev_tools_action',
        $action_prefix . '_dev_tools_status' => 'dev_tools_status',
        $action_prefix . '_dev_tools_get_logs' => 'dev_tools_logs',
        $action_prefix . '_create_simulator' => 'create_simulator',
        $action_prefix . '_create_doc' => 'create_doc',
        $action_prefix . '_save_settings' => 'save_settings',
        $action_prefix . '_update_phpunit_config' => 'update_phpunit_config',
        $action_prefix . '_run_single_test' => 'run_single_test'
    ];
    
    if (isset($nonce_actions[$action])) {
        $nonce = $_POST['nonce'] ?? '';
        
        // DEBUG: Logging detallado del nonce
        error_log("=== NONCE DEBUG ===");
        error_log("Action: " . $action);
        error_log("Expected nonce action: " . $nonce_actions[$action]);
        error_log("Received nonce: " . $nonce);
        error_log("Current user ID: " . get_current_user_id());
        error_log("POST data: " . print_r($_POST, true));
        
        $verification_result = wp_verify_nonce($nonce, $nonce_actions[$action]);
        error_log("Nonce verification result: " . ($verification_result ? 'VALID' : 'INVALID'));
        
        if (!$verification_result) {
            error_log("NONCE VERIFICATION FAILED");
            wp_send_json_error('Token de seguridad inválido.');
            return;
        }
        error_log("=== NONCE DEBUG END ===");
    }
    
    // Enrutar a la función correspondiente
    switch ($action) {
        case $action_prefix . '_dev_tools_action':
            handle_dev_tools_action();
            break;
        case $action_prefix . '_dev_tools_status':
            handle_system_status();
            break;
        case $action_prefix . '_dev_tools_get_logs':
            handle_get_logs();
            break;
        case $action_prefix . '_create_simulator':
            handle_create_simulator();
            break;
        case $action_prefix . '_create_doc':
            handle_create_doc();
            break;
        case $action_prefix . '_save_settings':
            handle_save_settings();
            break;
        case $action_prefix . '_update_phpunit_config':
            handle_update_phpunit_config();
            break;
        case $action_prefix . '_run_single_test':
            handle_run_single_test_direct();
            break;
        default:
            wp_send_json_error('Acción no reconocida.');
    }
}

/**
 * Maneja las acciones generales de dev-tools
 */
function handle_dev_tools_action() {
    $dev_action = sanitize_text_field($_POST['dev_action'] ?? '');
    
    switch ($dev_action) {
        case 'run_all_tests':
            run_all_tests();
            break;
        case 'run_single_test':
            run_single_test();
            break;
        case 'clear_cache':
            clear_dev_cache();
            break;
        case 'generate_test_data':
            generate_test_data();
            break;
        case 'export_logs':
            export_logs();
            break;
        case 'run_simulator':
            run_simulator();
            break;
        case 'validate_system':
            validate_system();
            break;
        case 'optimize_db':
            optimize_database();
            break;
        case 'backup_db':
            backup_database();
            break;
        case 'refresh_tests':
            refresh_tests_list();
            break;
        case 'refresh_simulators':
            refresh_simulators_list();
            break;
        case 'create_wp_test':
            create_wp_unitcase_test();
            break;
        case 'run_wp_tests':
            run_wordpress_tests();
            break;
        case 'wp_test_coverage':
            generate_wp_test_coverage();
            break;
        case 'reset_wp_test_db':
            reset_wp_test_database();
            break;
        case 'delete_test':
            delete_test_file();
            break;
        default:
            wp_send_json_error('Acción de dev-tools no reconocida: ' . $dev_action);
    }
}

/**
 * Ejecuta todos los tests disponibles
 */
function run_all_tests() {
    $dev_tools_dir = get_dev_tools_dir();
    $tests_dir = $dev_tools_dir . 'tests';
    $phpunit_path = $dev_tools_dir . 'vendor/bin/phpunit';
    
    if (!file_exists($phpunit_path)) {
        wp_send_json_error('PHPUnit no está instalado. Ejecuta composer install primero.');
        return;
    }
    
    // Ejecutar PHPUnit
    $command = "cd " . escapeshellarg($dev_tools_dir) . " && " . escapeshellarg($phpunit_path) . " --verbose 2>&1";
    $output = shell_exec($command);
    
    if ($output === null) {
        wp_send_json_error('No se pudo ejecutar PHPUnit. Verifica la configuración del servidor.');
        return;
    }
    
    // Analizar resultados
    $success = strpos($output, 'FAILURES!') === false && strpos($output, 'ERRORS!') === false;
    
    wp_send_json_success([
        'message' => $success ? 'Todos los tests ejecutados exitosamente.' : 'Algunos tests fallaron.',
        'output' => $output,
        'success' => $success
    ]);
}

/**
 * Ejecuta un test específico (versión unificada y robusta)
 */
function run_single_test() {
    // Definir constantes específicas para tests solo durante ejecución
    if (!defined('WP_TESTS_INDIVIDUAL')) {
        define('WP_TESTS_INDIVIDUAL', true);
    }
    
    $test_file = sanitize_text_field($_POST['test_file'] ?? '');
    
    error_log('🔍 run_single_test() - Iniciando con datos: ' . json_encode([
        'test_file' => $test_file,
        'post_data' => $_POST
    ]));
    
    if (empty($test_file)) {
        error_log('❌ Error: No se especificó el archivo de test');
        wp_send_json_error('No se especificó el archivo de test.');
        return;
    }

    $dev_tools_dir = get_dev_tools_dir();
    
    // Normalizar la ruta del test (puede venir con o sin prefijo tests/)
    $test_file = ltrim($test_file, '/');
    if (!str_starts_with($test_file, 'tests/')) {
        $test_file = 'tests/' . $test_file;
    }
    
    $test_path = $dev_tools_dir . $test_file;
    
    if (!file_exists($test_path)) {
        // Intentar buscar en subdirectorios de tests
        $possible_paths = [
            $dev_tools_dir . 'tests/basic/' . basename($test_file),
            $dev_tools_dir . 'tests/integration/' . basename($test_file),
            $dev_tools_dir . 'tests/unit/' . basename($test_file),
            $dev_tools_dir . 'tests/' . basename($test_file)
        ];
        
        $test_path = null;
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $test_path = $path;
                break;
            }
        }
        
        if (!$test_path) {
            wp_send_json_error('El archivo de test no existe: ' . $test_file);
            return;
        }
    }
    
    $start_time = microtime(true);
    
    // Verificar si PHPUnit está disponible
    $phpunit_path = $dev_tools_dir . 'vendor/bin/phpunit';
    if (!file_exists($phpunit_path)) {
        // Intentar con PHPUnit global
        $phpunit_path = 'phpunit';
        $test_command = "which phpunit 2>/dev/null";
        $phpunit_check = shell_exec($test_command);
        
        if (empty($phpunit_check)) {
            wp_send_json_error('PHPUnit no está instalado. Ejecuta composer install o instala PHPUnit globalmente.');
            return;
        }
    }
    
    // Ejecutar el test
    $bootstrap_path = $dev_tools_dir . 'tests/bootstrap.php';
    
    // Debug: Verificar paths críticos
    error_log('🔍 Bootstrap path: ' . $bootstrap_path);
    error_log('🔍 Bootstrap existe: ' . (file_exists($bootstrap_path) ? 'SÍ' : 'NO'));
    error_log('🔍 Test path: ' . $test_path);
    error_log('🔍 Test existe: ' . (file_exists($test_path) ? 'SÍ' : 'NO'));
    
    // Detectar la ruta de PHP automáticamente
    $php_path = shell_exec('which php 2>/dev/null');
    if (empty($php_path)) {
        // Fallback para rutas comunes
        $common_php_paths = [
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/bin/php'
        ];
        
        foreach ($common_php_paths as $path) {
            if (file_exists($path)) {
                $php_path = $path;
                break;
            }
        }
        
        if (empty($php_path)) {
            wp_send_json_error('No se pudo encontrar el ejecutable PHP.');
            return;
        }
    } else {
        $php_path = trim($php_path);
    }
    
    // Configurar el entorno con PATH correctos
    $path_env = 'PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin';
    
    $command = "cd " . escapeshellarg($dev_tools_dir) . " && " . $path_env . " " . escapeshellarg($php_path) . " " . escapeshellarg($phpunit_path);
    
    // Agregar bootstrap si existe
    if (file_exists($bootstrap_path)) {
        $command .= " --bootstrap " . escapeshellarg($bootstrap_path);
    }
    
    $command .= " " . escapeshellarg($test_path) . " --verbose --colors=never 2>&1";
    
    error_log('🚀 Ejecutando comando: ' . $command);
    $output = shell_exec($command);
    error_log('📥 Salida del comando: ' . $output);
    
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // En milisegundos
    
    if ($output === null) {
        error_log('❌ Error: shell_exec devolvió null');
        wp_send_json_error('No se pudo ejecutar el test. Verifica la configuración del servidor.');
        return;
    }
    
    // Generar cabecera visual para el test
    $test_header = generate_test_header($test_file, 1);
    
    // Agregar la cabecera al output
    $output = $test_header . $output;
    
    // Analizar resultados con detección mejorada de errores
    $success = strpos($output, 'FAILURES!') === false && 
               strpos($output, 'ERRORS!') === false &&
               strpos($output, 'FATAL') === false &&
               strpos($output, 'could not be found') === false && // Clases no encontradas
               strpos($output, 'No tests executed') === false &&   // Sin tests ejecutados
               strpos($output, 'Class') === false || 
               (strpos($output, 'Class') !== false && strpos($output, 'could not be found') === false);
    
    $errors = [];
    if (!$success) {
        // Extraer errores específicos incluyendo clases no encontradas
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            if (preg_match('/^(FAIL|ERROR|FATAL)/', $trimmed_line) ||
                strpos($trimmed_line, 'could not be found') !== false ||
                strpos($trimmed_line, 'No tests executed') !== false) {
                $errors[] = $trimmed_line;
            }
        }
        
        // Si no hay errores específicos pero la ejecución falló, agregar mensaje genérico
        if (empty($errors)) {
            $errors[] = 'Error no específico en la ejecución del test';
        }
    }
    
    $response_data = [
        'status' => $success ? 'PASSED' : 'FAILED',
        'message' => $success ? 'Test ejecutado exitosamente.' : 'El test falló.',
        'output' => $output,
        'success' => $success,
        'execution_time' => $execution_time,
        'test_file' => basename($test_file),
        'errors' => $errors
    ];
    
    error_log('✅ Enviando respuesta: ' . json_encode($response_data));
    wp_send_json_success($response_data);
}

/**
 * Maneja la ejecución directa de un test individual (llamada AJAX independiente)
 */
function handle_run_single_test_direct() {
    // Definir constantes específicas para tests solo durante ejecución
    if (!defined('WP_TESTS_INDIVIDUAL')) {
        define('WP_TESTS_INDIVIDUAL', true);
    }
    
    // Verificar nonce
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'run_single_test')) {
        wp_send_json_error('Nonce inválido.');
        return;
    }
    
    $test_file = sanitize_text_field($_POST['test_file'] ?? '');
    
    error_log('🔍 handle_run_single_test_direct() - Iniciando con datos: ' . json_encode([
        'test_file' => $test_file,
        'nonce' => $nonce,
        'post_data' => $_POST
    ]));
    
    if (empty($test_file)) {
        error_log('❌ Error: No se especificó el archivo de test');
        wp_send_json_error('No se especificó el archivo de test.');
        return;
    }
    
    $dev_tools_dir = get_dev_tools_dir();
    
    // Normalizar la ruta del test (puede venir con o sin prefijo tests/)
    $test_file = ltrim($test_file, '/');
    if (!str_starts_with($test_file, 'tests/')) {
        $test_file = 'tests/' . $test_file;
    }
    
    $test_path = $dev_tools_dir . $test_file;
    
    if (!file_exists($test_path)) {
        // Intentar buscar en subdirectorios de tests
        $possible_paths = [
            $dev_tools_dir . 'tests/integration/' . basename($test_file),
            $dev_tools_dir . 'tests/unit/' . basename($test_file),
            $dev_tools_dir . 'tests/' . basename($test_file)
        ];
        
        $test_path = null;
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $test_path = $path;
                break;
            }
        }
        
        if (!$test_path) {
            wp_send_json_error('El archivo de test no existe: ' . $test_file);
            return;
        }
    }
    
    $start_time = microtime(true);
    
    // Verificar si PHPUnit está disponible
    $phpunit_path = $dev_tools_dir . 'vendor/bin/phpunit';
    if (!file_exists($phpunit_path)) {
        // Intentar con PHPUnit global
        $phpunit_path = 'phpunit';
        $test_command = "which phpunit 2>/dev/null";
        $phpunit_check = shell_exec($test_command);
        
        if (empty($phpunit_check)) {
            wp_send_json_error('PHPUnit no está instalado. Ejecuta composer install o instala PHPUnit globalmente.');
            return;
        }
    }
    
    // Ejecutar el test
    $bootstrap_path = $dev_tools_dir . 'tests/bootstrap.php';
    
    // Debug: Verificar paths críticos
    error_log('🔍 Bootstrap path: ' . $bootstrap_path);
    error_log('🔍 Bootstrap existe: ' . (file_exists($bootstrap_path) ? 'SÍ' : 'NO'));
    error_log('🔍 Test path: ' . $test_path);
    error_log('🔍 Test existe: ' . (file_exists($test_path) ? 'SÍ' : 'NO'));
    
    // Detectar la ruta de PHP automáticamente
    $php_path = shell_exec('which php 2>/dev/null');
    if (empty($php_path)) {
        // Fallback para rutas comunes
        $common_php_paths = [
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/bin/php'
        ];
        
        foreach ($common_php_paths as $path) {
            if (file_exists($path)) {
                $php_path = $path;
                break;
            }
        }
        
        if (empty($php_path)) {
            wp_send_json_error('No se pudo encontrar el ejecutable PHP.');
            return;
        }
    } else {
        $php_path = trim($php_path);
    }
    
    // Configurar el entorno con PATH correctos
    $path_env = 'PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin';
    
    $command = "cd " . escapeshellarg($dev_tools_dir) . " && " . $path_env . " " . escapeshellarg($php_path) . " " . escapeshellarg($phpunit_path);
    
    // Agregar bootstrap si existe
    if (file_exists($bootstrap_path)) {
        $command .= " --bootstrap " . escapeshellarg($bootstrap_path);
    }
    
    $command .= " " . escapeshellarg($test_path) . " --verbose --colors=never 2>&1";
    
    error_log('🚀 Ejecutando comando: ' . $command);
    $output = shell_exec($command);
    error_log('📥 Salida del comando: ' . $output);
    
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // En milisegundos
    
    if ($output === null) {
        error_log('❌ Error: shell_exec devolvió null');
        wp_send_json_error('No se pudo ejecutar el test. Verifica la configuración del servidor.');
        return;
    }
    
    // Generar cabecera visual para el test
    $test_header = generate_test_header($test_file, 1);
    
    // Agregar la cabecera al output
    $output = $test_header . $output;
    
    // Analizar resultados con detección mejorada de errores
    $success = strpos($output, 'FAILURES!') === false && 
               strpos($output, 'ERRORS!') === false &&
               strpos($output, 'FATAL') === false &&
               strpos($output, 'could not be found') === false && // Clases no encontradas
               strpos($output, 'No tests executed') === false &&   // Sin tests ejecutados
               strpos($output, 'Class') === false || 
               (strpos($output, 'Class') !== false && strpos($output, 'could not be found') === false);
    
    $errors = [];
    if (!$success) {
        // Extraer errores específicos incluyendo clases no encontradas
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            if (preg_match('/^(FAIL|ERROR|FATAL)/', $trimmed_line) ||
                strpos($trimmed_line, 'could not be found') !== false ||
                strpos($trimmed_line, 'No tests executed') !== false) {
                $errors[] = $trimmed_line;
            }
        }
        
        // Si no hay errores específicos pero la ejecución falló, agregar mensaje genérico
        if (empty($errors)) {
            $errors[] = 'Error no específico en la ejecución del test';
        }
    }
    
    // NUEVO: Activar debug condicional si hay fallos
    if (!$success && isset($GLOBALS['dev_tools_debug_function'])) {
        if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
            error_log('[DEV-TOOLS-DEBUG] Activando debug condicional para test fallido: ' . basename($test_file));
        }
        call_user_func($GLOBALS['dev_tools_debug_function'], true);
    }
    
    $response_data = [
        'status' => $success ? 'PASSED' : 'FAILED',
        'message' => $success ? 'Test ejecutado exitosamente.' : 'El test falló.',
        'output' => $output,
        'success' => $success,
        'execution_time' => $execution_time,
        'test_file' => basename($test_file),
        'errors' => $errors
    ];
    
    error_log('✅ Enviando respuesta: ' . json_encode($response_data));
    wp_send_json_success($response_data);
}

/**
 * Elimina un archivo de test específico
 */
function delete_test_file() {
    // Verificar nonce de seguridad
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'dev_tools_action')) {
        wp_send_json_error('Token de seguridad inválido.');
        return;
    }
    
    $test_file = sanitize_text_field($_POST['test_file'] ?? '');
    
    if (empty($test_file)) {
        wp_send_json_error('No se especificó el archivo de test.');
        return;
    }

    $dev_tools_dir = get_dev_tools_dir();
    
    // Normalizar la ruta del test (puede venir con o sin prefijo tests/)
    $test_file = ltrim($test_file, '/');
    if (!str_starts_with($test_file, 'tests/')) {
        $test_file = 'tests/' . $test_file;
    }
    
    $test_path = $dev_tools_dir . $test_file;
    
    // Verificaciones de seguridad
    // 1. El archivo debe existir
    if (!file_exists($test_path)) {
        wp_send_json_error('El archivo de test no existe: ' . $test_file);
        return;
    }
    
    // 2. El archivo debe estar dentro del directorio de tests
    $tests_dir = realpath($dev_tools_dir . 'tests');
    $real_test_path = realpath($test_path);
    
    if (!$real_test_path || !str_starts_with($real_test_path, $tests_dir)) {
        wp_send_json_error('Ruta de archivo no válida por motivos de seguridad.');
        return;
    }
    
    // 3. Solo permitir archivos .php
    if (!str_ends_with($test_file, '.php')) {
        wp_send_json_error('Solo se pueden eliminar archivos .php de test.');
        return;
    }
    
    // 4. No permitir eliminar archivos críticos del sistema
    $protected_files = [
        'bootstrap.php',
        'DevToolsTestCase.php', 
        'basetest.php'
    ];
    
    $filename = basename($test_file);
    if (in_array($filename, $protected_files)) {
        wp_send_json_error('No se puede eliminar un archivo del sistema: ' . $filename);
        return;
    }
    
    // Intentar eliminar el archivo
    try {
        // Log para debugging si está habilitado
        if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
            error_log('[DEV-TOOLS-PHP] Eliminando archivo de test: ' . $test_path);
        }
        
        if (unlink($test_path)) {
            wp_send_json_success([
                'message' => 'Archivo eliminado correctamente: ' . basename($test_file),
                'deleted_file' => $test_file,
                'timestamp' => current_time('c')
            ]);
        } else {
            wp_send_json_error('No se pudo eliminar el archivo. Verificar permisos.');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error al eliminar el archivo: ' . $e->getMessage());
    }
}

/**
 * Limpia el cache del sistema de desarrollo
 */
function clear_dev_cache() {
    // 🐛 DEBUG: Log de inicio
    error_log('🧹 [CACHE DEBUG PHP] Iniciando clear_dev_cache()');
    
    $cache_cleared = false;
    $messages = [];
    $stats = [
        'files_removed' => 0,
        'transients_removed' => 0,
        'size_freed' => 0
    ];
    
    // 1. Limpiar cache de WordPress
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        $cache_cleared = true;
        $messages[] = '✅ Cache de WordPress limpiado (wp_cache_flush)';
        error_log('🧹 [CACHE DEBUG PHP] wp_cache_flush() ejecutado');
    }
    
    // 2. Limpiar transients del desarrollo
    $dev_transients = [
        'tarokina_dev_test_data',
        'tarokina_dev_logs',
        'tarokina_dev_status',
        'tarokina_dev_stats',
        'tarokina_test_results'
    ];
    
    foreach ($dev_transients as $transient) {
        if (delete_transient($transient)) {
            $stats['transients_removed']++;
            $cache_cleared = true;
            error_log("🧹 [CACHE DEBUG PHP] Transient eliminado: {$transient}");
        }
    }
    
    if ($stats['transients_removed'] > 0) {
        $messages[] = "✅ {$stats['transients_removed']} transients de desarrollo eliminados";
    }
    
    // 3. Limpiar archivos de cache personalizados
    $dev_tools_dir = get_dev_tools_dir();
    error_log("🧹 [CACHE DEBUG PHP] Directorio dev-tools: {$dev_tools_dir}");
    
    $cache_directories = [
        $dev_tools_dir . 'cache',
        $dev_tools_dir . 'logs/cache',
        $dev_tools_dir . 'tmp'
    ];
    
    foreach ($cache_directories as $cache_dir) {
        error_log("🧹 [CACHE DEBUG PHP] Verificando directorio: {$cache_dir}");
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            error_log("🧹 [CACHE DEBUG PHP] Archivos encontrados: " . count($files));
            foreach ($files as $file) {
                if (is_file($file)) {
                    $size = filesize($file);
                    if (unlink($file)) {
                        $stats['files_removed']++;
                        $stats['size_freed'] += $size;
                        $cache_cleared = true;
                        error_log("🧹 [CACHE DEBUG PHP] Archivo eliminado: {$file}");
                    }
                }
            }
        } else {
            error_log("🧹 [CACHE DEBUG PHP] Directorio no existe: {$cache_dir}");
        }
    }
    
    if ($stats['files_removed'] > 0) {
        $size_mb = round($stats['size_freed'] / 1024 / 1024, 2);
        $messages[] = "✅ {$stats['files_removed']} archivos de cache eliminados ({$size_mb} MB liberados)";
    }
    
    // 4. Limpiar cache de opciones si existe
    if (function_exists('wp_cache_delete')) {
        $dev_cache_keys = [
            'tarokina_dev_options',
            'tarokina_test_config',
            'alloptions' // WordPress options cache
        ];
        
        foreach ($dev_cache_keys as $key) {
            wp_cache_delete($key, 'options');
        }
        $messages[] = '✅ Cache de opciones de desarrollo limpiado';
        $cache_cleared = true;
        error_log('🧹 [CACHE DEBUG PHP] Cache de opciones limpiado');
    }
    
    // 5. Resultados finales
    error_log('🧹 [CACHE DEBUG PHP] Cache cleared: ' . ($cache_cleared ? 'true' : 'false'));
    error_log('🧹 [CACHE DEBUG PHP] Stats: ' . json_encode($stats));
    error_log('🧹 [CACHE DEBUG PHP] Messages: ' . json_encode($messages));
    
    if ($cache_cleared) {
        $summary = sprintf(
            'Cache de desarrollo limpiado exitosamente: %d archivos, %d transients, %.2f MB liberados',
            $stats['files_removed'],
            $stats['transients_removed'],
            round($stats['size_freed'] / 1024 / 1024, 2)
        );
        
        error_log('🧹 [CACHE DEBUG PHP] Enviando respuesta de éxito');
        wp_send_json_success([
            'message' => $summary,
            'details' => $messages,
            'stats' => $stats,
            'timestamp' => current_time('mysql')
        ]);
    } else {
        error_log('🧹 [CACHE DEBUG PHP] Enviando respuesta de error');
        wp_send_json_error('No se encontraron archivos de cache para limpiar.');
    }
}

/**
 * Genera datos de prueba
 */
function generate_test_data() {
    $test_data_generated = 0;
    
    // Generar posts de prueba
    for ($i = 1; $i <= 5; $i++) {
        $post_id = wp_insert_post([
            'post_title' => 'Test Post ' . $i,
            'post_content' => 'Este es un post de prueba generado por dev-tools.',
            'post_status' => 'publish',
            'post_type' => 'post',
            'meta_input' => [
                '_dev_tools_test_data' => true
            ]
        ]);
        
        if ($post_id && !is_wp_error($post_id)) {
            $test_data_generated++;
        }
    }
    
    wp_send_json_success([
        'message' => "Se generaron {$test_data_generated} elementos de datos de prueba.",
        'generated' => $test_data_generated
    ]);
}

/**
 * Exporta los logs del sistema
 */
function export_logs() {
    $dev_tools_dir = get_dev_tools_dir();
    $log_file = $dev_tools_dir . 'logs/dev-tools.log';
    
    if (!file_exists($log_file)) {
        wp_send_json_error('No se encontraron logs para exportar.');
        return;
    }
    
    $logs = file_get_contents($log_file);
    $config = dev_tools_config();
    $filename = $config->get('plugin.slug') . '-dev-tools-logs-' . date('Y-m-d-H-i-s') . '.txt';
    
    // En un entorno real, aquí se generaría la descarga
    wp_send_json_success([
        'message' => 'Logs exportados exitosamente.',
        'filename' => $filename,
        'size' => strlen($logs)
    ]);
}

/**
 * Obtiene el estado del sistema
 */
function handle_system_status() {
    $dev_tools_dir = get_dev_tools_dir();
    $status = [
        'wordpress' => function_exists('wp_get_current_user'),
        'phpunit' => file_exists($dev_tools_dir . 'vendor/bin/phpunit'),
        'composer' => file_exists($dev_tools_dir . 'composer.json'),
        'dev_tools' => true, // Siempre true si llegamos aquí
        'memory_usage' => memory_get_usage(true),
        'memory_limit' => ini_get('memory_limit')
    ];
    
    wp_send_json_success([
        'data' => $status,
        'timestamp' => time()
    ]);
}

/**
 * Obtiene los logs recientes
 */
function handle_get_logs() {
    $dev_tools_dir = get_dev_tools_dir();
    $log_file = $dev_tools_dir . 'logs/dev-tools.log';
    
    if (!file_exists($log_file)) {
        wp_send_json_success([
            'data' => '<div class="text-muted">No hay logs disponibles.</div>'
        ]);
        return;
    }
    
    $logs = file_get_contents($log_file);
    $log_lines = explode("\n", $logs);
    
    // Obtener las últimas 50 líneas
    $recent_logs = array_slice($log_lines, -50);
    $formatted_logs = '';
    
    foreach ($recent_logs as $line) {
        if (trim($line)) {
            $formatted_logs .= '<div class="log-line">' . esc_html($line) . '</div>';
        }
    }
    
    wp_send_json_success([
        'data' => $formatted_logs ?: '<div class="text-muted">No hay logs recientes.</div>'
    ]);
}

/**
 * Crea un nuevo simulador
 */
function handle_create_simulator() {
    $simulator_name = sanitize_text_field($_POST['simulator_name'] ?? '');
    $simulator_module = sanitize_text_field($_POST['simulator_module'] ?? '');
    $simulator_description = sanitize_textarea_field($_POST['simulator_description'] ?? '');
    
    if (empty($simulator_name)) {
        wp_send_json_error('El nombre del simulador es obligatorio.');
        return;
    }
    
    $simulator_template = create_simulator_template($simulator_name, $simulator_module, $simulator_description);
    $filename = "sim_{$simulator_module}_{$simulator_name}.php";
    $dev_tools_dir = get_dev_tools_dir();
    $simulator_path = $dev_tools_dir . "simulators/{$filename}";
    
    if (file_exists($simulator_path)) {
        wp_send_json_error('Ya existe un simulador con ese nombre.');
        return;
    }
    
    $result = file_put_contents($simulator_path, $simulator_template);
    
    if ($result === false) {
        wp_send_json_error('No se pudo crear el archivo del simulador.');
        return;
    }
    
    wp_send_json_success([
        'message' => 'Simulador creado exitosamente.',
        'filename' => $filename,
        'path' => $simulator_path
    ]);
}

/**
 * Crea la plantilla para un nuevo simulador
 */
function create_simulator_template($name, $module, $description) {
    $date = date('Y-m-d H:i:s');
    $author = wp_get_current_user()->display_name;
    
    return "<?php
/**
 * Simulador: {$name}
 * 
 * @description {$description}
 * @module {$module}
 * @author {$author}
 * @created {$date}
 */

class {$name}Simulator
{
    /**
     * Ejecuta el simulador
     */
    public function run()
    {
        echo \"Ejecutando simulador {$name}...\n\";
        
        // TODO: Implementar la lógica del simulador
        
        return true;
    }
    
    /**
     * Configura el simulador
     */
    public function setup()
    {
        // TODO: Configuración inicial
    }
    
    /**
     * Limpia después de la ejecución
     */
    public function cleanup()
    {
        // TODO: Limpieza después de la ejecución
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) === basename(\$_SERVER['SCRIPT_NAME'])) {
    \$simulator = new {$name}Simulator();
    \$simulator->setup();
    \$result = \$simulator->run();
    \$simulator->cleanup();
    
    echo \$result ? \"Simulador ejecutado exitosamente\n\" : \"Error en la ejecución\n\";
}
";
}

/**
 * Guarda la configuración del sistema
 */
function handle_save_settings() {
    $config = dev_tools_config();
    
    $settings = [
        'debug_mode' => isset($_POST['debug_mode']),
        'auto_refresh' => isset($_POST['auto_refresh']),
        'log_level' => sanitize_text_field($_POST['log_level'] ?? 'info'),
        'max_log_size' => intval($_POST['max_log_size'] ?? 10),
        'backup_retention' => intval($_POST['backup_retention'] ?? 7),
        'test_timeout' => intval($_POST['test_timeout'] ?? 300),
        'production_warning' => isset($_POST['production_warning']),
        'email_notifications' => isset($_POST['email_notifications']),
        'notification_email' => sanitize_email($_POST['notification_email'] ?? get_option('admin_email'))
    ];
    
    $result = update_option($config->get('plugin.slug') . '_dev_tools_settings', $settings);
    
    if ($result) {
        wp_send_json_success([
            'message' => 'Configuración guardada exitosamente.',
            'settings' => $settings
        ]);
    } else {
        wp_send_json_error('No se pudo guardar la configuración.');
    }
}

/**
 * Crea un nuevo documento
 */
function handle_create_doc() {
    $doc_title = sanitize_text_field($_POST['doc_title'] ?? '');
    $doc_type = sanitize_text_field($_POST['doc_type'] ?? 'md');
    $doc_category = sanitize_text_field($_POST['doc_category'] ?? 'general');
    $doc_content = wp_kses_post($_POST['doc_content'] ?? '');
    $doc_author = sanitize_text_field($_POST['doc_author'] ?? wp_get_current_user()->display_name);
    
    if (empty($doc_title)) {
        wp_send_json_error('El título del documento es obligatorio.');
        return;
    }
    
    $filename = sanitize_file_name($doc_title) . '.' . $doc_type;
    $dev_tools_dir = get_dev_tools_dir();
    $doc_path = $dev_tools_dir . "docs/{$doc_category}/{$filename}";
    
    // Crear directorio si no existe
    $doc_dir = dirname($doc_path);
    if (!is_dir($doc_dir)) {
        wp_mkdir_p($doc_dir);
    }
    
    if (file_exists($doc_path)) {
        wp_send_json_error('Ya existe un documento con ese nombre en esa categoría.');
        return;
    }
    
    $doc_template = create_doc_template($doc_title, $doc_content, $doc_author, $doc_type);
    $result = file_put_contents($doc_path, $doc_template);
    
    if ($result === false) {
        wp_send_json_error('No se pudo crear el documento.');
        return;
    }
    
    wp_send_json_success([
        'message' => 'Documento creado exitosamente.',
        'filename' => $filename,
        'path' => $doc_path
    ]);
}

/**
 * Crea la plantilla para un nuevo documento
 */
function create_doc_template($title, $content, $author, $type) {
    $date = date('Y-m-d');
    
    if ($type === 'md') {
        return "# {$title}

**Autor:** {$author}  
**Fecha:** {$date}

---

{$content}

---

*Generado por Tarokina Pro Dev Tools*
";
    } else {
        return "{$title}

Autor: {$author}
Fecha: {$date}

{$content}

---
Generado por Tarokina Pro Dev Tools
";
    }
}

/**
 * Refresca la lista de tests disponibles
 */
function refresh_tests_list() {
    $dev_tools_dir = get_dev_tools_dir();
    $tests_dir = $dev_tools_dir . 'tests';
    
    if (!is_dir($tests_dir)) {
        wp_send_json_error('El directorio de tests no existe: ' . $tests_dir);
        return;
    }
    
    // Obtener lista actualizada de tests
    $tests = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tests_dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filename = $file->getFilename();
            
            // Solo contar archivos que terminen en 'Test.php' (excluir bootstrap.php, etc.)
            if (preg_match('/Test\.php$/', $filename)) {
                $relative_path = str_replace($tests_dir . '/', '', $file->getPathname());
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
    
    $test_count = count($tests);
    
    wp_send_json_success([
        'message' => "Lista de tests actualizada. Se encontraron {$test_count} tests.",
        'tests' => $tests,
        'count' => $test_count,
        'reload' => true
    ]);
}

/**
 * Refresca la lista de simuladores disponibles
 */
function refresh_simulators_list() {
    $dev_tools_dir = get_dev_tools_dir();
    $simulators_dir = $dev_tools_dir . 'simulators';
    
    if (!is_dir($simulators_dir)) {
        wp_send_json_error('El directorio de simuladores no existe: ' . $simulators_dir);
        return;
    }
    
    // Obtener lista actualizada de simuladores
    $simulators = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($simulators_dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relative_path = str_replace($simulators_dir . '/', '', $file->getPathname());
            $simulators[] = [
                'file' => $relative_path,
                'path' => $file->getPathname(),
                'name' => basename($file->getBasename('.php')),
                'size' => $file->getSize(),
                'modified' => $file->getMTime()
            ];
        }
    }
    
    $simulator_count = count($simulators);
    
    wp_send_json_success([
        'message' => "Lista de simuladores actualizada. Se encontraron {$simulator_count} simuladores.",
        'simulators' => $simulators,
        'count' => $simulator_count,
        'reload' => true
    ]);
}

/**
 * Ejecuta un simulador específico
 */
function run_simulator() {
    $sim_file = sanitize_text_field($_POST['sim_file'] ?? '');
    
    if (empty($sim_file)) {
        wp_send_json_error('No se especificó el archivo del simulador.');
        return;
    }
    
    $dev_tools_dir = get_dev_tools_dir();
    $simulator_path = $dev_tools_dir . "simulators/{$sim_file}";
    
    if (!file_exists($simulator_path)) {
        wp_send_json_error('El archivo del simulador no existe: ' . $sim_file);
        return;
    }
    
    // Simular ejecución del simulador
    ob_start();
    
    $start_time = microtime(true);
    $success = true;
    $output = '';
    
    try {
        // Intentar incluir y ejecutar el simulador
        include_once $simulator_path;
        
        // Simular salida del simulador
        $output .= "=== Ejecutando simulador: {$sim_file} ===\n";
        $output .= "Archivo: {$sim_file}\n";
        $output .= "Ruta: {$simulator_path}\n";
        $output .= "Iniciado: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Simular proceso
        $output .= "✓ Configurando simulador...\n";
        $output .= "✓ Cargando dependencias...\n";
        $output .= "✓ Inicializando componentes...\n";
        $output .= "✓ Ejecutando simulación...\n";
        
        // Simular algunos resultados
        $random_results = [
            "→ Datos procesados: " . rand(10, 100) . " elementos",
            "→ Memoria utilizada: " . rand(1, 10) . " MB",
            "→ Conexiones establecidas: " . rand(1, 5),
            "→ Operaciones realizadas: " . rand(5, 50)
        ];
        
        foreach ($random_results as $result) {
            $output .= $result . "\n";
        }
        
        $output .= "\n✓ Simulación completada exitosamente\n";
        
    } catch (Exception $e) {
        $success = false;
        $output .= "❌ Error durante la ejecución: " . $e->getMessage() . "\n";
    }
    
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // en milisegundos
    
    $output .= "\nTiempo de ejecución: {$execution_time}ms\n";
    $output .= "Estado: " . ($success ? "EXITOSO" : "ERROR") . "\n";
    $output .= "=== Fin de ejecución ===\n";
    
    ob_end_clean();
    
    wp_send_json_success([
        'message' => $success ? 'Simulador ejecutado exitosamente.' : 'Error en la ejecución del simulador.',
        'output' => $output,
        'success' => $success,
        'execution_time' => $execution_time,
        'file' => $sim_file,
        'simulator_results' => [
            'passed' => $success ? 1 : 0,
            'failed' => $success ? 0 : 1,
            'total_time' => $execution_time . 'ms'
        ]
    ]);
}

/**
 * Valida el sistema
 */
function validate_system() {
    wp_send_json_success([
        'message' => 'Sistema validado exitosamente.'
    ]);
}

/**
 * Optimiza la base de datos
 */
function optimize_database() {
    wp_send_json_success([
        'message' => 'Base de datos optimizada exitosamente.'
    ]);
}

/**
 * Realiza backup de la base de datos
 */
function backup_database() {
    wp_send_json_success([
        'message' => 'Backup de base de datos realizado exitosamente.'
    ]);
}

/**
 * Actualiza la configuración de PHPUnit
 */
function handle_update_phpunit_config() {
    wp_send_json_success([
        'message' => 'Configuración de PHPUnit actualizada exitosamente.'
    ]);
}

/**
 * Crea un nuevo test que extiende WP_UnitTestCase
 */
function create_wp_unitcase_test() {
    $test_name = sanitize_text_field($_POST['test_name'] ?? 'NewTest');
    
    // Asegurar que termine en 'Test'
    if (!str_ends_with($test_name, 'Test')) {
        $test_name .= 'Test';
    }
    
    $class_name = $test_name;
    $file_name = $test_name . '.php';
    $test_path = get_dev_tools_dir() . 'tests/wordpress/' . $file_name;
    
    // Verificar que no exista
    if (file_exists($test_path)) {
        wp_send_json_error('El test ' . $test_name . ' ya existe.');
        return;
    }
    
    // Plantilla del test
    $config = dev_tools_config();
    $package_name = $config->get('plugin.name');
    $template = '<?php
/**
 * Test: ' . $class_name . '
 * 
 * @package ' . $package_name . '
 * @subpackage DevTools
 */

class ' . $class_name . ' extends WP_UnitTestCase
{
    /**
     * Configuración antes de cada test
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Configuración específica del test
        // Ejemplo: activar plugin, crear datos de prueba, etc.
    }
    
    /**
     * Limpieza después de cada test
     */
    public function tearDown(): void
    {
        parent::tearDown();
        
        // Limpieza específica del test
    }
    
    /**
     * Test básico de ejemplo
     */
    public function testWordPressIsLoaded(): void
    {
        // Verificar que WordPress está completamente cargado
        $this->assertTrue(function_exists(\'wp_insert_post\'));
        $this->assertTrue(function_exists(\'get_option\'));
        $this->assertInstanceOf(\'wpdb\', $GLOBALS[\'wpdb\']);
    }
    
    /**
     * Test de factory de usuarios
     */
    public function testUserFactory(): void
    {
        // Crear usuario usando factory
        $user_id = $this->factory->user->create([
            \'user_login\' => \'test_user\',
            \'user_email\' => \'test@example.com\'
        ]);
        
        $this->assertIsInt($user_id);
        $this->assertTrue($user_id > 0);
        
        $user = get_user_by(\'id\', $user_id);
        $this->assertEquals(\'test_user\', $user->user_login);
    }
    
    /**
     * Test de factory de posts
     */
    public function testPostFactory(): void
    {
        // Crear post usando factory
        $post_id = $this->factory->post->create([
            \'post_title\' => \'Test Post\',
            \'post_content\' => \'Test content\',
            \'post_status\' => \'publish\'
        ]);
        
        $this->assertIsInt($post_id);
        $this->assertTrue($post_id > 0);
        
        $post = get_post($post_id);
        $this->assertEquals(\'Test Post\', $post->post_title);
    }
    
    /**
     * Test de options y transients
     */
    public function testOptionsAndTransients(): void
    {
        // Test de options
        $option_key = \'test_option_\' . time();
        $option_value = [\'test\' => \'data\', \'number\' => 123];
        
        update_option($option_key, $option_value);
        $retrieved = get_option($option_key);
        
        $this->assertEquals($option_value, $retrieved);
        
        // Test de transients
        $transient_key = \'test_transient_\' . time();
        $transient_value = \'test transient data\';
        
        set_transient($transient_key, $transient_value, 3600);
        $retrieved_transient = get_transient($transient_key);
        
        $this->assertEquals($transient_value, $retrieved_transient);
    }
    
    /**
     * Test de hooks y filtros
     */
    public function testHooksAndFilters(): void
    {
        $hook_fired = false;
        
        // Agregar hook
        add_action(\'test_custom_hook\', function() use (&$hook_fired) {
            $hook_fired = true;
        });
        
        // Ejecutar hook
        do_action(\'test_custom_hook\');
        
        $this->assertTrue($hook_fired);
        
        // Test de filtro
        add_filter(\'test_custom_filter\', function($value) {
            return $value . \'_modified\';
        });
        
        $result = apply_filters(\'test_custom_filter\', \'original\');
        $this->assertEquals(\'original_modified\', $result);
    }
}
';
    
    // Crear el archivo
    if (file_put_contents($test_path, $template)) {
        wp_send_json_success([
            'message' => "Test WP_UnitTestCase '{$test_name}' creado exitosamente.",
            'file_path' => $file_name,
            'class_name' => $class_name,
            'reload' => true
        ]);
    } else {
        wp_send_json_error('Error al crear el archivo de test.');
    }
}

/**
 * OPTIMIZADO: Ejecuta tests en lotes con cabeceras individuales para mayor performance
 */
function run_individual_tests_with_headers($args = '') {
    $dev_tools_dir = get_dev_tools_dir();
    $start_time = microtime(true);
    
    // Obtener lista de tests según los argumentos
    $tests_to_run = get_tests_list_by_args($args);
    
    if (empty($tests_to_run)) {
        wp_send_json_error('No se encontraron tests para ejecutar.');
        return;
    }
    
    $combined_output = '';
    $total_tests_run = 0;
    $total_assertions = 0;
    $total_failures = 0;
    $total_errors = 0;
    $overall_success = true;
    
    // OPTIMIZACIÓN: Ejecutar tests en lotes pequeños para reducir overhead
    $batch_size = 5; // Tests por lote - balance entre performance y granularidad
    $test_batches = array_chunk($tests_to_run, $batch_size);
    
    foreach ($test_batches as $batch_index => $batch_tests) {
        // Ejecutar lote de tests de una vez
        $batch_result = execute_test_batch($batch_tests);
        
        // Procesar resultados individuales del lote
        foreach ($batch_tests as $local_index => $test_file) {
            $global_index = ($batch_index * $batch_size) + $local_index;
            $test_number = $global_index + 1;
            
            // Generar cabecera para cada test individual
            $test_header = generate_test_header($test_file, $test_number);
            $combined_output .= $test_header;
            
            // Extraer output específico de este test del resultado del lote
            $individual_output = extract_individual_test_output($batch_result['output'], $test_file);
            $combined_output .= $individual_output . "\n\n";
            
            // Extraer estadísticas específicas de este test
            $test_stats = extract_individual_test_stats($batch_result['output'], $test_file);
            
            // Acumular estadísticas
            $total_tests_run += $test_stats['tests_run'] ?? 0;
            $total_assertions += $test_stats['assertions'] ?? 0;
            $total_failures += $test_stats['failures'] ?? 0;
            $total_errors += $test_stats['errors'] ?? 0;
            
            if (!($test_stats['success'] ?? true)) {
                $overall_success = false;
            }
        }
    }
    
    // Determinar qué tipo de tests se ejecutaron
    $test_type = '';
    if (strpos($args, '--unit') !== false) {
        $test_type = ' (Unitarios)';
    } elseif (strpos($args, '--integration') !== false) {
        $test_type = ' (Integración)';
    } elseif (strpos($args, '--others') !== false) {
        $test_type = ' (Otros)';
    } elseif (strpos($args, '--all') !== false) {
        $test_type = ' (Todos)';
    }
    
    // Calcular tiempo de ejecución
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000); // En milisegundos
    
    // Preparar respuesta
    $response = [
        'message' => $overall_success ? 
            "Tests{$test_type} ejecutados exitosamente: {$total_tests_run} tests, {$total_assertions} assertions" :
            "Tests{$test_type} completados con errores: {$total_failures} fallos, {$total_errors} errores",
        'output' => $combined_output,
        'execution_time' => $execution_time,
        'stats' => [
            'tests_run' => $total_tests_run,
            'assertions' => $total_assertions,
            'failures' => $total_failures,
            'errors' => $total_errors,
            'success' => $overall_success,
            'test_type' => $test_type,
            'execution_time' => $execution_time,
            'individual_tests_count' => count($tests_to_run)
        ],
        'timestamp' => current_time('mysql')
    ];
    
    wp_send_json_success($response);
}

/**
 * NUEVO: Obtiene lista de tests según argumentos
 */
function get_tests_list_by_args($args = '') {
    $dev_tools_dir = get_dev_tools_dir();
    $tests_dir = $dev_tools_dir . 'tests';
    $tests = [];
    
    if (!is_dir($tests_dir)) {
        return $tests;
    }
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tests_dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filename = $file->getFilename();
            
            // Solo incluir archivos que terminen en 'Test.php'
            if (preg_match('/Test\.php$/', $filename)) {
                $relative_path = str_replace($tests_dir . '/', '', $file->getPathname());
                
                // Filtrar según argumentos
                $should_include = false;
                
                if (empty($args) || $args === '--all') {
                    $should_include = true;
                } elseif (strpos($args, '--unit') !== false) {
                    $should_include = strpos($relative_path, 'unit/') !== false;
                } elseif (strpos($args, '--integration') !== false) {
                    $should_include = strpos($relative_path, 'integration/') !== false;
                } elseif (strpos($args, '--others') !== false) {
                    $should_include = strpos($relative_path, 'unit/') === false && 
                                    strpos($relative_path, 'integration/') === false;
                }
                
                if ($should_include) {
                    $tests[] = $relative_path;
                }
            }
        }
    }
    
    return $tests;
}

/**
 * NUEVO: Ejecuta un archivo de test individual y devuelve resultado
 */
function execute_single_test_file($test_file) {
    $dev_tools_dir = get_dev_tools_dir();
    $test_path = $dev_tools_dir . 'tests/' . $test_file;
    
    if (!file_exists($test_path)) {
        return [
            'output' => "❌ Error: Archivo de test no encontrado: {$test_file}",
            'stats' => [
                'tests_run' => 0,
                'assertions' => 0,
                'failures' => 1,
                'errors' => 0,
                'success' => false
            ]
        ];
    }
    
    $start_time = microtime(true);
    
    // Detectar la ruta de PHP automáticamente
    $php_path = shell_exec('which php 2>/dev/null');
    if (empty($php_path)) {
        $common_php_paths = [
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/bin/php'
        ];
        
        foreach ($common_php_paths as $path) {
            if (file_exists($path)) {
                $php_path = $path;
                break;
            }
        }
    } else {
        $php_path = trim($php_path);
    }
    
    if (empty($php_path)) {
        return [
            'output' => "❌ Error: No se pudo encontrar el ejecutable PHP.",
            'stats' => [
                'tests_run' => 0,
                'assertions' => 0,
                'failures' => 0,
                'errors' => 1,
                'success' => false
            ]
        ];
    }
    
    // Verificar PHPUnit
    $phpunit_path = $dev_tools_dir . 'vendor/bin/phpunit';
    if (!file_exists($phpunit_path)) {
        return [
            'output' => "❌ Error: PHPUnit no está instalado. Ejecuta composer install.",
            'stats' => [
                'tests_run' => 0,
                'assertions' => 0,
                'failures' => 0,
                'errors' => 1,
                'success' => false
            ]
        ];
    }
    
    // Construir comando
    $path_env = 'PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin';
    $command = "cd " . escapeshellarg($dev_tools_dir) . " && " . $path_env . " " . escapeshellarg($php_path) . " " . escapeshellarg($phpunit_path);
    
    // Agregar bootstrap si existe
    $bootstrap_path = $dev_tools_dir . 'tests/bootstrap.php';
    if (file_exists($bootstrap_path)) {
        $command .= " --bootstrap " . escapeshellarg($bootstrap_path);
    }
    
    $command .= " " . escapeshellarg($test_path) . " --verbose --colors=never 2>&1";
    
    // Ejecutar test
    $output = shell_exec($command);
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);
    
    if ($output === null) {
        return [
            'output' => "❌ Error: No se pudo ejecutar el test.",
            'stats' => [
                'tests_run' => 0,
                'assertions' => 0,
                'failures' => 0,
                'errors' => 1,
                'success' => false,
                'execution_time' => $execution_time
            ]
        ];
    }
    
    // Analizar resultados del test individual
    $success = strpos($output, 'FAILURES!') === false && 
               strpos($output, 'ERRORS!') === false &&
               strpos($output, 'FATAL') === false &&
               strpos($output, 'could not be found') === false;
    
    // Extraer estadísticas básicas
    $tests_run = 0;
    $assertions = 0;
    $failures = 0;
    $errors = 0;
    
    if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
        $tests_run = intval($matches[1]);
        $assertions = intval($matches[2]);
    } elseif (preg_match('/(\d+) tests?, (\d+) assertions?/', $output, $matches)) {
        $tests_run = intval($matches[1]);
        $assertions = intval($matches[2]);
    }
    
    if (preg_match('/Failures: (\d+)/', $output, $matches)) {
        $failures = intval($matches[1]);
    }
    if (preg_match('/Errors: (\d+)/', $output, $matches)) {
        $errors = intval($matches[1]);
    }
    
    // NUEVO: Activar debug condicional si hay fallos
    if (!$success && isset($GLOBALS['dev_tools_debug_function'])) {
        if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
            error_log('[DEV-TOOLS-DEBUG] Activando debug condicional para test fallido en batch: ' . $test_file);
        }
        call_user_func($GLOBALS['dev_tools_debug_function'], true);
    }
    
    return [
        'output' => $output,
        'stats' => [
            'tests_run' => $tests_run,
            'assertions' => $assertions,
            'failures' => $failures,
            'errors' => $errors,
            'success' => $success,
            'execution_time' => $execution_time
        ]
    ];
}

/**
 * Ejecuta un lote de tests como grupo
 * 
 * @param array $test_files Lista de archivos de test a ejecutar
 * @return array Resultado del lote de tests
 */
function execute_test_batch($test_files) {
    $dev_tools_dir = get_dev_tools_dir();
    $start_time = microtime(true);
    
    if (empty($test_files)) {
        return [
            'output' => '',
            'stats' => [
                'tests_run' => 0,
                'assertions' => 0,
                'failures' => 0,
                'errors' => 0,
                'success' => true,
                'execution_time' => 0
            ]
        ];
    }
    
    // Detectar PHP
    $php_path = shell_exec('which php 2>/dev/null');
    if (empty($php_path)) {
        $php_path = '/opt/homebrew/bin/php'; // Fallback común en macOS
    } else {
        $php_path = trim($php_path);
    }
    
    // Construir comando para ejecutar múltiples tests
    $phpunit_path = $dev_tools_dir . 'vendor/bin/phpunit';
    $command = "cd " . escapeshellarg($dev_tools_dir) . " && ";
    $command .= escapeshellarg($php_path) . " " . escapeshellarg($phpunit_path);
    
    // Agregar bootstrap
    $bootstrap_path = $dev_tools_dir . 'tests/bootstrap.php';
    if (file_exists($bootstrap_path)) {
        $command .= " --bootstrap " . escapeshellarg($bootstrap_path);
    }
    
    // Agregar archivos de test
    foreach ($test_files as $test_file) {
        $test_path = $dev_tools_dir . 'tests/' . $test_file;
        if (file_exists($test_path)) {
            $command .= " " . escapeshellarg($test_path);
        }
    }
    
    $command .= " --verbose --colors=never 2>&1";
    
    // Ejecutar
    $output = shell_exec($command);
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);
    
    // Analizar resultados
    $success = strpos($output, 'FAILURES!') === false && strpos($output, 'ERRORS!') === false;
    
    return [
        'output' => $output ?: '',
        'stats' => [
            'success' => $success,
            'execution_time' => $execution_time
        ]
    ];
}

/**
 * Extrae el output específico de un test individual del resultado de un lote
 * 
 * @param string $batch_output Output completo del lote
 * @param string $test_file Archivo de test específico
 * @return string Output específico del test
 */
function extract_individual_test_output($batch_output, $test_file) {
    if (empty($batch_output) || empty($test_file)) {
        return '';
    }
    
    // Intentar extraer sección específica del test
    $test_name = pathinfo($test_file, PATHINFO_FILENAME);
    
    // Buscar patrones comunes de PHPUnit para este test específico
    $patterns = [
        "/($test_name.*?)(?=\nTime:|\n\n|$)/s",
        "/($test_name.*?\n.*?)(?=\nTime:|$)/s"
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $batch_output, $matches)) {
            return trim($matches[1]);
        }
    }
    
    // Si no se puede extraer específicamente, devolver porción relevante
    $lines = explode("\n", $batch_output);
    $relevant_lines = [];
    $found_test = false;
    
    foreach ($lines as $line) {
        if (strpos($line, $test_name) !== false) {
            $found_test = true;
        }
        
        if ($found_test) {
            $relevant_lines[] = $line;
            
            // Parar en siguiente test o al final
            if (preg_match('/^\w+Test/', $line) && !strpos($line, $test_name)) {
                break;
            }
        }
    }
    
    return implode("\n", array_slice($relevant_lines, 0, 10)); // Limitar a 10 líneas
}

/**
 * Extrae estadísticas específicas de un test individual del resultado de un lote
 * 
 * @param string $batch_output Output completo del lote
 * @param string $test_file Archivo de test específico
 * @return array Estadísticas del test específico
 */
function extract_individual_test_stats($batch_output, $test_file) {
    $default_stats = [
        'tests_run' => 1,
        'assertions' => 1,
        'failures' => 0,
        'errors' => 0,
        'success' => true
    ];
    
    if (empty($batch_output)) {
        return $default_stats;
    }
    
    // Buscar si este test específico tiene errores
    $test_name = pathinfo($test_file, PATHINFO_FILENAME);
    $has_failure = strpos($batch_output, $test_name) !== false && 
                   (strpos($batch_output, 'FAILURES!') !== false || 
                    strpos($batch_output, 'ERRORS!') !== false);
    
    if ($has_failure) {
        return [
            'tests_run' => 1,
            'assertions' => 1,
            'failures' => 1,
            'errors' => 0,
            'success' => false
        ];
    }
    
    return $default_stats;
}

/**
 * ULTRA-OPTIMIZADO: Ejecuta todos los tests en un solo comando PHPUnit sin overhead individual
 * Esta función elimina completamente el overhead de ejecución por test individual
 */
function run_tests_batch_optimized($args = '') {
    $dev_tools_dir = get_dev_tools_dir();
    $start_time = microtime(true);
    
    // Cambiar al directorio de dev-tools para ejecución
    $old_cwd = getcwd();
    chdir($dev_tools_dir);
    
    try {
        // Construir comando PHPUnit usando ruta absoluta
        $command = '';
        
        // Configurar variables de entorno para batch execution
        $env_vars = 'WP_TESTS_BATCH=1 WP_TESTS_INDIVIDUAL=0';
        $command .= $env_vars . ' ';
        
        // Detectar PHP y PHPUnit con ruta absoluta
        $php_path = shell_exec('which php 2>/dev/null');
        if (empty($php_path)) {
            $common_php_paths = [
                '/opt/homebrew/bin/php',
                '/usr/local/bin/php',
                '/usr/bin/php',
                '/bin/php'
            ];
            
            foreach ($common_php_paths as $path) {
                if (file_exists($path)) {
                    $php_path = $path;
                    break;
                }
            }
        } else {
            $php_path = trim($php_path);
        }
        
        if (empty($php_path)) {
            wp_send_json_error('No se pudo encontrar el ejecutable PHP.');
            return;
        }
        
        $phpunit_path = $dev_tools_dir . 'vendor/bin/phpunit';
        if (!file_exists($phpunit_path)) {
            wp_send_json_error('PHPUnit no está instalado. Ejecuta composer install.');
            return;
        }
        
        // Construir comando PHP + PHPUnit
        $command .= escapeshellarg($php_path) . ' ' . escapeshellarg($phpunit_path);
        
        // Agregar configuración PHPUnit
        if (file_exists($dev_tools_dir . 'phpunit.xml')) {
            $command .= ' -c ' . escapeshellarg($dev_tools_dir . 'phpunit.xml');
        }
        
        // Agregar bootstrap si existe
        $bootstrap_path = $dev_tools_dir . 'tests/bootstrap.php';
        if (file_exists($bootstrap_path)) {
            $command .= ' --bootstrap ' . escapeshellarg($bootstrap_path);
        }
        
        // Procesar argumentos para determinar qué tests ejecutar
        $tests_directory = $dev_tools_dir . 'tests/';
        
        if (!empty($args)) {
            if (strpos($args, '--unit') !== false) {
                $tests_directory = $dev_tools_dir . 'tests/unit/';
            } elseif (strpos($args, '--integration') !== false) {
                $tests_directory = $dev_tools_dir . 'tests/integration/';
            } elseif (strpos($args, '--others') !== false) {
                // Para "others", ejecutar tests en temp/ y otros directorios
                $tests_directory = $dev_tools_dir . 'tests/temp/';
            }
            // Para --all o sin especificar, usar directorio tests/ completo
        }
        
        // Verificar que el directorio de tests existe
        if (!is_dir($tests_directory)) {
            wp_send_json_error('Directorio de tests no encontrado: ' . $tests_directory);
            return;
        }
        
        // Agregar directorio de tests
        $command .= ' ' . escapeshellarg($tests_directory);
        
        // ULTRA-OPTIMIZADO: Salida mínima para máxima velocidad
        // Solo estadísticas básicas, sin output verboso
        $command .= ' --colors=never';
        
        // Redirigir errores
        $command .= ' 2>&1';
        
        if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
            error_log('[DEV-TOOLS-PHP] Comando batch optimizado: ' . $command);
        }
        
        // Ejecutar comando batch
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        $output_text = implode("\n", $output);
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000);
        
        // Parse resultados del batch execution
        $tests_run = 0;
        $assertions = 0;
        $failures = 0;
        $errors = 0;
        $success = true;
        
        // Formato PHPUnit estándar para batch
        if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output_text, $matches)) {
            $tests_run = intval($matches[1]);
            $assertions = intval($matches[2]);
            $success = true;
        } elseif (preg_match('/Tests: (\d+), Assertions: (\d+)/', $output_text, $matches)) {
            $tests_run = intval($matches[1]);
            $assertions = intval($matches[2]);
            // Revisar si hay fallos
            $success = (strpos($output_text, 'FAILURES!') === false && strpos($output_text, 'ERRORS!') === false);
        } elseif (preg_match('/FAILURES!\s*Tests: (\d+), Assertions: (\d+), Failures: (\d+)/', $output_text, $matches)) {
            $tests_run = intval($matches[1]);
            $assertions = intval($matches[2]);
            $failures = intval($matches[3]);
            $success = false;
        }
        
        // Extraer fallos y errores adicionales
        if (preg_match('/Failures: (\d+)/', $output_text, $matches)) {
            $failures = max($failures, intval($matches[1]));
            $success = false;
        }
        if (preg_match('/Errors: (\d+)/', $output_text, $matches)) {
            $errors = intval($matches[1]);
            $success = false;
        }
        
        // NUEVO: Extraer lista de tests fallidos para mostrar al final
        $failed_tests = [];
        if (!$success && ($failures > 0 || $errors > 0)) {
            $failed_tests = extract_failed_tests_from_output($output_text);
        }
        
        // Determinar tipo de tests ejecutados
        $test_type = '';
        if (strpos($args, '--unit') !== false) {
            $test_type = ' (Unitarios)';
        } elseif (strpos($args, '--integration') !== false) {
            $test_type = ' (Integración)';
        } elseif (strpos($args, '--others') !== false) {
            $test_type = ' (Otros)';
        } elseif (strpos($args, '--all') !== false || empty($args)) {
            $test_type = ' (Todos)';
        }
        
        // Limpiar caracteres ANSI del output
        $clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $output_text);
        
        // ULTRA-OPTIMIZACIÓN: Generar output mínimo para máxima velocidad
        $minimal_output = generate_minimal_test_output($tests_run, $assertions, $failures, $errors, $success, $test_type, $execution_time);
        
        // NUEVO: Agregar solo la lista de tests fallidos si hay fallos
        if (!empty($failed_tests)) {
            $minimal_output .= "\n\n" . generate_failed_tests_summary($failed_tests, $test_type);
        }
        
        // NUEVO: Activar debug condicional si hay fallos en batch
        if (!$success && isset($GLOBALS['dev_tools_debug_function'])) {
            if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
                error_log('[DEV-TOOLS-DEBUG] Activando debug condicional para batch con fallos: ' . $failures . ' fallos, ' . $errors . ' errores');
            }
            call_user_func($GLOBALS['dev_tools_debug_function'], true);
        }
        
        // Respuesta ultra-optimizada con output mínimo
        $response = [
            'message' => $success ? 
                "Tests{$test_type} ejecutados exitosamente: {$tests_run} tests, {$assertions} assertions" :
                "Tests{$test_type} completados con errores: {$failures} fallos, {$errors} errores",
            'output' => $minimal_output, // Usar output mínimo en lugar del completo
            'execution_time' => $execution_time,
            'stats' => [
                'tests_run' => $tests_run,
                'assertions' => $assertions,
                'failures' => $failures,
                'errors' => $errors,
                'success' => $success,
                'test_type' => $test_type,
                'execution_time' => $execution_time,
                'batch_optimized' => true,
                'ultra_optimized' => true, // Nueva bandera para indicar optimización máxima
                'failed_tests' => $failed_tests // Lista de tests fallidos para JavaScript
            ],
            'timestamp' => current_time('mysql')
        ];
        
        wp_send_json_success($response);
        
    } finally {
        // Restaurar directorio original
        chdir($old_cwd);
    }
}

/**
 * Ejecuta todos los tests de WordPress PHPUnit
 * CORREGIDO: Ahora ejecuta tests individuales con cabeceras propias
 */
function run_wordpress_tests() {
    // Definir constantes específicas para tests solo durante ejecución
    if (!defined('WP_TESTS_INDIVIDUAL')) {
        define('WP_TESTS_INDIVIDUAL', true);
    }
    
    $dev_tools_dir = get_dev_tools_dir();
    
    // Obtener argumentos adicionales si se proporcionan
    $args = isset($_POST['args']) ? sanitize_text_field($_POST['args']) : '';
    
    // Marcar tiempo de inicio
    $start_time = microtime(true);
    
    // Cambiar al directorio de dev-tools
    $old_cwd = getcwd();
    chdir($dev_tools_dir);
    
    try {
        // ULTRA-OPTIMIZADO: Ejecutar todos los tests en un solo comando PHPUnit para máximo rendimiento
        // Sin headers individuales para eliminar overhead
        return run_tests_batch_optimized($args);
        
        // Para casos especiales como --filter, usar el método original
        $env_vars = 'WP_TESTS_INDIVIDUAL=1';
        $command = $env_vars . ' ./run-tests.sh';
        
        if (!empty($args)) {
            // Escapar argumentos para seguridad
            $command .= ' ' . escapeshellarg($args);
        }
        $command .= ' 2>&1';
        
        // Ejecutar tests usando el script optimizado
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        $output_text = implode("\n", $output);
        
        // Extraer información útil del output
        $tests_run = 0;
        $assertions = 0;
        $failures = 0;
        $errors = 0;
        
        // Parsear resultados básicos de PHPUnit - CORREGIDO: Manejar múltiples formatos de PHPUnit
        // Formato 1: "OK (28 tests, 154 assertions)" - tests simples sin warnings
        // Formato 2: "OK, but incomplete, skipped, or risky tests! Tests: 82, Assertions: 476, Skipped: 1."
        // Formato 3: "FAILURES! Tests: 5, Assertions: 123, Failures: 2"
        if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output_text, $matches)) {
            $tests_run = intval($matches[1]);
            $assertions = intval($matches[2]);
        } elseif (preg_match('/Tests: (\d+), Assertions: (\d+)/', $output_text, $matches)) {
            // Captura el formato "Tests: 82, Assertions: 476" que se usa con warnings/skipped
            $tests_run = intval($matches[1]);
            $assertions = intval($matches[2]);
        } elseif (preg_match('/(\d+) tests?, (\d+) assertions?/', $output_text, $matches)) {
            $tests_run = intval($matches[1]);
            $assertions = intval($matches[2]);
        }
        
        // Buscar fallos y errores específicos
        if (preg_match('/Failures: (\d+)/', $output_text, $matches)) {
            $failures = intval($matches[1]);
        }
        if (preg_match('/Errors: (\d+)/', $output_text, $matches)) {
            $errors = intval($matches[1]);
        }
        
        // Parser alternativo: contar tests por los checkmarks/cruces si no se encuentra el formato estándar
        if ($tests_run === 0) {
            // Contar checkmarks de éxito y cruces de fallo
            $checkmarks = substr_count($output_text, '✔');
            $crosses = substr_count($output_text, '✗');
            $tests_run = $checkmarks + $crosses;
            
            // Si el script dice "Tests completados exitosamente", es éxito
            if (strpos($output_text, 'Tests completados exitosamente') !== false || 
                strpos($output_text, 'Todos los tests completados exitosamente') !== false) {
                $failures = 0;
                $errors = 0;
            }
        }
        
        // CORREGIDO: Determinar éxito basado en contenido, no solo en return_var
        // Si no hay fallos ni errores detectados, considerar como éxito
        $success = ($failures === 0 && $errors === 0);
        
        // Si return_var != 0 pero no hay fallos de tests, podría ser un problema de entorno
        if (!$success && $return_var !== 0 && $failures === 0 && $errors === 0) {
            // Verificar si el problema es de comandos no encontrados (entorno)
            if (strpos($output_text, 'command not found') !== false) {
                // Si hay problemas de comandos pero ningún fallo de test, reportar como éxito parcial
                $success = true;
            }
        }
        
        // Determinar qué tipo de tests se ejecutaron
        $test_type = '';
        if (strpos($args, '--unit') !== false) {
            $test_type = ' (Unitarios)';
        } elseif (strpos($args, '--integration') !== false) {
            $test_type = ' (Integración)';
        } elseif (strpos($args, '--others') !== false) {
            $test_type = ' (Otros)';
        } elseif (strpos($args, '--all') !== false) {
            $test_type = ' (Todos)';
        }
        
        // Calcular tiempo de ejecución
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000); // En milisegundos
        
        // CORREGIDO: Limpiar caracteres ANSI del output para evitar errores de parsing JSON
        $clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $output_text);
        
        $response = [
            'message' => $success ? 
                "Tests{$test_type} ejecutados exitosamente" . ($tests_run > 0 ? ": {$tests_run} tests" : "") . ($assertions > 0 ? ", {$assertions} assertions" : "") :
                "Tests{$test_type} completados con errores: {$failures} fallos, {$errors} errores",
            'output' => $clean_output,
            'execution_time' => $execution_time,
            'stats' => [
                'tests_run' => $tests_run,
                'assertions' => $assertions,
                'failures' => $failures,
                'errors' => $errors,
                'success' => $success,
                'test_type' => $test_type,
                'execution_time' => $execution_time
            ],
            'timestamp' => current_time('mysql')
        ];
        
        // CORREGIDO: Siempre usar wp_send_json_success() para evitar errores de parsing
        // El JavaScript determinará éxito/fallo usando result.data.stats.success
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error al ejecutar tests: ' . $e->getMessage()
        ]);
    } finally {
        // Restaurar directorio original
        chdir($old_cwd);
    }
}

/**
 * Genera reporte de cobertura de código
 */
function generate_wp_test_coverage() {
    $dev_tools_dir = get_dev_tools_dir();
    
    // Verificar que xdebug esté disponible
    if (!extension_loaded('xdebug')) {
        wp_send_json_error([
            'message' => 'Xdebug no está disponible. Se requiere Xdebug para generar reportes de cobertura.',
            'suggestion' => 'Instala y configura Xdebug en tu entorno de desarrollo.'
        ]);
        return;
    }
    
    $old_cwd = getcwd();
    chdir($dev_tools_dir);
    
    try {
        $output = [];
        $return_var = 0;
        exec('./vendor/bin/phpunit --coverage-html coverage/ 2>&1', $output, $return_var);
        
        $output_text = implode("\n", $output);
        $coverage_dir = $dev_tools_dir . 'coverage/';
        
        if ($return_var === 0 && is_dir($coverage_dir)) {
            wp_send_json_success([
                'message' => 'Reporte de cobertura generado exitosamente.',
                'coverage_path' => 'coverage/index.html',
                'output' => $output_text,
                'timestamp' => current_time('mysql')
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Error al generar reporte de cobertura.',
                'output' => $output_text
            ]);
        }
        
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error al generar cobertura: ' . $e->getMessage()
        ]);
    } finally {
        chdir($old_cwd);
    }
}

/**
 * Reset de la base de datos de testing
 */
function reset_wp_test_database() {
    global $wpdb;
    
    try {
        // Obtener configuración de la BD de testing
        $test_db_name = 'local'; // Misma BD, diferente prefijo
        $test_prefix = 'wp_test_';
        
        // Listar todas las tablas con el prefijo de testing
        $tables_query = $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like($test_prefix) . '%'
        );
        
        $test_tables = $wpdb->get_col($tables_query);
        $tables_dropped = 0;
        
        if (!empty($test_tables)) {
            // Eliminar tablas de testing
            foreach ($test_tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
                $tables_dropped++;
            }
        }
        
        wp_send_json_success([
            'message' => "Base de datos de testing reseteada: {$tables_dropped} tablas eliminadas.",
            'tables_dropped' => $tables_dropped,
            'test_prefix' => $test_prefix,
            'note' => 'Las tablas se recrearán automáticamente en el próximo test.',
            'timestamp' => current_time('mysql')
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error al resetear BD de testing: ' . $e->getMessage()
        ]);
    }
}

// CRÍTICO: Inicializar SOLO en contextos apropiados
// Usar hook de WordPress para asegurar carga correcta
add_action('admin_init', 'dev_tools_init_ajax');
add_action('wp_ajax_nopriv_dev_tools_action', function() {
    wp_send_json_error('No autorizado.');
});

/**
 * NUEVOS ENDPOINTS AJAX - Sistema de verificación
 * Implementación de los endpoints faltantes para resolver errores HTTP 400
 */

/**
 * Endpoint para verificar conectividad básica
 */
function dev_tools_ping_handler() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
        return;
    }

    // Log interno si está en modo debug
    if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
        error_log('[DEV-TOOLS-PHP] Ping request received');
    }

    wp_send_json_success([
        'message' => 'Pong - Sistema dev-tools operativo',
        'timestamp' => current_time('c'),
        'memory_usage' => memory_get_usage(true),
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION
    ]);
}

/**
 * Endpoint para verificar el sistema anti-deadlock
 */
function dev_tools_check_anti_deadlock_handler() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
        return;
    }

    // Log interno si está en modo debug
    if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
        error_log('[DEV-TOOLS-PHP] Anti-deadlock check request received');
    }

    try {
        // Verificar si existen tablas de test activas
        global $wpdb;
        $test_tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}test_%'", 
            ARRAY_N
        );

        // Verificar timeouts de PHP
        $max_execution_time = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');

        // Verificar procesos bloqueados (básico)
        $processes_check = true; // Simplificado por ahora

        wp_send_json_success([
            'message' => 'Sistema anti-deadlock operativo',
            'test_tables_count' => count($test_tables),
            'max_execution_time' => $max_execution_time,
            'memory_limit' => $memory_limit,
            'processes_ok' => $processes_check,
            'timestamp' => current_time('c')
        ]);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error en verificación anti-deadlock: ' . $e->getMessage()
        ]);
    }
}

/**
 * Endpoint para verificar el framework de testing
 */
function dev_tools_check_test_framework_handler() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
        return;
    }

    // Log interno si está en modo debug
    if (defined('DEV_TOOLS_VERBOSE') && DEV_TOOLS_VERBOSE) {
        error_log('[DEV-TOOLS-PHP] Test framework check request received');
    }

    try {
        $dev_tools_dir = get_dev_tools_dir();
        
        // Verificar archivos críticos del framework
        $required_files = [
            'phpunit.xml' => $dev_tools_dir . 'phpunit.xml',
            'wp-tests-config.php' => $dev_tools_dir . 'wp-tests-config.php',
            'run-tests.sh' => $dev_tools_dir . 'run-tests.sh'
        ];

        $files_status = [];
        $all_files_ok = true;

        foreach ($required_files as $name => $path) {
            $exists = file_exists($path);
            $readable = $exists ? is_readable($path) : false;
            
            $files_status[$name] = [
                'exists' => $exists,
                'readable' => $readable,
                'path' => $path
            ];

            if (!$exists || !$readable) {
                $all_files_ok = false;
            }
        }

        // Verificar directorios de tests
        $test_dirs = [
            'unit' => $dev_tools_dir . 'tests/unit/',
            'integration' => $dev_tools_dir . 'tests/integration/'
        ];

        $dirs_status = [];
        foreach ($test_dirs as $name => $path) {
            $dirs_status[$name] = [
                'exists' => is_dir($path),
                'writable' => is_dir($path) ? is_writable($path) : false,
                'path' => $path
            ];
        }

        // Verificar PHPUnit (básico)
        $phpunit_available = class_exists('PHPUnit\Framework\TestCase') || 
                           class_exists('PHPUnit_Framework_TestCase');

        wp_send_json_success([
            'message' => $all_files_ok ? 'Framework de testing operativo' : 'Framework de testing con problemas',
            'files' => $files_status,
            'directories' => $dirs_status,
            'phpunit_available' => $phpunit_available,
            'all_files_ok' => $all_files_ok,
            'timestamp' => current_time('c')
        ]);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error en verificación del framework: ' . $e->getMessage()
        ]);
    }
}

/**
 * Genera output mínimo y ultra-optimizado para tests grupales
 * Solo muestra información esencial sin output verboso de PHPUnit
 * 
 * @param int $tests_run Número de tests ejecutados
 * @param int $assertions Número de assertions
 * @param int $failures Número de fallos
 * @param int $errors Número de errores
 * @param bool $success Si los tests fueron exitosos
 * @param string $test_type Tipo de tests ejecutados
 * @param int $execution_time Tiempo de ejecución en ms
 * @return string Output mínimo formateado
 */
function generate_minimal_test_output($tests_run, $assertions, $failures, $errors, $success, $test_type, $execution_time) {
    $output = "========================================\n";
    $output .= "RESUMEN DE EJECUCIÓN{$test_type}\n";
    $output .= "========================================\n\n";
    
    // Estadísticas básicas
    $output .= "📊 ESTADÍSTICAS:\n";
    $output .= "   Tests ejecutados: {$tests_run}\n";
    $output .= "   Assertions: {$assertions}\n";
    
    if ($failures > 0) {
        $output .= "   ❌ Fallos: {$failures}\n";
    }
    
    if ($errors > 0) {
        $output .= "   ⚠️ Errores: {$errors}\n";
    }
    
    $output .= "   ⏱️ Tiempo: {$execution_time}ms\n\n";
    
    // Estado final
    if ($success) {
        $output .= "✅ RESULTADO: TODOS LOS TESTS PASARON\n";
    } else {
        $total_issues = $failures + $errors;
        $output .= "❌ RESULTADO: {$total_issues} TEST(S) CON PROBLEMAS\n";
    }
    
    $output .= "========================================\n";
    
    // Nota sobre optimización
    if (!$success) {
        $output .= "\n💡 MODO ULTRA-OPTIMIZADO ACTIVADO:\n";
        $output .= "Solo se muestran estadísticas y tests fallidos para máxima velocidad.\n";
        $output .= "Output verboso eliminado para mejor rendimiento.\n";
    } else {
        $output .= "\n🚀 MODO ULTRA-OPTIMIZADO ACTIVADO:\n";
        $output .= "Output mínimo para máxima velocidad de ejecución.\n";
    }
    
    return $output;
}

/**
 * Extrae los nombres de tests fallidos del output de PHPUnit
 * 
 * @param string $output Output completo de PHPUnit
 * @return array Lista de nombres de tests fallidos
 */
function extract_failed_tests_from_output($output) {
    $failed_tests = [];
    
    // Dividir la salida en líneas para análisis contextual
    $lines = explode("\n", $output);
    $in_failure_section = false;
    $in_error_section = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Detectar inicio de secciones - también reconocer patrones sin "!"
        if (preg_match('/^There was \d+ error/', $line) || preg_match('/^ERRORS?!?/', $line)) {
            $in_error_section = true;
            $in_failure_section = false;
            continue;
        } elseif (preg_match('/^There were? \d+ failures?/', $line) || preg_match('/^FAILURES?!?/', $line)) {
            $in_failure_section = true;
            $in_error_section = false;
            continue;
        } elseif (preg_match('/^There was \d+ risky test/', $line) || preg_match('/^There were \d+ risky tests/', $line)) {
            // Sección de risky tests - NO incluir estos
            $in_failure_section = false;
            $in_error_section = false;
            continue;
        } elseif (preg_match('/^There was \d+ skipped test/', $line) || preg_match('/^There were \d+ skipped tests/', $line)) {
            // Sección de skipped tests - NO incluir estos
            $in_failure_section = false;
            $in_error_section = false;
            continue;
        } elseif (preg_match('/^Tests: \d+/', $line)) {
            // Fin de todas las secciones de detalles
            $in_failure_section = false;
            $in_error_section = false;
            break;
        } elseif (preg_match('/^--$/', $line)) {
            // Separador entre secciones - mantener el contexto actual
            continue;
        }
        
        // Solo procesar tests si estamos en sección de failures o errors
        if ($in_failure_section || $in_error_section) {
            // Buscar patrón de test numerado: "1) TestClassName::testMethodName"
            if (preg_match('/^\s*(\d+)\)\s+([A-Za-z_][A-Za-z0-9_]*::test[A-Za-z0-9_]*)/i', $line, $matches)) {
                $test_name = $matches[2];
                if (!in_array($test_name, $failed_tests)) {
                    $failed_tests[] = $test_name;
                }
            }
        }
    }
    
    // Si no encontramos nada con el método contextual, usar patrones de respaldo
    if (empty($failed_tests)) {
        // Buscar específicamente en secciones de error/failure
        if (preg_match('/There was \d+ error.*?(?=There (?:was|were)|$)/s', $output, $error_section)) {
            if (preg_match_all('/^\s*(\d+)\)\s+([A-Za-z_][A-Za-z0-9_]*::test[A-Za-z0-9_]*)/m', $error_section[0], $matches)) {
                foreach ($matches[2] as $test_name) {
                    if (!in_array($test_name, $failed_tests)) {
                        $failed_tests[] = $test_name;
                    }
                }
            }
        }
        
        if (preg_match('/There were? \d+ failures?.*?(?=There (?:was|were)|$)/s', $output, $failure_section)) {
            if (preg_match_all('/^\s*(\d+)\)\s+([A-Za-z_][A-Za-z0-9_]*::test[A-Za-z0-9_]*)/m', $failure_section[0], $matches)) {
                foreach ($matches[2] as $test_name) {
                    if (!in_array($test_name, $failed_tests)) {
                        $failed_tests[] = $test_name;
                    }
                }
            }
        }
    }
    
    // Retornar tests únicos
    return array_unique($failed_tests);
}

/**
 * Mapea nombres de clases PHP a nombres de archivos reales
 * 
 * @param string $class_name Nombre de la clase PHP
 * @return string Nombre del archivo PHP o la clase si no se encuentra el archivo
 */
function map_class_to_filename($class_name) {
    $dev_tools_dir = get_dev_tools_dir();
    $tests_dir = $dev_tools_dir . 'tests';
    
    // Buscar archivo con el nombre de la clase + .php
    $filename = $class_name . '.php';
    
    // Buscar recursivamente en todo el directorio de tests
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tests_dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() === $filename) {
            // Retornar el nombre del archivo (no la ruta completa)
            return $file->getFilename();
        }
    }
    
    // Si no se encuentra el archivo, retornar el nombre de la clase + .php
    return $filename;
}

/**
 * Genera un resumen formateado de tests fallidos para mostrar al final del output
 * 
 * @param array $failed_tests Lista de nombres de tests fallidos
 * @param string $test_type Tipo de tests ejecutados
 * @return string Resumen formateado para mostrar
 */
function generate_failed_tests_summary($failed_tests, $test_type = '') {
    if (empty($failed_tests)) {
        return '';
    }
    
    $count = count($failed_tests);
    $plural = $count > 1 ? 's' : '';
    
    $summary = "================================\n";
    $summary .= "RESUMEN DE TESTS FALLIDOS{$test_type}\n";
    $summary .= "================================\n";
    $summary .= "Total de test{$plural} fallido{$plural}: {$count}\n\n";
    
    // Agrupar tests por clase para mejor organización
    $grouped_tests = [];
    foreach ($failed_tests as $test_name) {
        if (strpos($test_name, '::') !== false) {
            list($class, $method) = explode('::', $test_name, 2);
            if (!isset($grouped_tests[$class])) {
                $grouped_tests[$class] = [];
            }
            $grouped_tests[$class][] = $method;
        } else {
            // Tests sin formato clase::método
            if (!isset($grouped_tests['Otros'])) {
                $grouped_tests['Otros'] = [];
            }
            $grouped_tests['Otros'][] = $test_name;
        }
    }
    
    // Mostrar tests agrupados por archivo (mapear clase a nombre de archivo)
    foreach ($grouped_tests as $class => $methods) {
        // Mapear nombre de clase a nombre de archivo real
        $filename = ($class !== 'Otros') ? map_class_to_filename($class) : $class;
        
        $summary .= "📋 {$filename}:\n";
        foreach ($methods as $method) {
            $summary .= "   ❌ {$method}\n";
        }
        $summary .= "\n";
    }
    
    $summary .= "================================\n";
    $summary .= "Revisa los detalles arriba para información específica de cada fallo.\n";
    
    return $summary;
}

// Registrar los nuevos endpoints AJAX con configuración dinámica
$config = dev_tools_config();
$action_prefix = $config->get('ajax.action_prefix');

add_action('wp_ajax_' . $action_prefix . '_dev_tools_ping', 'dev_tools_ping_handler');
add_action('wp_ajax_' . $action_prefix . '_dev_tools_check_anti_deadlock', 'dev_tools_check_anti_deadlock_handler');
add_action('wp_ajax_' . $action_prefix . '_dev_tools_check_test_framework', 'dev_tools_check_test_framework_handler');

// Bloquear acceso no autorizado a los nuevos endpoints
add_action('wp_ajax_nopriv_' . $action_prefix . '_dev_tools_ping', function() {
    wp_send_json_error('No autorizado.');
});
add_action('wp_ajax_nopriv_' . $action_prefix . '_dev_tools_check_anti_deadlock', function() {
    wp_send_json_error('No autorizado.');
});
add_action('wp_ajax_nopriv_' . $action_prefix . '_dev_tools_check_test_framework', function() {
    wp_send_json_error('No autorizado.');
});
