/**
 * DevTools Test Runner
 * Integración con el sistema de dev-tools existente
 */

// Extender el objeto devTools global con funcionalidad de test runner
if (window.devTools) {
    window.devTools.testRunner = {
        isRunning: false,
        
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
                
                // Usar el sistema AJAX existente de dev-tools
                const response = await window.devTools.makeRequest('run_tests', {
                    test_type: testType,
                    test_file: testFile
                });
                
                if (response.success) {
                    this.displayResults(response.data);
                } else {
                    this.showError('Error: ' + (response.data || 'Error desconocido'));
                }
                
            } catch (error) {
                console.error('Error ejecutando test:', error);
                this.showError('Error de conexión: ' + error.message);
            } finally {
                this.isRunning = false;
            }
        },

        /**
         * Mostrar resultados en el div testResults
         */
        displayResults(results) {
            const testResultsDiv = document.getElementById('testResults');
            if (!testResultsDiv) {
                console.error('Div testResults no encontrado');
                return;
            }

            const html = this.formatResults(results);
            testResultsDiv.innerHTML = html;
            testResultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },

        /**
         * Formatear resultados
         */
        formatResults(results) {
            const { output, return_code, execution_time, command } = results;
            
            let statusClass = return_code === 0 ? 'success' : 'danger';
            let statusIcon = return_code === 0 ? '✅' : '❌';
            let statusText = return_code === 0 ? 'PASÓ' : 'FALLÓ';
            
            const timestamp = new Date().toLocaleString('es-ES');

            return `
                <div class="card border-${statusClass} mb-3">
                    <div class="card-header bg-${statusClass} text-white">
                        <h5 class="mb-0">
                            ${statusIcon} Test ${statusText}
                            <small class="float-end">${timestamp}</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Comando:</strong>
                                <code class="d-block bg-light p-2 rounded">${command}</code>
                            </div>
                            <div class="col-md-3">
                                <strong>Código:</strong>
                                <span class="badge bg-${statusClass}">${return_code}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Tiempo:</strong>
                                <span class="badge bg-info">${execution_time}ms</span>
                            </div>
                        </div>
                        
                        <div class="test-output">
                            <strong>Salida del Test:</strong>
                            <pre class="bg-dark text-light p-3 rounded mt-2" style="max-height: 400px; overflow-y: auto;"><code>${this.escapeHtml(output)}</code></pre>
                        </div>

                        ${this.generateSummary(output, return_code)}
                    </div>
                </div>
            `;
        },

        /**
         * Generar resumen
         */
        generateSummary(output, return_code) {
            const lines = output.split('\n');
            const summaryLine = lines.find(line => line.includes('Tests:') || line.includes('OK'));
            
            if (summaryLine) {
                let summaryClass = return_code === 0 ? 'success' : 'danger';
                return `
                    <div class="alert alert-${summaryClass} mt-3">
                        <strong>📊 Resumen:</strong> ${summaryLine.trim()}
                    </div>
                `;
            }
            return '';
        },

        /**
         * Mostrar mensaje
         */
        showMessage(message, type = 'info') {
            const testResultsDiv = document.getElementById('testResults');
            if (!testResultsDiv) return;

            const alertClass = {
                'info': 'alert-info',
                'success': 'alert-success', 
                'warning': 'alert-warning',
                'danger': 'alert-danger'
            }[type] || 'alert-info';

            testResultsDiv.innerHTML = `
                <div class="alert ${alertClass}" role="alert">
                    ${message}
                </div>
            `;
        },

        /**
         * Mostrar error
         */
        showError(message) {
            this.showMessage(`❌ ${message}`, 'danger');
        },

        /**
         * Limpiar resultados
         */
        clearResults() {
            const testResultsDiv = document.getElementById('testResults');
            if (testResultsDiv) {
                testResultsDiv.innerHTML = `
                    <div class="alert alert-secondary" role="alert">
                        <i class="fas fa-info-circle"></i> Los resultados aparecerán aquí...
                    </div>
                `;
            }
        },

        /**
         * Escapar HTML
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Crear alias global para fácil acceso
    window.devToolsTestRunner = window.devTools.testRunner;
    
    console.log('✅ DevTools Test Runner cargado correctamente');
}
