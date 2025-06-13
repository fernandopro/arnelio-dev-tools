<?php
/**
 * DatabaseConnection Module - Dev-Tools Arquitectura 3.0
 * 
 * Módulo agnóstico para conexiones MySQL con auto-detección de entorno
 * Compatible con Local by WP Engine, Docker, staging y producción
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

namespace DevTools\Modules;

use PDO;
use PDOException;
use Exception;

class DatabaseConnectionModule {
    
    private $connection = null;
    private $environment_info = [];
    private $debug_mode = false;
    
    public function __construct($debug = false) {
        $this->debug_mode = $debug;
        $this->detect_environment();
    }
    
    /**
     * Detecta el entorno de desarrollo actual
     */
    private function detect_environment() {
        $this->environment_info = [
            'is_local_wp' => $this->is_local_wp_engine(),
            'wp_db_host' => DB_HOST,
            'wp_db_name' => DB_NAME,
            'wp_db_user' => DB_USER,
            'socket_path' => $this->find_mysql_socket(),
            'php_version' => phpversion(),
            'wordpress_version' => get_bloginfo('version')
        ];
        
        if ($this->debug_mode) {
            error_log('🔧 DevTools DatabaseConnection - Environment detected: ' . json_encode($this->environment_info));
        }
    }
    
    /**
     * Detecta si estamos en Local by WP Engine
     */
    private function is_local_wp_engine() {
        // Verificaciones múltiples para detectar Local by WP Engine
        $indicators = [
            // Path típico de Local by WP Engine
            strpos(__FILE__, '/Local Sites/') !== false,
            // Socket path característico
            strpos(DB_HOST, 'Library/Application Support/Local') !== false,
            // Variables de entorno
            isset($_SERVER['LOCAL_WP']) || isset($_ENV['LOCAL_WP']),
            // Hostname típico
            DB_HOST === 'localhost' && $this->find_mysql_socket()
        ];
        
        return count(array_filter($indicators)) > 0;
    }
    
    /**
     * Busca el socket MySQL en ubicaciones típicas de Local by WP Engine
     */
    private function find_mysql_socket() {
        $possible_sockets = [
            // Socket específico proporcionado por el usuario
            '/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock',
            // Patrón general de Local by WP Engine
            '/Users/' . get_current_user() . '/Library/Application Support/Local/run/*/mysql/mysqld.sock',
            // Ubicaciones estándar de Unix
            '/tmp/mysql.sock',
            '/var/lib/mysql/mysql.sock',
            '/var/run/mysqld/mysqld.sock'
        ];
        
        foreach ($possible_sockets as $socket) {
            // Para patrones con wildcard, usar glob
            if (strpos($socket, '*') !== false) {
                $matches = glob($socket);
                if (!empty($matches) && file_exists($matches[0])) {
                    return $matches[0];
                }
            } elseif (file_exists($socket)) {
                return $socket;
            }
        }
        
        return null;
    }
    
    /**
     * Crea la conexión PDO optimizada para el entorno
     */
    public function get_connection() {
        if ($this->connection !== null) {
            return $this->connection;
        }
        
        try {
            $dsn = $this->build_dsn();
            $options = $this->get_pdo_options();
            
            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASSWORD,
                $options
            );
            
            if ($this->debug_mode) {
                error_log('✅ DevTools DatabaseConnection - Connected successfully with DSN: ' . $dsn);
            }
            
            return $this->connection;
            
        } catch (PDOException $e) {
            $error_msg = '❌ DevTools DatabaseConnection - Connection failed: ' . $e->getMessage();
            error_log($error_msg);
            
            if ($this->debug_mode) {
                throw new Exception($error_msg);
            }

            return null;
        }
    }
    
    /**
     * Construye el DSN apropiado para el entorno
     */
    private function build_dsn() {
        $charset = 'utf8mb4';
        $dbname = DB_NAME;
        
        // Para Local by WP Engine, priorizar socket
        if ($this->environment_info['is_local_wp'] && $this->environment_info['socket_path']) {
            return "mysql:unix_socket={$this->environment_info['socket_path']};dbname={$dbname};charset={$charset}";
        }
        
        // Fallback para otros entornos
        $host = DB_HOST;
        $port = '';
        
        // Extraer puerto si está especificado
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
            $port = ";port={$port}";
        }
        
        return "mysql:host={$host}{$port};dbname={$dbname};charset={$charset}";
    }
    
    /**
     * Opciones PDO optimizadas
     */
    private function get_pdo_options() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_PERSISTENT => false // No persistent en desarrollo local
        ];
    }
    
    /**
     * Ejecuta una consulta preparada
     */
    public function query($sql, $params = []) {
        $connection = $this->get_connection();
        if (!$connection) {
            throw new Exception('No database connection available');
        }
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }
    
    /**
     * Obtiene información del entorno para debugging
     */
    public function get_environment_info() {
        return $this->environment_info;
    }
    
    /**
     * Test de conexión con información detallada
     */
    public function test_connection() {
        $result = [
            'success' => false,
            'environment' => $this->environment_info,
            'dsn_used' => null,
            'error' => null,
            'server_info' => null
        ];
        
        try {
            $result['dsn_used'] = $this->build_dsn();
            $connection = $this->get_connection();
            
            if ($connection) {
                $result['success'] = true;
                $result['server_info'] = $connection->getAttribute(PDO::ATTR_SERVER_INFO);
                
                // Test simple query
                $stmt = $connection->query('SELECT VERSION() as version, NOW() as current_time');
                $result['test_query'] = $stmt->fetch();
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Cierra la conexión
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Destructor - limpia la conexión
     */
    public function __destruct() {
        $this->close();
    }
}
