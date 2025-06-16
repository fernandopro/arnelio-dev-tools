/**
 * Verificación Final - Comandos PHPUnit Generados
 * 
 * Este script te permite verificar manualmente que los comandos PHPUnit
 * se generen correctamente con las opciones seleccionadas.
 * 
 * INSTRUCCIONES:
 * 1. Abre el panel de Dev-Tools
 * 2. Abre la consola del navegador
 * 3. Copia y pega este script
 * 4. Usa las funciones para probar diferentes combinaciones
 */

console.log('🎯 VERIFICACIÓN FINAL - COMANDOS PHPUNIT');
console.log('=========================================');

// Función para hacer una llamada AJAX manual y capturar el comando generado
async function testCommandGeneration(testTypes, verbose, coverage) {
    console.log(`\n🔧 PROBANDO CONFIGURACIÓN:`);
    console.log(`  Test Types: ${JSON.stringify(testTypes)}`);
    console.log(`  Verbose: ${verbose}`);
    console.log(`  Coverage: ${coverage}`);
    
    try {
        // Configurar los checkboxes en la UI
        if (document.getElementById('unitTests')) {
            document.getElementById('unitTests').checked = testTypes.includes('unit');
        }
        if (document.getElementById('integrationTests')) {
            document.getElementById('integrationTests').checked = testTypes.includes('integration');
        }
        if (document.getElementById('database')) {
            document.getElementById('database').checked = testTypes.includes('database');
        }
        if (document.getElementById('verboseOutput')) {
            document.getElementById('verboseOutput').checked = verbose;
        }
        if (document.getElementById('generateCoverage')) {
            document.getElementById('generateCoverage').checked = coverage;
        }
        
        // Hacer la llamada AJAX
        const nonce = (typeof devToolsConfig !== 'undefined') ? devToolsConfig.nonce : '';
        const apiUrl = (typeof devToolsConfig !== 'undefined') ? devToolsConfig.ajaxurl : '';
        
        const requestBody = {
            action: 'dev_tools_run_tests',
            nonce: nonce,
            test_types: testTypes,
            verbose: verbose,
            coverage: coverage
        };
        
        console.log(`📤 ENVIANDO:`, requestBody);
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestBody)
        });
        
        const responseText = await response.text();
        let result;
        
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Error parseando respuesta:', responseText);
            return;
        }
        
        if (result.success) {
            console.log(`✅ RESPUESTA EXITOSA:`);
            console.log(`  Comando: ${result.data.command}`);
            console.log(`  Exit Code: ${result.data.return_code}`);
            console.log(`  Tiempo: ${result.data.execution_time}ms`);
            
            // Verificar que las opciones están en el comando
            const hasVerbose = result.data.command.includes('--verbose');
            const hasCoverage = result.data.command.includes('--coverage-text');
            
            console.log(`\n🔍 VERIFICACIÓN DE OPCIONES:`);
            console.log(`  --verbose esperado: ${verbose}, encontrado: ${hasVerbose} ${verbose === hasVerbose ? '✅' : '❌'}`);
            console.log(`  --coverage-text esperado: ${coverage}, encontrado: ${hasCoverage} ${coverage === hasCoverage ? '✅' : '❌'}`);
            
            // Verificar path de tests
            testTypes.forEach(type => {
                const hasPath = result.data.command.includes(`tests/${type}/`) || result.data.command.includes('tests/');
                console.log(`  tests/${type}/ path: ${hasPath ? '✅' : '⚠️'}`);
            });
            
        } else {
            console.log(`❌ ERROR EN RESPUESTA:`, result);
        }
        
    } catch (error) {
        console.error(`❌ Error en testCommandGeneration:`, error);
    }
}

// Casos de prueba predefinidos
const testCases = [
    {
        name: 'Solo Unit - Sin opciones',
        testTypes: ['unit'],
        verbose: false,
        coverage: false
    },
    {
        name: 'Solo Unit - Con verbose',
        testTypes: ['unit'],
        verbose: true,
        coverage: false
    },
    {
        name: 'Solo Unit - Con coverage',
        testTypes: ['unit'],
        verbose: false,
        coverage: true
    },
    {
        name: 'Solo Unit - Ambas opciones',
        testTypes: ['unit'],
        verbose: true,
        coverage: true
    },
    {
        name: 'Integration - Ambas opciones',
        testTypes: ['integration'],
        verbose: true,
        coverage: true
    },
    {
        name: 'Database - Solo verbose',
        testTypes: ['database'],
        verbose: true,
        coverage: false
    },
    {
        name: 'Múltiples - Todas las opciones',
        testTypes: ['unit', 'integration', 'database'],
        verbose: true,
        coverage: true
    }
];

// Función para ejecutar todos los casos de prueba
async function runAllTestCases() {
    console.log('🚀 EJECUTANDO TODOS LOS CASOS DE PRUEBA...\n');
    
    for (const [index, testCase] of testCases.entries()) {
        console.log(`\n📋 CASO ${index + 1}: ${testCase.name}`);
        console.log('─'.repeat(40));
        
        await testCommandGeneration(testCase.testTypes, testCase.verbose, testCase.coverage);
        
        // Pausa entre tests
        await new Promise(resolve => setTimeout(resolve, 1000));
    }
    
    console.log('\n🎉 VERIFICACIÓN COMPLETA FINALIZADA');
}

// Función para probar un caso específico
function testSpecific(testTypes = ['unit'], verbose = false, coverage = false) {
    testCommandGeneration(testTypes, verbose, coverage);
}

// Mostrar instrucciones
console.log('📝 FUNCIONES DISPONIBLES:');
console.log('1. testSpecific(["unit"], true, false) - Probar configuración específica');
console.log('2. runAllTestCases() - Ejecutar todos los casos de prueba');
console.log('3. testCommandGeneration(["unit", "integration"], true, true) - Función directa');

console.log('\n💡 EJEMPLOS DE USO:');
console.log('• testSpecific(["unit"], true, true) - Unit test con verbose y coverage');
console.log('• testSpecific(["integration"], true, false) - Integration test solo con verbose');
console.log('• testSpecific(["unit", "integration"], false, true) - Múltiples tests solo con coverage');

console.log('\n⚡ EJECUTANDO CASO BÁSICO DE DEMOSTRACIÓN...');
setTimeout(() => {
    testSpecific(['unit'], true, true);
}, 1000);
