/**
 * Test de verificación después de optimización de carga de módulos
 * Para ejecutar en consola del navegador en la página de dev-tools
 */

console.log('🚀 Test de verificación - Carga optimizada de módulos');

// Verificar que DashboardModule funciona
if (typeof DevToolsDashboard !== 'undefined') {
    console.log('✅ DashboardModule JavaScript cargado correctamente');
    
    // Test funcionalidad básica
    try {
        const dashboard = new DevToolsDashboard();
        console.log('✅ Dashboard inicializado correctamente');
    } catch (error) {
        console.error('❌ Error al inicializar dashboard:', error);
    }
} else {
    console.log('ℹ️ DevToolsDashboard no disponible (normal si no estás en página dashboard)');
}

// Verificar assets compilados
const cssAssets = document.querySelectorAll('link[href*="dev-tools"]');
const jsAssets = document.querySelectorAll('script[src*="dev-tools"]');

console.log(`✅ CSS assets encontrados: ${cssAssets.length}`);
console.log(`⚡ JS assets encontrados: ${jsAssets.length} (optimizado)`);

// Analizar assets CSS
cssAssets.forEach((asset, i) => {
    const filename = asset.href.split('/').pop().split('?')[0];
    console.log(`  CSS ${i+1}: ${filename}`);
});

// Analizar assets JavaScript y detectar módulos innecesarios
const expectedModules = ['dev-tools.min.js', 'dev-utils.min.js', 'dashboard.min.js'];
const unnecessaryModules = ['system-info.min.js', 'cache.min.js', 'ajax-tester.min.js', 'logs.min.js', 'performance.min.js'];

jsAssets.forEach((asset, i) => {
    const filename = asset.src.split('/').pop().split('?')[0];
    const isExpected = expectedModules.some(expected => filename.includes(expected.replace('.min.js', '')));
    const isUnnecessary = unnecessaryModules.some(unnecessary => filename.includes(unnecessary.replace('.min.js', '')));
    
    if (isExpected) {
        console.log(`  ✅ JS ${i+1}: ${filename} (necesario)`);
    } else if (isUnnecessary) {
        console.log(`  ⚠️ JS ${i+1}: ${filename} (NO debería estar cargado)`);
    } else {
        console.log(`  ℹ️ JS ${i+1}: ${filename} (otro asset)`);
    }
});

// Contar módulos innecesarios cargados
const unnecessaryCount = Array.from(jsAssets).filter(asset => {
    const filename = asset.src.split('/').pop().split('?')[0];
    return unnecessaryModules.some(unnecessary => filename.includes(unnecessary.replace('.min.js', '')));
}).length;

if (unnecessaryCount === 0) {
    console.log('🎉 ¡PERFECTO! Solo se cargaron los módulos necesarios');
} else {
    console.log(`⚠️ ADVERTENCIA: Se cargaron ${unnecessaryCount} módulos innecesarios`);
}

// Test AJAX endpoints
if (typeof ajaxurl !== 'undefined') {
    console.log('✅ WordPress AJAX disponible');
    console.log('ℹ️ AJAX URL:', ajaxurl);
} else {
    console.log('ℹ️ WordPress AJAX no disponible (normal fuera del admin)');
}

// Verificar variables globales de página (si están disponibles en el frontend)
if (typeof window.location !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 'no detectado';
    const currentTab = urlParams.get('tab') || 'dashboard (por defecto)';
    
    console.log(`📍 Página detectada: ${currentPage}`);
    console.log(`📋 Tab detectado: ${currentTab}`);
}

console.log('🎯 Verificación de optimización completada');
