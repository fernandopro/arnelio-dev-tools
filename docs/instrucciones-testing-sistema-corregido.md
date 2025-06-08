# 🧪 Instrucciones de Testing - Sistema Refactorizado

## ✅ Pasos para Probar el Sistema Corregido

### 1. Abrir la Página de Dev-Tools
- Navegar a: `http://localhost:10019/wp-admin/tools.php?page=tarokina-dev-tools`
- Ir a la pestaña **"Tests"**

### 2. Abrir Consola del Navegador
- Presionar `F12` (o `Cmd+Option+I` en Mac)
- Ir a la pestaña **Console**

### 3. Verificar Configuración AJAX
Ejecutar este código en la consola para verificar que todo esté configurado:

```javascript
// Copiar y pegar este código en la consola del navegador
console.log('🔍 Verificación de Configuración AJAX');
console.log('=====================================');

if (typeof tkn_dev_tools_config !== 'undefined') {
    console.log('✅ tkn_dev_tools_config está disponible');
    console.log('📊 Configuración:', tkn_dev_tools_config);
    
    if (tkn_dev_tools_config.ajax_url) {
        console.log('✅ ajax_url está configurado:', tkn_dev_tools_config.ajax_url);
    } else {
        console.log('❌ ajax_url NO está configurado');
    }
} else {
    console.log('❌ tkn_dev_tools_config NO está disponible');
}

if (typeof window.devToolsTestRunner !== 'undefined') {
    console.log('✅ DevToolsTestRunner está disponible');
    const ajaxUrl = window.devToolsTestRunner.getAjaxUrl();
    console.log('✅ getAjaxUrl() funciona:', ajaxUrl);
} else {
    console.log('❌ DevToolsTestRunner NO está disponible');
}
```

### 4. Probar Funcionalidad de Botones

#### A) Test Individual
1. Hacer clic en cualquier botón **"Ejecutar"** de un test individual
2. **Verificar en la consola:**
   - Debe aparecer el header del test con estilos de color
   - Debe mostrar el progreso de ejecución
   - Debe mostrar el resultado del test
   - **NO debe aparecer ningún error 400 Bad Request**

#### B) Todos los Tests
1. Hacer clic en el botón **"Ejecutar Todos los Tests"**
2. **Verificar en la consola:**
   - Debe aparecer el header de ejecución masiva
   - Debe mostrar el progreso
   - Debe mostrar los resultados
   - **NO debe aparecer ningún error 400 Bad Request**

### 5. Resultados Esperados

#### ✅ **ÉXITO** - Deberías ver:
```
🧪 DevTools Test Runner v2.0 - Inicializado
✅ Event listeners configurados
🚀 DevToolsTestRunner inicializado globalmente
🧪 Ejecutando Test Individual: [nombre_del_test]
✅ Test ejecutado exitosamente
📝 Output del Test:
[resultados del test]
```

#### ❌ **FALLO** - Si ves esto, reportar:
```
❌ Error ejecutando test: HTTP 400: Bad Request
❌ tkn_dev_tools_config NO está disponible
❌ DevToolsTestRunner NO está disponible
```

### 6. Archivo de Verificación
También puedes ejecutar el script de verificación:
```bash
# En la consola del navegador, cargar este script
fetch('/wp-content/plugins/tarokina-2025/dev-tools/test-ajax-config.js')
  .then(response => response.text())
  .then(script => eval(script));
```

## 🔧 Cambios Realizados

### JavaScript Corregido:
- ✅ Uso de `tkn_dev_tools_config.ajax_url` en lugar de `ajaxurl` global
- ✅ Método `getAjaxUrl()` con fallbacks múltiples
- ✅ Inicialización automática de `DevToolsTestRunner`
- ✅ Sistema de logging con estilos de consola
- ✅ Event delegation robusto
- ✅ Control de estado de ejecución

### Archivos Modificados:
- `src/js/dev-tools-tests.js` - Código fuente corregido
- `dist/js/dev-tools-tests.min.js` - Compilado con webpack

### Sistema de Fallbacks:
1. **Primero**: `tkn_dev_tools_config.ajax_url` (configuración localizada)
2. **Segundo**: `ajaxurl` global (fallback estándar)
3. **Tercero**: `window.location.origin + '/wp-admin/admin-ajax.php'` (construcción manual)

## 📋 Estado del Sistema

- ✅ **Compilación**: Exitosa (70.8 KiB)
- ✅ **AJAX URL**: Corregido y configurado
- ✅ **Event Listeners**: Implementados
- ✅ **Manejo de Errores**: Mejorado
- ✅ **Logging**: Con estilos de consola
- ✅ **Documentación**: Completa

**Estado**: 🟢 **LISTO PARA PRUEBAS**
