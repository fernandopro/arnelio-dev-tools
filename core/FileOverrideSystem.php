<?php
/**
 * Sistema de Override de Archivos - Similar a Child Themes de WordPress
 * 
 * Implementa jerarquía de carga: plugin-dev-tools/ → dev-tools/
 * Similar a como WordPress carga: child-theme/ → parent-theme/
 * 
 * @package DevTools
 * @version 3.0.0
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sistema de override de archivos tipo child theme
 * 
 * Lógica de carga jerárquica:
 * 1. Busca en plugin-dev-tools/ (específico del plugin)
 * 2. Si no existe, busca en dev-tools/ (compartido)
 * 3. Permite override completo de cualquier archivo
 */
class DevToolsFileOverrideSystem {
    
    /**
     * Directorio padre (dev-tools - compartido)
     */
    private $parent_dir;
    
    /**
     * Directorio hijo (plugin-dev-tools - específico)
     */
    private $child_dir;
    
    /**
     * Plugin host info
     */
    private $host_plugin;
    
    /**
     * Constructor
     */
    public function __construct($host_plugin_info) {
        $this->host_plugin = $host_plugin_info;
        $this->parent_dir = $host_plugin_info['dir_path'] . '/dev-tools';
        $this->child_dir = $host_plugin_info['dir_path'] . '/plugin-dev-tools';
        
        $this->init();
    }
    
    /**
     * Inicializar el sistema
     */
    private function init() {
        // Crear directorio hijo si no existe
        if (!file_exists($this->child_dir)) {
            wp_mkdir_p($this->child_dir);
            $this->create_child_structure();
        }
    }
    
    /**
     * Crear estructura básica del directorio hijo
     */
    private function create_child_structure() {
        $directories = [
            'modules',
            'templates', 
            'tests',
            'logs',
            'reports',
            'fixtures'
        ];
        
        foreach ($directories as $dir) {
            wp_mkdir_p($this->child_dir . '/' . $dir);
        }
        
        // Crear archivo README
        $readme_content = $this->generate_child_readme();
        file_put_contents($this->child_dir . '/README.md', $readme_content);
    }
    
    /**
     * Buscar archivo con lógica de override
     * 
     * @param string $relative_path Ruta relativa desde dev-tools/
     * @return string|false Ruta completa del archivo o false si no existe
     */
    public function locate_file($relative_path) {
        // 1. Buscar primero en directorio hijo (plugin-specific)
        $child_file = $this->child_dir . '/' . $relative_path;
        if (file_exists($child_file)) {
            return $child_file;
        }
        
        // 2. Buscar en directorio padre (compartido)
        $parent_file = $this->parent_dir . '/' . $relative_path;
        if (file_exists($parent_file)) {
            return $parent_file;
        }
        
        return false;
    }
    
    /**
     * Incluir archivo con lógica de override
     * 
     * @param string $relative_path Ruta relativa
     * @param bool $once Si usar include_once
     * @return mixed Resultado del include
     */
    public function include_file($relative_path, $once = true) {
        $file_path = $this->locate_file($relative_path);
        
        if (!$file_path) {
            return false;
        }
        
        if ($once) {
            return include_once $file_path;
        } else {
            return include $file_path;
        }
    }
    
    /**
     * Cargar configuración con override
     * 
     * @param string $config_name Nombre del archivo de configuración
     * @return array Configuración mergeada
     */
    public function load_config($config_name = 'config.php') {
        $config = [];
        
        // Cargar configuración padre (base)
        $parent_config_file = $this->locate_file($config_name);
        if ($parent_config_file && strpos($parent_config_file, $this->parent_dir) === 0) {
            $parent_config = include $parent_config_file;
            if (is_array($parent_config)) {
                $config = $parent_config;
            }
        }
        
        // Cargar configuración hijo (override)
        $child_config_file = $this->child_dir . '/' . $config_name;
        if (file_exists($child_config_file)) {
            $child_config = include $child_config_file;
            if (is_array($child_config)) {
                $config = $this->deep_array_merge($config, $child_config);
            }
        }
        
        return $config;
    }
    
    /**
     * Cargar template con override
     * 
     * @param string $template_name Nombre del template
     * @param array $vars Variables para el template
     */
    public function load_template($template_name, $vars = []) {
        $template_file = $this->locate_file("templates/{$template_name}");
        
        if (!$template_file) {
            return false;
        }
        
        // Extraer variables para el template
        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }
        
        include $template_file;
        return true;
    }
    
    /**
     * Obtener URL de archivo con override
     * 
     * @param string $relative_path Ruta relativa
     * @return string URL del archivo
     */
    public function get_file_url($relative_path) {
        $file_path = $this->locate_file($relative_path);
        
        if (!$file_path) {
            return '';
        }
        
        // Convertir path absoluto a URL
        if (strpos($file_path, $this->child_dir) === 0) {
            // Archivo en directorio hijo
            $relative_to_child = str_replace($this->child_dir, '', $file_path);
            return $this->host_plugin['dir_url'] . 'plugin-dev-tools' . $relative_to_child;
        } else {
            // Archivo en directorio padre  
            $relative_to_parent = str_replace($this->parent_dir, '', $file_path);
            return $this->host_plugin['dir_url'] . 'dev-tools' . $relative_to_parent;
        }
    }
    
    /**
     * Verificar si un archivo tiene override
     * 
     * @param string $relative_path Ruta relativa
     * @return bool True si existe override
     */
    public function has_override($relative_path) {
        $child_file = $this->child_dir . '/' . $relative_path;
        return file_exists($child_file);
    }
    
    /**
     * Listar todos los overrides existentes
     * 
     * @return array Lista de archivos con override
     */
    public function list_overrides() {
        $overrides = [];
        
        if (!is_dir($this->child_dir)) {
            return $overrides;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->child_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative_path = str_replace($this->child_dir . '/', '', $file->getPathname());
                $overrides[] = $relative_path;
            }
        }
        
        return $overrides;
    }
    
    /**
     * Migrar archivo desde parent a child para customización
     * 
     * @param string $relative_path Ruta relativa del archivo
     * @return bool True si se migró correctamente
     */
    public function migrate_to_override($relative_path) {
        $parent_file = $this->parent_dir . '/' . $relative_path;
        $child_file = $this->child_dir . '/' . $relative_path;
        
        if (!file_exists($parent_file)) {
            return false;
        }
        
        // Crear directorio si no existe
        $child_dir = dirname($child_file);
        if (!is_dir($child_dir)) {
            wp_mkdir_p($child_dir);
        }
        
        // Copiar archivo para customización
        $copied = copy($parent_file, $child_file);
        
        if ($copied) {
            // Añadir header explicativo
            $content = file_get_contents($child_file);
            $header = $this->generate_override_header($relative_path);
            file_put_contents($child_file, $header . "\n" . $content);
        }
        
        return $copied;
    }
    
    /**
     * Generar header para archivo override
     */
    private function generate_override_header($relative_path) {
        $plugin_name = $this->host_plugin['name'];
        $date = date('Y-m-d H:i:s');
        
        return "<?php\n/**\n * OVERRIDE ARCHIVO: {$relative_path}\n * Plugin: {$plugin_name}\n * Creado: {$date}\n * \n * Este archivo override el original en dev-tools/\n * Modifica según necesidades específicas del plugin\n */";
    }
    
    /**
     * Generar README para directorio hijo
     */
    private function generate_child_readme() {
        $plugin_name = $this->host_plugin['name'];
        $plugin_slug = $this->host_plugin['slug'];
        
        return <<<EOF
# Plugin Dev-Tools - {$plugin_name}

## 🎯 Propósito

Este directorio contiene **overrides específicos** para el plugin **{$plugin_name}**.

Funciona similar a los **child themes** de WordPress:
- Los archivos aquí **SOBRESCRIBEN** los del directorio `dev-tools/`
- Permite customización específica sin modificar el core compartido
- Se mantiene independiente del submódulo git

## 🏗️ Jerarquía de Carga

1. **Primero busca aquí** (`plugin-dev-tools/`)
2. **Luego en** `dev-tools/` (fallback)

## 📁 Estructura

- `modules/` - Módulos específicos del plugin
- `templates/` - Templates customizados
- `tests/` - Tests específicos del plugin  
- `logs/` - Logs locales
- `reports/` - Reports de testing
- `fixtures/` - Datos de prueba específicos

## 🚀 Uso

### Crear Override de un Archivo
```php
// Migrar archivo para customización
\$override_system->migrate_to_override('config.php');
\$override_system->migrate_to_override('modules/SystemInfoModule.php');
```

### Cargar con Override
```php
// Carga automáticamente desde plugin-dev-tools/ o dev-tools/
\$config = \$override_system->load_config('config.php');
\$override_system->include_file('modules/CustomModule.php');
```

## ⚠️ Importante

- Archivos aquí son **específicos del plugin {$plugin_slug}**
- NO se comparten con otros plugins que usen dev-tools
- Modificaciones seguras sin afectar el core compartido

---
**Generado automáticamente por DevTools Override System**
EOF;
    }
    
    /**
     * Merge profundo de arrays
     */
    private function deep_array_merge($array1, $array2) {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->deep_array_merge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * Obtener información de directorios
     */
    public function get_directory_info() {
        return [
            'parent_dir' => $this->parent_dir,
            'child_dir' => $this->child_dir,
            'parent_exists' => is_dir($this->parent_dir),
            'child_exists' => is_dir($this->child_dir),
            'overrides_count' => count($this->list_overrides())
        ];
    }
}
