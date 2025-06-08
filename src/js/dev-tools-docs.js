/**
 * Dev Tools - Docs Tab JavaScript
 * Gestión específica de la pestaña de documentación
 * 
 * @package TarokinaPro
 * @subpackage DevTools
 * @since 1.0.0
 */

class DevToolsDocsManager {
    constructor() {
        this.config = window.tkn_dev_tools_config || {};
        this.templates = this.getDocTemplates();
        
        this.init();
    }
    
    /**
     * Inicializar el gestor de documentación
     */
    init() {
        this.setupEventListeners();
    }
    
    /**
     * Configurar event listeners específicos para docs
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeDocsTab();
        });
    }
    
    /**
     * Inicializar la pestaña de documentación
     */
    initializeDocsTab() {
        if (this.config.debug_mode) {
            console.log('DevToolsDocsManager: Inicializando pestaña de documentación');
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
     * Ver un documento específico
     */
    viewDoc(docFile) {
        if (this.config.debug_mode) {
            console.log('ViewDoc llamado para:', docFile);
        }
        
        // TODO: Implementar visualizador de documentos
        alert(`Visualizador de documentos en desarrollo para: ${docFile}`);
    }
    
    /**
     * Editar un documento específico
     */
    editDoc(docFile) {
        if (this.config.debug_mode) {
            console.log('EditDoc llamado para:', docFile);
        }
        
        // TODO: Implementar editor de documentos
        alert(`Editor de documentos en desarrollo para: ${docFile}`);
    }
    
    /**
     * Cargar una plantilla de documento
     */
    loadDocTemplate(templateType) {
        if (this.config.debug_mode) {
            console.log('LoadTemplate llamado para:', templateType);
        }
        
        const docContent = document.getElementById('doc-content');
        if (docContent && this.templates[templateType]) {
            docContent.value = this.templates[templateType];
            
            // Opcional: Mostrar mensaje de confirmación
            if (this.config.debug_mode) {
                console.log('Plantilla cargada:', templateType);
            }
        } else if (!this.templates[templateType]) {
            console.warn('Plantilla no encontrada:', templateType);
        }
    }
    
    /**
     * Guardar contenido del documento
     */
    saveDocContent() {
        const docContent = document.getElementById('doc-content');
        const docName = document.getElementById('doc-name');
        
        if (!docContent || !docName) {
            alert('Error: No se encontraron los elementos necesarios');
            return;
        }
        
        const content = docContent.value;
        const name = docName.value || 'documento-sin-nombre';
        
        if (!content.trim()) {
            alert('Error: El contenido del documento no puede estar vacío');
            return;
        }
        
        // Simular guardado (en producción esto iría al servidor via AJAX)
        if (this.config.debug_mode) {
            console.log('Guardando documento:', name, content);
        }
        
        // Mostrar confirmación
        alert('Documento "' + name + '" guardado exitosamente');
    }
    
    /**
     * Obtener las plantillas de documentos
     */
    getDocTemplates() {
        const currentDate = new Date().toISOString().split('T')[0];
        
        return {
            'api': 'plantilla_api_markdown',
            'installation': 'plantilla_instalacion_markdown', 
            'troubleshooting': 'plantilla_solucion_problemas_markdown',
            'changelog': 'plantilla_changelog_markdown'
        };
    }
}

// Funciones globales para compatibilidad con código existente
window.loadDocTemplate = function(templateType) {
    if (window.devToolsDocsManager) {
        window.devToolsDocsManager.loadDocTemplate(templateType);
    }
};

window.saveDocContent = function() {
    if (window.devToolsDocsManager) {
        window.devToolsDocsManager.saveDocContent();
    }
};

window.viewDoc = function(docFile) {
    alert('Visualizador de documentos en desarrollo para: ' + docFile);
};

window.editDoc = function(docFile) {
    alert('Editor de documentos en desarrollo para: ' + docFile);
};

window.loadTemplate = function(templateType) {
    const docContent = document.getElementById('doc-content');
    const templates = {
        'api': '# Documentación API\n\n## Introducción\nDescripción general de la API...',
        'installation': '# Guía de Instalación\n\n## Requisitos\n- WordPress 5.0+\n- PHP 7.4+',
        'troubleshooting': '# Solución de Problemas\n\n## Problemas Comunes',
        'changelog': '# Changelog\n\n## [1.0.0] - ' + new Date().toISOString().split('T')[0]
    };
    
    if (docContent && templates[templateType]) {
        docContent.value = templates[templateType];
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.devToolsDocsManager = new DevToolsDocsManager();
});
