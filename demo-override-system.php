<?php
/**
 * DEMOSTRACIÓN - Sistema de Override tipo Child Theme
 * 
 * Este archivo demuestra cómo funciona el nuevo sistema de override
 * similar a los child themes de WordPress
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar sistema
require_once __DIR__ . '/config.php';

/**
 * Clase de demostración del sistema de override
 */
class DevToolsOverrideDemo {
    
    private $config;
    
    public function __construct() {
        $this->config = DevToolsConfig::getInstance();
    }
    
    /**
     * Ejecutar todas las demostraciones
     */
    public function run_all_demos() {
        echo "<h1>🎯 Demostración - Sistema de Override tipo Child Theme</h1>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 20px 0;'>\n";
        
        $this->demo_directory_info();
        $this->demo_file_location();
        $this->demo_override_creation();
        $this->demo_config_loading();
        $this->demo_template_loading();
        
        echo "</div>\n";
    }
    
    /**
     * Demo: Información de directorios
     */
    private function demo_directory_info() {
        echo "<h2>📁 Información de Directorios</h2>\n";
        
        $info = $this->config->get_override_info();
        
        echo "<pre>\n";
        echo "📂 Directorio Padre (dev-tools):     " . $info['parent_dir'] . "\n";
        echo "📂 Directorio Hijo (plugin-specific): " . $info['child_dir'] . "\n";
        echo "✅ Parent existe:                     " . ($info['parent_exists'] ? 'SI' : 'NO') . "\n";
        echo "✅ Child existe:                      " . ($info['child_exists'] ? 'SI' : 'NO') . "\n";
        echo "🔧 Overrides activos:                 " . $info['overrides_count'] . "\n";
        echo "</pre>\n";
    }
    
    /**
     * Demo: Localización de archivos
     */
    private function demo_file_location() {
        echo "<h2>🔍 Localización de Archivos (Jerarquía Child Theme)</h2>\n";
        
        $test_files = [
            'config.php',
            'config-local.php', 
            'modules/SystemInfoModule.php',
            'templates/dashboard.php',
            'wp-tests-config-local.php'
        ];
        
        echo "<pre>\n";
        foreach ($test_files as $file) {
            $override_system = $this->config->getOverrideSystem();
            $located_file = $override_system ? $override_system->locate_file($file) : false;
            $has_override = $this->config->has_override($file);
            
            echo "📄 {$file}:\n";
            if ($located_file) {
                $source = strpos($located_file, 'plugin-dev-tools') !== false ? 'OVERRIDE' : 'PARENT';
                echo "   └─ ✅ Encontrado en: {$source}\n";
                echo "   └─ 📍 Ruta: " . str_replace(dirname(__DIR__), '...', $located_file) . "\n";
            } else {
                echo "   └─ ❌ No encontrado\n";
            }
            echo "   └─ 🔧 Tiene override: " . ($has_override ? 'SI' : 'NO') . "\n";
            echo "\n";
        }
        echo "</pre>\n";
    }
    
    /**
     * Demo: Creación de overrides
     */
    private function demo_override_creation() {
        echo "<h2>🛠️ Creación de Overrides</h2>\n";
        
        echo "<pre>\n";
        echo "Demostrando cómo crear override de archivos...\n\n";
        
        // Ejemplo de creación de override
        $files_to_override = [
            'config-local.php' => 'Configuración específica del plugin',
            'modules/CustomModule.php' => 'Módulo personalizado'
        ];
        
        foreach ($files_to_override as $file => $description) {
            echo "📄 {$file} ({$description}):\n";
            
            if ($this->config->has_override($file)) {
                echo "   └─ ✅ Ya existe override\n";
            } else {
                echo "   └─ 💡 Se puede crear con: \$config->create_override('{$file}')\n";
                echo "   └─ 🎯 Resultado: plugin-dev-tools/{$file}\n";
            }
            echo "\n";
        }
        echo "</pre>\n";
    }
    
    /**
     * Demo: Carga de configuración
     */
    private function demo_config_loading() {
        echo "<h2>⚙️ Carga de Configuración con Override</h2>\n";
        
        echo "<pre>\n";
        echo "Demostrando merge de configuraciones...\n\n";
        
        // Simular carga de configuración
        echo "🔄 Proceso de carga de configuración:\n";
        echo "1. Cargar config base desde dev-tools/config.php\n";
        echo "2. Buscar override en plugin-dev-tools/config-local.php\n";
        echo "3. Merge profundo de configuraciones\n";
        echo "4. Resultado final con prioridad a override\n\n";
        
        // Cargar configuración con override
        $override_config = $this->config->load_override_config('config-local.php');
        
        if (!empty($override_config)) {
            echo "✅ Configuración override cargada:\n";
            echo "📋 Claves encontradas: " . implode(', ', array_keys($override_config)) . "\n";
        } else {
            echo "ℹ️ No hay configuración override (normal en primera ejecución)\n";
        }
        echo "</pre>\n";
    }
    
    /**
     * Demo: Carga de templates
     */
    private function demo_template_loading() {
        echo "<h2>🎨 Carga de Templates con Override</h2>\n";
        
        echo "<pre>\n";
        echo "Sistema de templates con jerarquía:\n\n";
        
        $test_templates = [
            'dashboard.php',
            'module-info.php',
            'admin-panel.php'
        ];
        
        foreach ($test_templates as $template) {
            echo "🎨 Template: {$template}\n";
            
            $override_system = $this->config->getOverrideSystem();
            $template_file = $override_system ? $override_system->locate_file("templates/{$template}") : false;
            
            if ($template_file) {
                $source = strpos($template_file, 'plugin-dev-tools') !== false ? 'OVERRIDE' : 'PARENT';
                echo "   └─ ✅ Se cargaría desde: {$source}\n";
                echo "   └─ 📍 Archivo: " . basename($template_file) . "\n";
            } else {
                echo "   └─ 💡 Se puede crear override en: plugin-dev-tools/templates/{$template}\n";
            }
            echo "\n";
        }
        
        echo "🔧 Uso del sistema:\n";
        echo "\$config->load_template('dashboard.php', ['data' => \$data]);\n";
        echo "// Carga automáticamente desde plugin-dev-tools/ o dev-tools/\n";
        echo "</pre>\n";
    }
    
    /**
     * Obtener código de ejemplo para usar el sistema
     */
    public function get_usage_examples() {
        return [
            'php' => '
// Cargar archivo con override automático
$config = DevToolsConfig::getInstance();
$config->include_file("modules/CustomModule.php");

// Cargar configuración específica
$local_config = $config->load_override_config("config-local.php");

// Verificar si existe override
if ($config->has_override("templates/custom.php")) {
    $config->load_template("custom.php", ["data" => $data]);
}

// Crear nuevo override para customización
$config->create_override("modules/SystemInfoModule.php");
',
            'structure' => '
plugin-directory/
├── dev-tools/                    # PADRE (compartido)
│   ├── config.php               # ✅ Base
│   ├── modules/                 # ✅ Módulos base
│   └── templates/               # ✅ Templates base
│
├── plugin-dev-tools/            # HIJO (específico)
│   ├── config-local.php         # 🔧 Override config
│   ├── modules/                 # 🔧 Módulos custom
│   └── templates/               # 🔧 Templates custom
│
└── otros-archivos-plugin/
'
        ];
    }
}

// Si se llama directamente, ejecutar demo
if (basename($_SERVER['REQUEST_URI']) === basename(__FILE__)) {
    $demo = new DevToolsOverrideDemo();
    $demo->run_all_demos();
    
    echo "<h2>💻 Ejemplos de Uso</h2>\n";
    $examples = $demo->get_usage_examples();
    
    echo "<h3>PHP:</h3>\n";
    echo "<pre style='background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px;'>" . 
         htmlspecialchars($examples['php']) . "</pre>\n";
    
    echo "<h3>Estructura:</h3>\n";
    echo "<pre style='background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px;'>" . 
         htmlspecialchars($examples['structure']) . "</pre>\n";
}
