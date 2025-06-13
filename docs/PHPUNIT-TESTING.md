# PHPUnit Testing Framework - Dev-Tools Arquitectura 3.0

## 🎯 Resumen

Este documento describe la configuración completa de PHPUnit para testing automatizado en **Dev-Tools Arquitectura 3.0** dentro del entorno **Local by WP Engine** en macOS.

## ✅ Estado de la Implementación

- **✅ PHPUnit 9.6.23** - Framework de testing instalado via Composer
- **✅ WordPress Test Suite** - wp-phpunit/wp-phpunit 6.8.1 integrado
- **✅ Local by WP Engine** - Conexión por socket configurada correctamente
- **✅ Base de Datos** - Aislamiento con prefijo `wptests_`
- **✅ Autoloader** - PSR-4 configurado para clases de testing
- **✅ Coverage** - Reportes HTML y Clover configurados

## 📦 Componentes de la WordPress Test Suite

### 🔧 **Dependencias Instaladas Automáticamente**

La instalación de Dev-Tools incluye automáticamente **todos los componentes necesarios** para testing de WordPress:

#### **1. PHPUnit Framework**
```json
"phpunit/phpunit": "^9"
```
- ✅ Framework principal de testing
- ✅ Versión 9.x (estable y moderna)
- ✅ Motor de ejecución de tests

#### **2. WordPress Test Suite Completa**
```json
"wp-phpunit/wp-phpunit": "^6.8"
```
- ✅ **WordPress Core PHPUnit Library** oficial
- ✅ **Bootstrap completo** para WordPress testing
- ✅ **Factory classes** para crear datos de prueba
- ✅ **TestCase classes** especializadas:
  - `WP_UnitTestCase` - Clase base para tests unitarios
  - `WP_Ajax_UnitTestCase` - Testing de AJAX
  - `WP_REST_TestCase` - Testing de REST API
  - `WP_XML_TestCase` - Testing de XML/RSS
  - `WP_Canonical_UnitTestCase` - Testing de URLs canónicas

#### **3. WordPress Test Data & Fixtures**
- ✅ **Mock objects** para WordPress classes
- ✅ **Factory system** para crear usuarios, posts, terms, etc.
- ✅ **WordPress database schema** para testing
- ✅ **Formatting test data** (XSS attacks, feeds, etc.)

#### **4. Polyfills y Compatibilidad**
```json
"yoast/phpunit-polyfills": "^4.0"
```
- ✅ Compatibilidad con versiones de PHPUnit
- ✅ Polyfills para funciones modernas

### 📁 **Estructura de la WordPress Test Suite**

```bash
vendor/wp-phpunit/wp-phpunit/
├── includes/
│   ├── bootstrap.php          # Bootstrap principal de WordPress
│   ├── testcase.php          # WP_UnitTestCase base
│   ├── testcase-ajax.php     # Testing AJAX WordPress
│   ├── testcase-rest-api.php # Testing REST API
│   ├── factory/              # Factory para crear datos
│   │   ├── class-wp-unittest-factory.php
│   │   ├── class-wp-unittest-factory-for-user.php
│   │   ├── class-wp-unittest-factory-for-post.php
│   │   └── class-wp-unittest-factory-for-term.php
│   ├── mock-*.php           # Mock objects de WordPress
│   └── utils.php            # Utilidades de testing
├── data/                    # Datos de prueba
│   ├── formatting/          # Tests de formateo
│   ├── feed/               # Tests de feeds RSS
│   └── images/             # Imágenes para tests
└── wp-tests-config.php     # Configuración de tests
```

### 🎯 **Capacidades de Testing Disponibles**

Con la instalación completa tienes acceso a:

- ✅ **WordPress Core Classes**: `WP_User`, `WP_Post`, `WP_Query`, `wpdb`, etc.
- ✅ **WordPress Functions**: `wp_insert_user()`, `get_user_by()`, `add_action()`, etc.
- ✅ **Database Testing**: Creación/manipulación de posts, usuarios, taxonomías
- ✅ **AJAX Testing**: Simulación de peticiones AJAX de WordPress
- ✅ **REST API Testing**: Testing de endpoints personalizados
- ✅ **Hook Testing**: Testing de actions y filters
- ✅ **Mock/Stub Patterns**: Patrones avanzados de testing

### ✨ **Instalación Autosuficiente**

**Una sola instalación incluye todo**:
```bash
composer install  # Instala automáticamente:
                  # ✅ PHPUnit ^9
                  # ✅ WordPress Test Suite completa
                  # ✅ Polyfills y compatibilidad
                  # ✅ Autoloading configurado
```

**No necesitas instalar nada adicional** - el framework es completamente autosuficiente.

## 🏗️ Arquitectura del Sistema de Testing

```
dev-tools/
├── tests/
│   ├── bootstrap.php              # Bootstrap principal
│   ├── wp-tests-config.php        # Configuración de BD para Local
│   ├── includes/
│   │   ├── TestCase.php          # Clase base para tests
│   │   └── Helpers.php           # Utilidades de testing
│   ├── unit/
│   │   └── DatabaseTest.php      # Tests de conexión a BD
│   ├── modules/
│   │   └── DashboardModuleTest.php # Tests de módulos
│   └── integration/              # Tests de integración
├── phpunit.xml.dist              # Configuración PHPUnit
├── composer.json                 # Dependencias de testing
└── vendor/                       # Dependencias Composer
    ├── phpunit/phpunit
    ├── wp-phpunit/wp-phpunit
    └── yoast/phpunit-polyfills
```

## 🔧 Configuración Técnica

### Dependencias Instaladas

```json
{
  "require-dev": {
    "phpunit/phpunit": "^9",
    "wp-phpunit/wp-phpunit": "^6.8",
    "yoast/phpunit-polyfills": "^4.0"
  }
}
```

**Nota**: Se removió el autoloader PSR-4 para simplificar el sistema y usar clases globales.

### Configuración de Base de Datos (Local by WP Engine)

La configuración utiliza el socket específico de Local by WP Engine para máxima compatibilidad:

```php
// tests/wp-tests-config.php
define( 'DB_NAME', 'local' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost:/Users/[USUARIO]/Library/Application Support/Local/run/[SITE_ID]/mysql/mysqld.sock' );
define( 'WP_TESTS_TABLE_PREFIX', 'wptests_' );
$table_prefix = 'wptests_';
```

### Aislamiento de Datos

- **Estrategia**: Prefijo de tabla dedicado (`wptests_`)
- **Base de Datos**: Misma BD de Local pero tablas separadas
- **Ventajas**: No requiere permisos CREATE DATABASE
- **Aislamiento**: 100% separación entre datos de desarrollo y testing

## 🚀 Comandos de Testing

### Ejecutar Tests

```bash
# Todos los tests
vendor/bin/phpunit

# Tests con formato testdox (más legible)
vendor/bin/phpunit --testdox

# Test específico
vendor/bin/phpunit tests/unit/DatabaseTest.php

# Tests con coverage HTML
vendor/bin/phpunit --coverage-html tests/coverage/html

# Tests con verbose output
vendor/bin/phpunit --verbose
```

### Gestión de Dependencias

```bash
# Instalar dependencias
composer install

# Actualizar dependencias de testing
composer update --dev

# Regenerar autoloader
composer dump-autoload
```

## 📋 Tests Implementados

### Tests de Base de Datos (DatabaseTest)
- ✅ Conexión a base de datos MySQL
- ✅ Verificación de prefijo de tablas (`wptests_`)
- ✅ Existencia de tablas de WordPress
- ✅ Creación de datos de testing
- ✅ Aislamiento de datos

### Tests de Módulos (DashboardModuleTest)
- ✅ Módulos Dev-Tools existentes (`DatabaseConnectionModule`, `SiteUrlDetectionModule`)
- ✅ Carga de clases de módulos
- ✅ Instanciación de módulos
- ✅ Integración con WordPress
- ✅ Configuración de módulos

### Resultado Actual
```
Database
 ✔ Database connection
 ✔ Table prefix
 ✔ Wordpress tables exist
 ✔ Create test data
 ✔ Data isolation

Dashboard Module
 ✔ Database connection module loaded
 ✔ Site url detection module loaded
 ✔ Module classes exist
 ✔ Module default config
 ✔ Module instantiation
 ✔ Wordpress integration

OK (11 tests, 38 assertions)
```

## 🎨 Estructura de Clases de Testing

### DevToolsTestCase Base

```php
class DevToolsTestCase extends WP_UnitTestCase {
    
    /**
     * Almacena la última respuesta AJAX
     */
    protected $_last_response = '';
    
    // Setup/teardown automático
    public function setUp(): void;
    public function tearDown(): void;
    
    // Helpers para Dev-Tools
    protected function create_admin_user();
    protected function create_test_post($args = []);
    protected function create_test_page($args = []);
    protected function create_test_user($role = 'subscriber', $args = []);
    protected function simulate_ajax_request($action, $data = []);
    protected function get_ajax_response();
    protected function assert_test_table_exists($table_name);
    protected function create_module_test_data($module_name, $data = []);
}
```

### DevToolsTestHelpers Disponibles

```php
class DevToolsTestHelpers {
    
    // Generación de datos de testing
    public static function generate_test_config($overrides = []);
    public static function generate_system_info_data();
    public static function generate_performance_data();
    public static function generate_log_data($level, $count);
    
    // Gestión de archivos temporales
    public static function create_temp_file($content, $extension);
    public static function cleanup_temp_files($pattern);
    
    // Verificación de estructura
    public static function verify_directory_structure($base_path);
    public static function verify_required_files($base_path);
}
```

### Uso de Factory (Actualizado)

```php
// ✅ CORRECTO - Método actual sin deprecaciones
$admin_id = static::factory()->user->create(['role' => 'administrator']);
$post_id = static::factory()->post->create(['post_title' => 'Test']);

// ❌ DEPRECADO - No usar
$admin_id = $this->factory->user->create(['role' => 'administrator']);
```

## ⚙️ Configuración PHPUnit (phpunit.xml.dist)

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
        <testsuite name="Dev-Tools Test Suite">
            <directory>./tests/unit/</directory>
            <directory>./tests/modules/</directory>
            <directory>./tests/integration/</directory>
        </testsuite>
    </testsuites>
    
    <coverage includeUncoveredFiles="true">
        <include>
            <directory suffix=".php">./includes/</directory>
            <directory suffix=".php">./modules/</directory>
            <directory suffix=".php">./config/</directory>
            <file>./loader.php</file>
        </include>
        <exclude>
            <directory>./tests/</directory>
            <directory>./vendor/</directory>
            <directory>./node_modules/</directory>
        </exclude>
        <report>
            <html outputDirectory="tests/coverage/html"/>
            <clover outputFile="tests/coverage/clover.xml"/>
        </report>
    </coverage>
    
    <php>
        <env name="WP_PHPUNIT__TESTS_CONFIG" value="tests/wp-tests-config.php"/>
        <env name="WP_PHPUNIT__TABLE_PREFIX" value="wptests_"/>
        <const name="DEV_TOOLS_TESTING" value="true"/>
        <const name="DEV_TOOLS_TEST_MODE" value="unit"/>
    </php>
</phpunit>
```

## 🛠️ Troubleshooting

### Problemas Comunes

#### Error de Conexión a Base de Datos
```bash
# Verificar socket MySQL de Local
find "/Users/$(whoami)/Library/Application Support/Local" -name "mysqld.sock" 2>/dev/null

# Verificar configuración
cat tests/wp-tests-config.php | grep DB_HOST
```

#### Clases No Encontradas
```bash
# Regenerar autoloader
composer dump-autoload

# Verificar namespace en tests
grep -r "namespace" tests/
```

#### Tests No Se Ejecutan
```bash
# Verificar estructura de archivos
find tests -name "*Test.php" -type f

# Verificar configuración PHPUnit
vendor/bin/phpunit --configuration phpunit.xml.dist --dry-run
```

### Logs de Debugging

```bash
# Logs de Local by WP Engine
tail -f "/Users/$(whoami)/Local Sites/tarokina-2025/logs/php/error.log"

# Output de PHPUnit con debug
vendor/bin/phpunit --debug
```

## 📊 Coverage Reports

### Generar Coverage HTML
```bash
vendor/bin/phpunit --coverage-html tests/coverage/html
open tests/coverage/html/index.html
```

### Coverage Clover (para CI/CD)
```bash
vendor/bin/phpunit --coverage-clover tests/coverage/clover.xml
```

## 🔄 Integración Continua

### GitHub Actions (ejemplo)
```yaml
name: PHPUnit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Install dependencies
      run: composer install --dev
      
    - name: Run tests
      run: vendor/bin/phpunit --coverage-clover coverage.xml
```

## 📈 Métricas Actuales

- **Tests**: 11 tests implementados
- **Assertions**: 38 assertions ejecutadas
- **Coverage**: En configuración (requiere Xdebug)
- **Performance**: ~0.3 segundos por suite completa
- **Confiabilidad**: 100% tests passing
- **Módulos**: DatabaseConnectionModule y SiteUrlDetectionModule testeados
- **Sin Deprecaciones**: Código actualizado con `static::factory()`

## 🎯 Próximos Pasos

### Fase 2: Expansión de Tests
- [ ] Tests para todos los módulos Dev-Tools
- [ ] Tests de integración AJAX
- [ ] Tests de performance y caching
- [ ] Tests de sistema completo

### Fase 3: Optimización
- [ ] Configurar coverage con Xdebug
- [ ] Implementar tests paralelos
- [ ] Métricas de calidad de código
- [ ] Integración con CI/CD

## 📚 Referencias

- [WordPress PHPUnit Handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Local by WP Engine](https://localwp.com/)
- [Composer Documentation](https://getcomposer.org/doc/)

## 🏷️ Tags

`phpunit` `testing` `wordpress` `local-wp` `dev-tools` `arquitectura-3.0` `automation` `quality-assurance`

---

**Última actualización**: Junio 12, 2025  
**Estado**: ✅ Funcional y documentado  
**Entorno**: Local by WP Engine + macOS + PHP 8.3 + PHPUnit 9
