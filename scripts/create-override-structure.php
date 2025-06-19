#!/usr/bin/env php
<?php
/**
 * Script para crear la estructura plugin-dev-tools desde cero
 * 
 * Este script:
 * 1. Crea la estructura completa de directorios
 * 2. Genera archivos de configuración específicos
 * 3. Crea tests de ejemplo
 * 4. Configura phpunit.xml personalizado
 * 5. Establece .gitignore y README
 * 
 * Uso:
 *   php scripts/create-override-structure.php
 */

// Incluir el sistema de override
require_once __DIR__ . '/../includes/Core/FileOverrideSystem.php';

echo "🔧 Dev-Tools Override System - Creador de Estructura\n";
echo "=======================================================\n\n";

try {
    $override = FileOverrideSystem::getInstance();
    $info = $override->get_system_info();
    
    echo "📍 Directorio del plugin: " . $info['plugin_root'] . "\n";
    echo "📍 Directorio dev-tools: " . $info['parent_dir'] . "\n";
    echo "📍 Directorio plugin-dev-tools: " . $info['child_dir'] . "\n\n";
    
    // 1. Crear estructura de directorios
    echo "📦 Creando estructura de directorios...\n";
    $override->create_child_structure();
    echo "✅ Estructura de directorios creada\n\n";
    
    // 2. Crear archivo de configuración específica
    echo "⚙️ Creando configuración específica...\n";
    $config_content = '<?php
/**
 * Configuración específica del plugin
 * 
 * Este archivo sobrescribe configuraciones del dev-tools core
 * para adaptarlas a las necesidades específicas de este plugin.
 */

return [
    // Configuración específica del plugin
    \'plugin_name\' => \'' . basename($info['plugin_root']) . '\',
    \'testing\' => [
        \'enabled\' => true,
        \'mock_external_apis\' => true,
        \'test_data_fixtures\' => true,
    ],
    
    // Configuración de módulos específicos
    \'modules\' => [
        \'dashboard\' => [
            \'custom_panels\' => true,
            \'plugin_specific_widgets\' => true,
        ],
        \'ajax\' => [
            \'custom_endpoints\' => true,
            \'plugin_specific_actions\' => true,
        ],
    ],
    
    // Configuración de testing
    \'phpunit\' => [
        \'test_suites\' => [
            \'unit\' => \'./tests/unit/\',
            \'integration\' => \'./tests/integration/\',
        ],
        \'coverage\' => [
            \'include\' => [
                \'../includes/\',
                \'./modules/\',
            ],
            \'exclude\' => [
                \'./tests/\',
                \'../dev-tools/vendor/\',
            ],
        ],
    ],
];';
    
    file_put_contents($info['child_dir'] . '/config/config-local.php', $config_content);
    echo "✅ Configuración específica creada\n\n";
    
    // 3. Crear phpunit.xml personalizado
    echo "🧪 Creando configuración PHPUnit específica...\n";
    $phpunit_content = '<?xml version="1.0"?>
<phpunit
    bootstrap="../dev-tools/tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    stopOnFailure="false"
    beStrictAboutTestsThatDoNotTestAnything="true"
    beStrictAboutOutputDuringTests="true"
>
    <testsuites>
        <testsuite name="Plugin Specific Tests">
            <directory>./tests/unit/</directory>
            <directory>./tests/integration/</directory>
        </testsuite>
        <testsuite name="Core Framework Tests">
            <directory>../dev-tools/tests/unit/</directory>
            <directory>../dev-tools/tests/modules/</directory>
        </testsuite>
        <testsuite name="Database Tests">
            <directory>./tests/database/</directory>
            <directory>../dev-tools/tests/database/</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>../dev-tools/tests/integration/</directory>
        </testsuite>
    </testsuites>
    
    <coverage includeUncoveredFiles="true">
        <include>
            <directory suffix=".php">../includes/</directory>
            <directory suffix=".php">./modules/</directory>
            <directory suffix=".php">../blocks/</directory>
            <directory suffix=".php">../elementor/</directory>
        </include>
        <exclude>
            <directory>./tests/</directory>
            <directory>../dev-tools/tests/</directory>
            <directory>../dev-tools/vendor/</directory>
            <directory>../node_modules/</directory>
        </exclude>
        <report>
            <html outputDirectory="./reports/coverage"/>
            <clover outputFile="./reports/coverage.xml"/>
        </report>
    </coverage>
    
    <logging>
        <junit outputFile="./reports/junit.xml"/>
    </logging>
    
    <php>
        <!-- Variables específicas del plugin -->
        <env name="PLUGIN_TEST_MODE" value="true"/>
        <env name="WP_TESTS_MULTISITE" value="0"/>
    </php>
</phpunit>';
    
    file_put_contents($info['child_dir'] . '/phpunit.xml', $phpunit_content);
    echo "✅ Configuración PHPUnit creada\n";
    
    // 3.1. Crear phpunit-plugin-only.xml (sin conflictos con configuración moderna)
    echo "🧪 Creando configuración PHPUnit específica (sin conflictos)...\n";
    $phpunit_only_content = '<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    stopOnFailure="false"
    beStrictAboutTestsThatDoNotTestAnything="true"
    beStrictAboutOutputDuringTests="true"
>
    <testsuites>
        <testsuite name="Plugin Specific Tests Only">
            <directory>./tests/unit/</directory>
            <directory>./tests/integration/</directory>
        </testsuite>
    </testsuites>

    <php>
        <const name="WP_TESTS_DOMAIN" value="example.org" />
        <const name="WP_TESTS_EMAIL" value="admin@example.org" />
        <const name="WP_TESTS_TITLE" value="Test Blog" />
        <const name="WP_PHP_BINARY" value="php" />
        <const name="WP_TESTS_FORCE_KNOWN_BUGS" value="true" />
        <env name="WP_PHPUNIT__TESTS_CONFIG" value="tests/wp-tests-config.php" />
        <env name="PLUGIN_TEST_MODE" value="true"/>
        <env name="WP_TESTS_MULTISITE" value="0"/>
    </php>

    <groups>
        <exclude>
            <group>ajax</group>
            <group>ms-files</group>
            <group>external-http</group>
        </exclude>
    </groups>

    <logging>
        <junit outputFile="./reports/junit.xml"/>
        <testdoxText outputFile="./reports/testdox.txt"/>
    </logging>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./</directory>
        </include>
        <exclude>
            <directory>./tests/</directory>
            <directory>./vendor/</directory>
            <directory>../dev-tools/</directory>
        </exclude>
    </coverage>
</phpunit>';
    
    file_put_contents($info['child_dir'] . '/phpunit-plugin-only.xml', $phpunit_only_content);
    echo "✅ Configuración PHPUnit específica creada\n\n";
    
    // 4. Crear test básico dinámico
    echo "🧪 Verificando tests básicos...\n";
    
    // Generar nombre dinámico del test básico (misma lógica que DevToolsAdminPanel.php)
    $plugin_name = basename($info['plugin_root']);
    $safe_plugin_name = preg_replace('/[^a-zA-Z0-9_]/', '', ucwords(str_replace(['-', '_'], ' ', $plugin_name)));
    $safe_plugin_name = str_replace(' ', '', $safe_plugin_name);
    $basic_test_filename = $safe_plugin_name . 'BasicTest.php';
    $basic_test_file = $info['child_dir'] . '/tests/unit/' . $basic_test_filename;
    
    if (file_exists($basic_test_file)) {
        echo "✅ {$basic_test_filename} ya está disponible para Quick Test\n\n";
    } else {
        echo "📝 Creando {$basic_test_filename} para Quick Test...\n";
        
        // Crear el test básico dinámico
        $basic_test_content = '<?php
/**
 * Test Básico para Quick Test - Generado Automáticamente
 * 
 * Este test básico se usa para el botón "Quick Test" del panel de administración.
 * Verifica funcionalidades básicas del sistema sin requerir configuraciones complejas.
 * 
 * Plugin: ' . $plugin_name . '
 * Generado: ' . date('Y-m-d H:i:s') . '
 * 
 * @package DevTools
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class ' . $safe_plugin_name . 'BasicTest extends TestCase {
    
    /**
     * Test básico - verificar que PHPUnit funciona
     */
    public function test_phpunit_works() {
        $this->assertTrue(true, "PHPUnit está funcionando correctamente");
        $this->assertNotEmpty("test", "Las aserciones básicas funcionan");
    }
    
    /**
     * Test básico - verificar variables PHP básicas
     */
    public function test_php_environment() {
        $this->assertNotEmpty(PHP_VERSION, "PHP_VERSION debe estar definida");
        $this->assertTrue(function_exists("strlen"), "Funciones PHP básicas deben estar disponibles");
        $this->assertTrue(class_exists("stdClass"), "Clases PHP básicas deben estar disponibles");
    }
    
    /**
     * Test básico - verificar matemáticas simples
     */
    public function test_basic_math() {
        $this->assertEquals(4, 2 + 2, "Suma básica debe funcionar");
        $this->assertEquals(10, 5 * 2, "Multiplicación básica debe funcionar");
        $this->assertTrue(10 > 5, "Comparaciones deben funcionar");
    }
    
    /**
     * Test básico - verificar arrays y strings
     */
    public function test_basic_data_types() {
        $array = [1, 2, 3];
        $this->assertCount(3, $array, "Conteo de arrays debe funcionar");
        $this->assertContains(2, $array, "Arrays deben contener elementos esperados");
        
        $string = "Hello World";
        $this->assertStringContainsString("World", $string, "Strings deben contener subcadenas esperadas");
        $this->assertEquals(11, strlen($string), "Longitud de strings debe ser correcta");
    }
    
    /**
     * Test básico - verificar fechas y tiempo
     */
    public function test_basic_datetime() {
        $timestamp = time();
        $this->assertIsInt($timestamp, "time() debe retornar un entero");
        $this->assertGreaterThan(0, $timestamp, "timestamp debe ser positivo");
        
        $date = date("Y-m-d");
        $this->assertMatchesRegularExpression("/^\d{4}-\d{2}-\d{2}$/", $date, "Formato de fecha debe ser YYYY-MM-DD");
    }
    
    /**
     * Test básico - verificar constantes del plugin
     */
    public function test_plugin_environment() {
        // Test que no requiere WordPress pero verifica el entorno
        $this->assertTrue(true, "El entorno de testing está funcionando");
        
        // Verificar que podemos usar assertions avanzadas
        $data = [
            "plugin" => "' . $plugin_name . '",
            "test_type" => "basic",
            "timestamp" => time()
        ];
        
        $this->assertArrayHasKey("plugin", $data, "Array debe contener key plugin");
        $this->assertEquals("' . $plugin_name . '", $data["plugin"], "Plugin name debe coincidir");
        $this->assertArrayHasKey("test_type", $data, "Array debe contener key test_type");
        $this->assertEquals("basic", $data["test_type"], "Test type debe ser basic");
    }
    
    /**
     * Test básico - verificar manejo de excepciones
     */
    public function test_exception_handling() {
        $this->expectException(InvalidArgumentException::class);
        
        // Provocar una excepción para verificar que el manejo funciona
        throw new InvalidArgumentException("Test exception");
    }
    
    /**
     * Test básico - verificar assertions de contenido
     */
    public function test_content_assertions() {
        $html = "<div class=\"test\">Content</div>";
        $json = \'{"key": "value", "number": 42}\';
        
        // Test HTML
        $this->assertStringContainsString("test", $html, "HTML debe contener class test");
        $this->assertStringContainsString("Content", $html, "HTML debe contener contenido esperado");
        
        // Test JSON
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded, "JSON debe ser decodificable");
        $this->assertArrayHasKey("key", $decoded, "JSON debe contener key esperada");
        $this->assertEquals("value", $decoded["key"], "JSON debe contener valor esperado");
        $this->assertEquals(42, $decoded["number"], "JSON debe contener número esperado");
    }
}';
        
        file_put_contents($basic_test_file, $basic_test_content);
        echo "✅ {$basic_test_filename} creado exitosamente\n\n";
    }
    
    // 4.5. Crear bootstrap.php para tests del plugin (clonar desde dev-tools)
    echo "⚙️ Creando bootstrap.php para tests del plugin...\n";
    $bootstrap_content = '<?php
/**
 * OVERRIDE FILE - Específico del Plugin
 * Copiado desde: tests/bootstrap.php
 * Fecha: ' . date('Y-m-d H:i:s') . '
 */

/**
 * Bootstrap agnóstico para testing PHPUnit
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 * 
 * Este archivo inicializa automáticamente el entorno de testing para WordPress y Dev-Tools
 * detectando dinámicamente la instalación de WordPress y la estructura del plugin
 */

// Definir constantes de testing antes de cargar WordPress
if ( ! defined( \'DEV_TOOLS_TESTING\' ) ) {
    define( \'DEV_TOOLS_TESTING\', true );
}

if ( ! defined( \'DEV_TOOLS_TEST_MODE\' ) ) {
    define( \'DEV_TOOLS_TEST_MODE\', \'unit\' );
}

// Configurar la zona horaria
date_default_timezone_set( \'UTC\' );

// Verificar que estamos en el directorio correcto
$plugin_dir = dirname( __DIR__ );
$wp_tests_dir = getenv( \'WP_TESTS_DIR\' );

// Cargar autoloader de Composer desde dev-tools
require_once dirname($plugin_dir) . \'/dev-tools/vendor/autoload.php\';

// Si WP_TESTS_DIR no está definido, intentar encontrar WordPress dinámicamente
if ( ! $wp_tests_dir ) {
    // Detectar dinámicamente la instalación de WordPress
    $current_dir = $plugin_dir;
    $max_depth = 10;
    $current_depth = 0;
    
    while ($current_depth < $max_depth) {
        if (file_exists($current_dir . \'/wp-config.php\') || file_exists($current_dir . \'/wp-settings.php\')) {
            $wp_tests_dir = $current_dir;
            break;
        }
        $parent = dirname($current_dir);
        if ($parent === $current_dir) {
            // Llegamos al directorio raíz sin encontrar WordPress
            break;
        }
        $current_dir = $parent;
        $current_depth++;
    }
}

// Cargar PHPUnit Polyfills - OBLIGATORIO para WordPress Test Suite
require_once dirname($plugin_dir) . \'/dev-tools/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php\';

// Verificar que tenemos wp-phpunit disponible
$wp_phpunit_dir = dirname($plugin_dir) . \'/dev-tools/vendor/wp-phpunit/wp-phpunit\';
if ( ! file_exists( $wp_phpunit_dir . \'/includes/functions.php\' ) ) {
    echo "Error: wp-phpunit no encontrado. Ejecuta: composer require --dev wp-phpunit/wp-phpunit\n";
    exit( 1 );
}

// Cargar wp-tests-config.php
$config_file = $plugin_dir . \'/tests/wp-tests-config.php\';
if ( ! file_exists( $config_file ) ) {
    echo "Error: wp-tests-config.php no encontrado en tests/\n";
    exit( 1 );
}

// Cargar la configuración
require_once $config_file;

// Definir WP_TESTS_CONFIG_FILE_PATH para wp-phpunit
if ( ! defined( \'WP_TESTS_CONFIG_FILE_PATH\' ) ) {
    define( \'WP_TESTS_CONFIG_FILE_PATH\', $config_file );
}

// Cargar las funciones de testing de WordPress desde wp-phpunit
require_once $wp_phpunit_dir . \'/includes/functions.php\';

/**
 * Función para cargar el plugin antes de que WordPress se inicialice
 */
function _manually_load_plugin() {
    // Cargar el loader de Dev-Tools desde el directorio correcto
    require dirname( dirname( __DIR__ ) ) . \'/dev-tools/loader.php\';
}

// Registrar la función para cargar el plugin
tests_add_filter( \'muplugins_loaded\', \'_manually_load_plugin\' );

// Cargar el bootstrap de WordPress desde wp-phpunit
require $wp_phpunit_dir . \'/includes/bootstrap.php\';

// Incluir helpers adicionales para testing desde dev-tools
require_once dirname(dirname(__DIR__)) . \'/dev-tools/tests/includes/TestCase.php\';
require_once dirname(dirname(__DIR__)) . \'/dev-tools/tests/includes/Helpers.php\';

// Activar el plugin programáticamente para las pruebas
// Detectar dinámicamente el path del plugin principal
$plugin_base_dir = dirname($plugin_dir);
$plugin_name = basename($plugin_base_dir);
$dev_tools_plugin_path = $plugin_name . \'/dev-tools/loader.php\';

// Intentar activar el plugin de dev-tools
if (function_exists(\'activate_plugin\')) {
    try {
        activate_plugin($dev_tools_plugin_path);
    } catch (Exception $e) {
        // Si falla, intentar activar solo el loader directamente
        // No es crítico para todos los tests
    }
}

echo "✅ Bootstrap de Dev-Tools testing cargado correctamente\n";
';
    
    $bootstrap_file = $info['child_dir'] . '/tests/bootstrap.php';
    file_put_contents($bootstrap_file, $bootstrap_content);
    echo "✅ bootstrap.php creado exitosamente\n\n";
    
    // 5. Crear .gitignore específico
    echo "📄 Creando .gitignore específico...\n";
    $gitignore_content = '# Plugin Dev-Tools - Archivos específicos del plugin
# NO commitear configuraciones locales sensibles

# Logs específicos del plugin
/logs/*.log
/logs/*.txt
!logs/.gitkeep

# Reports de testing
/reports/*
!reports/.gitkeep

# Cache de testing
.phpunit.result.cache
*.cache

# Configuraciones sensibles (si las hay)
# config/config-sensitive.php

# Datos de testing temporales
/tests/temp/*

# Coverage reports
/reports/coverage/*

# Archivos de desarrollo temporal
*.tmp
*.temp
.DS_Store
Thumbs.db';
    
    file_put_contents($info['child_dir'] . '/.gitignore', $gitignore_content);
    echo "✅ .gitignore creado\n\n";
    
    // 6. Crear README específico
    echo "📖 Creando README específico...\n";
    $readme_content = '# ' . $plugin_name . ' - Dev-Tools Override

Este directorio contiene configuraciones y tests específicos para el plugin **' . $plugin_name . '**.

## 🎯 Sistema Override Child Theme

Este directorio funciona como un "child theme" para el framework dev-tools:

- **Archivos aquí** sobrescriben los del directorio `dev-tools/`
- **Configuraciones específicas** del plugin
- **Tests únicos** para este plugin
- **Sin afectar otros plugins** que usen dev-tools

## 📁 Estructura

```
plugin-dev-tools/
├── config/              # Configuraciones específicas
├── tests/               # Tests específicos del plugin
│   ├── unit/           # Tests unitarios
│   ├── integration/    # Tests de integración
│   ├── database/       # Tests de base de datos
│   ├── modules/        # Tests de módulos
│   └── includes/       # Clases helper
├── modules/            # Módulos personalizados
├── templates/          # Templates específicos
├── logs/               # Logs del plugin
└── reports/            # Reports de coverage/testing
```

## 🧪 Testing

### Ejecutar Tests Específicos del Plugin
```bash
# Desde la raíz del plugin
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml

# Solo tests unitarios específicos
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml --testsuite="Plugin Specific Tests"

# Tests solo del plugin (sin framework - recomendado)
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit-plugin-only.xml

# Con coverage
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml --coverage-html plugin-dev-tools/reports/coverage
```

### Ejecutar Tests del Framework Core
```bash
# Desde dev-tools/
vendor/bin/phpunit
```

## ⚙️ Configuración

La configuración específica del plugin está en `config/config-local.php` y se mergea automáticamente con la configuración base del framework.

## 🔄 Override de Archivos

Para personalizar un archivo del framework:

```php
// Desde PHP
$override = FileOverrideSystem::getInstance();
$override->create_override(\'modules/CustomModule.php\');
```

```bash
# Desde terminal
php dev-tools/scripts/create-override.php modules/CustomModule.php
```

## 📊 Información del Sistema

```php
$override = FileOverrideSystem::getInstance();
$info = $override->get_system_info();
print_r($info);
```

---
*Generado automáticamente por Dev-Tools Override System*';
    
    file_put_contents($info['child_dir'] . '/README.md', $readme_content);
    echo "✅ README específico creado\n\n";
    
    // 7. Crear archivos .gitkeep para directorios vacíos
    echo "📁 Creando archivos .gitkeep...\n";
    $gitkeep_dirs = ['logs', 'reports'];
    
    // Agregar directorios de tests que existen
    $test_subdirs = ['database', 'includes', 'integration', 'modules', 'unit'];
    foreach ($test_subdirs as $subdir) {
        $test_path = $info['child_dir'] . '/tests/' . $subdir;
        if (is_dir($test_path)) {
            $gitkeep_dirs[] = 'tests/' . $subdir;
        }
    }
    
    foreach ($gitkeep_dirs as $dir) {
        $full_path = $info['child_dir'] . '/' . $dir;
        if (is_dir($full_path)) {
            file_put_contents($full_path . '/.gitkeep', '');
        }
    }
    echo "✅ Archivos .gitkeep creados\n\n";
    
    // Resumen final
    echo "🎉 ESTRUCTURA PLUGIN-DEV-TOOLS CREADA EXITOSAMENTE\n";
    echo "=======================================================\n\n";
    
    $final_info = $override->get_system_info();
    echo "📊 Resumen:\n";
    echo "   - Directorio plugin-dev-tools: ✅ Creado\n";
    echo "   - Estructura de directorios: ✅ " . count(scandir($final_info['child_dir'])) . " elementos\n";
    echo "   - Configuración específica: ✅ Creada\n";
    echo "   - PHPUnit configurado: ✅ Listo\n";
    echo "   - Tests de ejemplo: ✅ Incluidos\n";
    echo "   - Documentación: ✅ Generada\n\n";
    
    echo "🚀 Próximos pasos:\n";
    echo "   1. Ejecutar tests: dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit-plugin-only.xml\n";
    echo "   2. Tests con framework: dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml\n";
    echo "   3. Personalizar: plugin-dev-tools/config/config-local.php\n";
    echo "   4. Añadir tests: plugin-dev-tools/tests/unit/\n";
    echo "   5. Crear overrides: FileOverrideSystem::getInstance()->create_override('archivo.php')\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ ¡Sistema Override listo para usar!\n";
