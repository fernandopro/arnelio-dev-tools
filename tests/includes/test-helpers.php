<?php
/**
 * Dev-Tools Test Helpers
 * 
 * Funciones helper para facilitar los tests
 * Agnósticas y reutilizables
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

/**
 * Simular entorno Local by WP Engine
 */
function simulate_local_wp_engine() {
    $_SERVER['HTTP_HOST'] = 'test-site.local';
    $_SERVER['SERVER_NAME'] = 'test-site.local';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=dev-tools';
    $_SERVER['SCRIPT_FILENAME'] = '/Users/testuser/Local Sites/test-site/app/public/wp-content/plugins/test-plugin/test.php';
    $_SERVER['DOCUMENT_ROOT'] = '/Users/testuser/Local Sites/test-site/app/public';
    
    // Simular constantes de DB si no existen
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'localhost');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', 'test_database');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', 'root');
    }
    if (!defined('DB_PASSWORD')) {
        define('DB_PASSWORD', '');
    }
}

/**
 * Simular entorno de producción
 */
function simulate_production_environment() {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['SERVER_NAME'] = 'example.com';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['REQUEST_URI'] = '/wp-admin/';
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SCRIPT_FILENAME'] = '/var/www/html/wp-content/plugins/test-plugin/test.php';
    $_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
}

/**
 * Restaurar variables $_SERVER originales
 */
function restore_server_environment() {
    // Valores por defecto para testing
    $_SERVER['HTTP_HOST'] = 'example.org';
    $_SERVER['SERVER_NAME'] = 'example.org';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REQUEST_URI'] = '/';
    unset($_SERVER['HTTPS']);
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;
    $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);
}

/**
 * Crear mock de conexión PDO
 */
function create_mock_pdo_connection($should_fail = false) {
    if ($should_fail) {
        throw new PDOException('Mock connection failure');
    }
    
    // Para testing real necesitaríamos una base de datos de test
    // Por ahora retornamos null para indicar que es un mock
    return null;
}

/**
 * Verificar si PHPUnit está disponible
 */
function is_phpunit_available() {
    $possible_paths = [
        '/usr/local/bin/phpunit',
        '/usr/bin/phpunit',
        './vendor/bin/phpunit',
        dirname(__FILE__, 6) . '/vendor/bin/phpunit'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path) && is_executable($path)) {
            return $path;
        }
    }
    
    return false;
}

/**
 * Obtener path de WordPress test environment
 */
function get_wp_tests_dir() {
    $tests_dir = getenv('WP_TESTS_DIR');
    
    if (!$tests_dir) {
        $tests_dir = '/tmp/wordpress-tests-lib';
    }
    
    return $tests_dir;
}

/**
 * Verificar si el entorno de testing de WordPress está disponible
 */
function is_wp_test_environment_available() {
    $tests_dir = get_wp_tests_dir();
    return file_exists($tests_dir . '/includes/functions.php');
}

/**
 * Crear archivo temporal con contenido específico
 */
function create_temp_file_with_content($content, $extension = '.tmp') {
    $temp_file = tempnam(sys_get_temp_dir(), 'dev_tools_test') . $extension;
    file_put_contents($temp_file, $content);
    return $temp_file;
}

/**
 * Capturar output de una función
 */
function capture_output($callable, ...$args) {
    ob_start();
    $result = call_user_func_array($callable, $args);
    $output = ob_get_clean();
    
    return [
        'result' => $result,
        'output' => $output
    ];
}

/**
 * Medir tiempo de ejecución
 */
function measure_execution_time($callable, ...$args) {
    $start_time = microtime(true);
    $result = call_user_func_array($callable, $args);
    $end_time = microtime(true);
    
    return [
        'result' => $result,
        'execution_time' => ($end_time - $start_time) * 1000 // millisegundos
    ];
}

/**
 * Verificar si una URL es válida
 */
function is_valid_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Simular request AJAX para testing
 */
function simulate_ajax_request($command, $data = [], $valid_nonce = true) {
    $nonce = $valid_nonce ? wp_create_nonce('dev_tools_nonce') : 'invalid_nonce';
    
    $_POST = [
        'action' => 'dev_tools_ajax',
        'nonce' => $nonce,
        'command' => $command,
        'data' => $data
    ];
    
    $_REQUEST = $_POST;
    
    return $_POST;
}

/**
 * Limpiar datos de test del entorno
 */
function cleanup_test_environment() {
    // Limpiar variables globales
    unset($GLOBALS['dev_tools_test_data']);
    
    // Limpiar transients de test
    global $wpdb;
    if ($wpdb) {
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dev_tools_test_%'");
    }
    
    // Restaurar variables $_SERVER
    restore_server_environment();
    
    // Limpiar archivos temporales de test
    $temp_dir = sys_get_temp_dir();
    $test_files = glob($temp_dir . '/dev_tools_test*');
    
    foreach ($test_files as $file) {
        if (file_exists($file)) {
            if (is_dir($file)) {
                // Remover directorio recursivamente
                $iterator = new RecursiveDirectoryIterator($file, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
                
                foreach ($files as $fileInfo) {
                    if ($fileInfo->isDir()) {
                        rmdir($fileInfo->getRealPath());
                    } else {
                        unlink($fileInfo->getRealPath());
                    }
                }
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }
}

/**
 * Generar datos de test aleatorios
 */
function generate_random_test_data($type = 'string', $length = 10) {
    switch ($type) {
        case 'string':
            return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
            
        case 'email':
            return 'test' . uniqid() . '@example.org';
            
        case 'url':
            return 'http://test' . uniqid() . '.example.org';
            
        case 'path':
            return '/tmp/test_' . uniqid();
            
        case 'database':
            return 'test_db_' . uniqid();
            
        case 'user':
            return 'test_user_' . uniqid();
            
        default:
            return uniqid();
    }
}

/**
 * Verificar que una clase tiene los métodos requeridos
 */
function assert_class_has_methods($class_name, $required_methods) {
    if (!class_exists($class_name)) {
        throw new Exception("Class {$class_name} does not exist");
    }
    
    $reflection = new ReflectionClass($class_name);
    $existing_methods = array_map(function($method) {
        return $method->getName();
    }, $reflection->getMethods());
    
    $missing_methods = array_diff($required_methods, $existing_methods);
    
    if (!empty($missing_methods)) {
        throw new Exception("Class {$class_name} is missing methods: " . implode(', ', $missing_methods));
    }
    
    return true;
}

/**
 * Verificar estructura de array esperada
 */
function assert_array_structure($array, $expected_structure) {
    foreach ($expected_structure as $key => $type) {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Array is missing key: {$key}");
        }
        
        $actual_type = gettype($array[$key]);
        if ($actual_type !== $type) {
            throw new Exception("Array key {$key} should be {$type} but is {$actual_type}");
        }
    }
    
    return true;
}

/**
 * Helper para tests de performance
 */
function assert_performance_under($milliseconds, $callable, ...$args) {
    $measurement = measure_execution_time($callable, ...$args);
    
    if ($measurement['execution_time'] > $milliseconds) {
        throw new Exception(
            "Performance test failed: expected under {$milliseconds}ms but took {$measurement['execution_time']}ms"
        );
    }
    
    return $measurement['result'];
}

/**
 * Mock de funciones WordPress para testing unitario
 */
function mock_wordpress_functions() {
    // Solo definir si no existen (para evitar conflictos en integration tests)
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce($action) {
            return 'test_nonce_' . md5($action . 'test_salt');
        }
    }
    
    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            return true; // Para tests, asumimos que el usuario tiene permisos
        }
    }
    
    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data) {
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }
    }
    
    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data) {
            echo json_encode(['success' => false, 'data' => $data]);
            exit;
        }
    }
}

/**
 * Verificar que Dev-Tools está correctamente inicializado
 */
function assert_dev_tools_initialized() {
    if (!defined('DEV_TOOLS_LOADED')) {
        throw new Exception('Dev-Tools system is not loaded');
    }
    
    if (!class_exists('DevToolsLoader')) {
        throw new Exception('DevToolsLoader class is not available');
    }
    
    return true;
}

/**
 * Helper para debugging en tests
 */
function debug_test($message, $data = null) {
    if (defined('DEV_TOOLS_TEST_DEBUG') && DEV_TOOLS_TEST_DEBUG) {
        echo "\n[TEST DEBUG] {$message}";
        if ($data !== null) {
            echo "\n" . print_r($data, true);
        }
        echo "\n";
    }
}
