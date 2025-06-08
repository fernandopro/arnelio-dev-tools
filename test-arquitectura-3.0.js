/**
 * Script de test para navegador - Arquitectura 3.0
 * Verificar funcionamiento del sistema dev-tools modular
 * 
 * INSTRUCCIONES:
 * 1. Ir a /wp-admin/tools.php?page=tarokina-2025-dev-tools
 * 2. Abrir consola del navegador (F12)
 * 3. Copiar y pegar este código completo
 * 4. Presionar Enter y observar resultados
 */

console.log('=== DEV-TOOLS ARQUITECTURA 3.0 - TEST COMPLETO ===');

// Test 1: Verificar configuración
console.group('🔧 Test 1: Configuración del Sistema');
if (typeof devToolsConfig !== 'undefined') {
    console.log('✅ devToolsConfig disponible:', devToolsConfig);
    
    const requiredFields = ['ajaxUrl', 'nonce', 'actionPrefix'];
    const missingFields = requiredFields.filter(field => !devToolsConfig[field]);
    
    if (missingFields.length === 0) {
        console.log('✅ Todos los campos requeridos están presentes');
    } else {
        console.warn('⚠️ Campos faltantes:', missingFields);
    }
} else {
    console.error('❌ devToolsConfig no está disponible');
}
console.groupEnd();

// Test 2: Verificar clases JavaScript
console.group('🔄 Test 2: Clases JavaScript');
const jsClasses = ['DevToolsDashboard', 'DevToolsClientLogger'];
jsClasses.forEach(className => {
    if (typeof window[className] !== 'undefined') {
        console.log(`✅ ${className} disponible`);
    } else {
        console.error(`❌ ${className} no disponible`);
    }
});
console.groupEnd();

// Test 3: Verificar elementos DOM
console.group('🎨 Test 3: Elementos DOM');
const requiredElements = [
    '#alert-container',
    '#btn-test-system',
    '#btn-clear-cache',
    '#btn-refresh-data',
    '#modules-status'
];

requiredElements.forEach(selector => {
    const element = document.querySelector(selector);
    if (element) {
        console.log(`✅ Elemento encontrado: ${selector}`);
    } else {
        console.warn(`⚠️ Elemento no encontrado: ${selector}`);
    }
});
console.groupEnd();

// Test 4: Test AJAX básico
console.group('🌐 Test 4: Conectividad AJAX');
async function testAjax() {
    if (typeof devToolsConfig === 'undefined') {
        console.error('❌ No se puede hacer test AJAX: configuración no disponible');
        return;
    }
    
    try {
        console.log('📡 Enviando ping al servidor...');
        
        const formData = new FormData();
        formData.append('action', `${devToolsConfig.actionPrefix}_dev_tools`);
        formData.append('action_type', 'ping');
        formData.append('nonce', devToolsConfig.nonce);
        
        const response = await fetch(devToolsConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Ping exitoso:', data.data);
            
            // Test adicional: system info
            console.log('📊 Obteniendo información del sistema...');
            
            const sysFormData = new FormData();
            sysFormData.append('action', `${devToolsConfig.actionPrefix}_dev_tools`);
            sysFormData.append('action_type', 'get_system_info');
            sysFormData.append('nonce', devToolsConfig.nonce);
            
            const sysResponse = await fetch(devToolsConfig.ajaxUrl, {
                method: 'POST',
                body: sysFormData
            });
            
            const sysData = await sysResponse.json();
            
            if (sysData.success) {
                console.log('✅ Información del sistema obtenida:', sysData.data);
            } else {
                console.warn('⚠️ Error al obtener info del sistema:', sysData.data);
            }
            
        } else {
            console.error('❌ Ping falló:', data.data);
        }
        
    } catch (error) {
        console.error('❌ Error en test AJAX:', error);
    }
}

testAjax();
console.groupEnd();

// Test 5: Test de Dashboard
console.group('📊 Test 5: Dashboard');
if (typeof DevToolsDashboard !== 'undefined' && typeof devToolsConfig !== 'undefined') {
    try {
        console.log('🚀 Inicializando Dashboard...');
        
        const dashboard = new DevToolsDashboard();
        
        // Test de inicialización
        dashboard.init();
        
        console.log('✅ Dashboard inicializado exitosamente');
        
        // Test de logger
        if (dashboard.logger) {
            dashboard.logger.logExternal('Test message from dashboard', 'info');
            console.log('✅ Logger funcionando');
        }
        
        // Guardar referencia global para debugging
        window.testDashboard = dashboard;
        console.log('💡 Dashboard guardado en window.testDashboard para debugging');
        
    } catch (error) {
        console.error('❌ Error al inicializar Dashboard:', error);
    }
} else {
    console.warn('⚠️ No se puede probar Dashboard: dependencias no disponibles');
}
console.groupEnd();

// Test 6: Test de conectividad completa
console.group('🔗 Test 6: Conectividad Completa');
async function testFullConnectivity() {
    if (typeof devToolsConfig === 'undefined') {
        console.error('❌ Configuración no disponible');
        return;
    }
    
    try {
        console.log('🧪 Ejecutando test de conectividad...');
        
        const formData = new FormData();
        formData.append('action', `${devToolsConfig.actionPrefix}_dev_tools`);
        formData.append('action_type', 'test_connection');
        formData.append('nonce', devToolsConfig.nonce);
        
        const response = await fetch(devToolsConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Test de conectividad exitoso:', data.data);
            
            const results = data.data;
            Object.entries(results).forEach(([test, result]) => {
                const status = result ? '✅' : '❌';
                console.log(`  ${status} ${test}: ${result}`);
            });
            
        } else {
            console.error('❌ Test de conectividad falló:', data.data);
        }
        
    } catch (error) {
        console.error('❌ Error en test de conectividad:', error);
    }
}

testFullConnectivity();
console.groupEnd();

// Resumen final
setTimeout(() => {
    console.log('\n=== RESUMEN DEL TEST ===');
    console.log('🔧 Configuración: ' + (typeof devToolsConfig !== 'undefined' ? '✅' : '❌'));
    console.log('🔄 Clases JS: ' + (typeof DevToolsDashboard !== 'undefined' ? '✅' : '❌'));
    console.log('🎨 DOM Elements: ' + (document.querySelector('#alert-container') ? '✅' : '❌'));
    console.log('📊 Dashboard: ' + (typeof window.testDashboard !== 'undefined' ? '✅' : '❌'));
    console.log('\n💡 Si todos los tests están ✅, la Arquitectura 3.0 está funcionando correctamente!');
    console.log('📝 Para más tests, usar: window.testDashboard (si está disponible)');
    console.log('🌐 URL actual:', window.location.href);
}, 3000);

// Función de ayuda para usuarios
window.devToolsHelp = function() {
    console.log(`
🚀 DEV-TOOLS ARQUITECTURA 3.0 - AYUDA

Funciones disponibles:
- devToolsHelp() : Mostrar esta ayuda
- testDashboard   : Instancia del dashboard (si está inicializado)

Configuración actual:
- AJAX URL: ${devToolsConfig?.ajaxUrl || 'No disponible'}
- Action Prefix: ${devToolsConfig?.actionPrefix || 'No disponible'}
- Debug Mode: ${devToolsConfig?.debug || false}

Ejemplos de uso:
1. testDashboard.refreshData()     - Refrescar datos
2. testDashboard.runSystemTest()   - Ejecutar test del sistema
3. testDashboard.clearCache()      - Limpiar cache

Para más información, consultar la documentación del módulo.
    `);
};

console.log('\n💡 Tip: Usar devToolsHelp() para ver funciones disponibles');
