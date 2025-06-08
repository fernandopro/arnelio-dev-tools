// DIAGNÓSTICO CRÍTICO - Console.log No Aparece
// ==============================================

console.log('%c🚨 DIAGNÓSTICO CRÍTICO - CONSOLE.LOG NO APARECE', 'background: #dc2626; color: white; padding: 12px; font-size: 16px; font-weight: bold;');

// 1. Verificar que console.log funciona básicamente
console.log('%c✅ TEST BÁSICO: Console.log funciona', 'color: #059669; font-weight: bold;');
console.error('%c✅ TEST BÁSICO: Console.error funciona', 'color: #dc2626; font-weight: bold;');
console.warn('%c✅ TEST BÁSICO: Console.warn funciona', 'color: #d97706; font-weight: bold;');

// 2. Verificar DevToolsTestRunner
if (typeof DevToolsTestRunner !== 'undefined') {
    console.log('%c🔍 DevToolsTestRunner encontrado', 'color: #059669; font-weight: bold;');
    
    // Verificar si hay instancias
    const buttons = document.querySelectorAll('button[data-action="run_single_test"]');
    console.log(`🔢 Botones run_single_test encontrados: ${buttons.length}`);
    
    if (buttons.length > 0) {
        const testButton = buttons[0];
        console.log('🎯 Botón de test seleccionado:', testButton.dataset.testFile);
        
        // INTERCEPTAR EL MÉTODO handleSingleTest
        const originalHandleSingleTest = DevToolsTestRunner.prototype.handleSingleTest;
        
        DevToolsTestRunner.prototype.handleSingleTest = function(button) {
            console.log('%c🚀 INTERCEPTED: handleSingleTest llamado', 'background: #f59e0b; color: white; padding: 8px; font-weight: bold;');
            console.log('   • Button:', button);
            console.log('   • Test file:', button.dataset.testFile);
            
            // Llamar al método original
            return originalHandleSingleTest.call(this, button);
        };
        
        // INTERCEPTAR EL MÉTODO executeTest
        const originalExecuteTest = DevToolsTestRunner.prototype.executeTest;
        
        DevToolsTestRunner.prototype.executeTest = function(action, data) {
            console.log('%c📡 INTERCEPTED: executeTest llamado', 'background: #8b5cf6; color: white; padding: 8px; font-weight: bold;');
            console.log('   • Action:', action);
            console.log('   • Data:', data);
            
            // Llamar al método original
            const result = originalExecuteTest.call(this, action, data);
            
            // Interceptar la promesa
            if (result && typeof result.then === 'function') {
                return result.then(response => {
                    console.log('%c📥 INTERCEPTED: executeTest response', 'background: #059669; color: white; padding: 8px; font-weight: bold;');
                    console.log('   • Response:', response);
                    return response;
                });
            }
            
            return result;
        };
        
        // INTERCEPTAR EL MÉTODO processTestResult
        const originalProcessTestResult = DevToolsTestRunner.prototype.processTestResult;
        
        DevToolsTestRunner.prototype.processTestResult = function(result, testName) {
            console.log('%c🎨 INTERCEPTED: processTestResult llamado', 'background: #ec4899; color: white; padding: 8px; font-weight: bold;');
            console.log('   • Result:', result);
            console.log('   • Test name:', testName);
            console.log('   • Result.output existe:', !!result.output);
            console.log('   • Result.output length:', result.output ? result.output.length : 0);
            
            // Llamar al método original
            return originalProcessTestResult.call(this, result, testName);
        };
        
        console.log('%c✅ INTERCEPTORES INSTALADOS', 'color: #059669; font-weight: bold;');
        console.log('📋 Ahora haz click en un botón "Ejecutar Test" y verás todos los pasos interceptados');
        
        // Función para test manual inmediato
        window.testManualInmediato = function() {
            console.log('%c🎮 TEST MANUAL INMEDIATO', 'background: #3b82f6; color: white; padding: 8px; font-weight: bold;');
            
            // Simular click directo
            console.log('👆 Haciendo click en el primer botón...');
            testButton.click();
        };
        
        console.log('%c🎮 Comando disponible: testManualInmediato()', 'background: #6366f1; color: white; padding: 8px;');
        
    } else {
        console.error('❌ No se encontraron botones run_single_test');
    }
} else {
    console.error('❌ DevToolsTestRunner no está definido');
}

// 3. Verificar configuración AJAX
if (typeof tkn_dev_tools_config !== 'undefined') {
    console.log('%c🔧 Configuración AJAX:', 'color: #7c3aed; font-weight: bold;');
    console.log('   • ajaxUrl:', tkn_dev_tools_config.ajaxUrl);
    console.log('   • nonce:', tkn_dev_tools_config.nonce);
} else {
    console.error('❌ tkn_dev_tools_config no está definido');
}

console.log('\n' + '='.repeat(80));
console.log('%c🎯 DIAGNÓSTICO INSTALADO', 'color: #059669; font-weight: bold;');
console.log('📋 Pasos siguientes:');
console.log('   1. Haz click en un botón "Ejecutar Test"');
console.log('   2. O ejecuta: testManualInmediato()');
console.log('   3. Observa qué interceptores se activan');
