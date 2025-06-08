#!/bin/bash

# =============================================================================
# Script de Migración para Separación de Configuraciones
# 
# Migra configuraciones específicas del plugin existentes a archivos locales
# para evitar contaminación entre plugins que usan dev-tools como submódulo.
# =============================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

section() {
    echo -e "\n${PURPLE}$1${NC}"
    echo -e "${PURPLE}$(echo "$1" | sed 's/./=/g')${NC}\n"
}

# Verificar que estamos en el directorio dev-tools
if [ ! -f "config.php" ] || [ ! -f "loader.php" ]; then
    error "Este script debe ejecutarse desde el directorio dev-tools"
fi

section "🔄 Migración de Configuraciones a Sistema Local"

# Detectar información del plugin
PLUGIN_DIR=$(dirname "$(pwd)")
PLUGIN_NAME=""
PLUGIN_SLUG=$(basename "$PLUGIN_DIR")

for file in "$PLUGIN_DIR"/*.php; do
    if [ -f "$file" ] && grep -q "Plugin Name:" "$file" 2>/dev/null; then
        PLUGIN_NAME=$(grep "Plugin Name:" "$file" | sed 's/.*Plugin Name:\s*//' | sed 's/\s*$//')
        break
    fi
done

if [ -z "$PLUGIN_NAME" ]; then
    error "No se pudo detectar el plugin host"
fi

info "Migrando configuraciones para: $PLUGIN_NAME"
info "Plugin slug: $PLUGIN_SLUG"

# =============================================================================
# MIGRAR CONFIGURACIÓN DE TESTING EXISTENTE
# =============================================================================

if [ -f "wp-tests-config.php" ]; then
    section "📋 Migrando configuración de testing"
    
    # Backup del archivo original
    if [ ! -f "wp-tests-config.php.backup" ]; then
        cp wp-tests-config.php wp-tests-config.php.backup
        success "Backup creado: wp-tests-config.php.backup"
    fi
    
    # Crear configuración específica de Tarokina desde el archivo actual
    if [ ! -f "wp-tests-config-tarokina.php" ]; then
        info "Creando configuración específica para Tarokina..."
        
        # Extraer configuraciones específicas de Tarokina del archivo actual
        cat > wp-tests-config-tarokina.php << 'EOL'
<?php
/**
 * Configuración de Testing Específica para Tarokina Pro
 * 
 * Este archivo contiene configuraciones que eran específicas de Tarokina
 * en wp-tests-config.php y ahora se mantienen localmente.
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// CONFIGURACIÓN ESPECÍFICA DE TAROKINA PRO
// =============================================================================

// Base de datos Local by Flywheel para Tarokina
define('DB_NAME', 'local');
define('DB_USER', 'root'); 
define('DB_PASSWORD', 'root');
define('DB_HOST', '127.0.0.1');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Prefijo específico para tests de Tarokina
$table_prefix = 'wp_test_tarokina_';

// URLs específicas de Tarokina (Local by Flywheel)
define('WP_HOME', 'https://tarokina-2025.local');
define('WP_SITEURL', 'https://tarokina-2025.local');

// Plugin de Tarokina para testing
$plugin_dir = dirname(__DIR__);
$tarokina_plugin_file = $plugin_dir . '/tarokina-pro.php';

if (file_exists($tarokina_plugin_file)) {
    define('DEV_TOOLS_PLUGIN_FILE', $tarokina_plugin_file);
    define('DEV_TOOLS_TESTING', true);
    define('TAROKINA_TESTING_MODE', true);
}

// Configuración de debug específica de Tarokina
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Keys específicas para Tarokina testing
define('AUTH_KEY', 'test-auth-key-tarokina');
define('SECURE_AUTH_KEY', 'test-secure-auth-key-tarokina');
define('LOGGED_IN_KEY', 'test-logged-in-key-tarokina');
define('NONCE_KEY', 'test-nonce-key-tarokina');
define('AUTH_SALT', 'test-auth-salt-tarokina');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt-tarokina');
define('LOGGED_IN_SALT', 'test-logged-in-salt-tarokina');
define('NONCE_SALT', 'test-nonce-salt-tarokina');

// Función para cargar Tarokina Pro durante tests
function dev_tools_load_tarokina_plugin() {
    $plugin_file = DEV_TOOLS_PLUGIN_FILE;
    
    if (file_exists($plugin_file)) {
        include_once $plugin_file;
        
        // Verificar constantes específicas de Tarokina
        if (defined('TKINA_TAROKINA_PRO_DIR_PATH')) {
            error_log('✅ TAROKINA TESTS: Plugin Tarokina Pro cargado exitosamente');
            error_log('📁 Plugin Path: ' . TKINA_TAROKINA_PRO_DIR_PATH);
            error_log('🌐 Plugin URL: ' . (defined('TKINA_TAROKINA_PRO_DIR_URL') ? TKINA_TAROKINA_PRO_DIR_URL : 'N/A'));
        }
    }
}

// Función para verificar elementos específicos de Tarokina
function dev_tools_verify_tarokina_plugin() {
    // Verificar Custom Post Types de Tarokina
    if (post_type_exists('tkina_tarots') && post_type_exists('tarokkina_pro')) {
        error_log('✅ TAROKINA TESTS: Custom Post Types registrados correctamente');
    } else {
        error_log('⚠️  TAROKINA TESTS: Custom Post Types no registrados aún');
    }
    
    // Verificar taxonomías de Tarokina
    if (taxonomy_exists('tarokkina_pro-cat') && taxonomy_exists('tarokkina_pro-tag')) {
        error_log('✅ TAROKINA TESTS: Taxonomías registradas correctamente');
    } else {
        error_log('⚠️  TAROKINA TESTS: Taxonomías no registradas aún');
    }
    
    // Verificar funciones principales de Tarokina
    if (function_exists('is_name_pro')) {
        error_log('✅ TAROKINA TESTS: Funciones principales del plugin disponibles');
    } else {
        error_log('⚠️  TAROKINA TESTS: Funciones principales no disponibles');
    }
}

// Registrar funciones de carga
$GLOBALS['dev_tools_plugin_loader'] = 'dev_tools_load_tarokina_plugin';
$GLOBALS['dev_tools_plugin_verifier'] = 'dev_tools_verify_tarokina_plugin';

// Debug condicional (solo cuando hay fallos)
function dev_tools_show_debug_on_failure() {
    static $debug_shown = false;
    if ($debug_shown) return;
    $debug_shown = true;
    
    error_log('=== DEBUG TAROKINA TESTS ===');
    error_log('Base de datos: ' . DB_NAME . '@' . DB_HOST);
    error_log('Prefijo tablas: ' . $table_prefix);
    error_log('Sitio URL: ' . WP_HOME);
    error_log('Plugin: ' . (defined('DEV_TOOLS_PLUGIN_FILE') ? DEV_TOOLS_PLUGIN_FILE : 'No detectado'));
    error_log('============================');
}

$GLOBALS['dev_tools_debug_function'] = 'dev_tools_show_debug_on_failure';
EOL

        success "wp-tests-config-tarokina.php creado"
    else
        info "wp-tests-config-tarokina.php ya existe"
    fi
    
    warning "ACCIÓN REQUERIDA: Decide cómo manejar wp-tests-config.php:"
    echo "   1. Mantenerlo como está (funcionará pero tendrá datos específicos de Tarokina)"
    echo "   2. Crear versión genérica para futuros plugins"
    echo "   3. Renombrarlo a wp-tests-config-generic.php"
    echo ""
    
else
    info "No se encontró wp-tests-config.php para migrar"
fi

# =============================================================================
# MIGRAR TESTS ESPECÍFICOS
# =============================================================================

section "🧪 Identificando tests específicos del plugin"

if [ -d "tests" ]; then
    # Buscar tests que contengan referencias específicas a Tarokina
    TAROKINA_TESTS=$(find tests -name "*.php" -exec grep -l -i "tarokina\|tkina" {} \; 2>/dev/null)
    
    if [ -n "$TAROKINA_TESTS" ]; then
        info "Tests específicos de Tarokina encontrados:"
        echo "$TAROKINA_TESTS" | while read test_file; do
            echo "  📝 $test_file"
        done
        
        warning "ACCIÓN RECOMENDADA:"
        echo "   Mover estos tests a tests/plugin-specific/ en futuras ejecuciones"
        echo "   O crear nuevos tests en el directorio plugin-specific"
    else
        success "No se encontraron tests específicos de Tarokina (los tests son genéricos)"
    fi
else
    info "Directorio tests no encontrado"
fi

# =============================================================================
# MIGRAR CONFIGURACIONES CI/CD
# =============================================================================

section "🔄 Revisando configuraciones CI/CD"

CI_FILES=(
    "../.github/workflows/test.yml"
    "../.github/workflows/ci.yml"
    "../.gitlab-ci.yml"
    "phpunit.xml"
)

PLUGIN_SPECIFIC_CI=()

for ci_file in "${CI_FILES[@]}"; do
    if [ -f "$ci_file" ]; then
        # Buscar referencias específicas al plugin
        if grep -q -i "tarokina\|tkina" "$ci_file" 2>/dev/null; then
            PLUGIN_SPECIFIC_CI+=("$ci_file")
        fi
    fi
done

if [ ${#PLUGIN_SPECIFIC_CI[@]} -gt 0 ]; then
    warning "Configuraciones CI/CD con referencias específicas a Tarokina:"
    for ci_file in "${PLUGIN_SPECIFIC_CI[@]}"; do
        echo "  ⚙️  $ci_file"
    done
    echo ""
    warning "ACCIÓN RECOMENDADA:"
    echo "   Crear versiones locales de estos archivos si necesitas configuraciones específicas"
    echo "   O generalizar las configuraciones para que sean plugin-agnósticas"
else
    success "Las configuraciones CI/CD son genéricas (correcto para submódulo compartido)"
fi

# =============================================================================
# CREAR CONFIGURACIÓN LOCAL
# =============================================================================

section "📋 Configurando sistema local"

# Ejecutar setup local si existe
if [ -f "setup-local.sh" ]; then
    info "Ejecutando configuración local..."
    chmod +x setup-local.sh
    ./setup-local.sh
    
    if [ $? -eq 0 ]; then
        success "Sistema local configurado correctamente"
    else
        warning "Hubo problemas con la configuración local"
    fi
else
    warning "Script setup-local.sh no encontrado"
    info "Ejecuta primero el setup principal para obtener la última versión"
fi

# =============================================================================
# RESUMEN Y RECOMENDACIONES
# =============================================================================

section "📊 Resumen de Migración"

echo -e "${CYAN}ARCHIVOS MIGRADOS:${NC}"
if [ -f "wp-tests-config-tarokina.php" ]; then
    echo "  ✅ wp-tests-config-tarokina.php (configuración específica de Tarokina)"
fi
if [ -f "wp-tests-config.php.backup" ]; then
    echo "  ✅ wp-tests-config.php.backup (backup del original)"
fi

echo ""
echo -e "${CYAN}PRÓXIMOS PASOS RECOMENDADOS:${NC}"
echo "  1. 🔄 Ejecutar tests con configuración local: ./run-tests-local.sh"
echo "  2. 📝 Mover tests específicos de Tarokina a tests/plugin-specific/"
echo "  3. 🧹 Limpiar wp-tests-config.php para hacerlo genérico (opcional)"
echo "  4. 📋 Documentar configuraciones específicas en LOCAL-SETUP.md"

echo ""
echo -e "${CYAN}BENEFICIOS OBTENIDOS:${NC}"
echo "  ✅ Configuraciones específicas separadas del core compartido"
echo "  ✅ Evita contaminación entre diferentes plugins"
echo "  ✅ Mantiene funcionalidad completa del sistema"
echo "  ✅ Permite personalización por plugin sin afectar el submódulo"

echo ""
success "🎉 Migración completada exitosamente"
warning "Recuerda: Los archivos *-local.php no se incluyen en el submódulo git compartido"
