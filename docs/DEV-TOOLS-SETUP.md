# Dev-Tools Arquitectura 3.0 - Framework Agnóstico para WordPress

## 🎯 Objetivo
Crear un framework de dev-tools **completamente agnóstico y desacoplado** que funcione como **submódulo independiente de Git** para cualquier plugin de WordPress.

## 🚀 Visión del Sistema
**Dev-Tools Arquitectura 3.0** es un sistema de herramientas de desarrollo **plugin-agnóstico** diseñado para ser **el submódulo universal** para proyectos WordPress.

### 📦 Arquitectura de Submódulo
```
mi-plugin-wordpress/
├── ...archivos del plugin...
└── dev-tools/              ← Git submodule independiente
    ├── config/
    ├── modules/
    ├── dist/
    └── webpack.config.js
```

### 🎯 Características Agnósticas

#### 🚫 Zero Coupling
- **Sin referencias hardcodeadas** al plugin host
- **No asume nombres** de archivos, carpetas o clases específicas
- **APIs completamente genéricas** que funcionan con cualquier estructura

#### 🔄 Reutilización Universal
- **Un solo submódulo** sirve a múltiples proyectos
- **Configuración automática** para cada entorno
- **Sin modificaciones** necesarias al cambiar de proyecto

#### ⚙️ Auto-detección Inteligente
- **Entorno**: Local by WP Engine, Docker, staging, producción
- **URLs dinámicas**: Detecta automáticamente site_url() y plugin_url()
- **Base de datos**: Unix socket vs TCP/IP, credenciales WordPress
- **Estructura**: Adaptación a diferentes organizaciones de archivos

### 🏗️ Filosofía de Desarrollo
- **Plugin-first**: El dev-tools se adapta al plugin, no al revés
- **Environment-aware**: Funciona en cualquier entorno sin configuración manual
- **Framework-agnostic**: Compatible con cualquier metodología de desarrollo WordPress

## ⚠️ Configuración Especial: Local by WP Engine
Este proyecto utiliza **Local by WP Engine** para desarrollo local, lo que requiere configuración específica para la conexión a MySQL.

### 📊 Información del Entorno
- **Socket MySQL**: `/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock`
- **Entorno**: Local by WP Engine
- **WordPress**: Local development
- **Sistema**: macOS

## 🗃️ Paso 1: Módulo de Conexión a Base de Datos

### Problema Identificado
Local by WP Engine utiliza **Unix socket** para conexiones MySQL en lugar de TCP/IP estándar. Las conexiones tradicionales `localhost:3306` fallan.

### Solución Técnica
Basado en la documentación oficial de MySQL y PHP PDO:

1. **Para conexiones socket**: Usar `unix_socket` en lugar de `host`
2. **WordPress Integration**: Utilizar las constantes de WordPress cuando sea posible
3. **Fallback apropiado**: Tener método alternativo para otros entornos

### Configuración de Conexión PDO
```php
// ✅ CORRECTO para Local by WP engine
$dsn = 'mysql:unix_socket=/path/to/socket;dbname=database_name;charset=utf8mb4';

// ❌ INCORRECTO - No funciona en Local by WP engine
$dsn = 'mysql:host=localhost;port=3306;dbname=database_name';
```

### Implementación Propuesta
- Detectar automáticamente el entorno (Local by WP engine vs otros)
- Usar configuración WordPress existente como base
- Implementar fallback para diferentes entornos
- Logging detallado para debugging

---

## 📋 Plan de Desarrollo Agnóstico

### Paso 1: ✅ Investigación MySQL Local by WP Engine
**Completado**: Socket Unix `/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock`

### Paso 2: ✅ DatabaseConnectionModule
**Completado**: Módulo agnóstico para conexión MySQL con auto-detección de entorno

### Paso 2.1: ✅ SiteUrlDetectionModule  
**Completado**: Módulo agnóstico para detección de URLs con soporte Router Mode Local by WP Engine

### Paso 3: Estructura Base Agnóstica
- Configuración descentralizada sin referencias al plugin host
- Sistema de auto-detección de rutas y entorno
- APIs genéricas para integración universal

### Paso 4: Sistema de Configuración Desacoplada
- Config agnóstico que detecta automáticamente el entorno
- Carga dinámica sin dependencias del plugin host
- Configuración adaptable a cualquier estructura de proyecto

### Paso 5: Loader Principal Universal
- Cargador que funciona en cualquier plugin WordPress
- Detección automática de la ubicación del dev-tools
- Sin referencias hardcodeadas a archivos específicos

### Paso 6: Sistema de Overrides Plugin-Específicos
- Sistema tipo "child themes" para personalización sin modificar el core
- Directorio `plugin-dev-tools/` en el plugin host para overrides específicos
- Jerarquía de carga: plugin-dev-tools/ → dev-tools/ (fallback)
- Migración automática de archivos para customización
- Preserva integridad del submódulo agnóstico

### Paso 7: Sistema de Módulos Reutilizable
- Clase base para módulos completamente agnóstica
- Auto-registro sin configuración manual
- APIs estándar que funcionan en cualquier contexto

### Paso 8: AJAX Handler Genérico
- Manejador universal de peticiones AJAX
- Sistema de comandos desacoplado del plugin
- Seguridad y nonces automáticos

### Paso 9: Webpack Agnóstico
- Configuración universal para cualquier proyecto
- Detección automática de archivos a compilar
- Output adaptable a diferentes estructuras

### Paso 10: Framework de Testing Universal
- Testing que funciona en cualquier entorno WordPress
- Sin dependencias específicas del plugin host
- Casos de prueba reutilizables

### Paso 11: Documentación de Integración
- Guía para integrar como submódulo Git
- Configuración mínima requerida en el plugin host
- Mejores prácticas para uso agnóstico

---

---

## 🔬 Ejemplo de Implementación: DatabaseConnectionModule ✅

### Ubicación
`dev-tools/modules/DatabaseConnectionModule.php`

### Características Agnósticas Implementadas

#### 🚫 Zero Coupling en Acción
- **Sin referencias al plugin host**: No menciona "Tarokina" ni nombres específicos
- **APIs universales**: Funciona en cualquier WordPress sin modificaciones
- **Auto-configuración**: Detecta automáticamente el entorno sin configuración manual

#### ⚙️ Auto-detección Inteligente
- **Entorno Local by WP Engine**: Identificación automática de paths característicos
- **Socket MySQL**: Detección dinámica del socket Unix específico del usuario
- **Fallback universal**: TCP/IP para entornos de staging/producción
- **Credenciales WordPress**: Utiliza constantes existentes (DB_HOST, DB_NAME, etc.)

#### 🔄 Reutilización Universal
- **Testing independiente**: Script que funciona en cualquier instalación WordPress
- **Sin configuración específica**: No requiere setup particular del plugin host
- **APIs estándar**: Métodos que funcionan en cualquier contexto WordPress

### Conexión MySQL: Caso de Estudio Local by WP Engine
**Problema**: Local by WP Engine utiliza Unix socket en lugar de TCP/IP  
**Solución agnóstica**: Detección automática de entorno y adaptación de DSN  
**Resultado**: Socket detectado `/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock`  

### Testing del Módulo Agnóstico
```php
// El módulo detecta automáticamente el entorno y se configura
$db = new DatabaseConnectionModule(true);
$test_result = $db->test_connection();

// Resultado: Conexión exitosa usando socket Unix
// Output: Información completa del entorno detectado
```

---

## 📝 Estado del Desarrollo Agnóstico

### ✅ Completado
- **Investigación MySQL**: Socket Unix para Local by WP Engine identificado
- **DatabaseConnectionModule**: Módulo completamente agnóstico implementado y probado
- **SiteUrlDetectionModule**: Detección de URLs agnóstica con soporte Router Mode

### 🔄 Próximos Pasos del Sistema Agnóstico
- **Paso 3**: Estructura base agnóstica (config, loader, core)
- **Paso 4**: Sistema de configuración desacoplado
- **Paso 5**: Loader principal universal
- **Paso 6**: Sistema de overrides plugin-específicos
- **Paso 7**: Módulos reutilizables
- **Paso 8**: AJAX handler genérico
- **Paso 9**: Webpack agnóstico
- **Paso 10**: Testing universal
- **Paso 11**: Documentación como submódulo Git

---

## 🎯 Filosofía Agnóstica en Acción

El `DatabaseConnectionModule` es el **primer ejemplo** de cómo Dev-Tools Arquitectura 3.0 implementa el desacoplamiento total:

- **Plugin-agnostic**: Funciona en cualquier plugin WordPress sin modificaciones
- **Environment-aware**: Auto-detección de Local by WP Engine, staging, producción
- **Self-configuring**: No requiere configuración manual del usuario
- **Universal APIs**: Interfaces estándar que funcionan en cualquier contexto

### Como Submódulo Git
```bash
# En cualquier plugin WordPress:
git submodule add https://github.com/tu-repo/dev-tools.git dev-tools
cd dev-tools && npm install && npm run dev

# El sistema se auto-configura automáticamente
# Sin necesidad de modificar código del plugin host
```

---

## 🔧 Sistema de Overrides Plugin-Específicos

### 🎯 Concepto: Child Theme para Dev-Tools
Inspirado en el sistema de **child themes** de WordPress, el sistema de overrides permite:

- **Personalización sin modificar el core**: Cada plugin puede tener sus propios scripts
- **Preservar integridad del submódulo**: El dev-tools agnóstico permanece intacto
- **Versionado independiente**: Overrides específicos del plugin vs core universal

### 📁 Estructura Dual
```
mi-plugin-wordpress/
├── dev-tools/                  ← Submódulo Git agnóstico (core)
│   ├── config/
│   ├── modules/
│   └── webpack.config.js
└── plugin-dev-tools/           ← Overrides específicos del plugin
    ├── modules/
    ├── templates/
    ├── tests/
    └── logs/
```

### 🔄 Jerarquía de Carga
1. **Primero busca**: `plugin-dev-tools/` (overrides específicos)
2. **Luego fallback**: `dev-tools/` (core agnóstico)

### 🚀 Ventajas del Sistema
- **Zero coupling preservado**: Core agnóstico sin modificar
- **Customización segura**: Cambios específicos sin afectar otros proyectos
- **Compatibilidad futura**: Actualizaciones del core sin romper customizaciones
- **Desarrollo ágil**: Testing y desarrollo específico por plugin

### 💡 Casos de Uso
- **Módulos específicos**: Funcionalidades únicas del plugin
- **Templates customizados**: Interfaces específicas del proyecto
- **Tests particulares**: Casos de prueba específicos del dominio
- **Configuraciones locales**: Settings específicos del plugin

### 🔧 API de Overrides
```php
// Sistema automático de override
$override_system = new DevToolsOverrideSystem();

// Migrar archivo del core para customización
$override_system->migrate_to_override('config.php');
$override_system->migrate_to_override('modules/SystemInfoModule.php');

// Carga automática con fallback
$config = $override_system->load_config('config.php');           // plugin-dev-tools/ o dev-tools/
$override_system->include_file('modules/CustomModule.php');      // prioridad plugin-dev-tools/
```

---

## 🌐 SiteUrlDetectionModule ✅ **PROBADO Y FUNCIONAL**

### Ubicación
`dev-tools/modules/SiteUrlDetectionModule.php`

### 🎯 Problema Resuelto: Router Mode de Local by WP Engine
**Local by WP Engine** tiene dos modos de routing que generan URLs completamente diferentes:
- **Site Domains Mode**: `http://tarokina-2025.local` (dominio .local personalizado)
- **Localhost Mode**: `http://localhost:10019` (localhost + puerto dinámico)

### 🚫 Características Agnósticas Implementadas

#### ⚙️ Detección Universal sin WordPress
- **Funciona sin entorno WP**: Detecta URLs incluso ejecutando desde terminal
- **Múltiples métodos de detección**: wp-config parsing, server vars, Local config
- **Zero dependencies**: No requiere funciones WordPress para operar

#### 🔍 Auto-detección Inteligente Local by WP Engine
- **Router Mode detection automático**:
  - `localhost_mode`: Detecta `localhost:10019` (o cualquier puerto) y asigna `http://`
  - `site_domains_mode`: Detecta dominios `.local` personalizados y maneja HTTPS/HTTP
  - `not_local`: Entornos de staging/producción estándar
- **Puerto dinámico**: Maneja puertos como 10019, 10020, etc. automáticamente
- **Socket path correlation**: Relaciona con DatabaseConnectionModule para consistencia

#### 🔄 Métodos de Detección en Cascada
1. **WordPress function**: `get_site_url()` si está disponible
2. **wp-config parsing**: Lee `WP_HOME`/`WP_SITEURL` constants
3. **Local WP detection**: Configuración específica de Local by WP Engine
4. **Server variables**: Fallback universal con `$_SERVER`

### 📊 Resultados de Testing

#### ✅ Test Terminal (Agnóstico)
```bash
# Sin WordPress cargado - Localhost Mode
URL detectado: http://localhost:10019
Router Mode: localhost_mode
Es Local WP: SÍ
Método usado: wp_config_parsing
```

#### ✅ Test Browser - Site Domains Mode
```php
// Comparación automática con WordPress (Router Mode: Site Domains)
WordPress get_site_url(): http://tarokina-2025.local
Nuestro módulo detectó: http://tarokina-2025.local
¿Coinciden?: ✅ SÍ
```

#### ✅ Test Browser - Localhost Mode
```php
// Comparación automática con WordPress (Router Mode: Localhost)
WordPress get_site_url(): http://localhost:10019
Nuestro módulo detectó: http://localhost:10019
¿Coinciden?: ✅ SÍ
```

### 🚀 API de Uso
```php
// Uso estático rápido
$site_url = SiteUrlDetectionModule::get_current_site_url();

// Uso con debug y información completa
$detector = new SiteUrlDetectionModule(true);
$url = $detector->get_site_url();
$router_mode = $detector->get_environment_info()['router_mode'];
$all_methods = $detector->test_detection()['all_methods'];
```

### 🎪 Casos de Uso Cubiertos
- **Local by WP Engine Site Domains**: `http://tarokina-2025.local` ✅
- **Local by WP Engine Localhost**: `http://localhost:10019` (puerto dinámico) ✅
- **Docker/XAMPP**: Entornos de desarrollo alternativos ✅
- **Staging**: Dominios de prueba ✅
- **Producción**: Dominios finales con HTTPS ✅
- **Terminal execution**: Scripts CLI sin WordPress ✅

---

## 📚 Documentación del Proyecto

### 🎯 Documentación Principal
- **[DEV-TOOLS-SETUP.md](DEV-TOOLS-SETUP.md)** - Configuración y arquitectura general
- **[PHPUNIT-TESTING.md](PHPUNIT-TESTING.md)** - Framework de testing automatizado
- **[PHPUNIT-QUICK-REFERENCE.md](PHPUNIT-QUICK-REFERENCE.md)** - Referencia rápida de PHPUnit
- **[PHPUNIT-TROUBLESHOOTING.md](PHPUNIT-TROUBLESHOOTING.md)** - Solución de problemas de testing

### 🔧 Testing Framework
El sistema incluye un **framework completo de testing con PHPUnit** configurado específicamente para **Local by WP Engine**:

- ✅ **PHPUnit 9.6.23** con WordPress Test Suite
- ✅ **Conexión por socket MySQL** automática para Local
- ✅ **Aislamiento de datos** con prefijo de tablas `wptests_`
- ✅ **Coverage reports** HTML y Clover
- ✅ **Autoloader PSR-4** para clases de testing
- ✅ **CI/CD ready** con configuración para GitHub Actions

#### Estado Actual del Testing
```bash
Database Tests: 5/5 PASSING ✅
Module Tests: 3/5 PASSING ✅ (2 fallos esperados en desarrollo)
Framework: Completamente funcional ✅
Socket MySQL: Conectado correctamente ✅
```

#### Comandos de Testing
```bash
# Ejecutar todos los tests
vendor/bin/phpunit

# Tests con formato legible
vendor/bin/phpunit --testdox

# Coverage HTML
vendor/bin/phpunit --coverage-html tests/coverage/html
```
