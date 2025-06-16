/**
 * Script Simple de Verificación de Opciones
 * 
 * Este script verifica específicamente que las opciones verbose y coverage
 * se lean correctamente de los checkboxes y se envíen al backend.
 * 
 * INSTRUCCIONES:
 * 1. Abre el panel de Dev-Tools en tu navegador
 * 2. Abre la consola del navegador (F12)
 * 3. Copia y pega este script
 * 4. Marca/desmarca las opciones y ejecuta los tests manualmente
 */

console.log('🔍 VERIFICACIÓN SIMPLE DE OPCIONES');
console.log('=====================================');

// Función para mostrar el estado actual de los checkboxes
function showCheckboxStatus() {
    const unitTests = document.getElementById('unitTests')?.checked || false;
    const integrationTests = document.getElementById('integrationTests')?.checked || false;
    const database = document.getElementById('database')?.checked || false;
    const verboseOutput = document.getElementById('verboseOutput')?.checked || false;
    const generateCoverage = document.getElementById('generateCoverage')?.checked || false;
    
    console.log('📋 ESTADO ACTUAL DE CHECKBOXES:');
    console.log(`  Unit Tests: ${unitTests}`);
    console.log(`  Integration Tests: ${integrationTests}`);
    console.log(`  Database Tests: ${database}`);
    console.log(`  Verbose Output: ${verboseOutput}`);
    console.log(`  Generate Coverage: ${generateCoverage}`);
    
    return {
        testTypes: [
            unitTests && 'unit',
            integrationTests && 'integration', 
            database && 'database'
        ].filter(Boolean),
        verbose: verboseOutput,
        coverage: generateCoverage
    };
}

// Interceptar llamadas AJAX para ver qué se envía
const originalFetch = window.fetch;
window.fetch = function(...args) {
    const [url, options] = args;
    
    if (url.includes('admin-ajax.php') && options && options.body) {
        const body = options.body;
        
        // Si es URLSearchParams, convertir a string para analizar
        let bodyString = '';
        if (body instanceof URLSearchParams) {
            bodyString = body.toString();
        } else if (typeof body === 'string') {
            bodyString = body;
        }
        
        // Verificar si es una llamada de test
        if (bodyString.includes('dev_tools_run_tests')) {
            console.log('🚀 DETECTADA LLAMADA AJAX DE TEST:');
            console.log('  URL:', url);
            
            // Parsear parámetros
            const params = new URLSearchParams(bodyString);
            console.log('  Parámetros enviados:');
            for (const [key, value] of params.entries()) {
                console.log(`    ${key}: ${value}`);
            }
            
            // Destacar las opciones específicas
            const verbose = params.get('verbose');
            const coverage = params.get('coverage');
            const testTypes = params.getAll('test_types[]') || [params.get('test_types')];
            
            console.log('  🎯 OPCIONES CLAVE:');
            console.log(`    test_types: ${JSON.stringify(testTypes)}`);
            console.log(`    verbose: ${verbose}`);
            console.log(`    coverage: ${coverage}`);
        }
    }
    
    return originalFetch.apply(this, args);
};

// Función para verificar elementos específicos
function verifyUIElements() {
    const elements = ['unitTests', 'integrationTests', 'database', 'verboseOutput', 'generateCoverage'];
    
    console.log('🔍 VERIFICANDO ELEMENTOS UI:');
    elements.forEach(id => {
        const element = document.getElementById(id);
        const status = element ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO';
        console.log(`  ${id}: ${status}`);
        
        if (element && element.type === 'checkbox') {
            console.log(`    Estado: ${element.checked ? 'MARCADO' : 'DESMARCADO'}`);
        }
    });
}

// Función para establecer un estado de test específico
function setTestConfiguration(config = {}) {
    const {
        unit = true,
        integration = false,
        database = false,
        verbose = true,
        coverage = true
    } = config;
    
    console.log('⚙️ CONFIGURANDO ESTADO DE TEST:', config);
    
    if (document.getElementById('unitTests')) {
        document.getElementById('unitTests').checked = unit;
    }
    if (document.getElementById('integrationTests')) {
        document.getElementById('integrationTests').checked = integration;
    }
    if (document.getElementById('database')) {
        document.getElementById('database').checked = database;
    }
    if (document.getElementById('verboseOutput')) {
        document.getElementById('verboseOutput').checked = verbose;
    }
    if (document.getElementById('generateCoverage')) {
        document.getElementById('generateCoverage').checked = coverage;
    }
    
    // Mostrar estado resultante
    setTimeout(() => {
        showCheckboxStatus();
        console.log('💡 Ahora puedes hacer clic en "Run Selected Tests" para ver las opciones enviadas');
    }, 100);
}

// Ejecutar verificación inicial
console.log('🔧 Ejecutando verificación inicial...\n');
verifyUIElements();
console.log('');
showCheckboxStatus();

console.log('\n📝 INSTRUCCIONES DE USO:');
console.log('1. Para ver el estado actual: showCheckboxStatus()');
console.log('2. Para configurar test con opciones: setTestConfiguration({unit: true, verbose: true, coverage: true})');
console.log('3. Para configurar test básico: setTestConfiguration({unit: true, verbose: false, coverage: false})');
console.log('4. Para configurar test completo: setTestConfiguration({unit: true, integration: true, database: true, verbose: true, coverage: true})');
console.log('5. Las llamadas AJAX se mostrarán automáticamente en la consola');

console.log('\n🎯 CONFIGURACIÓN RECOMENDADA PARA PRUEBA:');
setTestConfiguration({
    unit: true,
    integration: false,
    database: false,
    verbose: true,
    coverage: true
});
