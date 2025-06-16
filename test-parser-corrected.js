// Script CORREGIDO para probar el parser de PHPUnit con los selectores correctos
console.log('🔧 Probando parser de tests unitarios CORREGIDO...');

// Verificar que estamos en la pestaña correcta
const testRunnerTab = document.querySelector('button[data-bs-target="#test-runner"]');
if (testRunnerTab && !testRunnerTab.classList.contains('active')) {
    console.log('📑 Cambiando a pestaña Test Runner...');
    testRunnerTab.click();
    setTimeout(() => {
        ejecutarTest();
    }, 500);
} else {
    ejecutarTest();
}

function ejecutarTest() {
    // Activar checkbox de Unit Tests
    const unitTestsCheckbox = document.querySelector('#unitTests');
    if (unitTestsCheckbox) {
        unitTestsCheckbox.checked = true;
        console.log('✅ Checkbox Unit Tests activado');
    } else {
        console.log('❌ No se encontró checkbox Unit Tests');
        return;
    }

    // Ejecutar tests
    const runButton = document.querySelector('#runTest');
    if (runButton) {
        console.log('▶️ Ejecutando tests unitarios...');
        runButton.click();
        
        // Monitorear resultados en el contenedor correcto
        let checkCount = 0;
        const maxChecks = 30;
        
        const checkResults = setInterval(() => {
            checkCount++;
            
            // Buscar en el contenedor correcto: #testResults
            const testResults = document.querySelector('#testResults');
            
            if (testResults && testResults.innerHTML.trim() !== '' && !testResults.innerHTML.includes('🔄')) {
                console.log('📊 ¡RESULTADOS ENCONTRADOS!');
                
                // Buscar el resumen específicamente
                const summaryDiv = testResults.querySelector('.alert h5');
                if (summaryDiv && summaryDiv.textContent.includes('Resumen de Tests')) {
                    console.log('✅ ÉXITO: Resumen de tests mostrado correctamente');
                    
                    const summaryAlert = summaryDiv.closest('.alert');
                    console.log('📋 Contenido del resumen:');
                    console.log(summaryAlert.textContent);
                    
                    // Extraer valores específicos
                    const totalMatch = summaryAlert.textContent.match(/Total:\s*(\d+)/);
                    const pasadosMatch = summaryAlert.textContent.match(/Pasados:\s*(\d+)/);
                    const fallidosMatch = summaryAlert.textContent.match(/Fallidos:\s*(\d+)/);
                    
                    if (totalMatch && parseInt(totalMatch[1]) > 0) {
                        console.log('✅ CORRECTO: Total tests > 0 (' + totalMatch[1] + ')');
                    } else {
                        console.log('❌ PROBLEMA: Total tests = 0');
                    }
                    
                    if (pasadosMatch && parseInt(pasadosMatch[1]) > 0) {
                        console.log('✅ CORRECTO: Tests pasados > 0 (' + pasadosMatch[1] + ')');
                    } else {
                        console.log('❌ PROBLEMA: Tests pasados = 0');
                    }
                    
                } else {
                    console.log('⚠️ Resultados encontrados pero sin resumen visible');
                    console.log('📄 Contenido de testResults:');
                    console.log(testResults.innerHTML);
                }
                
                clearInterval(checkResults);
                
            } else if (testResults && testResults.innerHTML.includes('🔄')) {
                console.log(`⏳ Tests ejecutándose... (${checkCount}/${maxChecks})`);
            } else if (checkCount >= maxChecks) {
                console.log('⏱️ Timeout - No se encontraron resultados');
                console.log('🔍 Estado actual de #testResults:');
                console.log(testResults ? testResults.innerHTML : 'Elemento no encontrado');
                clearInterval(checkResults);
            } else {
                console.log(`⏳ Esperando resultados... (${checkCount}/${maxChecks})`);
            }
        }, 1000);
        
    } else {
        console.log('❌ No se encontró botón de ejecutar tests');
    }
}
