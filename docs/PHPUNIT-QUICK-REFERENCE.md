# PHPUnit Quick Reference - Dev-Tools

## 🚀 Comandos Esenciales

```bash
# Directorio de trabajo
cd "/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/wp-content/plugins/tarokina-2025/dev-tools"

# Ejecutar todos los tests
vendor/bin/phpunit

# Tests con formato legible
vendor/bin/phpunit --testdox

# Test específico
vendor/bin/phpunit tests/unit/DatabaseTest.php

# Tests con coverage
vendor/bin/phpunit --coverage-html tests/coverage/html
```

## 🔧 Configuración Local by WP Engine

### Socket MySQL Detectado
```
/Users/fernandovazquezperez/Library/Application Support/Local/run/6ld71Gw6d/mysql/mysqld.sock
```

### Credenciales de Testing
```php
DB_NAME: 'local'
DB_USER: 'root'
DB_PASSWORD: 'root'
DB_HOST: 'localhost:/path/to/socket'
WP_TESTS_TABLE_PREFIX: 'wptests_'
```

## 📊 Estado Actual

```
✅ Database Tests: 5/5 PASSING
✅ Module Tests: 6/6 PASSING  
✅ Framework: Completamente funcional
✅ Coverage: Configurado y listo
✅ Sin Deprecaciones: Código actualizado
Total: 11 tests, 38 assertions ✅
```

## 🆘 Troubleshooting Rápido

### Error de Conexión
```bash
# Verificar socket
find "/Users/$(whoami)/Library/Application Support/Local" -name "mysqld.sock"

# Verificar configuración
cat tests/wp-tests-config.php | grep DB_HOST
```

### Classes Not Found
```bash
composer dump-autoload
```

### No Tests Executed
```bash
find tests -name "*Test.php" -type f
vendor/bin/phpunit --dry-run
```

## 📁 Estructura de Tests

```
tests/
├── bootstrap.php               # Bootstrap principal
├── wp-tests-config.php        # Config BD Local
├── includes/
│   ├── TestCase.php           # Clase base
│   └── Helpers.php            # Utilidades
├── unit/                      # Tests unitarios
├── modules/                   # Tests de módulos
└── integration/               # Tests de integración
```

## 🎯 Crear Nuevo Test

```php
<?php
class MiNuevoTest extends DevToolsTestCase {
    
    public function test_mi_funcionalidad() {
        // Setup - Usar métodos helper actualizados
        $admin_id = $this->create_admin_user();
        $post_id = $this->create_test_post(['post_title' => 'Test Post']);
        
        // Test
        $result = mi_funcion_a_testear($post_id);
        
        // Assertions
        $this->assertTrue($result);
        $this->assertEquals('expected', $result);
    }
    
    public function test_ajax_functionality() {
        // Setup
        $this->create_admin_user();
        wp_set_current_user($admin_id);
        
        // Simulate AJAX
        $this->simulate_ajax_request('my_action', ['data' => 'test']);
        
        // Verify response
        $response = $this->get_ajax_response();
        $this->assertStringContains('success', $response);
    }
}
```

## 📋 Checklist de Testing

- [ ] Test creado en directorio correcto
- [ ] Clase extiende `TestCase`
- [ ] Métodos empiezan con `test_`
- [ ] Assertions claras y específicas
- [ ] Setup/teardown si es necesario
- [ ] Test ejecuta sin errores

---

**Última actualización**: Junio 12, 2025
