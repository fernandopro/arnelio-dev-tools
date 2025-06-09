/**
 * AjaxTester Module JavaScript
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

class DevToolsAjaxTester {
    constructor() {
        this.isLoading = false;
        this.currentTest = null;
        this.history = [];
        this.presets = [];
        
        this.init();
    }

    /**
     * Inicialización del módulo
     */
    init() {
        // Configurar form handler
        this.setupFormHandler();
        
        // Cargar historial
        this.loadHistory();
        
        // Configurar auto-completado
        this.setupAutoComplete();

        this.logInternal('AjaxTester module initialized');
    }

    /**
     * Configurar manejador del formulario
     */
    setupFormHandler() {
        const form = document.getElementById('ajax-test-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.executeTest();
        });

        // Auto-formatear JSON en tiempo real
        const dataTextarea = document.getElementById('ajax-data');
        const headersTextarea = document.getElementById('ajax-headers');
        
        if (dataTextarea) {
            dataTextarea.addEventListener('blur', () => this.formatJSON(dataTextarea));
        }
        
        if (headersTextarea) {
            headersTextarea.addEventListener('blur', () => this.formatJSON(headersTextarea));
        }
    }

    /**
     * Ejecutar test AJAX
     */
    async executeTest() {
        if (this.isLoading) return;

        try {
            this.setLoadingState(true);
            this.updateStatus('Running', 'warning');

            // Obtener datos del formulario
            const testData = this.getFormData();
            
            // Validar datos
            if (!this.validateTestData(testData)) {
                return;
            }

            // Ejecutar test
            const startTime = performance.now();
            const result = await this.makeAjaxRequest('test_ajax_endpoint', testData);
            const endTime = performance.now();

            if (result.success) {
                this.displayResults(result, endTime - startTime);
                this.updateStatus('Success', 'success');
                this.addToHistory(testData, result, endTime - startTime);
            } else {
                this.displayError(result.message || 'Test failed');
                this.updateStatus('Failed', 'danger');
            }

        } catch (error) {
            this.logExternal('Test execution error: ' + error.message, 'error');
            this.displayError('Test execution failed: ' + error.message);
            this.updateStatus('Error', 'danger');
        } finally {
            this.setLoadingState(false);
        }
    }

    /**
     * Obtener datos del formulario
     */
    getFormData() {
        const endpoint = document.getElementById('ajax-endpoint')?.value || '';
        const action = document.getElementById('ajax-action')?.value || '';
        const method = document.getElementById('ajax-method')?.value || 'POST';
        const dataStr = document.getElementById('ajax-data')?.value || '{}';
        const headersStr = document.getElementById('ajax-headers')?.value || '{}';
        
        let data, headers;
        try {
            data = JSON.parse(dataStr);
        } catch (e) {
            data = {};
            this.showAlert('warning', 'Invalid JSON in data field, using empty object');
        }
        
        try {
            headers = JSON.parse(headersStr);
        } catch (e) {
            headers = {};
            this.showAlert('warning', 'Invalid JSON in headers field, using empty object');
        }

        // Auto-nonce si está activado
        const autoNonce = document.getElementById('auto-nonce')?.checked;
        if (autoNonce && !data.nonce) {
            data.nonce = 'auto';
        }

        return {
            endpoint,
            test_action: action,
            method,
            data: JSON.stringify(data),
            headers: JSON.stringify(headers),
            log_request: document.getElementById('log-request')?.checked || false,
            measure_time: document.getElementById('measure-time')?.checked || true
        };
    }

    /**
     * Validar datos del test
     */
    validateTestData(data) {
        if (!data.endpoint.trim()) {
            this.showAlert('danger', 'Endpoint URL is required');
            return false;
        }

        if (!data.test_action.trim()) {
            this.showAlert('danger', 'Action is required');
            return false;
        }

        try {
            JSON.parse(data.data);
            JSON.parse(data.headers);
        } catch (e) {
            this.showAlert('danger', 'Invalid JSON format in data or headers');
            return false;
        }

        return true;
    }

    /**
     * Mostrar resultados del test
     */
    displayResults(result, clientTime) {
        // Ocultar welcome y mostrar results
        document.getElementById('test-welcome')?.classList.add('d-none');
        document.getElementById('test-results')?.classList.remove('d-none');

        // Status
        const statusElement = document.getElementById('response-status');
        if (statusElement && result.result) {
            const statusCode = result.result.status_code;
            statusElement.textContent = statusCode;
            statusElement.className = 'badge ' + this.getStatusBadgeClass(statusCode);
        }

        // Response time
        const timeElement = document.getElementById('response-time');
        if (timeElement) {
            const serverTime = result.execution_time || 0;
            timeElement.textContent = `${serverTime}ms (server) / ${Math.round(clientTime)}ms (total)`;
        }

        // Content type
        const contentTypeElement = document.getElementById('response-content-type');
        if (contentTypeElement && result.result) {
            contentTypeElement.textContent = result.result.content_type || 'unknown';
        }

        // Headers
        const headersElement = document.getElementById('response-headers');
        if (headersElement && result.result?.headers) {
            headersElement.textContent = JSON.stringify(result.result.headers, null, 2);
        }

        // Body
        const bodyElement = document.getElementById('response-body');
        if (bodyElement && result.result?.body) {
            try {
                // Intentar formatear como JSON si es posible
                const jsonBody = JSON.parse(result.result.body);
                bodyElement.textContent = JSON.stringify(jsonBody, null, 2);
            } catch (e) {
                // Mostrar como texto plano
                bodyElement.textContent = result.result.body;
            }
        }

        // Ocultar errores
        document.getElementById('error-details')?.classList.add('d-none');
    }

    /**
     * Mostrar error
     */
    displayError(message) {
        // Mostrar error details
        document.getElementById('test-welcome')?.classList.add('d-none');
        document.getElementById('test-results')?.classList.add('d-none');
        document.getElementById('error-details')?.classList.remove('d-none');

        const errorElement = document.getElementById('error-message');
        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    /**
     * Obtener clase CSS para badge de status
     */
    getStatusBadgeClass(statusCode) {
        if (statusCode >= 200 && statusCode < 300) {
            return 'bg-success';
        } else if (statusCode >= 300 && statusCode < 400) {
            return 'bg-info';
        } else if (statusCode >= 400 && statusCode < 500) {
            return 'bg-warning';
        } else {
            return 'bg-danger';
        }
    }

    /**
     * Actualizar estado del test
     */
    updateStatus(text, type) {
        const statusElement = document.getElementById('test-status');
        if (statusElement) {
            statusElement.textContent = text;
            statusElement.className = `badge bg-${type}`;
        }
    }

    /**
     * Configurar estado de loading
     */
    setLoadingState(loading) {
        this.isLoading = loading;
        
        const loadingElement = document.getElementById('test-loading');
        const submitButton = document.querySelector('#ajax-test-form button[type="submit"]');
        
        if (loading) {
            loadingElement?.classList.remove('d-none');
            document.getElementById('test-results')?.classList.add('d-none');
            document.getElementById('test-welcome')?.classList.add('d-none');
            document.getElementById('error-details')?.classList.add('d-none');
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            }
        } else {
            loadingElement?.classList.add('d-none');
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-play"></i> Execute Test';
            }
        }
    }

    /**
     * Formatear JSON en textarea
     */
    formatJSON(textarea) {
        try {
            const parsed = JSON.parse(textarea.value);
            textarea.value = JSON.stringify(parsed, null, 2);
            textarea.classList.remove('is-invalid');
        } catch (e) {
            textarea.classList.add('is-invalid');
        }
    }

    /**
     * Agregar test al historial
     */
    addToHistory(testData, result, responseTime) {
        const historyItem = {
            timestamp: new Date().toISOString(),
            action: testData.test_action,
            method: testData.method,
            success: result.success && result.result?.status_code < 400,
            status_code: result.result?.status_code,
            response_time: responseTime
        };

        this.history.unshift(historyItem);
        this.updateHistoryDisplay();
    }

    /**
     * Cargar historial desde el servidor
     */
    async loadHistory() {
        try {
            const result = await this.makeAjaxRequest('get_test_history');
            
            if (result.success) {
                this.history = result.history || [];
                this.updateHistoryDisplay();
            }
        } catch (error) {
            this.logExternal('Failed to load test history: ' + error.message, 'error');
        }
    }

    /**
     * Actualizar visualización del historial
     */
    updateHistoryDisplay() {
        const container = document.getElementById('test-history-content');
        if (!container) return;

        if (this.history.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No tests executed yet.</p>';
            return;
        }

        const historyHtml = this.history.slice(0, 10).map(item => {
            const statusClass = item.success ? 'success' : 'danger';
            const statusIcon = item.success ? 'check-circle' : 'exclamation-circle';
            
            return `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>${item.action}</strong>
                        <small class="text-muted ms-2">${item.method}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${statusClass} me-2">
                            <i class="fas fa-${statusIcon}"></i> ${item.status_code || 'Error'}
                        </span>
                        <small class="text-muted">${Math.round(item.response_time)}ms</small>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = historyHtml;
    }

    /**
     * Mostrar historial completo
     */
    showHistory() {
        if (this.history.length === 0) {
            this.showAlert('info', 'No test history available');
            return;
        }

        // Crear modal temporal para mostrar historial completo
        this.showHistoryModal();
    }

    /**
     * Mostrar modal de historial
     */
    showHistoryModal() {
        const modalHtml = `
            <div class="modal fade" id="historyModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Test History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Action</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${this.history.map(item => `
                                            <tr>
                                                <td>${new Date(item.timestamp).toLocaleString()}</td>
                                                <td>${item.action}</td>
                                                <td><span class="badge bg-secondary">${item.method}</span></td>
                                                <td>
                                                    <span class="badge bg-${item.success ? 'success' : 'danger'}">
                                                        ${item.status_code || 'Error'}
                                                    </span>
                                                </td>
                                                <td>${Math.round(item.response_time)}ms</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Agregar modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();

        // Limpiar modal cuando se cierre
        document.getElementById('historyModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    /**
     * Limpiar historial
     */
    async clearHistory() {
        if (!confirm('Are you sure you want to clear the test history?')) {
            return;
        }

        try {
            const result = await this.makeAjaxRequest('clear_test_history');
            
            if (result.success) {
                this.history = [];
                this.updateHistoryDisplay();
                this.showAlert('success', 'Test history cleared successfully');
            } else {
                this.showAlert('danger', 'Failed to clear history: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            this.logExternal('Failed to clear history: ' + error.message, 'error');
            this.showAlert('danger', 'Failed to clear history: ' + error.message);
        }
    }

    /**
     * Cargar presets
     */
    async loadPresets() {
        try {
            const result = await this.makeAjaxRequest('load_test_presets');
            
            if (result.success) {
                this.presets = result.presets || {};
                this.showPresetsModal();
            }
        } catch (error) {
            this.logExternal('Failed to load presets: ' + error.message, 'error');
            this.showAlert('danger', 'Failed to load presets: ' + error.message);
        }
    }

    /**
     * Mostrar modal de presets
     */
    showPresetsModal() {
        const presetsContent = document.getElementById('presets-content');
        if (!presetsContent) return;

        const presetsHtml = Object.keys(this.presets).map(key => {
            const preset = this.presets[key];
            return `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-title">${preset.name}</h6>
                                <p class="card-text text-muted small">
                                    Action: ${preset.config.action}<br>
                                    Method: ${preset.config.method}<br>
                                    Author: ${preset.author}
                                </p>
                            </div>
                            <div>
                                <button class="btn btn-primary btn-sm" onclick="DevToolsAjaxTester.loadPreset('${key}')">
                                    Load
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        presetsContent.innerHTML = presetsHtml || '<p class="text-muted text-center">No presets available</p>';

        const modal = new bootstrap.Modal(document.getElementById('presetsModal'));
        modal.show();
    }

    /**
     * Cargar preset específico
     */
    loadPreset(presetKey) {
        const preset = this.presets[presetKey];
        if (!preset) return;

        const config = preset.config;

        // Cargar valores en el formulario
        document.getElementById('ajax-action').value = config.action || '';
        document.getElementById('ajax-method').value = config.method || 'POST';
        document.getElementById('ajax-data').value = JSON.stringify(config.data || {}, null, 2);
        document.getElementById('ajax-headers').value = JSON.stringify(config.headers || {}, null, 2);

        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('presetsModal'));
        if (modal) {
            modal.hide();
        }

        this.showAlert('success', `Preset "${preset.name}" loaded successfully`);
    }

    /**
     * Guardar configuración actual como preset
     */
    async saveAsPreset() {
        const name = prompt('Enter preset name:');
        if (!name) return;

        try {
            const testData = this.getFormData();
            const config = {
                action: testData.test_action,
                method: testData.method,
                data: JSON.parse(testData.data),
                headers: JSON.parse(testData.headers)
            };

            const result = await this.makeAjaxRequest('save_test_preset', {
                preset_name: name,
                config: JSON.stringify(config)
            });

            if (result.success) {
                this.showAlert('success', 'Preset saved successfully');
            } else {
                this.showAlert('danger', 'Failed to save preset: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            this.logExternal('Failed to save preset: ' + error.message, 'error');
            this.showAlert('danger', 'Failed to save preset: ' + error.message);
        }
    }

    /**
     * Detectar acciones AJAX de WordPress
     */
    async detectWordPressActions() {
        try {
            const result = await this.makeAjaxRequest('get_wordpress_ajax_actions');
            
            if (result.success && result.actions) {
                this.setupActionAutoComplete(result.actions);
                this.showAlert('info', `Found ${result.actions.length} WordPress AJAX actions`);
            }
        } catch (error) {
            this.logExternal('Failed to detect actions: ' + error.message, 'error');
            this.showAlert('warning', 'Failed to detect WordPress AJAX actions');
        }
    }

    /**
     * Configurar auto-completado para acciones
     */
    setupActionAutoComplete(actions) {
        const actionInput = document.getElementById('ajax-action');
        if (!actionInput) return;

        // Crear datalist para autocompletado
        let datalist = document.getElementById('ajax-actions-datalist');
        if (!datalist) {
            datalist = document.createElement('datalist');
            datalist.id = 'ajax-actions-datalist';
            actionInput.parentNode.appendChild(datalist);
            actionInput.setAttribute('list', 'ajax-actions-datalist');
        }

        datalist.innerHTML = actions.map(action => `<option value="${action}"></option>`).join('');
    }

    /**
     * Configurar auto-completado general
     */
    setupAutoComplete() {
        // Auto-completado básico para acciones comunes
        const commonActions = [
            'dev_tools_ajax',
            'heartbeat',
            'wp_ajax_nopriv_*',
            'admin_color_scheme_picker'
        ];

        this.setupActionAutoComplete(commonActions);
    }

    /**
     * Realizar petición AJAX usando el sistema dev-tools
     */
    async makeAjaxRequest(command, data = {}) {
        // Heredado de DevToolsBase (disponible globalmente)
        if (typeof window.devToolsApp !== 'undefined') {
            return await window.devToolsApp.makeAjaxRequest(command, data);
        }
        
        // Fallback manual
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            // Usar la acción dinámica desde la configuración (plugin-agnóstico)
            const ajaxAction = window.devToolsConfig?.ajaxAction || 
                              (window.devToolsConfig?.actionPrefix + '_dev_tools') || 
                              'dev_tools_ajax';
            formData.append('action', ajaxAction);
            formData.append('action_type', command);
            formData.append('nonce', window.devToolsConfig?.nonce || '');
            
            // Agregar datos individuales en lugar de JSON string
            Object.keys(data).forEach(key => {
                formData.append(key, data[key]);
            });

            fetch(window.devToolsConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(resolve)
            .catch(reject);
        });
    }

    /**
     * Mostrar alerta
     */
    showAlert(type, message) {
        // Usar sistema de alertas del dev-tools si está disponible
        if (typeof window.devToolsApp !== 'undefined') {
            window.devToolsApp.showAlert(type, message);
            return;
        }

        // Fallback simple
        console.log(`[${type.toUpperCase()}] ${message}`);
        alert(message);
    }

    /**
     * Log interno
     */
    logInternal(message) {
        if (typeof window.devToolsApp !== 'undefined') {
            window.devToolsApp.logInternal(message);
        } else {
            console.log('[DEV-TOOLS] ' + message);
        }
    }

    /**
     * Log externo
     */
    logExternal(message, level = 'info') {
        if (typeof window.devToolsApp !== 'undefined') {
            window.devToolsApp.logExternal(message, level);
        } else {
            console.log(`[${level.toUpperCase()}] ${message}`);
        }
    }
}

// Exportar la clase constructora inmediatamente
window.DevToolsAjaxTesterClass = DevToolsAjaxTester;

// Inicializar cuando esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Crear instancia global para uso directo
    window.DevToolsAjaxTester = new DevToolsAjaxTester();
    
    console.log('[DEV-TOOLS] AjaxTester module initialized');
    console.log('[DEV-TOOLS] DevToolsAjaxTesterClass available as constructor');
    console.log('[DEV-TOOLS] DevToolsAjaxTester available as instance');
});

// Export para uso modular
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DevToolsAjaxTester;
}
