/**
 * Test Script Completo - Sistema de URLs Dinámicas Dev-Tools
 * 
 * Este script verifica:
 * 1. Configuración dinámica de JavaScript
 * 2. Generación correcta de URLs
 * 3. Funcionalidad del panel v3
 * 4. Navegación entre módulos
 * 
 * INSTRUCCIONES:
 * 1. Abrir Dev Tools en el panel de WordPress
 * 2. Abrir consola del navegador (F12)
 * 3. Copiar y pegar este script completo
 * 4. Revisar los resultados
 */

console.group('🔧 Dev-Tools Sistema Completo - Test de URLs Dinámicas');

// 1. VERIFICAR CONFIGURACIÓN JAVASCRIPT
console.group('📋 1. Verificación de Configuración');

function checkJavaScriptConfig() {
    const configVars = [
        'tarokina_2025_dev_tools_config',
        'tarokina_dev_tools_config',
        'dev_tools_config'
    ];
    
    let activeConfig = null;
    
    for (const configVar of configVars) {
        if (window[configVar]) {
            activeConfig = window[configVar];
            console.log(`✅ Configuración encontrada: ${configVar}`, activeConfig);
            break;
        }
    }
    
    if (!activeConfig) {
        console.error('❌ No se encontró configuración de JavaScript');
        return null;
    }
    
    // Verificar propiedades críticas
    const requiredProps = [
        'ajaxUrl', 'nonce', 'ajaxAction', 'actionPrefix', 
        'menuSlug', 'baseAdminUrl', 'currentPageUrl'
    ];
    
    const missing = requiredProps.filter(prop => !activeConfig[prop]);
    
    if (missing.length > 0) {
        console.warn('⚠️ Propiedades faltantes:', missing);
    } else {
        console.log('✅ Todas las propiedades críticas presentes');
    }
    
    return activeConfig;
}

const config = checkJavaScriptConfig();
console.groupEnd();

if (!config) {
    console.error('❌ Test interrumpido: No hay configuración válida');
    console.groupEnd();
    throw new Error('Configuración de JavaScript no encontrada');
}

// 2. VERIFICAR URLs DINÁMICAS
console.group('🔗 2. Verificación de URLs Dinámicas');

function testDynamicUrls() {
    console.log('Base Admin URL:', config.baseAdminUrl);
    console.log('Current Page URL:', config.currentPageUrl);
    console.log('Menu Slug:', config.menuSlug);
    console.log('Action Prefix:', config.actionPrefix);
    
    // Construir URL de navegación
    const devToolsUrl = config.baseAdminUrl + 'tools.php?page=' + config.menuSlug;
    console.log('🎯 URL de Dev-Tools:', devToolsUrl);
    
    // Verificar si estamos en la página correcta
    const currentUrl = window.location.href;
    const isOnDevToolsPage = currentUrl.includes(config.menuSlug);
    
    console.log('📍 URL Actual:', currentUrl);
    console.log('🔍 ¿En página Dev-Tools?', isOnDevToolsPage);
    
    return { devToolsUrl, isOnDevToolsPage };
}

const urlTest = testDynamicUrls();
console.groupEnd();

// 3. VERIFICAR PANEL V3
console.group('🖥️ 3. Verificación del Panel v3');

function testPanelV3() {
    const results = {
        container: !!document.querySelector('.dev-tools-container'),
        navigation: !!document.querySelector('.dev-tools-nav'),
        modules: {},
        activeModule: null
    };
    
    // Verificar contenedor principal
    console.log('📦 Contenedor principal:', results.container ? '✅' : '❌');
    
    // Verificar navegación
    console.log('🧭 Navegación:', results.navigation ? '✅' : '❌');
    
    // Verificar módulos en navegación
    const navItems = document.querySelectorAll('.nav-link[data-module]');
    console.log(`🔧 Módulos en navegación: ${navItems.length}`);
    
    navItems.forEach(item => {
        const module = item.dataset.module;
        const status = item.querySelector('.status')?.textContent || '?';
        results.modules[module] = status;
        console.log(`  - ${module}: ${status}`);
    });
    
    // Verificar módulo activo
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab) {
        results.activeModule = activeTab.dataset.module;
        console.log('🎯 Módulo activo:', results.activeModule);
    }
    
    return results;
}

const panelTest = testPanelV3();
console.groupEnd();

// 4. VERIFICAR NAVEGACIÓN ENTRE MÓDULOS
console.group('🔄 4. Test de Navegación');

function testModuleNavigation() {
    const navLinks = document.querySelectorAll('.nav-link[data-module]');
    
    if (navLinks.length === 0) {
        console.warn('⚠️ No se encontraron enlaces de navegación');
        return false;
    }
    
    console.log(`🔧 Enlaces encontrados: ${navLinks.length}`);
    
    // Test de click en cada módulo
    navLinks.forEach((link, index) => {
        const module = link.dataset.module;
        const status = link.querySelector('.status')?.textContent;
        
        console.log(`${index + 1}. ${module} (${status})`);
        
        // Verificar si el enlace tiene evento click
        const hasEvent = link.onclick || link.addEventListener;
        console.log(`   🖱️ Evento click: ${hasEvent ? '✅' : '❌'}`);
    });
    
    return true;
}

const navTest = testModuleNavigation();
console.groupEnd();

// 5. TEST DE AJAX
console.group('📡 5. Test de AJAX');

async function testAjaxConnection() {
    try {
        const formData = new FormData();
        formData.append('action', config.ajaxAction);
        formData.append('command', 'test_connection');
        formData.append('nonce', config.nonce);
        
        console.log('📤 Enviando solicitud AJAX...');
        console.log('Action:', config.ajaxAction);
        console.log('URL:', config.ajaxUrl);
        
        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        console.log('📥 Respuesta recibida:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('✅ AJAX funcional:', data);
            return { success: true, data };
        } catch (e) {
            console.log('⚠️ Respuesta no JSON:', text);
            return { success: false, response: text };
        }
        
    } catch (error) {
        console.error('❌ Error en AJAX:', error);
        return { success: false, error: error.message };
    }
}

// Ejecutar test AJAX
testAjaxConnection().then(result => {
    console.log('🎯 Resultado AJAX:', result);
    console.groupEnd();
    
    // 6. RESUMEN FINAL
    console.group('📊 6. Resumen Final');
    
    const summary = {
        configuracion: !!config,
        urls_dinamicas: urlTest.isOnDevToolsPage,
        panel_v3: panelTest.container && panelTest.navigation,
        navegacion: navTest,
        ajax: result.success,
        modulos_detectados: Object.keys(panelTest.modules).length
    };
    
    console.table(summary);
    
    const allGood = Object.values(summary).every(v => v === true || (typeof v === 'number' && v > 0));
    
    if (allGood) {
        console.log('🎉 ¡Sistema completamente funcional!');
    } else {
        console.warn('⚠️ Algunos componentes necesitan atención');
    }
    
    console.groupEnd();
    console.groupEnd();
    
    return { config, urlTest, panelTest, navTest, ajaxTest: result, summary };
});

// 7. FUNCIONES DE AYUDA PARA DEBUGGING
console.group('🛠️ Funciones de Ayuda');

window.devToolsDebug = {
    config,
    
    // Función para cambiar de módulo
    switchModule: function(moduleName) {
        const link = document.querySelector(`[data-module="${moduleName}"]`);
        if (link) {
            link.click();
            console.log(`🔄 Cambiado a módulo: ${moduleName}`);
        } else {
            console.warn(`⚠️ Módulo no encontrado: ${moduleName}`);
        }
    },
    
    // Función para recargar configuración
    reloadConfig: function() {
        location.reload();
    },
    
    // Función para mostrar información del plugin
    showPluginInfo: function() {
        console.group('ℹ️ Información del Plugin');
        console.log('Nombre:', config.pluginName);
        console.log('Slug:', config.pluginSlug);
        console.log('Prefijo:', config.actionPrefix);
        console.log('Menu Slug:', config.menuSlug);
        console.log('Debug Mode:', config.debugMode);
        console.groupEnd();
    }
};

console.log('🛠️ Funciones disponibles en window.devToolsDebug:');
console.log('  - switchModule(nombre)');
console.log('  - reloadConfig()');
console.log('  - showPluginInfo()');

console.groupEnd();

console.log('✨ Test completo ejecutado. Revisa los resultados arriba.');
