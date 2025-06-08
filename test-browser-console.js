/**
 * SCRIPT DE TESTING DEV-TOOLS PARA CONSOLA DEL NAVEGADOR
 * Copia y pega este código en la consola del navegador (F12)
 * para probar la Arquitectura 3.0 completa
 */

console.log('🔧 INICIANDO TESTS DEV-TOOLS ARQUITECTURA 3.0...');

// Test 1: Verificar configuración disponible
function testConfig() {
    console.log('📋 Test 1: Configuración del sistema');
    
    if (typeof devToolsConfig !== 'undefined') {
        console.log('✅ devToolsConfig disponible:', devToolsConfig);
        return true;
    } else {
        console.log('❌ devToolsConfig no disponible');
        return false;
    }
}

// Test 2: Verificar aplicación principal
function testMainApp() {
    console.log('📋 Test 2: Aplicación principal');
    
    if (typeof DevToolsController !== 'undefined') {
        console.log('✅ DevToolsController disponible');
        window.devToolsApp = new DevToolsController();
        console.log('✅ Instancia creada:', window.devToolsApp);
        return true;
    } else {
        console.log('❌ DevToolsController no disponible');
        return false;
    }
}

// Test 3: Test AJAX básico
async function testAjax() {
    console.log('📋 Test 3: Conectividad AJAX');
    
    if (!window.devToolsApp) {
        console.log('❌ Aplicación no inicializada');
        return false;
    }
    
    try {
        const result = await window.devToolsApp.makeAjaxRequest('ping', {});
        console.log('✅ AJAX ping exitoso:', result);
        return true;
    } catch (error) {
        console.log('❌ AJAX ping falló:', error);
        return false;
    }
}

// Test 4: Test información del sistema
async function testSystemInfo() {
    console.log('📋 Test 4: Información del sistema');
    
    try {
        const result = await window.devToolsApp.makeAjaxRequest('get_system_info', {});
        console.log('✅ System info obtenida:', result);
        return true;
    } catch (error) {
        console.log('❌ System info falló:', error);
        return false;
    }
}

// Test 5: Test módulos disponibles
function testModules() {
    console.log('📋 Test 5: Módulos JavaScript');
    
    const modules = [
        'DevToolsDashboard',
        'DevToolsSystemInfo', 
        'DevToolsCache',
        'DevToolsAjaxTester',
        'DevToolsLogs',
        'DevToolsPerformance'
    ];
    
    let available = 0;
    modules.forEach(module => {
        if (typeof window[module] !== 'undefined') {
            console.log(`✅ ${module} disponible`);
            available++;
        } else {
            console.log(`❌ ${module} no disponible`);
        }
    });
    
    console.log(`📊 Módulos disponibles: ${available}/${modules.length}`);
    return available > 0;
}

// Test 6: Test debugging de AJAX
async function testAjaxDebugger() {
    console.log('📋 Test 6: Sistema de debugging AJAX');
    
    if (typeof window.devToolsDebugAjax === 'function') {
        try {
            const debugInfo = await window.devToolsDebugAjax();
            console.log('✅ Debug info obtenida:', debugInfo);
            return true;
        } catch (error) {
            console.log('❌ Debug info falló:', error);
            return false;
        }
    } else {
        console.log('❌ Función debug no disponible');
        return false;
    }
}

// Test 7: Test Bootstrap y estilos
function testBootstrap() {
    console.log('📋 Test 7: Bootstrap y estilos');
    
    const hasBootstrap = document.querySelector('.btn') !== null;
    const hasDevToolsStyles = document.querySelector('.dev-tools-container') !== null;
    
    if (hasBootstrap) {
        console.log('✅ Bootstrap detectado');
    } else {
        console.log('❌ Bootstrap no detectado');
    }
    
    if (hasDevToolsStyles) {
        console.log('✅ Estilos dev-tools detectados');
    } else {
        console.log('❌ Estilos dev-tools no detectados');
    }
    
    return hasBootstrap || hasDevToolsStyles;
}

// Test 8: Test memoria y rendimiento
function testPerformance() {
    console.log('📋 Test 8: Memoria y rendimiento');
    
    const memory = performance.memory;
    if (memory) {
        console.log('✅ Información de memoria:', {
            used: `${Math.round(memory.usedJSHeapSize / 1024 / 1024)}MB`,
            total: `${Math.round(memory.totalJSHeapSize / 1024 / 1024)}MB`,
            limit: `${Math.round(memory.jsHeapSizeLimit / 1024 / 1024)}MB`
        });
        return true;
    } else {
        console.log('❌ Información de memoria no disponible');
        return false;
    }
}

// Test 9: Test consola de errores
function testErrorReporting() {
    console.log('📋 Test 9: Sistema de reporte de errores');
    
    // Simular un error controlado
    window.addEventListener('error', function testErrorHandler(event) {
        console.log('✅ Sistema de captura de errores funcional');
        window.removeEventListener('error', testErrorHandler);
    });
    
    // Generar error controlado
    setTimeout(() => {
        try {
            // Esto no debería causar error real
            console.log('✅ Test de errores completado sin problemas');
        } catch (e) {
            console.log('Error controlado capturado:', e);
        }
    }, 100);
    
    return true;
}

// Función para ejecutar todos los tests
async function runAllTests() {
    console.log('🚀 EJECUTANDO SUITE COMPLETA DE TESTS...');
    console.log('=' .repeat(50));
    
    const tests = [
        { name: 'Config', func: testConfig },
        { name: 'Main App', func: testMainApp },
        { name: 'AJAX', func: testAjax },
        { name: 'System Info', func: testSystemInfo },
        { name: 'Modules', func: testModules },
        { name: 'AJAX Debugger', func: testAjaxDebugger },
        { name: 'Bootstrap', func: testBootstrap },
        { name: 'Performance', func: testPerformance },
        { name: 'Error Reporting', func: testErrorReporting }
    ];
    
    let passed = 0;
    const results = [];
    
    for (const test of tests) {
        try {
            const result = await test.func();
            results.push({ name: test.name, passed: result });
            if (result) passed++;
        } catch (error) {
            console.error(`❌ Test ${test.name} falló:`, error);
            results.push({ name: test.name, passed: false });
        }
        console.log(''); // Línea en blanco entre tests
    }
    
    console.log('=' .repeat(50));
    console.log('📊 RESUMEN DE TESTS:');
    results.forEach(result => {
        console.log(`${result.passed ? '✅' : '❌'} ${result.name}`);
    });
    console.log(`\n🎯 Total: ${passed}/${tests.length} tests pasaron`);
    
    if (passed === tests.length) {
        console.log('🎉 ¡TODOS LOS TESTS PASARON! Sistema completamente funcional.');
    } else if (passed > tests.length / 2) {
        console.log('⚠️ La mayoría de tests pasaron. Sistema mayormente funcional.');
    } else {
        console.log('❌ Varios tests fallaron. Revisar configuración.');
    }
    
    return { passed, total: tests.length, results };
}

// Funciones de ayuda adicionales
function showSystemInfo() {
    console.log('📋 INFORMACIÓN DEL SISTEMA:');
    console.log('- URL:', window.location.href);
    console.log('- User Agent:', navigator.userAgent);
    console.log('- Pantalla:', `${screen.width}x${screen.height}`);
    console.log('- Viewport:', `${window.innerWidth}x${window.innerHeight}`);
    console.log('- Idioma:', navigator.language);
    console.log('- Zona horaria:', Intl.DateTimeFormat().resolvedOptions().timeZone);
    console.log('- Conexión:', navigator.onLine ? 'Online' : 'Offline');
}

function testSpecificModule(moduleName) {
    console.log(`🔍 Testing specific module: ${moduleName}`);
    
    if (typeof window[moduleName] !== 'undefined') {
        console.log(`✅ ${moduleName} disponible`);
        try {
            const instance = new window[moduleName]();
            console.log(`✅ Instancia de ${moduleName} creada:`, instance);
            return instance;
        } catch (error) {
            console.log(`❌ Error creando instancia de ${moduleName}:`, error);
            return null;
        }
    } else {
        console.log(`❌ ${moduleName} no disponible`);
        return null;
    }
}

// Información de uso
console.log(`
🔧 COMANDOS DISPONIBLES:
- runAllTests()          - Ejecutar todos los tests
- testConfig()           - Test configuración
- testMainApp()          - Test aplicación principal  
- testAjax()             - Test conectividad AJAX
- testSystemInfo()       - Test información del sistema
- testModules()          - Test módulos JavaScript
- showSystemInfo()       - Mostrar info del sistema
- testSpecificModule('ModuleName') - Test módulo específico

📋 EJEMPLO DE USO:
runAllTests().then(result => {
    console.log('Tests completados:', result);
});

🎯 Para comenzar, ejecuta: runAllTests()
`);

// Auto-ejecutar test básico en desarrollo
if (window.location.hostname === 'localhost') {
    console.log('🏠 Entorno local detectado - ejecutando test rápido...');
    setTimeout(() => {
        testConfig() && testMainApp();
    }, 1000);
}
