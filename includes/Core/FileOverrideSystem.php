<?php
/**
 * File Override System - Similar a Child Themes de WordPress
 * 
 * Sistema que permite a cada plugin tener su propia carpeta 'plugin-dev-tools'
 * con archivos personalizados que sobreescriben los del framework core.
 * 
 * Jerarquía de carga:
 * 1. plugin-dev-tools/archivo.php (ESPECÍFICO del plugin)
 * 2. dev-tools/archivo.php (COMPARTIDO del framework)
 * 
 * @package DevTools
 * @version 3.0
 */

class FileOverrideSystem {
    
    private static $instance = null;
    private $parent_dir = '';      // Directorio dev-tools/
    private $child_dir = '';       // Directorio plugin-dev-tools/
    private $plugin_root = '';     // Directorio raíz del plugin
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Detecta automáticamente las rutas
     */
    private function __construct() {
        $this->detect_directories();
    }
    
    /**
     * Detecta automáticamente los directorios del sistema
     */
    private function detect_directories() {
        // Directorio dev-tools (parent)
        $this->parent_dir = realpath(__DIR__ . '/../../');
        
        // Directorio del plugin (navegamos hacia arriba desde dev-tools)
        $this->plugin_root = realpath($this->parent_dir . '/../');
        
        // Directorio plugin-dev-tools (child)
        $this->child_dir = $this->plugin_root . '/plugin-dev-tools';
        
        // Validar que las rutas son correctas
        if (!$this->parent_dir || !$this->plugin_root) {
            throw new Exception('No se pueden detectar las rutas del sistema de override');
        }
    }
    
    /**
     * Busca un archivo siguiendo la jerarquía de override
     * 
     * @param string $relative_path Ruta relativa desde dev-tools/ o plugin-dev-tools/
     * @return string|false Ruta absoluta del archivo encontrado o false
     */
    public function find_file($relative_path) {
        // Normalizar la ruta
        $relative_path = ltrim($relative_path, '/');
        
        // 1. Buscar primero en plugin-dev-tools/ (ESPECÍFICO)
        $child_file = $this->child_dir . '/' . $relative_path;
        if (file_exists($child_file)) {
            return $child_file;
        }
        
        // 2. Buscar en dev-tools/ (COMPARTIDO)
        $parent_file = $this->parent_dir . '/' . $relative_path;
        if (file_exists($parent_file)) {
            return $parent_file;
        }
        
        return false;
    }
    
    /**
     * Incluye un archivo con sistema de override
     * 
     * @param string $relative_path Ruta relativa del archivo
     * @param array $vars Variables a pasar al archivo incluido
     * @return mixed Resultado del include
     */
    public function include_file($relative_path, $vars = []) {
        $file_path = $this->find_file($relative_path);
        
        if (!$file_path) {
            throw new Exception("Archivo no encontrado: {$relative_path}");
        }
        
        // Extraer variables para el scope del archivo
        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }
        
        return include $file_path;
    }
    
    /**
     * Requiere un archivo con sistema de override
     * 
     * @param string $relative_path Ruta relativa del archivo
     * @param array $vars Variables a pasar al archivo
     * @return mixed Resultado del require
     */
    public function require_file($relative_path, $vars = []) {
        $file_path = $this->find_file($relative_path);
        
        if (!$file_path) {
            throw new Exception("Archivo requerido no encontrado: {$relative_path}");
        }
        
        // Extraer variables para el scope del archivo
        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }
        
        return require $file_path;
    }
    
    /**
     * Carga configuración con merge automático
     * 
     * @param string $config_file Archivo de configuración
     * @return array Configuración mergeada
     */
    public function load_config($config_file) {
        $config = [];
        
        // Cargar configuración base (parent)
        $parent_config_file = $this->parent_dir . '/' . $config_file;
        if (file_exists($parent_config_file)) {
            $parent_config = include $parent_config_file;
            if (is_array($parent_config)) {
                $config = $parent_config;
            }
        }
        
        // Mergear con configuración específica (child)
        $child_config_file = $this->child_dir . '/' . $config_file;
        if (file_exists($child_config_file)) {
            $child_config = include $child_config_file;
            if (is_array($child_config)) {
                $config = array_merge($config, $child_config);
            }
        }
        
        return $config;
    }
    
    /**
     * Verifica si existe un override para un archivo
     * 
     * @param string $relative_path Ruta relativa del archivo
     * @return bool True si existe override, false si usa el parent
     */
    public function has_override($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        $child_file = $this->child_dir . '/' . $relative_path;
        return file_exists($child_file);
    }
    
    /**
     * Crea un override copiando desde el parent
     * 
     * @param string $relative_path Ruta relativa del archivo a sobrescribir
     * @param bool $force Forzar sobrescritura si ya existe
     * @return bool Success
     */
    public function create_override($relative_path, $force = false) {
        $relative_path = ltrim($relative_path, '/');
        
        $parent_file = $this->parent_dir . '/' . $relative_path;
        $child_file = $this->child_dir . '/' . $relative_path;
        
        // Verificar que el archivo parent existe
        if (!file_exists($parent_file)) {
            throw new Exception("Archivo parent no existe: {$relative_path}");
        }
        
        // Verificar si ya existe y no forzar
        if (file_exists($child_file) && !$force) {
            throw new Exception("Override ya existe: {$relative_path}. Usa force=true para sobrescribir.");
        }
        
        // Crear directorio si no existe
        $child_dir = dirname($child_file);
        if (!file_exists($child_dir)) {
            mkdir($child_dir, 0755, true);
        }
        
        // Copiar archivo y añadir header explicativo
        $content = file_get_contents($parent_file);
        
        // Añadir header explicativo para archivos PHP
        if (pathinfo($parent_file, PATHINFO_EXTENSION) === 'php') {
            $header = "<?php\n";
            $header .= "/**\n";
            $header .= " * OVERRIDE FILE - Específico del Plugin\n";
            $header .= " * \n";
            $header .= " * Este archivo sobrescribe: dev-tools/{$relative_path}\n";
            $header .= " * \n";
            $header .= " * Personaliza este archivo según las necesidades específicas del plugin.\n";
            $header .= " * Los cambios aquí NO afectarán otros plugins que usen dev-tools.\n";
            $header .= " * \n";
            $header .= " * @created " . date('Y-m-d H:i:s') . "\n";
            $header .= " * @source dev-tools/{$relative_path}\n";
            $header .= " */\n\n";
            
            // Remover el <?php opening tag del contenido original si existe
            $content = preg_replace('/^<\?php\s*/', '', $content);
            $content = $header . $content;
        }
        
        return file_put_contents($child_file, $content) !== false;
    }
    
    /**
     * Obtiene información del sistema de override
     * 
     * @return array Información del sistema
     */
    public function get_system_info() {
        $overrides = [];
        
        // Escanear directorios para encontrar overrides
        if (is_dir($this->child_dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->child_dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relative_path = str_replace($this->child_dir . '/', '', $file->getPathname());
                    $overrides[] = $relative_path;
                }
            }
        }
        
        return [
            'parent_dir' => $this->parent_dir,
            'child_dir' => $this->child_dir,
            'plugin_root' => $this->plugin_root,
            'parent_exists' => is_dir($this->parent_dir),
            'child_exists' => is_dir($this->child_dir),
            'overrides_count' => count($overrides),
            'overrides' => $overrides
        ];
    }
    
    /**
     * Crea la estructura completa de plugin-dev-tools/
     * Replica exactamente la estructura de dev-tools/
     * 
     * @return bool Success
     */
    public function create_child_structure() {
        // Estructura básica principal
        $basic_structure = [
            'config',
            'modules',
            'templates',
            'logs',
            'reports'
        ];
        
        // Replicar exactamente la estructura de tests/ desde dev-tools/
        $tests_structure = $this->replicate_tests_structure();
        
        // Crear directorios básicos
        foreach ($basic_structure as $dir) {
            $full_path = $this->child_dir . '/' . $dir;
            if (!is_dir($full_path)) {
                mkdir($full_path, 0755, true);
            }
        }
        
        // Crear estructura de tests idéntica
        foreach ($tests_structure as $dir) {
            $full_path = $this->child_dir . '/' . $dir;
            if (!is_dir($full_path)) {
                mkdir($full_path, 0755, true);
            }
        }
        
        // Copiar archivos esenciales para el sistema de testing
        $this->copy_essential_files();
        
        return true;
    }
    
    /**
     * Replica exactamente la estructura de tests/ desde dev-tools/
     */
    private function replicate_tests_structure() {
        $tests_dirs = [];
        $source_tests_dir = $this->parent_dir . '/tests';
        
        if (!is_dir($source_tests_dir)) {
            return ['tests']; // Fallback básico
        }
        
        // Agregar directorio tests principal
        $tests_dirs[] = 'tests';
        
        // Escanear recursivamente la estructura de tests/
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_tests_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $path => $dir) {
            if ($dir->isDir()) {
                // Obtener ruta relativa sin el separador inicial
                $relative_path = substr($path, strlen($source_tests_dir));
                $relative_path = ltrim($relative_path, '/\\');
                
                if (!empty($relative_path)) {
                    $tests_dirs[] = 'tests/' . $relative_path;
                }
            }
        }
        
        return $tests_dirs;
    }
    
    /**
     * Copia archivos esenciales para el funcionamiento del sistema de testing
     */
    private function copy_essential_files() {
        $essential_files = [
            'tests/bootstrap.php',
            'tests/wp-tests-config.php'
        ];
        
        foreach ($essential_files as $file) {
            $source = $this->parent_dir . '/' . $file;
            $destination = $this->child_dir . '/' . $file;
            
            if (file_exists($source) && !file_exists($destination)) {
                // Asegurar que el directorio de destino existe
                $dir = dirname($destination);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                // Copiar archivo con header de override
                $content = file_get_contents($source);
                $header = "<?php\n/**\n * OVERRIDE FILE - Específico del Plugin\n";
                $header .= " * Copiado desde: {$file}\n";
                $header .= " * Fecha: " . date('Y-m-d H:i:s') . "\n";
                $header .= " */\n\n";
                
                // Si es un archivo PHP, agregar header después de la etiqueta de apertura
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $content = preg_replace('/^<\?php\s*/', $header, $content, 1);
                } else {
                    $content = $header . $content;
                }
                
                file_put_contents($destination, $content);
            }
        }
    }

    /**
     * Getters para las rutas del sistema
     */
    public function get_parent_dir() { return $this->parent_dir; }
    public function get_child_dir() { return $this->child_dir; }
    public function get_plugin_root() { return $this->plugin_root; }
}
