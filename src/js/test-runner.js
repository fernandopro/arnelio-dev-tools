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
        
        // Resumen de resultados con header unificado
        if (results.summary) {
            const summary = results.summary;
            
            // Determinar el color del estado basado en resultados
            let statusColor = '#10b981'; // Verde por defecto (éxito)
            let statusIcon = '✅';
            let statusText = 'Éxito';
            
            if (summary.failed > 0 || summary.errors > 0) {
                statusColor = '#ef4444'; // Rojo para fallos/errores
                statusIcon = '❌';
                statusText = 'Error';
            } else if (summary.skipped > 0 || summary.incomplete > 0 || summary.risky > 0) {
                statusColor = '#f59e0b'; // Amarillo para advertencias
                statusIcon = '⚠️';
                statusText = 'Advertencia';
            }
            
            html += `<div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 25px rgba(0,0,0,0.1);">
                <!-- Header con información unificada -->
                <div style="background: linear-gradient(135deg, ${statusColor} 0%, ${statusColor}dd 100%); color: white; padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div>
                            <h6 style="margin: 0; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                ${statusIcon} All Tests
                            </h6>
                        </div>
                        <div style="text-align: right;">
                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500;">
                                ${statusText}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Estadísticas completas -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; font-size: 0.875rem;">
                        <div class="metric-card">
                            <div class="metric-value">${summary.total_tests}</div>
                            <div class="metric-label">Tests</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value metric-success">${summary.passed}</div>
                            <div class="metric-label">Pasados</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value metric-error">${summary.failed}</div>
                            <div class="metric-label">Fallos</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value metric-warning">${summary.errors}</div>
                            <div class="metric-label">Errores</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value metric-info">${summary.skipped}</div>
                            <div class="metric-label">Omitidos</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value metric-purple">${summary.assertions}</div>
                            <div class="metric-label">Assertions</div>
                        </div>
                        ${summary.time ? `
                        <div class="metric-card">
                            <div class="metric-value">${summary.time}</div>
                            <div class="metric-label">Tiempo</div>
                        </div>
                        ` : ''}
                        ${summary.memory ? `
                        <div class="metric-card">
                            <div class="metric-value metric-cyan">${summary.memory}</div>
                            <div class="metric-label">Memoria</div>
                        </div>
                        ` : ''}
                        ${(summary.incomplete || 0) > 0 ? `
                        <div class="metric-card">
                            <div class="metric-value metric-warning">${summary.incomplete || 0}</div>
                            <div class="metric-label">Incompletos</div>
                        </div>
                        ` : ''}
                        ${summary.risky > 0 ? `
                        <div class="metric-card">
                            <div class="metric-value metric-warning">${summary.risky || 0}</div>
                            <div class="metric-label">Riesgosos</div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Comando ejecutado -->
                    ${results.command ? `
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2);">
                        <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.5rem; font-weight: 600;">💻 Comando:</div>
                        <div style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 0.7rem; word-break: break-all; line-height: 1.3;">
                            ${this.escapeHtml(results.command)}
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <!-- Contenido del test -->`;
        }
        
        // Output completo
        if (results.output) {
            html += `<div style="padding: 1.5rem;">
                <div class="modern-section">
                    <div class="modern-section-title">📋 Salida Detallada</div>
                    <pre class="modern-code-block modern-code-block-dark">${this.escapeHtml(results.output)}</pre>
                </div>
                
                ${results.command && results.summary ? `
                <div class="modern-section">
                    <div class="modern-section-title">💻 Comando Ejecutado</div>
                    <pre class="modern-code-block modern-code-block-light"><code>${this.escapeHtml(results.command)}</code></pre>
                </div>
                ` : ''}
            </div>`;
        }
        
        // Cerrar el contenedor unificado si hay summary
        if (results.summary) {
            html += `</div>`;
        }
        
        // Encabezado con información del comando (solo si no hay summary para evitar duplicación)
        if (results.command && !results.summary) {
            html += `<div class="modern-section">
                <div class="modern-section-title">💻 Comando Ejecutado</div>
                <pre class="modern-code-block modern-code-block-light"><code>${this.escapeHtml(results.command)}</code></pre>
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
