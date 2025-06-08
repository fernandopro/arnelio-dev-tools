// 🚀 Test del Nuevo Panel Arquitectura 3.0 - Dark Theme
// Ejecutar en la consola del navegador

console.log('%c🌟 Dev-Tools Arquitectura 3.0 - Test del Nuevo Panel', 'color: #0d6efd; font-size: 18px; font-weight: bold;');
console.log('=' .repeat(60));

// 1. Verificar que estamos en el panel correcto
const panel = document.getElementById('dev-tools-panel');
if (panel && panel.classList.contains('dev-tools-panel-v3')) {
    console.log('✅ Panel Arquitectura 3.0 detectado correctamente');
} else {
    console.log('❌ Panel v3 no encontrado');
}

// 2. Verificar tema oscuro
const html = document.documentElement;
if (html.getAttribute('data-bs-theme') === 'dark') {
    console.log('✅ Tema oscuro activado');
} else {
    console.log('⚠️ Tema oscuro no detectado');
}

// 3. Verificar navegación principal
const navContainer = document.querySelector('.nav-container');
const navItems = document.querySelectorAll('.nav-item');

console.log('📋 Navegación Principal:');
console.log(`  - Contenedor encontrado: ${navContainer ? '✅' : '❌'}`);
console.log(`  - Items de navegación: ${navItems.length}`);

navItems.forEach((item, index) => {
    const title = item.querySelector('.nav-title')?.textContent;
    const icon = item.querySelector('i')?.className;
    const status = item.querySelector('.nav-status i')?.className;
    const isActive = item.classList.contains('active');
    const isDisabled = item.classList.contains('disabled');
    
    console.log(`    ${index + 1}. ${title}:`);
    console.log(`       - Icono: ${icon}`);
    console.log(`       - Estado: ${status}`);
    console.log(`       - Activo: ${isActive ? '✅' : '⭕'}`);
    console.log(`       - Deshabilitado: ${isDisabled ? '⚠️' : '⭕'}`);
});

// 4. Verificar estilos CSS personalizados
const styleElement = document.querySelector('style');
if (styleElement && styleElement.textContent.includes('--dev-tools-bg-dark')) {
    console.log('✅ Estilos CSS personalizados cargados');
} else {
    console.log('⚠️ Estilos CSS personalizados no encontrados');
}

// 5. Verificar header
const header = document.querySelector('.dev-tools-header');
const headerBrand = document.querySelector('.header-brand');
const headerInfo = document.querySelector('.header-info');

console.log('🎨 Header del Panel:');
console.log(`  - Header principal: ${header ? '✅' : '❌'}`);
console.log(`  - Brand/Logo: ${headerBrand ? '✅' : '❌'}`);
console.log(`  - Info de estado: ${headerInfo ? '✅' : '❌'}`);

// 6. Verificar contenido principal
const main = document.querySelector('.dev-tools-main');
const alerts = document.querySelectorAll('.alert');
const cards = document.querySelectorAll('.card');

console.log('📄 Contenido Principal:');
console.log(`  - Main container: ${main ? '✅' : '❌'}`);
console.log(`  - Alertas: ${alerts.length}`);
console.log(`  - Cards: ${cards.length}`);

// 7. Verificar footer
const footer = document.querySelector('.dev-tools-footer');
console.log(`📊 Footer: ${footer ? '✅' : '❌'}`);

// 8. Test de interactividad
console.log('🖱️ Testing Interactividad:');

// Test hover en navegación
navItems.forEach((item, index) => {
    if (!item.classList.contains('disabled')) {
        console.log(`  - Item ${index + 1} (${item.querySelector('.nav-title')?.textContent}): Clickeable`);
    }
});

// 9. Verificar JavaScript embebido
console.log('📱 JavaScript del Panel:');
if (typeof testSystemStatus === 'function') {
    console.log('  - testSystemStatus(): ✅ Disponible');
} else {
    console.log('  - testSystemStatus(): ❌ No encontrada');
}

if (typeof refreshPage === 'function') {
    console.log('  - refreshPage(): ✅ Disponible');
} else {
    console.log('  - refreshPage(): ❌ No encontrada');
}

// 10. Test de Bootstrap y configuración
console.log('⚙️ Configuración y Librerías:');
console.log(`  - Bootstrap disponible: ${typeof bootstrap !== 'undefined' ? '✅' : '❌'}`);
console.log(`  - devToolsConfig disponible: ${typeof devToolsConfig !== 'undefined' ? '✅' : '❌'}`);

if (typeof devToolsConfig !== 'undefined') {
    console.log('  - Config data:', devToolsConfig);
}

// 11. Verificar responsive design
const isMobile = window.innerWidth <= 768;
console.log(`📱 Responsive: ${isMobile ? 'Vista móvil' : 'Vista desktop'}`);

// 12. Test de accesibilidad básica
const focusableElements = document.querySelectorAll('a, button, [tabindex]');
console.log(`♿ Elementos focuseables: ${focusableElements.length}`);

// 13. Test de colores del tema oscuro
const computedStyle = getComputedStyle(document.documentElement);
const bgDark = computedStyle.getPropertyValue('--dev-tools-bg-dark');
const textPrimary = computedStyle.getPropertyValue('--dev-tools-text-primary');

console.log('🎨 Variables CSS del tema:');
console.log(`  - Background dark: ${bgDark || 'No definido'}`);
console.log(`  - Text primary: ${textPrimary || 'No definido'}`);

// 14. Resumen final
console.log('=' .repeat(60));
console.log('%c📊 RESUMEN DEL TEST', 'color: #198754; font-size: 16px; font-weight: bold;');

const results = {
    panel_v3: panel && panel.classList.contains('dev-tools-panel-v3'),
    dark_theme: html.getAttribute('data-bs-theme') === 'dark',
    navigation: navContainer && navItems.length > 0,
    custom_styles: styleElement && styleElement.textContent.includes('--dev-tools-bg-dark'),
    header: header && headerBrand && headerInfo,
    main_content: main !== null,
    footer: footer !== null,
    javascript_functions: typeof testSystemStatus === 'function' && typeof refreshPage === 'function',
    bootstrap: typeof bootstrap !== 'undefined',
    config: typeof devToolsConfig !== 'undefined'
};

const passed = Object.values(results).filter(Boolean).length;
const total = Object.keys(results).length;

console.log(`✅ Tests pasados: ${passed}/${total}`);
console.log(`📈 Porcentaje de éxito: ${Math.round((passed/total) * 100)}%`);

if (passed === total) {
    console.log('%c🎉 ¡TODOS LOS TESTS PASARON! Panel funcionando perfectamente.', 'color: #198754; font-weight: bold;');
} else {
    console.log('%c⚠️ Algunos tests fallaron. Revisar los detalles arriba.', 'color: #ffc107; font-weight: bold;');
}

console.log('=' .repeat(60));

// 15. Función para probar navegación
window.testNavigation = function() {
    console.log('🧭 Testing navegación...');
    
    navItems.forEach((item, index) => {
        const title = item.querySelector('.nav-title')?.textContent;
        const href = item.getAttribute('href');
        const isDisabled = item.classList.contains('disabled');
        
        console.log(`${index + 1}. ${title}:`);
        console.log(`   URL: ${href}`);
        console.log(`   Estado: ${isDisabled ? 'Deshabilitado' : 'Disponible'}`);
        
        if (!isDisabled && href) {
            console.log(`   🔗 Puedes hacer clic o navegar a: ${href}`);
        }
    });
};

console.log('💡 Tip: Ejecuta testNavigation() para probar los enlaces de navegación');
console.log('💡 Tip: Ejecuta testSystemStatus() para probar el sistema (si está disponible)');
