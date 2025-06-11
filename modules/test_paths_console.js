/**
 * JavaScript Test para Dev-Tools Paths
 * 
 * Script para copiar y pegar en la consola del navegador
 * para probar las rutas agnósticas desde el frontend
 */

console.log('🔧 Dev-Tools Arquitectura 3.0 - Test de Rutas Agnósticas');
console.log('====================================================');

// Test de las rutas mediante AJAX
async function testDevToolsPaths() {
    const tests = [
        {
            name: 'Test Paths System',
            url: window.location.origin + '/wp-content/plugins/tarokina-2025/dev-tools/modules/test_paths.php'
        },
        {
            name: 'Test SiteUrl Detection',
            url: window.location.origin + '/wp-content/plugins/tarokina-2025/dev-tools/modules/test_browser.php'
        },
        {
            name: 'Test Database Connection',
            url: window.location.origin + '/wp-content/plugins/tarokina-2025/dev-tools/modules/test_database_connection.php'
        }
    ];
    
    console.log('🧪 Ejecutando tests de rutas...');
    
    for (const test of tests) {
        try {
            console.log(`\n📍 Testing: ${test.name}`);
            console.log(`🔗 URL: ${test.url}`);
            
            const response = await fetch(test.url);
            
            if (response.ok) {
                console.log(`✅ ${test.name}: Accesible correctamente`);
                console.log(`📊 Status: ${response.status} ${response.statusText}`);
                console.log(`📏 Size: ${response.headers.get('content-length') || 'Unknown'} bytes`);
            } else {
                console.error(`❌ ${test.name}: Error ${response.status}`);
            }
        } catch (error) {
            console.error(`💥 ${test.name}: Excepción - ${error.message}`);
        }
    }
    
    console.log('\n🎯 Tests completados. Abre los enlaces manualmente para ver los detalles:');
    tests.forEach(test => {
        console.log(`- ${test.name}: ${test.url}`);
    });
}

// Test de detección del entorno actual
function detectCurrentEnvironment() {
    console.log('\n🌍 Detectando entorno actual...');
    
    const env_info = {
        current_url: window.location.href,
        hostname: window.location.hostname,
        port: window.location.port,
        protocol: window.location.protocol,
        is_local_wp: window.location.hostname.includes('.local') || 
                     window.location.hostname === 'localhost',
        user_agent: navigator.userAgent,
        timestamp: new Date().toISOString()
    };
    
    console.table(env_info);
    
    if (env_info.is_local_wp) {
        console.log('✅ Local by WP Engine detectado');
        
        if (env_info.hostname.includes('.local')) {
            console.log('🔗 Router Mode: Site Domains (.local)');
        } else if (env_info.hostname === 'localhost') {
            console.log('🔗 Router Mode: localhost con puerto');
        }
    } else {
        console.log('🌐 Entorno remoto o de producción');
    }
    
    return env_info;
}

// Test de paths desde JavaScript
function testPathsFromJS() {
    console.log('\n📁 Testeando rutas desde JavaScript...');
    
    const base_url = window.location.origin + '/wp-content/plugins/tarokina-2025/dev-tools/';
    
    const paths_to_test = [
        'config/paths.php',
        'modules/test_paths.php',
        'modules/DatabaseConnectionModule.php',
        'modules/SiteUrlDetectionModule.php'
    ];
    
    console.log(`🎯 Base URL calculada: ${base_url}`);
    
    paths_to_test.forEach(path => {
        const full_url = base_url + path;
        console.log(`📂 ${path}: ${full_url}`);
    });
}

// Ejecutar todos los tests
async function runAllTests() {
    console.clear();
    console.log('🚀 Iniciando tests completos de Dev-Tools Paths...');
    
    detectCurrentEnvironment();
    testPathsFromJS();
    await testDevToolsPaths();
    
    console.log('\n🎉 Tests completados. Revisa los resultados arriba.');
    console.log('💡 Para más detalles, abre manualmente los enlaces mostrados.');
}

// Ejecutar automáticamente
runAllTests();
