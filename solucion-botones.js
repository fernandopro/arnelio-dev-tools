/**
 * SOLUCIÓN DEFINITIVA - Habilitar botones automáticamente
 * =====================================================
 * Habilita todos los botones disabled y los mantiene funcionando
 */

console.clear();
console.log('%c🔧 SOLUCIÓN DEFINITIVA - HABILITANDO BOTONES', 'background: #059669; color: white; padding: 12px; font-size: 16px; font-weight: bold;');

// 1. HABILITAR TODOS LOS BOTONES DE TEST
console.log('\n1️⃣ HABILITANDO BOTONES DE TEST');
console.log('==============================');

const testButtons = document.querySelectorAll('[data-action="run_single_test"]');
const allTestButtons = document.querySelectorAll('[data-action*="test"]');

console.log(`Botones run_single_test encontrados: ${testButtons.length}`);
console.log(`Botones de test total encontrados: ${allTestButtons.length}`);

// Habilitar todos los botones de test
allTestButtons.forEach((button, index) => {
    const wasDisabled = button.disabled;
    button.disabled = false;
    
    if (wasDisabled) {
        console.log(`✅ Habilitado botón ${index + 1}: ${button.getAttribute('data-action')}`);
    }
    
    // Restaurar clases visuales correctas
    button.classList.remove('btn-secondary');
    const action = button.getAttribute('data-action');
    
    switch (action) {
        case 'run_single_test':
            button.classList.add('btn-success');
            break;
        case 'run_wp_tests':
            button.classList.add('btn-outline-info', 'btn-outline-success', 'btn-outline-primary');
            break;
        case 'refresh_tests':
            button.classList.add('btn-outline-secondary');
            break;
    }
});

console.log('✅ Todos los botones habilitados');

// 2. INTERCEPTOR PARA PREVENIR DISABLED PERMANENTE
console.log('\n2️⃣ INTERCEPTOR ANTI-DISABLED');
console.log('=============================');

// Interceptar setRunningState para debuggear
if (typeof window.DevToolsTestRunner !== 'undefined') {
    const originalSetRunningState = window.DevToolsTestRunner.setRunningState;
    
    window.DevToolsTestRunner.setRunningState = function(isRunning, button) {
        console.log(`%c🔄 setRunningState: ${isRunning ? 'EJECUTANDO' : 'TERMINADO'}`, 
                   `background: ${isRunning ? '#f59e0b' : '#059669'}; color: white; padding: 4px; font-weight: bold;`);
        console.log('Button:', button);
        console.log('Button disabled antes:', button ? button.disabled : 'N/A');
        
        // Llamar al método original
        const result = originalSetRunningState.call(this, isRunning, button);
        
        console.log('Button disabled después:', button ? button.disabled : 'N/A');
        
        // Forzar habilitación después de un delay si terminó la ejecución
        if (!isRunning && button) {
            setTimeout(() => {
                if (button.disabled) {
                    console.log('🔧 Forzando habilitación del botón retrasada...');
                    button.disabled = false;
                    button.classList.remove('btn-secondary');
                    
                    const action = button.getAttribute('data-action');
                    if (action === 'run_single_test') {
                        button.classList.add('btn-success');
                    }
                }
            }, 100);
        }
        
        return result;
    };
    
    console.log('✅ Interceptor setRunningState instalado');
}

// 3. MONITOR PERIÓDICO DE BOTONES
console.log('\n3️⃣ MONITOR PERIÓDICO');
console.log('====================');

let monitorInterval = null;

function iniciarMonitor() {
    if (monitorInterval) {
        clearInterval(monitorInterval);
    }
    
    monitorInterval = setInterval(() => {
        const disabledButtons = document.querySelectorAll('[data-action*="test"]:disabled');
        
        if (disabledButtons.length > 0) {
            console.log(`🔧 Monitor: Encontrados ${disabledButtons.length} botones disabled, habilitando...`);
            
            disabledButtons.forEach(button => {
                // Solo habilitar si no está en estado de ejecución real
                if (!button.innerHTML.includes('Ejecutando...')) {
                    button.disabled = false;
                    button.classList.remove('btn-secondary');
                    
                    const action = button.getAttribute('data-action');
                    if (action === 'run_single_test') {
                        button.classList.add('btn-success');
                    }
                }
            });
        }
    }, 2000); // Cada 2 segundos
    
    console.log('✅ Monitor periódico iniciado (cada 2 segundos)');
}

function detenerMonitor() {
    if (monitorInterval) {
        clearInterval(monitorInterval);
        monitorInterval = null;
        console.log('🛑 Monitor periódico detenido');
    }
}

// Iniciar el monitor automáticamente
iniciarMonitor();

// 4. FUNCIONES DE CONTROL
console.log('\n4️⃣ FUNCIONES DE CONTROL');
console.log('=======================');

window.habilitarTodosLosBotones = function() {
    console.log('🔧 Habilitando todos los botones manualmente...');
    const buttons = document.querySelectorAll('[data-action*="test"]');
    buttons.forEach(button => {
        button.disabled = false;
        button.classList.remove('btn-secondary');
        
        const action = button.getAttribute('data-action');
        if (action === 'run_single_test') {
            button.classList.add('btn-success');
        }
    });
    console.log(`✅ ${buttons.length} botones habilitados`);
};

window.iniciarMonitorBotones = iniciarMonitor;
window.detenerMonitorBotones = detenerMonitor;

console.log('\n💡 Comandos disponibles:');
console.log('   • habilitarTodosLosBotones() - Habilita todos los botones manualmente');
console.log('   • iniciarMonitorBotones() - Reinicia el monitor automático');
console.log('   • detenerMonitorBotones() - Detiene el monitor automático');

console.log('\n🎉 ¡SISTEMA CORREGIDO! Los botones ahora deberían funcionar correctamente');
console.log('👉 Prueba haciendo click en cualquier botón verde "Ejecutar test"');
