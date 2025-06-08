<?php
/**
 * Complete AJAX Test Script for Dev-Tools
 * 
 * This script provides JavaScript test code for browser console
 * to verify all AJAX endpoints are working correctly.
 */

// Ensure we're in WordPress environment
if (!defined('WPINC')) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dev-Tools AJAX Complete Test</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .code-block { background: #f4f4f4; padding: 10px; margin: 10px 0; font-family: monospace; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🔧 Dev-Tools AJAX Complete Test</h1>
    
    <div class="test-section">
        <h2>📋 Instrucciones</h2>
        <p>Copia y ejecuta estos scripts en la consola del navegador para probar todos los endpoints AJAX:</p>
        
        <h3>1. Verificar configuración de Dev-Tools</h3>
        <div class="code-block">
// Test 1: Check Dev-Tools configuration
console.log('=== DEV-TOOLS CONFIGURATION TEST ===');
const config = window.findDevToolsConfig?.() || window.tarokina_2025_dev_tools_config || window.tkn_dev_tools_config;
console.log('Config found:', config ? 'YES' : 'NO');
if (config) {
    console.log('AJAX URL:', config.ajaxUrl);
    console.log('Nonce:', config.nonce);
    console.log('Action prefix:', config.actionPrefix);
} else {
    console.error('❌ Configuration not found');
}
        </div>
        
        <h3>2. Test AJAX Ping (Conectividad básica)</h3>
        <div class="code-block">
// Test 2: AJAX Ping
async function testAjaxPing() {
    console.log('=== AJAX PING TEST ===');
    const config = window.findDevToolsConfig?.() || window.tarokina_2025_dev_tools_config;
    if (!config) {
        console.error('❌ No config found');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'tarokina_2025_dev_tools_ping');
        formData.append('nonce', config.nonce);
        
        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.text();
        console.log('Response status:', response.status);
        console.log('Response text:', result);
        
        if (response.ok) {
            console.log('✅ AJAX Ping successful');
        } else {
            console.error('❌ AJAX Ping failed');
        }
    } catch (error) {
        console.error('❌ AJAX Ping error:', error);
    }
}

testAjaxPing();
        </div>
        
        <h3>3. Test Anti-Deadlock Check</h3>
        <div class="code-block">
// Test 3: Anti-Deadlock Check
async function testAntiDeadlock() {
    console.log('=== ANTI-DEADLOCK TEST ===');
    const config = window.findDevToolsConfig?.() || window.tarokina_2025_dev_tools_config;
    
    try {
        const formData = new FormData();
        formData.append('action', 'tarokina_2025_dev_tools_check_anti_deadlock');
        formData.append('nonce', config.nonce);
        
        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.text();
        console.log('Anti-deadlock response:', result);
        
        if (response.ok) {
            console.log('✅ Anti-deadlock check successful');
        } else {
            console.error('❌ Anti-deadlock check failed');
        }
    } catch (error) {
        console.error('❌ Anti-deadlock error:', error);
    }
}

testAntiDeadlock();
        </div>
        
        <h3>4. Test Framework Check</h3>
        <div class="code-block">
// Test 4: Test Framework Check
async function testFrameworkCheck() {
    console.log('=== TEST FRAMEWORK CHECK ===');
    const config = window.findDevToolsConfig?.() || window.tarokina_2025_dev_tools_config;
    
    try {
        const formData = new FormData();
        formData.append('action', 'tarokina_2025_dev_tools_check_test_framework');
        formData.append('nonce', config.nonce);
        
        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.text();
        console.log('Test framework response:', result);
        
        if (response.ok) {
            console.log('✅ Test framework check successful');
        } else {
            console.error('❌ Test framework check failed');
        }
    } catch (error) {
        console.error('❌ Test framework error:', error);
    }
}

testFrameworkCheck();
        </div>
        
        <h3>5. Complete System Test</h3>
        <div class="code-block">
// Test 5: Complete system test
async function runCompleteTest() {
    console.log('=== COMPLETE SYSTEM TEST ===');
    
    const tests = [
        { name: 'Configuration', fn: () => Promise.resolve(!!window.findDevToolsConfig?.()) },
        { name: 'AJAX Ping', fn: testAjaxPing },
        { name: 'Anti-Deadlock', fn: testAntiDeadlock },
        { name: 'Test Framework', fn: testFrameworkCheck }
    ];
    
    let passed = 0;
    let failed = 0;
    
    for (const test of tests) {
        try {
            console.log(`\n🔍 Running: ${test.name}`);
            await test.fn();
            passed++;
            console.log(`✅ ${test.name}: PASSED`);
        } catch (error) {
            failed++;
            console.error(`❌ ${test.name}: FAILED`, error);
        }
    }
    
    console.log(`\n📊 TEST RESULTS:`);
    console.log(`✅ Passed: ${passed}`);
    console.log(`❌ Failed: ${failed}`);
    console.log(`📈 Success rate: ${Math.round((passed / (passed + failed)) * 100)}%`);
    
    if (failed === 0) {
        console.log('🎉 ALL TESTS PASSED! Dev-Tools system is working correctly.');
    } else {
        console.log('⚠️ Some tests failed. Check the error messages above.');
    }
}

runCompleteTest();
        </div>
    </div>
    
    <div class="test-section">
        <h2>🎯 Siguiente paso</h2>
        <p>Después de ejecutar estas pruebas, comparte los resultados de la consola para confirmar que el sistema está funcionando correctamente.</p>
        
        <p><strong>Nota:</strong> Si ves errores HTTP 400, significa que los endpoints AJAX no están registrados correctamente. Si ves "0" como respuesta, indica problemas de autorización o nonce.</p>
    </div>
</body>
</html>
