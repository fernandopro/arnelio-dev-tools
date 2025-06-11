<?php
/**
 * Dev-Tools Test Data Factory
 * 
 * Factory para crear datos de prueba consistentes
 * Compatible con WordPress test environment
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

class DevToolsTestDataFactory {
    
    private $created_data = [];
    
    /**
     * Crear datos de prueba para conexión de base de datos
     */
    public function create_database_test_data() {
        return [
            'valid_connection' => [
                'host' => 'localhost',
                'dbname' => 'test_db',
                'user' => 'test_user',
                'password' => 'test_pass',
                'charset' => 'utf8mb4'
            ],
            'invalid_connection' => [
                'host' => 'nonexistent_host',
                'dbname' => 'invalid_db',
                'user' => 'invalid_user',
                'password' => 'invalid_pass',
                'charset' => 'utf8mb4'
            ],
            'local_wp_socket' => [
                'socket_path' => '/tmp/test_mysql.sock',
                'dbname' => 'test_local_db',
                'user' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]
        ];
    }
    
    /**
     * Crear datos de prueba para detección de URLs
     */
    public function create_url_detection_test_data() {
        return [
            'local_wp_engine' => [
                'http_host' => 'test-site.local',
                'server_name' => 'test-site.local',
                'request_uri' => '/wp-admin/admin.php?page=dev-tools',
                'https' => null,
                'expected_url' => 'http://test-site.local'
            ],
            'localhost_port' => [
                'http_host' => 'localhost:3000',
                'server_name' => 'localhost',
                'server_port' => '3000',
                'request_uri' => '/',
                'https' => null,
                'expected_url' => 'http://localhost:3000'
            ],
            'production_https' => [
                'http_host' => 'example.com',
                'server_name' => 'example.com',
                'server_port' => '443',
                'request_uri' => '/wp-admin/',
                'https' => 'on',
                'expected_url' => 'https://example.com'
            ]
        ];
    }
    
    /**
     * Crear configuración de prueba para módulos
     */
    public function create_module_test_config() {
        return [
            'test_module_config' => [
                'name' => 'Test Module',
                'version' => '1.0.0',
                'enabled' => true,
                'dependencies' => ['DatabaseConnectionModule'],
                'settings' => [
                    'debug' => true,
                    'cache_enabled' => false
                ]
            ]
        ];
    }
    
    /**
     * Crear datos de prueba para AJAX
     */
    public function create_ajax_test_data() {
        return [
            'valid_request' => [
                'action' => 'dev_tools_ajax',
                'nonce' => wp_create_nonce('dev_tools_nonce'),
                'command' => 'test_connection',
                'data' => ['debug' => true]
            ],
            'invalid_nonce' => [
                'action' => 'dev_tools_ajax',
                'nonce' => 'invalid_nonce',
                'command' => 'test_connection',
                'data' => []
            ],
            'missing_command' => [
                'action' => 'dev_tools_ajax',
                'nonce' => wp_create_nonce('dev_tools_nonce'),
                'data' => []
            ]
        ];
    }
    
    /**
     * Crear archivo temporal de configuración wp-config
     */
    public function create_temp_wp_config($options = []) {
        $defaults = [
            'db_name' => 'test_database',
            'db_user' => 'test_user',
            'db_password' => 'test_password',
            'db_host' => 'localhost',
            'site_url' => 'http://example.org',
            'home_url' => 'http://example.org'
        ];
        
        $config = array_merge($defaults, $options);
        
        $wp_config_content = "<?php\n";
        $wp_config_content .= "define('DB_NAME', '{$config['db_name']}');\n";
        $wp_config_content .= "define('DB_USER', '{$config['db_user']}');\n";
        $wp_config_content .= "define('DB_PASSWORD', '{$config['db_password']}');\n";
        $wp_config_content .= "define('DB_HOST', '{$config['db_host']}');\n";
        
        if (isset($config['site_url'])) {
            $wp_config_content .= "define('WP_SITEURL', '{$config['site_url']}');\n";
        }
        
        if (isset($config['home_url'])) {
            $wp_config_content .= "define('WP_HOME', '{$config['home_url']}');\n";
        }
        
        $temp_file = tempnam(sys_get_temp_dir(), 'wp_config_test');
        file_put_contents($temp_file, $wp_config_content);
        
        $this->created_data['wp_config_files'][] = $temp_file;
        
        return $temp_file;
    }
    
    /**
     * Crear socket MySQL temporal para tests
     */
    public function create_temp_mysql_socket() {
        $socket_dir = sys_get_temp_dir() . '/dev_tools_test_sockets';
        
        if (!is_dir($socket_dir)) {
            mkdir($socket_dir, 0755, true);
        }
        
        $socket_path = $socket_dir . '/mysql_test.sock';
        
        // Crear archivo vacío para simular socket
        touch($socket_path);
        chmod($socket_path, 0777);
        
        $this->created_data['socket_files'][] = $socket_path;
        
        return $socket_path;
    }
    
    /**
     * Crear archivo de configuración Local by WP Engine
     */
    public function create_temp_local_config($site_name = 'test-site') {
        $config_data = [
            'id' => 'test123',
            'name' => $site_name,
            'host' => $site_name . '.local',
            'ports' => [
                'http' => 80,
                'https' => 443
            ],
            'mysql' => [
                'port' => 3306,
                'socket' => "/tmp/test-sockets/{$site_name}/mysql.sock"
            ]
        ];
        
        $temp_file = tempnam(sys_get_temp_dir(), 'local_config_test');
        file_put_contents($temp_file, json_encode($config_data, JSON_PRETTY_PRINT));
        
        $this->created_data['config_files'][] = $temp_file;
        
        return $temp_file;
    }
    
    /**
     * Simular estructura de archivos de Local by WP Engine
     */
    public function create_local_wp_structure($base_path = null) {
        if (!$base_path) {
            $base_path = sys_get_temp_dir() . '/local_wp_test_' . uniqid();
        }
        
        $structure = [
            'base_path' => $base_path,
            'site_path' => $base_path . '/Local Sites/test-site/app/public',
            'logs_path' => $base_path . '/Local Sites/test-site/logs',
            'config_path' => $base_path . '/Local Sites/test-site/conf',
            'socket_path' => $base_path . '/Library/Application Support/Local/run/testsite/mysql/mysqld.sock'
        ];
        
        // Crear directorios
        foreach ($structure as $key => $path) {
            if ($key !== 'base_path') {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                // Crear archivos/sockets simulados
                if (strpos($key, 'socket') !== false) {
                    touch($path);
                    chmod($path, 0777);
                } elseif (strpos($key, 'path') !== false && !is_dir($path)) {
                    mkdir($path, 0755, true);
                }
            }
        }
        
        $this->created_data['directory_structures'][] = $structure;
        
        return $structure;
    }
    
    /**
     * Crear datos de sistema para tests de información
     */
    public function create_system_info_test_data() {
        return [
            'wordpress' => [
                'version' => '6.5.0',
                'multisite' => false,
                'debug' => true,
                'memory_limit' => '256M'
            ],
            'php' => [
                'version' => '8.2.0',
                'memory_limit' => '512M',
                'max_execution_time' => '300',
                'extensions' => ['pdo', 'mysqli', 'curl', 'json']
            ],
            'server' => [
                'software' => 'nginx/1.20.0',
                'document_root' => '/var/www/html',
                'http_host' => 'example.org'
            ],
            'environment' => [
                'is_local_wp' => true,
                'router_mode' => 'site_domains',
                'detection_method' => 'local_config'
            ]
        ];
    }
    
    /**
     * Crear usuario de prueba con permisos específicos
     */
    public function create_test_user($role = 'administrator', $capabilities = []) {
        $user_data = [
            'user_login' => 'test_user_' . uniqid(),
            'user_email' => 'test' . uniqid() . '@example.org',
            'user_pass' => 'test_password_123',
            'role' => $role
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            throw new Exception('Failed to create test user: ' . $user_id->get_error_message());
        }
        
        // Añadir capacidades adicionales si se especifican
        $user = new WP_User($user_id);
        foreach ($capabilities as $cap) {
            $user->add_cap($cap);
        }
        
        $this->created_data['users'][] = $user_id;
        
        return $user_id;
    }
    
    /**
     * Limpiar todos los datos de prueba creados
     */
    public function cleanup() {
        // Limpiar archivos temporales
        foreach (['wp_config_files', 'socket_files', 'config_files'] as $file_type) {
            if (isset($this->created_data[$file_type])) {
                foreach ($this->created_data[$file_type] as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        }
        
        // Limpiar estructuras de directorios
        if (isset($this->created_data['directory_structures'])) {
            foreach ($this->created_data['directory_structures'] as $structure) {
                $this->remove_directory_recursive($structure['base_path']);
            }
        }
        
        // Limpiar usuarios de prueba
        if (isset($this->created_data['users'])) {
            foreach ($this->created_data['users'] as $user_id) {
                wp_delete_user($user_id);
            }
        }
        
        // Limpiar cache y transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dev_tools_test_%'");
        
        $this->created_data = [];
    }
    
    /**
     * Remover directorio recursivamente
     */
    private function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->remove_directory_recursive($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Obtener datos creados de un tipo específico
     */
    public function get_created_data($type = null) {
        if ($type) {
            return $this->created_data[$type] ?? [];
        }
        
        return $this->created_data;
    }
}
