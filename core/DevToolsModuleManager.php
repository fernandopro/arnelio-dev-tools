<?php
/**
 * Manager de módulos para Dev Tools - Arquitectura 3.0
 * Gestiona la carga, activación y comunicación entre módulos
 * 
 * @package DevTools\Core
 * @version 3.0.0
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestor centralizado de módulos del sistema dev-tools
 * Implementa patrón Registry para administrar módulos
 */
class DevToolsModuleManager {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Configuración del sistema
     */
    private $config;
    
    /**
     * Logger del sistema
     */
    private $logger;
    
    /**
     * Registro de módulos disponibles
     */
    private $modules = [];
    
    /**
     * Módulos inicializados
     */
    private $initialized_modules = [];
    
    /**
     * Módulos activos
     */
    private $active_modules = [];
    
    /**
     * Estado del manager
     */
    private $is_initialized = false;
    
    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        $this->config = dev_tools_config();
        $this->logger = new DevToolsLogger();
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar el gestor de módulos
     */
    public function initialize() {
        if ($this->is_initialized) {
            return true;
        }
        
        try {
            $this->logger->logInternal('Initializing Module Manager');
            
            // Cargar módulos disponibles
            $this->discoverModules();
            
            // Inicializar módulos core
            $this->initializeCoreModules();
            
            // Inicializar módulos opcionales
            $this->initializeOptionalModules();
            
            // Activar módulos habilitados
            $this->activateEnabledModules();
            
            $this->is_initialized = true;
            $this->logger->logInternal('Module Manager initialized successfully');
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->logError('Module Manager initialization failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar un módulo
     */
    public function registerModule(string $name, DevToolsModuleInterface $module) {
        if (isset($this->modules[$name])) {
            $this->logger->logError("Module already registered: {$name}");
            return false;
        }
        
        $this->modules[$name] = $module;
        $this->logger->logInternal("Module registered: {$name}");
        
        return true;
    }
    
    /**
     * Obtener un módulo
     */
    public function getModule(string $name): ?DevToolsModuleInterface {
        return $this->modules[$name] ?? null;
    }
    
    /**
     * Obtener todos los módulos
     */
    public function getModules(): array {
        return $this->modules;
    }
    
    /**
     * Obtener módulos activos
     */
    public function getActiveModules(): array {
        return $this->active_modules;
    }
    
    /**
     * Inicializar un módulo específico
     */
    public function initializeModule(string $name): bool {
        if (!isset($this->modules[$name])) {
            $this->logger->logError("Module not found: {$name}");
            return false;
        }
        
        if (isset($this->initialized_modules[$name])) {
            $this->logger->logInternal("Module already initialized: {$name}");
            return true;
        }
        
        $module = $this->modules[$name];
        
        if ($module->initialize($this->config)) {
            $this->initialized_modules[$name] = $module;
            $this->logger->logInternal("Module initialized: {$name}");
            return true;
        }
        
        $this->logger->logError("Failed to initialize module: {$name}");
        return false;
    }
    
    /**
     * Activar un módulo
     */
    public function activateModule(string $name): bool {
        if (!isset($this->initialized_modules[$name])) {
            if (!$this->initializeModule($name)) {
                return false;
            }
        }
        
        if (isset($this->active_modules[$name])) {
            $this->logger->logInternal("Module already active: {$name}");
            return true;
        }
        
        $module = $this->initialized_modules[$name];
        
        if ($module->onActivate()) {
            // Registrar hooks del módulo
            $module->registerHooks();
            
            // Registrar comandos AJAX si existe el handler
            if (class_exists('DevToolsAjaxHandler')) {
                $ajax_handler = DevToolsAjaxHandler::getInstance();
                $module->registerAjaxCommands($ajax_handler);
            }
            
            $this->active_modules[$name] = $module;
            $this->logger->logInternal("Module activated: {$name}");
            
            return true;
        }
        
        $this->logger->logError("Failed to activate module: {$name}");
        return false;
    }
    
    /**
     * Desactivar un módulo
     */
    public function deactivateModule(string $name): bool {
        if (!isset($this->active_modules[$name])) {
            $this->logger->logInternal("Module not active: {$name}");
            return true;
        }
        
        $module = $this->active_modules[$name];
        
        if ($module->onDeactivate()) {
            unset($this->active_modules[$name]);
            $this->logger->logInternal("Module deactivated: {$name}");
            return true;
        }
        
        $this->logger->logError("Failed to deactivate module: {$name}");
        return false;
    }
    
    /**
     * Obtener estado de todos los módulos
     */
    public function getModulesStatus(): array {
        $status = [];
        
        foreach ($this->modules as $name => $module) {
            $module_status = $module->getStatus();
            $module_info = $module->getModuleInfo();
            
            $status[$name] = [
                'info' => $module_info,
                'status' => $module_status,
                'initialized' => isset($this->initialized_modules[$name]),
                'active' => isset($this->active_modules[$name])
            ];
        }
        
        return $status;
    }
    
    /**
     * Limpiar todos los módulos
     */
    public function cleanup() {
        $this->logger->logInternal('Cleaning up modules');
        
        // Desactivar módulos activos
        foreach (array_keys($this->active_modules) as $name) {
            $this->deactivateModule($name);
        }
        
        // Limpiar módulos inicializados
        foreach ($this->initialized_modules as $name => $module) {
            $module->cleanup();
        }
        
        // Reset arrays
        $this->modules = [];
        $this->initialized_modules = [];
        $this->active_modules = [];
        $this->is_initialized = false;
        
        $this->logger->logInternal('Modules cleanup completed');
    }
    
    /**
     * Descubrir módulos disponibles
     */
    private function discoverModules() {
        $modules_path = $this->config->get('paths.dev_tools') . '/modules';
        
        if (!is_dir($modules_path)) {
            $this->logger->logInternal('Modules directory not found, creating it');
            wp_mkdir_p($modules_path);
            return;
        }
        
        // Buscar archivos de módulos
        $module_files = glob($modules_path . '/*Module.php');
        
        foreach ($module_files as $file) {
            $this->loadModuleFromFile($file);
        }
        
        $this->logger->logInternal('Module discovery completed. Found: ' . count($this->modules) . ' modules');
    }
    
    /**
     * Cargar módulo desde archivo
     */
    private function loadModuleFromFile(string $file) {
        try {
            require_once $file;
            
            $filename = basename($file, '.php');
            $class_name = $filename;
            
            if (class_exists($class_name)) {
                $reflection = new ReflectionClass($class_name);
                
                if ($reflection->implementsInterface('DevToolsModuleInterface')) {
                    $module = new $class_name();
                    $module_info = $module->getModuleInfo();
                    $module_name = $module_info['name'] ?? $class_name;
                    
                    $this->registerModule($module_name, $module);
                    $this->logger->logInternal("Loaded module from file: {$file}");
                } else {
                    $this->logger->logError("Class {$class_name} does not implement DevToolsModuleInterface");
                }
            } else {
                $this->logger->logError("Class {$class_name} not found in file: {$file}");
            }
            
        } catch (Exception $e) {
            $this->logger->logError("Failed to load module from file {$file}: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializar módulos core (obligatorios)
     */
    private function initializeCoreModules() {
        $core_modules = [
            'dashboard',
            'system_info',
            'cache',
            'ajax_tester'
        ];
        
        foreach ($core_modules as $module_name) {
            if (isset($this->modules[$module_name])) {
                $this->initializeModule($module_name);
            }
        }
    }
    
    /**
     * Inicializar módulos opcionales
     */
    private function initializeOptionalModules() {
        $enabled_modules = $this->getEnabledModules();
        
        foreach ($enabled_modules as $module_name) {
            if (isset($this->modules[$module_name]) && !isset($this->initialized_modules[$module_name])) {
                $this->initializeModule($module_name);
            }
        }
    }
    
    /**
     * Activar módulos habilitados
     */
    private function activateEnabledModules() {
        $enabled_modules = $this->getEnabledModules();
        
        foreach ($enabled_modules as $module_name) {
            if (isset($this->initialized_modules[$module_name])) {
                $this->activateModule($module_name);
            }
        }
    }
    
    /**
     * Obtener lista de módulos habilitados
     */
    private function getEnabledModules(): array {
        $default_enabled = [
            'dashboard',
            'system_info',
            'cache',
            'ajax_tester'
        ];
        
        $enabled_modules = get_option('dev_tools_enabled_modules', $default_enabled);
        
        return is_array($enabled_modules) ? $enabled_modules : $default_enabled;
    }
    
    /**
     * Habilitar módulo
     */
    public function enableModule(string $name): bool {
        $enabled_modules = $this->getEnabledModules();
        
        if (!in_array($name, $enabled_modules)) {
            $enabled_modules[] = $name;
            update_option('dev_tools_enabled_modules', $enabled_modules);
            
            // Si el manager ya está inicializado, activar el módulo inmediatamente
            if ($this->is_initialized && isset($this->modules[$name])) {
                $this->activateModule($name);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Deshabilitar módulo
     */
    public function disableModule(string $name): bool {
        $enabled_modules = $this->getEnabledModules();
        $key = array_search($name, $enabled_modules);
        
        if ($key !== false) {
            unset($enabled_modules[$key]);
            update_option('dev_tools_enabled_modules', array_values($enabled_modules));
            
            // Desactivar el módulo si está activo
            if (isset($this->active_modules[$name])) {
                $this->deactivateModule($name);
            }
            
            return true;
        }
        
        return false;
    }
}

// Inicializar el gestor de módulos
add_action('init', function() {
    $module_manager = DevToolsModuleManager::getInstance();
    $module_manager->initialize();
}, 20); // Prioridad 20 para ejecutar después del config y ajax handler
