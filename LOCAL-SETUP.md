# Configuración Local Dev-Tools -        Tarokina

## 🎯 Propósito

Este directorio contiene configuraciones **específicas del plugin** que:
- ❌ **NO se comparten** entre diferentes plugins que usan dev-tools
- ❌ **NO se incluyen** en el submódulo git compartido  
- ✅ **Son específicas** para        Tarokina únicamente

## 📁 Archivos Locales Creados

### Configuración
- `config-local.php` - Configuración específica del plugin
- `wp-tests-config-local.php` - Configuración de testing local
- `phpunit-local.xml` - Configuración PHPUnit específica

### Scripts
- `setup-local.sh` - Script de inicialización (este archivo)
- `run-tests-local.sh` - Ejecutar tests con configuración local

### Directorios
- `tests/plugin-specific/` - Tests específicos de        Tarokina
- `reports/plugin-specific/` - Reports de testing locales
- `logs/plugin-specific/` - Logs específicos del plugin
- `fixtures/plugin-data/` - Datos de prueba específicos

## 🚀 Uso

### Ejecutar Tests Locales
```bash
# Tests usando configuración local (recomendado)
./run-tests-local.sh

# Tests específicos del plugin únicamente
./run-tests-local.sh tests/plugin-specific/

# Tests con cobertura
./run-tests-local.sh --coverage-html reports/plugin-specific/coverage
```

### Añadir Tests Específicos del Plugin
1. Crear archivos en `tests/plugin-specific/`
2. Usar `phpunit-local.xml` como configuración
3. Los reports se guardan en `reports/plugin-specific/`

## ⚠️ Importante

- Estos archivos están en `.gitignore` del submódulo
- Cada plugin tendrá su propia configuración local
- NO editar archivos del core compartido para configuraciones específicas

## 🔧 Personalización

Edita `config-local.php` para:
- Configurar Custom Post Types específicos
- Definir taxonomías del plugin
- Establecer funciones requeridas
- Configurar constantes específicas
