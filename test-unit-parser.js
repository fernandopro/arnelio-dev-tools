// Script para probar el parser mejorado de PHPUnit en el panel dev-tools
console.log('🔧 Probando parser de tests unitarios en panel...');

// Verificar que estamos en la pestaña correcta
const testRunnerTab = document.querySelector('button[data-bs-target="#test-runner"]');
if (testRunnerTab && !testRunnerTab.classList.contains('active')) {
    console.log('📑 Cambiando a pestaña Test Runner...');
    testRunnerTab.click();
    // Esperar un momento para que se cargue la pestaña
    setTimeout(() => {
        ejecutarTest();
    }, 500);
} else {
    ejecutarTest();
}

function ejecutarTest() {
    // Simular click en checkbox de Unit Tests usando ID correcto
    const unitTestsCheckbox = document.querySelector('#unitTests');
    if (unitTestsCheckbox) {
        unitTestsCheckbox.checked = true;
        console.log('✅ Checkbox Unit Tests activado');
    } else {
        console.log('❌ No se encontró checkbox Unit Tests (#unitTests)');
        console.log('🔍 Checkboxes disponibles:');
        document.querySelectorAll('input[type="checkbox"]').forEach((cb, i) => {
            console.log(`  ${i}: ${cb.id || cb.name || 'sin-id'} - ${cb.checked ? 'checked' : 'unchecked'}`);
        });
    }

    // Ejecutar tests usando ID correcto
    const runButton = document.querySelector('#runTest');
    if (runButton) {
        console.log('▶️ Ejecutando tests unitarios...');
        runButton.click();
        
        // Monitorear resultados
        let checkCount = 0;
        const maxChecks = 40; // 40 segundos
        
        const checkResults = setInterval(() => {
            checkCount++;
            const summary = document.querySelector('#testSummary');
            const output = document.querySelector('#testOutput');
            
            if (summary && summary.innerHTML.trim() !== '') {
                console.log('📊 Resumen encontrado:');
                console.log(summary.innerHTML);
                
                // Verificar valores específicos
                const totalText = summary.textContent.toLowerCase();
                if (totalText.includes('total: 0') || totalText.includes('pasados: 0')) {
                    console.log('⚠️ PROBLEMA: El resumen muestra valores en cero');
                    console.log('📋 Contenido completo del resumen:');
                    console.log(summary.outerHTML);
                    
                    // También mostrar el output completo para debugging
                    if (output) {
                        console.log('📄 Output completo de PHPUnit:');
                        console.log(output.textContent);
                    }
                } else {
                    console.log('✅ CORRECTO: El resumen muestra valores > 0');
                }
                
                clearInterval(checkResults);
            } else if (output && output.textContent.trim() !== '') {
                console.log(`⏳ Tests ejecutándose... salida actual: ${output.textContent.length} caracteres (${checkCount}/${maxChecks})`);
            } else if (checkCount >= maxChecks) {
                console.log('⏱️ Timeout - No se encontraron resultados después de 40 segundos');
                clearInterval(checkResults);
            } else {
                console.log(`⏳ Esperando resultados... (${checkCount}/${maxChecks})`);
            }
        }, 1000);
    } else {
        console.log('❌ No se encontró botón de ejecutar tests (#runTest)');
        console.log('🔍 Botones disponibles:');
        document.querySelectorAll('button').forEach((btn, i) => {
            console.log(`  ${i}: ${btn.id || btn.className || 'sin-id'} - "${btn.textContent.trim().substring(0, 50)}"`);
        });
    }
}
