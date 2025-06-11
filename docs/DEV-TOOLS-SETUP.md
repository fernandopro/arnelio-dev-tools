# Dev-Tools Arquitectura 3.0 - Setup Process

## 🎯 Objetivo
Crear desde cero el framework de desarrollo dev-tools con Arquitectura 3.0 para el plugin Tarokina Pro.

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

## 📝 Estado del Proceso
- [x] **Paso 1**: Investigación de conexión MySQL ✅
- [x] **Paso 2**: Crear módulo DatabaseConnection ✅
- [ ] **Paso 3**: Configurar estructura base dev-tools
- [ ] **Paso 4**: Sistema de configuración
- [ ] **Paso 5**: Loader principal
- [ ] **Paso 6**: Sistema de módulos
- [ ] **Paso 7**: AJAX handler
- [ ] **Paso 8**: Webpack configuration
- [ ] **Paso 9**: Testing framework
- [ ] **Paso 10**: Documentación final

---

## 🗃️ Paso 2: Módulo DatabaseConnection ✅

### Archivo Creado
`dev-tools/modules/DatabaseConnectionModule.php`

### Características Implementadas
1. **Auto-detección de entorno Local by WP Engine**
   - Verificación de paths característicos
   - Detección de socket MySQL automática
   - Fallback para otros entornos

2. **Conexión PDO optimizada**
   - Unix socket para Local by WP Engine
   - TCP/IP fallback para producción
   - Opciones PDO optimizadas para desarrollo

3. **Socket detection inteligente**
   - Socket específico del usuario: `/Users/fernandovazquezperez/Library/Application Support/Local/run/3AfHnCjli/mysql/mysqld.sock`
   - Búsqueda automática con glob patterns
   - Ubicaciones estándar Unix como fallback

4. **Debugging y testing**
   - Método `test_connection()` completo
   - Logging detallado en modo debug
   - Información de entorno estructurada

### Uso del Módulo
```php
// Instanciar con debug activado
$db = new DatabaseConnectionModule(true);

// Test de conexión
$test_result = $db->test_connection();

// Ejecutar consultas
$result = $db->query('SELECT * FROM wp_posts WHERE post_type = ?', ['post']);
```

---

**✍️ Documento generado**: 11 de junio de 2025
**🔄 Última actualización**: Paso 2 completado
