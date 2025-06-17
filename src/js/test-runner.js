/**
 * DevTools Test Runner
 * Integración con el sistema de dev-tools existente
 */

class TestRunner {
    constructor(devTools) {
        this.devTools = devTools;
        this.isRunning = false;
    }
    
    /**
     * Hacer llamada AJAX específica para tests
     */
    async makeTestAjaxRequest(action, data = {}) {
        // Obtener nonce directamente de devToolsConfig para debugging
        const nonce = (typeof devToolsConfig !== 'undefined') ? devToolsConfig.nonce : '';
        const apiUrl = (typeof devToolsConfig !== 'undefined') ? devToolsConfig.ajaxurl : this.devTools.apiUrl;
        
        console.log('🔍 AJAX Debug Info:');
        console.log('  - URL:', apiUrl);
        console.log('  - Action:', `dev_tools_${action}`);
        console.log('  - Nonce from devToolsConfig:', nonce);
        console.log('  - Nonce from devTools.getNonce():', this.devTools.getNonce());
        console.log('  - Data:', data);
        console.log('  - devToolsConfig completo:', devToolsConfig);
        
        const requestBody = {
            action: `dev_tools_${action}`,
            nonce: nonce,
            ...data
        };
        
        console.log('  - Request Body:', requestBody);
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestBody)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Capturar texto de respuesta para debugging
        const responseText = await response.text();
        console.log('🔍 Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON Parse Error:', parseError);
            console.error('❌ Response text:', responseText);
            throw new Error(`Invalid JSON response: ${responseText.substring(0, 100)}...`);
        }
        
        if (!result.success) {
            throw new Error(result.data?.message || 'Error en la respuesta del servidor');
        }
        
        return result.data;
    }
    
    /**
     * Ejecutar test usando el sistema AJAX de dev-tools
     */
    async runTest(testType, testFile = '') {
        if (this.isRunning) {
            this.showMessage('Ya hay un test en ejecución...', 'warning');
            return;
        }

        this.isRunning = true;
        
        try {
            this.showMessage(`🔄 Ejecutando ${testType}...`, 'info');
            
            // Usar el sistema AJAX específico para tests
            const response = await this.makeTestAjaxRequest('run_tests', {
                test_type: testType,
                test_file: testFile
            });
            
            if (response) {
                this.displayResults(response);
            } else {
                this.showError('Error: No se recibió respuesta del servidor');
            }
            
        } catch (error) {
            console.error('Error ejecutando test:', error);
            this.showError('Error de conexión: ' + error.message);
        } finally {
            this.isRunning = false;
        }
    }

    /**
     * Ejecutar tests con opciones específicas (para botón runTests)
     */
    async runTests() {
        if (this.isRunning) {
            this.showMessage('Ya hay un test en ejecución...', 'warning');
            return;
        }

        // Leer valor del radio button seleccionado
        const selectedTestType = document.querySelector('input[name="testType"]:checked');
        
        if (!selectedTestType) {
            this.showMessage('⚠️ Selecciona un tipo de test', 'warning');
            return;
        }
        
        const testTypes = [selectedTestType.value]; // Solo un tipo ahora
        
        const verbose = document.getElementById('devtools-verboseOutput')?.checked || false;
        const coverage = document.getElementById('devtools-generateCoverage')?.checked || false;
        const testdox = document.getElementById('devtools-testdoxOutput')?.checked || false;

        this.isRunning = true;
        this.updateButtonStates(true, 'devtools-runTests');
        
        try {
            const response = await this.makeTestAjaxRequest('run_tests', {
                test_types: testTypes,
                verbose: verbose,
                coverage: coverage,
                testdox: testdox
            });
            
            if (response) {
                this.displayResults(response);
            } else {
                this.showError('Error: No se recibió respuesta del servidor');
            }
            
        } catch (error) {
            console.error('Error ejecutando tests:', error);
            this.showError('Error de conexión: ' + error.message);
        } finally {
            this.isRunning = false;
            this.updateButtonStates(false, 'devtools-runTests');
        }
    }

    /**
     * Ejecutar test rápido (para botón runQuickTest)
     */
    async runQuickTest() {
        if (this.isRunning) {
            this.showMessage('Ya hay un test en ejecución...', 'warning');
            return;
        }

        this.isRunning = true;
        this.updateButtonStates(true, 'devtools-runQuickTest');
        
        try {
            const response = await this.makeTestAjaxRequest('run_quick_test', {});
            
            if (response) {
                this.displayResults(response);
            } else {
                this.showError('Error: No se recibió respuesta del servidor');
            }
            
        } catch (error) {
            console.error('Error ejecutando quick test:', error);
            this.showError('Error de conexión: ' + error.message);
        } finally {
            this.isRunning = false;
            this.updateButtonStates(false, 'devtools-runQuickTest');
        }
    }

    /**
     * Test de conectividad con el sistema AJAX
     */
    async testConnectivity() {
        if (this.isRunning) {
            this.showMessage('Ya hay un test en ejecución...', 'warning');
            return;
        }

        this.isRunning = true;
        this.updateButtonStates(true, 'devtools-testConnectivity');
        
        try {
            this.showMessage('🔄 Probando conectividad con el sistema...', 'info');
            
            // Test básico de conectividad
            const startTime = Date.now();
            const response = await this.makeTestAjaxRequest('run_quick_test', {});
            const endTime = Date.now();
            
            const connectivityInfo = 'Conectividad AJAX funcionando correctamente\n' +
                                   'Tiempo de respuesta: ' + (endTime - startTime) + 'ms\n' +
                                   'URL: ' + ((typeof devToolsConfig !== 'undefined') ? devToolsConfig.ajaxurl : 'No disponible') + '\n' +
                                   'Nonce: ' + ((typeof devToolsConfig !== 'undefined') ? 'Configurado' : 'No disponible');
            
            if (response) {
                this.showMessage('✅ ' + connectivityInfo, 'success');
            } else {
                this.showError('❌ Error de conectividad: No se recibió respuesta del servidor');
            }
            
        } catch (error) {
            console.error('Error en test de conectividad:', error);
            this.showError('❌ Error de conectividad: ' + error.message);
        } finally {
            this.isRunning = false;
            this.updateButtonStates(false, 'devtools-testConnectivity');
        }
    }

    /**
     * Mostrar resultados en el div testResults
     */
    displayResults(results) {
        const testResultsDiv = document.getElementById('devtools-testResults');
        if (!testResultsDiv) {
            console.error('Div devtools-testResults no encontrado');
            return;
        }
        
        let html = '<div class="test-results-container">';
        
        // Encabezado con información del comando
        if (results.command) {
            html += `<div class="modern-section">
                <div class="modern-section-title">Comando ejecutado</div>
                <pre class="modern-code-block modern-code-block-light"><code>${this.escapeHtml(results.command)}</code></pre>
            </div>`;
        }
        
        // Resumen de resultados
        if (results.summary) {
            const summary = results.summary;
            
            html += `<div class="modern-info-grid">
                <div class="modern-info-item total" data-type="total">
                    <div class="modern-info-label">Total</div>
                    <div class="modern-info-value">${summary.total_tests}</div>
                </div>
                <div class="modern-info-item success" data-type="success">
                    <div class="modern-info-label">Pasados</div>
                    <div class="modern-info-value">${summary.passed}</div>
                </div>
                <div class="modern-info-item ${summary.failed > 0 ? 'error' : 'muted'}" data-type="${summary.failed > 0 ? 'error' : 'muted'}">
                    <div class="modern-info-label">Fallidos</div>
                    <div class="modern-info-value">${summary.failed}</div>
                </div>
                <div class="modern-info-item ${summary.errors > 0 ? 'warning' : 'muted'}" data-type="${summary.errors > 0 ? 'warning' : 'muted'}">
                    <div class="modern-info-label">Errores</div>
                    <div class="modern-info-value">${summary.errors}</div>
                </div>
                <div class="modern-info-item ${summary.skipped > 0 ? 'warning' : 'muted'}" data-type="${summary.skipped > 0 ? 'warning' : 'muted'}">
                    <div class="modern-info-label">Omitidos</div>
                    <div class="modern-info-value">${summary.skipped}</div>
                </div>
                <div class="modern-info-item ${summary.risky > 0 ? 'warning' : 'muted'}" data-type="${summary.risky > 0 ? 'warning' : 'muted'}">
                    <div class="modern-info-label">Riesgosos</div>
                    <div class="modern-info-value">${summary.risky || 0}</div>
                </div>
                <div class="modern-info-item info" data-type="info">
                    <div class="modern-info-label">Aserciones</div>
                    <div class="modern-info-value">${summary.assertions}</div>
                </div>
                ${summary.time ? `<div class="modern-info-item info" data-type="info">
                    <div class="modern-info-label">Tiempo</div>
                    <div class="modern-info-value">${summary.time}</div>
                </div>` : ''}
                ${summary.memory ? `<div class="modern-info-item info" data-type="info">
                    <div class="modern-info-label">Memoria</div>
                    <div class="modern-info-value">${summary.memory}</div>
                </div>` : ''}
            </div>`;
        }
        
        // Output completo
        if (results.output) {
            html += `<div class="modern-section">
                <pre class="modern-code-block modern-code-block-dark">${this.escapeHtml(results.output)}</pre>
            </div>`;
        }
        
        // Información de ejecución
        if (results.execution_time) {
            html += `<div class="mt-3 text-muted small">
                ⏱️ Tiempo de ejecución: ${results.execution_time}ms
                ${results.exit_code !== undefined ? ` | Código de salida: ${results.exit_code}` : ''}
            </div>`;
        }
        
        html += '</div>';
        
        testResultsDiv.innerHTML = html;
        
        // Scroll al div de resultados suavemente
        testResultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Mostrar mensaje de estado
     */
    showMessage(message, type = 'info') {
        const testResultsDiv = document.getElementById('devtools-testResults');
        if (!testResultsDiv) return;
        
        const alertType = {
            'info': 'info',
            'success': 'success', 
            'warning': 'info',
            'error': 'error'
        }[type] || 'info';
        
        const alertIcon = {
            'info': '🔧',
            'success': '✅', 
            'warning': '⚠️',
            'error': '❌'
        }[type] || '🔧';
        
        testResultsDiv.innerHTML = `<div class="modern-alert modern-alert-${alertType}">
            <div class="modern-alert-icon">${alertIcon}</div>
            <div class="modern-alert-content">
                <div class="modern-alert-title">${message}</div>
            </div>
        </div>`;
    }

    /**
     * Mostrar error
     */
    showError(message) {
        this.showMessage(`❌ ${message}`, 'error');
    }

    /**
     * Limpiar resultados
     */
    clearResults() {
        const testResultsDiv = document.getElementById('devtools-testResults');
        if (testResultsDiv) {
            testResultsDiv.innerHTML = `<div class="text-center text-muted py-5">
                <i class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></i>
                <p class="mt-2 mb-0">No hay resultados de tests aún.</p>
                <small>Selecciona los tipos de test y presiona "Run Selected Tests"</small>
            </div>`;
        }
    }

    /**
     * Actualizar estado de un botón específico
     */
    updateSpecificButtonState(buttonId, isRunning, loadingText = 'Ejecutando...') {
        const button = document.getElementById(buttonId);
        if (!button) return;
        
        const originalContent = button.getAttribute('data-original-content');
        
        // Guardar contenido original la primera vez
        if (!originalContent && !isRunning) {
            button.setAttribute('data-original-content', button.innerHTML);
        }
        
        button.disabled = isRunning;
        
        if (isRunning) {
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
        } else {
            // Restaurar contenido original
            const content = originalContent || button.innerHTML;
            button.innerHTML = content;
        }
    }

    /**
     * Actualizar estado de los botones (método legacy - ahora usa específico)
     */
    updateButtonStates(isRunning, specificButtonId = null) {
        if (specificButtonId) {
            // Solo actualizar el botón específico
            this.updateSpecificButtonState(specificButtonId, isRunning);
            
            // Deshabilitar otros botones durante ejecución
            const allButtons = ['devtools-runTests', 'devtools-runQuickTest', 'devtools-clearResults', 'devtools-testConnectivity'];
            allButtons.forEach(buttonId => {
                if (buttonId !== specificButtonId) {
                    const button = document.getElementById(buttonId);
                    if (button) {
                        button.disabled = isRunning;
                    }
                }
            });
        } else {
            // Comportamiento original (actualizar todos)
            const runButton = document.getElementById('devtools-runTests');
            const quickButton = document.getElementById('devtools-runQuickTest');
            const clearButton = document.getElementById('devtools-clearResults');
            
            if (runButton) {
                runButton.disabled = isRunning;
                runButton.innerHTML = isRunning ? 
                    '<span class="spinner-border spinner-border-sm me-2"></span>Ejecutando...' : 
                    '<i class="dashicons dashicons-yes-alt"></i> 🚀 Run Selected Tests';
            }
            
            if (quickButton) {
                quickButton.disabled = isRunning;
                quickButton.innerHTML = isRunning ? 
                    '<span class="spinner-border spinner-border-sm me-2"></span>Ejecutando...' : 
                    '<i class="dashicons dashicons-performance"></i> ⚡ Quick Test';
            }
            
            if (clearButton) {
                clearButton.disabled = isRunning;
            }
        }
    }

    /**
     * Escapar HTML para mostrar output seguro
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

export default TestRunner;
