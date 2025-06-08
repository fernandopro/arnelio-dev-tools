/**
 * Tarokina Dev Tools - Sistema de Tests Simplificado
 * 
 * Sistema refactorizado que ejecuta tests directamente en console.log()
 * sin dependencias externas. Versión completamente funcional.
 * 
 * @version 2.1.0 - Limpio y optimizado
 * @author Tarokina Dev Tools
 */

class DevToolsTestRunner {
    constructor() {
        this.isRunning = false;
        this.currentTestButton = null;
        this.init();
    }

    /**
     * Inicializar el sistema de tests
     */
    init() {
        this.setupConsoleStyles();
        this.attachEventListeners();
    }

    /**
     * Configurar estilos de consola para mejor visualización
     */
    setupConsoleStyles() {
        this.styles = {
            header: 'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);',
            success: 'background: linear-gradient(135deg, #059669, #047857); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            error: 'background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            warning: 'background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            info: 'background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            test: 'background: #1f2937; color: #34d399; padding: 6px 10px; border-radius: 4px; font-family: "SF Mono", Monaco, Consolas, monospace; font-weight: 500;',
            code: 'background: #1f2937; color: #60a5fa; padding: 2px 6px; border-radius: 3px; font-family: "SF Mono", Monaco, Consolas, monospace;',
            highlight: 'background: #fbbf24; color: #1f2937; padding: 4px 8px; border-radius: 3px; font-weight: bold;'
        };

        // Estilos específicos para headers de tipos de tests
        this.testHeaderStyles = {
            others: 'background: linear-gradient(135deg, #4a5568, #2d3748); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);',
            unit: 'background: linear-gradient(135deg, #0dcaf0, #0bb5d6); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);',
            integration: 'background: linear-gradient(135deg, #ffc107, #e0a800); color: #1f2937; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(255,255,255,0.3);',
            all: 'background: linear-gradient(135deg, #212529, #0d1117); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);'
        };
    }

    /**
     * Obtener estilo de header según el tipo de test
     */
    getHeaderStyle(testType = 'default') {
        return this.testHeaderStyles[testType] || this.styles.header;
    }

    /**
     * Obtener estilos de output para el tipo de test específico
     */
    getTestTypeOutputStyle(testType = 'default') {
        const baseStyles = {
            output: 'font-family: "SF Mono", Monaco, Consolas, monospace; color: #d1d5db;',
            detail: 'color: #9ca3af; font-size: 0.9em;'
        };

        switch (testType) {
            case 'others':
                return {
                    ...baseStyles,
                    stats: 'background: linear-gradient(135deg, #4a5568, #2d3748); color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;'
                };
            case 'unit':
                return {
                    ...baseStyles,
                    stats: 'background: linear-gradient(135deg, #0dcaf0, #0bb5d6); color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;'
                };
            case 'integration':
                return {
                    ...baseStyles,
                    stats: 'background: linear-gradient(135deg, #ffc107, #e0a800); color: #1f2937; padding: 4px 8px; border-radius: 4px; font-weight: bold;'
                };
            case 'all':
                return {
                    ...baseStyles,
                    stats: 'background: linear-gradient(135deg, #212529, #0d1117); color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;'
                };
            default:
                return {
                    ...baseStyles,
                    stats: 'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;'
                };
        }
    }

    /**
     * Obtener la URL de AJAX desde la configuración de WordPress
     */
    getAjaxUrl() {
        // Verificar si está disponible la configuración localizada
        if (typeof tkn_dev_tools_config !== 'undefined' && tkn_dev_tools_config.ajax_url) {
            return tkn_dev_tools_config.ajax_url;
        }
        
        // Fallback para ajaxurl global (por si acaso)
        if (typeof ajaxurl !== 'undefined') {
            return ajaxurl;
        }
        
        // Último fallback construyendo la URL manualmente
        return window.location.origin + '/wp-admin/admin-ajax.php';
    }

    /**
     * Adjuntar event listeners a los botones
     */
    attachEventListeners() {
        // CORREGIDO: Verificar si ya se registraron los listeners para evitar duplicación
        if (document.devToolsTestListenersAttached) {
            console.log('%c⚠️ Event listeners ya registrados, saltando duplicación', 'color: #f59e0b; font-weight: bold;');
            return;
        }
        
        // Event delegation para botones de tests
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action]');
            if (!button) return;

            const action = button.dataset.action;
            
            switch (action) {
                case 'run_single_test':
                    e.preventDefault();
                    this.handleSingleTest(button);
                    break;
                    
                case 'run_wp_tests':
                    e.preventDefault();
                    this.handleAllTests(button);
                    break;
                    
                case 'refresh_tests':
                    e.preventDefault();
                    this.handleRefreshTests(button);
                    break;
                    
                case 'delete_test':
                    e.preventDefault();
                    this.handleDeleteTest(button);
                    break;
                    
                default:
                    // No hacer nada para otras acciones
                    break;
            }
        });
        
        // Marcar que los listeners ya están registrados
        document.devToolsTestListenersAttached = true;
    }

    /**
     * Detectar el tipo de test basándose en la ubicación del archivo
     */
    detectTestTypeFromPath(testFile) {
        if (testFile.includes('unit/')) {
            return 'unit';
        } else if (testFile.includes('integration/')) {
            return 'integration';
        } else {
            // Todos los demás directorios (custom/, temp/, simulators/, etc.)
            return 'others';
        }
    }

    /**
     * Ejecutar un test individual
     */
    async handleSingleTest(button) {
        if (this.isRunning) {
            this.logWarning('⚠️ Ya hay un test ejecutándose. Espera a que termine.');
            return;
        }

        const testFile = button.dataset.testFile;
        const nonce = button.dataset.nonce;

        if (!testFile) {
            this.logError('❌ No se encontró el archivo de test');
            return;
        }

        // Detectar tipo de test basándose en la ubicación del archivo
        const testType = this.detectTestTypeFromPath(testFile);
        
        // Crear header específico según el tipo de test detectado
        let headerMessage;
        switch (testType) {
            case 'unit':
                headerMessage = `⚡ Ejecutando Test Unitario: ${testFile}`;
                break;
            case 'integration':
                headerMessage = `🔗 Ejecutando Test de Integración: ${testFile}`;
                break;
            case 'others':
                headerMessage = `📁 Ejecutando Test Individual: ${testFile}`;
                break;
            default:
                headerMessage = `🧪 Ejecutando: ${testFile}`;
                break;
        }
        
        // CORREGIDO: Mostrar header sin color especial (el color estará en la línea TIPO:)
        console.log(`%c${headerMessage}`, 'color: #6b7280; font-weight: normal;');
        
        try {
            this.setRunningState(true, button);
            
            const result = await this.executeTest('run_single_test', {
                test_file: testFile,
                nonce: nonce
            });
            
            // Pasar el tipo de test detectado para aplicar colores consistentes
            this.processTestResult(result, testFile, testType);
            
        } catch (error) {
            this.logError(`❌ Error ejecutando test: ${error.message}`);
            // Solo mostrar detalles en caso de error
            console.log('%c🔍 Detalles del error:', 'color: #f87171; font-weight: bold;');
            console.log('   • testFile:', testFile);
            console.log('   • testType:', testType);
            console.log('   • nonce:', nonce);
            console.log('   • Error completo:', error);
        } finally {
            this.setRunningState(false, button);
        }
    }

    /**
     * Ejecutar todos los tests de WordPress
     */
    async handleAllTests(button) {
        if (this.isRunning) {
            this.logWarning('⚠️ Ya hay tests ejecutándose. Espera a que terminen.');
            return;
        }

        const nonce = button.dataset.nonce;
        const args = button.dataset.args || ''; // Obtener argumentos del botón
        
        // CORREGIDO: Mostrar header específico según los argumentos
        let headerMessage = '🚀 Ejecutando Todos los Tests de WordPress';
        let testTypeName = 'Todos los Tests WordPress';
        let testType = 'default'; // Tipo para el estilo del header
        
        if (args.includes('--unit')) {
            headerMessage = '⚡ Ejecutando Tests Unitarios';
            testTypeName = 'Tests Unitarios';
            testType = 'unit';
        } else if (args.includes('--integration')) {
            headerMessage = '🔗 Ejecutando Tests de Integración';
            testTypeName = 'Tests de Integración';
            testType = 'integration';
        } else if (args.includes('--others')) {
            headerMessage = '📁 Ejecutando Tests Otros (Personal, Temp, Simulators)';
            testTypeName = 'Tests Otros';
            testType = 'others';
        } else if (args.includes('--all')) {
            headerMessage = '🚀 Ejecutando Todos los Tests (Unitarios + Integración + Otros)';
            testTypeName = 'Todos los Tests';
            testType = 'all';
        }
        
        // CORREGIDO: Mostrar header inicial sin color de fondo (neutral como tests individuales)
        console.log(`%c${headerMessage}`, 'color: #6b7280; font-weight: normal;');
        
        try {
            this.setRunningState(true, button);
            
            // CORREGIDO: Pasar los argumentos al servidor
            const requestData = {
                nonce: nonce
            };
            
            // Solo agregar args si no está vacío
            if (args.trim()) {
                requestData.args = args;
            }
            
            const result = await this.executeTest('run_wp_tests', requestData);
            
            this.processTestResult(result, testTypeName, testType);
            
        } catch (error) {
            this.logError(`❌ Error ejecutando tests: ${error.message}`);
            // Solo mostrar detalles en caso de error
            console.log('%c🔍 Detalles del error:', 'color: #f87171; font-weight: bold;');
            console.log('   • nonce:', nonce);
            console.log('   • Error completo:', error);
        } finally {
            this.setRunningState(false, button);
        }
    }

    /**
     * Refrescar lista de tests
     */
    async handleRefreshTests(button) {
        if (this.isRunning) {
            this.logWarning('⚠️ Operación en curso. Espera a que termine.');
            return;
        }

        const nonce = button.dataset.nonce;
        
        this.logInfo('🔄 Refrescando lista de tests...');
        
        try {
            this.setRunningState(true, button);
            
            // Simplemente recargar la página para refrescar
            window.location.reload();
            
        } catch (error) {
            this.logError(`❌ Error refrescando tests: ${error.message}`);
        } finally {
            this.setRunningState(false, button);
        }
    }

    /**
     * Eliminar un archivo de test
     */
    async handleDeleteTest(button) {
        if (this.isRunning) {
            this.logWarning('⚠️ Operación en curso. Espera a que termine.');
            return;
        }

        const testFile = button.dataset.testFile;
        const nonce = button.dataset.nonce;

        if (!testFile) {
            this.logError('❌ No se encontró el archivo de test');
            return;
        }

        // Confirmar con el usuario antes de eliminar
        if (!confirm(`¿Estás seguro de que quieres eliminar el test "${testFile}"?\n\nEsta acción no se puede deshacer.`)) {
            this.logInfo('🚫 Eliminación cancelada por el usuario');
            return;
        }

        this.logWarning(`🗑️ Eliminando test: ${testFile}`);
        
        try {
            this.setRunningState(true, button);
            
            const resultData = await this.executeTest('delete_test', {
                test_file: testFile,
                nonce: nonce
            });
            
            // Si llegamos aquí, executeTest fue exitoso (no lanzó excepción)
            this.logSuccess(`✅ ${resultData.message || 'Test eliminado correctamente'}`);
            
            // Remover la fila del test de la tabla
            const testRow = button.closest('tr');
            if (testRow) {
                testRow.style.opacity = '0.5';
                testRow.style.transition = 'opacity 0.3s ease-out';
                
                setTimeout(() => {
                    testRow.remove();
                    this.logInfo('📝 Interfaz actualizada - test removido de la lista');
                }, 300);
            }
            
        } catch (error) {
            this.logError(`❌ Error eliminando test: ${error.message}`);
            console.log('%c🔍 Detalles del error:', 'color: #f87171; font-weight: bold;');
            console.log('   • testFile:', testFile);
            console.log('   • nonce:', nonce);
            console.log('   • Error completo:', error);
        } finally {
            this.setRunningState(false, button);
        }
    }

    /**
     * Ejecutar test mediante AJAX
     */
    async executeTest(action, data) {
        // Obtener la URL de AJAX desde la configuración localizada
        const ajaxUrl = this.getAjaxUrl();
        
        // Mapear acciones de JavaScript a acciones PHP correctas
        const actionMap = {
            'run_single_test': 'tarokina_run_single_test',
            'run_wp_tests': 'tarokina_dev_tools_action',
            'refresh_tests': 'tarokina_dev_tools_action',
            'delete_test': 'tarokina_dev_tools_action'
        };
        
        const phpAction = actionMap[action] || `tarokina_${action}`;
        
        const requestData = {
            action: phpAction,
            ...data
        };
        
        // Para acciones que van a través de tarokina_dev_tools_action, agregar dev_action
        if (phpAction === 'tarokina_dev_tools_action') {
            requestData.dev_action = action;
        }
        
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestData)
        });

        if (!response.ok) {
            // Solo mostrar detalles de debug en caso de error HTTP
            console.log('%c🔍 Detalles del error HTTP:', 'color: #f87171; font-weight: bold;');
            console.log('   • Status:', response.status);
            console.log('   • StatusText:', response.statusText);
            console.log('   • URL:', ajaxUrl);
            console.log('   • Action:', phpAction);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const responseText = await response.text();
        let result;
        
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            // Solo mostrar detalles en caso de error de parsing
            console.log('%c🔍 Error parseando JSON:', 'color: #f87171; font-weight: bold;');
            console.log('   • Error:', parseError.message);
            console.log('   • Respuesta (primeros 300 chars):', responseText.substring(0, 300));
            throw new Error(`Error parseando respuesta JSON: ${parseError.message}`);
        }
        
        // CORREGIDO: Manejar tanto success como error responses de WordPress
        if (!result.success) {
            // Para wp_send_json_error, los datos están en result.data
            const errorMessage = result.data?.message || result.data || 'Error desconocido en el servidor';
            throw new Error(errorMessage);
        }

        return result.data;
    }

    /**
     * Procesar y mostrar resultado del test
     */
    processTestResult(result, testName, testType = 'default') {
        // CORREGIDO: Usar result.stats.success para determinar éxito/fallo de tests
        const testSuccess = result.stats ? result.stats.success : true;
        
        // Obtener el estilo de color para este tipo de test
        const testTypeStyle = this.getTestTypeOutputStyle(testType);
        
        // Para tests exitosos, mostrar solo output esencial filtrado
        if (testSuccess) {
            if (result.output && result.output.trim()) {
                const filteredOutput = this.filterTestOutput(result.output, true); // true = exitoso
                if (filteredOutput.trim()) {
                    const lines = filteredOutput.split('\n');
                    lines.forEach((line) => {
                        if (line.trim()) {
                            // CORREGIDO: Aplicar color especial a la línea que contiene "TIPO:"
                            if (line.includes('🧪') && line.includes('- TIPO:')) {
                                console.log(`%c${line}`, testTypeStyle.stats); // Usar el estilo del tipo de test
                            } else {
                                console.log(`%c${line}`, testTypeStyle.output);
                            }
                        }
                    });
                }
            }
        } else {
            // Para tests fallidos, mostrar output completo sin filtrar tanto
            if (result.output && result.output.trim()) {
                const filteredOutput = this.filterTestOutput(result.output, false); // false = fallido
                if (filteredOutput.trim()) {
                    const lines = filteredOutput.split('\n');
                    lines.forEach((line) => {
                        if (line.trim()) {
                            // CORREGIDO: Aplicar color especial a la línea que contiene "TIPO:" también en tests fallidos
                            if (line.includes('🧪') && line.includes('- TIPO:')) {
                                console.log(`%c${line}`, testTypeStyle.stats); // Usar el estilo del tipo de test
                            } else {
                                console.log(`%c${line}`, testTypeStyle.output);
                            }
                        }
                    });
                }
            }
        }

        // Mostrar estadísticas finales si están disponibles
        if (result.stats) {
            const stats = result.stats;
            const statusIcon = testSuccess ? '✅' : '❌';
            
            // Línea de resumen compacta estilo PHPUnit con colores dinámicos
            const testsColor = (stats.tests_run > 0) ? '#22c55e' : '#6b7280';
            const assertionsColor = (stats.assertions > 0) ? '#22c55e' : '#6b7280';
            const failuresColor = (stats.failures > 0) ? '#ef4444' : '#22c55e';
            const errorsColor = (stats.errors > 0) ? '#ef4444' : '#22c55e';
            
            console.log(`%cTests: %c${stats.tests_run || 0}%c, Assertions: %c${stats.assertions || 0}%c, Failures: %c${stats.failures || 0}%c, Errors: %c${stats.errors || 0}%c.`,
                'color: #6b7280; font-weight: bold;',
                `color: ${testsColor}; font-weight: bold;`,
                'color: #6b7280;',
                `color: ${assertionsColor}; font-weight: bold;`,
                'color: #6b7280;',
                `color: ${failuresColor}; font-weight: bold;`,
                'color: #6b7280;',
                `color: ${errorsColor}; font-weight: bold;`,
                'color: #6b7280;'
            );
            
            console.log(`%c${statusIcon} Tests completados:`, testTypeStyle.stats);
            console.log(`%c   • Tests ejecutados: ${stats.tests_run || 0}`, testTypeStyle.detail);
            console.log(`%c   • Assertions: ${stats.assertions || 0}`, testTypeStyle.detail);
            
            // Colorear fallos y errores dinámicamente
            const failuresStyle = (stats.failures > 0) ? 'color: #ef4444; font-weight: bold;' : testTypeStyle.detail;
            const errorsStyle = (stats.errors > 0) ? 'color: #ef4444; font-weight: bold;' : testTypeStyle.detail;
            
            console.log(`%c   • Fallos: ${stats.failures || 0}`, failuresStyle);
            console.log(`%c   • Errores: ${stats.errors || 0}`, errorsStyle);
            console.log(`%c   • Tiempo: ${stats.execution_time || 0}ms`, testTypeStyle.detail);
        }

        // Solo mostrar información adicional de debug si el test falló
        if (!testSuccess) {
            // Mostrar errores si existen
            if (result.errors && result.errors.length > 0) {
                console.log('%c🚨 Errores encontrados:', 'background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;');
                result.errors.forEach((error) => {
                    console.log(`%c❌ ${error}`, testTypeStyle.detail);
                });
            }

            // NUEVO: Mostrar lista de tests fallidos si está disponible en las estadísticas
            if (result.stats && result.stats.failed_tests && result.stats.failed_tests.length > 0) {
                console.log('%c📋 Tests fallidos:', 'color: #ef4444; font-weight: bold; background: #fef2f2; padding: 6px 10px; border-radius: 4px;');
                
                // Función para mapear nombre de clase a nombre de archivo
                const mapClassToFilename = (className) => {
                    // Si es 'Otros' o ya tiene .php, retornar tal como está
                    if (className === 'Otros' || className.endsWith('.php')) {
                        return className;
                    }
                    // Agregar .php al nombre de la clase
                    return className + '.php';
                };
                
                // Agrupar tests por clase para mejor organización
                const groupedTests = {};
                result.stats.failed_tests.forEach(testName => {
                    if (testName.includes('::')) {
                        const [className, methodName] = testName.split('::');
                        if (!groupedTests[className]) {
                            groupedTests[className] = [];
                        }
                        groupedTests[className].push(methodName);
                    } else {
                        if (!groupedTests['Otros']) {
                            groupedTests['Otros'] = [];
                        }
                        groupedTests['Otros'].push(testName);
                    }
                });
                
                // Mostrar tests agrupados por archivo (mapear clase a nombre de archivo)
                Object.entries(groupedTests).forEach(([className, methods]) => {
                    const filename = mapClassToFilename(className);
                    console.log(`%c📂 ${filename}:`, 'color: #dc2626; font-weight: bold; margin-left: 10px;');
                    methods.forEach(methodName => {
                        console.log(`%c   ❌ ${methodName}`, 'color: #ef4444; margin-left: 20px;');
                    });
                });
            }

            if (result.status) {
                console.log(`%c🏷️ Status: ${result.status}`, testTypeStyle.detail);
            }

            // Separador visual antes del resultado final si hay contenido adicional
            if ((result.errors && result.errors.length > 0) || 
                (result.stats && result.stats.failed_tests && result.stats.failed_tests.length > 0) ||
                result.stats || 
                result.status) {
                console.log('%c' + '─'.repeat(50), testTypeStyle.detail);
            }
        }

        // Mostrar resultado final con colores específicos: verde para éxito, rojo para fallo
        if (testSuccess) {
            console.log(`%c✅ ${testName} - Exitoso`, 'color: #22c55e; font-weight: bold;');
        } else {
            console.log(`%c❌ ${testName} - Falló`, 'color: #ef4444; font-weight: bold;');
        }

        // Mostrar tiempo de ejecución al final (siempre)
        if (result.execution_time) {
            console.log(`%c⏱️ Tiempo: ${result.execution_time}ms`, testTypeStyle.detail);
        }
    }

    /**
     * Filtrar output del test para ocultar warnings específicos de headers
     */
    filterTestOutput(output, isSuccessful = false) {
        if (!output) return output;
        
        // Decodificar entidades HTML primero
        let cleanOutput = output
            .replace(/&#039;/g, "'")
            .replace(/&lt;/g, "<")
            .replace(/&gt;/g, ">")
            .replace(/&quot;/g, '"')
            .replace(/&amp;/g, "&");
        
        // Filtros básicos para todos los tests
        const basicFilters = [
            // Warnings de headers PHP
            /PHP Warning:\s+Cannot modify header information - headers already sent/,
            // Errores de base de datos WordPress (muy comunes en tests)
            /<div id="error"><p class="wpdberror">/,
            /WordPress database error:/,
            /Duplicate entry.*for key/,
            /INSERT INTO.*wp_test_/,
            /<\/code><\/p><\/div>/,
            // Líneas vacías consecutivas
            /^\s*$/
        ];
        
        // Filtros adicionales para tests exitosos (más agresivo)
        const successFilters = [
            ...basicFilters,
            // Mensajes de configuración de tests que no aportan valor en tests exitosos
            /Running as single site\.\.\. To run multisite/,
            /Installing\.\.\./,
            /Not running ajax tests\. To execute these/,
            /Not running ms-files tests\. To execute these/,
            /Not running external-http tests\. To execute these/,
            // Mensajes de carga del plugin Tarokina
            /Tarokina PRO - Cargando el plugin\.\.\./,
            /✅ TAROKINA TESTS: Plugin Tarokina Pro cargado exitosamente/,
            /📁 Plugin Path:/,
            /🌐 Plugin URL:/,
            /✅ TAROKINA TESTS: Custom Post Types registrados correctamente/,
            /✅ TAROKINA TESTS: Taxonomías registradas correctamente/,
            /✅ TAROKINA TESTS: Funciones principales del plugin disponibles/,
            /TAROKINA: Baraja predeterminada ya existe/,
            /TKINA: Verificando datos de la versión más reciente/,
            // Información de configuración de PHPUnit (solo en exitosos)
            /PHPUnit \d+\.\d+\.\d+ by Sebastian Bergmann and contributors\./,
            /Runtime:\s+PHP \d+\.\d+\.\d+/,
            /Configuration: .*phpunit\.xml$/
        ];
        
        const filtersToUse = isSuccessful ? successFilters : basicFilters;
        
        const lines = cleanOutput.split('\n');
        const filteredLines = lines.filter(line => {
            // Si la línea está dentro de un bloque de error HTML, filtrarla completa
            if (line.includes('<div id="error">') || line.includes('</div>')) {
                return false;
            }
            
            return !filtersToUse.some(pattern => pattern.test(line));
        });
        
        // Eliminar líneas vacías consecutivas al final del filtrado
        let result = filteredLines.join('\n');
        result = result.replace(/\n\s*\n\s*\n/g, '\n\n'); // Máximo 2 líneas vacías consecutivas
        result = result.trim();
        
        return result;
    }

    /**
     * Establecer estado de ejecución
     */
    setRunningState(isRunning, button) {
        this.isRunning = isRunning;
        this.currentTestButton = isRunning ? button : null;

        if (button) {
            const icon = button.querySelector('i');
            const originalContent = button.dataset.originalContent || button.innerHTML;

            if (isRunning) {
                // Guardar contenido original si no existe
                if (!button.dataset.originalContent) {
                    button.dataset.originalContent = button.innerHTML;
                }
                
                button.disabled = true;
                button.innerHTML = '<i class="bi bi-hourglass-split"></i> Ejecutando...';
                button.classList.add('btn-secondary');
                button.classList.remove('btn-success', 'btn-primary', 'btn-outline-info', 'btn-outline-success', 'btn-outline-primary');
            } else {
                button.disabled = false;
                
                // Restaurar contenido original
                if (button.dataset.originalContent) {
                    button.innerHTML = button.dataset.originalContent;
                } else {
                    button.innerHTML = originalContent;
                }
                
                button.classList.remove('btn-secondary');
                
                // Restaurar clase original basada en la acción
                const action = button.dataset.action;
                if (action === 'run_single_test') {
                    button.classList.add('btn-success');
                } else if (action === 'run_wp_tests') {
                    // Determinar clase específica basada en args
                    const args = button.dataset.args || '';
                    if (args.includes('--unit')) {
                        button.classList.add('btn-outline-info');
                    } else if (args.includes('--integration')) {
                        button.classList.add('btn-outline-success');
                    } else {
                        button.classList.add('btn-outline-primary');
                    }
                } else if (action === 'refresh_tests') {
                    button.classList.add('btn-outline-secondary');
                }
            }
        }
    }

    /**
     * Métodos de logging con estilos
     */
    logHeader(message, testType = 'default') {
        const headerStyle = this.getHeaderStyle(testType);
        console.log(`%c${message}`, headerStyle);
    }

    logSuccess(message) {
        console.log(`%c${message}`, this.styles.success);
    }

    logError(message) {
        console.log(`%c${message}`, this.styles.error);
    }

    logWarning(message) {
        console.log(`%c${message}`, this.styles.warning);
    }

    logInfo(message) {
        console.log(`%c${message}`, this.styles.info);
    }

    logTest(message) {
        console.log(`%c${message}`, this.styles.test);
    }
}

/**
 * Utilidades adicionales para debugging
 */
window.devTestsDebug = {
    /**
     * Mostrar información del sistema
     */
    info() {
        console.group('🔍 DevTools Tests - Información del Sistema');
        console.log('Estado de ejecución:', window.DevToolsTestRunner?.isRunning || false);
        console.log('Botón actual:', window.DevToolsTestRunner?.currentTestButton || null);
        console.log('AJAX URL:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'No disponible');
        console.groupEnd();
    },
    
    /**
     * Probar la conectividad con el backend
     */
    async testConnection() {
        try {
            const ajaxUrl = this.getAjaxUrl();
            
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'tarokina_dev_tools_action',
                    dev_action: 'test_connection'
                })
            });
            
            const result = await response.json();
            console.log('✅ Conexión exitosa:', result);
        } catch (error) {
            console.error('❌ Error de conexión:', error);
        }
    }
}

// Exportar la clase al objeto global para acceso directo
window.DevToolsTestRunner = DevToolsTestRunner;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // CORREGIDO: Verificar que no se instancie múltiples veces
    if (window.testRunner) {
        console.log('%c⚠️ TestRunner ya existe, saltando instanciación duplicada', 'color: #f59e0b; font-weight: bold;');
        return;
    }
    
    // Solo inicializar en la página de dev-tools
    if (document.querySelector('[data-action]')) {
        window.testRunner = new DevToolsTestRunner();
        window.devToolsTestRunner = window.testRunner; // Alias para compatibilidad
        console.log('%c✅ DevToolsTestRunner inicializado correctamente', 'color: #059669; font-weight: bold;');
    }
});