/**
 * Performance Module JavaScript - Dev-Tools Arquitectura 3.0
 * 
 * Módulo frontend para monitoreo y análisis de rendimiento.
 * Proporciona interfaz interactiva para visualizar métricas,
 * ejecutar tests de rendimiento y aplicar optimizaciones.
 * 
 * @package DevTools
 * @subpackage JavaScript
 * @version 3.0
 * @since 1.0.0
 */

/**
 * Clase PerformanceModule
 * 
 * Maneja toda la funcionalidad del frontend para el módulo de rendimiento:
 * - Monitoreo en tiempo real
 * - Visualización de métricas
 * - Tests de rendimiento
 * - Análisis de consultas de BD
 * - Optimizaciones automáticas
 */
class PerformanceModule {
    
    constructor() {
        this.moduleName = 'performance';
        this.config = window.devToolsConfig || {};
        this.logger = new DevToolsClientLogger();
        
        // Estado del módulo
        this.isMonitoring = false;
        this.refreshInterval = null;
        this.performanceChart = null;
        this.currentData = null;
        
        // Configuración
        this.config = {
            refreshRate: 5000, // 5 segundos
            chartUpdateInterval: 1000,
            maxDataPoints: 20,
            autoRefresh: false
        };
        
        // Datos para gráficos
        this.chartData = {
            labels: [],
            loadTimes: [],
            memoryUsage: [],
            dbQueries: []
        };
        
        this.init();
    }
    
    /**
     * Inicializar el módulo
     */
    init() {
        this.log('Inicializando PerformanceModule');
        this.bindEvents();
        this.loadInitialData();
        this.initChart();
        this.startBrowserMonitoring();
    }
    
    /**
     * Vincular eventos
     */
    bindEvents() {
        const moduleContainer = document.getElementById('performance-module');
        if (!moduleContainer) {
            this.log('Container del módulo no encontrado', 'warn');
            return;
        }
        
        // Botones principales
        this.bindEvent('#refresh-performance', 'click', () => this.refreshData());
        this.bindEvent('#run-performance-test', 'click', () => this.runPerformanceTest());
        
        // Tabs
        this.bindEvent('#performance-tabs a[data-bs-toggle="tab"]', 'shown.bs.tab', (e) => {
            this.handleTabChange(e.target.getAttribute('href'));
        });
        
        // Botones de optimización
        this.bindEvent('#optimize-db', 'click', () => this.optimizeDatabase());
        this.bindEvent('#clear-all-cache', 'click', () => this.clearAllCache());
        this.bindEvent('#run-cleanup', 'click', () => this.runDatabaseCleanup());
        this.bindEvent('#generate-report', 'click', () => this.generateReport());
        
        // Auto-refresh toggle
        this.bindEvent('#auto-refresh-toggle', 'change', (e) => {
            this.toggleAutoRefresh(e.target.checked);
        });
        
        // Configuración de intervalo
        this.bindEvent('#refresh-interval', 'change', (e) => {
            this.config.refreshRate = parseInt(e.target.value) * 1000;
            if (this.config.autoRefresh) {
                this.stopAutoRefresh();
                this.startAutoRefresh();
            }
        });
    }
    
    /**
     * Cargar datos iniciales
     */
    async loadInitialData() {
        try {
            this.showLoading();
            await this.refreshData();
            this.hideLoading();
        } catch (error) {
            this.log('Error cargando datos iniciales: ' + error.message, 'error');
            this.showError('Error al cargar los datos de rendimiento');
        }
    }
    
    /**
     * Refrescar todos los datos
     */
    async refreshData() {
        try {
            this.log('Refrescando datos de rendimiento');
            
            // Obtener datos de rendimiento
            const data = await this.makeAjaxRequest('get_performance_data');
            this.currentData = data;
            
            // Actualizar métricas principales
            this.updateMainMetrics(data);
            
            // Actualizar gráfico
            this.updateChart(data);
            
            // Actualizar problemas detectados
            this.updateIssues(data.issues || []);
            
            // Actualizar tab activo
            this.updateActiveTab();
            
            this.log('Datos actualizados correctamente');
            
        } catch (error) {
            this.log('Error refrescando datos: ' + error.message, 'error');
            this.showError('Error al actualizar los datos');
        }
    }
    
    /**
     * Actualizar métricas principales
     */
    updateMainMetrics(data) {
        // Tiempo de carga
        const loadTimeElement = document.getElementById('load-time');
        if (loadTimeElement) {
            loadTimeElement.textContent = data.load_time ? data.load_time.toFixed(3) : '--';
            loadTimeElement.className = this.getMetricClass(data.load_time, [1, 3]);
        }
        
        // Uso de memoria
        const memoryElement = document.getElementById('memory-usage');
        if (memoryElement) {
            memoryElement.textContent = data.memory_usage ? data.memory_usage.toFixed(2) : '--';
            memoryElement.className = this.getMetricClass(data.memory_usage, [50, 100]);
        }
        
        // Consultas de BD
        const queriesElement = document.getElementById('db-queries');
        if (queriesElement) {
            queriesElement.textContent = data.db_queries || '--';
            queriesElement.className = this.getMetricClass(data.db_queries, [25, 50]);
        }
        
        // Puntuación de rendimiento
        const scoreElement = document.getElementById('performance-score');
        if (scoreElement) {
            scoreElement.textContent = data.performance_score || '--';
            scoreElement.className = this.getScoreClass(data.performance_score);
        }
    }
    
    /**
     * Obtener clase CSS basada en umbrales
     */
    getMetricClass(value, thresholds) {
        if (!value) return '';
        
        if (value <= thresholds[0]) return 'text-success';
        if (value <= thresholds[1]) return 'text-warning';
        return 'text-danger';
    }
    
    /**
     * Obtener clase CSS para puntuación
     */
    getScoreClass(score) {
        if (!score) return '';
        
        if (score >= 80) return 'text-success';
        if (score >= 60) return 'text-warning';
        return 'text-danger';
    }
    
    /**
     * Inicializar gráfico de rendimiento
     */
    initChart() {
        const canvas = document.getElementById('performance-chart');
        if (!canvas) {
            this.log('Canvas del gráfico no encontrado', 'warn');
            return;
        }
        
        // Verificar si Chart.js está disponible
        if (typeof Chart === 'undefined') {
            this.log('Chart.js no está disponible', 'warn');
            this.loadChartJs().then(() => this.createChart(canvas));
            return;
        }
        
        this.createChart(canvas);
    }
    
    /**
     * Cargar Chart.js dinámicamente
     */
    async loadChartJs() {
        return new Promise((resolve, reject) => {
            if (typeof Chart !== 'undefined') {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Crear gráfico
     */
    createChart(canvas) {
        const ctx = canvas.getContext('2d');
        
        this.performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.chartData.labels,
                datasets: [
                    {
                        label: 'Tiempo de Carga (s)',
                        data: this.chartData.loadTimes,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Memoria (MB)',
                        data: this.chartData.memoryUsage,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Consultas BD',
                        data: this.chartData.dbQueries,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Tiempo'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Tiempo (s)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Memoria (MB)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'right',
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Métricas de Rendimiento en Tiempo Real'
                    }
                }
            }
        });
    }
    
    /**
     * Actualizar gráfico con nuevos datos
     */
    updateChart(data) {
        if (!this.performanceChart) return;
        
        const now = new Date().toLocaleTimeString();
        
        // Agregar nuevos datos
        this.chartData.labels.push(now);
        this.chartData.loadTimes.push(data.load_time || 0);
        this.chartData.memoryUsage.push(data.memory_usage || 0);
        this.chartData.dbQueries.push(data.db_queries || 0);
        
        // Mantener solo los últimos N puntos
        if (this.chartData.labels.length > this.config.maxDataPoints) {
            this.chartData.labels.shift();
            this.chartData.loadTimes.shift();
            this.chartData.memoryUsage.shift();
            this.chartData.dbQueries.shift();
        }
        
        // Actualizar gráfico
        this.performanceChart.update('none');
    }
    
    /**
     * Actualizar problemas detectados
     */
    updateIssues(issues) {
        const container = document.getElementById('performance-issues');
        if (!container) return;
        
        if (!issues || issues.length === 0) {
            container.innerHTML = `
                <div class="text-center text-success">
                    <i class="bi bi-check-circle fs-2"></i>
                    <p class="mt-2">No se detectaron problemas de rendimiento</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        issues.forEach(issue => {
            const badgeClass = issue.type === 'danger' ? 'bg-danger' : 'bg-warning';
            html += `
                <div class="alert alert-${issue.type} alert-dismissible fade show" role="alert">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <h6 class="alert-heading">${issue.title}</h6>
                            <p class="mb-1">${issue.description}</p>
                            <small class="text-muted">
                                <strong>Recomendación:</strong> ${issue.recommendation}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Manejar cambio de tab
     */
    async handleTabChange(tabId) {
        this.log(`Cambiando a tab: ${tabId}`);
        
        switch (tabId) {
            case '#database':
                await this.loadDatabaseData();
                break;
            case '#plugins':
                await this.loadPluginsData();
                break;
            case '#optimization':
                await this.loadOptimizationData();
                break;
        }
    }
    
    /**
     * Cargar datos de base de datos
     */
    async loadDatabaseData() {
        try {
            const data = await this.makeAjaxRequest('get_database_queries');
            this.renderQueriesTable(data);
        } catch (error) {
            this.log('Error cargando datos de BD: ' + error.message, 'error');
        }
    }
    
    /**
     * Renderizar tabla de consultas
     */
    renderQueriesTable(queries) {
        const tbody = document.querySelector('#queries-table tbody');
        if (!tbody) return;
        
        if (!queries || queries.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        No hay datos de consultas disponibles
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        queries.forEach(query => {
            const timeClass = query.total_time > 100 ? 'text-danger' : 
                           query.total_time > 50 ? 'text-warning' : 'text-success';
            
            html += `
                <tr>
                    <td>
                        <code class="text-truncate d-inline-block" style="max-width: 300px;" 
                              title="${this.escapeHtml(query.sql)}">
                            ${this.escapeHtml(query.sql.substring(0, 100))}${query.sql.length > 100 ? '...' : ''}
                        </code>
                    </td>
                    <td class="${timeClass}">
                        <strong>${query.total_time}</strong>
                        ${query.calls > 1 ? `<br><small>(${query.avg_time} avg)</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-secondary">${query.calls}</span>
                    </td>
                    <td>
                        <small class="text-muted">${query.function || 'unknown'}</small>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }
    
    /**
     * Cargar datos de plugins
     */
    async loadPluginsData() {
        try {
            const data = await this.makeAjaxRequest('get_plugin_performance');
            this.renderPluginsPerformance(data);
        } catch (error) {
            this.log('Error cargando datos de plugins: ' + error.message, 'error');
        }
    }
    
    /**
     * Renderizar rendimiento de plugins
     */
    renderPluginsPerformance(plugins) {
        const container = document.getElementById('plugins-performance');
        if (!container) return;
        
        if (!plugins || plugins.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted">
                    <i class="bi bi-puzzle"></i>
                    <p>No hay datos de rendimiento de plugins disponibles</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="row">';
        
        plugins.forEach(plugin => {
            const impactClass = plugin.impact_score > 50 ? 'danger' : 
                              plugin.impact_score > 25 ? 'warning' : 'success';
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">${plugin.name}</h6>
                                    <small class="text-muted">v${plugin.version}</small>
                                </div>
                                <span class="badge bg-${impactClass}">
                                    Impacto: ${plugin.impact_score}
                                </span>
                            </div>
                            <div class="mt-2">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted">Tiempo</small>
                                        <div>${(plugin.load_time * 1000).toFixed(1)}ms</div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Memoria</small>
                                        <div>${plugin.memory_usage}MB</div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Hooks</small>
                                        <div>${plugin.hooks_count}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    /**
     * Cargar datos de optimización
     */
    async loadOptimizationData() {
        // Los datos de optimización vienen en el currentData
        if (!this.currentData || !this.currentData.recommendations) return;
        
        this.renderOptimizationSuggestions(this.currentData.recommendations);
    }
    
    /**
     * Renderizar sugerencias de optimización
     */
    renderOptimizationSuggestions(recommendations) {
        const container = document.getElementById('optimization-suggestions');
        if (!container) return;
        
        if (!recommendations || recommendations.length === 0) {
            container.innerHTML = `
                <div class="text-center text-success">
                    <i class="bi bi-check-circle fs-2"></i>
                    <p class="mt-2">El sistema está optimizado</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        recommendations.forEach(rec => {
            const priorityClass = rec.priority === 'high' ? 'danger' : 
                                rec.priority === 'medium' ? 'warning' : 'info';
            
            html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-title">${rec.title}</h6>
                                <p class="card-text">${rec.description}</p>
                            </div>
                            <span class="badge bg-${priorityClass}">
                                ${rec.priority.toUpperCase()}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Ejecutar test de rendimiento
     */
    async runPerformanceTest() {
        const button = document.getElementById('run-performance-test');
        if (!button) return;
        
        const originalText = button.innerHTML;
        
        try {
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Ejecutando...';
            button.disabled = true;
            
            this.log('Ejecutando test de rendimiento');
            
            const results = await this.makeAjaxRequest('run_performance_test');
            
            this.showTestResults(results);
            this.log('Test de rendimiento completado');
            
        } catch (error) {
            this.log('Error en test de rendimiento: ' + error.message, 'error');
            this.showError('Error al ejecutar el test de rendimiento');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    /**
     * Mostrar resultados del test
     */
    showTestResults(results) {
        const modal = this.createTestResultsModal(results);
        document.body.appendChild(modal);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Limpiar modal al cerrar
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }
    
    /**
     * Crear modal de resultados
     */
    createTestResultsModal(results) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-clipboard-data"></i>
                            Resultados del Test de Rendimiento
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>URL Testeada:</strong> ${results.url_tested || 'N/A'}
                            </div>
                            <div class="col-md-6">
                                <strong>Duración del Test:</strong> ${results.test_duration || 0}ms
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <h2 class="display-4 ${this.getScoreClass(results.overall_score)}">
                                ${results.overall_score || 0}/100
                            </h2>
                            <p class="lead">Puntuación General</p>
                        </div>
                        
                        ${this.renderTestMetrics(results.metrics || {})}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        return modal;
    }
    
    /**
     * Renderizar métricas del test
     */
    renderTestMetrics(metrics) {
        let html = '<div class="row">';
        
        Object.entries(metrics).forEach(([key, value]) => {
            const title = this.formatMetricTitle(key);
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">${title}</h6>
                            ${this.formatMetricValue(key, value)}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    /**
     * Formatear título de métrica
     */
    formatMetricTitle(key) {
        const titles = {
            'response_time': 'Tiempo de Respuesta',
            'database_performance': 'Rendimiento de BD',
            'memory_efficiency': 'Eficiencia de Memoria',
            'cache_effectiveness': 'Efectividad del Cache'
        };
        
        return titles[key] || key.replace('_', ' ').toUpperCase();
    }
    
    /**
     * Formatear valor de métrica
     */
    formatMetricValue(key, value) {
        if (typeof value === 'object') {
            let html = '';
            Object.entries(value).forEach(([subKey, subValue]) => {
                html += `<p class="mb-1"><strong>${subKey}:</strong> ${subValue}</p>`;
            });
            return html;
        }
        
        return `<p class="card-text">${value}</p>`;
    }
    
    /**
     * Optimizar base de datos
     */
    async optimizeDatabase() {
        if (!confirm('¿Estás seguro de que quieres optimizar la base de datos? Esta operación puede tardar unos minutos.')) {
            return;
        }
        
        const button = document.getElementById('optimize-db');
        if (!button) return;
        
        const originalText = button.innerHTML;
        
        try {
            button.innerHTML = '<i class="bi bi-gear-fill"></i> Optimizando...';
            button.disabled = true;
            
            const results = await this.makeAjaxRequest('optimize_database');
            
            this.showSuccess(`
                Base de datos optimizada correctamente:
                <br>• ${results.optimized_tables} tablas optimizadas
                <br>• ${results.space_saved}MB de espacio liberado
                ${results.errors.length > 0 ? '<br>• ' + results.errors.length + ' errores encontrados' : ''}
            `);
            
            // Refrescar datos
            await this.refreshData();
            
        } catch (error) {
            this.log('Error optimizando BD: ' + error.message, 'error');
            this.showError('Error al optimizar la base de datos');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    /**
     * Limpiar todo el cache
     */
    async clearAllCache() {
        if (!confirm('¿Estás seguro de que quieres limpiar todo el cache?')) {
            return;
        }
        
        const button = document.getElementById('clear-all-cache');
        if (!button) return;
        
        const originalText = button.innerHTML;
        
        try {
            button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Limpiando...';
            button.disabled = true;
            
            const results = await this.makeAjaxRequest('clear_performance_cache');
            
            let message = 'Cache limpiado:';
            Object.entries(results).forEach(([type, success]) => {
                message += `<br>• ${type}: ${success ? '✓' : '✗'}`;
            });
            
            this.showSuccess(message);
            
            // Refrescar datos
            await this.refreshData();
            
        } catch (error) {
            this.log('Error limpiando cache: ' + error.message, 'error');
            this.showError('Error al limpiar el cache');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    /**
     * Ejecutar limpieza de base de datos
     */
    async runDatabaseCleanup() {
        if (!confirm('¿Estás seguro de que quieres ejecutar la limpieza de base de datos? Esta operación eliminará datos innecesarios.')) {
            return;
        }
        
        // Esta funcionalidad se puede implementar como parte de la optimización
        await this.optimizeDatabase();
    }
    
    /**
     * Generar reporte
     */
    async generateReport() {
        try {
            if (!this.currentData) {
                await this.refreshData();
            }
            
            const report = this.createPerformanceReport();
            this.downloadReport(report);
            
        } catch (error) {
            this.log('Error generando reporte: ' + error.message, 'error');
            this.showError('Error al generar el reporte');
        }
    }
    
    /**
     * Crear reporte de rendimiento
     */
    createPerformanceReport() {
        const data = this.currentData || {};
        const timestamp = new Date().toLocaleString();
        
        return `
# Reporte de Rendimiento - ${timestamp}

## Métricas Principales
- **Tiempo de Carga:** ${data.load_time || 'N/A'}s
- **Uso de Memoria:** ${data.memory_usage || 'N/A'}MB
- **Pico de Memoria:** ${data.memory_peak || 'N/A'}MB
- **Consultas de BD:** ${data.db_queries || 'N/A'}
- **Tiempo de BD:** ${data.db_query_time || 'N/A'}ms
- **Puntuación:** ${data.performance_score || 'N/A'}/100

## Problemas Detectados
${(data.issues || []).map(issue => `- **${issue.title}:** ${issue.description}`).join('\n')}

## Recomendaciones
${(data.recommendations || []).map(rec => `- **${rec.title}:** ${rec.description}`).join('\n')}

---
Generado por Dev-Tools Arquitectura 3.0
        `.trim();
    }
    
    /**
     * Descargar reporte
     */
    downloadReport(content) {
        const blob = new Blob([content], { type: 'text/markdown' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.href = url;
        a.download = `performance-report-${new Date().toISOString().split('T')[0]}.md`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        this.showSuccess('Reporte descargado correctamente');
    }
    
    /**
     * Iniciar monitoreo del navegador
     */
    startBrowserMonitoring() {
        if (!window.performance) {
            this.log('Performance API no disponible', 'warn');
            return;
        }
        
        // Monitorear métricas del navegador
        this.monitorBrowserMetrics();
    }
    
    /**
     * Monitorear métricas del navegador
     */
    monitorBrowserMetrics() {
        // Observar cambios en el rendimiento
        if ('PerformanceObserver' in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        this.log(`Performance: ${entry.name} - ${entry.duration}ms`);
                    });
                });
                
                observer.observe({ entryTypes: ['measure', 'navigation'] });
            } catch (error) {
                this.log('Error configurando PerformanceObserver: ' + error.message, 'warn');
            }
        }
    }
    
    /**
     * Alternar auto-refresh
     */
    toggleAutoRefresh(enabled) {
        this.config.autoRefresh = enabled;
        
        if (enabled) {
            this.startAutoRefresh();
        } else {
            this.stopAutoRefresh();
        }
        
        this.log(`Auto-refresh ${enabled ? 'activado' : 'desactivado'}`);
    }
    
    /**
     * Iniciar auto-refresh
     */
    startAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        this.refreshInterval = setInterval(() => {
            this.refreshData();
        }, this.config.refreshRate);
    }
    
    /**
     * Detener auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    /**
     * Actualizar tab activo
     */
    updateActiveTab() {
        const activeTab = document.querySelector('#performance-tabs .nav-link.active');
        if (activeTab) {
            const tabId = activeTab.getAttribute('href');
            this.handleTabChange(tabId);
        }
    }
    
    /**
     * Mostrar loading
     */
    showLoading() {
        const container = document.getElementById('performance-module');
        if (container) {
            container.style.opacity = '0.7';
            container.style.pointerEvents = 'none';
        }
    }
    
    /**
     * Ocultar loading
     */
    hideLoading() {
        const container = document.getElementById('performance-module');
        if (container) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        }
    }
    
    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Cleanup al destruir el módulo
     */
    destroy() {
        this.stopAutoRefresh();
        
        if (this.performanceChart) {
            this.performanceChart.destroy();
            this.performanceChart = null;
        }
        
        this.log('PerformanceModule destruido');
    }
    
    /**
     * Realizar petición AJAX
     * Método centralizado para todas las comunicaciones AJAX del módulo
     */
    async makeAjaxRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', `${this.config.actionPrefix || 'dev_tools'}_dev_tools`);
        formData.append('action_type', action);
        formData.append('nonce', this.config.nonce);
        
        // Agregar datos adicionales
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
        });

        this.log(`Making AJAX request: ${action}`, 'debug');

        const response = await fetch(this.config.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.data?.message || 'Request failed');
        }

        this.log(`AJAX response received: ${action}`, 'debug');
        return result.data;
    }
    
    /**
     * Mostrar mensaje de éxito
     */
    showSuccess(message) {
        this.showAlert('success', message);
    }
    
    /**
     * Mostrar mensaje de error
     */
    showError(message) {
        this.showAlert('danger', message);
    }
    
    /**
     * Mostrar alerta genérica
     */
    showAlert(type, message) {
        // Crear alerta Bootstrap 5
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Buscar contenedor de alertas o crear uno
        let alertContainer = document.getElementById('performance-alerts');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'performance-alerts';
            alertContainer.className = 'alert-container mb-3';
            
            const moduleContainer = document.getElementById('performance-module');
            if (moduleContainer) {
                moduleContainer.insertBefore(alertContainer, moduleContainer.firstChild);
            }
        }
        
        // Agregar alerta
        alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            const alerts = alertContainer.querySelectorAll('.alert');
            if (alerts.length > 0) {
                alerts[0].remove();
            }
        }, 5000);
    }
    
    /**
     * Log interno del módulo
     */
    log(message, level = 'info') {
        if (this.logger) {
            this.logger.log(message, level, 'PerformanceModule');
        } else {
            console.log(`[PerformanceModule-${level.toUpperCase()}] ${message}`);
        }
    }
    
    /**
     * Vincular evento del DOM
     */
    bindEvent(selector, event, handler) {
        const element = document.querySelector(selector);
        if (element) {
            element.addEventListener(event, handler);
            this.log(`Event bound: ${event} on ${selector}`, 'debug');
        } else {
            this.log(`Element not found for binding: ${selector}`, 'warn');
        }
    }
    
    /**
     * Formatear bytes para visualización
     */
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    /**
     * Formatear duración en milisegundos
     */
    formatDuration(ms) {
        if (ms < 1000) {
            return `${Math.round(ms)}ms`;
        } else if (ms < 60000) {
            return `${(ms / 1000).toFixed(1)}s`;
        } else {
            const minutes = Math.floor(ms / 60000);
            const seconds = ((ms % 60000) / 1000).toFixed(0);
            return `${minutes}m ${seconds}s`;
        }
    }
    
    /**
     * Validar configuración del módulo
     */
    validateConfig() {
        const required = ['ajaxUrl', 'nonce'];
        const missing = required.filter(key => !this.config[key]);
        
        if (missing.length > 0) {
            throw new Error(`Missing required config: ${missing.join(', ')}`);
        }
        
        this.log('Configuration validated', 'debug');
        return true;
    }
}

// COMENTADO: Auto-inicialización deshabilitada para evitar ejecuciones no deseadas
/*
// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('performance-module')) {
        window.performanceModule = new PerformanceModule();
    }
});
*/

// Función para inicialización manual del PerformanceModule
window.initializePerformanceModule = function() {
    if (window.performanceModule) {
        return window.performanceModule; // Ya inicializado
    }
    
    if (document.getElementById('performance-module')) {
        window.performanceModule = new PerformanceModule();
        console.log('[DEV-TOOLS] Performance module initialized manually');
        return window.performanceModule;
    }
    
    console.log('[DEV-TOOLS] Performance module container not found');
    return null;
};

// Exportar para uso como módulo
export { PerformanceModule };
