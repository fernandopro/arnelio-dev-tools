# Sistema de Debug WordPress Dinámico - Dev-Tools Core

## Descripción General

El **Sistema de Debug WordPress Dinámico** es una herramienta integrada en el núcleo de **Dev-Tools Arquitectura 3.0** que permite validar URLs dinámicas, configuración y detectar problemas en tiempo real dentro del contexto de WordPress.

> 📖 **Documentación Relacionada**
> - [`ESTADO-ARQUITECTURA-3.0.md`](ESTADO-ARQUITECTURA-3.0.md) - Estado completo de la implementación
> - [`ANALISIS-REFACTORIZACION-2025-06-08.md`](ANALISIS-REFACTORIZACION-2025-06-08.md) - Análisis técnico detallado
> - [`RESUMEN-DEBUG-SYSTEM-INTEGRATION.md`](RESUMEN-DEBUG-SYSTEM-INTEGRATION.md) - Resumen de integración

### Ubicación en el Sistema
- **Archivo**: `dev-tools/core/DebugWordPressDynamic.php`
- **Parte del núcleo**: Se carga automáticamente con Dev-Tools
- **Disponibilidad**: Todos los plugins que usen Dev-Tools tienen acceso

### 🎯 **Importancia Estratégica**

Esta herramienta representa una **innovación crítica** en el ecosistema Dev-Tools, abordando uno de los problemas más comunes en el desarrollo de plugins WordPress: **la gestión confiable de URLs dinámicas**. Su integración en el núcleo garantiza que esté disponible automáticamente para todos los desarrolladores.

## Características Principales

### 🔧 Debug Visual
- **URL de configuración general**: `?debug_config=1`
- **URL de debug de URLs**: `?debug_urls=1`
- Muestra información completa en formato HTML
- Script de consola automático para verificación JavaScript

### 📊 Endpoints AJAX
- `wp_ajax_debug_validate_urls` - Validación programática
- `wp_ajax_debug_url_generation` - Debug de generación de URLs
- Protegidos con nonces y permisos de administrador

### 🔍 Funciones Globales
- `get_debug_url_data()` - Datos programáticos
- `validate_url_consistency()` - Validación de consistencia
- `log_url_issues()` - Registro en error.log
- `get_debug_validation_nonce()` - Generación de nonces

## Métodos de Uso

### 1. Debug Visual Directo

Agrega estos parámetros a cualquier URL del admin de WordPress:

```
# Debug general de configuración
https://tu-sitio.local/wp-admin/tools.php?page=dev_tools&debug_config=1

# Debug específico de URLs
https://tu-sitio.local/wp-admin/admin.php?debug_urls=1
```

### 2. Uso Programático desde PHP

```php
// Obtener datos de debug
$debug_data = get_debug_url_data();
if (!$debug_data['success']) {
    error_log('Dev-Tools no está cargado correctamente');
}

// Validar consistencia
$issues = validate_url_consistency($urls, $config);
if (!empty($issues)) {
    log_url_issues($issues, 'MI_PLUGIN_DEBUG');
}

// Usar la clase directamente
$debug_system = DevToolsDebugWordPressDynamic::getInstance();
$url_debug = $debug_system->get_url_generation_debug();
```

### 3. Validación AJAX desde JavaScript

```javascript
// Función de prueba completa disponible en:
// test-debug-system-consolidated.js

// Validación de URLs
const debugData = {
    action: 'debug_validate_urls',
    nonce: 'tu-nonce-aqui'
};

fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(debugData)
})
.then(response => response.json())
.then(result => {
    if (result.success) {
        console.log('✅ Validación exitosa:', result.data);
    }
});
```

## Información Proporcionada

### Debug de Configuración General (`?debug_config=1`)
- ✅ Estado de carga de Dev-Tools
- 🌐 URLs dinámicas detectadas
- ⚙️ Configuración consolidada
- 🔧 Variables JavaScript disponibles
- ❌ Issues y problemas detectados
- 📝 Script de consola para verificación

### Debug de URLs (`?debug_urls=1`)
- 📁 Rutas de archivos y directorios
- 🔗 Comparación de métodos de generación de URLs:
  - `plugin_dir_url()` - Simple y directo
  - Construcción manual - Flexible pero complejo
  - **Configuración consolidada** - ⭐ RECOMENDADO
- ✅ Análisis de consistencia entre métodos
- 💡 Recomendaciones de mejores prácticas

## Integración con Dev-Tools Core

### Carga Automática
```php
// En dev-tools/loader.php
require_once __DIR__ . '/core/DebugWordPressDynamic.php';
```

### Hooks Integrados
- `admin_init` - Manejo de peticiones de debug
- `admin_init` - Validación automática en modo debug
- `wp_ajax_*` - Endpoints AJAX seguros

### Singleton Pattern
```php
// Uso interno del sistema
$debug_instance = DevToolsDebugWordPressDynamic::getInstance();
```

## Validación de URLs Dinámicas

### Métodos Analizados

1. **plugin_dir_url()** 
   - ✅ Pros: Simple, directo
   - ❌ Cons: Dependiente del archivo actual

2. **Construcción Manual**
   - ✅ Pros: Flexible
   - ❌ Cons: Complejo, propenso a errores

3. **Configuración Consolidada** ⭐ **RECOMENDADO**
   - ✅ Pros: Dinámico, centralizado, mantenible
   - ❌ Cons: Ninguno

### Ejemplo de Resultado
```php
[
    'methods_consistent' => true,
    'recommended_method' => 'method_3_consolidated_config',
    'url_methods' => [
        'method_3_consolidated_config' => [
            'base_url' => 'https://tarokina-2025.local/wp-content/plugins/tarokina-2025/dev-tools/',
            'css_url' => '...dev-tools/dist/css/dev-tools-styles.min.css',
            'js_url' => '...dev-tools/dist/js/dashboard.min.js'
        ]
    ]
]
```

## Logging y Monitoreo

### Error Log (Local by Flywheel)
```bash
# Ubicación del log
/Users/fernandovazquezperez/Local Sites/tarokina-2025/logs/php/error.log

# Monitoreo en tiempo real
tail -f "/Users/fernandovazquezperez/Local Sites/tarokina-2025/logs/php/error.log"
```

### Formato de Logs
```
🔧 DEV-TOOLS MANUAL_DEBUG ISSUES:
   - URL inconsistency detected between methods
   - Missing configuration variable: dev_tools.menu_slug
```

## Casos de Uso Comunes

### 1. Debug de Plugin en Desarrollo
```bash
# Abrir página de dev-tools con debug
https://tu-sitio.local/wp-admin/tools.php?page=dev_tools&debug_config=1
```

### 2. Validación de URLs en Deploy
```php
// En el hook de activación del plugin
register_activation_hook(__FILE__, function() {
    $debug_data = get_debug_url_data();
    if (!empty($debug_data['issues'])) {
        error_log('Issues detectados en activación: ' . print_r($debug_data['issues'], true));
    }
});
```

### 3. Diagnóstico de Problemas 404
```javascript
// En consola del navegador
console.log('🔧 Testing Dev-Tools Debug System...');
// Ejecutar test-debug-system-consolidated.js
```

## Compatibilidad y Fallbacks

### Funciones Helper Globales
Mantienen compatibilidad con código existente:
- `get_debug_url_data()`
- `validate_url_consistency()`
- `log_url_issues()`
- `get_debug_validation_nonce()`

### Fallback cuando Dev-Tools no está cargado
```php
if (!function_exists('dev_tools_config')) {
    // Mostrar URLs básicas de WordPress
    // Detectar problemas de carga
    // Proporcionar información de diagnóstico
}
```

## Seguridad

### Restricciones de Acceso
- ✅ Solo usuarios con permisos `manage_options`
- ✅ Nonces en todos los endpoints AJAX
- ✅ Verificación de contexto de WordPress (`ABSPATH`)
- ✅ Solo activo en modo debug (`WP_DEBUG`)

### Prevención de Acceso Directo
```php
if (!defined('ABSPATH')) {
    exit;
}
```

## Archivos Relacionados

- 📄 **Core**: `dev-tools/core/DebugWordPressDynamic.php`
- 🔧 **Loader**: `dev-tools/loader.php` (carga automática)
- 🧪 **Test** (opcional): `test-debug-system-consolidated.js` (en plugin padre)
- 📋 **Config**: `dev-tools/config.php` (configuración consolidada)
- ✅ **Verificación**: `dev-tools/verify-debug-system.sh` (plugin-agnóstico)

## Sistema Plugin-Agnóstico

### Detección Automática
El sistema detecta automáticamente su ubicación y funciona con cualquier plugin que use Dev-Tools:

```bash
# El script de verificación detecta automáticamente la ruta
./dev-tools/verify-debug-system.sh
```

### Rutas Dinámicas
- ✅ **Sin rutas hardcodeadas** - Se adapta a cualquier plugin
- ✅ **Detección automática** - Usa `dirname` para encontrar paths
- ✅ **Verificación inteligente** - Busca archivos relativos al directorio de dev-tools

## Próximas Mejoras

- [ ] **Dashboard Module** - Integración visual en panel principal
- [ ] **API REST** - Endpoints públicos para herramientas externas
- [ ] **Cache Debugging** - Debug específico del sistema de cache
- [ ] **Performance Metrics** - Medición de rendimiento en tiempo real
- [ ] **Export/Import** - Exportar configuración de debug

---

## 🚀 Quick Start

```bash
# 1. Compilar dev-tools (si es necesario)
cd dev-tools && npm run dev

# 2. Activar modo debug
# En wp-config.php: define('WP_DEBUG', true);

# 3. Probar debug visual
# URL: /wp-admin/tools.php?page=dev_tools&debug_config=1

# 4. Ejecutar test de consola
# Abrir consola del navegador y ejecutar test-debug-system-consolidated.js
```

**El sistema está listo para usar automáticamente en cualquier plugin que utilice Dev-Tools Arquitectura 3.0** 🎯
