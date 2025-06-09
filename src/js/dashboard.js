/**
 * DevTools Dashboard JavaScript - Arquitectura 3.0
 * Manejo del panel principal y comunicación AJAX
 * 
 * @package DevTools\Assets\JavaScript
 * @version 3.0.0
 * @since 3.0.0
 */

class DevToolsDashboard {
    constructor() {
        // Buscar configuración en múltiples variables posibles
        this.config = window.devToolsConfig || 
                      window.tarokina_dev_tools_config || 
                      window.dev_tools_config || 
                      {};
        this.isInitialized = false;
        this.refreshInterval = null;
        this.logger = new DevToolsClientLogger();
    }

    /**
     * Inicializar dashboard - MODO MANUAL
     * No ejecuta cargas automáticas para evitar alerts no deseadas
     */
    init() {
        if (this.isInitialized) {
            return;
        }

        this.logger.logInternal('Initializing DevTools Dashboard - MANUAL MODE');

        try {
            this.validateConfig();
            this.bindEvents();
            // ELIMINADO: this.loadInitialData(); // No cargar automáticamente
            // ELIMINADO: this.startAutoRefresh(); // No iniciar auto-refresh
            
            this.isInitialized = true;
            this.logger.logExternal('Dashboard initialized successfully (manual mode)', 'success');
            
        } catch (error) {
            this.logger.logError('Dashboard initialization failed', error);
            this.showAlert('Error al inicializar dashboard: ' + error.message, 'danger');
        }
    }

    /**
     * Validar configuración
     */
    validateConfig() {
        if (!this.config.ajaxUrl) {
            throw new Error('AJAX URL not configured');
        }
        if (!this.config.nonce) {
            throw new Error('Security nonce not available');
        }
        if (!this.config.actionPrefix) {
            throw new Error('Action prefix not configured');
        }

        this.logger.logInternal('Configuration validated');
    }

    /**
     * Vincular eventos del DOM
     */
    bindEvents() {
        // Botones de acción rápida
        this.bindQuickActions();
        
        // Toggles de módulos
        this.bindModuleToggles();
        
        // Refresh automático
        this.bindRefreshControls();

        this.logger.logInternal('Events bound successfully');
    }

    /**
     * Vincular acciones rápidas
     */
    bindQuickActions() {
        const actions = {
            'btn-test-system': () => this.runSystemTest(),
            'btn-clear-cache': () => this.clearCache(),
            'btn-refresh-data': () => this.refreshData(),
            'btn-export-logs': () => this.exportLogs()
        };

        Object.entries(actions).forEach(([id, handler]) => {
            const button = document.getElementById(id);
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.executeAction(handler, button);
                });
            }
        });
    }

    /**
     * Vincular toggles de módulos
     */
    bindModuleToggles() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('toggle-module')) {
                e.preventDefault();
                const moduleName = e.target.dataset.module;
                this.toggleModule(moduleName, e.target);
            }
        });
    }

    /**
     * Vincular controles de refresh
     */
    bindRefreshControls() {
        // Auto refresh cada 30 segundos por defecto
        // Los usuarios pueden deshabilitarlo si quieren
    }

    /**
     * Cargar datos iniciales
     */
    async loadInitialData() {
        try {
            this.showLoading(true);
            
            const data = await this.makeAjaxRequest('dashboard_get_stats');
            this.updateDashboardData(data);
            
            this.showLoading(false);
            
        } catch (error) {
            this.logger.logError('Failed to load initial data', error);
            this.showAlert('Error al cargar datos iniciales', 'warning');
            this.showLoading(false);
        }
    }

    /**
     * Ejecutar test del sistema
     */
    async runSystemTest() {
        try {
            // Inicializar si no está listo
            if (!this.isInitialized) {
                this.init();
            }
            
            this.showAlert('Ejecutando test del sistema...', 'info');
            
            const testData = await this.makeAjaxRequest('run_test', {
                test_type: 'basic'
            });
            
            this.displayTestResults(testData);
            
        } catch (error) {
            this.logger.logError('System test failed', error);
            this.showAlert('Error en test del sistema: ' + error.message, 'danger');
        }
    }

    /**
     * Limpiar cache
     */
    async clearCache() {
        try {
            // Inicializar si no está listo
            if (!this.isInitialized) {
                this.init();
            }
            
            this.showAlert('Limpiando cache...', 'info');
            
            const result = await this.makeAjaxRequest('clear_cache');
            
            this.showAlert(
                `Cache limpiado. Transients eliminados: ${result.transients_cleared}`,
                'success'
            );
            
            // Refrescar datos después de limpiar cache
            setTimeout(() => this.refreshData(), 1000);
            
        } catch (error) {
            this.logger.logError('Cache clear failed', error);
            this.showAlert('Error al limpiar cache: ' + error.message, 'danger');
        }
    }

    /**
     * Refrescar datos
     */
    async refreshData() {
        try {
            const data = await this.makeAjaxRequest('dashboard_refresh_data');
            this.updateDashboardData(data);
            this.showAlert('Datos actualizados', 'success');
            
        } catch (error) {
            this.logger.logError('Data refresh failed', error);
            this.showAlert('Error al actualizar datos', 'warning');
        }
    }

    /**
     * Exportar logs
     */
    async exportLogs() {
        try {
            // Inicializar si no está listo
            if (!this.isInitialized) {
                this.init();
            }
            
            this.showAlert('Preparando exportación de logs...', 'info');
            
            // Por ahora simular la exportación
            // En el futuro esto será una funcionalidad real
            const logsData = {
                timestamp: new Date().toISOString(),
                system_info: await this.makeAjaxRequest('get_system_info'),
                modules_status: await this.makeAjaxRequest('dashboard_get_modules'),
                browser_info: this.getBrowserInfo()
            };
            
            this.downloadJsonFile(logsData, 'dev-tools-logs.json');
            this.showAlert('Logs exportados', 'success');
            
        } catch (error) {
            this.logger.logError('Log export failed', error);
            this.showAlert('Error al exportar logs', 'warning');
        }
    }

    /**
     * Toggle módulo
     */
    async toggleModule(moduleName, button) {
        if (!moduleName) {
            return;
        }

        const originalText = button.textContent;
        
        try {
            button.disabled = true;
            button.textContent = 'Procesando...';
            
            const result = await this.makeAjaxRequest('dashboard_toggle_module', {
                module_name: moduleName
            });
            
            // Actualizar estado del módulo en la UI
            this.updateModuleStatus(result.new_status);
            
            this.showAlert(
                `Módulo ${moduleName} ${result.action === 'enabled' ? 'activado' : 'desactivado'}`,
                'success'
            );
            
        } catch (error) {
            this.logger.logError('Module toggle failed', error);
            this.showAlert('Error al cambiar estado del módulo', 'danger');
            button.textContent = originalText;
        } finally {
            button.disabled = false;
        }
    }

    /**
     * Actualizar datos del dashboard
     */
    updateDashboardData(data) {
        if (data.system_info) {
            this.updateSystemInfo(data.system_info);
        }
        
        if (data.modules_status) {
            this.updateModuleStatus(data.modules_status);
        }
        
        if (data.recent_activity) {
            this.updateRecentActivity(data.recent_activity);
        }

        this.logger.logInternal('Dashboard data updated');
    }

    /**
     * Actualizar información del sistema
     */
    updateSystemInfo(systemInfo) {
        // Actualizar cards de estadísticas
        const memoryCard = document.querySelector('.card-body .text-warning + .display-6');
        if (memoryCard && systemInfo.memory) {
            memoryCard.textContent = systemInfo.memory.current;
        }

        // Actualizar información detallada
        const infoElements = {
            'memory_peak': systemInfo.memory?.peak,
            'debug_status': systemInfo.debug ? 'Activado' : 'Desactivado'
        };

        Object.entries(infoElements).forEach(([key, value]) => {
            const element = document.querySelector(`[data-info="${key}"]`);
            if (element && value !== undefined) {
                element.textContent = value;
            }
        });
    }

    /**
     * Actualizar estado de módulos
     */
    updateModuleStatus(modulesStatus) {
        const modulesContainer = document.getElementById('modules-status');
        if (!modulesContainer || !modulesStatus.all) {
            return;
        }

        // Actualizar contador en card
        const activeCountCard = document.querySelector('.card-body .text-info + .display-6');
        if (activeCountCard) {
            activeCountCard.textContent = modulesStatus.active_count;
        }

        // Actualizar lista de módulos
        const modulesList = modulesContainer.querySelector('.list-group');
        if (modulesList) {
            Object.entries(modulesStatus.all).forEach(([name, module]) => {
                const moduleElement = modulesList.querySelector(`[data-module="${name}"]`);
                if (moduleElement) {
                    const badge = moduleElement.closest('.list-group-item').querySelector('.badge');
                    const button = moduleElement;
                    
                    if (badge) {
                        badge.className = `badge me-2 bg-${module.active ? 'success' : 'secondary'}`;
                        badge.textContent = module.active ? 'Activo' : 'Inactivo';
                    }
                    
                    button.textContent = module.active ? 'Desactivar' : 'Activar';
                }
            });
        }
    }

    /**
     * Actualizar actividad reciente
     */
    updateRecentActivity(activity) {
        const activityContainer = document.getElementById('recent-activity');
        if (!activityContainer || !Array.isArray(activity)) {
            return;
        }

        // Por ahora no actualizamos automáticamente la actividad
        // ya que puede interferir con la lectura del usuario
        this.logger.logInternal('Recent activity data received', activity);
    }

    /**
     * Mostrar resultados de test
     */
    displayTestResults(testData) {
        const successRate = testData.success_rate || 0;
        const alertType = successRate >= 90 ? 'success' : 
                         successRate >= 70 ? 'warning' : 'danger';
        
        let message = `Test completado: ${testData.passed_tests}/${testData.total_tests} exitosos (${successRate}%)`;
        
        if (testData.tests) {
            const failedTests = Object.entries(testData.tests)
                .filter(([_, test]) => !test.passed)
                .map(([name, test]) => test.name || name);
            
            if (failedTests.length > 0) {
                message += `\nTests fallidos: ${failedTests.join(', ')}`;
            }
        }
        
        this.showAlert(message, alertType);
    }

    /**
     * Realizar petición AJAX
     */
    async makeAjaxRequest(action, additionalData = {}) {
        const formData = new FormData();
        formData.append('action', `${this.config.actionPrefix}_dev_tools`);
        formData.append('action_type', action);
        formData.append('nonce', this.config.nonce);
        
        // Agregar datos adicionales
        Object.entries(additionalData).forEach(([key, value]) => {
            formData.append(key, value);
        });

        this.logger.logInternal(`Making AJAX request: ${action}`, additionalData);

        const response = await fetch(this.config.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.data?.message || 'Request failed');
        }

        this.logger.logInternal(`AJAX response received: ${action}`, data.data);
        return data.data;
    }

    /**
     * Ejecutar acción con manejo de errores
     */
    async executeAction(actionHandler, button) {
        const originalText = button?.textContent;
        
        try {
            if (button) {
                button.disabled = true;
                button.textContent = 'Procesando...';
            }
            
            await actionHandler();
            
        } catch (error) {
            this.logger.logError('Action execution failed', error);
            this.showAlert('Error al ejecutar acción: ' + error.message, 'danger');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    }

    /**
     * Mostrar alerta
     */
    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            console.log(`Alert [${type}]: ${message}`);
            return;
        }

        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${this.escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        alertContainer.insertAdjacentHTML('beforeend', alertHtml);

        // Auto-dismiss después de 5 segundos
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }

    /**
     * Mostrar/ocultar loading
     */
    showLoading(show) {
        // Por ahora simplemente log, en el futuro añadir spinner
        this.logger.logInternal(`Loading state: ${show ? 'shown' : 'hidden'}`);
    }

    /**
     * Iniciar auto-refresh
     */
    startAutoRefresh() {
        // Auto-refresh cada 30 segundos
        this.refreshInterval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.refreshData().catch(() => {
                    // Ignorar errores silenciosamente en auto-refresh
                });
            }
        }, 30000);
    }

    /**
     * Detener auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * Obtener información del navegador
     */
    getBrowserInfo() {
        return {
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth
            },
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            }
        };
    }

    /**
     * Descargar archivo JSON
     */
    downloadJsonFile(data, filename) {
        const jsonStr = JSON.stringify(data, null, 2);
        const blob = new Blob([jsonStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Destructor
     */
    destroy() {
        this.stopAutoRefresh();
        this.isInitialized = false;
        this.logger.logInternal('Dashboard destroyed');
    }
}

/**
 * Logger del lado cliente
 */
class DevToolsClientLogger {
    constructor() {
        this.isVerbose = this.detectVerboseMode();
        this.isDebug = this.detectDebugMode();
    }

    detectVerboseMode() {
        return (window.devToolsConfig?.debug) || 
               (localStorage.getItem('devtools_verbose') === 'true') ||
               (sessionStorage.getItem('devtools_verbose') === 'true');
    }

    detectDebugMode() {
        return window.devToolsConfig?.debug || false;
    }

    logInternal(message, data = null) {
        if (this.isDebug) {
            console.debug('[DEV-TOOLS-INTERNAL]', message, data);
        }
    }

    logExternal(message, type = 'info') {
        if (this.isVerbose || this.isDebug) {
            const method = type === 'error' ? 'error' : 
                          type === 'warning' ? 'warn' : 'log';
            console[method](`[DEV-TOOLS-${type.toUpperCase()}]`, message);
        }
    }

    logError(message, error = null) {
        console.error('[DEV-TOOLS-ERROR]', message, error);
    }
}

// Inicialización automática cuando se incluye el script
if (typeof window !== 'undefined') {
    window.DevToolsDashboard = DevToolsDashboard;
    window.DevToolsClientLogger = DevToolsClientLogger;
}
