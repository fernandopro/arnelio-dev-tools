/**
 * Dev Tools - Maintenance Tab JavaScript
 * Gestión específica de la pestaña de mantenimiento
 * 
 * @package TarokinaPro
 * @subpackage DevTools
 * @since 1.0.0
 */

class DevToolsMaintenanceManager {
    constructor() {
        this.config = window.tkn_dev_tools_config || {};
        this.logContainer = null;
        this.logFilter = null;
        
        this.init();
    }
    
    /**
     * Inicializar el gestor de mantenimiento
     */
    init() {
        this.bindElements();
        this.setupEventListeners();
    }
    
    /**
     * Vincular elementos del DOM
     */
    bindElements() {
        this.logContainer = document.getElementById('system-logs');
        this.logFilter = document.getElementById('log-level-filter');
    }
    
    /**
     * Configurar event listeners específicos para mantenimiento
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeMaintenanceTab();
        });
        
        // Filtro de logs
        if (this.logFilter) {
            this.logFilter.addEventListener('change', (event) => {
                this.filterLogs(event.target.value);
            });
        }
    }
    
    /**
     * Inicializar la pestaña de mantenimiento
     */
    initializeMaintenanceTab() {
        if (this.config.debug_mode) {
            console.log('DevToolsMaintenanceManager: Inicializando pestaña de mantenimiento');
        }
        
        // Cargar logs iniciales
        this.loadSystemLogs();
        
        // Inicializar tooltips
        this.initializeTooltips();
    }
    
    /**
     * Inicializar tooltips de Bootstrap
     */
    initializeTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
    
    /**
     * Filtrar logs por nivel
     */
    filterLogs(level) {
        if (this.config.debug_mode) {
            console.log('Filtrar logs por nivel:', level);
        }
        
        if (!this.logContainer) return;
        
        const logEntries = this.logContainer.querySelectorAll('.log-entry');
        
        logEntries.forEach(entry => {
            if (level === 'all') {
                entry.style.display = 'block';
            } else {
                const entryLevel = this.getLogEntryLevel(entry);
                entry.style.display = entryLevel === level ? 'block' : 'none';
            }
        });
    }
    
    /**
     * Obtener el nivel de un log entry
     */
    getLogEntryLevel(entry) {
        if (entry.classList.contains('text-danger')) return 'error';
        if (entry.classList.contains('text-warning')) return 'warning';
        if (entry.classList.contains('text-info')) return 'info';
        if (entry.classList.contains('text-success')) return 'success';
        if (entry.classList.contains('text-muted')) return 'debug';
        return 'info';
    }
    
    /**
     * Cargar logs del sistema
     */
    async loadSystemLogs() {
        if (!this.logContainer) return;
        
        try {
            // Mostrar indicador de carga
            this.logContainer.innerHTML = '<div class="text-muted"><i class="bi bi-hourglass-split"></i> Cargando logs...</div>';
            
            // Simular carga de logs (en producción esto vendría del servidor)
            await this.simulateLogLoading();
            
        } catch (error) {
            this.logContainer.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-triangle"></i> Error al cargar logs</div>';
            console.error('Error cargando logs:', error);
        }
    }
    
    /**
     * Simular carga de logs del sistema
     */
    async simulateLogLoading() {
        return new Promise((resolve) => {
            setTimeout(() => {
                const currentTime = new Date().toLocaleString();
                
                this.logContainer.innerHTML = `
                    <div class="log-entry text-success">[${currentTime}] INFO: Sistema iniciado correctamente</div>
                    <div class="log-entry text-warning">[${currentTime}] WARN: Cache casi lleno</div>
                    <div class="log-entry text-info">[${currentTime}] INFO: Test ejecutado exitosamente</div>
                    <div class="log-entry text-muted">[${currentTime}] DEBUG: Depuración activada</div>
                    <div class="log-entry text-success">[${currentTime}] INFO: CSS y JavaScript separados correctamente</div>
                    <div class="log-entry text-info">[${currentTime}] INFO: Sistema dev-tools funcionando</div>
                `;
                
                resolve();
            }, 1000);
        });
    }
    
    /**
     * Refrescar logs del sistema
     */
    async refreshLogs() {
        if (this.config.debug_mode) {
            console.log('Refrescando logs del sistema');
        }
        
        await this.loadSystemLogs();
    }
    
    /**
     * Limpiar logs del sistema
     */
    clearLogs() {
        if (this.logContainer) {
            this.logContainer.innerHTML = '<div class="text-muted"><i class="bi bi-info-circle"></i> Logs limpiados</div>';
        }
        
        if (this.config.debug_mode) {
            console.log('Logs del sistema limpiados');
        }
    }
}

// Funciones globales para compatibilidad con código existente
window.filterLogs = function(level) {
    const logContainer = document.getElementById('system-logs');
    if (!logContainer) return;
    
    const logEntries = logContainer.querySelectorAll('.log-entry');
    logEntries.forEach(entry => {
        if (level === 'all') {
            entry.style.display = 'block';
        } else {
            const entryLevel = getLogEntryLevel(entry);
            entry.style.display = entryLevel === level ? 'block' : 'none';
        }
    });
};

window.loadSystemLogs = function() {
    const logContainer = document.getElementById('system-logs');
    if (!logContainer) return;
    
    setTimeout(() => {
        const currentTime = new Date().toLocaleString();
        logContainer.innerHTML = `
            <div class="log-entry text-success">[${currentTime}] INFO: Sistema iniciado correctamente</div>
            <div class="log-entry text-warning">[${currentTime}] WARN: Cache casi lleno</div>
            <div class="log-entry text-info">[${currentTime}] INFO: Test ejecutado exitosamente</div>
            <div class="log-entry text-muted">[${currentTime}] DEBUG: Depuración activada</div>
        `;
    }, 1000);
};

function getLogEntryLevel(entry) {
    if (entry.classList.contains('text-danger')) return 'error';
    if (entry.classList.contains('text-warning')) return 'warning';
    if (entry.classList.contains('text-info')) return 'info';
    if (entry.classList.contains('text-success')) return 'success';
    if (entry.classList.contains('text-muted')) return 'debug';
    return 'info';
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.devToolsMaintenanceManager = new DevToolsMaintenanceManager();
    
    const logFilter = document.getElementById('log-level-filter');
    if (logFilter) {
        logFilter.addEventListener('change', function() {
            filterLogs(this.value);
        });
    }
    
    loadSystemLogs();
});
