/**
 * Detector de Errores AJAX 500
 * Intercepta TODOS los requests AJAX para identificar cuál está fallando
 */

console.log('🔍 INICIANDO DETECTOR DE ERRORES AJAX 500...');

// Interceptar fetch nativo
const originalFetch = window.fetch;
window.fetch = function(...args) {
    const [url, options] = args;
    
    // Solo interceptar requests a admin-ajax.php
    if (url && url.includes('admin-ajax.php')) {
        console.log('🚨 AJAX INTERCEPTADO:', {
            url: url,
            method: options?.method || 'GET',
            body: options?.body,
            timestamp: new Date().toISOString()
        });
        
        // Si es FormData, intentar leer los datos
        if (options?.body instanceof FormData) {
            console.log('📋 FormData detectado:');
            for (let [key, value] of options.body.entries()) {
                console.log(`  - ${key}:`, value);
            }
        }
        
        return originalFetch.apply(this, args)
            .then(response => {
                if (!response.ok) {
                    console.error('❌ AJAX FAILED:', {
                        url: url,
                        status: response.status,
                        statusText: response.statusText,
                        timestamp: new Date().toISOString()
                    });
                    
                    // Intentar leer el cuerpo del error
                    return response.text().then(text => {
                        console.error('💀 ERROR RESPONSE BODY:', text);
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                } else {
                    console.log('✅ AJAX SUCCESS:', {
                        url: url,
                        status: response.status,
                        timestamp: new Date().toISOString()
                    });
                }
                return response;
            })
            .catch(error => {
                console.error('🔥 AJAX ERROR CAUGHT:', {
                    url: url,
                    error: error.message,
                    timestamp: new Date().toISOString()
                });
                throw error;
            });
    }
    
    return originalFetch.apply(this, args);
};

// Interceptar XMLHttpRequest también
const originalXHROpen = XMLHttpRequest.prototype.open;
const originalXHRSend = XMLHttpRequest.prototype.send;

XMLHttpRequest.prototype.open = function(method, url, ...args) {
    this._intercepted_url = url;
    this._intercepted_method = method;
    return originalXHROpen.apply(this, [method, url, ...args]);
};

XMLHttpRequest.prototype.send = function(data) {
    if (this._intercepted_url && this._intercepted_url.includes('admin-ajax.php')) {
        console.log('🚨 XHR AJAX INTERCEPTADO:', {
            url: this._intercepted_url,
            method: this._intercepted_method,
            data: data,
            timestamp: new Date().toISOString()
        });
        
        // Escuchar el resultado
        this.addEventListener('load', () => {
            if (this.status >= 400) {
                console.error('❌ XHR AJAX FAILED:', {
                    url: this._intercepted_url,
                    status: this.status,
                    statusText: this.statusText,
                    response: this.responseText,
                    timestamp: new Date().toISOString()
                });
            } else {
                console.log('✅ XHR AJAX SUCCESS:', {
                    url: this._intercepted_url,
                    status: this.status,
                    timestamp: new Date().toISOString()
                });
            }
        });
        
        this.addEventListener('error', () => {
            console.error('🔥 XHR AJAX ERROR:', {
                url: this._intercepted_url,
                status: this.status,
                statusText: this.statusText,
                timestamp: new Date().toISOString()
            });
        });
    }
    
    return originalXHRSend.apply(this, [data]);
};

// Interceptar jQuery AJAX si está disponible
if (typeof jQuery !== 'undefined') {
    const originalAjax = jQuery.ajax;
    jQuery.ajax = function(options) {
        if (options.url && options.url.includes('admin-ajax.php')) {
            console.log('🚨 JQUERY AJAX INTERCEPTADO:', {
                url: options.url,
                type: options.type || 'GET',
                data: options.data,
                timestamp: new Date().toISOString()
            });
            
            // Interceptar callbacks
            const originalSuccess = options.success;
            const originalError = options.error;
            
            options.success = function(data, textStatus, jqXHR) {
                console.log('✅ JQUERY AJAX SUCCESS:', {
                    url: options.url,
                    status: jqXHR.status,
                    timestamp: new Date().toISOString()
                });
                if (originalSuccess) {
                    return originalSuccess.apply(this, arguments);
                }
            };
            
            options.error = function(jqXHR, textStatus, errorThrown) {
                console.error('❌ JQUERY AJAX FAILED:', {
                    url: options.url,
                    status: jqXHR.status,
                    statusText: jqXHR.statusText,
                    response: jqXHR.responseText,
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    timestamp: new Date().toISOString()
                });
                if (originalError) {
                    return originalError.apply(this, arguments);
                }
            };
        }
        
        return originalAjax.apply(this, [options]);
    };
}

console.log('✅ Detector de AJAX 500 activado');
console.log('📋 Interceptando: fetch, XMLHttpRequest, jQuery.ajax');
console.log('🎯 Objetivo: admin-ajax.php requests');

// Función para limpiar interceptores
window.clearAjaxInterceptors = function() {
    window.fetch = originalFetch;
    XMLHttpRequest.prototype.open = originalXHROpen;
    XMLHttpRequest.prototype.send = originalXHRSend;
    
    if (typeof jQuery !== 'undefined') {
        jQuery.ajax = originalAjax;
    }
    
    console.log('🧹 Interceptores AJAX removidos');
};

console.log('💡 Para detener la interceptación: clearAjaxInterceptors()');
