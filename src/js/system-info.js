/**
 * SystemInfo Module JavaScript
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

class DevToolsSystemInfo {
    constructor() {
        this.isLoading = false;
        this.data = null;
        this.autoRefreshInterval = null;
        
        this.init();
    }

    /**
     * Inicialización del módulo
     */
    init() {
        // Auto-cargar datos al inicializar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.loadData());
        } else {
            this.loadData();
        }

        // Configurar auto-refresh cada 5 minutos
        this.setupAutoRefresh();

        this.logInternal('SystemInfo module initialized');
    }

    /**
     * Configura auto-refresh
     */
    setupAutoRefresh() {
        this.autoRefreshInterval = setInterval(() => {
            if (!this.isLoading) {
                this.refreshData(true); // silent refresh
            }
        }, 5 * 60 * 1000); // 5 minutos
    }

    /**
     * Carga datos del sistema
     */
    async loadData() {
        if (this.isLoading) return;

        this.showLoading();
        this.logInternal('Loading system information');

        try {
            const response = await this.makeRequest('get_system_info');
            
            if (response.success) {
                this.data = response.data;
                this.renderSystemInfo(response.data);
                this.hideLoading();
                this.logExternal('System information loaded successfully');
            } else {
                throw new Error(response.message || 'Error cargando información del sistema');
            }
        } catch (error) {
            this.logExternal(`Error loading system info: ${error.message}`, 'error');
            this.showError('Error al cargar información del sistema: ' + error.message);
            this.hideLoading();
        }
    }

    /**
     * Refresca datos
     */
    async refreshData(silent = false) {
        if (!silent) {
            this.showAlert('Actualizando información del sistema...', 'info');
        }
        
        await this.loadData();
        
        if (!silent) {
            this.showAlert('Información del sistema actualizada', 'success');
        }
    }

    /**
     * Exporta información del sistema
     */
    async exportInfo() {
        if (this.isLoading) return;

        this.showAlert('Preparando exportación...', 'info');
        this.logInternal('Exporting system information');

        try {
            const response = await this.makeRequest('export_system_info');
            
            if (response.success) {
                this.downloadFile(response.data.filename, response.data.content);
                this.showAlert(`Información exportada: ${response.data.filename}`, 'success');
                this.logExternal('System info exported successfully');
            } else {
                throw new Error(response.message || 'Error exportando información');
            }
        } catch (error) {
            this.logExternal(`Error exporting system info: ${error.message}`, 'error');
            this.showAlert('Error al exportar información: ' + error.message, 'danger');
        }
    }

    /**
     * Ejecuta diagnóstico del sistema
     */
    async runDiagnostic() {
        if (this.isLoading) return;

        this.showAlert('Ejecutando diagnóstico del sistema...', 'info');
        this.logInternal('Running system diagnostic');

        try {
            const response = await this.makeRequest('run_diagnostic');
            
            if (response.success) {
                this.renderDiagnostic(response.data);
                this.showAlert('Diagnóstico completado', 'success');
                this.logExternal('System diagnostic completed successfully');
            } else {
                throw new Error(response.message || 'Error ejecutando diagnóstico');
            }
        } catch (error) {
            this.logExternal(`Error running diagnostic: ${error.message}`, 'error');
            this.showAlert('Error al ejecutar diagnóstico: ' + error.message, 'danger');
        }
    }

    /**
     * Renderiza información del sistema
     */
    renderSystemInfo(data) {
        // WordPress Info
        this.renderSection('wordpress-info', data.wordpress, {
            'Version': 'version',
            'Multisite': 'multisite',
            'Site URL': 'site_url',
            'Language': 'language',
            'Timezone': 'timezone',
            'Users': 'users_count',
            'Posts': 'posts_count',
            'Pages': 'pages_count'
        });

        // PHP Info
        this.renderSection('php-info', data.php, {
            'Version': 'version',
            'Memory Limit': 'memory_limit',
            'Max Execution Time': 'max_execution_time',
            'Max Input Vars': 'max_input_vars',
            'Post Max Size': 'post_max_size',
            'Upload Max Size': 'upload_max_filesize',
            'Display Errors': 'display_errors'
        });

        // Server Info
        this.renderSection('server-info', data.server, {
            'Software': 'software',
            'PHP SAPI': 'php_sapi',
            'Operating System': 'operating_system',
            'Architecture': 'architecture',
            'HTTPS': 'https',
            'Document Root': 'document_root'
        });

        // Database Info
        this.renderSection('database-info', data.database, {
            'Version': 'version',
            'Charset': 'charset',
            'Collate': 'collate',
            'Prefix': 'prefix',
            'Tables Count': 'tables_count',
            'Total Size': 'total_size'
        });

        // Plugins Info
        this.renderPluginsInfo(data.plugins);

        // Theme Info
        this.renderThemeInfo(data.theme);
    }

    /**
     * Renderiza una sección de información
     */
    renderSection(elementId, data, fields) {
        const element = document.getElementById(elementId);
        if (!element) return;

        let html = '<div class="row">';
        
        Object.entries(fields).forEach(([label, key]) => {
            const value = data[key];
            const displayValue = this.formatValue(value);
            
            html += `
                <div class="col-6 mb-2">
                    <small class="text-muted">${label}:</small><br>
                    <strong>${displayValue}</strong>
                </div>
            `;
        });
        
        html += '</div>';
        element.innerHTML = html;
    }

    /**
     * Renderiza información de plugins
     */
    renderPluginsInfo(data) {
        const element = document.getElementById('plugins-info');
        if (!element) return;

        let html = `
            <div class="row mb-3">
                <div class="col-4">
                    <small class="text-muted">Total Plugins:</small><br>
                    <strong>${data.total_count}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Active Plugins:</small><br>
                    <strong>${data.active_count}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Inactive:</small><br>
                    <strong>${data.total_count - data.active_count}</strong>
                </div>
            </div>
        `;

        // Lista de plugins activos (primeros 10)
        if (data.plugins && data.plugins.length > 0) {
            html += '<h6>Active Plugins:</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm">';
            html += '<thead><tr><th>Plugin</th><th>Version</th><th>Author</th></tr></thead>';
            html += '<tbody>';
            
            data.plugins
                .filter(plugin => plugin.active)
                .slice(0, 10)
                .forEach(plugin => {
                    html += `
                        <tr>
                            <td><strong>${plugin.name}</strong></td>
                            <td><span class="badge bg-secondary">${plugin.version}</span></td>
                            <td><small class="text-muted">${plugin.author}</small></td>
                        </tr>
                    `;
                });
            
            html += '</tbody></table>';
            html += '</div>';
            
            if (data.active_count > 10) {
                html += `<small class="text-muted">... y ${data.active_count - 10} plugins más</small>`;
            }
        }

        element.innerHTML = html;
    }

    /**
     * Renderiza información del tema
     */
    renderThemeInfo(data) {
        const element = document.getElementById('theme-info');
        if (!element) return;

        let html = `
            <div class="row">
                <div class="col-md-8">
                    <h6>${data.name}</h6>
                    <p class="text-muted mb-2">${data.description}</p>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Version:</small><br>
                            <strong>${data.version}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Author:</small><br>
                            <strong>${data.author}</strong>
                        </div>
                        <div class="col-6 mt-2">
                            <small class="text-muted">Template:</small><br>
                            <strong>${data.template}</strong>
                        </div>
                        <div class="col-6 mt-2">
                            <small class="text-muted">Stylesheet:</small><br>
                            <strong>${data.stylesheet}</strong>
                        </div>
                    </div>
                </div>
        `;

        if (data.screenshot) {
            html += `
                <div class="col-md-4">
                    <img src="${data.screenshot}" class="img-fluid rounded" alt="Theme Screenshot">
                </div>
            `;
        }

        html += '</div>';

        if (data.parent_theme) {
            html += `
                <div class="mt-3">
                    <small class="text-muted">Parent Theme:</small><br>
                    <strong>${data.parent_theme}</strong>
                </div>
            `;
        }

        element.innerHTML = html;
    }

    /**
     * Renderiza diagnóstico del sistema
     */
    renderDiagnostic(data) {
        const element = document.getElementById('diagnostic-content');
        if (!element) return;

        // Mostrar sección de diagnóstico
        document.getElementById('diagnostic-results').classList.remove('d-none');

        const { diagnostic, overall_status } = data;

        let html = `
            <div class="alert alert-${this.getBootstrapClass(overall_status.status)} mb-3">
                <h6 class="mb-1">
                    <i class="fas ${this.getStatusIcon(overall_status.status)} me-2"></i>
                    ${overall_status.message}
                </h6>
                <small>
                    ✅ ${overall_status.summary.good} Good | 
                    ⚠️ ${overall_status.summary.warning} Warnings | 
                    ❌ ${overall_status.summary.error} Errors
                </small>
            </div>
        `;

        html += '<div class="row">';
        
        Object.entries(diagnostic).forEach(([key, check]) => {
            const icon = this.getStatusIcon(check.status);
            const badgeClass = this.getBootstrapClass(check.status);
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card border-${badgeClass}">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas ${icon} me-2"></i>
                                ${this.formatCheckName(key)}
                                <span class="badge bg-${badgeClass} ms-2">${check.status}</span>
                            </h6>
                            <p class="card-text">${check.message}</p>
            `;
            
            // Información adicional según el tipo de check
            if (check.current !== undefined) {
                html += `<small class="text-muted">Current: <strong>${check.current}</strong></small>`;
            }
            if (check.recommended !== undefined) {
                html += `<br><small class="text-muted">Recommended: <strong>${check.recommended}</strong></small>`;
            }
            if (check.checks !== undefined) {
                html += '<br><small class="text-muted">Details:</small><ul class="small">';
                Object.entries(check.checks).forEach(([checkName, result]) => {
                    const checkIcon = result ? 'check text-success' : 'times text-danger';
                    html += `<li><i class="fas fa-${checkIcon}"></i> ${checkName}</li>`;
                });
                html += '</ul>';
            }
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';

        // Timestamp
        html += `
            <div class="text-end mt-3">
                <small class="text-muted">
                    Diagnóstico realizado: ${new Date(data.timestamp).toLocaleString()}
                </small>
            </div>
        `;

        element.innerHTML = html;
    }

    /**
     * Obtiene clase Bootstrap según estado
     */
    getBootstrapClass(status) {
        switch (status) {
            case 'good': return 'success';
            case 'warning': return 'warning';
            case 'error': return 'danger';
            default: return 'secondary';
        }
    }

    /**
     * Obtiene icono según estado
     */
    getStatusIcon(status) {
        switch (status) {
            case 'good': return 'fa-check-circle';
            case 'warning': return 'fa-exclamation-triangle';
            case 'error': return 'fa-times-circle';
            default: return 'fa-question-circle';
        }
    }

    /**
     * Formatea nombre de check
     */
    formatCheckName(key) {
        return key.replace(/_/g, ' ')
                 .replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Formatea valores para mostrar
     */
    formatValue(value) {
        if (value === null || value === undefined) {
            return '<em>N/A</em>';
        }
        
        if (typeof value === 'boolean') {
            return value ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>';
        }
        
        if (Array.isArray(value)) {
            return `<span class="badge bg-secondary">${value.length} items</span>`;
        }
        
        if (typeof value === 'string' && value.length > 50) {
            return `<span title="${value}">${value.substring(0, 50)}...</span>`;
        }
        
        return String(value);
    }

    /**
     * Descarga archivo
     */
    downloadFile(filename, content) {
        const blob = new Blob([content], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Muestra estado de carga
     */
    showLoading() {
        this.isLoading = true;
        const loading = document.getElementById('system-info-loading');
        const content = document.getElementById('system-info-content');
        
        if (loading) loading.classList.remove('d-none');
        if (content) content.classList.add('d-none');
    }

    /**
     * Oculta estado de carga
     */
    hideLoading() {
        this.isLoading = false;
        const loading = document.getElementById('system-info-loading');
        const content = document.getElementById('system-info-content');
        
        if (loading) loading.classList.add('d-none');
        if (content) content.classList.remove('d-none');
    }

    /**
     * Muestra error
     */
    showError(message) {
        const content = document.getElementById('system-info-content');
        if (content) {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Error</h6>
                    <p class="mb-0">${message}</p>
                </div>
            `;
            content.classList.remove('d-none');
        }
    }

    /**
     * Realiza petición AJAX
     */
    async makeRequest(action, data = {}) {
        const config = window.tkn_dev_tools_config;
        if (!config) {
            throw new Error('Configuración de dev-tools no disponible');
        }

        const formData = new FormData();
        formData.append('action', `${config.ajax_prefix}_dev_tools_ajax`);
        formData.append('nonce', config.nonce);
        formData.append('command', action);
        
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Muestra alerta
     */
    showAlert(message, type = 'info') {
        if (typeof DevToolsUI !== 'undefined' && DevToolsUI.showAlert) {
            DevToolsUI.showAlert(message, type);
        } else {
            console.log(`[SYSTEM-INFO-${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Log interno (siempre silencioso)
     */
    logInternal(message, data = null) {
        console.debug('[SYSTEM-INFO-INTERNAL]', message, data);
    }

    /**
     * Log externo (condicional)
     */
    logExternal(message, type = 'info') {
        const config = window.tkn_dev_tools_config;
        if (config?.verbose || localStorage.getItem('devtools_verbose') === 'true') {
            console.log(`[SYSTEM-INFO-${type.toUpperCase()}]`, message);
        }
    }

    /**
     * Destructor - limpia intervalos
     */
    destroy() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
        this.logInternal('SystemInfo module destroyed');
    }
}

// Instanciar cuando se carga el módulo
let devToolsSystemInfo;

// Auto-inicializar cuando esté disponible el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        devToolsSystemInfo = new DevToolsSystemInfo();
    });
} else {
    devToolsSystemInfo = new DevToolsSystemInfo();
}

// Exponer métodos estáticos para uso desde HTML
window.DevToolsSystemInfo = {
    refreshData: () => devToolsSystemInfo?.refreshData(),
    exportInfo: () => devToolsSystemInfo?.exportInfo(),
    runDiagnostic: () => devToolsSystemInfo?.runDiagnostic()
};
