# 📚 Guía Completa de Uso del Sistema de Tests - Tarokina Pro

## 🎯 Introducción

El sistema de testing de Tarokina Pro utiliza **WordPress PHPUnit oficial** con extensiones personalizadas (`DevToolsTestCase`) que proporcionan características avanzadas como protección anti-deadlock, debugging avanzado y compatibilidad completa con Local by Flywheel.

## 🚀 Comandos Básicos

### Ejecutar Todos los Tests
```bash
# Método recomendado (script optimizado)
./run-tests.sh

# Método directo con PHPUnit
./vendor/bin/phpunit --configuration=phpunit.xml
```

### Ejecutar Tests por Categoría
```bash
# Solo tests unitarios (rápidos, lógica pura)
./run-tests.sh --unit

# Solo tests de integración (WordPress completo)
./run-tests.sh --integration

# Alternativa con PHPUnit directo
./vendor/bin/phpunit --configuration=phpunit.xml --testsuite=tarokina-unit-tests
./vendor/bin/phpunit --configuration=phpunit.xml --testsuite=tarokina-integration-tests
```

### Ejecutar Tests Específicos
```bash
# Por nombre de clase
./run-tests.sh --filter=TarokinaLicenseTransientsTest

# Por método específico
./vendor/bin/phpunit --filter=TarokinaLicenseTransientsTest::testAnalyzeRealLicenseTransients

# Por patrón
./vendor/bin/phpunit --filter="DevTools.*Test"
```

## 🔍 Modos de Debugging y Verbose

### Modo Verbose Estándar
```bash
# Ver output descriptivo completo de todos los tests
./run-tests.sh --verbose

# Con PHPUnit directo
./vendor/bin/phpunit --configuration=phpunit.xml --verbose
```

**Qué muestra:**
- Headers descriptivos de cada test
- Acciones paso a paso
- Resultados detallados
- Métricas de performance

### Modo Debug Específico para Transients
```bash
# Activar debugging específico para el test de transients
TAROKINA_DEBUG_TRANSIENTS=1 ./vendor/bin/phpunit --filter=testShowDetailedTransientInfo

# Combinado con verbose para máximo detalle
TAROKINA_DEBUG_TRANSIENTS=1 ./vendor/bin/phpunit --filter=TarokinaLicenseTransientsTest --verbose
```

**Qué muestra:**
- 📊 Total de transients encontrados
- 📋 Detalles de cada transient (nombre, tamaño, autoload, preview)
- ⏰ Análisis de timeouts con fechas de expiración
- 🏷️ Categorización automática
- ⏱️ Tiempo restante calculado en formato legible

### Variables de Entorno de Debug
```bash
# Debug específico de transients de licencia
export TAROKINA_DEBUG_TRANSIENTS=1

# Debug general del sistema DevTools
export DEV_TOOLS_TESTS_DEBUG=1

# Modo verbose para todos los tests
export DEV_TOOLS_TESTS_VERBOSE=1

# Ejecutar con las variables activas
./run-tests.sh
```

## 🛠️ Extensión de DevToolsTestCase

### ¿Qué es DevToolsTestCase?

`DevToolsTestCase` es una extensión de `WP_UnitTestCase` que añade:

1. **Sistema Anti-deadlock**: Previene bloqueos de BD durante tests masivos
2. **Configuración Optimizada**: Para Local by Flywheel y entornos de desarrollo
3. **Debugging Avanzado**: Sistema de logging y diagnóstico integrado
4. **Protección de Concurrencia**: Manejo seguro de tests paralelos

### Cómo Extender DevToolsTestCase

```php
<?php
/**
 * Ejemplo de test personalizado extendiendo DevToolsTestCase
 */
class MiTestPersonalizadoTest extends DevToolsTestCase
{
    private $verbose_mode = false;
    private $test_results = [];
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Detectar modo verbose para logging condicional
        $this->verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
    }
    
    /**
     * Test básico con logging interno
     */
    public function testMiFuncionalidad(): void
    {
        // Logging interno (no produce output externo)
        $this->logResult('test_inicio', ['timestamp' => time()]);
        
        // Tu lógica de test aquí
        $resultado = mi_funcion_a_testear();
        
        // Assertions normales
        $this->assertNotNull($resultado);
        $this->assertTrue($resultado['success']);
        
        // Logging de resultado
        $this->logResult('test_resultado', $resultado);
    }
    
    /**
     * Test con output condicional (solo en modo verbose)
     */
    public function testConOutputVerbose(): void
    {
        if ($this->verbose_mode) {
            echo "\n🧪 EJECUTANDO MI TEST PERSONALIZADO\n";
            echo "📊 Información detallada...\n";
        }
        
        // Lógica del test...
        $this->assertTrue(true);
    }
    
    /**
     * Test con debugging específico activado por variable de entorno
     */
    public function testConDebugEspecifico(): void
    {
        $debug_enabled = (getenv('MI_DEBUG_VARIABLE') === '1');
        
        if (!$debug_enabled) {
            $this->markTestSkipped('Debug no activado. Usa MI_DEBUG_VARIABLE=1');
            return;
        }
        
        // Lógica de debugging específico...
        echo "\n🔍 INFORMACIÓN DETALLADA DE DEBUG\n";
        
        $this->assertTrue(true);
    }
    
    /**
     * Método helper para logging interno
     */
    private function logResult(string $test_name, array $data): void
    {
        $this->test_results[$test_name] = $data;
    }
}
```

### Características Automáticas de DevToolsTestCase

#### 1. Sistema Anti-deadlock
```php
// Se activa automáticamente en contextos riesgosos
// Configura isolation levels MySQL optimizados
// Previene bloqueos durante tests masivos
```

#### 2. Información Diagnóstica
```php
// Accede a información de contexto del sistema
$info = $this->getAntiDeadlockInfo();
echo "Sistema anti-deadlock: " . ($info['anti_deadlock_active'] ? 'ACTIVO' : 'INACTIVO');
```

#### 3. Configuración de BD Optimizada
```php
// Compatible con Local by Flywheel automáticamente
// Manejo seguro de conexiones
// Limpieza automática después de cada test
```

## 📊 Análisis de Resultados

### Output Estándar
```bash
PHPUnit 9.6.23 by Sebastian Bergmann and contributors.

....                                                                4 / 4 (100%)

Time: 00:00.029, Memory: 44.50 MB

OK (4 tests, 35 assertions)
```

### Output Verbose (con --verbose)
```bash
🧪 TEST #1: Funcionalidad Básica de Transients
======================================================================
📋 Configurando transient
   └─ key: dev_tools_test_transient
   └─ expiration: 3600 segundos
   └─ data_type: array

📊 RESUMEN: Basic Transients - 5 tests completados
```

### Output de Debug Específico (TAROKINA_DEBUG_TRANSIENTS=1)
```bash
================================================================================
🔍 INFORMACIÓN DETALLADA DE TRANSIENTS DE LICENCIA
================================================================================
🔧 Configuración BD: local
🏷️  Prefijo: wp_

📊 Total transients encontrados: 6

📋 DETALLES DE TRANSIENTS:
--------------------------------------------------------------------------------
• _transient_lic_tarokina_con
  ├─ Tamaño: 5 bytes
  ├─ Autoload: off
  └─ Preview: valid

⏰ ANÁLISIS DE TIMEOUTS:
--------------------------------------------------------------------------------
• lic_tarokina_con: ✅ ACTIVO - 06-06-2025 18:44:39 (8h 53m restante)
```

## 🎛️ Configuración Avanzada

### Variables de Entorno Disponibles

```bash
# Sistema de debugging
export TAROKINA_DEBUG_TRANSIENTS=1    # Debug específico de transients
export DEV_TOOLS_TESTS_DEBUG=1        # Debug general del sistema
export DEV_TOOLS_TESTS_VERBOSE=1      # Modo verbose global

# Sistema anti-deadlock
export DEV_TOOLS_DISABLE_ANTI_DEADLOCK=1    # Desactivar anti-deadlock
export DEV_TOOLS_FORCE_ANTI_DEADLOCK=1      # Forzar anti-deadlock

# Performance
export WP_TESTS_SKIP_INSTALL=1        # Saltar reinstalación WordPress
```

### Configuración PHPUnit (phpunit.xml)

```xml
<!-- Configuraciones personalizables -->
<phpunit
    verbose="true"                          <!-- Output detallado -->
    stopOnError="false"                     <!-- Continuar tras errores -->
    beStrictAboutOutputDuringTests="false"  <!-- Permitir output en tests -->
>
    <!-- Test suites configurados -->
    <testsuites>
        <testsuite name="tarokina-unit-tests">
            <directory>./tests/unit/</directory>
        </testsuite>
        <testsuite name="tarokina-integration-tests">
            <directory>./tests/integration/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## 🔧 Cobertura de Código

### Generar Reporte de Cobertura
```bash
# Cobertura HTML (recomendado)
./run-tests.sh --coverage-html=coverage/

# Cobertura en formato Clover XML
./vendor/bin/phpunit --coverage-clover=coverage.xml

# Cobertura en texto
./vendor/bin/phpunit --coverage-text
```

### Ver Resultados de Cobertura
```bash
# Abrir reporte HTML en navegador
open coverage/index.html

# En VS Code
code coverage/index.html
```

## 🚨 Troubleshooting

### Tests Marcados como "Risky"
```bash
# Problema: Tests producen output inesperado
# Solución: Usar logging interno en lugar de echo directo

# ❌ Incorrecto
echo "Debug info";

# ✅ Correcto
if ($this->verbose_mode) {
    echo "Debug info";
}
```

### Deadlocks de Base de Datos
```bash
# Problema: Tests se cuelgan o fallan por bloqueos BD
# Solución: DevToolsTestCase tiene protección automática

# Ver estado del sistema anti-deadlock
./vendor/bin/phpunit --filter=DevToolsConstantsTest --verbose
```

### Advertencias de Constantes en VS Code
```bash
# Problema: Constantes aparecen como indefinidas
# Solución: Las constantes están definidas en bootstrap.php

# Verificar constantes
./vendor/bin/phpunit --filter=testDevToolsConstantsAreDefined
```

### Lentitud en Tests
```bash
# Usar solo tests unitarios para desarrollo rápido
./run-tests.sh --unit

# Tests específicos
./run-tests.sh --filter=NombreDelTestRapido
```

## 📖 Ejemplos Prácticos

### Test Simple con Logging
```php
public function testSimpleConLogging(): void
{
    // Logging interno (siempre silencioso)
    $this->logResult('inicio', ['action' => 'testing_simple_function']);
    
    $resultado = mi_funcion();
    
    // Assertions normales
    $this->assertNotNull($resultado);
    
    // Log de resultado
    $this->logResult('resultado', ['value' => $resultado]);
}
```

### Test con Debug Condicional
```php
public function testConDebugCondicional(): void
{
    $debug = (getenv('MY_DEBUG') === '1') || $this->verbose_mode;
    
    if ($debug) {
        echo "\n🔍 EJECUTANDO TEST CON DEBUG\n";
        echo "📊 Datos del sistema: " . wp_debug_backtrace_summary() . "\n";
    }
    
    // Lógica del test...
    $this->assertTrue(true);
}
```

### Test con Información Avanzada de BD
```php
public function testBaseDatosAvanzado(): void
{
    global $wpdb;
    
    if ($this->verbose_mode) {
        echo "\n📊 INFO BD: {$wpdb->db_version()}\n";
        echo "🔗 Conexión: " . ($wpdb->check_connection() ? '✅' : '❌') . "\n";
    }
    
    // Tests de BD...
    $this->assertNotEmpty($wpdb->get_var("SELECT 1"));
}
```

## 🎯 Mejores Prácticas

### 1. Logging Interno vs Output Externo
```php
// ✅ Correcto: Logging interno silencioso
$this->logResult('test_name', $data);

// ✅ Correcto: Output condicional
if ($this->verbose_mode) {
    echo "Debug info\n";
}

// ❌ Incorrecto: Output siempre activo
echo "Debug info\n";
```

### 2. Manejo de Variables de Entorno
```php
// ✅ Correcto: Verificación robusta
$debug_enabled = (
    (getenv('MY_DEBUG_VAR') === '1') ||
    (isset($_ENV['MY_DEBUG_VAR']) && $_ENV['MY_DEBUG_VAR'] === '1') ||
    $this->verbose_mode
);

// ❌ Incorrecto: Solo getenv
$debug_enabled = getenv('MY_DEBUG_VAR') === '1';
```

### 3. Organización de Tests
```php
class MiTestTest extends DevToolsTestCase
{
    // ✅ Properties privadas para datos
    private $test_data = [];
    private $verbose_mode = false;
    
    // ✅ setUp para configuración común
    public function setUp(): void
    {
        parent::setUp();
        $this->verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
    }
    
    // ✅ Tests descriptivos con nombres claros
    public function testSpecificFunctionalityWithExpectedBehavior(): void
    {
        // Test implementation
    }
}
```

---

## 📞 Soporte y Recursos

### Archivos de Documentación Relacionados
- `docs/optimizaciones-sistema-tests.md` - Optimizaciones técnicas del sistema
- `docs/fix-license-transients-test.md` - Ejemplo específico del test de transients
- `docs/testing/expansiones-testing-futuras.md` - Planes de expansión del sistema
- `tests/DevToolsTestCase.php` - Código fuente de la clase base

### Comandos de Verificación del Sistema
```bash
# Verificar estado general
./run-tests.sh --filter=DevToolsConstantsTest

# Verificar configuración anti-deadlock
./vendor/bin/phpunit --filter=testAntiDeadlockInfo --verbose

# Test de ejemplo con output descriptivo
TAROKINA_DEBUG_TRANSIENTS=1 ./vendor/bin/phpunit --filter=testShowDetailedTransientInfo
```

---

**Fecha de Actualización**: 6 de junio de 2025  
**Versión**: 1.0.0  
**Estado**: ✅ Documentación Completa  
**Compatibilidad**: WordPress PHPUnit oficial + DevTools Extensions
