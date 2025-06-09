<?php
/**
 * Clase base abstracta para módulos de Dev Tools - Arquitectura 3.0
 * Proporciona funcionalidad común para todos los módulos
 * 
 * @package DevTools\Core
 * @version 3.0.0
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase base que implementa funcionalidad común para módulos
 * Los módulos específicos deben heredar de esta clase
 */
abstract class DevToolsModuleBase implements DevToolsModuleInterface {
    
    /**
     * Configuración del sistema
     */
    protected $config;
    
    /**
     * Logger del módulo
     */
    protected $logger;
    
    /**
     * Estado del módulo
     */
    protected $status = [
        'active' => false,
        'initialized' => false,
        'status' => 'inactive',
        'errors' => []
    ];
    
    /**
     * Configuración del módulo
     */
    protected $module_config = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new DevToolsLogger();
    }
    
    /**
     * Inicializar el módulo (implementación base)
     */
    public function initialize(DevToolsConfig $config): bool {
        try {
            $this->config = $config;
            
            // Verificar si puede ejecutarse
            if (!$this->canRun()) {
                $this->status['status'] = 'cannot_run';
                return false;
            }
            
            // Cargar configuración del módulo
            $this->loadModuleConfig();
            
            // Validar configuración
            if (!$this->validateConfig($this->module_config)) {
                $this->status['status'] = 'invalid_config';
                $this->status['errors'][] = 'Invalid module configuration';
                return false;
            }
            
            // Inicialización específica del módulo
            if (!$this->initializeModule()) {
                $this->status['status'] = 'init_failed';
                return false;
            }
            
            $this->status['initialized'] = true;
            $this->status['status'] = 'initialized';
            
            $this->logger->logInternal("Module initialized: " . $this->getModuleName());
            return true;
            
        } catch (Exception $e) {
            $this->status['errors'][] = $e->getMessage();
            $this->status['status'] = 'error';
            $this->logger->logError("Module initialization failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el módulo puede ejecutarse (implementación base)
     */
    public function canRun(): bool {
        $info = $this->getModuleInfo();
        
        // Verificar capacidades del usuario (excepto en entorno de pruebas)
        if (!empty($info['capabilities']) && !$this->isTestEnvironment()) {
            foreach ($info['capabilities'] as $capability) {
                if (!current_user_can($capability)) {
                    $this->status['errors'][] = "Missing capability: {$capability}";
                    return false;
                }
            }
        }
        
        // Verificar dependencias
        if (!empty($info['dependencies'])) {
            foreach ($info['dependencies'] as $dependency) {
                if (!$this->isDependencyAvailable($dependency)) {
                    $this->status['errors'][] = "Missing dependency: {$dependency}";
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Activar módulo
     */
    public function onActivate(): bool {
        try {
            if (!$this->status['initialized']) {
                throw new Exception('Module not initialized');
            }
            
            // Activación específica del módulo
            if (!$this->activateModule()) {
                return false;
            }
            
            $this->status['active'] = true;
            $this->status['status'] = 'active';
            
            $this->logger->logInternal("Module activated: " . $this->getModuleName());
            return true;
            
        } catch (Exception $e) {
            $this->status['errors'][] = $e->getMessage();
            $this->logger->logError("Module activation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desactivar módulo
     */
    public function onDeactivate(): bool {
        try {
            // Desactivación específica del módulo
            if (!$this->deactivateModule()) {
                return false;
            }
            
            $this->status['active'] = false;
            $this->status['status'] = 'inactive';
            
            $this->logger->logInternal("Module deactivated: " . $this->getModuleName());
            return true;
            
        } catch (Exception $e) {
            $this->status['errors'][] = $e->getMessage();
            $this->logger->logError("Module deactivation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estado del módulo
     */
    public function getStatus(): array {
        return $this->status;
    }
    
    /**
     * Obtener configuración por defecto (implementación base)
     */
    public function getDefaultConfig(): array {
        return [
            'enabled' => true,
            'debug' => false,
            'cache_enabled' => true,
            'cache_duration' => 3600
        ];
    }
    
    /**
     * Validar configuración (implementación base)
     */
    public function validateConfig(array $config): bool {
        // Validación básica
        if (!is_array($config)) {
            return false;
        }
        
        // Validar campos requeridos si los hay
        $required_fields = $this->getRequiredConfigFields();
        foreach ($required_fields as $field) {
            if (!isset($config[$field])) {
                $this->status['errors'][] = "Missing required config field: {$field}";
                return false;
            }
        }
        
        return $this->validateModuleConfig($config);
    }
    
    /**
     * Limpiar recursos del módulo
     */
    public function cleanup(): void {
        try {
            $this->cleanupModule();
            $this->status = [
                'active' => false,
                'initialized' => false,
                'status' => 'cleaned',
                'errors' => []
            ];
            
            $this->logger->logInternal("Module cleaned up: " . $this->getModuleName());
            
        } catch (Exception $e) {
            $this->logger->logError("Module cleanup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener nombre del módulo
     */
    protected function getModuleName(): string {
        $info = $this->getModuleInfo();
        return $info['name'] ?? get_class($this);
    }
    
    /**
     * Cargar configuración del módulo
     */
    protected function loadModuleConfig(): void {
        $default_config = $this->getDefaultConfig();
        $saved_config = $this->getSavedConfig();
        
        $this->module_config = array_merge($default_config, $saved_config);
    }
    
    /**
     * Obtener configuración guardada del módulo
     */
    protected function getSavedConfig(): array {
        $option_name = 'dev_tools_module_' . sanitize_key($this->getModuleName());
        return get_option($option_name, []);
    }
    
    /**
     * Guardar configuración del módulo
     */
    protected function saveConfig(array $config): bool {
        $option_name = 'dev_tools_module_' . sanitize_key($this->getModuleName());
        return update_option($option_name, $config);
    }
    
    /**
     * Verificar si una dependencia está disponible
     */
    protected function isDependencyAvailable(string $dependency): bool {
        // Verificar funciones
        if (strpos($dependency, 'function:') === 0) {
            $function_name = substr($dependency, 9);
            return function_exists($function_name);
        }
        
        // Verificar clases
        if (strpos($dependency, 'class:') === 0) {
            $class_name = substr($dependency, 6);
            return class_exists($class_name);
        }
        
        // Verificar plugins
        if (strpos($dependency, 'plugin:') === 0) {
            $plugin_file = substr($dependency, 7);
            return is_plugin_active($plugin_file);
        }
        
        // Verificar extensiones PHP
        if (strpos($dependency, 'extension:') === 0) {
            $extension_name = substr($dependency, 10);
            return extension_loaded($extension_name);
        }
        
        return true;
    }
    
    /**
     * Obtener valor de configuración
     */
    protected function getConfigValue(string $key, $default = null) {
        return $this->module_config[$key] ?? $default;
    }
    
    /**
     * Establecer valor de configuración
     */
    protected function setConfigValue(string $key, $value): void {
        $this->module_config[$key] = $value;
    }
    
    /**
     * Registrar comando AJAX para el módulo
     */
    protected function register_ajax_command(string $action, callable $callback): void {
        // DEBUG: Log registro de comando
        error_log('[DEV-TOOLS-DEBUG] Registrando comando AJAX: ' . $action . ' desde módulo: ' . $this->getModuleName());
        
        $ajax_handler = DevToolsAjaxHandler::getInstance();
        $ajax_handler->registerCommand($action, $callback);
        
        // DEBUG: Verificar que se registró
        error_log('[DEV-TOOLS-DEBUG] Comando ' . $action . ' registrado correctamente');
    }
    
    /**
     * Log interno del módulo
     */
    protected function log_internal(string $message, $data = null): void {
        $this->logger->logInternal($message, $data);
    }
    
    /**
     * Log externo del módulo
     */
    protected function log_external(string $message, string $type = 'info'): void {
        $this->logger->logExternal($message, $type);
    }
    
    // ========================================
    // MÉTODOS ABSTRACTOS - DEBEN SER IMPLEMENTADOS POR MÓDULOS ESPECÍFICOS
    // ========================================
    
    /**
     * Inicialización específica del módulo
     * Implementar lógica específica de inicialización
     */
    abstract protected function initializeModule(): bool;
    
    /**
     * Activación específica del módulo
     * Implementar lógica específica de activación
     */
    abstract protected function activateModule(): bool;
    
    /**
     * Desactivación específica del módulo
     * Implementar lógica específica de desactivación
     */
    abstract protected function deactivateModule(): bool;
    
    /**
     * Limpieza específica del módulo
     * Implementar lógica específica de limpieza
     */
    abstract protected function cleanupModule(): void;
    
    /**
     * Validación específica de configuración del módulo
     * Implementar validaciones específicas
     */
    abstract protected function validateModuleConfig(array $config): bool;
    
    /**
     * Obtener campos de configuración requeridos
     * Devolver array con nombres de campos obligatorios
     */
    abstract protected function getRequiredConfigFields(): array;
    
    /**
     * Detectar si estamos en un entorno de pruebas
     */
    protected function isTestEnvironment(): bool {
        return (
            defined('WP_TESTS_CONFIG_FILE_PATH') ||
            defined('PHPUNIT_COMPOSER_INSTALL') ||
            (defined('WP_DEBUG') && WP_DEBUG && isset($_ENV['DEV_TOOLS_TEST_MODE'])) ||
            (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'development') ||
            strpos($_SERVER['SCRIPT_NAME'] ?? '', 'phpunit') !== false
        );
    }
}
