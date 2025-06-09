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

### **Estructura Nueva**
```
plugin-directory/
├── dev-tools/                           # 🔄 SUBMÓDULO COMPARTIDO (PADRE)
│   ├── config.php                       # ✅ Base compartida
│   ├── loader.php                       # ✅ Con lógica de override
│   ├── modules/                         # ✅ Módulos base
│   ├── templates/                       # ✅ Templates base
│   └── core/FileOverrideSystem.php      # ✅ Sistema de override
│
├── plugin-dev-tools/                    # 🎯 ESPECÍFICO DEL PLUGIN (HIJO)
│   ├── config-local.php                 # ❌ Override específico
│   ├── wp-tests-config-local.php        # ❌ Testing específico
│   ├── modules/CustomModule.php         # ❌ Módulos personalizados
│   ├── templates/custom-dashboard.php   # ❌ Templates específicos
│   ├── tests/                           # ❌ Tests del plugin
│   ├── logs/                            # ❌ Logs locales
│   └── README.md                        # ❌ Documentación específica
│
└── 📁 Otros archivos del plugin...
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

## 🛠️ **Migración Automática**

### **Script de Migración**
```bash
# Migrar archivos existentes al nuevo sistema
cd dev-tools
./migrate-to-override-system.sh
```

**El script automáticamente:**
1. ✅ Crea directorio `plugin-dev-tools/` en la raíz del plugin
2. ✅ Migra archivos locales desde `dev-tools/` a `plugin-dev-tools/`
3. ✅ Crea backups de archivos originales (`.backup`)
4. ✅ Añade headers explicativos a archivos override
5. ✅ Genera README.md y .gitignore específicos
6. ✅ Configura estructura completa

### **Archivos Migrados Automáticamente**
- `config-local.php` → `plugin-dev-tools/config-local.php`
- `wp-tests-config-local.php` → `plugin-dev-tools/wp-tests-config-local.php`
- `phpunit-local.xml` → `plugin-dev-tools/phpunit-local.xml`
- `tests/plugin-specific/` → `plugin-dev-tools/tests/`
- `logs/plugin-specific/` → `plugin-dev-tools/logs/`
- `reports/plugin-specific/` → `plugin-dev-tools/reports/`

## 🧪 **Testing con Override**

### **Configuración de Tests**
```xml
<!-- plugin-dev-tools/phpunit-local.xml -->
<phpunit bootstrap="bootstrap-local.php">
    <testsuites>
        <testsuite name="Plugin Specific Tests">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### **Ejecutar Tests Específicos**
```bash
# Tests usando configuración override
cd plugin-dev-tools
phpunit -c phpunit-local.xml

# Tests específicos del plugin únicamente
phpunit tests/TarokinaCustomPostTypesTest.php
```

## 🔧 **Configuración Específica por Plugin**

### **Configuración Base (dev-tools/config.php)**
```php
// Plugin-agnóstico, configuración compartida
return [
    'dev_tools' => [
        'version' => '3.0.0',
        'features' => ['ajax', 'testing', 'modules']
    ]
];
```

### **Override Específico (plugin-dev-tools/config-local.php)**
```php
// Específico para Tarokina Pro
return [
    'plugin' => [
        'slug' => 'tarokina-2025',
        'name' => 'Tarokina Pro',
        'custom_post_types' => ['tkina_tarots', 'tarokkina_pro'],
        'features' => ['tarot_engine', 'premium_spreads']
    ],
    'dev_tools' => [
        'extra_modules' => ['TarotModule', 'SpreadModule']
    ]
];
```

### **Resultado Final (Merge Automático)**
```php
// El sistema automáticamente merge ambas configuraciones
// Prioridad: plugin-dev-tools/ override dev-tools/
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

---

**🔧 DevTools 3.0 - Sistema Override Child Theme Completado**  
**📅 Migración Completada: 9 de junio de 2025**
