/**
 * Dev Tools - Settings Tab JavaScript
 * Gestión específica de la pestaña de configuración
 * 
 * @package TarokinaPro
 * @subpackage DevTools
 * @since 1.0.0
 */

class DevToolsSettingsManager {
    constructor() {
        this.config = window.tkn_dev_tools_config || {};
        this.settingsForm = null;
        
        this.init();
    }
    
    /**
     * Inicializar el gestor de configuración
     */
    init() {
        this.bindElements();
        this.setupEventListeners();
    }
    
    /**
     * Vincular elementos del DOM
     */
    bindElements() {
        this.settingsForm = document.getElementById('settings-form');
    }
    
    /**
     * Configurar event listeners específicos para configuración
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeSettingsTab();
        });
        
        // Validación del formulario
        if (this.settingsForm) {
            this.settingsForm.addEventListener('submit', (event) => {
                this.validateForm(event);
            });
        }
    }
    
    /**
     * Inicializar la pestaña de configuración
     */
    initializeSettingsTab() {
        if (this.config.debug_mode) {
            console.log('DevToolsSettingsManager: Inicializando pestaña de configuración');
        }
        
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
     * Resetear configuración a valores por defecto
     */
    resetSettings() {
        if (confirm('¿Estás seguro de que quieres restaurar todos los valores por defecto?')) {
            if (this.settingsForm) {
                this.settingsForm.reset();
                
                if (this.config.debug_mode) {
                    console.log('Configuración reseteada a valores por defecto');
                }
                
                // Mostrar mensaje de confirmación
                this.showMessage('Configuración reseteada a valores por defecto', 'success');
            }
        }
    }
    
    /**
     * Aplicar perfil de desarrollador
     */
    applyDevProfile() {
        this.setFormValue('debug-mode', true);
        this.setFormValue('auto-refresh', true);
        this.setFormValue('log-level', 'debug');
        this.setFormValue('production-warning', true);
        
        this.showMessage('Perfil de desarrollador aplicado. No olvides guardar la configuración.', 'info');
        
        if (this.config.debug_mode) {
            console.log('Perfil de desarrollador aplicado');
        }
    }
    
    /**
     * Aplicar perfil de testing
     */
    applyTestProfile() {
        this.setFormValue('debug-mode', true);
        this.setFormValue('auto-refresh', false);
        this.setFormValue('log-level', 'info');
        this.setFormValue('email-notifications', true);
        
        this.showMessage('Perfil de testing aplicado. No olvides guardar la configuración.', 'info');
        
        if (this.config.debug_mode) {
            console.log('Perfil de testing aplicado');
        }
    }
    
    /**
     * Aplicar perfil de producción
     */
    applyProdProfile() {
        this.setFormValue('debug-mode', false);
        this.setFormValue('auto-refresh', false);
        this.setFormValue('log-level', 'error');
        this.setFormValue('production-warning', true);
        
        this.showMessage('Perfil de producción aplicado. No olvides guardar la configuración.', 'warning');
        
        if (this.config.debug_mode) {
            console.log('Perfil de producción aplicado');
        }
    }
    
    /**
     * Establecer valor en un campo del formulario
     */
    setFormValue(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = value;
            } else {
                field.value = value;
            }
        }
    }
    
    /**
     * Validar formulario antes del envío
     */
    validateForm(event) {
        const errors = [];
        
        // Validar tamaño máximo de log
        const maxLogSize = document.getElementById('max-log-size');
        if (maxLogSize && maxLogSize.value > 100) {
            errors.push('El tamaño máximo de log no puede ser mayor a 100 MB');
        }
        
        // Validar timeout de tests
        const testTimeout = document.getElementById('test-timeout');
        if (testTimeout && testTimeout.value > 3600) {
            errors.push('El timeout de tests no puede ser mayor a 1 hora (3600 segundos)');
        }
        
        // Si hay errores, prevenir el envío
        if (errors.length > 0) {
            event.preventDefault();
            this.showMessage(errors.join('\n'), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Mostrar mensaje al usuario
     */
    showMessage(message, type = 'info') {
        // Usar alert como fallback, en producción se podría usar un sistema de notificaciones más sofisticado
        if (type === 'error') {
            alert('Error: ' + message);
        } else {
            alert(message);
        }
    }
}

// Funciones globales para compatibilidad con código existente
window.resetSettings = function() {
    if (window.devToolsSettingsManager) {
        window.devToolsSettingsManager.resetSettings();
    }
};

window.applyDevProfile = function() {
    if (window.devToolsSettingsManager) {
        window.devToolsSettingsManager.applyDevProfile();
    }
};

window.applyTestProfile = function() {
    if (window.devToolsSettingsManager) {
        window.devToolsSettingsManager.applyTestProfile();
    }
};

window.applyProdProfile = function() {
    if (window.devToolsSettingsManager) {
        window.devToolsSettingsManager.applyProdProfile();
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.devToolsSettingsManager = new DevToolsSettingsManager();
});
