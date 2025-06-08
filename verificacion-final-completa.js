// Verificación Final Completa - Test de Botones Dev-Tools
// ========================================================

console.log('%c🎯 VERIFICACIÓN FINAL COMPLETA', 'background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px; font-size: 16px; font-weight: bold; border-radius: 6px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);');

// 1. Verificar que DevToolsTestRunner esté cargado
if (typeof DevToolsTestRunner === 'undefined') {
    console.error('%c❌ DevToolsTestRunner no está definido', 'background: #dc2626; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
} else {
    console.log('%c✅ DevToolsTestRunner está disponible', 'background: #059669; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
}

// 2. Verificar configuración
if (typeof tkn_dev_tools_config === 'undefined') {
    console.error('%c❌ tkn_dev_tools_config no está definido', 'background: #dc2626; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
} else {
    console.log('%c✅ Configuración dev-tools disponible', 'background: #059669; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
    console.log('   • ajaxUrl:', '%c' + tkn_dev_tools_config.ajaxUrl, 'color: #60a5fa; font-weight: bold;');
    console.log('   • nonce:', '%c' + tkn_dev_tools_config.nonce, 'color: #a78bfa; font-weight: bold;');
}

// 3. Contar botones disponibles
const buttons = document.querySelectorAll('button[data-action]');
console.log('%c✅ Botones encontrados:', 'background: #1f2937; color: #34d399; padding: 6px; border-radius: 4px; font-weight: bold;', buttons.length);

buttons.forEach((button, index) => {
    const action = button.dataset.action;
    const disabled = button.disabled;
    const text = button.textContent.trim();
    
    const statusColor = disabled ? '#f87171' : '#34d399';
    const statusText = disabled ? 'DESHABILITADO' : 'HABILITADO';
    
    console.log(`   %c[${index}]%c ${action}%c - ${statusText}%c - Text: "${text}"`, 
        'color: #fbbf24; font-weight: bold;',
        'color: #60a5fa; font-weight: bold;',
        `color: ${statusColor}; font-weight: bold;`,
        'color: #d1d5db;'
    );
});

// 4. Función de test automático
function testButtonFunctionality() {
    console.log('\n%c🧪 INICIANDO TEST AUTOMÁTICO DE BOTONES', 'background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 10px; font-weight: bold; border-radius: 6px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);');
    
    const testButton = document.querySelector('button[data-action="run_single_test"]');
    
    if (!testButton) {
        console.error('%c❌ No se encontró botón de test individual', 'background: #dc2626; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
        return;
    }
    
    console.log('%c✅ Botón de test encontrado:', 'color: #34d399; font-weight: bold;', '%c' + testButton.dataset.testFile, 'color: #60a5fa; font-weight: bold;');
    console.log('   • Estado inicial disabled:', '%c' + testButton.disabled, testButton.disabled ? 'color: #f87171; font-weight: bold;' : 'color: #34d399; font-weight: bold;');
    console.log('   • Contenido inicial:', '%c' + testButton.innerHTML, 'color: #d1d5db;');
    
    // Simular click
    console.log('\n%c👆 SIMULANDO CLICK EN BOTÓN...', 'background: #f59e0b; color: white; padding: 8px; border-radius: 4px; font-weight: bold;');
    
    testButton.click();
    
    // Verificar estado durante ejecución (después de 100ms)
    setTimeout(() => {
        console.log('\n%c⏳ ESTADO DURANTE EJECUCIÓN (100ms después):', 'background: #7c3aed; color: white; padding: 8px; border-radius: 4px; font-weight: bold;');
        console.log('   • Disabled:', '%c' + testButton.disabled, testButton.disabled ? 'color: #f87171; font-weight: bold;' : 'color: #34d399; font-weight: bold;');
        console.log('   • Contenido:', '%c' + testButton.innerHTML, 'color: #d1d5db;');
        console.log('   • Classes:', '%c' + testButton.className, 'color: #a78bfa;');
        
        // Verificar estado final (después de 5 segundos)
        setTimeout(() => {
            console.log('\n%c🏁 ESTADO FINAL (5s después):', 'background: linear-gradient(135deg, #059669, #047857); color: white; padding: 8px; border-radius: 4px; font-weight: bold;');
            console.log('   • Disabled:', '%c' + testButton.disabled, testButton.disabled ? 'color: #f87171; font-weight: bold;' : 'color: #34d399; font-weight: bold;');
            console.log('   • Contenido:', '%c' + testButton.innerHTML, 'color: #d1d5db;');
            console.log('   • Classes:', '%c' + testButton.className, 'color: #a78bfa;');
            
            if (testButton.disabled) {
                console.error('%c❌ PROBLEMA: El botón sigue deshabilitado después de 5 segundos', 'background: #dc2626; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
                console.log('%c💡 Intentando habilitar manualmente...', 'color: #fbbf24; font-weight: bold;');
                
                // Habilitar manualmente para verificar que es posible
                testButton.disabled = false;
                testButton.classList.remove('btn-secondary');
                testButton.classList.add('btn-success');
                
                if (testButton.dataset.originalContent) {
                    testButton.innerHTML = testButton.dataset.originalContent;
                }
                
                console.log('%c✅ Botón habilitado manualmente', 'background: #059669; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
            } else {
                console.log('%c🎉 ¡ÉXITO! El botón se habilitó correctamente automáticamente', 'background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);');
            }
        }, 5000);
        
    }, 100);
}

// 5. Botón para ejecutar test automático
console.log('\n%c🎮 Para probar la funcionalidad, ejecuta: testButtonFunctionality()', 'background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 10px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);');

// Hacer disponible globalmente
window.testButtonFunctionality = testButtonFunctionality;

console.log('\n' + '%c' + '='.repeat(80), 'color: #6b7280;');
console.log('%c✅ VERIFICACIÓN FINAL LISTA', 'background: linear-gradient(135deg, #059669, #047857); color: white; padding: 8px; border-radius: 4px; font-weight: bold;');
console.log('%c📋 Comandos disponibles:', 'color: #fbbf24; font-weight: bold; font-size: 14px;');
console.log('   • %ctestButtonFunctionality()%c - Test automático completo', 'color: #60a5fa; font-weight: bold; background: #1f2937; padding: 2px 4px; border-radius: 3px;', 'color: #d1d5db;');
console.log('   • %cDevToolsTestRunner%c - Clase principal', 'color: #a78bfa; font-weight: bold; background: #1f2937; padding: 2px 4px; border-radius: 3px;', 'color: #d1d5db;');
console.log('   • %ctkn_dev_tools_config%c - Configuración', 'color: #f87171; font-weight: bold; background: #1f2937; padding: 2px 4px; border-radius: 3px;', 'color: #d1d5db;');
