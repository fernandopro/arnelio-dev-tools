/**
 * Dev Tools Main Controller - Plugin Agnóstico
 * 
 * Sistema principal de desarrollo que coordina todos los módulos del dev-tools.
 * Implementa los protocolos establecidos en guia-uso-tests.md para:
 * - Logging interno vs output externo
 * - Sistema de debugging condicional con variables de entorno
 * - Integración con DevToolsTestCase y WordPress PHPUnit oficial
 * - Compatibilidad con WordPress y Bootstrap
 * 
 * @package DevTools
 * @subpackage DevTools
 * @version 2.0.0
 * @since 1.0.0
 * 
 * Arquitectura:
 * - ES6+ JavaScript moderno (sin jQuery)
 * - Sistema anti-deadlock compatible
 * - URLs dinámicas con Local by Flywheel
 * - Bootstrap para interface administrativa
 */

/**
 * Controlador principal del sistema Dev Tools
 * Coordina todos los módulos y funcionalidades según protocolos establecidos
 */
class DevToolsController {
    constructor() {

        // ⚠️ IMPORTANTE: Después de cambiar esta constante, DEBES compilar con 'npm run dev'
        // para que los cambios se apliquen en el archivo compilado de producción.
        // 
        // 🔧 CONTROL MANUAL DE VERBOSE LOGGING
        // Cambia esto a 'true' para activar logging detallado de debug
        // Cambia a 'false' para consola limpia (recomendado para testing normal)
        this.VERBOSE_DEBUG_LOGGING = true; // Cambiado a true para debugging

        // AJAX Debug Mode - activar para depurar errores 400
        this.AJAX_DEBUG_MODE = true;

        // Estado interno del sistema
        this.isInitialized = false;
        this.activeModules = new Map();
        this.internalLogs = [];
        this.debugMode = false;
        this.verboseMode = false;
        
        // CRÍTICO: Inicializar logger interno INMEDIATAMENTE
        // Para evitar errores al llamar logError() antes de setupInternalLogging()
        this.internalLogger = {
            logs: [],
            
            add: (level, message, data = null) => {
                const logEntry = {
                    timestamp: new Date().toISOString(),
                    level: level,
                    message: message,
                    data: data,
                    context: 'DevToolsController'
                };
                
                this.internalLogs.push(logEntry);
                
                // Mantener solo los últimos 1000 logs para evitar memory leaks
                if (this.internalLogs.length > 1000) {
                    this.internalLogs = this.internalLogs.slice(-1000);
                }
            }
        };
        
        // Referencias a módulos especializados
        this.testRunner = null;
        this.maintenanceManager = null;
        this.docsManager = null;
        this.settingsManager = null;
        
        // Configuración del sistema
        this.config = {
            ajaxUrl: '',
            nonce: '',
            debugMode: false,
            verboseMode: false,
            antiDeadlockEnabled: true
        };
        
        this.init();
    }

    /**
     * Detecta automáticamente la configuración de dev-tools del plugin host
     * Sistema plugin-agnóstico que no depende de nombres específicos
     * 
     * @returns {Object|null} Configuración detectada o null si no se encuentra
     */
    detectHostDevToolsConfig() {
        // Cache para evitar múltiples detecciones
        if (this._cachedHostConfig !== undefined) {
            return this._cachedHostConfig;
        }

        // Patrones comunes de nombres de configuración dev-tools
        const possibleConfigNames = [
            // Patrón genérico: [prefix]_dev_tools_config
            'dev_tools_config',
            'tkn_dev_tools_config',  // Retrocompatibilidad
            'wp_dev_tools_config',
            'plugin_dev_tools_config'
        ];

        // Buscar en variables globales
        for (const configName of possibleConfigNames) {
            if (typeof window[configName] !== 'undefined') {
                this._cachedHostConfig = window[configName];
                this.logInternal(`🔍 Configuración dev-tools detectada: ${configName}`, this._cachedHostConfig, 'minimal');
                return this._cachedHostConfig;
            }
        }

        // Buscar por patrones dinámicos en window
        for (const key in window) {
            if (key.endsWith('_dev_tools_config') && typeof window[key] === 'object') {
                this._cachedHostConfig = window[key];
                this.logInternal(`🔍 Configuración dev-tools detectada dinámicamente: ${key}`, this._cachedHostConfig, 'minimal');
                return this._cachedHostConfig;
            }
        }

        // No se encontró configuración
        this._cachedHostConfig = null;
        this.logInternal('⚠️ No se detectó configuración específica del plugin host, usando fallbacks', null, 'minimal');
        return null;
    }

    /**
     * Inicialización principal del sistema
     * Sigue protocolo de detección de modo verbose/debug establecido en guía
     */
    async init() {
        try {
            this.detectModes();
            this.loadConfiguration();
            this.setupInternalLogging();
            this.initializeModules();
            this.attachEventListeners();
            
            this.isInitialized = true;
            this.logInternal('DevToolsController inicializado correctamente', {
                timestamp: new Date().toISOString(),
                modules: Array.from(this.activeModules.keys()),
                debugMode: this.debugMode,
                verboseMode: this.verboseMode
            }, 'minimal'); // Usar nivel minimal para inicialización
            
            // Realizar verificaciones del sistema en background (no bloquear inicialización)
            setTimeout(() => {
                this.performSystemChecks();
            }, 100);
            
        } catch (error) {
            this.logError('Error durante la inicialización del DevToolsController', error);
        }
    }

    /**
     * Obtener información de debug AJAX
     * Llamar esta función desde la consola para diagnosticar problemas AJAX
     */
    async getAjaxDebugInfo() {
        if (!this.AJAX_DEBUG_MODE) {
            console.warn('AJAX Debug Mode no está activado. Actívalo para obtener más información.');
        }

        const debugInfo = {
            timestamp: new Date().toISOString(),
            controller_state: {
                isInitialized: this.isInitialized,
                debugMode: this.debugMode,
                verboseMode: this.verboseMode,
                AJAX_DEBUG_MODE: this.AJAX_DEBUG_MODE
            },
            config: this.config,
            test_actions: {}
        };

        // Probar algunas acciones básicas
        const testActions = ['ping', 'check_anti_deadlock', 'check_test_framework'];
        
        for (const action of testActions) {
            const wpAction = this.getWordPressAjaxAction();
            debugInfo.test_actions[action] = {
                command: action,
                wordpress_action: wpAction,
                expected_endpoint: `wp_ajax_${wpAction}`
            };
            
            // Intentar ping básico
            if (action === 'ping') {
                try {
                    console.log(`🔍 Probando ${fullAction}...`);
                    const response = await fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: fullAction })
                    });
                    
                    debugInfo.test_actions[action].test_result = {
                        status: response.status,
                        ok: response.ok,
                        response: response.ok ? await response.json() : await response.text()
                    };
                } catch (error) {
                    debugInfo.test_actions[action].test_result = {
                        error: error.message
                    };
                }
            }
        }

        console.log('🔍 AJAX Debug Info:', debugInfo);
        return debugInfo;
    }

    /**
     * Detectar modos de funcionamiento según protocolos de la guía
     * Implementa detección robusta de variables de entorno y parámetros
     */
    detectModes() {
        // Detección automática de configuración del plugin host
        const hostConfig = this.detectHostDevToolsConfig();
        
        // Detección de modo verbose (protocolo establecido)
        this.verboseMode = (
            // Desde configuración WordPress localizada (genérica)
            (hostConfig && hostConfig.verbose_mode) ||
            // Desde parámetros URL
            new URLSearchParams(window.location.search).has('verbose') ||
            // Desde localStorage para persistencia de sesión
            localStorage.getItem('dev_tools_verbose') === '1' ||
            // Detección de contexto de testing PHPUnit
            window.location.search.includes('phpunit') ||
            window.location.search.includes('test')
        );

        // Detección de modo debug (variables de entorno múltiples)
        this.debugMode = (
            // Variables de entorno específicas del sistema
            this.checkEnvironmentVariable('DEV_TOOLS_TESTS_DEBUG') ||
            this.checkEnvironmentVariable('DEV_TOOLS_TESTS_VERBOSE') ||
            // Desde configuración WordPress (genérica)
            (hostConfig && hostConfig.debug_mode) ||
            // Modo verbose implica debug
            this.verboseMode ||
            // Parámetro URL directo
            new URLSearchParams(window.location.search).has('debug')
        );

        // Detección específica de ejecución de tests para mayor verbosidad
        this.isTestExecution = (
            window.location.search.includes('phpunit') ||
            window.location.search.includes('test') ||
            this.checkEnvironmentVariable('DEV_TOOLS_TESTS_DEBUG') ||
            this.checkEnvironmentVariable('DEV_TOOLS_TESTS_VERBOSE')
        );

        // Sistema de logging inteligente - Solo mostrar información crítica durante inicialización
        // Respeta la configuración VERBOSE_DEBUG_LOGGING
        if (this.VERBOSE_DEBUG_LOGGING && this.verboseMode && this.isTestExecution) {
            // Durante ejecución de tests, mostrar información completa solo si verbose está activado
            console.log('%c🔧 DEV TOOLS - MODO TEST/VERBOSE ACTIVO', 
                'background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold;');
            console.log('📊 Configuración de testing detectada:', {
                verboseMode: this.verboseMode,
                debugMode: this.debugMode,
                isTestExecution: this.isTestExecution,
                timestamp: new Date().toISOString()
            });
        } else if (this.VERBOSE_DEBUG_LOGGING && this.verboseMode) {
            // Durante desarrollo normal, mostrar solo mensaje mínimo si verbose está activado
            console.log('%c🔧 DEV TOOLS', 'color: #2563eb; font-weight: bold;', '- Modo verbose activo');
        }
    }

    /**
     * Verificar variable de entorno según múltiples métodos
     * Implementa verificación robusta según mejores prácticas de la guía
     */
    checkEnvironmentVariable(varName) {
        // Detectar configuración del plugin host automáticamente
        const hostConfig = this.detectHostDevToolsConfig();
        
        // 1. Desde configuración WordPress localizada (genérica)
        if (hostConfig && 
            hostConfig.env_vars && 
            hostConfig.env_vars[varName] === '1') {
            return true;
        }

        // 2. Desde metaetiquetas en el DOM (insertadas por PHP)
        const metaTag = document.querySelector(`meta[name="dev-tools-env-${varName.toLowerCase()}"]`);
        if (metaTag && metaTag.content === '1') {
            return true;
        }

        // 3. Desde localStorage (para persistencia de sesión)
        if (localStorage.getItem(`dev_tools_env_${varName.toLowerCase()}`) === '1') {
            return true;
        }

        // 4. Desde parámetros URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get(varName.toLowerCase()) === '1') {
            return true;
        }

        return false;
    }

    /**
     * Encontrar la variable de configuración dinámica
     * Busca variables que terminen en '_dev_tools_config'
     */
    findDevToolsConfig() {
        // Buscar variables que terminen en '_dev_tools_config'
        const configVars = Object.keys(window).filter(key => 
            key.endsWith('_dev_tools_config')
        );
        
        if (this.verboseMode) {
            console.log('🔍 Variables de configuración encontradas:', configVars);
        }
        
        if (configVars.length > 0) {
            const configVar = configVars[0];
            if (this.verboseMode) {
                console.log(`✅ Usando configuración: ${configVar}`, window[configVar]);
            }
            return window[configVar];
        }
        
        // Fallback a variables genéricas comunes
        const fallbackVars = [
            'dev_tools_config',               // Genérico principal
            'wp_dev_tools_config',            // WordPress genérico
            'plugin_dev_tools_config'         // Plugin genérico
        ];
        
        for (const varName of fallbackVars) {
            if (typeof window[varName] !== 'undefined') {
                if (this.verboseMode) {
                    console.log(`✅ Usando fallback: ${varName}`, window[varName]);
                }
                return window[varName];
            }
        }
        
        if (this.verboseMode) {
            console.warn('⚠️ No se encontró configuración localizada. Sistema funcionará con configuración mínima.');
        }
        
        return null;
    }

    /**
     * Cargar configuración del sistema
     * Integra con configuración WordPress y Local by Flywheel
     */
    loadConfiguration() {
        // Configuración desde WordPress localizada (dinámica)
        const devToolsConfig = this.findDevToolsConfig();
        if (devToolsConfig) {
            this.config = {
                ...this.config,
                ...devToolsConfig
            };
            
            if (this.verboseMode) {
                console.log('✅ Configuración WordPress localizada cargada:', devToolsConfig);
            }
        } else {
            if (this.verboseMode) {
                console.warn('⚠️ Configuración WordPress no disponible - usando configuración mínima');
            }
        }

        // URLs dinámicas (protocolo Local by Flywheel)
        this.config.ajaxUrl = this.getAjaxUrl();
        this.config.adminUrl = this.getAdminUrl();
        this.config.siteUrl = this.getSiteUrl();

        // Configuración de debugging
        this.config.debugMode = this.debugMode;
        this.config.verboseMode = this.verboseMode;

        // Logging inteligente - Solo durante ejecución de tests o verbose explícito
        if (this.isTestExecution || (this.verboseMode && this.debugMode)) {
            this.logInternal('Configuración cargada', this.config);
        } else {
            // Logging mínimo durante inicialización normal
            this.logInternal('Configuración cargada - URLs dinámicas configuradas');
        }
    }

    /**
     * Obtener acción AJAX de WordPress
     * Devuelve la acción completa para wp_ajax_*
     */
    getWordPressAjaxAction() {
        return this.config.ajaxAction || 'dev_tools_ajax';
    }

    /**
     * Generar acción AJAX dinámicamente (DEPRECATED)
     * @deprecated Usar getWordPressAjaxAction() en su lugar
     */
    getAjaxAction(action) {
        // DEPRECATED: Mantener para compatibilidad temporal
        console.warn('getAjaxAction() está deprecado. Usar getWordPressAjaxAction()');
        return this.getWordPressAjaxAction();
    }

    /**
     * Obtener URL de AJAX dinámicamente
     * Sigue protocolo de URLs dinámicas establecido
     */
    getAjaxUrl() {
        // Detectar configuración del plugin host automáticamente
        const hostConfig = this.detectHostDevToolsConfig();
        
        // 1. Desde configuración localizada (método preferido)
        if (hostConfig && hostConfig.ajax_url) {
            return hostConfig.ajax_url;
        }
        
        // 2. Desde variable global WordPress
        if (typeof ajaxurl !== 'undefined') {
            return ajaxurl;
        }
        
        // 3. Construcción manual con puerto dinámico detectado
        const port = this.detectLocalPort();
        const baseUrl = port ? `http://localhost:${port}` : window.location.origin;
        return `${baseUrl}/wp-admin/admin-ajax.php`;
    }

    /**
     * Obtener URL de administración dinámicamente
     */
    getAdminUrl() {
        // Detectar configuración del plugin host automáticamente
        const hostConfig = this.detectHostDevToolsConfig();
        
        if (hostConfig && hostConfig.admin_url) {
            return hostConfig.admin_url;
        }
        
        const port = this.detectLocalPort();
        const baseUrl = port ? `http://localhost:${port}` : window.location.origin;
        return `${baseUrl}/wp-admin/`;
    }

    /**
     * Obtener URL del sitio dinámicamente
     */
    getSiteUrl() {
        // Detectar configuración del plugin host automáticamente
        const hostConfig = this.detectHostDevToolsConfig();
        
        if (hostConfig && hostConfig.site_url) {
            return hostConfig.site_url;
        }
        
        const port = this.detectLocalPort();
        return port ? `http://localhost:${port}` : window.location.origin;
    }

    /**
     * Detectar puerto dinámico de Local by Flywheel
     */
    detectLocalPort() {
        // 1. Desde configuración
        if (this.config.local_port) {
            return this.config.local_port;
        }

        // 2. Desde URL actual
        const currentPort = window.location.port;
        if (currentPort && currentPort !== '80' && currentPort !== '443') {
            return currentPort;
        }

        // 3. Desde metaetiqueta insertada por PHP
        const portMeta = document.querySelector('meta[name="dev-tools-local-port"]');
        if (portMeta && portMeta.content) {
            return portMeta.content;
        }

        return null;
    }

    /**
     * Configurar sistema de logging interno
     * Implementa protocolo de logging interno vs output externo de la guía
     */
    setupInternalLogging() {
        // El logger interno ya está inicializado en el constructor
        // Solo necesitamos configurar estilos de consola para output externo
        this.consoleStyles = {
            header: 'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 10px 16px; border-radius: 6px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.3);',
            success: 'background: linear-gradient(135deg, #059669, #047857); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            error: 'background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            warning: 'background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            info: 'background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold;',
            debug: 'background: #1f2937; color: #34d399; padding: 6px 10px; border-radius: 4px; font-family: "SF Mono", Monaco, Consolas, monospace; font-weight: 500;'
        };
    }

    /**
     * Logging interno inteligente con niveles
     * Sistema optimizado para reducir verbosidad durante inicialización
     */
    logInternal(message, data = null, level = 'normal') {
        // Siempre guardar en logger interno (silencioso)
        this.internalLogger.add('INFO', message, data);
        
        // Output externo inteligente basado en contexto y VERBOSE_DEBUG_LOGGING
        const shouldShowOutput = this.VERBOSE_DEBUG_LOGGING && this.shouldShowInternalOutput(level);
        
        if (shouldShowOutput) {
            if (data && typeof data === 'object') {
                console.log(`%c🔧 DEV TOOLS`, 'color: #6b7280; font-weight: 500;', message, data);
            } else {
                console.log(`%c🔧 DEV TOOLS`, 'color: #6b7280; font-weight: 500;', message);
            }
        }
    }

    /**
     * Determinar si mostrar output interno basado en contexto
     */
    shouldShowInternalOutput(level) {
        // Niveles:
        // - 'critical': Siempre mostrar (errores, tests importantes)
        // - 'normal': Solo durante test execution o debug completo
        // - 'minimal': Solo con verbose + debug explícito
        
        switch (level) {
            case 'critical':
                return true; // Siempre mostrar información crítica
                
            case 'normal':
                return this.isTestExecution || (this.verboseMode && this.debugMode);
                
            case 'minimal':
                return this.verboseMode && this.debugMode && this.isTestExecution;
                
            default:
                // Comportamiento por defecto: mostrar solo durante tests
                return this.isTestExecution;
        }
    }

    /**
     * Logging de errores interno
     */
    logError(message, error = null) {
        this.internalLogger.add('ERROR', message, error);
        
        // Los errores SÍ se muestran externamente siempre
        console.error(`%c❌ DEV TOOLS ERROR`, this.consoleStyles.error);
        console.error(message, error);
    }

    /**
     * Output externo condicional (solo en modo verbose)
     * Implementa protocolo de output condicional según guía
     */
    logVerbose(message, data = null, style = 'info') {
        // Logging interno SIEMPRE (silencioso)
        this.logInternal(`[VERBOSE] ${message}`, data);
        
        // Output externo SOLO en modo verbose Y si VERBOSE_DEBUG_LOGGING está activado
        if (this.VERBOSE_DEBUG_LOGGING && this.verboseMode) {
            console.log(`%c📊 DEV TOOLS`, this.consoleStyles[style]);
            if (data) {
                console.log(message, data);
            } else {
                console.log(message);
            }
        }
    }

    /**
     * Output de debug específico activado por variables de entorno
     * Implementa protocolo de debug condicional de la guía
     */
    logDebug(message, data = null, debugVariable = null) {
        // Logging interno SIEMPRE
        this.logInternal(`[DEBUG] ${message}`, data);
        
        // Output externo solo si debug está activado Y VERBOSE_DEBUG_LOGGING está activado
        let debugEnabled = this.debugMode;
        
        // Si se especifica variable de debug específica, verificarla
        if (debugVariable) {
            debugEnabled = this.checkEnvironmentVariable(debugVariable);
        }
        
        if (this.VERBOSE_DEBUG_LOGGING && debugEnabled) {
            console.log(`%c🔍 DEV TOOLS DEBUG`, this.consoleStyles.debug);
            if (data) {
                console.log(message, data);
            } else {
                console.log(message);
            }
        }
    }

    /**
     * Inicializar módulos especializados
     * Arquitectura modular siguiendo patrones establecidos
     */
    initializeModules() {
        try {
            // Módulo de Tests (DevToolsTestRunner ya existente)
            if (typeof DevToolsTestRunner !== 'undefined') {
                this.testRunner = new DevToolsTestRunner();
                this.activeModules.set('testRunner', this.testRunner);
                this.logInternal('Módulo TestRunner inicializado', null, 'minimal');
            }

            // Módulo de Maintenance (si existe)
            if (typeof DevToolsMaintenanceManager !== 'undefined') {
                this.maintenanceManager = new DevToolsMaintenanceManager();
                this.activeModules.set('maintenance', this.maintenanceManager);
                this.logInternal('Módulo Maintenance inicializado', null, 'minimal');
            }

            // Módulo de Documentación (si existe)
            if (typeof DevToolsDocsManager !== 'undefined') {
                this.docsManager = new DevToolsDocsManager();
                this.activeModules.set('docs', this.docsManager);
                this.logInternal('Módulo Docs inicializado', null, 'minimal');
            }

            // Módulo de Settings (si existe)
            if (typeof DevToolsSettingsManager !== 'undefined') {
                this.settingsManager = new DevToolsSettingsManager();
                this.activeModules.set('settings', this.settingsManager);
                this.logInternal('Módulo Settings inicializado', null, 'minimal');
            }

            this.logInternal('Módulos inicializados', {
                count: this.activeModules.size,
                modules: Array.from(this.activeModules.keys())
            }, 'minimal');

        } catch (error) {
            this.logError('Error inicializando módulos', error);
        }
    }

    /**
     * Adjuntar event listeners principales
     * Manejo de eventos unificado compatible con Bootstrap
     */
    attachEventListeners() {
        // Event delegation para toda la interfaz de dev-tools
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        document.addEventListener('change', this.handleGlobalChange.bind(this));
        document.addEventListener('submit', this.handleGlobalSubmit.bind(this));

        // Eventos específicos del sistema
        document.addEventListener('devtools:module:loaded', this.handleModuleLoaded.bind(this));
        document.addEventListener('devtools:test:completed', this.handleTestCompleted.bind(this));
        document.addEventListener('devtools:error', this.handleSystemError.bind(this));

        // Eventos de navegación para preservar estado
        window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));

        this.logInternal('Event listeners configurados', null, 'minimal');
    }

    /**
     * Manejador global de clicks
     * Coordina acciones entre módulos
     */
    handleGlobalClick(event) {
        const element = event.target.closest('[data-dev-action]');
        if (!element) return;

        const action = element.dataset.devAction;
        const module = element.dataset.devModule;
        const params = this.extractDataParams(element);

        this.logDebug(`Acción global: ${action}`, { module, params });

        // Delegar a módulo específico si está definido
        if (module && this.activeModules.has(module)) {
            const moduleInstance = this.activeModules.get(module);
            if (typeof moduleInstance.handleAction === 'function') {
                event.preventDefault();
                moduleInstance.handleAction(action, params, element);
                return;
            }
        }

        // Acciones globales del controlador principal
        switch (action) {
            case 'toggle_debug':
                this.toggleDebugMode();
                break;
                
            case 'toggle_verbose':
                this.toggleVerboseMode();
                break;
                
            case 'export_logs':
                this.exportInternalLogs();
                break;
                
            case 'clear_logs':
                this.clearInternalLogs();
                break;
                
            case 'system_status':
                this.showSystemStatus();
                break;
                
            default:
                this.logDebug(`Acción no reconocida: ${action}`);
                break;
        }
    }

    /**
     * Manejador global de cambios de formulario
     */
    handleGlobalChange(event) {
        const element = event.target;
        if (!element.hasAttribute('data-dev-setting')) return;

        const setting = element.dataset.devSetting;
        const value = element.type === 'checkbox' ? element.checked : element.value;

        this.updateSetting(setting, value);
        this.logDebug(`Setting actualizado: ${setting} = ${value}`);
    }

    /**
     * Manejador global de envío de formularios
     */
    handleGlobalSubmit(event) {
        const form = event.target;
        if (!form.hasAttribute('data-dev-form')) return;

        event.preventDefault();
        
        const formType = form.dataset.devForm;
        const formData = new FormData(form);
        
        this.handleFormSubmission(formType, formData, form);
    }

    /**
     * Extraer parámetros data-* de un elemento
     */
    extractDataParams(element) {
        const params = {};
        
        for (const [key, value] of Object.entries(element.dataset)) {
            if (key.startsWith('dev') && key !== 'devAction' && key !== 'devModule') {
                // Convertir devParamName a param_name
                const paramKey = key.replace(/^dev/, '').replace(/([A-Z])/g, '_$1').toLowerCase();
                params[paramKey] = value;
            }
        }
        
        return params;
    }

    /**
     * Realizar verificaciones del sistema
     * Compatibilidad con DevToolsTestCase y WordPress PHPUnit
     */
    async performSystemChecks() {
        const checks = {
            ajax_connectivity: false,
            wordpress_loaded: false,
            dev_tools_plugin: false,
            anti_deadlock_system: false,
            test_framework: false
        };

        try {
            // Verificar conectividad AJAX
            checks.ajax_connectivity = await this.checkAjaxConnectivity();
            
            // Verificar WordPress
            checks.wordpress_loaded = (typeof wp !== 'undefined' || typeof ajaxurl !== 'undefined');
            
            // Verificar plugin dev-tools (genérico)
            const hostConfig = this.detectHostDevToolsConfig();
            checks.dev_tools_plugin = (hostConfig !== null);
            
            // Verificar sistema anti-deadlock
            checks.anti_deadlock_system = await this.checkAntiDeadlockSystem();
            
            // Verificar framework de testing
            checks.test_framework = await this.checkTestFramework();

            this.logInternal('Verificaciones del sistema completadas', checks, 'minimal');
            
            // Notificar problemas encontrados solo si hay errores
            const failedChecks = Object.entries(checks).filter(([key, passed]) => !passed);
            if (failedChecks.length > 0) {
                this.logError('⚠️ Algunas verificaciones fallaron', failedChecks);
            } else if (this.isTestExecution) {
                // Solo mostrar éxito completo durante tests
                this.logInternal('✅ Todas las verificaciones pasaron', checks, 'normal');
            }

        } catch (error) {
            this.logError('Error en verificaciones del sistema', error);
        }

        this.systemChecks = checks;
    }

    /**
     * Verificar conectividad AJAX
     */
    async checkAjaxConnectivity() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 segundos timeout
            
            this.logInternal('🔍 Verificando conectividad AJAX...', { 
                url: this.config.ajaxUrl,
                wordpress_action: this.getWordPressAjaxAction(),
                command: 'ping'
            }, 'minimal');
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: this.getWordPressAjaxAction(),
                    action_type: 'ping',
                    nonce: this.config.nonce || ''
                }),
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (response.ok) {
                const result = await response.json();
                this.logInternal('✅ Conectividad AJAX OK', result, 'minimal');
                return true;
            } else {
                this.logError(`❌ Error HTTP en verificación AJAX: ${response.status} ${response.statusText}`, {
                    status: response.status,
                    statusText: response.statusText,
                    url: this.config.ajaxUrl
                });
                return false;
            }
        } catch (error) {
            this.logError('❌ Error en verificación de conectividad AJAX', {
                error: error.message,
                url: this.config.ajaxUrl,
                command: 'ping'
            });
            return false;
        }
    }

    /**
     * Verificar sistema anti-deadlock
     * Compatible con DevToolsTestCase según protocolos
     */
    async checkAntiDeadlockSystem() {
        try {
            this.logInternal('🔍 Verificando sistema anti-deadlock...', { 
                command: 'check_anti_deadlock'
            }, 'minimal');
            
            const response = await this.makeAjaxRequest('check_anti_deadlock', {}, { timeout: 5000 });
            
            if (response.success && response.data?.processes_ok) {
                this.logInternal('✅ Sistema anti-deadlock OK', response.data, 'minimal');
                return true;
            } else {
                this.logError('❌ Error en sistema anti-deadlock', response);
                return false;
            }
        } catch (error) {
            this.logError('❌ Error verificando sistema anti-deadlock', {
                error: error.message,
                command: 'check_anti_deadlock'
            });
            return false;
        }
    }

    /**
     * Verificar framework de testing WordPress PHPUnit
     */
    async checkTestFramework() {
        try {
            this.logInternal('🔍 Verificando framework de testing...', { 
                command: 'check_test_framework'
            }, 'minimal');
            
            const response = await this.makeAjaxRequest('check_test_framework', {}, { timeout: 5000 });
            
            if (response.success && response.data?.all_files_ok) {
                this.logInternal('✅ Framework de testing OK', response.data, 'minimal');
                return true;
            } else {
                this.logError('❌ Error en framework de testing', response);
                return false;
            }
        } catch (error) {
            this.logError('❌ Error verificando framework de testing', {
                error: error.message,
                command: 'check_test_framework'
            });
            return false;
        }
    }

    /**
     * Realizar petición AJAX genérica
     * Método centralizado para todas las comunicaciones AJAX
     */
    async makeAjaxRequest(command, data = {}, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            timeout: 10000
        };

        const requestOptions = { ...defaultOptions, ...options };
        
        // Configurar parámetros AJAX correctamente:
        // - action: Acción WordPress (wp_ajax_) 
        // - action_type: Comando interno del handler
        const requestData = new URLSearchParams({
            action: this.config.ajaxAction || 'dev_tools_ajax', // Acción WordPress
            action_type: command,                               // Comando interno
            nonce: this.config.nonce || '',
            ...data
        });

        // AJAX DEBUG: Log detallado cuando está habilitado
        if (this.AJAX_DEBUG_MODE) {
            console.group('🔍 AJAX DEBUG');
            console.log('Command:', command);
            console.log('WordPress Action:', this.config.ajaxAction);
            console.log('URL:', this.config.ajaxUrl);
            console.log('Nonce:', this.config.nonce);
            console.log('Request Data:', Object.fromEntries(requestData));
            console.log('Config Available:', !!this.config);
            console.log('Full Config:', this.config);
        }

        // Crear AbortController para timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), requestOptions.timeout);

        try {
            this.logDebug(`📡 Petición AJAX: ${command}`, { data, timeout: requestOptions.timeout });
            
            const response = await fetch(this.config.ajaxUrl, {
                method: requestOptions.method,
                headers: requestOptions.headers,
                body: requestData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // AJAX DEBUG: Log response details
            if (this.AJAX_DEBUG_MODE) {
                console.log('Response Status:', response.status);
                console.log('Response OK:', response.ok);
                console.log('Response Headers:', Object.fromEntries(response.headers));
            }

            if (!response.ok) {
                if (this.AJAX_DEBUG_MODE) {
                    const responseText = await response.clone().text();
                    console.log('Error Response Text:', responseText.substring(0, 500));
                    console.groupEnd();
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            
            // AJAX DEBUG: Log successful response
            if (this.AJAX_DEBUG_MODE) {
                console.log('Response Data:', result);
                console.groupEnd();
            }
            
            this.logDebug(`📥 Respuesta AJAX: ${command}`, result);
            
            return result;

        } catch (error) {
            clearTimeout(timeoutId);
            
            // AJAX DEBUG: Log error details
            if (this.AJAX_DEBUG_MODE) {
                console.error('AJAX Request Failed:', error);
                console.groupEnd();
            }
            
            // Información detallada del error para debugging
            const errorInfo = {
                command,
                wpAction: this.config.ajaxAction,
                url: this.config.ajaxUrl,
                error: error.message,
                data,
                options: requestOptions
            };
            
            this.logError(`❌ Error en petición AJAX: ${command}`, errorInfo);
            throw error;
        }
    }

    /**
     * Alternar modo debug
     */
    toggleDebugMode() {
        this.debugMode = !this.debugMode;
        this.config.debugMode = this.debugMode;
        
        // Persistir en localStorage
        localStorage.setItem('dev_tools_debug', this.debugMode ? '1' : '0');
        
        this.logVerbose(`Modo debug ${this.debugMode ? 'activado' : 'desactivado'}`);
        
        // Actualizar interfaz
        this.updateDebugModeUI();
    }

    /**
     * Alternar modo verbose
     */
    toggleVerboseMode() {
        this.verboseMode = !this.verboseMode;
        this.config.verboseMode = this.verboseMode;
        
        // Persistir en localStorage
        localStorage.setItem('dev_tools_verbose', this.verboseMode ? '1' : '0');
        
        console.log(`%c🔧 Modo verbose ${this.verboseMode ? 'activado' : 'desactivado'}`, 
            this.consoleStyles.info);
        
        // Actualizar interfaz
        this.updateVerboseModeUI();
    }

    /**
     * Exportar logs internos
     * Facilita debugging según protocolos establecidos
     */
    exportInternalLogs() {
        const logsData = {
            timestamp: new Date().toISOString(),
            system_info: {
                debug_mode: this.debugMode,
                verbose_mode: this.verboseMode,
                modules: Array.from(this.activeModules.keys()),
                system_checks: this.systemChecks
            },
            logs: this.internalLogs
        };

        const jsonData = JSON.stringify(logsData, null, 2);
        const filename = `dev-tools-logs-${new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-')}.json`;
        
        this.downloadAsFile(jsonData, filename, 'application/json');
        
        this.logVerbose(`Logs exportados: ${filename}`);
    }

    /**
     * Limpiar logs internos
     */
    clearInternalLogs() {
        const previousCount = this.internalLogs.length;
        this.internalLogs = [];
        
        this.logVerbose(`${previousCount} logs internos eliminados`);
    }

    /**
     * Mostrar estado del sistema
     */
    showSystemStatus() {
        const status = {
            initialized: this.isInitialized,
            debug_mode: this.debugMode,
            verbose_mode: this.verboseMode,
            active_modules: Array.from(this.activeModules.keys()),
            internal_logs_count: this.internalLogs.length,
            system_checks: this.systemChecks,
            configuration: {
                ajax_url: this.config.ajaxUrl,
                admin_url: this.config.adminUrl,
                site_url: this.config.siteUrl,
                anti_deadlock_enabled: this.config.antiDeadlockEnabled
            }
        };

        console.log('%c📊 ESTADO DEL SISTEMA DEV TOOLS', this.consoleStyles.header);
        console.table(status);
        
        this.logInternal('Estado del sistema consultado', status);
    }

    /**
     * Actualizar configuración específica
     */
    updateSetting(key, value) {
        this.config[key] = value;
        this.logInternal(`Setting actualizado: ${key}`, { value });
        
        // Persistir settings importantes
        if (['debugMode', 'verboseMode', 'antiDeadlockEnabled'].includes(key)) {
            localStorage.setItem(`dev_tools_${key}`, value);
        }
    }

    /**
     * Manejar envío de formularios
     */
    async handleFormSubmission(formType, formData, formElement) {
        try {
            this.logDebug(`Procesando formulario: ${formType}`);
            
            // Convertir FormData a objeto
            const data = {};
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Determinar comando AJAX según tipo de formulario
            let command = formType;
            
            // Realizar petición
            const response = await this.makeAjaxRequest(command, data);
            
            if (response.success) {
                this.showNotification('Formulario procesado correctamente', 'success');
                this.logVerbose(`Formulario ${formType} procesado exitosamente`, response.data);
            } else {
                throw new Error(response.data?.message || 'Error desconocido');
            }
            
        } catch (error) {
            this.showNotification(`Error procesando formulario: ${error.message}`, 'error');
            this.logError(`Error en formulario ${formType}`, error);
        }
    }

    /**
     * Mostrar notificación en interfaz
     * Compatible con Bootstrap alerts
     */
    showNotification(message, type = 'info', duration = 5000) {
        const alertClass = {
            success: 'alert-success',
            error: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info'
        }[type] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Buscar contenedor de notificaciones
        let container = document.getElementById('dev-tools-notifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'dev-tools-notifications';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1050';
            document.body.appendChild(container);
        }

        // Insertar notificación
        container.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-eliminar después del tiempo especificado
        if (duration > 0) {
            setTimeout(() => {
                const alert = container.lastElementChild;
                if (alert && alert.classList.contains('alert')) {
                    alert.remove();
                }
            }, duration);
        }

        this.logVerbose(`Notificación mostrada: ${type} - ${message}`);
    }

    /**
     * Actualizar interfaz de modo debug
     */
    updateDebugModeUI() {
        const debugToggles = document.querySelectorAll('[data-dev-action="toggle_debug"]');
        debugToggles.forEach(toggle => {
            if (toggle.type === 'checkbox') {
                toggle.checked = this.debugMode;
            } else {
                toggle.textContent = this.debugMode ? 'Desactivar Debug' : 'Activar Debug';
                toggle.classList.toggle('btn-success', this.debugMode);
                toggle.classList.toggle('btn-outline-secondary', !this.debugMode);
            }
        });
    }

    /**
     * Actualizar interfaz de modo verbose
     */
    updateVerboseModeUI() {
        const verboseToggles = document.querySelectorAll('[data-dev-action="toggle_verbose"]');
        verboseToggles.forEach(toggle => {
            if (toggle.type === 'checkbox') {
                toggle.checked = this.verboseMode;
            } else {
                toggle.textContent = this.verboseMode ? 'Desactivar Verbose' : 'Activar Verbose';
                toggle.classList.toggle('btn-info', this.verboseMode);
                toggle.classList.toggle('btn-outline-secondary', !this.verboseMode);
            }
        });
    }

    /**
     * Descargar contenido como archivo
     * Utilidad para exportaciones
     */
    downloadAsFile(content, filename, contentType = 'text/plain') {
        const blob = new Blob([content], { type: contentType });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    }

    /**
     * Manejadores de eventos del sistema
     */
    handleModuleLoaded(event) {
        const { module, instance } = event.detail;
        this.activeModules.set(module, instance);
        this.logVerbose(`Módulo cargado dinámicamente: ${module}`);
    }

    handleTestCompleted(event) {
        const { testName, result, duration } = event.detail;
        this.logVerbose(`Test completado: ${testName}`, { result, duration });
    }

    handleSystemError(event) {
        const { error, context } = event.detail;
        this.logError(`Error del sistema (${context})`, error);
    }

    handleBeforeUnload(event) {
        // Guardar estado importante antes de cerrar
        if (this.internalLogs.length > 0) {
            localStorage.setItem('dev_tools_last_session_logs', JSON.stringify({
                timestamp: new Date().toISOString(),
                logs: this.internalLogs.slice(-100) // Solo últimos 100 logs
            }));
        }
    }

    /**
     * API pública para módulos externos
     */
    getPublicAPI() {
        return {
            // Logging
            logInternal: this.logInternal.bind(this),
            logVerbose: this.logVerbose.bind(this),
            logDebug: this.logDebug.bind(this),
            logError: this.logError.bind(this),
            
            // Configuración
            getConfig: () => ({ ...this.config }),
            updateSetting: this.updateSetting.bind(this),
            
            // Comunicación
            makeAjaxRequest: this.makeAjaxRequest.bind(this),
            
            // Utilidades
            showNotification: this.showNotification.bind(this),
            downloadAsFile: this.downloadAsFile.bind(this),
            
            // Estado
            isInitialized: () => this.isInitialized,
            getActiveModules: () => Array.from(this.activeModules.keys()),
            getSystemChecks: () => ({ ...this.systemChecks })
        };
    }
}

/**
 * Inicialización automática del sistema
 * Se ejecuta cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', () => {
    // Exponer la clase DevToolsController globalmente para testing/debugging
    window.DevToolsController = DevToolsController;
    
    // Crear instancia global del controlador
    window.devToolsController = new DevToolsController();
    
    // Exponer API pública para módulos
    window.DevToolsAPI = window.devToolsController.getPublicAPI();
    
    // Evento para notificar que el sistema está listo
    document.dispatchEvent(new CustomEvent('devtools:ready', {
        detail: {
            controller: window.devToolsController,
            api: window.DevToolsAPI
        }
    }));
});

/**
 * Compatibilidad con inicialización manual
 * Para casos donde DOMContentLoaded ya se ejecutó
 */
if (document.readyState === 'loading') {
    // El código anterior se ejecutará automáticamente
} else {
    // DOM ya está listo, inicializar inmediatamente
    setTimeout(() => {
        if (!window.devToolsController) {
            // Exponer la clase DevToolsController globalmente
            window.DevToolsController = DevToolsController;
            
            // Crear instancia global
            window.devToolsController = new DevToolsController();
            window.DevToolsAPI = window.devToolsController.getPublicAPI();
            
            document.dispatchEvent(new CustomEvent('devtools:ready', {
                detail: {
                    controller: window.devToolsController,
                    api: window.DevToolsAPI
                }
            }));
        }
    }, 0);
}