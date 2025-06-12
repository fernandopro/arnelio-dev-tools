<?php
/**
 * Helpers y utilidades para testing
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 */

namespace DevTools\Tests;

class Helpers {

    /**
     * Generar datos de configuración de testing
     */
    public static function generate_test_config( $overrides = [] ) {
        $default_config = [
            'debug_mode' => true,
            'cache_enabled' => false,
            'log_level' => 'debug',
            'modules' => [
                'dashboard' => true,
                'system_info' => true,
                'ajax_tester' => false,
                'cache' => false,
                'logs' => false,
                'performance' => false
            ],
            'paths' => [
                'dev_tools_dir' => '/dev-tools/',
                'dev_tools_url' => 'http://example.org/wp-content/plugins/test/dev-tools/'
            ]
        ];
        
        return array_merge_recursive( $default_config, $overrides );
    }

    /**
     * Crear una respuesta AJAX simulada
     */
    public static function create_ajax_response( $success = true, $data = [] ) {
        return [
            'success' => $success,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Generar datos de sistema para testing
     */
    public static function generate_system_info_data() {
        return [
            'php_version' => '8.1.0',
            'wordpress_version' => '6.4.0',
            'mysql_version' => '8.0.35',
            'server_software' => 'nginx/1.20.0',
            'memory_limit' => '256M',
            'max_execution_time' => 300,
            'upload_max_filesize' => '64M',
            'post_max_size' => '64M',
            'plugins_active' => 15,
            'themes_available' => 3,
            'is_multisite' => false,
            'wp_debug' => true,
            'wp_debug_log' => true
        ];
    }

    /**
     * Crear archivos temporales para testing
     */
    public static function create_temp_file( $content = '', $extension = 'txt' ) {
        $temp_dir = sys_get_temp_dir();
        $filename = uniqid( 'dev_tools_test_' ) . '.' . $extension;
        $filepath = $temp_dir . '/' . $filename;
        
        file_put_contents( $filepath, $content );
        
        return $filepath;
    }

    /**
     * Limpiar archivos temporales
     */
    public static function cleanup_temp_files( $pattern = 'dev_tools_test_*' ) {
        $temp_dir = sys_get_temp_dir();
        $files = glob( $temp_dir . '/' . $pattern );
        
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
    }

    /**
     * Simular datos de performance
     */
    public static function generate_performance_data() {
        return [
            'page_load_time' => 1.2,
            'database_queries' => 25,
            'memory_usage' => '45M',
            'plugins_load_time' => 0.3,
            'theme_load_time' => 0.1,
            'cache_hit_ratio' => 85.5,
            'timestamp' => time()
        ];
    }

    /**
     * Verificar estructura de directorio
     */
    public static function verify_directory_structure( $base_path ) {
        $required_dirs = [
            'config',
            'includes', 
            'modules',
            'tests',
            'dist',
            'src'
        ];
        
        $missing_dirs = [];
        
        foreach ( $required_dirs as $dir ) {
            if ( ! is_dir( $base_path . '/' . $dir ) ) {
                $missing_dirs[] = $dir;
            }
        }
        
        return $missing_dirs;
    }

    /**
     * Verificar archivos requeridos
     */
    public static function verify_required_files( $base_path ) {
        $required_files = [
            'loader.php',
            'package.json',
            'composer.json',
            'webpack.config.js'
        ];
        
        $missing_files = [];
        
        foreach ( $required_files as $file ) {
            if ( ! file_exists( $base_path . '/' . $file ) ) {
                $missing_files[] = $file;
            }
        }
        
        return $missing_files;
    }

    /**
     * Generar datos de log para testing
     */
    public static function generate_log_data( $level = 'info', $count = 5 ) {
        $logs = [];
        $levels = [ 'error', 'warning', 'info', 'debug' ];
        
        for ( $i = 0; $i < $count; $i++ ) {
            $logs[] = [
                'timestamp' => time() - ( $i * 3600 ),
                'level' => $level === 'random' ? $levels[ array_rand( $levels ) ] : $level,
                'message' => 'Test log message ' . ( $i + 1 ),
                'context' => [
                    'module' => 'test_module',
                    'user_id' => 1,
                    'request_id' => uniqid()
                ]
            ];
        }
        
        return $logs;
    }
}
