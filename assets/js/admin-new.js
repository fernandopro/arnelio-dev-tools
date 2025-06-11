/**
 * Dev-Tools Admin JavaScript - Arquitectura 3.0
 * Sistema agnóstico de interacción con Bootstrap 5 y pestañas
 * Vanilla JavaScript - Sin dependencias de jQuery
 * 
 * @package DevTools
 * @version 3.0
 * @author Dev-Tools Arquitectura 3.0
 */

(function() {
    'use strict';
    
    // Objeto principal del sistema Dev-Tools
    window.devTools = {
        
        // Configuración
        config: {
            ajaxTimeout: 30000,
            retryAttempts: 3,
            debugMode: true
        },
        
        // Cache de resultados
        cache: new Map(),
        
        // Inicialización
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.checkSystemStatus();
            
            if (this.config.debugMode) {
                console.log('🔧 Dev-Tools JavaScript initialized');
            }
        },
        
        // Bind de eventos
        bindEvents: function() {
            document.addEventListener('DOMContentLoaded', () => {
                // Auto-refresh para ciertos elementos
                this.setupAutoRefresh();
                
                // Eventos de teclado
                this.setupKeyboardShortcuts();
                
                // Event listeners para formularios
                this.setupFormHandlers();
                
                // Event listeners para pestañas
                this.setupTabHandlers();
            });
        },
        
        // Configurar manejadores de pestañas
        setupTabHandlers: function() {
            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach(button => {
                button.addEventListener('shown.bs.tab', (e) => {
                    const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
                    this.onTabChange(tabId);
                });
            });
        },
        
        // Manejar cambio de pestaña
        onTabChange: function(tabId) {
            if (this.config.debugMode) {
                console.log(`🔧 Tab changed to: ${tabId}`);
            }
            
            // Ejecutar acciones específicas por pestaña
            switch(tabId) {
                case 'system-info':
                    this.loadSystemInfo();
                    break;
                case 'database':
                    this.refreshDatabaseStatus();
                    break;
                case 'ajax-tester':
                    this.initAjaxTester();
                    break;
                case 'tests':
                    this.initTestRunner();
                    break;
            }
        },
        
        // Inicializar tooltips de Bootstrap
        initTooltips: function() {
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            }
        },
        
        // Función principal para peticiones AJAX
        makeAjaxRequest: function(command, data = {}, options = {}) {
            const defaultOptions = {
                timeout: this.config.ajaxTimeout,
                showLoading: true,
                useCache: false,
                retries: 0
            };
            
            const opts = { ...defaultOptions, ...options };
            const cacheKey = `${command}_${JSON.stringify(data)}`;
            
            // Verificar cache si está habilitado
            if (opts.useCache && this.cache.has(cacheKey)) {
                return Promise.resolve(this.cache.get(cacheKey));
            }
            
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('action', 'dev_tools_ajax');
                formData.append('command', command);
                formData.append('data', JSON.stringify(data));
                formData.append('nonce', devToolsAjax.nonce);
                
                fetch(devToolsAjax.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    signal: AbortSignal.timeout(opts.timeout)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        // Guardar en cache si está habilitado
                        if (opts.useCache) {
                            this.cache.set(cacheKey, result);
                        }
                        resolve(result);
                    } else {
                        reject(new Error(result.data || 'Unknown error'));
                    }
                })
                .catch(error => {
                    if (opts.retries < this.config.retryAttempts) {
                        opts.retries++;
                        setTimeout(() => {
                            this.makeAjaxRequest(command, data, opts)
                                .then(resolve)
                                .catch(reject);
                        }, 1000 * opts.retries);
                    } else {
                        reject(error);
                    }
                });
            });
        },
        
        // Test de conexión a base de datos
        testDatabase: function() {
            this.showLoadingModal('Probando conexión a base de datos...');
            
            this.makeAjaxRequest('test_connection')
                .then(response => {
                    this.hideLoadingModal();
                    this.showResultModal('Test Database', this.formatConnectionResult(response.data));
                })
                .catch(error => {
                    this.hideLoadingModal();
                    this.showErrorModal('Error en test de base de datos', error.message);
                });
        },
        
        // Test de socket de conexión
        testSocket: function() {
            this.showLoadingModal('Probando conexión socket...');
            
            this.makeAjaxRequest('test_socket')
                .then(response => {
                    this.hideLoadingModal();
                    this.showResultModal('Test Socket', this.formatConnectionResult(response.data));
                })
                .catch(error => {
                    this.hideLoadingModal();
                    this.showErrorModal('Error en test de socket', error.message);
                });
        },
        
        // Test de detección de URL del sitio
        testSiteUrl: function() {
            this.showLoadingModal('Detectando URLs del sitio...');
            
            this.makeAjaxRequest('site_url_detection')
                .then(response => {
                    this.hideLoadingModal();
                    this.showResultModal('Site URL Detection', this.formatUrlResult(response.data));
                })
                .catch(error => {
                    this.hideLoadingModal();
                    this.showErrorModal('Error en detección de URL', error.message);
                });
        },
        
        // Ejecutar tests de PHPUnit
        runTests: function() {
            const selectedTests = this.getSelectedTestTypes();
            const options = this.getTestOptions();
            
            this.showLoadingModal('Ejecutando tests...');
            
            this.makeAjaxRequest('run_tests', { 
                types: selectedTests, 
                options: options 
            })
            .then(response => {
                this.hideLoadingModal();
                this.updateTestResults(response.data);
            })
            .catch(error => {
                this.hideLoadingModal();
                this.showErrorModal('Error ejecutando tests', error.message);
            });
        },
        
        // Test rápido
        runQuickTest: function() {
            this.showLoadingModal('Ejecutando test rápido...');
            
            this.makeAjaxRequest('quick_test')
                .then(response => {
                    this.hideLoadingModal();
                    this.updateTestResults(response.data);
                })
                .catch(error => {
                    this.hideLoadingModal();
                    this.showErrorModal('Error en test rápido', error.message);
                });
        },
        
        // Test AJAX personalizado
        testAjax: function() {
            const command = document.getElementById('ajaxCommand')?.value;
            const dataText = document.getElementById('ajaxData')?.value || '{}';
            
            if (!command) {
                this.showErrorModal('Error', 'Selecciona un comando AJAX');
                return;
            }
            
            let data;
            try {
                data = JSON.parse(dataText);
            } catch (e) {
                this.showErrorModal('Error', 'Datos JSON inválidos');
                return;
            }
            
            this.makeAjaxRequest(command, data)
                .then(response => {
                    const resultElement = document.getElementById('ajaxResult');
                    if (resultElement) {
                        resultElement.textContent = JSON.stringify(response, null, 2);
                    }
                })
                .catch(error => {
                    const resultElement = document.getElementById('ajaxResult');
                    if (resultElement) {
                        resultElement.textContent = `Error: ${error.message}`;
                    }
                });
        },
        
        // Cargar información del sistema
        loadSystemInfo: function() {
            this.makeAjaxRequest('system_info', {}, { useCache: true })
                .then(response => {
                    this.updateSystemInfoDisplay(response.data);
                })
                .catch(error => {
                    console.error('Error loading system info:', error);
                });
        },
        
        // Refrescar estado de base de datos
        refreshDatabaseStatus: function() {
            this.makeAjaxRequest('test_connection')
                .then(response => {
                    this.updateDatabaseStatusDisplay(response.data);
                })
                .catch(error => {
                    console.error('Error refreshing database status:', error);
                });
        },
        
        // Inicializar tester AJAX
        initAjaxTester: function() {
            // Configurar valores por defecto si es necesario
            const commandSelect = document.getElementById('ajaxCommand');
            const dataTextarea = document.getElementById('ajaxData');
            
            if (commandSelect && !commandSelect.value) {
                commandSelect.value = 'test_connection';
            }
            
            if (dataTextarea && !dataTextarea.value.trim()) {
                dataTextarea.value = '{}';
            }
        },
        
        // Inicializar runner de tests
        initTestRunner: function() {
            // Asegurar que los checkboxes tengan valores por defecto
            const unitTests = document.getElementById('unitTests');
            if (unitTests && !unitTests.hasAttribute('checked')) {
                unitTests.checked = true;
            }
        },
        
        // Helpers para formatear resultados
        formatConnectionResult: function(data) {
            return `
                <div class="alert alert-${data.success ? 'success' : 'danger'}">
                    <h6>${data.success ? '✅' : '❌'} ${data.message}</h6>
                    ${data.details ? `<p class="mb-0">${data.details}</p>` : ''}
                </div>
            `;
        },
        
        formatUrlResult: function(data) {
            return `
                <div class="alert alert-info">
                    <h6>🌐 URLs Detectadas</h6>
                    <ul class="mb-0">
                        <li><strong>Site URL:</strong> ${data.site_url || 'N/A'}</li>
                        <li><strong>Admin URL:</strong> ${data.admin_url || 'N/A'}</li>
                        <li><strong>Environment:</strong> ${data.environment || 'Unknown'}</li>
                    </ul>
                </div>
            `;
        },
        
        // Obtener tipos de test seleccionados
        getSelectedTestTypes: function() {
            const types = [];
            const checkboxes = ['unitTests', 'integrationTests', 'environmentTests'];
            
            checkboxes.forEach(id => {
                const checkbox = document.getElementById(id);
                if (checkbox?.checked) {
                    types.push(id.replace('Tests', ''));
                }
            });
            
            return types;
        },
        
        // Obtener opciones de test
        getTestOptions: function() {
            return {
                verbose: document.getElementById('verboseOutput')?.checked || false,
                coverage: document.getElementById('generateCoverage')?.checked || false
            };
        },
        
        // Actualizar resultados de tests
        updateTestResults: function(data) {
            const resultsElement = document.getElementById('testResults');
            if (resultsElement) {
                resultsElement.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'warning'}">
                        <h6>📊 Resultados de Tests</h6>
                        <pre class="mb-0">${data.output || 'No output'}</pre>
                    </div>
                `;
            }
        },
        
        // Actualizar display de información del sistema
        updateSystemInfoDisplay: function(data) {
            // Implementation depends on specific data structure
            console.log('System info updated:', data);
        },
        
        // Actualizar display de estado de base de datos
        updateDatabaseStatusDisplay: function(data) {
            // Implementation depends on specific data structure
            console.log('Database status updated:', data);
        },
        
        // Verificar estado del sistema
        checkSystemStatus: function() {
            this.makeAjaxRequest('system_status', {}, { useCache: true })
                .then(response => {
                    if (this.config.debugMode) {
                        console.log('🔧 System status:', response.data);
                    }
                })
                .catch(error => {
                    console.warn('Could not check system status:', error.message);
                });
        },
        
        // Modales de Bootstrap
        showLoadingModal: function(message = 'Cargando...') {
            // Implementar modal de loading
            console.log('Loading:', message);
        },
        
        hideLoadingModal: function() {
            // Ocultar modal de loading
            console.log('Loading hidden');
        },
        
        showResultModal: function(title, content) {
            // Implementar modal de resultados
            console.log('Result:', title, content);
        },
        
        showErrorModal: function(title, message) {
            // Implementar modal de error
            console.error('Error:', title, message);
        },
        
        // Setup functions
        setupAutoRefresh: function() {
            // Configurar auto-refresh para elementos específicos
        },
        
        setupKeyboardShortcuts: function() {
            // Configurar atajos de teclado
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 't') {
                    e.preventDefault();
                    this.runQuickTest();
                }
            });
        },
        
        setupFormHandlers: function() {
            // Configurar manejadores de formularios
        }
    };
    
    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => devTools.init());
    } else {
        devTools.init();
    }
    
})();
