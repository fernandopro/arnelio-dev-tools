/**
 * Script de Verificación de Opciones de Test
 * 
 * Este script verifica que las opciones verboseOutput y generateCoverage
 * funcionan correctamente tanto individualmente como en combinación.
 * 
 * INSTRUCCIONES:
 * 1. Navega manualmente al panel de Dev-Tools en tu WordPress
 * 2. Abre la consola del navegador (F12)
 * 3. Copia y pega este script completo
 * 4. El script ejecutará tests automáticamente con diferentes combinaciones
 */

console.log('🧪 INICIANDO VERIFICACIÓN DE OPCIONES DE TEST');
console.log('='.repeat(50));

// Función de utilidad para esperar
const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Función para verificar que los elementos existen
function verifyUIElements() {
    const elements = {
        unitTests: document.getElementById('unitTests'),
        integrationTests: document.getElementById('integrationTests'),
        database: document.getElementById('database'),
        verboseOutput: document.getElementById('verboseOutput'),
        generateCoverage: document.getElementById('generateCoverage'),
        runTest: document.getElementById('runTest'),
        testResults: document.getElementById('testResults')
    };
    
    console.log('🔍 Verificando elementos UI...');
    for (const [name, element] of Object.entries(elements)) {
        if (element) {
            console.log(`  ✅ ${name}: ENCONTRADO`);
        } else {
            console.log(`  ❌ ${name}: NO ENCONTRADO`);
        }
    }
    
    return elements;
}

// Función para configurar checkboxes
function setCheckboxes(unit = false, integration = false, database = false, verbose = false, coverage = false) {
    const config = { unit, integration, database, verbose, coverage };
    console.log(`⚙️ Configurando checkboxes:`, config);
    
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
    
    // Verificar que se configuraron correctamente
    const actual = {
        unit: document.getElementById('unitTests')?.checked,
        integration: document.getElementById('integrationTests')?.checked,
        database: document.getElementById('database')?.checked,
        verbose: document.getElementById('verboseOutput')?.checked,
        coverage: document.getElementById('generateCoverage')?.checked
    };
    
    console.log(`📋 Estado actual de checkboxes:`, actual);
    return actual;
}

// Función para ejecutar un test y capturar resultados
async function executeTest(testName, unit = false, integration = false, database = false, verbose = false, coverage = false) {
    console.log(`\n🚀 EJECUTANDO: ${testName}`);
    console.log('-'.repeat(30));
    
    // Configurar checkboxes
    setCheckboxes(unit, integration, database, verbose, coverage);
    
    // Limpiar resultados previos
    const testResults = document.getElementById('testResults');
    if (testResults) {
        testResults.innerHTML = '<div class="text-muted">Ejecutando test...</div>';
    }
    
    // Capturar consola antes de ejecutar
    const originalLog = console.log;
    const logs = [];
    console.log = (...args) => {
        logs.push(args.join(' '));
        originalLog(...args);
    };
    
    try {
        // Verificar que devTools.testRunner existe
        if (typeof devTools === 'undefined' || !devTools.testRunner) {
            throw new Error('devTools.testRunner no está disponible');
        }
        
        // Ejecutar el test
        await devTools.testRunner.runTests();
        
        // Esperar a que termine la ejecución
        await wait(3000);
        
        // Capturar resultados
        const finalResults = testResults ? testResults.innerHTML : 'No se pudo obtener resultados';
        
        // Buscar en los logs información específica de comando y opciones
        const relevantLogs = logs.filter(log => 
            log.includes('build_phpunit_command') || 
            log.includes('--verbose') || 
            log.includes('--coverage-text') ||
            log.includes('DEBUG') ||
            log.includes('AJAX') ||
            log.includes('test_types')
        );
        
        console.log(`📊 RESULTADOS DE: ${testName}`);
        console.log(`  - Resultado HTML: ${finalResults.substring(0, 100)}...`);
        console.log(`  - Logs relevantes:`, relevantLogs);
        
        return {
            success: !finalResults.includes('Error'),
            results: finalResults,
            logs: relevantLogs
        };
        
    } catch (error) {
        console.error(`❌ Error en ${testName}:`, error);
        return {
            success: false,
            error: error.message,
            logs: logs
        };
    } finally {
        // Restaurar console.log
        console.log = originalLog;
    }
}

// Función principal de verificación
async function runVerification() {
    console.log('🔧 Iniciando verificación completa...\n');
    
    // Verificar elementos UI
    const elements = verifyUIElements();
    
    if (!elements.verboseOutput || !elements.generateCoverage) {
        console.error('❌ ERROR: No se encontraron los checkboxes de opciones');
        return;
    }
    
    // Conjunto de tests para verificar todas las combinaciones
    const testCases = [
        {
            name: 'Test Unitario - Sin opciones',
            unit: true, integration: false, database: false, verbose: false, coverage: false
        },
        {
            name: 'Test Unitario - Solo Verbose',
            unit: true, integration: false, database: false, verbose: true, coverage: false
        },
        {
            name: 'Test Unitario - Solo Coverage',
            unit: true, integration: false, database: false, verbose: false, coverage: true
        },
        {
            name: 'Test Unitario - Verbose + Coverage',
            unit: true, integration: false, database: false, verbose: true, coverage: true
        },
        {
            name: 'Test Integración - Verbose + Coverage',
            unit: false, integration: true, database: false, verbose: true, coverage: true
        },
        {
            name: 'Test Database - Solo Verbose',
            unit: false, integration: false, database: true, verbose: true, coverage: false
        },
        {
            name: 'Múltiples Tests - Todas las opciones',
            unit: true, integration: true, database: true, verbose: true, coverage: true
        }
    ];
    
    const results = [];
    
    for (const testCase of testCases) {
        const result = await executeTest(
            testCase.name,
            testCase.unit,
            testCase.integration,
            testCase.database,
            testCase.verbose,
            testCase.coverage
        );
        
        results.push({ testCase, result });
        
        // Esperar entre tests
        await wait(2000);
    }
    
    // Resumen final
    console.log('\n📈 RESUMEN FINAL DE VERIFICACIÓN');
    console.log('='.repeat(50));
    
    results.forEach(({ testCase, result }, index) => {
        const status = result.success ? '✅ ÉXITO' : '❌ FALLO';
        console.log(`${index + 1}. ${testCase.name}: ${status}`);
        
        if (result.error) {
            console.log(`   Error: ${result.error}`);
        }
        
        // Verificar si las opciones aparecen en los logs
        const hasVerbose = result.logs.some(log => log.includes('--verbose'));
        const hasCoverage = result.logs.some(log => log.includes('--coverage-text'));
        
        if (testCase.verbose && !hasVerbose) {
            console.log(`   ⚠️ ADVERTENCIA: Se esperaba --verbose pero no se encontró en logs`);
        }
        if (testCase.coverage && !hasCoverage) {
            console.log(`   ⚠️ ADVERTENCIA: Se esperaba --coverage-text pero no se encontró en logs`);
        }
        if (testCase.verbose && hasVerbose) {
            console.log(`   ✅ Opción --verbose detectada en comando`);
        }
        if (testCase.coverage && hasCoverage) {
            console.log(`   ✅ Opción --coverage-text detectada en comando`);
        }
    });
    
    // Estadísticas
    const successCount = results.filter(r => r.result.success).length;
    const totalCount = results.length;
    
    console.log(`\n📊 ESTADÍSTICAS:`);
    console.log(`  - Tests exitosos: ${successCount}/${totalCount}`);
    console.log(`  - Porcentaje de éxito: ${Math.round((successCount/totalCount) * 100)}%`);
    
    if (successCount === totalCount) {
        console.log('\n🎉 VERIFICACIÓN COMPLETADA CON ÉXITO');
        console.log('✅ Todas las opciones de test funcionan correctamente');
    } else {
        console.log('\n⚠️ VERIFICACIÓN COMPLETADA CON PROBLEMAS');
        console.log('❌ Algunas opciones no funcionaron como se esperaba');
    }
}

// Verificar que estamos en el contexto correcto
if (typeof devTools === 'undefined') {
    console.error('❌ ERROR: devTools no está disponible. Asegúrate de estar en el panel de Dev-Tools.');
} else {
    console.log('✅ devTools detectado, iniciando verificación en 2 segundos...');
    setTimeout(runVerification, 2000);
}
