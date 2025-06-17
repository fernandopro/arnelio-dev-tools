/**
 * Tests Module - Dev-Tools Arquitectura 3.0
 * Módulo para ejecutar y gestionar tests PHPUnit
 */

class TestsModule {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupTests();
        });
    }
    
    setupTests() {
        console.log('🔧 Tests: Module initialized');
        this.bindTestEvents();
        this.loadTestStatus();
    }
    
    bindTestEvents() {
        // Run Selected Tests button
        const runTestsBtn = document.querySelector('[data-action="run-tests"]');
        if (runTestsBtn) {
            runTestsBtn.addEventListener('click', () => this.runSelectedTests());
        }
        
        // Quick Test button
        const quickTestBtn = document.querySelector('[data-action="quick-test"]');
        if (quickTestBtn) {
            quickTestBtn.addEventListener('click', () => this.runQuickTest());
        }
        
        // Test type checkboxes
        const testCheckboxes = document.querySelectorAll('input[type="checkbox"][id$="Tests"]');
        testCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateTestSelection());
        });
    }
    
    async runSelectedTests() {
        const selectedTypes = this.getSelectedTestTypes();
        const options = this.getTestOptions();
        
        if (selectedTypes.length === 0) {
            this.showError('Please select at least one test type');
            return;
        }
        
        this.showLoading('Running selected tests...');
        
        try {
            if (window.devTools) {
                const result = await window.devTools.makeAjaxRequest('run_tests', {
                    types: selectedTypes,
                    options: options
                });
                this.displayTestResults(result);
            }
        } catch (error) {
            this.showError(`Tests failed: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }
    
    async runQuickTest() {
        this.showLoading('Running quick test...');
        
        try {
            if (window.devTools) {
                const result = await window.devTools.makeAjaxRequest('run_tests', {
                    types: ['unit'],
                    options: { verbose: false, quick: true }
                });
                this.displayTestResults(result);
            }
        } catch (error) {
            this.showError(`Quick test failed: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }
    
    getSelectedTestTypes() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][id$="Tests"]:checked');
        return Array.from(checkboxes).map(cb => {
            return cb.id.replace('devtools-', '').replace('Tests', '').toLowerCase();
        });
    }
    
    getTestOptions() {
        return {
            verbose: document.getElementById('verboseOutput')?.checked || false,
            coverage: document.getElementById('generateCoverage')?.checked || false
        };
    }
    
    updateTestSelection() {
        const selectedTypes = this.getSelectedTestTypes();
        const runButton = document.querySelector('[data-action="run-tests"]');
        
        if (runButton) {
            runButton.disabled = selectedTypes.length === 0;
            runButton.textContent = selectedTypes.length > 0 
                ? `🚀 Run ${selectedTypes.length} Test Type(s)`
                : '🚀 Run Selected Tests';
        }
    }
    
    async loadTestStatus() {
        try {
            if (window.devTools) {
                const result = await window.devTools.makeAjaxRequest('test_status');
                this.displayTestStatus(result);
            }
        } catch (error) {
            console.warn('Could not load test status:', error);
        }
    }
    
    displayTestResults(result) {
        const container = document.getElementById('testResults');
        if (container) {
            container.innerHTML = this.formatTestResults(result);
        }
    }
    
    displayTestStatus(status) {
        const statusContainer = document.querySelector('.test-status');
        if (statusContainer && status) {
            statusContainer.innerHTML = this.formatTestStatus(status);
        }
    }
    
    formatTestResults(result) {
        if (!result) return '<p class="text-muted">No test results available</p>';
        
        const { summary, tests, errors } = result;
        
        let html = '<div class="test-results-container">';
        
        // Summary
        if (summary) {
            html += `
                <div class="test-summary mb-3">
                    <h6>Test Summary</h6>
                    <div class="row">
                        <div class="col-sm-3">
                            <span class="badge bg-success">${summary.passed || 0} Passed</span>
                        </div>
                        <div class="col-sm-3">
                            <span class="badge bg-danger">${summary.failed || 0} Failed</span>
                        </div>
                        <div class="col-sm-3">
                            <span class="badge bg-warning">${summary.skipped || 0} Skipped</span>
                        </div>
                        <div class="col-sm-3">
                            <span class="badge bg-info">${summary.total || 0} Total</span>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Test details
        if (tests && tests.length > 0) {
            html += '<div class="test-details">';
            tests.forEach(test => {
                const statusClass = test.status === 'passed' ? 'test-success' : 
                                  test.status === 'failed' ? 'test-error' : 'test-warning';
                html += `
                    <div class="test-item ${statusClass}">
                        <strong>${test.name}</strong>
                        <span class="float-end">${test.status}</span>
                        ${test.message ? `<div class="test-message">${test.message}</div>` : ''}
                    </div>
                `;
            });
            html += '</div>';
        }
        
        // Errors
        if (errors && errors.length > 0) {
            html += '<div class="test-errors mt-3">';
            html += '<div class="modern-section-title text-danger">Errors</div>';
            errors.forEach(error => {
                html += `<div class="modern-alert modern-alert-error">
                    <div class="modern-alert-icon">❌</div>
                    <div class="modern-alert-content">
                        <div class="modern-alert-title">${error}</div>
                    </div>
                </div>`;
            });
            html += '</div>';
        }
        
        html += '</div>';
        
        return html;
    }
    
    formatTestStatus(status) {
        return `
            <div class="test-status-info">
                <p><strong>PHPUnit:</strong> ${status.phpunit_available ? '✅ Available' : '❌ Not Available'}</p>
                <p><strong>Test Files:</strong> ${status.test_files_count || 0} found</p>
                <p><strong>Last Run:</strong> ${status.last_run || 'Never'}</p>
            </div>
        `;
    }
    
    showLoading(message) {
        const container = document.getElementById('testResults');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border mb-2" role="status"></div>
                    <div>${message}</div>
                </div>
            `;
        }
    }
    
    hideLoading() {
        // Loading se oculta automáticamente al mostrar resultados
    }
    
    showError(message) {
        const container = document.getElementById('testResults');
        if (container) {
            container.innerHTML = `
                <div class="modern-alert modern-alert-error">
                    <div class="modern-alert-icon">❌</div>
                    <div class="modern-alert-content">
                        <div class="modern-alert-title">Error: ${message}</div>
                    </div>
                </div>`;
        }
    }
}

// Inicializar módulo
new TestsModule();

export default TestsModule;
