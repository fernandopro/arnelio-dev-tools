/**
 * Cache Module JavaScript
 * 
 * @package DevTools
 * @subpackage Modules
 * @since 3.0
 */

class DevToolsCache {
    constructor() {
        this.isLoading = false;
        this.data = null;
        this.transients = [];
        this.filteredTransients = [];
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

        // Configurar auto-refresh cada 3 minutos
        this.setupAutoRefresh();

        this.logInternal('Cache module initialized');
    }

    /**
     * Configura auto-refresh
     */
    setupAutoRefresh() {
        this.autoRefreshInterval = setInterval(() => {
            if (!this.isLoading) {
                this.refreshStats(true); // silent refresh
            }
        }, 3 * 60 * 1000); // 3 minutos
    }

    /**
     * Carga estadísticas de caché
     */
    async loadData() {
        if (this.isLoading) return;

        this.showLoading();
        this.logInternal('Loading cache statistics');

        try {
            const response = await this.makeRequest('get_cache_stats');
            
            if (response.success) {
                this.data = response.data;
                this.renderCacheStats(response.data);
                await this.loadTransients();
                this.hideLoading();
                this.logExternal('Cache statistics loaded successfully');
            } else {
                throw new Error(response.message || 'Error cargando estadísticas de caché');
            }
        } catch (error) {
            this.logExternal(`Error loading cache stats: ${error.message}`, 'error');
            this.showError('Error al cargar estadísticas de caché: ' + error.message);
            this.hideLoading();
        }
    }

    /**
     * Refresca estadísticas
     */
    async refreshStats(silent = false) {
        if (!silent) {
            this.showAlert('Actualizando estadísticas de caché...', 'info');
        }
        
        await this.loadData();
        
        if (!silent) {
            this.showAlert('Estadísticas de caché actualizadas', 'success');
        }
    }

    /**
     * Limpia toda la caché
     */
    async clearAllCache() {
        if (this.isLoading) return;

        const confirmed = confirm('¿Estás seguro de que quieres limpiar toda la caché? Esto puede afectar el rendimiento temporalmente.');
        if (!confirmed) return;

        this.showAlert('Limpiando toda la caché...', 'warning');
        this.logInternal('Clearing all cache');

        try {
            const response = await this.makeRequest('clear_cache');
            
            if (response.success) {
                this.showAlert('Toda la caché limpiada exitosamente', 'success');
                this.logExternal('All cache cleared successfully');
                await this.refreshStats(true);
            } else {
                throw new Error(response.message || 'Error limpiando caché');
            }
        } catch (error) {
            this.logExternal(`Error clearing cache: ${error.message}`, 'error');
            this.showAlert('Error al limpiar caché: ' + error.message, 'danger');
        }
    }

    /**
     * Limpia transients
     */
    async clearTransients() {
        if (this.isLoading) return;

        const confirmed = confirm('¿Estás seguro de que quieres limpiar todos los transients?');
        if (!confirmed) return;

        this.showAlert('Limpiando transients...', 'warning');
        this.logInternal('Clearing transients');

        try {
            const response = await this.makeRequest('clear_transients');
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                this.logExternal('Transients cleared successfully');
                await this.refreshStats(true);
                await this.refreshTransients();
            } else {
                throw new Error(response.message || 'Error limpiando transients');
            }
        } catch (error) {
            this.logExternal(`Error clearing transients: ${error.message}`, 'error');
            this.showAlert('Error al limpiar transients: ' + error.message, 'danger');
        }
    }

    /**
     * Limpia object cache
     */
    async clearObjectCache() {
        if (this.isLoading) return;

        this.showAlert('Limpiando object cache...', 'info');
        this.logInternal('Clearing object cache');

        try {
            const response = await this.makeRequest('clear_object_cache');
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                this.logExternal('Object cache cleared successfully');
                await this.refreshStats(true);
            } else {
                throw new Error(response.message || 'Error limpiando object cache');
            }
        } catch (error) {
            this.logExternal(`Error clearing object cache: ${error.message}`, 'error');
            this.showAlert('Error al limpiar object cache: ' + error.message, 'danger');
        }
    }

    /**
     * Carga lista de transients
     */
    async loadTransients() {
        this.logInternal('Loading transients list');

        try {
            const response = await this.makeRequest('get_transients');
            
            if (response.success) {
                this.transients = response.data;
                this.filteredTransients = [...this.transients];
                this.renderTransients();
                this.logExternal('Transients list loaded successfully');
            } else {
                throw new Error(response.message || 'Error cargando transients');
            }
        } catch (error) {
            this.logExternal(`Error loading transients: ${error.message}`, 'error');
            this.showAlert('Error al cargar transients: ' + error.message, 'danger');
        }
    }

    /**
     * Refresca lista de transients
     */
    async refreshTransients() {
        await this.loadTransients();
        this.filterTransients(); // Re-aplicar filtros
    }

    /**
     * Elimina transient específico
     */
    async deleteTransient(name) {
        if (this.isLoading) return;

        const confirmed = confirm(`¿Estás seguro de que quieres eliminar el transient '${name}'?`);
        if (!confirmed) return;

        this.showAlert(`Eliminando transient '${name}'...`, 'warning');
        this.logInternal('Deleting transient: ' + name);

        try {
            const response = await this.makeRequest('delete_transient', { transient_name: name });
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                this.logExternal('Transient deleted: ' + name);
                await this.refreshTransients();
                await this.refreshStats(true);
            } else {
                throw new Error(response.message || 'Error eliminando transient');
            }
        } catch (error) {
            this.logExternal(`Error deleting transient: ${error.message}`, 'error');
            this.showAlert('Error al eliminar transient: ' + error.message, 'danger');
        }
    }

    /**
     * Muestra modal para agregar transient
     */
    showAddTransient() {
        const modal = new bootstrap.Modal(document.getElementById('addTransientModal'));
        modal.show();
        
        // Limpiar formulario
        document.getElementById('add-transient-form').reset();
        document.getElementById('transient-value').value = '{"example": "value"}';
    }

    /**
     * Guarda nuevo transient
     */
    async saveTransient() {
        const form = document.getElementById('add-transient-form');
        const formData = new FormData(form);
        
        const name = formData.get('transient-name') || document.getElementById('transient-name').value;
        const value = formData.get('transient-value') || document.getElementById('transient-value').value;
        const expiration = formData.get('transient-expiration') || document.getElementById('transient-expiration').value;

        if (!name || !value) {
            this.showAlert('Nombre y valor son requeridos', 'danger');
            return;
        }

        this.showAlert('Guardando transient...', 'info');
        this.logInternal('Saving transient: ' + name);

        try {
            const response = await this.makeRequest('set_transient', {
                transient_name: name,
                transient_value: value,
                expiration: expiration
            });
            
            if (response.success) {
                this.showAlert('Transient guardado exitosamente', 'success');
                this.logExternal('Transient saved: ' + name);
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addTransientModal'));
                modal.hide();
                
                await this.refreshTransients();
                await this.refreshStats(true);
            } else {
                throw new Error(response.message || 'Error guardando transient');
            }
        } catch (error) {
            this.logExternal(`Error saving transient: ${error.message}`, 'error');
            this.showAlert('Error al guardar transient: ' + error.message, 'danger');
        }
    }

    /**
     * Filtra transients según criterios
     */
    filterTransients() {
        const search = document.getElementById('transient-search')?.value.toLowerCase() || '';
        const filter = document.getElementById('transient-filter')?.value || '';
        const showExpired = document.getElementById('show-expired')?.checked || false;

        this.filteredTransients = this.transients.filter(transient => {
            // Filtro de búsqueda
            if (search && !transient.name.toLowerCase().includes(search)) {
                return false;
            }

            // Filtro por tipo
            if (filter) {
                switch (filter) {
                    case 'expired':
                        if (!transient.is_expired) return false;
                        break;
                    case 'permanent':
                        if (transient.expiration) return false;
                        break;
                    case 'dev_tools':
                        if (!transient.name.includes('dev_tools')) return false;
                        break;
                }
            }

            // Mostrar/ocultar expirados
            if (!showExpired && transient.is_expired) {
                return false;
            }

            return true;
        });

        this.renderTransients();
    }

    /**
     * Analiza tamaño de caché
     */
    async analyzeSize() {
        const analysisDiv = document.getElementById('cache-analysis');
        if (!this.data) return;

        // Mostrar análisis existente
        analysisDiv.style.display = 'block';
        
        const content = document.getElementById('cache-analysis-content');
        const { size_analysis, transients, options } = this.data;

        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Cache Size Breakdown</h6>
                    <canvas id="cache-size-chart" width="300" height="200"></canvas>
                </div>
                <div class="col-md-6">
                    <h6>Recommendations</h6>
                    <div class="alert alert-info">
        `;

        // Generar recomendaciones
        if (transients.expired > 0) {
            html += `<p><i class="fas fa-exclamation-triangle text-warning"></i> 
                     Tienes ${transients.expired} transients expirados que puedes limpiar.</p>`;
        }

        if (size_analysis.breakdown.autoload_options > 1000000) { // 1MB
            html += `<p><i class="fas fa-exclamation-triangle text-warning"></i> 
                     Las opciones autoload son muy grandes (${size_format(size_analysis.breakdown.autoload_options)}). 
                     Considera optimizarlas.</p>`;
        }

        if (transients.total > 100) {
            html += `<p><i class="fas fa-info-circle text-info"></i> 
                     Tienes ${transients.total} transients. Considera limpiar los innecesarios regularmente.</p>`;
        }

        html += `
                    </div>
                    
                    <h6>Largest Autoload Options</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Option</th><th>Size</th></tr>
                            </thead>
                            <tbody>
        `;

        options.largest_options.slice(0, 10).forEach(option => {
            html += `
                <tr>
                    <td><code>${option.option_name}</code></td>
                    <td><span class="badge bg-secondary">${this.formatBytes(option.size)}</span></td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        content.innerHTML = html;

        // Crear gráfico simple con CSS (sin Chart.js para simplicidad)
        this.createSizeChart(size_analysis.breakdown);
    }

    /**
     * Crea gráfico de tamaño (simple con CSS)
     */
    createSizeChart(breakdown) {
        const canvas = document.getElementById('cache-size-chart');
        if (!canvas) return;

        const total = breakdown.transients + breakdown.autoload_options;
        const transientsPercent = (breakdown.transients / total) * 100;
        const optionsPercent = (breakdown.autoload_options / total) * 100;

        // Reemplazar canvas con div para gráfico CSS simple
        canvas.outerHTML = `
            <div class="cache-chart">
                <div class="chart-legend mb-2">
                    <span class="badge bg-primary me-2">Transients (${transientsPercent.toFixed(1)}%)</span>
                    <span class="badge bg-success">Autoload Options (${optionsPercent.toFixed(1)}%)</span>
                </div>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-primary" style="width: ${transientsPercent}%">${this.formatBytes(breakdown.transients)}</div>
                    <div class="progress-bar bg-success" style="width: ${optionsPercent}%">${this.formatBytes(breakdown.autoload_options)}</div>
                </div>
                <div class="text-center mt-2">
                    <strong>Total: ${this.formatBytes(total)}</strong>
                </div>
            </div>
        `;
    }

    /**
     * Muestra opciones autoload
     */
    viewOptions() {
        if (!this.data || !this.data.options) return;

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Autoload Options</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Option Name</th>
                                        <th>Size</th>
                                        <th>% of Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${this.data.options.largest_options.map(option => `
                                        <tr>
                                            <td><code>${option.option_name}</code></td>
                                            <td><span class="badge bg-secondary">${this.formatBytes(option.size)}</span></td>
                                            <td>${((option.size / this.data.options.autoload_size) * 100).toFixed(2)}%</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <strong>Total Autoload Size: ${this.data.options.autoload_size_formatted}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Limpiar cuando se cierre
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    /**
     * Renderiza estadísticas de caché
     */
    renderCacheStats(data) {
        // Transients count
        const transientsCount = document.getElementById('transients-count');
        if (transientsCount) {
            transientsCount.textContent = data.transients.total;
            if (data.transients.expired > 0) {
                transientsCount.innerHTML += ` <small class="text-warning">(${data.transients.expired} expired)</small>`;
            }
        }

        // Object cache status
        const objectCacheStatus = document.getElementById('object-cache-status');
        if (objectCacheStatus) {
            objectCacheStatus.textContent = data.object_cache.status;
            objectCacheStatus.className = data.object_cache.status === 'Active' ? 'text-success' : 'text-warning';
        }

        // Autoload options
        const autoloadOptions = document.getElementById('autoload-options');
        if (autoloadOptions) {
            autoloadOptions.textContent = data.options.autoload_count;
        }

        // Total cache size
        const totalCacheSize = document.getElementById('total-cache-size');
        if (totalCacheSize) {
            totalCacheSize.textContent = data.size_analysis.total_size_formatted;
        }
    }

    /**
     * Renderiza tabla de transients
     */
    renderTransients() {
        const tbody = document.getElementById('transients-list');
        if (!tbody) return;

        if (this.filteredTransients.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        No se encontraron transients con los filtros aplicados
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredTransients.map(transient => {
            const expirationClass = transient.is_expired ? 'text-danger' : (transient.expiration ? 'text-muted' : 'text-success');
            const expirationText = transient.is_expired ? 'EXPIRED' : transient.expiration_formatted;
            
            return `
                <tr ${transient.is_expired ? 'class="table-warning"' : ''}>
                    <td>
                        <strong>${transient.name}</strong>
                        ${transient.name.includes('dev_tools') ? '<span class="badge bg-primary ms-1">Dev-Tools</span>' : ''}
                        <br>
                        <small class="text-muted">${transient.value_preview}</small>
                    </td>
                    <td class="${expirationClass}">
                        <small>${expirationText}</small>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${transient.size_formatted}</span>
                    </td>
                    <td>
                        <button class="btn btn-outline-danger btn-sm" 
                                onclick="DevToolsCache.deleteTransient('${transient.name}')"
                                title="Delete transient">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * Formatea bytes
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Muestra estado de carga
     */
    showLoading() {
        this.isLoading = true;
        const loading = document.getElementById('cache-loading');
        const content = document.getElementById('cache-content');
        
        if (loading) loading.classList.remove('d-none');
        if (content) content.classList.add('d-none');
    }

    /**
     * Oculta estado de carga
     */
    hideLoading() {
        this.isLoading = false;
        const loading = document.getElementById('cache-loading');
        const content = document.getElementById('cache-content');
        
        if (loading) loading.classList.add('d-none');
        if (content) content.classList.remove('d-none');
    }

    /**
     * Muestra error
     */
    showError(message) {
        const content = document.getElementById('cache-content');
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
            console.log(`[CACHE-${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Log interno (siempre silencioso)
     */
    logInternal(message, data = null) {
        console.debug('[CACHE-INTERNAL]', message, data);
    }

    /**
     * Log externo (condicional)
     */
    logExternal(message, type = 'info') {
        const config = window.tkn_dev_tools_config;
        if (config?.verbose || localStorage.getItem('devtools_verbose') === 'true') {
            console.log(`[CACHE-${type.toUpperCase()}]`, message);
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
        this.logInternal('Cache module destroyed');
    }
}

// Instanciar cuando se carga el módulo
let devToolsCache;

// Auto-inicializar cuando esté disponible el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        devToolsCache = new DevToolsCache();
    });
} else {
    devToolsCache = new DevToolsCache();
}

// Exponer métodos estáticos para uso desde HTML
window.DevToolsCache = {
    refreshStats: () => devToolsCache?.refreshStats(),
    clearAllCache: () => devToolsCache?.clearAllCache(),
    clearTransients: () => devToolsCache?.clearTransients(),
    clearObjectCache: () => devToolsCache?.clearObjectCache(),
    deleteTransient: (name) => devToolsCache?.deleteTransient(name),
    showAddTransient: () => devToolsCache?.showAddTransient(),
    saveTransient: () => devToolsCache?.saveTransient(),
    filterTransients: () => devToolsCache?.filterTransients(),
    analyzeSize: () => devToolsCache?.analyzeSize(),
    viewOptions: () => devToolsCache?.viewOptions(),
    refreshTransients: () => devToolsCache?.refreshTransients()
};
