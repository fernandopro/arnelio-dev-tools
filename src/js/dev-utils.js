/**
 * Dev Tools - Utilities
 * Funciones de utilidad para el sistema de desarrollo
 * 
 * @package DevTools
 * @subpackage DevTools
 * @since 1.0.0
 */

/**
 * Utilidades de Debug y Desarrollo
 */
class DevToolsUtils {
    /**
     * Logger mejorado para debugging
     */
    static logger = {
        /**
         * Log con timestamp y categoría
         */
        log(message, category = 'INFO', data = null) {
            const timestamp = new Date().toISOString();
            const prefix = `[${timestamp}] [${category}]`;
            
            if (data) {
                console.log(`${prefix} ${message}`, data);
            } else {
                console.log(`${prefix} ${message}`);
            }
        },

        error(message, error = null) {
            this.log(message, 'ERROR', error);
        },

        warn(message, data = null) {
            this.log(message, 'WARN', data);
        },

        debug(message, data = null) {
            if (window.tkn_dev_tools_config?.debug_mode) {
                this.log(message, 'DEBUG', data);
            }
        },

        success(message, data = null) {
            this.log(message, 'SUCCESS', data);
        }
    };

    /**
     * Utilidades para manejo de archivos
     */
    static fileUtils = {
        /**
         * Descarga contenido como archivo
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
        },

        /**
         * Copia texto al portapapeles
         */
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                // Fallback para navegadores sin clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                
                document.body.appendChild(textArea);
                textArea.select();
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                return successful;
            }
        },

        /**
         * Obtiene extensión de archivo
         */
        getFileExtension(filename) {
            return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2);
        },

        /**
         * Valida tipo de archivo
         */
        isValidFileType(filename, allowedTypes = []) {
            if (allowedTypes.length === 0) return true;
            
            const extension = this.getFileExtension(filename).toLowerCase();
            return allowedTypes.includes(extension);
        }
    };

    /**
     * Utilidades de DOM
     */
    static domUtils = {
        /**
         * Crea elemento con atributos
         */
        createElement(tag, attributes = {}, textContent = '') {
            const element = document.createElement(tag);
            
            Object.entries(attributes).forEach(([key, value]) => {
                if (key === 'className') {
                    element.className = value;
                } else if (key === 'dataset') {
                    Object.entries(value).forEach(([dataKey, dataValue]) => {
                        element.dataset[dataKey] = dataValue;
                    });
                } else {
                    element.setAttribute(key, value);
                }
            });
            
            if (textContent) {
                element.textContent = textContent;
            }
            
            return element;
        },

        /**
         * Elimina todos los hijos de un elemento
         */
        clearChildren(element) {
            while (element.firstChild) {
                element.removeChild(element.firstChild);
            }
        },

        /**
         * Verifica si un elemento está visible
         */
        isElementVisible(element) {
            return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
        }
    };

    /**
     * Utilidades de validación
     */
    static validation = {
        /**
         * Valida email
         */
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Valida URL
         */
        isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        /**
         * Valida JSON
         */
        isValidJson(jsonString) {
            try {
                JSON.parse(jsonString);
                return true;
            } catch {
                return false;
            }
        },

        /**
         * Sanitiza HTML básico
         */
        sanitizeHtml(html) {
            const div = document.createElement('div');
            div.textContent = html;
            return div.innerHTML;
        }
    };

    /**
     * Utilidades de performance
     */
    static performance = {
        /**
         * Debounce function
         */
        debounce(func, wait, immediate = false) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func(...args);
                };
                
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                
                if (callNow) func(...args);
            };
        },

        /**
         * Throttle function
         */
        throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Mide tiempo de ejecución
         */
        measureTime(func, label = 'Operation') {
            const start = performance.now();
            const result = func();
            const end = performance.now();
            
            DevToolsUtils.logger.debug(`${label} took ${(end - start).toFixed(2)} milliseconds`);
            return result;
        }
    };

    /**
     * Utilidades de formato
     */
    static format = {
        /**
         * Formatea bytes a tamaño legible
         */
        formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        /**
         * Formatea número con separadores
         */
        formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        /**
         * Formatea fecha relativa
         */
        formatRelativeTime(date) {
            const now = new Date();
            const targetDate = new Date(date);
            const diffTime = Math.abs(now - targetDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return 'Hoy';
            if (diffDays === 1) return 'Ayer';
            if (diffDays < 7) return `Hace ${diffDays} días`;
            if (diffDays < 30) return `Hace ${Math.ceil(diffDays / 7)} semanas`;
            return `Hace ${Math.ceil(diffDays / 30)} meses`;
        },

        /**
         * Trunca texto con ellipsis
         */
        truncateText(text, maxLength = 100) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }
    };
}

/**
 * Code Highlighter Simple
 */
class SimpleCodeHighlighter {
    constructor() {
        this.keywords = [
            'function', 'const', 'let', 'var', 'if', 'else', 'for', 'while', 
            'return', 'class', 'extends', 'import', 'export', 'default',
            'php', 'echo', 'public', 'private', 'protected', 'static'
        ];
    }

    /**
     * Aplica resaltado básico de sintaxis
     */
    highlight(code, language = 'javascript') {
        let highlighted = this.escapeHtml(code);
        
        // Strings
        highlighted = highlighted.replace(/(["'`])([^"'`]*)\1/g, '<span class="string">$1$2$1</span>');
        
        // Comments
        highlighted = highlighted.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
        highlighted = highlighted.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');
        
        // Keywords
        this.keywords.forEach(keyword => {
            const regex = new RegExp(`\\b${keyword}\\b`, 'g');
            highlighted = highlighted.replace(regex, `<span class="keyword">${keyword}</span>`);
        });
        
        // Numbers
        highlighted = highlighted.replace(/\b\d+\.?\d*\b/g, '<span class="number">$&</span>');
        
        return highlighted;
    }

    /**
     * Escapa HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/**
 * Test Results Formatter
 */
class TestResultsFormatter {
    /**
     * Formatea resultados de PHPUnit
     */
    static formatPhpUnitResults(output) {
        const lines = output.split('\n');
        const results = [];
        
        lines.forEach(line => {
            if (line.includes('OK') && line.includes('test')) {
                results.push({
                    type: 'success',
                    message: line.trim()
                });
            } else if (line.includes('FAILURES') || line.includes('ERRORS')) {
                results.push({
                    type: 'error',
                    message: line.trim()
                });
            } else if (line.includes('WARNINGS')) {
                results.push({
                    type: 'warning',
                    message: line.trim()
                });
            }
        });
        
        return results;
    }

    /**
     * Genera HTML para mostrar resultados
     */
    static generateResultsHtml(results) {
        if (!results || results.length === 0) {
            return '<div class="alert alert-info">No hay resultados disponibles</div>';
        }
        
        return results.map(result => {
            const alertClass = result.type === 'error' ? 'danger' : result.type;
            return `
                <div class="alert alert-${alertClass}">
                    <i class="bi bi-${this.getIconForType(result.type)}"></i>
                    ${result.message}
                </div>
            `;
        }).join('');
    }

    /**
     * Obtiene icono apropiado para el tipo de resultado
     */
    static getIconForType(type) {
        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

/**
 * Inicialización de utilidades
 */
document.addEventListener('DOMContentLoaded', () => {
    // Agregar utilidades al objeto global
    window.DevToolsUtils = DevToolsUtils;
    window.SimpleCodeHighlighter = SimpleCodeHighlighter;
    window.TestResultsFormatter = TestResultsFormatter;
    
    // Configurar botónes de copia de código
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const codeBlock = e.target.closest('.code-block');
            const code = codeBlock.querySelector('pre').textContent;
            
            const success = await DevToolsUtils.fileUtils.copyToClipboard(code);
            if (success) {
                e.target.textContent = '✓ Copiado';
                setTimeout(() => {
                    e.target.textContent = 'Copiar';
                }, 2000);
            }
        });
    });
    
    // Inicializar resaltado de código simple
    const highlighter = new SimpleCodeHighlighter();
    document.querySelectorAll('.code-highlight').forEach(block => {
        const language = block.dataset.language || 'javascript';
        const code = block.textContent;
        block.innerHTML = highlighter.highlight(code, language);
    });
});

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DevToolsUtils, SimpleCodeHighlighter, TestResultsFormatter };
}
