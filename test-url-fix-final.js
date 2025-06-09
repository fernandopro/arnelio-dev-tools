// Test final de verificación de URLs corregidas
// Copia y pega este código completo en la consola del navegador

(async function testUrlsFinal() {
    console.log('%c🔧 VERIFICACIÓN FINAL - URLs Corregidas', 'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 12px 20px; border-radius: 8px; font-weight: bold; font-size: 16px;');
    
    const expectedBase = 'http://localhost:10019/wp-content/plugins/tarokina-2025/dev-tools/dist/js/';
    
    const filesToTest = [
        'dev-tools.min.js',
        'dashboard.min.js', 
        'system-info.min.js',
        'cache.min.js',
        'ajax-tester.min.js',
        'logs.min.js',
        'performance.min.js',
        'dev-utils.min.js'
    ];
    
    console.log('📊 Verificando archivos compilados existentes...\n');
    
    let allCorrect = true;
    let results = [];
    
    for (const file of filesToTest) {
        const url = expectedBase + file;
        
        try {
            const response = await fetch(url, { method: 'HEAD' });
            
            if (response.ok) {
                console.log(`✅ ${file} - OK`);
                results.push({ file, status: 'OK', url });
            } else {
                console.log(`❌ ${file} - Error ${response.status}`);
                results.push({ file, status: `Error ${response.status}`, url });
                allCorrect = false;
            }
        } catch (error) {
            console.log(`❌ ${file} - Network Error`);
            results.push({ file, status: 'Network Error', url });
            allCorrect = false;
        }
    }
    
    console.log('\n📋 Resumen de resultados:');
    console.table(results);
    
    if (allCorrect) {
        console.log('%c🎉 ¡TODAS LAS URLs ESTÁN CORRECTAS!', 'background: linear-gradient(135deg, #059669, #047857); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 14px;');
        console.log('✨ El problema de URLs 404 ha sido completamente solucionado');
    } else {
        console.log('%c⚠️ Algunas URLs siguen con problemas', 'background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 14px;');
    }
    
    // Test adicional: Verificar la configuración de dev-tools
    console.log('\n🔧 Verificando configuración de dev-tools...');
    
    if (typeof tkn_dev_tools_config !== 'undefined') {
        console.log('✅ Configuración tkn_dev_tools_config disponible');
        console.log('🔗 AJAX URL:', tkn_dev_tools_config.ajaxUrl);
        console.log('🔑 Nonce disponible:', !!tkn_dev_tools_config.nonce);
    } else {
        console.log('⚠️ Configuración tkn_dev_tools_config no disponible');
    }
    
    if (typeof devToolsController !== 'undefined') {
        console.log('✅ devToolsController disponible');
        console.log('🎯 Estado inicializado:', devToolsController.isInitialized);
    } else {
        console.log('⚠️ devToolsController no disponible');
    }
    
    console.log('\n🏁 Verificación final completada');
})();
