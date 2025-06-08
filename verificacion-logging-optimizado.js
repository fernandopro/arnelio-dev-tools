/**
 * Verificación del Sistema de Logging Optimizado
 * 
 * Este script verifica que el nuevo sistema de logging inteligente
 * esté funcionando correctamente y reduzca la verbosidad durante
 * la inicialización.
 * 
 * @version 1.0.0
 * @author Tarokina Dev Tools
 */

console.log('%c🔧 VERIFICACIÓN DE LOGGING OPTIMIZADO', 
    'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);'
);

const styles = {
    success: 'background: linear-gradient(135deg, #059669, #047857); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
    error: 'background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
    warning: 'background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
    info: 'background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
    code: 'background: #1f2937; color: #60a5fa; padding: 2px 6px; border-radius: 3px; font-family: "SF Mono", Monaco, Consolas, monospace;',
    highlight: 'background: #fbbf24; color: #1f2937; padding: 4px 8px; border-radius: 3px; font-weight: bold;'
};

// 1. Verificar que DevToolsController esté disponible
console.log('\n%c1️⃣ VERIFICACIÓN DE DISPONIBILIDAD:', styles.info);
if (typeof window.DevToolsController !== 'undefined') {
    console.log('%c✅ DevToolsController está disponible', styles.success);
    
    // Verificar propiedades del sistema de logging optimizado
    const controller = window.DevToolsController;
    
    if (typeof controller.shouldShowInternalOutput === 'function') {
        console.log('%c✅ Método shouldShowInternalOutput implementado', styles.success);
    } else {
        console.log('%c❌ Método shouldShowInternalOutput NO encontrado', styles.error);
    }
    
    if (typeof controller.isTestExecution !== 'undefined') {
        console.log('%c✅ Propiedad isTestExecution detectada:', styles.success, controller.isTestExecution);
    } else {
        console.log('%c❌ Propiedad isTestExecution NO encontrada', styles.error);
    }
    
} else {
    console.log('%c❌ DevToolsController NO está disponible', styles.error);
}

// 2. Verificar configuración de modos
console.log('\n%c2️⃣ VERIFICACIÓN DE MODOS DE LOGGING:', styles.info);
if (window.DevToolsController) {
    const controller = window.DevToolsController;
    
    console.log('%c📊 Estado actual de modos:', styles.code);
    console.log('   • verboseMode:', controller.verboseMode);
    console.log('   • debugMode:', controller.debugMode);
    console.log('   • isTestExecution:', controller.isTestExecution);
    
    // Test de niveles de logging
    console.log('\n%c3️⃣ TEST DE NIVELES DE LOGGING:', styles.info);
    
    // Simular diferentes contextos
    const testCases = [
        { level: 'critical', expected: true, description: 'Critical - Siempre mostrar' },
        { level: 'normal', expected: controller.isTestExecution || (controller.verboseMode && controller.debugMode), description: 'Normal - Solo durante tests o debug completo' },
        { level: 'minimal', expected: controller.verboseMode && controller.debugMode && controller.isTestExecution, description: 'Minimal - Solo con verbose + debug + test' }
    ];
    
    testCases.forEach(testCase => {
        const result = controller.shouldShowInternalOutput(testCase.level);
        const status = result === testCase.expected ? '✅' : '❌';
        const style = result === testCase.expected ? styles.success : styles.error;
        
        console.log(`%c${status} ${testCase.level.toUpperCase()}`, style, 
                   `- ${testCase.description} (esperado: ${testCase.expected}, obtenido: ${result})`);
    });
}

// 4. Test de reducción de verbosidad
console.log('\n%c4️⃣ TEST DE REDUCCIÓN DE VERBOSIDAD:', styles.info);

// Contar mensajes en consola antes de recargar
let messageCount = 0;
const originalLog = console.log;
const capturedMessages = [];

console.log = function(...args) {
    messageCount++;
    const message = args.join(' ');
    
    // Detectar mensajes de inicialización que deberían estar ocultos
    const initializationMessages = [
        'Configuración cargada',
        'Event listeners configurados',
        'Módulos inicializados',
        'DevToolsController inicializado'
    ];
    
    const isInitMessage = initializationMessages.some(pattern => 
        message.includes(pattern) && !message.includes('TEST') && !message.includes('🔧 VERIFICACIÓN')
    );
    
    if (isInitMessage) {
        capturedMessages.push({
            message: message,
            args: args,
            shouldBeHidden: !window.DevToolsController?.isTestExecution
        });
    }
    
    originalLog.apply(console, args);
};

// Simular recarga del controlador si es posible
console.log('\n%c🔄 SIMULANDO INICIALIZACIÓN...', styles.highlight);

setTimeout(() => {
    // Restaurar console.log
    console.log = originalLog;
    
    console.log('\n%c📈 RESULTADOS DE VERBOSIDAD:', styles.info);
    console.log(`%c📊 Total de mensajes capturados: ${capturedMessages.length}`, styles.code);
    
    const hiddenMessages = capturedMessages.filter(m => m.shouldBeHidden);
    const visibleMessages = capturedMessages.filter(m => !m.shouldBeHidden);
    
    console.log(`%c🔇 Mensajes que deberían estar ocultos: ${hiddenMessages.length}`, 
                hiddenMessages.length === 0 ? styles.success : styles.warning);
    console.log(`%c👁 Mensajes visibles apropiados: ${visibleMessages.length}`, styles.code);
    
    if (hiddenMessages.length > 0) {
        console.log('\n%c⚠️ Mensajes de inicialización detectados:', styles.warning);
        hiddenMessages.forEach(m => {
            console.log(`   • ${m.message}`);
        });
    }
}, 1000);

// 5. Comandos de prueba
console.log('\n%c5️⃣ COMANDOS DISPONIBLES PARA PRUEBAS:', styles.info);
console.log('%c   • testLoggingLevels()', styles.code, '- Probar todos los niveles de logging');
console.log('%c   • toggleTestMode()', styles.code, '- Alternar modo de testing');
console.log('%c   • checkInitializationNoise()', styles.code, '- Verificar ruido de inicialización');

// Funciones de prueba globales
window.testLoggingLevels = function() {
    console.log('%c🧪 PROBANDO NIVELES DE LOGGING', styles.highlight);
    
    if (window.DevToolsController) {
        const controller = window.DevToolsController;
        
        console.log('\n%cTesting logInternal con diferentes niveles:', styles.info);
        controller.logInternal('Mensaje de prueba - CRITICAL', { test: true }, 'critical');
        controller.logInternal('Mensaje de prueba - NORMAL', { test: true }, 'normal');
        controller.logInternal('Mensaje de prueba - MINIMAL', { test: true }, 'minimal');
        
        console.log('\n%cEstado actual:', styles.code);
        console.log('verboseMode:', controller.verboseMode);
        console.log('debugMode:', controller.debugMode);
        console.log('isTestExecution:', controller.isTestExecution);
    }
};

window.toggleTestMode = function() {
    if (window.DevToolsController) {
        const controller = window.DevToolsController;
        controller.isTestExecution = !controller.isTestExecution;
        console.log('%c🔄 Modo test alternado:', styles.highlight, controller.isTestExecution);
        
        // Probar logging después del cambio
        controller.logInternal('Prueba después de alternar test mode', { newState: controller.isTestExecution }, 'normal');
    }
};

window.checkInitializationNoise = function() {
    console.log('%c🔍 VERIFICANDO RUIDO DE INICIALIZACIÓN', styles.highlight);
    
    // Simular reinicialización
    if (window.DevToolsController) {
        const controller = window.DevToolsController;
        
        console.log('\n%cEstado antes de simular inicialización:', styles.code);
        console.log('isTestExecution:', controller.isTestExecution);
        console.log('verboseMode:', controller.verboseMode);
        console.log('debugMode:', controller.debugMode);
        
        // Simular mensajes de inicialización
        console.log('\n%c📝 Simulando mensajes de inicialización:', styles.info);
        controller.logInternal('Configuración cargada - Simulación', null, 'minimal');
        controller.logInternal('Módulos inicializados - Simulación', null, 'minimal');
        controller.logInternal('Event listeners configurados - Simulación', null, 'minimal');
        
        console.log('\n%c✅ Verificación completada. ¿Aparecieron mensajes de inicialización?', styles.success);
    }
};

console.log('\n%c🎯 INSTRUCCIONES:', styles.highlight);
console.log('%c1. Ejecuta testLoggingLevels() para probar el sistema', styles.code);
console.log('%c2. Verifica que no aparezcan mensajes de inicialización innecesarios', styles.code);
console.log('%c3. Solo deberían verse logs durante ejecución de tests o con debug completo', styles.code);
console.log('%c4. Los mensajes críticos (errores) siempre deben aparecer', styles.code);
