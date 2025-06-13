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

        // Leer valores de los checkboxes
        const testTypes = [];
        if (document.getElementById('unitTests')?.checked) testTypes.push('unit');
        if (document.getElementById('integrationTests')?.checked) testTypes.push('integration');
        if (document.getElementById('database')?.checked) testTypes.push('database');
        
        const verbose = document.getElementById('verboseOutput')?.checked || false;
        const coverage = document.getElementById('generateCoverage')?.checked || false;
        
        if (testTypes.length === 0) {
            this.showMessage('⚠️ Selecciona al menos un tipo de test', 'warning');
            return;
        }

        this.isRunning = true;
        
        try {
            this.showMessage(`🔄 Ejecutando tests: ${testTypes.join(', ')}...`, 'info');
            
            const response = await this.makeTestAjaxRequest('run_tests', {
                test_types: testTypes,
                verbose: verbose,
                coverage: coverage
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
        
        try {
            this.showMessage('🚀 Ejecutando test rápido...', 'info');
            
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
        }
    }

    /**
     * Mostrar resultados en el div testResults
     */
    displayResults(results) {
        const testResultsDiv = document.getElementById('testResults');
        if (!testResultsDiv) {
            console.error('Div testResults no encontrado');
            return;
        }
        
        let html = '<div class="test-results-container">';
        
        // Encabezado con información del comando
        if (results.command) {
            html += `<div class="alert alert-info mb-3">
                <strong>Comando ejecutado:</strong> <code>${results.command}</code>
            </div>`;
        }
        
        // Resumen de resultados
        if (results.summary) {
            const summary = results.summary;
            const statusClass = summary.status === 'success' ? 'success' : 
                               summary.status === 'error' ? 'danger' : 'warning';
            
            html += `<div class="alert alert-${statusClass} mb-3">
                <h5>📊 Resumen de Tests</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Total:</strong> ${summary.total_tests}</p>
                        <p><strong>Pasados:</strong> ${summary.passed}</p>
                        <p><strong>Fallidos:</strong> ${summary.failed}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Errores:</strong> ${summary.errors}</p>
                        <p><strong>Omitidos:</strong> ${summary.skipped}</p>
                        <p><strong>Aserciones:</strong> ${summary.assertions}</p>
                    </div>
                </div>
                ${summary.time ? `<p><strong>Tiempo:</strong> ${summary.time}</p>` : ''}
                ${summary.memory ? `<p><strong>Memoria:</strong> ${summary.memory}</p>` : ''}
            </div>`;
        }
        
        // Output completo
        if (results.output) {
            html += `<div class="card">
                <div class="card-header">
                    <h6>🔍 Output Completo</h6>
                </div>
                <div class="card-body">
                    <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">${this.escapeHtml(results.output)}</pre>
                </div>
            </div>`;
        }
        
        // Información de ejecución
        if (results.execution_time) {
            html += `<div class="mt-3 text-muted small">
                ⏱️ Tiempo de ejecución: ${results.execution_time}s
                ${results.exit_code !== undefined ? ` | Código de salida: ${results.exit_code}` : ''}
            </div>`;
        }
        
        html += '</div>';
        
        testResultsDiv.innerHTML = html;
        
        // Scroll al div de resultados
        testResultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Mostrar mensaje de estado
     */
    showMessage(message, type = 'info') {
        const testResultsDiv = document.getElementById('testResults');
        if (!testResultsDiv) return;
        
        const alertClass = {
            'info': 'alert-info',
            'success': 'alert-success', 
            'warning': 'alert-warning',
            'error': 'alert-danger'
        }[type] || 'alert-info';
        
        testResultsDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    }

    /**
     * Mostrar error
     */
    showError(message) {
        this.showMessage(`❌ ${message}`, 'error');
    }

    /**
     * Escapar HTML para mostrar output seguro
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Limpiar resultados
     */
    clearResults() {
        const testResultsDiv = document.getElementById('testResults');
        if (testResultsDiv) {
            testResultsDiv.innerHTML = '<p class="text-muted">No hay resultados de tests aún.</p>';
        }
    }
}

export default TestRunner;
