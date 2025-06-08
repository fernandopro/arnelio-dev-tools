/**
 * Test de Debug AJAX - Ejecutar en la consola del navegador
 * 
 * Instrucciones:
 * 1. Abrir dev-tools en el navegador
 * 2. Abrir la consola de desarrollador (F12)
 * 3. Copiar y pegar este código para ejecutarlo
 */

// Función para probar AJAX step by step
async function testAjaxDebug() {
    console.log('🚀 Iniciando test de debug AJAX...');
    
    // 1. Verificar que DevToolsController está disponible
    if (typeof window.DevToolsController === 'undefined') {
        console.error('❌ DevToolsController no está disponible');
        return;
    }
    
    console.log('✅ DevToolsController disponible');
    
    // 2. Obtener información de debug
    try {
        const debugInfo = await window.DevToolsController.getAjaxDebugInfo();
        console.log('📊 Debug Info obtenida:', debugInfo);
    } catch (error) {
        console.error('❌ Error obteniendo debug info:', error);
    }
    
    // 3. Probar ping manual
    console.log('🔍 Probando ping manual...');
    try {
        const controller = window.DevToolsController;
        const pingAction = controller.getAjaxAction('ping');
        
        console.log('Ping action generada:', pingAction);
        console.log('URL AJAX:', controller.config.ajaxUrl);
        console.log('Nonce:', controller.config.nonce);
        
        const response = await fetch(controller.config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: pingAction,
                nonce: controller.config.nonce || ''
            })
        });
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (response.ok) {
            const data = await response.json();
            console.log('✅ Ping exitoso:', data);
        } else {
            const errorText = await response.text();
            console.error('❌ Ping falló:', errorText.substring(0, 500));
        }
        
    } catch (error) {
        console.error('❌ Error en ping manual:', error);
    }
    
    // 4. Probar usando makeAjaxRequest
    console.log('🔍 Probando usando makeAjaxRequest...');
    try {
        const result = await window.DevToolsController.makeAjaxRequest(
            window.DevToolsController.getAjaxAction('ping')
        );
        console.log('✅ makeAjaxRequest exitoso:', result);
    } catch (error) {
        console.error('❌ makeAjaxRequest falló:', error);
    }
}

// 5. Función para verificar configuración
function verifyConfig() {
    console.group('🔧 Verificación de Configuración');
    
    if (typeof window.DevToolsController !== 'undefined') {
        const config = window.DevToolsController.config;
        console.log('Config disponible:', !!config);
        console.log('AJAX URL:', config?.ajaxUrl);
        console.log('Nonce:', config?.nonce);
        console.log('Debug Mode:', config?.debugMode);
        console.log('Verbose Mode:', config?.verboseMode);
        
        // Verificar config global
        if (typeof tkn_dev_tools_config !== 'undefined') {
            console.log('Config global tkn_dev_tools_config:', tkn_dev_tools_config);
        } else {
            console.warn('Config global tkn_dev_tools_config NO disponible');
        }
    } else {
        console.error('DevToolsController NO disponible');
    }
    
    console.groupEnd();
}

// Auto-ejecutar cuando se carga
console.log('📝 Test de debug AJAX cargado. Ejecuta:');
console.log('  verifyConfig() - para verificar configuración');
console.log('  testAjaxDebug() - para probar AJAX paso a paso');

// Ejecutar verificación automáticamente
verifyConfig();
