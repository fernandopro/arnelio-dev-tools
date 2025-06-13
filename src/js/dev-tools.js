/**
 * Dev-Tools Arquitectura 3.0 - JavaScript Principal
 * Sistema moderno ES6+ con Bootstrap 5
 */

// Import Bootstrap JS components que necesitamos
import { Tab } from 'bootstrap';

// Import módulos específicos de dev-tools
import TestRunner from './test-runner.js';

/**
 * Clase principal DevTools para manejo global
 */
class DevTools {
    constructor() {
        this.apiUrl = this.getApiUrl();
        this.nonce = this.getNonce();
        this.cache = new Map();
        this.activeRequests = new Map();
        
        this.init();
    }
    
    /**
     * Obtener URL de la API desde la configuración de WordPress
     */
    getApiUrl() {
        return (typeof devToolsConfig !== 'undefined') ? devToolsConfig.ajaxurl : '/wp-admin/admin-ajax.php';
    }
    
    /**
     * Obtener nonce desde la configuración de WordPress
     */
    getNonce() {
        return (typeof devToolsConfig !== 'undefined') ? devToolsConfig.nonce : '';
    }
    
    /**
     * Inicialización principal
     */
    init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    /**
     * Configuración inicial
     */
    setup() {
        this.initializeTabs();
        this.bindEvents();
        this.setupErrorHandling();
        
        // Inicializar test runner
        this.testRunner = new TestRunner(this);
        
        // Log de inicialización
        this.log('🔧 Dev-Tools Arquitectura 3.0 initialized');
    }
    
    /**
     * Inicializar pestañas de Bootstrap 5
     */
    initializeTabs() {
        const tabElements = document.querySelectorAll('.nav-tabs [data-bs-toggle="tab"]');
        tabElements.forEach(tabElement => {
            new Tab(tabElement);
            
            // Event listener para carga lazy de contenido
            tabElement.addEventListener('shown.bs.tab', (event) => {
                const targetId = event.target.getAttribute('data-bs-target').substring(1);
                this.loadTabContent(targetId);
            });
        });
    }
    
    /**
     * Vincular eventos globales
     */
    bindEvents() {
        // Quick actions
        this.bindQuickActions();
        
        // Form submissions
        this.bindForms();
        
        // Error handling
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError('Unhandled Promise Rejection', event.reason);
        });
    }
    
    /**
     * Vincular acciones rápidas
     */
    bindQuickActions() {
        // Test Database
        const testDbBtn = document.querySelector('[data-action="test-database"]');
        if (testDbBtn) {
            testDbBtn.addEventListener('click', () => this.testDatabase());
        }
        
        // Test Site URL
        const testUrlBtn = document.querySelector('[data-action="test-site-url"]');
        if (testUrlBtn) {
            testUrlBtn.addEventListener('click', () => this.testSiteUrl());
        }
        
        // Run Tests
        const runTestsBtn = document.querySelector('[data-action="run-tests"]');
        if (runTestsBtn) {
            runTestsBtn.addEventListener('click', () => this.runTests());
        }
        
        // Clear Cache
        const clearCacheBtn = document.querySelector('[data-action="clear-cache"]');
        if (clearCacheBtn) {
            clearCacheBtn.addEventListener('click', () => this.clearCache());
        }
    }
    
    /**
     * Vincular formularios
     */
    bindForms() {
        // AJAX Tester Form
        const ajaxForm = document.getElementById('ajaxTestForm');
        if (ajaxForm) {
            ajaxForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.testAjax();
            });
        }
    }
    
    /**
     * Configurar manejo de errores
     */
    setupErrorHandling() {
        // Global error handler
        window.addEventListener('error', (event) => {
            this.handleError('JavaScript Error', event.error);
        });
    }
    
    /**
     * Realizar petición AJAX centralizada
     */
    async makeAjaxRequest(command, data = {}, options = {}) {
        const requestKey = `${command}-${JSON.stringify(data)}`;
        
        // Check cache if enabled
        if (options.useCache && this.cache.has(requestKey)) {
            return this.cache.get(requestKey);
        }
        
        // Check if request is already in progress
        if (this.activeRequests.has(requestKey)) {
            return this.activeRequests.get(requestKey);
        }
        
        const requestBody = {
            action: (typeof devToolsConfig !== 'undefined') ? devToolsConfig.action : 'dev_tools_ajax',
            command: command,
            data: JSON.stringify(data),
            nonce: this.nonce
        };
        
        const requestPromise = fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestBody)
        })
        .then(async response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data?.message || 'Unknown error occurred');
            }
            
            // Cache successful results if enabled
            if (options.useCache) {
                this.cache.set(requestKey, result.data);
            }
            
            return result.data;
        })
        .finally(() => {
            this.activeRequests.delete(requestKey);
        });
        
        this.activeRequests.set(requestKey, requestPromise);
        
        return requestPromise;
    }
    
    /**
     * Test de conexión a base de datos
     */
    async testDatabase() {
        this.showLoading('Testing database connection...');
        
        try {
            const result = await this.makeAjaxRequest('test_connection');
            this.hideLoading();
            this.showResult('Database Test', this.formatConnectionResult(result));
        } catch (error) {
            this.hideLoading();
            this.showError('Database Test Failed', error.message);
        }
    }
    
    /**
     * Test de detección de Site URL
     */
    async testSiteUrl() {
        this.showLoading('Testing site URL detection...');
        
        try {
            const result = await this.makeAjaxRequest('site_url_detection');
            this.hideLoading();
            this.showResult('Site URL Test', this.formatUrlResult(result));
        } catch (error) {
            this.hideLoading();
            this.showError('Site URL Test Failed', error.message);
        }
    }
    
    /**
     * Ejecutar tests de PHPUnit
     */
    async runTests() {
        const selectedTests = this.getSelectedTestTypes();
        const options = this.getTestOptions();
        
        this.showLoading('Running tests...');
        
        try {
            const result = await this.makeAjaxRequest('run_tests', { 
                types: selectedTests, 
                options: options 
            });
            this.hideLoading();
            this.updateTestResults(result);
        } catch (error) {
            this.hideLoading();
            this.showError('Tests Failed', error.message);
        }
    }
    
    /**
     * Test AJAX personalizado
     */
    async testAjax() {
        const command = document.getElementById('ajaxCommand').value;
        const dataText = document.getElementById('ajaxData').value;
        
        let data = {};
        try {
            data = JSON.parse(dataText);
        } catch (error) {
            this.showError('Invalid JSON', 'Please provide valid JSON data');
            return;
        }
        
        this.showLoading('Sending AJAX request...');
        
        try {
            const result = await this.makeAjaxRequest(command, data);
            this.hideLoading();
            this.updateAjaxResult(result);
        } catch (error) {
            this.hideLoading();
            this.showError('AJAX Test Failed', error.message);
        }
    }
    
    /**
     * Limpiar cache
     */
    async clearCache() {
        this.showLoading('Clearing cache...');
        
        try {
            await this.makeAjaxRequest('clear_cache');
            this.cache.clear();
            this.hideLoading();
            this.showSuccess('Cache cleared successfully');
        } catch (error) {
            this.hideLoading();
            this.showError('Cache Clear Failed', error.message);
        }
    }
    
    /**
     * Cargar contenido de pestaña de forma lazy
     */
    async loadTabContent(tabId) {
        const tabContent = document.getElementById(tabId);
        if (!tabContent || tabContent.dataset.loaded) return;
        
        // Marcar como cargado para evitar recargas
        tabContent.dataset.loaded = 'true';
        
        // Aquí se puede implementar carga específica por módulo
        switch (tabId) {
            case 'system-info':
                await this.loadSystemInfo();
                break;
            case 'database':
                await this.loadDatabaseInfo();
                break;
            // Más casos según necesidad
        }
    }
    
    /**
     * Cargar información del sistema
     */
    async loadSystemInfo() {
        const container = document.querySelector('#system-info .system-info-content');
        if (!container) return;
        
        try {
            const result = await this.makeAjaxRequest('system_info', {}, { useCache: true });
            container.innerHTML = result.formatted || this.formatSystemInfo(result);
        } catch (error) {
            container.innerHTML = `<div class="alert alert-danger">Error loading system info: ${error.message}</div>`;
        }
    }
    
    /**
     * Obtener URL de la API
     */
    getApiUrl() {
        return window.ajaxurl || '/wp-admin/admin-ajax.php';
    }
    
    /**
     * Obtener nonce de seguridad
     */
    getNonce() {
        return window.devToolsNonce || '';
    }
    
    /**
     * Obtener tipos de test seleccionados
     */
    getSelectedTestTypes() {
        const checkboxes = document.querySelectorAll('input[type=\"checkbox\"]:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }
    
    /**
     * Obtener opciones de test
     */
    getTestOptions() {
        return {
            verbose: document.getElementById('verboseOutput')?.checked || false,
            coverage: document.getElementById('generateCoverage')?.checked || false
        };
    }
    
    /**
     * Mostrar loading
     */
    showLoading(message = 'Loading...') {
        // Implementación de loading modal o indicator
        console.log('Loading:', message);
    }
    
    /**
     * Ocultar loading
     */
    hideLoading() {
        // Implementación para ocultar loading
        console.log('Loading hidden');
    }
    
    /**
     * Mostrar resultado
     */
    showResult(title, content) {
        console.log('Result:', title, content);
        // Implementación de modal o alert
    }
    
    /**
     * Mostrar error
     */
    showError(title, message) {
        console.error('Error:', title, message);
        // Implementación de error modal
    }
    
    /**
     * Mostrar éxito
     */
    showSuccess(message) {
        console.log('Success:', message);
        // Implementación de success alert
    }
    
    /**
     * Actualizar resultados de test
     */
    updateTestResults(result) {
        const container = document.getElementById('testResults');
        if (container) {
            container.innerHTML = this.formatTestResults(result);
        }
    }
    
    /**
     * Actualizar resultado AJAX
     */
    updateAjaxResult(result) {
        const container = document.getElementById('ajaxResult');
        if (container) {
            container.innerHTML = this.formatAjaxResult(result);
        }
    }
    
    /**
     * Formatear resultado de conexión
     */
    formatConnectionResult(result) {
        return `<pre>${JSON.stringify(result, null, 2)}</pre>`;
    }
    
    /**
     * Formatear resultado de URL
     */
    formatUrlResult(result) {
        return `<pre>${JSON.stringify(result, null, 2)}</pre>`;
    }
    
    /**
     * Formatear resultados de test
     */
    formatTestResults(result) {
        return `<pre>${JSON.stringify(result, null, 2)}</pre>`;
    }
    
    /**
     * Formatear resultado AJAX
     */
    formatAjaxResult(result) {
        return `<pre>${JSON.stringify(result, null, 2)}</pre>`;
    }
    
    /**
     * Formatear información del sistema
     */
    formatSystemInfo(info) {
        return `<pre>${JSON.stringify(info, null, 2)}</pre>`;
    }
    
    /**
     * Manejar errores
     */
    handleError(title, error) {
        console.error(`[DevTools] ${title}:`, error);
        // Implementación adicional de manejo de errores
    }
    
    /**
     * Log con timestamp
     */
    log(message, ...args) {
        const timestamp = new Date().toISOString();
        console.log(`[DevTools ${timestamp}]`, message, ...args);
    }
}

// Inicializar DevTools cuando el script se carga
const devTools = new DevTools();

// Exportar para uso global
window.devTools = devTools;

// Funciones globales para compatibilidad con onclick en HTML
window.runTests = function() {
    if (window.devTools && window.devTools.testRunner) {
        window.devTools.testRunner.runTests();
    } else {
        console.error('TestRunner no está disponible');
    }
};

window.runQuickTest = function() {
    if (window.devTools && window.devTools.testRunner) {
        window.devTools.testRunner.runQuickTest();
    } else {
        console.error('TestRunner no está disponible');
    }
};

// Export para módulos ES6
export default DevTools;
