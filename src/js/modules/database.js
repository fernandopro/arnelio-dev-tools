/**
 * Database Module - Dev-Tools Arquitectura 3.0
 * Módulo para gestión de base de datos
 */

class DatabaseModule {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupDatabase();
        });
    }
    
    setupDatabase() {
        console.log('🔧 Database: Module initialized');
        this.bindDatabaseEvents();
    }
    
    bindDatabaseEvents() {
        // Test Database Connection button
        const testDbBtn = document.querySelector('[data-action="test-database"]');
        if (testDbBtn) {
            testDbBtn.addEventListener('click', () => this.testConnection());
        }
        
        // Test Socket Connection button
        const testSocketBtn = document.querySelector('[data-action="test-socket"]');
        if (testSocketBtn) {
            testSocketBtn.addEventListener('click', () => this.testSocket());
        }
    }
    
    async testConnection() {
        console.log('🔧 Database: Testing connection');
        
        if (window.devTools) {
            try {
                const result = await window.devTools.makeAjaxRequest('test_connection');
                this.displayConnectionResult(result);
            } catch (error) {
                console.error('Database connection test failed:', error);
            }
        }
    }
    
    async testSocket() {
        console.log('🔧 Database: Testing socket');
        
        if (window.devTools) {
            try {
                const result = await window.devTools.makeAjaxRequest('test_socket');
                this.displaySocketResult(result);
            } catch (error) {
                console.error('Socket test failed:', error);
            }
        }
    }
    
    displayConnectionResult(result) {
        const container = document.querySelector('#database .connection-status');
        if (container) {
            const statusClass = result.success ? 'success' : 'error';
            container.className = `connection-status ${statusClass}`;
            container.innerHTML = `
                <h6>${result.success ? '✅' : '❌'} Connection Status</h6>
                <p>${result.message || 'Unknown status'}</p>
                ${result.details ? `<small>${result.details}</small>` : ''}
            `;
        }
    }
    
    displaySocketResult(result) {
        console.log('Socket test result:', result);
        // Implementar visualización de resultado de socket
    }
}

// Inicializar módulo
new DatabaseModule();

export default DatabaseModule;
