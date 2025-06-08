# Dev-Tools Testing Framework - Arquitectura 3.0

Sistema de testing avanzado para Dev-Tools con múltiples niveles de testing y automatización.

## 📁 Estructura de Testing

```
tests/
├── 📋 README.md                       # Esta documentación
├── 🏗️ bootstrap.php                   # Bootstrap WordPress PHPUnit
├── 📝 DevToolsTestCase.php            # Clase base para tests
│
├── 🧪 TESTS CORE
│   ├── unit/                          # Tests unitarios (lógica pura)
│   │   ├── CoreSystemTest.php         # Tests sistema core
│   │   ├── ConfigTest.php             # Tests configuración
│   │   ├── ModuleManagerTest.php      # Tests gestor módulos
│   │   ├── AjaxHandlerTest.php        # Tests AJAX handler
│   │   └── modules/                   # Tests módulos individuales
│   │       ├── DashboardModuleTest.php
│   │       ├── SystemInfoModuleTest.php
│   │       └── CacheModuleTest.php
│   │
│   ├── integration/                   # Tests integración WordPress
│   │   ├── WordPressIntegrationTest.php
│   │   ├── AjaxEndpointsTest.php
│   │   ├── AdminPanelsTest.php
│   │   └── DatabaseTest.php
│   │
│   ├── e2e/                          # Tests End-to-End
│   │   ├── playwright.config.js      # Configuración Playwright
│   │   ├── setup/                    # Setup E2E
│   │   ├── specs/                    # Especificaciones tests
│   │   └── utils/                    # Utilidades E2E
│   │
│   ├── coverage/                     # Reports cobertura
│   │   ├── html/                     # Reports HTML
│   │   ├── xml/                      # Reports XML
│   │   └── json/                     # Reports JSON
│   │
│   └── ci/                          # CI/CD Pipeline
│       ├── github-actions.yml       # GitHub Actions
│       ├── phpunit.xml              # Config PHPUnit CI
│       └── scripts/                 # Scripts automatización
│
├── 🔧 UTILITIES
│   ├── fixtures/                     # Datos de prueba
│   ├── mocks/                       # Mocks y stubs
│   ├── helpers/                     # Helpers de testing
│   └── custom/                      # Tests personalizados
│
└── 📊 REPORTS
    ├── latest/                      # Últimos reports
    ├── history/                     # Historial reports
    └── artifacts/                   # Artefactos testing
```

## 🚀 Tipos de Tests

### 1. **Tests Unitarios** (`unit/`)
Tests de lógica pura, sin dependencias externas:
```bash
# Ejecutar todos los tests unitarios
./run-tests.sh --unit

# Test específico
./run-tests.sh --unit --filter=ConfigTest
```

### 2. **Tests de Integración** (`integration/`)
Tests que requieren WordPress y base de datos:
```bash
# Ejecutar tests de integración
./run-tests.sh --integration

# Con base de datos limpia
./run-tests.sh --integration --reset-db
```

### 3. **Tests E2E** (`e2e/`)
Tests completos del sistema usando Playwright:
```bash
# Instalar dependencias E2E
npm install @playwright/test

# Ejecutar tests E2E
npx playwright test

# Con UI
npx playwright test --ui
```

### 4. **Coverage Reports** (`coverage/`)
Generación de reportes de cobertura:
```bash
# Generar cobertura completa
./run-tests.sh --coverage

# Solo HTML
./run-tests.sh --coverage-html
```

## ⚙️ Configuración

### PHPUnit
```xml
<!-- phpunit.xml -->
<phpunit>
    <testsuites>
        <testsuite name="unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Playwright
```javascript
// playwright.config.js
module.exports = {
    testDir: './e2e/specs',
    use: {
        baseURL: 'http://localhost:10019',
        headless: true
    }
};
```

## 🔄 CI/CD Pipeline

### GitHub Actions
```yaml
name: Dev-Tools Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
      - name: Run Tests
        run: ./run-tests.sh --ci
```

## 📈 Métricas de Calidad

- **Code Coverage**: >80%
- **Unit Tests**: >90% funciones core
- **Integration Tests**: Todos los endpoints AJAX
- **E2E Tests**: Flujos críticos usuario
- **Performance**: <2s tiempo respuesta

## 🚦 Estados de Tests

- ✅ **Passing**: Todo funciona correctamente
- ⚠️ **Warning**: Tests pasan pero con advertencias
- ❌ **Failing**: Tests fallan, requiere atención
- ⏸️ **Skipped**: Tests saltados condicionalmente

## 📝 Convenciones

### Naming
- **Unit Tests**: `[ClassName]Test.php`
- **Integration**: `[Feature]IntegrationTest.php`
- **E2E**: `[workflow].spec.js`

### Estructura Test
```php
class ExampleTest extends DevToolsTestCase {
    public function setUp(): void {
        parent::setUp();
        // Setup específico
    }
    
    public function testFeature(): void {
        // Arrange
        // Act  
        // Assert
    }
}
```

## 🛠️ Herramientas

- **PHPUnit**: Framework testing PHP
- **Playwright**: Testing E2E moderno
- **PHPUnit Coverage**: Análisis cobertura
- **GitHub Actions**: CI/CD automatizado
- **WordPress Test Suite**: Entorno WordPress oficial

---

**Arquitectura 3.0** - Sistema de testing avanzado para desarrollo profesional.
