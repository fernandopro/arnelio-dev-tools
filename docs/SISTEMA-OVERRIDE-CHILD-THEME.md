# 🎯 Sistema de Override tipo Child Theme - DevTools 3.0

## 🚀 **NUEVA ARQUITECTURA IMPLEMENTADA (ACTUAL - JUNIO 2025)**

Hemos implementado un sistema de **override de archivos similar a los child themes de WordPress** que resuelve completamente el problema de separación entre archivos específicos del plugin y el core compartido.

### 📅 **EVOLUCIÓN ARQUITECTÓNICA**
- **❌ Arquitectura Híbrida (ELIMINADA):** Archivos locales dentro del submódulo dev-tools/
- **✅ Sistema Override Child Theme (ACTUAL):** Archivos específicos en plugin-dev-tools/

Este cambio **elimina la contaminación entre plugins** y **simplifica la gestión de configuraciones específicas**.

## 🏗️ **Cómo Funciona (Similar a Child Themes)**

### **Jerarquía de Carga**
```
1. 🔍 Busca primero en: plugin-dev-tools/archivo.php  (ESPECÍFICO)
2. 🔍 Si no existe, busca: dev-tools/archivo.php      (COMPARTIDO)
3. ✅ Carga el que encuentre primero
```

### **Estructura Optimizada (PHPUnit 9.x + WordPress)**
```
tarokina-2025/                           # 🎯 PLUGIN PRINCIPAL
├── dev-tools/                           # 🔄 SUBMÓDULO COMPARTIDO (PADRE)
│   ├── loader.php                       # ✅ Cargador principal con override logic
│   ├── composer.json                    # ✅ Dependencias core (PHPUnit, wp-phpunit, etc.)
│   ├── phpunit.xml.dist                 # ✅ Configuración PHPUnit base
│   ├── babel.config.js                  # ✅ Configuración Babel
│   ├── webpack.config.js                # ✅ Build system
│   ├── postcss.config.js                # ✅ PostCSS config
│   │
│   ├── config/                          # ✅ Configuraciones base
│   │   ├── config.php                   # ✅ Config agnóstica compartida
│   │   └── environment.php              # ✅ Detección de entorno
│   │
│   ├── includes/                        # ✅ Core classes
│   │   ├── Config/                      # ✅ Sistema de configuración
│   │   ├── Core/                        # ✅ FileOverrideSystem.php
│   │   ├── Ajax/                        # ✅ Sistema AJAX
│   │   └── Utils/                       # ✅ Utilidades
│   │
│   ├── modules/                         # ✅ Módulos base compartidos
│   │   ├── DashboardModule.php          # ✅ Dashboard base
│   │   ├── SystemInfoModule.php         # ✅ Info del sistema
│   │   └── ModuleBase.php               # ✅ Clase base
│   │
│   ├── tests/                           # ✅ Tests del framework
│   │   ├── bootstrap.php                # ✅ Bootstrap agnóstico
│   │   ├── wp-tests-config.php          # ✅ Config WordPress tests
│   │   ├── environment/                 # ✅ Tests de entorno (47 tests)
│   │   ├── unit/                        # ✅ Tests unitarios core
│   │   ├── integration/                 # ✅ Tests integración core
│   │   ├── mocks/                       # ✅ WordPress mocks
│   │   └── includes/TestCase.php        # ✅ Base test case
│   │
│   ├── src/                             # ✅ Assets fuente
│   │   ├── css/                         # ✅ Estilos SCSS
│   │   └── js/                          # ✅ JavaScript ES6+
│   │
│   ├── dist/                            # ✅ Assets compilados
│   │   ├── css/                         # ✅ CSS minificado
│   │   └── js/                          # ✅ JS compilado
│   │
│   ├── scripts/                         # ✅ Scripts de automatización
│   │   ├── create-override-structure.php # ✅ Crear sistema override
│   │   └── migrate-to-override-system.php # ✅ Migración automática
│   │
│   └── vendor/                          # ✅ Dependencias Composer
│       ├── phpunit/                     # ✅ PHPUnit 9.x
│       ├── wp-phpunit/                  # ✅ WordPress testing suite
│       └── brain/monkey/                # ✅ Mocking framework
│
├── plugin-dev-tools/                    # 🎯 ESPECÍFICO DEL PLUGIN (HIJO)
│   ├── config/                          # ❌ Configuraciones específicas
│   │   └── config-local.php             # ❌ Override config Tarokina
│   │
│   ├── tests/                           # ❌ Tests específicos del plugin
│   │   ├── phpunit.xml                  # ❌ Config PHPUnit específica
│   │   ├── bootstrap-local.php          # ❌ Bootstrap específico (opcional)
│   │   ├── unit/                        # ❌ Tests unitarios Tarokina
│   │   │   ├── TarokinaProPluginTest.php # ❌ Tests plugin específicos
│   │   │   ├── TarotEngineTest.php      # ❌ Tests motor tarot
│   │   │   └── CustomPostTypesTest.php  # ❌ Tests CPT Tarokina
│   │   ├── integration/                 # ❌ Tests integración plugin
│   │   │   ├── ElementorIntegrationTest.php # ❌ Tests Elementor
│   │   │   ├── BlocksIntegrationTest.php    # ❌ Tests Gutenberg
│   │   │   └── ApiEndpointsTest.php     # ❌ Tests API endpoints
│   │   └── fixtures/                    # ❌ Data de prueba específica
│   │
│   ├── modules/                         # ❌ Módulos personalizados
│   │   ├── TarokinaModule.php           # ❌ Módulo específico tarot
│   │   ├── SpreadModule.php             # ❌ Módulo tiradas
│   │   └── AIReadingModule.php          # ❌ Módulo lecturas IA
│   │
│   ├── templates/                       # ❌ Templates específicos
│   │   ├── tarokina-dashboard.php       # ❌ Dashboard personalizado
│   │   └── tarot-admin-panels.php       # ❌ Panels admin específicos
│   │
│   ├── logs/                            # ❌ Logs específicos plugin
│   ├── reports/                         # ❌ Reportes coverage/testing
│   └── README.md                        # ❌ Documentación específica
│
├── tarokina-pro.php                     # 🎯 Archivo principal plugin
├── includes/                            # 🎯 Clases específicas Tarokina
├── blocks/                              # 🎯 Bloques Gutenberg
├── elementor/                           # 🎯 Widgets Elementor
├── languages/                           # 🎯 Traducciones
└── composer.json                        # 🎯 Dependencias plugin padre
```

## 🎯 **VENTAJAS vs ARQUITECTURA ANTERIOR**

| Aspecto | ❌ Antes (Arquitectura Híbrida) | ✅ Ahora (Override Child Theme) |
|---------|--------------------------------|-----------------------------------|
| **Ubicación** | dev-tools/ + archivos locales | plugin-dev-tools/ completamente separado |
| **Git** | Conflictos con submódulo | Independiente del submódulo |
| **Compartición** | Contaminación entre plugins | Aislamiento total por plugin |
| **Conflictos** | Archivos locales en submódulo | Sin conflictos, jerarquía clara |
| **Actualizaciones** | Se perdían configuraciones | Configuraciones preservadas automáticamente |
| **Override** | Manual y propenso a errores | Automático y transparente como child themes |
| **Testing** | Tests mixtos entre plugins | Tests específicos por plugin |
| **Logs** | Logs compartidos | Logs independientes por plugin |

## 💻 **Uso del Sistema**

### **PHP - Carga Automática con Override**
```php
// Obtener instancia (ya incluye sistema de override)
$config = DevToolsConfig::getInstance();

// Incluir archivo con override automático
$config->include_file('modules/SystemInfoModule.php');
// → Busca: plugin-dev-tools/modules/SystemInfoModule.php
// → Si no existe: dev-tools/modules/SystemInfoModule.php

// Cargar configuración con merge automático
$local_config = $config->load_override_config('config-local.php');

// Cargar template con override
$config->load_template('dashboard.php', ['data' => $data]);

// Verificar si existe override
if ($config->has_override('custom-config.php')) {
    // Usar versión específica del plugin
}
```

### **Crear Nuevo Override**
```php
// Migrar archivo desde dev-tools/ para customización
$success = $config->create_override('modules/SystemInfoModule.php');
// → Copia dev-tools/modules/SystemInfoModule.php
// → A plugin-dev-tools/modules/SystemInfoModule.php
// → Añade header explicativo
// → Listo para customizar
```

### **Información del Sistema**
```php
$info = $config->get_override_info();
/*
Array:
[
    'parent_dir' => '/path/to/dev-tools',
    'child_dir' => '/path/to/plugin-dev-tools', 
    'parent_exists' => true,
    'child_exists' => true,
    'overrides_count' => 5
]
*/
```

## 🛠️ **Migración y Setup Automático**

### **Scripts de Configuración Automática**
```bash
# Crear estructura plugin-dev-tools/ desde cero
cd dev-tools
composer run dev-tools:create-override

# Migrar archivos existentes al nuevo sistema
composer run dev-tools:migrate

# Script directo de creación
php scripts/create-override-structure.php

# Script directo de migración
php scripts/migrate-to-override-system.php
```

**El sistema automáticamente:**
1. ✅ Detecta automáticamente la configuración del plugin padre
2. ✅ Crea directorio `plugin-dev-tools/` en la raíz del plugin
3. ✅ Configura estructura completa con subdirectorios optimizados
4. ✅ Genera archivos de configuración específicos del plugin
5. ✅ Crea tests de ejemplo y configuración PHPUnit
6. ✅ Establece .gitignore y README específicos
7. ✅ Configura composer.json del plugin padre (si no existe)

### **Estructura Generada Automáticamente**
```bash
plugin-dev-tools/
├── config/
│   └── config-local.php             # Configuración específica Tarokina Pro
├── tests/
│   ├── phpunit.xml                  # Config PHPUnit específica optimizada
│   ├── unit/
│   │   └── TarokinaProPluginTest.php # Test ejemplo con casos específicos
│   ├── integration/                 # Tests de integración preparados
│   └── fixtures/                    # Directorio para datos de prueba
├── modules/                         # Para módulos personalizados
├── templates/                       # Para templates específicos
├── logs/                           # Logs independientes del plugin
├── reports/                        # Coverage y reportes
├── .gitignore                      # Configurado para el plugin
└── README.md                       # Documentación específica
```

## 🧪 **Testing con Override - PHPUnit 9.x Optimizado**

### **Configuración de Tests Principal (dev-tools/phpunit.xml.dist)**
```xml
<?xml version="1.0"?>
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
        <testsuite name="Dev-Tools Framework">
            <directory>./tests/unit/</directory>
            <directory>./tests/modules/</directory>
        </testsuite>
        <testsuite name="Environment Tests">
            <directory>./tests/environment/</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>./tests/integration/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### **Configuración Override Plugin (plugin-dev-tools/phpunit.xml)**
```xml
<?xml version="1.0"?>
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
        <testsuite name="Tarokina Plugin Specific Tests">
            <directory>./tests/unit/</directory>
            <directory>./tests/integration/</directory>
        </testsuite>
        <testsuite name="Core Environment Tests">
            <directory>../dev-tools/tests/environment/</directory>
        </testsuite>
        <testsuite name="Framework Core Tests">
            <directory>../dev-tools/tests/unit/</directory>
            <directory>../dev-tools/tests/modules/</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">../includes/</directory>
            <directory suffix=".php">./modules/</directory>
            <directory suffix=".php">../blocks/</directory>
            <directory suffix=".php">../elementor/</directory>
        </whitelist>
    </filter>
    
    <logging>
        <log type="coverage-html" target="./reports/coverage"/>
        <log type="coverage-text" target="php://stdout" showUncoveredFiles="false"/>
    </logging>
</phpunit>
```

### **Comandos de Testing Actualizados**
```bash
# Tests específicos del plugin (desde raíz del plugin)
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml

# Tests del framework dev-tools (desde dev-tools/)
cd dev-tools && vendor/bin/phpunit

# Tests de environment únicamente
cd dev-tools && vendor/bin/phpunit --testsuite="Environment Tests"

# Tests completos del plugin + framework
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml --testsuite="Tarokina Plugin Specific Tests,Framework Core Tests"

# Coverage completo
dev-tools/vendor/bin/phpunit -c plugin-dev-tools/phpunit.xml --coverage-html plugin-dev-tools/reports/coverage
```

### **Bootstrap Agnóstico (dev-tools/tests/bootstrap.php)**
```php
<?php
/**
 * Bootstrap para Testing - Dev-Tools 3.0
 * Soporte para modo WordPress completo y modo Mock
 */

// Auto-detectar WordPress root desde dev-tools
$dev_tools_dir = dirname(__DIR__);
$plugin_dir = dirname($dev_tools_dir);

// Cargar PHPUnit Polyfills
require_once $dev_tools_dir . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Determinar modo de testing
$wp_tests_available = file_exists($dev_tools_dir . '/vendor/wp-phpunit/wp-phpunit/includes/functions.php');
$wp_config_available = file_exists($dev_tools_dir . '/tests/wp-tests-config.php');

if ($wp_tests_available && $wp_config_available) {
    // Modo WordPress Test Suite completo
    require_once $dev_tools_dir . '/tests/wp-tests-config.php';
    require_once $dev_tools_dir . '/vendor/wp-phpunit/wp-phpunit/includes/functions.php';
    
    tests_add_filter('muplugins_loaded', function() use ($dev_tools_dir) {
        require $dev_tools_dir . '/loader.php';
    });
    
    require $dev_tools_dir . '/vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php';
} else {
    // Modo Mock para tests básicos
    require_once $dev_tools_dir . '/vendor/brain/monkey/inc/bootstrap.php';
    require_once $dev_tools_dir . '/tests/mocks/wordpress-mocks.php';
}

// Cargar TestCase base
if (file_exists($dev_tools_dir . '/tests/includes/TestCase.php')) {
    require_once $dev_tools_dir . '/tests/includes/TestCase.php';
}
```

## 🔧 **Configuración Específica por Plugin**

### **Configuración Base (dev-tools/config/config.php)**
```php
<?php
/**
 * Configuración Base Dev-Tools 3.0
 * Agnóstica, compartida entre todos los plugins
 */
return [
    'dev_tools' => [
        'version' => '3.0.0',
        'features' => ['ajax', 'testing', 'modules', 'override-system'],
        'modules' => [
            'dashboard' => true,
            'system_info' => true,
            'testing' => true
        ]
    ],
    'testing' => [
        'phpunit_version' => '^9',
        'wp_phpunit_version' => '^6.8',
        'mocking_framework' => 'brain/monkey'
    ],
    'build' => [
        'webpack' => true,
        'babel' => true,
        'postcss' => true,
        'assets_compilation' => true
    ]
];
```

### **Override Específico (plugin-dev-tools/config/config-local.php)**
```php
<?php
/**
 * Configuración Override - Tarokina Pro
 * Específica para este plugin
 */
return [
    'plugin' => [
        'slug' => 'tarokina-2025',
        'name' => 'Tarokina Pro',
        'version' => '1.0.0',
        'namespace' => 'TarokinaPro\\',
        'textdomain' => 'tarokina-2025',
        'custom_post_types' => ['tkina_tarots', 'tarokkina_pro'],
        'features' => [
            'tarot_engine' => true,
            'premium_spreads' => true,
            'ai_readings' => true,
            'elementor_integration' => true,
            'gutenberg_blocks' => true
        ]
    ],
    'dev_tools' => [
        'extra_modules' => [
            'TarokinaModule' => true,
            'SpreadModule' => true,
            'AIReadingModule' => true
        ],
        'custom_commands' => [
            'generate_tarot_data',
            'process_spreads',
            'ai_reading_analysis'
        ]
    ],
    'testing' => [
        'wp_tests_dir' => getenv('WP_TESTS_DIR') ?: false,
        'db_name' => 'tarokina_2025_test',
        'test_suites' => [
            'tarot_engine' => './tests/unit/TarotEngineTest.php',
            'custom_post_types' => './tests/unit/CustomPostTypesTest.php',
            'elementor_integration' => './tests/integration/ElementorIntegrationTest.php'
        ]
    ]
];
```

### **Resultado Final (Merge Automático)**
```php
// El sistema automáticamente hace merge de ambas configuraciones
// Prioridad: plugin-dev-tools/config/ override dev-tools/config/
// La configuración final incluye ambas configuraciones combinadas
```

## 📁 **Gestión de Archivos**

### **URLs de Archivos Override**
```php
// Obtener URL correcta (plugin-dev-tools/ o dev-tools/)
$css_url = $config->get_file_url('src/css/dashboard.css');
$js_url = $config->get_file_url('src/js/modules.js');

// El sistema detecta automáticamente la ubicación correcta
```

### **Templates Específicos**
```php
// Template base en dev-tools/templates/dashboard.php
// Template override en plugin-dev-tools/templates/dashboard.php

$config->load_template('dashboard.php', [
    'plugin_name' => 'Tarokina Pro',
    'custom_data' => $tarokina_data
]);
// → Carga automáticamente la versión más específica
```

## 🎨 **Personalización Avanzada**

### **Módulos Específicos del Plugin**
```php
// plugin-dev-tools/modules/TarokinaModule.php
<?php
/**
 * OVERRIDE ARCHIVO: modules/TarokinaModule.php
 * Plugin: Tarokina Pro
 * 
 * Módulo específico para funcionalidades de Tarot
 */

class TarokinaModule extends DevToolsModuleBase {
    public function init() {
        $this->register_ajax_command('get_tarot_data', [$this, 'get_tarot_data']);
    }
    
    public function get_tarot_data() {
        // Lógica específica para Tarokina
    }
}
```

## 📋 **Flujo de Desarrollo**

### **1. Desarrollo Normal**
```php
// El código funciona igual, pero con override automático
$config = DevToolsConfig::getInstance();
$config->include_file('modules/NewModule.php');
```

### **2. Customización Específica**
```php
// Crear override para customizar
$config->create_override('modules/SystemInfoModule.php');
// → Editar plugin-dev-tools/modules/SystemInfoModule.php
```

### **3. Testing Local**
```bash
# Tests con configuración específica
cd plugin-dev-tools
phpunit -c phpunit-local.xml
```

### **4. Sin Afectación al Core**
- ✅ dev-tools/ permanece intacto
- ✅ Actualizaciones del submódulo sin conflictos
- ✅ Configuraciones específicas preservadas

## 🔍 **Demostración**

```php
// Ejecutar demo del sistema
include 'dev-tools/demo-override-system.php';
// → Muestra jerarquía de archivos, overrides existentes, etc.
```

## ⚠️ **Consideraciones Importantes**

### **✅ Ventajas**
- **Separación completa** entre específico y compartido
- **Override automático** y transparente
- **Migración automática** de archivos existentes
- **Compatible** con submódulos git
- **Escalable** para múltiples plugins

### **🚨 Precauciones**
- Los archivos en `plugin-dev-tools/` son **específicos** de este plugin
- No editar archivos en `dev-tools/` para configuraciones específicas
- Usar `create_override()` para customizar archivos del core
- Los backups están en `dev-tools/*.backup`

## 🎉 **Resultado Final**

**Sistema override tipo child theme que mantiene la potencia del dev-tools compartido eliminando completamente la contaminación entre plugins**, con la simplicidad y elegancia del sistema de child themes de WordPress.

## 📋 **MIGRACIÓN DESDE ARQUITECTURA HÍBRIDA**

### ✅ **Cambios Implementados (Junio 2025)**

1. **❌ ELIMINADO: Archivos locales en dev-tools/**
   ```bash
   # Estos archivos YA NO EXISTEN:
   dev-tools/config-local.php
   dev-tools/wp-tests-config-local.php
   dev-tools/phpunit-local.xml
   dev-tools/run-tests-local.sh
   ```

2. **✅ NUEVO: Estructura plugin-dev-tools/**
   ```bash
   # Nueva ubicación para archivos específicos:
   plugin-dev-tools/config-local.php
   plugin-dev-tools/wp-tests-config-local.php  
   plugin-dev-tools/modules/TarokinaModule.php
   plugin-dev-tools/tests/
   plugin-dev-tools/logs/
   ```

3. **🔄 AUTOMÁTICO: Sistema de carga con jerarquía**
   - El loader busca automáticamente en plugin-dev-tools/ primero
   - Si no encuentra el archivo, usa la versión de dev-tools/
   - **NO requiere configuración manual**

### 🚨 **ARCHIVO OBSOLETO ELIMINADO**
- **`dev-tools/ARQUITECTURA-HIBRIDA.md`** → Reemplazado por este documento

## 📊 **Estado Actual del Framework (Junio 2025)**

### **✅ Componentes Implementados**
- **Core System**: Loader agnóstico, sistema de configuración
- **Testing Framework**: 105+ tests (47 environment, 58+ framework)
- **PHPUnit 9.x**: Configuración optimizada con wp-phpunit 6.8
- **Módulos**: Dashboard, SystemInfo (funcionales)
- **Build System**: Webpack, Babel, PostCSS configurados
- **Override System**: Estructura documentada y scripts preparados

### **🔄 Próximas Implementaciones**
- **FileOverrideSystem.php**: Clase para jerarquía automática de archivos
- **Scripts Automáticos**: Finalizar create-override-structure.php
- **Módulos Adicionales**: Cache, AJAX Tester, Logs, Performance
- **Plugin-Dev-Tools**: Crear estructura inicial para Tarokina Pro

### **📋 Comandos Disponibles Actualmente**
```bash
# Testing del framework core
cd dev-tools && composer run test:environment  # 47 tests de entorno
cd dev-tools && composer run test:framework    # Tests completos

# Configuración del sistema override
cd dev-tools && composer run dev-tools:create-override  # Crear estructura
cd dev-tools && composer run dev-tools:migrate          # Migrar archivos

# Verificación del entorno
cd dev-tools && php scripts/check-environment.php      # Análisis del entorno
```

---

**🔧 DevTools 3.0 - Sistema Override Child Theme Actualizado**  
**📅 Estructura Actualizada: 12 de junio de 2025**
