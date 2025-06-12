# Troubleshooting PHPUnit en Local by WP Engine

## 🚨 Problemas Comunes y Soluciones

### 1. Error de Conexión a Base de Datos

#### Síntomas
```
Error Establishing a Database Connection
Cannot connect to database
```

#### Diagnóstico
```bash
# Verificar socket MySQL actual
find "/Users/$(whoami)/Library/Application Support/Local" -name "mysqld.sock" 2>/dev/null

# Verificar configuración actual
cat tests/wp-tests-config.php | grep DB_HOST
```

#### Solución
1. **Verificar ruta del socket**: El socket puede cambiar si Local recrea el sitio
2. **Actualizar wp-tests-config.php** con la ruta correcta:
```php
define( 'DB_HOST', 'localhost:/Users/[USUARIO]/Library/Application Support/Local/run/[NUEVO_ID]/mysql/mysqld.sock' );
```

### 2. Socket MySQL No Encontrado

#### Síntomas
```
Can't connect to local MySQL server through socket
No such file or directory
```

#### Diagnóstico
```bash
# Buscar todos los sockets MySQL
sudo find /Users -name "mysqld.sock" 2>/dev/null

# Verificar que Local esté ejecutándose
ps aux | grep mysql
```

#### Solución
1. **Reiniciar Local by WP Engine**
2. **Verificar que el sitio está activo** en Local
3. **Obtener nueva ruta del socket**:
```bash
# Método 1: Buscar directamente
ls "/Users/$(whoami)/Library/Application Support/Local/run/"

# Método 2: Desde logs de Local
tail -f ~/Library/Logs/local-lightning.log | grep socket
```

### 3. Constantes Ya Definidas

#### Síntomas
```
PHP Warning: Constant WP_TESTS_TABLE_PREFIX already defined
PHP Warning: Constant WP_TESTS_DOMAIN already defined
```

#### Diagnóstico
El problema ocurre cuando las constantes se definen en múltiples lugares.

#### Solución
Usar verificación condicional en `wp-tests-config.php`:
```php
if ( ! defined( 'WP_TESTS_TABLE_PREFIX' ) ) {
    define( 'WP_TESTS_TABLE_PREFIX', 'wptests_' );
}

if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
    define( 'WP_TESTS_DOMAIN', 'example.org' );
}
```

### 4. Polyfills No Encontrados

#### Síntomas
```
Error: The PHPUnit Polyfills library is a requirement for running the WP test suite
```

#### Solución
```bash
composer require --dev yoast/phpunit-polyfills
```

### 5. Clases de Test No Encontradas

#### Síntomas
```
Class DashboardModuleTest could not be found
```

#### Diagnóstico
```bash
# Verificar autoloader
composer dump-autoload

# Verificar namespace en archivos de test
grep -r "namespace" tests/
```

#### Solución
1. **Regenerar autoloader**:
```bash
composer dump-autoload
```

2. **Verificar composer.json**:
```json
{
  "autoload-dev": {
    "psr-4": {
      "DevTools\\Tests\\": "tests/"
    }
  }
}
```

3. **Verificar estructura de clases**:
```php
// Correcto
use DevTools\Tests\TestCase;

class MiTest extends TestCase {
    // ...
}
```

### 6. No Tests Executed

#### Síntomas
```
PHPUnit X.X.X by Sebastian Bergmann and contributors.

Time: 00:00.001, Memory: 4.00 MB

No tests executed!
```

#### Diagnóstico
```bash
# Verificar archivos de test existentes
find tests -name "*Test.php" -type f

# Verificar configuración PHPUnit
vendor/bin/phpunit --configuration phpunit.xml.dist --dry-run
```

#### Solución
1. **Verificar phpunit.xml.dist**:
```xml
<testsuites>
    <testsuite name="Dev-Tools Test Suite">
        <directory>./tests/unit/</directory>
        <directory>./tests/modules/</directory>
    </testsuite>
</testsuites>
```

2. **Verificar nombrado de archivos**: Deben terminar en `Test.php`

3. **Verificar métodos de test**: Deben empezar con `test_`

### 7. Variable $table_prefix Undefined

#### Síntomas
```
PHP Warning: Undefined variable $table_prefix
```

#### Solución
Agregar en `wp-tests-config.php`:
```php
define( 'WP_TESTS_TABLE_PREFIX', 'wptests_' );
$table_prefix = 'wptests_';
```

### 8. Memory Limit Exceeded

#### Síntomas
```
Fatal error: Allowed memory size exhausted
```

#### Solución
1. **Aumentar memory limit en PHP**:
```bash
php -d memory_limit=512M vendor/bin/phpunit
```

2. **Configurar en wp-tests-config.php**:
```php
ini_set('memory_limit', '512M');
```

## 🔍 Herramientas de Diagnóstico

### Logs de Local by WP Engine
```bash
# Log principal de Local
tail -f ~/Library/Logs/local-lightning.log

# Log de PHP del sitio
tail -f "/Users/$(whoami)/Local Sites/tarokina-2025/logs/php/error.log"

# Log de MySQL del sitio
tail -f "/Users/$(whoami)/Local Sites/tarokina-2025/logs/mysql/error.log"
```

### Debug de PHPUnit
```bash
# Ejecutar con debug completo
vendor/bin/phpunit --debug --verbose

# Dry run para verificar configuración
vendor/bin/phpunit --dry-run

# Verificar configuración XML
vendor/bin/phpunit --configuration phpunit.xml.dist --dry-run
```

### Verificación de Entorno
```bash
# Verificar PHP
php -v

# Verificar extensiones PHP necesarias
php -m | grep -E "(mysqli|pdo_mysql|json|mbstring)"

# Verificar Composer
composer --version

# Verificar dependencias
composer show --dev
```

## ⚡ Soluciones Rápidas

### Reset Completo del Entorno
```bash
# 1. Limpiar vendor
rm -rf vendor/ composer.lock

# 2. Reinstalar dependencias
composer install

# 3. Regenerar autoloader
composer dump-autoload

# 4. Verificar socket actual
find "/Users/$(whoami)/Library/Application Support/Local" -name "mysqld.sock"

# 5. Actualizar wp-tests-config.php con nueva ruta

# 6. Ejecutar test básico
vendor/bin/phpunit tests/unit/DatabaseTest.php
```

### Verificación de Configuración Mínima
```bash
# Script de verificación rápida
cat > verify-setup.php << 'EOF'
<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "MySQL Extension: " . (extension_loaded('mysqli') ? 'OK' : 'MISSING') . "\n";

$socket_path = '/Users/' . get_current_user() . '/Library/Application Support/Local';
$sockets = glob($socket_path . '/run/*/mysql/mysqld.sock');
echo "MySQL Sockets found: " . count($sockets) . "\n";
foreach ($sockets as $socket) {
    echo "  - $socket\n";
}

if (file_exists('tests/wp-tests-config.php')) {
    echo "wp-tests-config.php: EXISTS\n";
} else {
    echo "wp-tests-config.php: MISSING\n";
}

if (file_exists('vendor/bin/phpunit')) {
    echo "PHPUnit: INSTALLED\n";
} else {
    echo "PHPUnit: MISSING\n";
}
EOF

php verify-setup.php
rm verify-setup.php
```

## 📞 Contacto de Soporte

Para problemas específicos de Dev-Tools Arquitectura 3.0:
- **Documentación**: `docs/PHPUNIT-TESTING.md`
- **Referencia rápida**: `docs/PHPUNIT-QUICK-REFERENCE.md`
- **Issues**: Crear issue en el repositorio del proyecto

---

**Última actualización**: Junio 12, 2025  
**Basado en**: "Configuración Avanzada de PHPUnit para Plugins de WordPress en Local by WP Engine sobre macOS"
