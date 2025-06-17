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

        // Leer valores de los checkboxes con nuevos IDs
        const testTypes = [];
        if (document.getElementById('devtools-unitTests')?.checked) testTypes.push('unit');
        if (document.getElementById('devtools-integrationTests')?.checked) testTypes.push('integration');
        if (document.getElementById('devtools-databaseTests')?.checked) testTypes.push('database');
        
        const verbose = document.getElementById('devtools-verboseOutput')?.checked || false;
        const coverage = document.getElementById('devtools-generateCoverage')?.checked || false;
        const testdox = document.getElementById('devtools-testdoxOutput')?.checked || false;
        
        if (testTypes.length === 0) {
            this.showMessage('⚠️ Selecciona al menos un tipo de test', 'warning');
            return;
        }

        this.isRunning = true;
        this.updateButtonStates(true, 'devtools-runTests');
        this.showStatus('Ejecutando tests: ' + testTypes.join(', ') + '...');
        
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
            this.hideStatus();
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
        this.showStatus('Ejecutando test rápido...');
        
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
            this.hideStatus();
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
        this.showStatus('Probando conectividad AJAX...');
        
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
            this.hideStatus();
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
            html += `<div class="alert alert-info mb-3">
                <strong>📝 Comando ejecutado:</strong><br>
                <code class="small">${this.escapeHtml(results.command)}</code>
            </div>`;
        }
        
        // Resumen de resultados
        if (results.summary) {
            const summary = results.summary;
            const statusClass = summary.status === 'success' ? 'success' : 
                               summary.status === 'error' ? 'danger' : 'warning';
            const statusIcon = summary.status === 'success' ? '✅' : 
                              summary.status === 'error' ? '❌' : '⚠️';
            
            html += `<div class="alert alert-${statusClass} mb-3">
                <h6>${statusIcon} Resumen de Tests</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Total:</strong> ${summary.total_tests}</p>
                        <p class="mb-1"><strong>Pasados:</strong> <span class="text-success">${summary.passed}</span></p>
                        <p class="mb-1"><strong>Fallidos:</strong> <span class="text-danger">${summary.failed}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Errores:</strong> <span class="text-warning">${summary.errors}</span></p>
                        <p class="mb-1"><strong>Omitidos:</strong> <span class="text-muted">${summary.skipped}</span></p>
                        <p class="mb-1"><strong>Aserciones:</strong> ${summary.assertions}</p>
                    </div>
                </div>
                ${summary.time ? `<p class="mb-0"><strong>⏱️ Tiempo:</strong> ${summary.time}</p>` : ''}
                ${summary.memory ? `<p class="mb-0"><strong>💾 Memoria:</strong> ${summary.memory}</p>` : ''}
            </div>`;
        }
        
        // Output completo
        if (results.output) {
            html += `<div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🔍 Output Completo</h6>
                </div>
                <div class="card-body p-0">
                    <pre class="bg-dark text-light p-3 m-0 rounded-bottom" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">${this.escapeHtml(results.output)}</pre>
                </div>
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
        
        const alertClass = {
            'info': 'alert-info',
            'success': 'alert-success', 
            'warning': 'alert-warning',
            'error': 'alert-danger'
        }[type] || 'alert-info';
        
        testResultsDiv.innerHTML = `<div class="alert ${alertClass} mb-0">${message}</div>`;
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
     * Mostrar estado de ejecución
     */
    showStatus(message) {
        const statusDiv = document.getElementById('devtools-testStatus');
        const statusText = document.getElementById('devtools-statusText');
        
        if (statusDiv && statusText) {
            statusText.textContent = message;
            statusDiv.style.display = 'block';
        }
    }

    /**
     * Ocultar estado de ejecución
     */
    hideStatus() {
        const statusDiv = document.getElementById('devtools-testStatus');
        if (statusDiv) {
            statusDiv.style.display = 'none';
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
