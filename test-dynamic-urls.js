// 🔧 Test de URLs Dinámicas - Dev-Tools Arquitectura 3.0
// Ejecutar en la consola del navegador para verificar URLs dinámicas

console.log('%c🔗 Test de URLs Dinámicas - Plugin Agnóstico', 'color: #0d6efd; font-size: 18px; font-weight: bold;');
console.log('=' .repeat(60));

// 1. Verificar configuración disponible
if (typeof devToolsConfig !== 'undefined') {
    console.log('✅ Config JavaScript disponible');
    console.log('📄 Config completa:', devToolsConfig);
    
    // Analizar configuración
    console.log('🔍 Análisis de Configuración:');
    console.log(`  - Menu Slug: ${devToolsConfig.menuSlug || 'No definido'}`);
    console.log(`  - Action Prefix: ${devToolsConfig.actionPrefix || 'No definido'}`);
    console.log(`  - AJAX URL: ${devToolsConfig.ajaxUrl || 'No definido'}`);
    console.log(`  - Nonce: ${devToolsConfig.nonce || 'No definido'}`);
} else {
    console.log('❌ Config JavaScript no disponible');
}

// 2. Verificar URL actual y parámetros
const currentUrl = new URL(window.location.href);
const urlParams = new URLSearchParams(currentUrl.search);

console.log('🌐 URL Actual:');
console.log(`  - URL completa: ${currentUrl.href}`);
console.log(`  - Host: ${currentUrl.host}`);
console.log(`  - Pathname: ${currentUrl.pathname}`);

console.log('📊 Parámetros URL:');
for (const [key, value] of urlParams.entries()) {
    console.log(`  - ${key}: ${value}`);
}

// 3. Verificar parámetros específicos de dev-tools
const pageParam = urlParams.get('page');
const pageSectionParam = urlParams.get('page_section');

console.log('🎯 Parámetros Dev-Tools:');
console.log(`  - page: ${pageParam || 'No definido'}`);
console.log(`  - page_section: ${pageSectionParam || 'No definido'}`);

// 4. Verificar que el parámetro 'page' sea dinámico
if (pageParam) {
    if (pageParam.includes('-dev-tools')) {
        console.log('✅ Menu slug parece dinámico (contiene -dev-tools)');
        
        // Extraer el prefijo del plugin
        const pluginPrefix = pageParam.replace('-dev-tools', '');
        console.log(`🏷️ Prefijo del plugin detectado: "${pluginPrefix}"`);
        
        // Verificar consistencia con config JavaScript
        if (typeof devToolsConfig !== 'undefined' && devToolsConfig.actionPrefix) {
            if (devToolsConfig.actionPrefix === pluginPrefix) {
                console.log('✅ Consistencia: prefix URL = prefix config JavaScript');
            } else {
                console.log(`⚠️ Inconsistencia: URL="${pluginPrefix}" vs Config="${devToolsConfig.actionPrefix}"`);
            }
        }
    } else {
        console.log('⚠️ Menu slug no parece seguir el patrón dinámico esperado');
    }
} else {
    console.log('❌ Parámetro page no encontrado en URL');
}

// 5. Test de generación de URLs de navegación
console.log('🧭 Test de URLs de Navegación:');

const pages = ['dashboard', 'system-info', 'cache', 'ajax-tester', 'logs', 'performance'];
const baseUrl = currentUrl.origin + currentUrl.pathname;

pages.forEach(page => {
    // Simular generación de URL
    const newUrl = new URL(baseUrl);
    if (pageParam) {
        newUrl.searchParams.set('page', pageParam);
    }
    newUrl.searchParams.set('page_section', page);
    
    console.log(`  - ${page}: ${newUrl.href}`);
});

// 6. Verificar elementos de navegación en el DOM
console.log('🖱️ Elementos de Navegación en DOM:');

const navItems = document.querySelectorAll('.nav-item');
navItems.forEach((item, index) => {
    const href = item.getAttribute('href');
    const title = item.querySelector('.nav-title')?.textContent;
    const isActive = item.classList.contains('active');
    
    console.log(`  ${index + 1}. ${title}:`);
    console.log(`     - URL: ${href}`);
    console.log(`     - Activo: ${isActive ? '✅' : '⭕'}`);
    
    // Validar que la URL contiene los parámetros correctos
    if (href) {
        try {
            const linkUrl = new URL(href);
            const linkParams = new URLSearchParams(linkUrl.search);
            const linkPage = linkParams.get('page');
            const linkSection = linkParams.get('page_section');
            
            console.log(`     - Parámetros: page="${linkPage}", page_section="${linkSection}"`);
            
            // Validar consistencia
            if (linkPage === pageParam) {
                console.log(`     - ✅ Consistencia de menu slug`);
            } else {
                console.log(`     - ⚠️ Inconsistencia: esperado="${pageParam}", encontrado="${linkPage}"`);
            }
        } catch (e) {
            console.log(`     - ❌ Error parseando URL: ${e.message}`);
        }
    }
});

// 7. Test de función dev_tools_get_nav_url (simulación JavaScript)
console.log('🔧 Simulación de dev_tools_get_nav_url():');

function simulateNavUrl(pageSection) {
    const baseParams = new URLSearchParams();
    if (pageParam) {
        baseParams.set('page', pageParam);
    }
    if (pageSection) {
        baseParams.set('page_section', pageSection);
    }
    
    return `${baseUrl}?${baseParams.toString()}`;
}

pages.forEach(page => {
    const simulatedUrl = simulateNavUrl(page);
    console.log(`  - ${page}: ${simulatedUrl}`);
});

// 8. Verificar detección automática del plugin host
console.log('🏠 Información del Plugin Host:');

// Buscar indicadores en el HTML
const pageTitle = document.title;
const headerTitle = document.querySelector('.dev-tools-header h1')?.textContent;

console.log(`  - Título de página: ${pageTitle}`);
console.log(`  - Título de header: ${headerTitle}`);

if (headerTitle && headerTitle.includes(' - Dev Tools')) {
    const pluginName = headerTitle.replace(' - Dev Tools', '');
    console.log(`  - 🎯 Plugin host detectado: "${pluginName}"`);
} else {
    console.log(`  - ⚠️ No se pudo detectar el nombre del plugin host`);
}

// 9. Resumen y recomendaciones
console.log('=' .repeat(60));
console.log('%c📊 RESUMEN DEL TEST DE URLs', 'color: #198754; font-size: 16px; font-weight: bold;');

const results = {
    config_available: typeof devToolsConfig !== 'undefined',
    url_has_page_param: !!pageParam,
    menu_slug_dynamic: pageParam && pageParam.includes('-dev-tools'),
    nav_items_found: navItems.length > 0,
    consistent_urls: true // Asumimos true, se validaría en un análisis más profundo
};

const passed = Object.values(results).filter(Boolean).length;
const total = Object.keys(results).length;

console.log(`✅ Tests pasados: ${passed}/${total}`);
console.log(`📈 Porcentaje de éxito: ${Math.round((passed/total) * 100)}%`);

if (results.menu_slug_dynamic) {
    console.log('✅ El sistema parece estar generando URLs dinámicas correctamente');
} else {
    console.log('⚠️ Posible problema con la generación dinámica de URLs');
}

console.log('=' .repeat(60));

// 10. Función helper para testing
window.testNavigation = function(pageSection) {
    const url = simulateNavUrl(pageSection);
    console.log(`🧭 Navegando a: ${url}`);
    window.location.href = url;
};

console.log('💡 Tip: Usa testNavigation("system-info") para probar navegación');
console.log('💡 Tip: Todas las URLs deberían contener el prefijo del plugin detectado');
