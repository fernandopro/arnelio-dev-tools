/**
 * Test completo para verificar corrección del error AJAX 400
 * Copia y pega este código en la consola del navegador
 * URL: http://localhost:10030/wp-admin/tools.php?page=tarokina-2025-dev-tools
 */

console.log('🔧 INICIANDO TEST DE CORRECCIÓN AJAX 400...');
console.log('=====================================');

// 1. Verificar configuración disponible
console.log('📋 1. VERIFICANDO CONFIGURACIÓN...');

if (typeof window.tarokina_2025_dev_tools_config === 'undefined') {
    console.error('❌ ERROR: Configuración tarokina_2025_dev_tools_config no encontrada');
    console.log('Variables disponibles:', Object.keys(window).filter(k => k.includes('dev_tools')));
} else {
    const config = window.tarokina_2025_dev_tools_config;
    console.log('✅ Configuración encontrada:', config);
    
    // Verificar campos críticos
    const criticalFields = ['ajaxUrl', 'nonce', 'ajaxAction', 'actionPrefix'];
    let configOK = true;
    
    criticalFields.forEach(field => {
        if (!config[field]) {
            console.error(`❌ ERROR: Campo ${field} faltante en configuración`);
            configOK = false;
        } else {
            console.log(`✅ ${field}: ${config[field]}`);
        }
    });
    
    if (configOK) {
        console.log('✅ Configuración AJAX válida');
    }
}

// 2. Verificar clase DevTools disponible
console.log('\n📋 2. VERIFICANDO CLASE DEVTOOLS...');

if (typeof window.DevTools === 'undefined') {
    console.error('❌ ERROR: Clase DevTools no encontrada');
    console.log('Clases disponibles:', Object.keys(window).filter(k => k.includes('DevTools') || k.includes('dev')));
} else {
    console.log('✅ Clase DevTools encontrada');
    
    if (typeof window.DevTools.prototype.makeAjaxRequest === 'function') {
        console.log('✅ Método makeAjaxRequest disponible');
    } else {
        console.error('❌ ERROR: Método makeAjaxRequest no encontrado');
    }
}

// 3. Función de prueba AJAX con debugging detallado
async function testAjaxFix() {
    console.log('\n🧪 3. EJECUTANDO PRUEBA AJAX...');
    
    try {
        const config = window.tarokina_2025_dev_tools_config;
        
        // Preparar datos de la petición
        const testData = {
            test: true,
            timestamp: Date.now(),
            browser: navigator.userAgent.substring(0, 50)
        };
        
        console.log('📤 Enviando petición con datos:', {
            url: config.ajaxUrl,
            action: config.ajaxAction,
            nonce: config.nonce.substring(0, 10) + '...',
            command: 'ping',
            data: testData
        });
        
        // Crear FormData manualmente para debugging
        const formData = new FormData();
        formData.append('action', config.ajaxAction);
        formData.append('nonce', config.nonce);
        formData.append('command', 'ping');
        formData.append('data', JSON.stringify(testData));
        
        // Log detallado de FormData
        console.log('📋 FormData preparado:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        // Ejecutar petición fetch manual
        console.log('🚀 Ejecutando fetch...');
        
        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        console.log('📨 Respuesta recibida:');
        console.log('  Status:', response.status);
        console.log('  StatusText:', response.statusText);
        console.log('  Headers:', [...response.headers.entries()]);
        
        if (response.status === 400) {
            console.error('❌ ERROR 400 PERSISTE - Bad Request');
            
            // Intentar leer el cuerpo de la respuesta para más detalles
            const errorText = await response.text();
            console.error('Detalles del error:', errorText);
            
            return {
                success: false,
                error: 'Error 400 persiste',
                status: 400,
                details: errorText
            };
        }
        
        if (response.status === 200) {
            const responseData = await response.json();
            console.log('✅ ÉXITO - Respuesta 200:', responseData);
            
            return {
                success: true,
                status: 200,
                data: responseData
            };
        }
        
        // Otros códigos de estado
        const responseText = await response.text();
        console.warn(`⚠️ Respuesta inesperada (${response.status}):`, responseText);
        
        return {
            success: false,
            status: response.status,
            data: responseText
        };
        
    } catch (error) {
        console.error('❌ ERROR EN PETICIÓN AJAX:', error);
        return {
            success: false,
            error: error.message
        };
    }
}

// 4. Función de prueba usando DevTools.makeAjaxRequest
async function testDevToolsMethod() {
    console.log('\n🧪 4. PROBANDO MÉTODO DEVTOOLS...');
    
    if (typeof window.DevTools === 'undefined') {
        console.error('❌ DevTools no disponible');
        return { success: false, error: 'DevTools no disponible' };
    }
    
    try {
        // Crear instancia temporal de DevTools
        const devTools = new window.DevTools();
        
        console.log('📤 Usando DevTools.makeAjaxRequest...');
        
        const result = await devTools.makeAjaxRequest('ping', {
            test: true,
            method: 'DevTools.makeAjaxRequest',
            timestamp: Date.now()
        });
        
        console.log('✅ ÉXITO con DevTools:', result);
        return { success: true, data: result };
        
    } catch (error) {
        console.error('❌ ERROR con DevTools:', error);
        return { success: false, error: error.message };
    }
}

// 5. Función principal que ejecuta todas las pruebas
async function runAjax400Test() {
    console.log('\n🎯 EJECUTANDO BATERÍA COMPLETA DE PRUEBAS...');
    console.log('===========================================');
    
    const results = {
        config: window.tarokina_2025_dev_tools_config ? 'OK' : 'FALLO',
        devtools: typeof window.DevTools !== 'undefined' ? 'OK' : 'FALLO',
        manualAjax: null,
        devtoolsAjax: null
    };
    
    // Prueba 1: AJAX manual
    console.log('\n🧪 PRUEBA 1: AJAX Manual...');
    results.manualAjax = await testAjaxFix();
    
    // Prueba 2: DevTools method
    console.log('\n🧪 PRUEBA 2: Método DevTools...');
    results.devtoolsAjax = await testDevToolsMethod();
    
    // Resumen final
    console.log('\n📊 RESUMEN FINAL:');
    console.log('=================');
    console.log('✅ Configuración:', results.config);
    console.log('✅ DevTools disponible:', results.devtools);
    console.log('✅ AJAX Manual:', results.manualAjax?.success ? 'ÉXITO' : 'FALLO');
    console.log('✅ AJAX DevTools:', results.devtoolsAjax?.success ? 'ÉXITO' : 'FALLO');
    
    if (results.manualAjax?.success && results.devtoolsAjax?.success) {
        console.log('\n🎉 ¡ERROR 400 CORREGIDO EXITOSAMENTE!');
        console.log('El sistema AJAX está funcionando correctamente.');
    } else {
        console.log('\n❌ ERROR 400 AÚN PERSISTE');
        console.log('Detalles de errores:');
        if (!results.manualAjax?.success) {
            console.log('  - AJAX Manual:', results.manualAjax?.error || 'Error desconocido');
        }
        if (!results.devtoolsAjax?.success) {
            console.log('  - AJAX DevTools:', results.devtoolsAjax?.error || 'Error desconocido');
        }
    }
    
    return results;
}

// 6. Verificación de hooks AJAX en WordPress (debugging avanzado)
function checkWordPressAjaxHooks() {
    console.log('\n🔍 6. VERIFICANDO HOOKS WORDPRESS (Si disponible)...');
    
    // Esta información solo estará disponible si hay debugging del lado del servidor
    console.log('Para verificar hooks del servidor, revisa:');
    console.log('1. Network tab en DevTools');
    console.log('2. Logs PHP en:', '/Users/fernandovazquezperez/Local Sites/tarokina-2025/logs/php/error.log');
    console.log('3. WordPress admin-ajax.php registrations');
    
    // Verificar configuración local
    const config = window.tarokina_2025_dev_tools_config;
    if (config) {
        console.log('Hook esperado en servidor:', 'wp_ajax_' + config.ajaxAction);
        console.log('Acción AJAX esperada:', config.ajaxAction);
    }
}

// Auto-ejecutar si estamos en la página correcta
if (window.location.href.includes('tarokina-2025-dev-tools')) {
    console.log('🚀 Auto-ejecutando test en página de Dev Tools...');
    setTimeout(() => {
        runAjax400Test().then(results => {
            console.log('\n🏁 Test completado automáticamente');
            
            // Verificación adicional
            checkWordPressAjaxHooks();
        });
    }, 1000);
} else {
    console.log('⚠️ Para ejecutar manualmente, usa: runAjax400Test()');
    console.log('📍 Asegúrate de estar en: /wp-admin/tools.php?page=tarokina-2025-dev-tools');
}

// Exportar función para uso manual
window.runAjax400Test = runAjax400Test;
window.testAjaxFix = testAjaxFix;
window.testDevToolsMethod = testDevToolsMethod;
window.checkWordPressAjaxHooks = checkWordPressAjaxHooks;

console.log('\n✅ Script de test cargado. Funciones disponibles:');
console.log('  - runAjax400Test() - Ejecutar todas las pruebas');
console.log('  - testAjaxFix() - Prueba AJAX manual');
console.log('  - testDevToolsMethod() - Prueba método DevTools');
console.log('  - checkWordPressAjaxHooks() - Verificar hooks WP');
