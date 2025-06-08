/**
 * Verificación Final Completa - Consola Limpia
 * Script para verificar que no aparezcan mensajes de inicialización innecesarios
 * 
 * Ejecutar en consola del navegador en la página de dev-tools
 * Resultado esperado: Solo logs relevantes de tests, sin ruido de inicialización
 * 
 * ACTUALIZADO: Versión final con detección mejorada de mensajes innecesarios
 */

// 🎨 Estilos optimizados para modo oscuro
const styles = {
    title: `background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
             color: white; 
             padding: 12px 24px; 
             border-radius: 8px; 
             font-weight: bold; 
             font-size: 16px;
             text-shadow: 0 1px 2px rgba(0,0,0,0.3);`,
    
    success: `background: linear-gradient(135deg, #34d399 0%, #10b981 100%); 
              color: white; 
              padding: 8px 16px; 
              border-radius: 6px; 
              font-weight: bold;
              text-shadow: 0 1px 2px rgba(0,0,0,0.2);`,
    
    warning: `background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); 
              color: #1f2937; 
              padding: 8px 16px; 
              border-radius: 6px; 
              font-weight: bold;
              text-shadow: 0 1px 2px rgba(255,255,255,0.2);`,
    
    info: `background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); 
           color: white; 
           padding: 8px 16px; 
           border-radius: 6px;
           text-shadow: 0 1px 2px rgba(0,0,0,0.2);`,
    
    code: `background: #1f2937; 
           color: #34d399; 
           padding: 4px 8px; 
           border-radius: 4px; 
           font-family: 'Fira Code', 'Monaco', monospace;
           font-size: 13px;`,
           
    test: `background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); 
           color: white; 
           padding: 8px 16px; 
           border-radius: 6px; 
           font-weight: bold;
           text-shadow: 0 1px 2px rgba(0,0,0,0.2);`
};

console.log('%c🧹 VERIFICACIÓN FINAL - CONSOLA LIMPIA', styles.title);
console.log('');

// 1. Verificar configuración cargada
if (typeof window.tkn_dev_tools_config !== 'undefined') {
    console.log('%c✅ Configuración cargada correctamente', styles.success);
    console.log('%c   debug_mode: ' + window.tkn_dev_tools_config.debug_mode, styles.info);
} else {
    console.log('%c❌ Configuración no encontrada', styles.warning);
}

// 2. Verificar clases principales
const classes = [
    { name: 'DevToolsTestRunner', obj: window.DevToolsTestRunner },
    { name: 'DevToolsUtils', obj: window.DevToolsUtils },
    { name: 'SimpleCodeHighlighter', obj: window.SimpleCodeHighlighter },
    { name: 'TestResultsFormatter', obj: window.TestResultsFormatter }
];

console.log('\n%c🔍 VERIFICACIÓN DE CLASES:', styles.info);
classes.forEach(({ name, obj }) => {
    if (obj) {
        console.log('%c✅ ' + name + ' está disponible', styles.success);
    } else {
        console.log('%c❌ ' + name + ' no está disponible', styles.warning);
    }
});

// 3. Verificar botones de test
const testButtons = document.querySelectorAll('button[data-action]');
console.log('\n%c🧪 BOTONES DE TEST ENCONTRADOS:', styles.info);
console.log('%c   Total: ' + testButtons.length, styles.code);

if (testButtons.length > 0) {
    console.log('%c✅ Botones de test disponibles', styles.success);
    
    // Mostrar algunos ejemplos
    const firstFew = Array.from(testButtons).slice(0, 3);
    firstFew.forEach((btn, index) => {
        const action = btn.dataset.action;
        const text = btn.textContent.trim();
        const disabled = btn.disabled ? ' (DESHABILITADO)' : '';
        console.log(`%c   ${index + 1}. ${action}: "${text}"${disabled}`, styles.code);
    });
} else {
    console.log('%c❌ No se encontraron botones de test', styles.warning);
}

// 4. Test de ejecución (solo si hay botones disponibles)
if (testButtons.length > 0) {
    console.log('\n%c🚀 PREPARANDO TEST DE LIMPIEZA...', styles.test);
    console.log('%cHaz clic en cualquier botón de test para verificar que solo aparezcan logs relevantes', styles.info);
    console.log('%c(Sin mensajes de "Inicializado" o similares)', styles.info);
    
    // Interceptar console.log para detectar mensajes innecesarios
    const originalLog = console.log;
    const unwantedMessages = [
        'DevToolsDocsManager: Inicializado',
        'DevToolsMaintenanceManager: Inicializado', 
        'DevToolsSettingsManager: Inicializado',
        'Dev Tools Utilities cargadas correctamente',
        'DevTools Test Runner',
        'Event listeners configurados'
    ];
    
    let messageCount = 0;
    console.log = function(...args) {
        messageCount++;
        const message = args.join(' ');
        
        // Detectar mensajes no deseados
        const hasUnwantedMessage = unwantedMessages.some(unwanted => 
            message.includes(unwanted)
        );
        
        if (hasUnwantedMessage) {
            originalLog('%c⚠️ MENSAJE INNECESARIO DETECTADO:', styles.warning);
            originalLog('%c' + message, 'color: #f87171; font-weight: bold;');
        } else {
            // Permitir el log normal
            originalLog.apply(console, args);
        }
    };
    
    setTimeout(() => {
        console.log = originalLog; // Restaurar después de 30 segundos
        console.log('%c🔍 Interceptor de consola desactivado', styles.info);
    }, 30000);
    
} else {
    console.log('\n%c⚠️ No se pueden realizar tests sin botones disponibles', styles.warning);
}

console.log('\n%c📋 INSTRUCCIONES:', styles.info);
console.log('%c1. Haz clic en cualquier botón "Ejecutar Test"', styles.code);
console.log('%c2. Observa que solo aparezcan logs de ejecución de tests', styles.code);
console.log('%c3. No deberían aparecer mensajes de inicialización', styles.code);
console.log('%c4. Los colores están optimizados para modo oscuro', styles.code);
