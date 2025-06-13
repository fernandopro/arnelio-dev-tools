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
        <testsuite name="Environment Tests">
            <directory>../dev-tools/tests/environment/</directory>
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
    echo "✅ Configuración PHPUnit creada\n\n";
    
    // 4. Crear test de ejemplo
    echo "🧪 Creando tests de ejemplo...\n";
    $plugin_name = basename($info['plugin_root']);
    $class_name = str_replace(['-', '_'], '', ucwords($plugin_name, '-_'));
    
    $test_content = '<?php
/**
 * Tests específicos del plugin ' . $plugin_name . '
 * 
 * Estos tests son específicos para la funcionalidad del plugin.
 * No afectan ni dependen de tests de otros plugins.
 */

class ' . $class_name . 'PluginTest extends DevToolsTestCase {
    
    public function setUp(): void {
        parent::setUp();
        
        // Setup específico del plugin
        $this->plugin_setup();
    }
    
    /**
     * Test básico de activación del plugin
     */
    public function test_plugin_activation() {
        // Verificar que el plugin principal está cargado
        $this->assertTrue(function_exists(\'get_plugin_data\'), \'WordPress plugin functions should be available\');
        
        // Agregar aquí tests específicos de activación del plugin
        $this->assertTrue(true, \'Plugin activation test placeholder\');
    }
    
    /**
     * Test de funcionalidad específica del plugin
     */
    public function test_plugin_specific_functionality() {
        // Ejemplo: Test de custom post types, taxonomías, etc.
        $this->assertTrue(true, \'Plugin specific functionality test placeholder\');
    }
    
    /**
     * Test de integración con WordPress
     */
    public function test_wordpress_integration() {
        // Verificar que las integraciones con WordPress funcionan
        $this->assertTrue(is_user_logged_in() || !is_user_logged_in(), \'WordPress user system should be available\');
        $this->assertTrue(function_exists(\'wp_insert_post\'), \'WordPress post functions should be available\');
    }
    
    /**
     * Setup específico del plugin
     */
    private function plugin_setup() {
        // Configuración específica para tests de este plugin
        // Crear datos de prueba, configurar mocks, etc.
    }
    
    public function tearDown(): void {
        // Limpieza específica del plugin
        parent::tearDown();
    }
}';
    
    file_put_contents($info['child_dir'] . '/tests/unit/' . $class_name . 'PluginTest.php', $test_content);
    echo "✅ Test de ejemplo creado\n\n";
    
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
/tests/fixtures/temp/*

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
│   └── fixtures/       # Datos de prueba
├── modules/            # Módulos personalizados
├── templates/          # Templates específicos
├── logs/               # Logs del plugin
└── reports/            # Reports de coverage/testing
```

## 🧪 Testing

### Ejecutar Tests Específicos del Plugin
```bash
# Desde la raíz del plugin
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/tests/phpunit.xml

# Solo tests unitarios específicos
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/tests/phpunit.xml --testsuite="Plugin Specific Tests"

# Con coverage
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/tests/phpunit.xml --coverage-html plugin-dev-tools/reports/coverage
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
    echo "   1. Ejecutar tests: dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml\n";
    echo "   2. Personalizar: plugin-dev-tools/config/config-local.php\n";
    echo "   3. Añadir tests: plugin-dev-tools/tests/unit/\n";
    echo "   4. Crear overrides: FileOverrideSystem::getInstance()->create_override('archivo.php')\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ ¡Sistema Override listo para usar!\n";
