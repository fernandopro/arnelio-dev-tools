// 🌙 SCRIPT DE TEST COMPLETO PARA TEMA OSCURO DEV-TOOLS
// Copiar y pegar en la consola del navegador en la página de Dev-Tools
// Este script verifica que el tema oscuro esté aplicado correctamente

console.log('🌙 Iniciando test completo del tema oscuro Dev-Tools...');
console.log('🕐 Timestamp:', new Date().toLocaleString());

// 1. Test de estructura HTML (verificar que NO haya duplicación)
console.group('🏗️  Test 1: Estructura HTML');
const htmlCount = document.querySelectorAll('html').length;
const bodyCount = document.querySelectorAll('body').length;
const headCount = document.querySelectorAll('head').length;

console.log('Elementos HTML:', htmlCount === 1 ? '✅ OK (1)' : `❌ ERROR (${htmlCount})`);
console.log('Elementos BODY:', bodyCount === 1 ? '✅ OK (1)' : `❌ ERROR (${bodyCount})`);
console.log('Elementos HEAD:', headCount === 1 ? '✅ OK (1)' : `❌ ERROR (${headCount})`);
console.groupEnd();

// 2. Test de tema oscuro aplicado
console.group('🎨 Test 2: Tema Oscuro Aplicado');
const wpElements = {
    'html': document.documentElement,
    'body': document.body,
    '#wpwrap': document.querySelector('#wpwrap'),
    '#wpcontent': document.querySelector('#wpcontent'),
    '#wpbody': document.querySelector('#wpbody'),
    '#wpbody-content': document.querySelector('#wpbody-content'),
    '.wrap': document.querySelector('.wrap')
};

Object.entries(wpElements).forEach(([name, element]) => {
    if (element) {
        const bgColor = getComputedStyle(element).backgroundColor;
        const isDark = bgColor.includes('26, 29, 35') || bgColor.includes('#1a1d23');
        console.log(`${name}: ${isDark ? '✅ OSCURO' : '❌ CLARO'} (${bgColor})`);
    } else {
        console.log(`${name}: ⚠️  ELEMENTO NO ENCONTRADO`);
    }
});
console.groupEnd();

// 3. Test de clases aplicadas
console.group('🏷️  Test 3: Clases CSS');
const hasDevToolsTheme = document.documentElement.classList.contains('dev-tools-dark-theme');
const hasDevToolsActive = document.body.classList.contains('dev-tools-active');

console.log('Clase dev-tools-dark-theme:', hasDevToolsTheme ? '✅ APLICADA' : '❌ FALTANTE');
console.log('Clase dev-tools-active:', hasDevToolsActive ? '✅ APLICADA' : '❌ FALTANTE');

// Verificar indicador visual
const indicator = window.getComputedStyle(document.body, '::before').content;
console.log('Indicador visual:', indicator.includes('Dev-Tools') ? '✅ VISIBLE' : '❌ NO VISIBLE');
console.groupEnd();

// 4. Test de assets cargados
console.group('📦 Test 4: Assets CSS/JS');
const stylesheets = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
    .filter(link => link.href.includes('dev-tools'));
const scripts = Array.from(document.querySelectorAll('script[src]'))
    .filter(script => script.src.includes('dev-tools'));

console.log('CSS Dev-Tools cargados:', stylesheets.length);
stylesheets.forEach((sheet, index) => {
    const filename = sheet.href.split('/').pop();
    console.log(`  ${index + 1}. ${filename}`);
});

console.log('JS Dev-Tools cargados:', scripts.length);
scripts.forEach((script, index) => {
    const filename = script.src.split('/').pop();
    console.log(`  ${index + 1}. ${filename}`);
});
console.groupEnd();

// 5. Test del Dashboard específico
console.group('📊 Test 5: Dashboard Module');
const dashboardContainer = document.querySelector('.dev-tools-dashboard');
if (dashboardContainer) {
    console.log('Container dashboard: ✅ ENCONTRADO');
    
    const darkCards = document.querySelectorAll('.dev-tools-dashboard .card.bg-dark');
    console.log(`Cards oscuras: ${darkCards.length} encontradas`);
    
    const lightTexts = document.querySelectorAll('.dev-tools-dashboard .text-light');
    console.log(`Textos claros: ${lightTexts.length} encontrados`);
    
    const bootstrapIcons = document.querySelectorAll('.dev-tools-dashboard .bi');
    console.log(`Iconos Bootstrap: ${bootstrapIcons.length} encontrados`);
    
    const actionButtons = document.querySelectorAll('.dev-tools-dashboard .btn');
    console.log(`Botones de acción: ${actionButtons.length} encontrados`);
} else {
    console.log('Container dashboard: ❌ NO ENCONTRADO');
}
console.groupEnd();

// 6. Test de configuración JavaScript
console.group('⚙️  Test 6: Configuración JS');
console.log('jQuery disponible:', typeof jQuery !== 'undefined' ? '✅ SI' : '❌ NO');
console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined' ? '✅ SI' : '❌ NO');
console.log('devToolsConfig disponible:', typeof devToolsConfig !== 'undefined' ? '✅ SI' : '❌ NO');

if (typeof devToolsConfig !== 'undefined') {
    console.log('AJAX URL:', devToolsConfig.ajaxUrl);
    console.log('Action Prefix:', devToolsConfig.actionPrefix);
    console.log('Version:', devToolsConfig.version);
    console.log('Debug mode:', devToolsConfig.debug);
}
console.groupEnd();

// 7. Test de compatibilidad con WordPress Admin
console.group('🔧 Test 7: Compatibilidad WordPress Admin');
const adminBar = document.querySelector('#wpadminbar');
const adminMenu = document.querySelector('#adminmenu');
const adminContent = document.querySelector('#wpbody-content');

console.log('Admin bar:', adminBar ? '✅ PRESENTE' : '❌ AUSENTE');
console.log('Admin menu:', adminMenu ? '✅ PRESENTE' : '❌ AUSENTE');
console.log('Admin content:', adminContent ? '✅ PRESENTE' : '❌ AUSENTE');

// Verificar que no interfiera con el admin de WordPress
const wpNotices = document.querySelectorAll('.notice, .update-nag');
console.log(`Notificaciones WP: ${wpNotices.length} encontradas`);
console.groupEnd();

// 8. Resumen final
console.group('📋 Test 8: Resumen Final');
const tests = [
    htmlCount === 1 && bodyCount === 1 && headCount === 1,
    hasDevToolsTheme && hasDevToolsActive,
    stylesheets.length > 0,
    dashboardContainer !== null,
    typeof devToolsConfig !== 'undefined'
];

const passedTests = tests.filter(Boolean).length;
const totalTests = tests.length;

console.log(`Tests pasados: ${passedTests}/${totalTests}`);
console.log(`Porcentaje éxito: ${Math.round((passedTests/totalTests) * 100)}%`);

if (passedTests === totalTests) {
    console.log('🎉 TODOS LOS TESTS PASARON - Tema oscuro funcionando correctamente');
} else {
    console.log('⚠️  Algunos tests fallaron - Revisar implementación');
}
console.groupEnd();

// 9. Información adicional para debugging
console.group('🔍 Info Adicional (Debugging)');
console.log('URL actual:', window.location.href);
console.log('User Agent:', navigator.userAgent.split(' ').slice(-2).join(' '));
console.log('Viewport:', `${window.innerWidth}x${window.innerHeight}`);
console.log('Elementos totales DOM:', document.querySelectorAll('*').length);
console.groupEnd();

console.log('✅ Test completo finalizado.');
console.log('📝 Expandir cada grupo para ver detalles específicos.');
console.log('🚀 Si todos los tests pasan, el tema oscuro está funcionando correctamente.');
