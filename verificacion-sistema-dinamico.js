/**
 * Verificación del Sistema Dinámico Dev Tools
 * 
 * Script para verificar que el sistema dev-tools funciona correctamente
 * con configuración dinámica (plugin-agnóstico)
 */

console.log('%c🔧 VERIFICACIÓN SISTEMA DINÁMICO DEV TOOLS', 
    'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold;');

// Verificar configuración dinámica
console.group('📋 Configuración Dinámica');

if (typeof tkn_dev_tools_config !== 'undefined') {
    console.log('✅ Configuración localizada detectada');
    console.table(tkn_dev_tools_config);
    
    // Verificar campos dinámicos clave
    const requiredFields = ['ajaxUrl', 'ajaxAction', 'pluginName', 'pluginSlug'];
    requiredFields.forEach(field => {
        if (tkn_dev_tools_config[field]) {
            console.log(`✅ ${field}: ${tkn_dev_tools_config[field]}`);
        } else {
            console.warn(`⚠️ ${field}: No configurado`);
        }
    });
} else {
    console.error('❌ Configuración localizada NO detectada');
}

console.groupEnd();

// Verificar instancia del controlador
console.group('🎛️ Controlador Principal');

if (typeof DevToolsController !== 'undefined') {
    console.log('✅ Clase DevToolsController disponible');
    
    if (window.DevToolsController) {
        console.log('✅ Instancia global creada');
        console.log('Estado:', {
            initialized: window.DevToolsController.isInitialized,
            debugMode: window.DevToolsController.debugMode,
            verboseMode: window.DevToolsController.verboseMode,
            config: window.DevToolsController.config
        });
    } else {
        console.warn('⚠️ Instancia global NO creada');
    }
} else {
    console.error('❌ Clase DevToolsController NO disponible');
}

console.groupEnd();

// Verificar API pública
console.group('🔌 API Pública');

if (window.DevToolsAPI) {
    console.log('✅ API pública disponible');
    console.log('Métodos disponibles:', Object.keys(window.DevToolsAPI));
} else {
    console.error('❌ API pública NO disponible');
}

console.groupEnd();

// Verificar sistema de testing
console.group('🧪 Sistema de Testing');

if (typeof DevToolsTestRunner !== 'undefined') {
    console.log('✅ Clase DevToolsTestRunner disponible');
    
    if (window.DevToolsTestRunner) {
        console.log('✅ Instancia de TestRunner creada');
        console.log('Configuración:', window.DevToolsTestRunner.config);
    } else {
        console.warn('⚠️ Instancia de TestRunner NO creada');
    }
} else {
    console.error('❌ Clase DevToolsTestRunner NO disponible');
}

console.groupEnd();

// Verificar funciones dinámicas
console.group('⚙️ Funciones Dinámicas');

if (window.DevToolsController && typeof window.DevToolsController.getAjaxAction === 'function') {
    console.log('✅ Función getAjaxAction disponible');
    
    // Probar generación de acciones dinámicas
    const testActions = ['ping', 'check_anti_deadlock', 'check_test_framework', 'action'];
    testActions.forEach(action => {
        const generatedAction = window.DevToolsController.getAjaxAction(action);
        console.log(`  ${action} → ${generatedAction}`);
    });
} else {
    console.error('❌ Función getAjaxAction NO disponible');
}

console.groupEnd();

// Verificar URLs dinámicas
console.group('🌐 URLs Dinámicas');

if (window.DevToolsController && window.DevToolsController.config) {
    const urls = {
        'AJAX URL': window.DevToolsController.config.ajaxUrl,
        'Admin URL': window.DevToolsController.config.adminUrl,
        'Site URL': window.DevToolsController.config.siteUrl
    };
    
    Object.entries(urls).forEach(([label, url]) => {
        if (url) {
            console.log(`✅ ${label}: ${url}`);
        } else {
            console.warn(`⚠️ ${label}: No configurada`);
        }
    });
} else {
    console.error('❌ URLs dinámicas NO disponibles');
}

console.groupEnd();

// Resumen final
console.group('📊 Resumen de Verificación');

const checks = {
    'Configuración Localizada': typeof tkn_dev_tools_config !== 'undefined',
    'DevToolsController': typeof DevToolsController !== 'undefined' && !!window.DevToolsController,
    'API Pública': !!window.DevToolsAPI,
    'Sistema de Testing': typeof DevToolsTestRunner !== 'undefined',
    'Funciones Dinámicas': window.DevToolsController && typeof window.DevToolsController.getAjaxAction === 'function'
};

const passed = Object.values(checks).filter(Boolean).length;
const total = Object.keys(checks).length;

console.log(`%c${passed}/${total} verificaciones pasaron`, 
    passed === total ? 'color: #22c55e; font-weight: bold;' : 'color: #ef4444; font-weight: bold;');

Object.entries(checks).forEach(([check, passed]) => {
    console.log(`${passed ? '✅' : '❌'} ${check}`);
});

if (passed === total) {
    console.log('%c🎉 Sistema dinámico funcionando correctamente', 
        'background: #22c55e; color: white; padding: 8px 12px; border-radius: 4px; font-weight: bold;');
} else {
    console.log('%c⚠️ Sistema dinámico con problemas', 
        'background: #ef4444; color: white; padding: 8px 12px; border-radius: 4px; font-weight: bold;');
}

console.groupEnd();

console.log('%c✨ Verificación completada', 
    'background: #7c3aed; color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;');
