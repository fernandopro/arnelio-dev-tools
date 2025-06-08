/**
 * Logs Module JavaScript
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

class DevToolsLogs {
    constructor() {
        this.isLoading = false;
        this.logs = [];
        this.sources = [];
        this.currentFilter = 'all';
        this.searchTerm = '';
        this.autoRefresh = false;
        this.refreshInterval = null;
        this.tailMode = false;
        
        this.init();
    }

    /**
     * Inicialización del módulo
     */
    init() {
        // Cargar logs iniciales
        this.loadLogs();
        
        // Cargar fuentes de logs
        this.loadLogSources();
        
        // Configurar manejadores de eventos
        this.setupEventHandlers();
        
        // Configurar auto-refresh si está habilitado
        this.setupAutoRefresh();

        this.logInternal('Logs module initialized');
    }

    /**
     * Configurar manejadores de eventos
     */
    setupEventHandlers() {
        // Filtros
        const filterSelect = document.getElementById('log-filter');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.currentFilter = e.target.value;
                this.filterLogs();
            });
        }

        // Búsqueda
        const searchInput = document.getElementById('log-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value;
                    this.filterLogs();
                }, 300);
            });
        }

        // Auto-refresh toggle
        const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                this.autoRefresh = e.target.checked;
                this.setupAutoRefresh();
            });
        }

        // Tail mode toggle
        const tailToggle = document.getElementById('tail-mode-toggle');
        if (tailToggle) {
            tailToggle.addEventListener('change', (e) => {
                this.tailMode = e.target.checked;
                if (this.tailMode) {
                    this.startTailing();
                } else {
                    this.stopTailing();
                }
            });
        }
    }

    /**
     * Cargar logs desde el servidor
     */
    async loadLogs() {
        if (this.isLoading) return;

        try {
            this.setLoadingState(true);
            
            const result = await this.makeAjaxRequest('get_logs', {
                source: this.currentFilter,
                search: this.searchTerm,
                limit: 1000
            });
            
            if (result.success) {
                this.logs = result.logs || [];
                this.updateLogsDisplay();
            } else {
                this.showAlert('danger', 'Failed to load logs: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            this.logExternal('Failed to load logs: ' + error.message, 'error');
            this.showAlert('danger', 'Failed to load logs: ' + error.message);
        } finally {
            this.setLoadingState(false);
        }
    }

    /**
     * Cargar fuentes de logs disponibles
     */
    async loadLogSources() {
        try {
            const result = await this.makeAjaxRequest('get_log_sources');
            
            if (result.success) {
                this.sources = result.sources || [];
                this.updateSourcesFilter();
            }
        } catch (error) {
            this.logExternal('Failed to load log sources: ' + error.message, 'error');
        }
    }

    /**
     * Actualizar el filtro de fuentes
     */
    updateSourcesFilter() {
        const filterSelect = document.getElementById('log-filter');
        if (!filterSelect) return;

        // Limpiar opciones existentes (excepto "All")
        const allOption = filterSelect.querySelector('option[value="all"]');
        filterSelect.innerHTML = '';
        if (allOption) {
            filterSelect.appendChild(allOption);
        } else {
            filterSelect.innerHTML = '<option value="all">All Sources</option>';
        }

        // Agregar fuentes disponibles
        this.sources.forEach(source => {
            const option = document.createElement('option');
            option.value = source.id;
            option.textContent = `${source.name} (${source.count})`;
            filterSelect.appendChild(option);
        });
    }

    /**
     * Actualizar visualización de logs
     */
    updateLogsDisplay() {
        const container = document.getElementById('logs-content');
        if (!container) return;

        if (this.logs.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4">No logs available</div>';
            return;
        }

        const filteredLogs = this.getFilteredLogs();
        
        if (filteredLogs.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4">No logs match the current filter</div>';
            return;
        }

        const logsHtml = filteredLogs.map(log => this.renderLogEntry(log)).join('');
        container.innerHTML = `
            <div class="logs-list">
                ${logsHtml}
            </div>
        `;

        // Auto-scroll al final si está en modo tail
        if (this.tailMode) {
            container.scrollTop = container.scrollHeight;
        }

        // Actualizar estadísticas
        this.updateLogStats(filteredLogs);
    }

    /**
     * Renderizar una entrada de log
     */
    renderLogEntry(log) {
        const levelClass = this.getLogLevelClass(log.level);
        const timestamp = new Date(log.timestamp).toLocaleString();
        
        return `
            <div class="log-entry border-bottom py-2" data-level="${log.level}" data-source="${log.source}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="log-content flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-${levelClass} me-2">${log.level.toUpperCase()}</span>
                            <small class="text-muted me-2">${timestamp}</small>
                            <small class="text-secondary">[${log.source}]</small>
                        </div>
                        <div class="log-message">
                            <pre class="mb-0">${this.escapeHtml(log.message)}</pre>
                        </div>
                        ${log.context ? `
                            <div class="log-context mt-1">
                                <button class="btn btn-link btn-sm p-0" onclick="DevToolsLogs.toggleContext(this)">
                                    <i class="fas fa-chevron-right"></i> Show Context
                                </button>
                                <div class="context-data d-none mt-2">
                                    <pre class="bg-light p-2 rounded" style="font-size: 0.8rem;">${this.escapeHtml(JSON.stringify(log.context, null, 2))}</pre>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="log-actions">
                        <button class="btn btn-outline-secondary btn-sm" onclick="DevToolsLogs.copyLogEntry(this)" title="Copy log entry">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Obtener clase CSS para el nivel de log
     */
    getLogLevelClass(level) {
        const levelMap = {
            emergency: 'danger',
            alert: 'danger',
            critical: 'danger',
            error: 'danger',
            warning: 'warning',
            notice: 'info',
            info: 'info',
            debug: 'secondary'
        };
        return levelMap[level?.toLowerCase()] || 'secondary';
    }

    /**
     * Obtener logs filtrados
     */
    getFilteredLogs() {
        let filtered = [...this.logs];

        // Filtrar por fuente
        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(log => log.source === this.currentFilter);
        }

        // Filtrar por búsqueda
        if (this.searchTerm) {
            const searchLower = this.searchTerm.toLowerCase();
            filtered = filtered.filter(log => 
                log.message.toLowerCase().includes(searchLower) ||
                log.source.toLowerCase().includes(searchLower) ||
                log.level.toLowerCase().includes(searchLower)
            );
        }

        return filtered;
    }

    /**
     * Actualizar estadísticas de logs
     */
    updateLogStats(logs) {
        const stats = {
            total: logs.length,
            error: 0,
            warning: 0,
            info: 0,
            debug: 0
        };

        logs.forEach(log => {
            const level = log.level?.toLowerCase();
            if (['emergency', 'alert', 'critical', 'error'].includes(level)) {
                stats.error++;
            } else if (level === 'warning') {
                stats.warning++;
            } else if (['notice', 'info'].includes(level)) {
                stats.info++;
            } else if (level === 'debug') {
                stats.debug++;
            }
        });

        // Actualizar elementos de estadísticas
        this.updateStatElement('total-logs', stats.total);
        this.updateStatElement('error-logs', stats.error);
        this.updateStatElement('warning-logs', stats.warning);
        this.updateStatElement('info-logs', stats.info);
        this.updateStatElement('debug-logs', stats.debug);
    }

    /**
     * Actualizar elemento de estadística
     */
    updateStatElement(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    /**
     * Filtrar logs
     */
    filterLogs() {
        this.updateLogsDisplay();
    }

    /**
     * Refrescar logs
     */
    async refreshLogs() {
        await this.loadLogs();
        this.showAlert('success', 'Logs refreshed successfully');
    }

    /**
     * Limpiar logs
     */
    async clearLogs() {
        if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
            return;
        }

        try {
            const result = await this.makeAjaxRequest('clear_logs', {
                source: this.currentFilter === 'all' ? null : this.currentFilter
            });
            
            if (result.success) {
                this.logs = [];
                this.updateLogsDisplay();
                this.showAlert('success', 'Logs cleared successfully');
            } else {
                this.showAlert('danger', 'Failed to clear logs: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            this.logExternal('Failed to clear logs: ' + error.message, 'error');
            this.showAlert('danger', 'Failed to clear logs: ' + error.message);
        }
    }

    /**
     * Exportar logs
     */
    async exportLogs() {
        try {
            const result = await this.makeAjaxRequest('export_logs', {
                source: this.currentFilter === 'all' ? null : this.currentFilter,
                search: this.searchTerm,
                format: 'json'
            });
            
            if (result.success && result.data) {
                const filename = `dev-tools-logs-${new Date().toISOString().slice(0, 10)}.json`;
                this.downloadJsonFile(result.data, filename);
                this.showAlert('success', 'Logs exported successfully');
            } else {
                this.showAlert('danger', 'Failed to export logs: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            this.logExternal('Failed to export logs: ' + error.message, 'error');
            this.showAlert('danger', 'Failed to export logs: ' + error.message);
        }
    }

    /**
     * Configurar auto-refresh
     */
    setupAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }

        if (this.autoRefresh) {
            this.refreshInterval = setInterval(() => {
                this.loadLogs();
            }, 30000); // Refrescar cada 30 segundos
        }
    }

    /**
     * Iniciar modo tail
     */
    startTailing() {
        if (this.tailInterval) {
            clearInterval(this.tailInterval);
        }

        this.tailInterval = setInterval(async () => {
            await this.loadLogs();
        }, 5000); // Tail cada 5 segundos
    }

    /**
     * Detener modo tail
     */
    stopTailing() {
        if (this.tailInterval) {
            clearInterval(this.tailInterval);
            this.tailInterval = null;
        }
    }

    /**
     * Alternar contexto de un log
     */
    toggleContext(button) {
        const contextData = button.parentElement.querySelector('.context-data');
        const icon = button.querySelector('i');
        
        if (contextData.classList.contains('d-none')) {
            contextData.classList.remove('d-none');
            icon.className = 'fas fa-chevron-down';
            button.innerHTML = '<i class="fas fa-chevron-down"></i> Hide Context';
        } else {
            contextData.classList.add('d-none');
            icon.className = 'fas fa-chevron-right';
            button.innerHTML = '<i class="fas fa-chevron-right"></i> Show Context';
        }
    }

    /**
     * Copiar entrada de log
     */
    copyLogEntry(button) {
        const logEntry = button.closest('.log-entry');
        const message = logEntry.querySelector('.log-message pre').textContent;
        const timestamp = logEntry.querySelector('.text-muted').textContent;
        const level = logEntry.querySelector('.badge').textContent;
        
        const logText = `[${timestamp}] ${level}: ${message}`;
        
        navigator.clipboard.writeText(logText).then(() => {
            this.showAlert('success', 'Log entry copied to clipboard');
        }).catch(() => {
            this.showAlert('warning', 'Failed to copy to clipboard');
        });
    }

    /**
     * Configurar estado de loading
     */
    setLoadingState(loading) {
        this.isLoading = loading;
        
        const loadingElement = document.getElementById('logs-loading');
        const contentElement = document.getElementById('logs-content');
        
        if (loadingElement && contentElement) {
            if (loading) {
                loadingElement.classList.remove('d-none');
                contentElement.classList.add('d-none');
            } else {
                loadingElement.classList.add('d-none');
                contentElement.classList.remove('d-none');
            }
        }
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
            formData.append('action', 'dev_tools_ajax');
            formData.append('command', command);
            formData.append('data', JSON.stringify(data));
            formData.append('nonce', window.devToolsConfig?.nonce || '');

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
        // Crear alerta temporal
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Buscar contenedor de alertas o crear uno temporal
        let alertContainer = document.querySelector('.dev-tools-alerts');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.className = 'dev-tools-alerts position-fixed top-0 end-0 p-3';
            alertContainer.style.zIndex = '9999';
            document.body.appendChild(alertContainer);
        }
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            const alerts = alertContainer.querySelectorAll('.alert');
            if (alerts.length > 0) {
                alerts[0].remove();
            }
        }, 5000);
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
     * Log interno
     */
    logInternal(message) {
        if (window.devToolsConfig?.debug) {
            console.log('[DevTools Logs]', message);
        }
    }

    /**
     * Log externo
     */
    logExternal(message, level = 'info') {
        if (typeof window.devToolsApp !== 'undefined') {
            window.devToolsApp.logExternal(message, level);
        } else {
            console.log(`[DevTools Logs - ${level.toUpperCase()}]`, message);
        }
    }
}

// Inicializar cuando esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('logs-module')) {
        window.DevToolsLogs = new DevToolsLogs();
    }
});

// Export para uso modular
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DevToolsLogs;
}
