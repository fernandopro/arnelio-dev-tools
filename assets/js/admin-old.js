/**
 * Dev-Tools Admin JavaScript - Arquitectura 3.0
 * Sistema agnóstico de interacción con Bootstrap 5 y pestañas
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
                retries: this.config.retryAttempts
            };
            
            const finalOptions = { ...defaultOptions, ...options };
            const cacheKey = `${command}_${JSON.stringify(data)}`;
            
            // Verificar cache
            if (finalOptions.useCache && this.cache.has(cacheKey)) {
                return Promise.resolve(this.cache.get(cacheKey));
            }
            
            // Mostrar loading si está habilitado
            if (finalOptions.showLoading) {
                this.showLoading();
            }
            
            return new Promise((resolve, reject) => {
                const attemptRequest = (attemptsLeft) => {
                    $.ajax({
                        url: devToolsAjax.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        timeout: finalOptions.timeout,
                        data: {
                            action: devToolsAjax.action,
                            nonce: devToolsAjax.nonce,
                            command: command,
                            data: data
                        },
                        success: (response) => {
                            this.hideLoading();
                            
                            if (response.success) {
                                // Guardar en cache si está habilitado
                                if (finalOptions.useCache) {
                                    this.cache.set(cacheKey, response.data);
                                }
                                
                                resolve(response.data);
                            } else {
                                reject(new Error(response.data?.message || 'Unknown error'));
                            }
                        },
                        error: (xhr, status, error) => {
                            if (attemptsLeft > 0 && status !== 'timeout') {
                                setTimeout(() => attemptRequest(attemptsLeft - 1), 1000);
                            } else {
                                this.hideLoading();
                                reject(new Error(`AJAX Error: ${error} (${status})`));
                            }
                        }
                    });
                };
                
                attemptRequest(finalOptions.retries);
            });
        },
        
        // Test de conexión a base de datos
        testDatabase: function() {
            this.makeAjaxRequest('quick_action', { action: 'database' })
                .then(result => {
                    this.showModal('Database Test', result.formatted || this.formatResult(result));
                })
                .catch(error => {
                    this.showError('Database Test Failed', error.message);
                });
        },
        
        // Test detallado de base de datos
        testDatabaseDetailed: function() {
            const container = document.getElementById('databaseTestContent');
            if (!container) return;
            
            container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
            
            this.makeAjaxRequest('test_connection')
                .then(result => {
                    container.innerHTML = result.formatted || this.formatResult(result);
                })
                .catch(error => {
                    container.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
                });
        },
        
        // Test de detección de Site URL
        testSiteUrl: function() {
            this.makeAjaxRequest('quick_action', { action: 'site_url' })
                .then(result => {
                    this.showModal('Site URL Detection', result.formatted || this.formatResult(result));
                })
                .catch(error => {
                    this.showError('Site URL Test Failed', error.message);
                });
        },
        
        // Cargar información del sistema
        loadSystemInfo: function() {
            const container = document.getElementById('systemInfoContent');
            if (!container) return;
            
            this.makeAjaxRequest('system_info', {}, { useCache: true })
                .then(result => {
                    container.innerHTML = result.formatted || this.formatSystemInfo(result.data);
                })
                .catch(error => {
                    container.innerHTML = `<div class="alert alert-danger">Error loading system info: ${error.message}</div>`;
                });
        },
        
        // Ejecutar tests PHPUnit
        runTests: function() {
            this.runTestSuite('all');
        },
        
        // Ejecutar suite de tests específica
        runTestSuite: function(type) {
            const options = {
                verbose: document.getElementById('verboseOutput')?.checked || false,
                stop_on_failure: document.getElementById('stopOnFailure')?.checked || false,
                coverage: document.getElementById('generateCoverage')?.checked || false
            };
            
            const container = document.getElementById('testResults');
            if (container) {
                container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Running tests...</p></div>';
            }
            
            this.makeAjaxRequest('run_tests', { type: type, options: options }, { timeout: 60000 })
                .then(result => {
                    if (container) {
                        container.innerHTML = result.formatted || this.formatTestResults(result);
                    } else {
                        this.showModal(`Test Results (${type})`, result.formatted || this.formatTestResults(result));
                    }
                })
                .catch(error => {
                    const errorHtml = `<div class="alert alert-danger">Error running tests: ${error.message}</div>`;
                    if (container) {
                        container.innerHTML = errorHtml;
                    } else {
                        this.showError('Test Error', error.message);
                    }
                });
        },
        
        // Test AJAX específico
        ajaxTest: function(command) {
            const resultContainer = document.getElementById('ajaxTestResult');
            if (!resultContainer) return;
            
            resultContainer.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Testing...</div>';
            
            this.makeAjaxRequest(command)
                .then(result => {
                    resultContainer.innerHTML = `
                        <div class="alert alert-success">
                            <strong>✅ Success</strong>
                        </div>
                        <h6>Result:</h6>
                        <pre class="dev-tools-code">${JSON.stringify(result, null, 2)}</pre>
                    `;
                })
                .catch(error => {
                    resultContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>❌ Error:</strong> ${error.message}
                        </div>
                    `;
                });
        },
        
        // Limpiar cache
        clearCache: function() {
            this.makeAjaxRequest('quick_action', { action: 'cache' })
                .then(result => {
                    this.showSuccess('Cache Cleared', 'Cache cleared successfully');
                    this.cache.clear(); // Limpiar cache local también
                })
                .catch(error => {
                    this.showError('Cache Clear Failed', error.message);
                });
        },
        
        // Formatear resultado genérico
        formatResult: function(result) {
            return `<pre class="dev-tools-code">${JSON.stringify(result, null, 2)}</pre>`;
        },
        
        // Formatear información del sistema
        formatSystemInfo: function(data) {
            let html = '';
            
            for (const [section, info] of Object.entries(data)) {
                html += `
                    <div class="card mb-3 dev-tools-card">
                        <div class="card-header">
                            <h6 class="mb-0">${this.capitalize(section)}</h6>
                        </div>
                        <div class="card-body">
                `;
                
                if (typeof info === 'object' && info !== null) {
                    html += '<dl class="row mb-0">';
                    for (const [key, value] of Object.entries(info)) {
                        html += `
                            <dt class="col-sm-4">${this.capitalize(key.replace(/_/g, ' '))}:</dt>
                            <dd class="col-sm-8">
                        `;
                        
                        if (Array.isArray(value)) {
                            html += `<small class="text-muted">${value.slice(0, 3).join(', ')}${value.length > 3 ? '...' : ''}</small>`;
                        } else {
                            html += `<code class="small">${value}</code>`;
                        }
                        
                        html += '</dd>';
                    }
                    html += '</dl>';
                } else {
                    html += `<p class="mb-0">${info}</p>`;
                }
                
                html += '</div></div>';
            }
            
            return html;
        },
        
        // Formatear resultados de tests
        formatTestResults: function(result) {
            const status = result.success ? 'success' : 'danger';
            const icon = result.success ? '✅' : '❌';
            
            return `
                <div class="alert alert-${status}">
                    <strong>${icon} Tests ${result.success ? 'Passed' : 'Failed'}</strong>
                </div>
                <div class="mb-3">
                    <h6>Command:</h6>
                    <code class="small">${result.command}</code>
                </div>
                <div>
                    <h6>Output:</h6>
                    <div class="terminal-output custom-scrollbar">${result.output}</div>
                </div>
            `;
        },
        
        // Mostrar modal genérico
        showModal: function(title, content) {
            // Crear modal dinámico si no existe
            let modal = document.getElementById('devToolsModal');
            if (!modal) {
                modal = this.createModal();
            }
            
            modal.querySelector('.modal-title').textContent = title;
            modal.querySelector('.modal-body').innerHTML = content;
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        },
        
        // Crear modal dinámico
        createModal: function() {
            const modalHtml = `
                <div class="modal fade" id="devToolsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            return document.getElementById('devToolsModal');
        },
        
        // Mostrar toast de éxito
        showSuccess: function(title, message) {
            this.showToast(title, message, 'success');
        },
        
        // Mostrar toast de error
        showError: function(title, message) {
            this.showToast(title, message, 'danger');
        },
        
        // Mostrar toast genérico
        showToast: function(title, message, type = 'info') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong><br>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'position-fixed top-0 end-0 p-3';
                container.style.zIndex = '1050';
                document.body.appendChild(container);
            }
            
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toast = container.lastElementChild;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remover después de que se oculte
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        },
        
        // Mostrar/ocultar loading global
        showLoading: function() {
            let overlay = document.getElementById('devToolsLoading');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'devToolsLoading';
                overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
                overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
                overlay.style.zIndex = '9999';
                overlay.innerHTML = `
                    <div class="bg-white p-4 rounded shadow">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-2" role="status"></div>
                            <div>Processing...</div>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);
            }
            overlay.style.display = 'flex';
        },
        
        hideLoading: function() {
            const overlay = document.getElementById('devToolsLoading');
            if (overlay) {
                overlay.style.display = 'none';
            }
        },
        
        // Configurar auto-refresh
        setupAutoRefresh: function() {
            // Auto-refresh cada 30 segundos para el dashboard
            if (window.location.href.includes('page=dev-tools') && !window.location.href.includes('-')) {
                setInterval(() => {
                    this.checkSystemStatus();
                }, 30000);
            }
        },
        
        // Configurar atajos de teclado
        setupKeyboardShortcuts: function() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + Shift + D para abrir dashboard
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                    e.preventDefault();
                    window.location.href = 'admin.php?page=dev-tools';
                }
                
                // Ctrl/Cmd + Shift + T para ejecutar tests
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                    e.preventDefault();
                    this.runTests();
                }
            });
        },
        
        // Configurar handlers de formularios
        setupFormHandlers: function() {
            // Prevenir submit accidental de formularios
            $('form').on('submit', function(e) {
                if (!confirm('Are you sure you want to submit this form?')) {
                    e.preventDefault();
                }
            });
        },
        
        // Verificar estado del sistema
        checkSystemStatus: function() {
            if (typeof devToolsAjax === 'undefined') {
                console.warn('Dev-Tools: AJAX configuration not available');
                return;
            }
            
            // Verificación silenciosa del estado
            this.makeAjaxRequest('system_info', {}, { 
                showLoading: false, 
                useCache: true,
                timeout: 5000
            }).catch(error => {
                if (this.config.debugMode) {
                    console.warn('Dev-Tools: System status check failed:', error.message);
                }
            });
        },
        
        // Utilidades
        capitalize: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },
        
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Formatear bytes
        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        devTools.init();
    });
    
})(jQuery);
