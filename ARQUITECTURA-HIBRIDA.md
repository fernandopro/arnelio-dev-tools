# 🏗️ Arquitectura Híbrida Dev-Tools - Separación Plugin-Específico vs Compartido

## 🎯 **PROBLEMA RESUELTO**

**ANTES (❌ Problemático):**
- Tests, configuraciones y datos específicos del plugin se almacenaban en el directorio `dev-tools/`
- Cuando dev-tools se usaba como submódulo en múltiples plugins, estas configuraciones se compartían entre todos
- **RESULTADO:** Contaminación entre plugins, configuraciones mixtas, tests de un plugin aparecían en otros

**AHORA (✅ Solucionado):**
- **Separación clara** entre core compartido y configuraciones específicas del plugin
- **Archivos locales** que NO se incluyen en el submódulo git compartido
- **Cada plugin** mantiene sus propias configuraciones sin afectar a otros

## 🏛️ **NUEVA ARQUITECTURA HÍBRIDA**

```
plugin-directory/
├── dev-tools/                           # 🔄 SUBMÓDULO COMPARTIDO
│   ├── 📦 CORE COMPARTIDO (en git)
│   │   ├── config.php                   # ✅ Plugin-agnóstico
│   │   ├── loader.php                   # ✅ Detección automática
│   │   ├── modules/                     # ✅ Módulos reutilizables
│   │   ├── core/                        # ✅ Clases base
│   │   └── src/                         # ✅ Assets fuente
│   │
│   ├── 🔧 CONFIGURACIÓN LOCAL (ignorado por git)
│   │   ├── config-local.php             # ❌ Específico del plugin
│   │   ├── wp-tests-config-local.php    # ❌ Testing local
│   │   ├── phpunit-local.xml            # ❌ Config PHPUnit local
│   │   └── run-tests-local.sh           # ❌ Script testing local
│   │
│   ├── 📁 DIRECTORIOS LOCALES (ignorados por git)
│   │   ├── tests/plugin-specific/       # ❌ Tests específicos
│   │   ├── reports/plugin-specific/     # ❌ Reports locales
│   │   ├── logs/plugin-specific/        # ❌ Logs locales
│   │   └── fixtures/plugin-data/        # ❌ Datos de prueba locales
│   │
│   └── 🛠️ HERRAMIENTAS DE SEPARACIÓN
│       ├── setup-local.sh               # ✅ Configurar archivos locales
│       ├── migrate-to-local.sh          # ✅ Migrar configs existentes
│       └── config-local-template.php    # ✅ Template para nuevos plugins
│
└── 📁 Plugin files...
```

## 🔧 **FLUJO DE CONFIGURACIÓN**

### **Para Nuevo Plugin:**
```bash
# 1. Clonar dev-tools como submódulo
git submodule add https://github.com/tu-repo/dev-tools.git dev-tools

# 2. Setup automático (incluye configuración local)
./setup-dev-tools.sh

# 3. Los archivos locales se crean automáticamente específicos para el plugin
```

### **Para Plugin Existente (Migración):**
```bash
# 1. Migrar configuraciones existentes
cd dev-tools
./migrate-to-local.sh

# 2. Los archivos específicos del plugin se separan automáticamente
```

## 📋 **SEPARACIÓN DE ARCHIVOS**

### **✅ COMPARTIDO (en submódulo git):**
```php
// config.php - Plugin-agnóstico
class DevToolsConfig {
    private function detect_host_plugin() {
        // Detección automática del plugin host
        // NO contiene datos específicos de ningún plugin
    }
}
```

### **❌ LOCAL (ignorado por git del submódulo):**
```php
// config-local.php - Específico del plugin
return [
    'plugin' => [
        'slug' => 'tarokina-2025',           // Específico de Tarokina
        'name' => 'Tarokina Pro',            // Específico de Tarokina
        'main_file' => '/path/tarokina-pro.php'
    ],
    'plugin_specific' => [
        'custom_post_types' => ['tkina_tarots', 'tarokkina_pro'],
        'custom_taxonomies' => ['tarokkina_pro-cat'],
        'required_functions' => ['is_name_pro']
    ]
];
```

## 🧪 **SISTEMA DE TESTING SEPARADO**

### **Tests Compartidos (Core):**
```
dev-tools/tests/
├── unit/              # ✅ Tests del core dev-tools
├── integration/       # ✅ Tests de integración genéricos
└── bootstrap.php      # ✅ Bootstrap plugin-agnóstico
```

### **Tests Específicos del Plugin (Local):**
```
dev-tools/tests/plugin-specific/
├── TarokinaCustomPostTypesTest.php    # ❌ Solo para Tarokina
├── TarokinaLicenseTest.php            # ❌ Solo para Tarokina
└── TarokinaExportTest.php             # ❌ Solo para Tarokina

dev-tools/reports/plugin-specific/     # ❌ Reports solo de Tarokina
dev-tools/logs/plugin-specific/        # ❌ Logs solo de Tarokina
```

## 🔒 **CONFIGURACIÓN .GITIGNORE**

```gitignore
# ==========================================
# SEPARACIÓN PLUGIN-SPECIFIC vs SHARED CORE
# ==========================================

# Configuraciones específicas del plugin (LOCAL ONLY)
wp-tests-config-local.php
config-local.php
phpunit-local.xml

# Tests específicos del plugin (LOCAL ONLY)
tests/plugin-specific/
reports/plugin-specific/
logs/plugin-specific/

# Archivos con nombres de plugins específicos
*tarokina*-config.php
*superman*-config.php
wp-tests-config.tarokina.php
```

## 🚀 **COMANDOS DE USO**

### **Desarrollo Normal:**
```bash
# Compilar dev-tools (igual que antes)
cd dev-tools && npm run dev

# Ejecutar tests del core (compartidos)
./run-tests.sh

# Ejecutar tests específicos del plugin (locales)
./run-tests-local.sh
```

### **Gestión de Configuración:**
```bash
# Inicializar configuración local
./setup-local.sh

# Migrar configuraciones existentes
./migrate-to-local.sh

# Ver configuración actual
cat config-local.php
```

## 💡 **BENEFICIOS DE LA ARQUITECTURA HÍBRIDA**

### **✅ Para el Core Compartido:**
- **Reutilizable** entre múltiples plugins
- **Mantenimiento centralizado** de funcionalidades base
- **Sin contaminación** de configuraciones específicas
- **Actualizaciones automáticas** via git submodule

### **✅ Para Configuraciones Locales:**
- **Personalización específica** por plugin
- **Tests específicos** del plugin sin interferencias
- **Reports y logs separados** por plugin
- **Sin conflictos** entre diferentes proyectos

### **✅ Para el Desarrollo:**
- **Separación clara** de responsabilidades
- **Facilidad de mantenimiento** de múltiples plugins
- **Flexibilidad** para configuraciones específicas
- **Seguridad** - no se comparten datos sensibles entre plugins

## 📚 **DOCUMENTACIÓN ADICIONAL**

- **`LOCAL-SETUP.md`** - Guía específica del plugin actual
- **`INSTALACION-DEV-TOOLS.md`** - Instalación general
- **`dev-tools/docs/`** - Documentación técnica del core
- **`config-local-template.php`** - Template para nuevos plugins

## ⚠️ **CONSIDERACIONES IMPORTANTES**

1. **Los archivos `*-local.php` NUNCA deben committearse al submódulo**
2. **Cada plugin tendrá su propia configuración local independiente**  
3. **El core compartido se mantiene plugin-agnóstico**
4. **Las actualizaciones del submódulo NO afectan configuraciones locales**
5. **Para nuevos plugins, usar `setup-local.sh` para inicializar**

## 🔄 **PROCESO DE ACTUALIZACIÓN DEL SUBMÓDULO**

```bash
# Actualizar dev-tools compartido (NO afecta configuraciones locales)
git submodule update --remote dev-tools

# Las configuraciones locales se mantienen intactas
# Solo se actualiza el core compartido
```

---

**🎉 RESULTADO:** Sistema híbrido que mantiene la potencia del dev-tools compartido eliminando completamente la contaminación entre plugins.
