// Script de prueba para verificar el estado del sistema Dev-Tools Arquitectura 3.0
// Ejecutar en la consola del navegador en la página de dev-tools

console.log('🔧 Dev-Tools Arquitectura 3.0 - Test de Sistema');
console.log('=' .repeat(50));

// 1. Verificar configuración JavaScript
if (typeof devToolsConfig !== 'undefined') {
    console.log('✅ Config JavaScript disponible:', devToolsConfig);
} else {
    console.log('❌ Config JavaScript no disponible');
}

// 2. Verificar Bootstrap está cargado
if (typeof bootstrap !== 'undefined') {
    console.log('✅ Bootstrap 5 cargado');
} else {
    console.log('❌ Bootstrap 5 no disponible');
}

// 3. Verificar elementos del dashboard
const dashboardPanel = document.getElementById('dev-tools-panel');
if (dashboardPanel) {
    console.log('✅ Panel principal encontrado');
} else {
    console.log('❌ Panel principal no encontrado');
}

// 4. Verificar si se muestra el módulo Dashboard
const dashboardContent = document.querySelector('.dev-tools-container');
if (dashboardContent) {
    console.log('✅ Contenedor del dashboard encontrado');
    
    // Buscar indicadores de éxito o error
    const warningAlert = dashboardContent.querySelector('.alert-warning');
    const successContent = dashboardContent.querySelector('.card');
    
    if (warningAlert && warningAlert.textContent.includes('No encontrado')) {
        console.log('❌ Dashboard Module: No encontrado (sistema legacy)');
    } else if (successContent) {
        console.log('✅ Dashboard Module: Funcionando correctamente');
    } else {
        console.log('⚠️ Estado del dashboard incierto');
    }
} else {
    console.log('❌ Contenedor del dashboard no encontrado');
}

// 5. Test de AJAX si está disponible
if (typeof devToolsConfig !== 'undefined' && devToolsConfig.ajaxUrl) {
    console.log('🧪 Iniciando test de AJAX...');
    
    const formData = new FormData();
    formData.append('action', devToolsConfig.actionPrefix + '_dev_tools');
    formData.append('action_type', 'get_dashboard_data');
    formData.append('nonce', devToolsConfig.nonce);
    
    fetch(devToolsConfig.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ AJAX Test exitoso:', data);
        if (data.success) {
            console.log('✅ Sistema AJAX operativo');
        } else {
            console.log('⚠️ AJAX responde pero con errores:', data.data);
        }
    })
    .catch(error => {
        console.log('❌ Error en test AJAX:', error);
    });
} else {
    console.log('⚠️ No se puede realizar test AJAX (configuración no disponible)');
}

console.log('=' .repeat(50));
console.log('Test completado. Revisa los resultados arriba.');
