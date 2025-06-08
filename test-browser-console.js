// Test de funcionalidad AJAX debug después del arreglo
// Ejecutar en la consola del navegador en la página de dev-tools

console.log('🔧 TEST DE FUNCIONALIDAD DEV-TOOLS POST-ARREGLO');
console.log('================================================');

// 1. Verificar que la configuración JavaScript está disponible
console.log('1. Verificando configuración JavaScript...');
if (typeof window.tkn_dev_tools_config !== 'undefined') {
    console.log('✅ Configuración encontrada:', window.tkn_dev_tools_config);
} else {
    console.log('❌ Configuración JavaScript no encontrada');
}

// 2. Test de conectividad AJAX básica
console.log('2. Probando conectividad AJAX...');
if (typeof window.tkn_dev_tools_config !== 'undefined' && window.tkn_dev_tools_config.ajaxUrl) {
    
    const testAjax = () => {
        const formData = new FormData();
        formData.append('action', 'dev_tools_debug');
        formData.append('nonce', window.tkn_dev_tools_config.nonce || '');
        
        fetch(window.tkn_dev_tools_config.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Test AJAX exitoso:', data);
            if (data.success) {
                console.log('📊 Info del sistema:', data.data);
            }
        })
        .catch(error => {
            console.log('⚠️ Test AJAX falló (esperado si no hay handler):', error);
        });
    };
    
    testAjax();
} else {
    console.log('❌ No se puede probar AJAX - configuración faltante');
}

// 3. Verificar que los assets compilados se cargaron
console.log('3. Verificando assets compilados...');
const scripts = Array.from(document.scripts).filter(s => s.src.includes('dev-tools'));
console.log('📦 Scripts dev-tools cargados:', scripts.map(s => s.src));

const stylesheets = Array.from(document.styleSheets).filter(s => 
    s.href && s.href.includes('dev-tools')
);
console.log('🎨 Stylesheets dev-tools cargados:', stylesheets.map(s => s.href));

// 4. Test de funcionalidad Bootstrap
console.log('4. Verificando Bootstrap...');
if (typeof bootstrap !== 'undefined') {
    console.log('✅ Bootstrap disponible');
} else if (typeof window.bootstrap !== 'undefined') {
    console.log('✅ Bootstrap disponible en window');
} else {
    console.log('⚠️ Bootstrap no detectado');
}

console.log('================================================');
console.log('✅ TEST COMPLETADO - Revisar resultados arriba');
