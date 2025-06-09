/**
 * Script para debuggear el problema del nonce
 * Ejecutar en la consola del navegador en la página de DevTools
 */

console.log('🔍 DEBUGGING NONCE CONFIGURATION');
console.log('========================================');

// 1. Verificar configuración disponible
console.log('1. Configuración devToolsConfig:', window.devToolsConfig);

if (window.devToolsConfig) {
    console.log('  - ajaxUrl:', window.devToolsConfig.ajaxUrl);
    console.log('  - nonce:', window.devToolsConfig.nonce);
    console.log('  - actionPrefix:', window.devToolsConfig.actionPrefix);
    console.log('  - debug:', window.devToolsConfig.debug);
}

// 2. Hacer petición de test simple
async function testNonceValidation() {
    if (!window.devToolsConfig) {
        console.error('❌ devToolsConfig no está disponible');
        return;
    }

    const formData = new FormData();
    formData.append('action', `${window.devToolsConfig.actionPrefix}_dev_tools`);
    formData.append('action_type', 'ping');
    formData.append('nonce', window.devToolsConfig.nonce);

    console.log('2. Datos enviados en la petición AJAX:');
    console.log('  - action:', `${window.devToolsConfig.actionPrefix}_dev_tools`);
    console.log('  - action_type: ping');
    console.log('  - nonce:', window.devToolsConfig.nonce);

    try {
        console.log('3. Enviando petición AJAX...');
        
        const response = await fetch(window.devToolsConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        console.log('4. Respuesta HTTP:', response.status, response.statusText);

        const data = await response.json();
        console.log('5. Datos de respuesta:', data);

        if (data.success) {
            console.log('✅ ÉXITO: El nonce es válido');
            console.log('  - Datos:', data.data);
        } else {
            console.error('❌ ERROR: Fallo en la validación');
            console.error('  - Mensaje:', data.data?.message || 'Sin mensaje');
            
            // Posibles problemas
            console.log('🔧 POSIBLES CAUSAS:');
            console.log('  - Nonce action no coincide entre PHP y JavaScript');
            console.log('  - Configuración ajax.nonce_action incorrecta');
            console.log('  - Slug del plugin no detectado correctamente');
        }

    } catch (error) {
        console.error('❌ ERROR EN LA PETICIÓN:', error);
    }
}

// 3. Verificar valores específicos del sistema
console.log('3. Verificando configuración específica...');

// Ejecutar test
testNonceValidation();
