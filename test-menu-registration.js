/**
 * Test de verificación de registro de menú Dev-Tools
 * Usar en consola del navegador para verificar si el menú está presente
 */

console.log('🔧 Testing Dev-Tools Menu Registration...');

// Test 1: Verificar si el menú Dev-Tools existe en el sidebar
function testMenuPresence() {
    console.log('\n📋 Test 1: Verificando presencia del menú...');
    
    // Buscar el enlace del menú Dev-Tools
    const devToolsMenu = document.querySelector('a[href*="tools.php?page=dev-tools"]');
    
    if (devToolsMenu) {
        console.log('✅ Menú Dev-Tools encontrado en el sidebar');
        console.log('📍 Elemento:', devToolsMenu);
        console.log('📄 Texto del menú:', devToolsMenu.textContent.trim());
        
        // Verificar si está en la sección Tools
        const toolsSection = devToolsMenu.closest('li.wp-has-submenu, li.menu-top');
        if (toolsSection) {
            const sectionTitle = toolsSection.querySelector('.wp-menu-name, .menu-top .wp-menu-name');
            console.log('📂 Sección padre:', sectionTitle ? sectionTitle.textContent.trim() : 'No identificada');
        }
        
        return true;
    } else {
        console.log('❌ Menú Dev-Tools NO encontrado en el sidebar');
        
        // Buscar cualquier enlace relacionado con dev-tools
        const anyDevToolsLink = document.querySelector('a[href*="dev-tools"], a[href*="dev_tools"]');
        if (anyDevToolsLink) {
            console.log('⚠️ Encontrado enlace relacionado:', anyDevToolsLink.href);
        }
        
        return false;
    }
}

// Test 2: Verificar estructura del menú Tools
function testToolsMenuStructure() {
    console.log('\n🔧 Test 2: Analizando estructura del menú Tools...');
    
    // Buscar el menú Tools
    const toolsMenu = document.querySelector('a[href="tools.php"]');
    
    if (toolsMenu) {
        console.log('✅ Menú Tools encontrado');
        
        // Buscar el submenu
        const toolsItem = toolsMenu.closest('li');
        const submenu = toolsItem ? toolsItem.querySelector('ul.wp-submenu') : null;
        
        if (submenu) {
            console.log('📋 Submenús en Tools:');
            const submenuItems = submenu.querySelectorAll('li a');
            submenuItems.forEach((item, index) => {
                console.log(`  ${index + 1}. ${item.textContent.trim()} (${item.href})`);
            });
        } else {
            console.log('⚠️ No se encontró submenu en Tools');
        }
    } else {
        console.log('❌ Menú Tools no encontrado');
    }
}

// Test 3: Verificar si la página responde
async function testDevToolsPageAccess() {
    console.log('\n🌐 Test 3: Verificando acceso a la página Dev-Tools...');
    
    try {
        // Construir URL de la página dev-tools
        const currentUrl = new URL(window.location.href);
        const devToolsUrl = `${currentUrl.origin}${currentUrl.pathname}?page=dev-tools`;
        
        console.log('🔗 URL a verificar:', devToolsUrl);
        
        // Solo podemos verificar si hay un elemento que nos lleve allí
        const devToolsLink = document.querySelector('a[href*="page=dev-tools"]');
        
        if (devToolsLink) {
            console.log('✅ Enlace a página dev-tools encontrado');
            console.log('🔗 URL del enlace:', devToolsLink.href);
        } else {
            console.log('❌ No se encontró enlace a página dev-tools');
        }
        
    } catch (error) {
        console.log('❌ Error verificando acceso:', error.message);
    }
}

// Test 4: Verificar hooks y timing
function testHooksAndTiming() {
    console.log('\n⏰ Test 4: Información de hooks y timing...');
    
    // Verificar si estamos en el admin
    const isAdmin = document.body.classList.contains('wp-admin');
    console.log('🏛️ En área de administración:', isAdmin);
    
    // Verificar página actual
    const currentPage = new URLSearchParams(window.location.search).get('page');
    console.log('📄 Página actual:', currentPage || 'Página principal del admin');
    
    // Verificar si hay errores de JavaScript
    if (window.console && console.error) {
        console.log('⚠️ Revisar errores de consola arriba para problemas de carga');
    }
}

// Ejecutar todos los tests
async function runAllTests() {
    console.log('🚀 Iniciando tests de registro de menú Dev-Tools...\n');
    
    const menuFound = testMenuPresence();
    testToolsMenuStructure();
    await testDevToolsPageAccess();
    testHooksAndTiming();
    
    console.log('\n📊 RESUMEN:');
    console.log(`Menú Dev-Tools registrado: ${menuFound ? '✅ SÍ' : '❌ NO'}`);
    
    if (!menuFound) {
        console.log('\n🔧 POSIBLES SOLUCIONES:');
        console.log('1. Verificar que el hook admin_menu se ejecute antes de que se renderice el menú');
        console.log('2. Verificar permisos de usuario (manage_options)');
        console.log('3. Verificar que el DashboardModule se inicialice correctamente');
        console.log('4. Verificar logs de error en la consola del servidor');
    }
    
    console.log('\n🔍 Para más información, revisar los logs del servidor en wp-content/debug.log');
}

// Ejecutar automáticamente
runAllTests();
