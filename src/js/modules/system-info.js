/**
 * System Info Module - Dev-Tools Arquitectura 3.0
 * Módulo para información del sistema
 */

class SystemInfoModule {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupSystemInfo();
        });
    }
    
    setupSystemInfo() {
        console.log('🔧 SystemInfo: Module initialized');
        this.loadSystemInformation();
    }
    
    async loadSystemInformation() {
        try {
            // Usar el devTools global para hacer la petición
            if (window.devTools) {
                const result = await window.devTools.makeAjaxRequest('system_info');
                this.displaySystemInfo(result);
            }
        } catch (error) {
            console.error('Error loading system info:', error);
        }
    }
    
    displaySystemInfo(data) {
        const container = document.querySelector('#system-info .system-info-content');
        if (container && data) {
            container.innerHTML = this.formatSystemInfo(data);
        }
    }
    
    formatSystemInfo(data) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <h6>WordPress</h6>
                    <ul class="list-unstyled">
                        <li><strong>Version:</strong> ${data.wordpress?.version || 'N/A'}</li>
                        <li><strong>Site URL:</strong> ${data.urls?.site_url || 'N/A'}</li>
                        <li><strong>Admin URL:</strong> ${data.urls?.admin_url || 'N/A'}</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>PHP</h6>
                    <ul class="list-unstyled">
                        <li><strong>Version:</strong> ${data.php?.version || 'N/A'}</li>
                        <li><strong>Memory Limit:</strong> ${data.php?.memory_limit || 'N/A'}</li>
                        <li><strong>Max Execution Time:</strong> ${data.php?.max_execution_time || 'N/A'}s</li>
                    </ul>
                </div>
            </div>
        `;
    }
}

// Inicializar módulo
new SystemInfoModule();

export default SystemInfoModule;
