/**
 * AJAX Tester Module - Dev-Tools Arquitectura 3.0
 * Módulo para testing de endpoints AJAX
 */

class AjaxTesterModule {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupAjaxTester();
        });
    }
    
    setupAjaxTester() {
        console.log('🔧 AjaxTester: Module initialized');
        this.bindFormEvents();
        this.setupCommandPresets();
    }
    
    bindFormEvents() {
        // AJAX Test Form
        const ajaxForm = document.getElementById('ajaxTestForm');
        if (ajaxForm) {
            ajaxForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.executeAjaxTest();
            });
        }
        
        // Quick test buttons
        const quickTestBtns = document.querySelectorAll('[data-ajax-test]');
        quickTestBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const command = btn.dataset.ajaxTest;
                this.runQuickTest(command);
            });
        });
    }
    
    setupCommandPresets() {
        const commandSelect = document.getElementById('ajaxCommand');
        if (commandSelect) {
            commandSelect.addEventListener('change', (e) => {
                this.loadCommandPreset(e.target.value);
            });
        }
    }
    
    async executeAjaxTest() {
        const command = document.getElementById('ajaxCommand')?.value;
        const dataText = document.getElementById('ajaxData')?.value || '{}';
        
        if (!command) {
            this.showError('Please select a command');
            return;
        }
        
        let data = {};
        try {
            data = JSON.parse(dataText);
        } catch (error) {
            this.showError('Invalid JSON data format');
            return;
        }
        
        this.showLoading('Executing AJAX test...');
        
        try {
            if (window.devTools) {
                const result = await window.devTools.makeAjaxRequest(command, data);
                this.displayResult(result);
            }
        } catch (error) {
            this.showError(`AJAX test failed: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }
    
    async runQuickTest(command) {
        this.showLoading(`Running quick test: ${command}`);
        
        try {
            if (window.devTools) {
                const result = await window.devTools.makeAjaxRequest(command);
                this.displayResult(result);
            }
        } catch (error) {
            this.showError(`Quick test failed: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }
    
    loadCommandPreset(command) {
        const dataTextarea = document.getElementById('ajaxData');
        if (!dataTextarea) return;
        
        const presets = {
            'test_connection': '{}',
            'system_info': '{}',
            'site_url_detection': '{}',
            'run_tests': '{"types": ["unit"], "options": {"verbose": true}}',
            'clear_cache': '{}'
        };
        
        dataTextarea.value = presets[command] || '{}';
    }
    
    displayResult(result) {
        const resultContainer = document.getElementById('ajaxResult');
        if (resultContainer) {
            resultContainer.innerHTML = this.formatResult(result);
        }
    }
    
    formatResult(result) {
        return `
            <div class="ajax-result-content">
                <div class="result-header">
                    <span class="badge bg-success">Success</span>
                    <small class="text-muted">${new Date().toLocaleTimeString()}</small>
                </div>
                <pre class="result-data">${JSON.stringify(result, null, 2)}</pre>
            </div>
        `;
    }
    
    showLoading(message) {
        const resultContainer = document.getElementById('ajaxResult');
        if (resultContainer) {
            resultContainer.innerHTML = `
                <div class="text-center p-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    ${message}
                </div>
            `;
        }
    }
    
    hideLoading() {
        // Loading se oculta automáticamente al mostrar resultado
    }
    
    showError(message) {
        const resultContainer = document.getElementById('ajaxResult');
        if (resultContainer) {
            resultContainer.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${message}
                </div>
            `;
        }
    }
}

// Inicializar módulo
new AjaxTesterModule();

export default AjaxTesterModule;
