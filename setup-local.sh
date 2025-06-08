#!/bin/bash

# =============================================================================
# Script de Inicialización Local para Dev-Tools
# 
# Este script configura archivos específicos del plugin que NO deben ser
# compartidos en el submódulo git para evitar contaminación entre plugins.
# =============================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Funciones de utilidad
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

# Detectar información del plugin host
PLUGIN_DIR=$(dirname "$(pwd)")
PLUGIN_NAME=""
PLUGIN_SLUG=$(basename "$PLUGIN_DIR")

# Buscar archivo principal del plugin
for file in "$PLUGIN_DIR"/*.php; do
    if [ -f "$file" ] && grep -q "Plugin Name:" "$file" 2>/dev/null; then
        PLUGIN_NAME=$(grep "Plugin Name:" "$file" | sed 's/.*Plugin Name:\s*//' | sed 's/\s*$//')
        break
    fi
done

if [ -z "$PLUGIN_NAME" ]; then
    error "No se pudo detectar el plugin host"
fi

section "🔧 Inicializando Configuración Local para Dev-Tools"
info "Plugin detectado: $PLUGIN_NAME"
info "Plugin slug: $PLUGIN_SLUG"

# =============================================================================
# CREAR CONFIGURACIÓN LOCAL DE TESTING
# =============================================================================

section "📋 Creando configuración local de testing"

# Crear wp-tests-config-local.php específico del plugin
if [ ! -f "wp-tests-config-local.php" ]; then
    info "Creando wp-tests-config-local.php específico para $PLUGIN_NAME..."
    
    cat > wp-tests-config-local.php << 'EOL'
<?php
/**
 * Configuración LOCAL de Testing para Plugin Específico
 * 
 * Este archivo contiene configuraciones específicas del plugin
 * y NO debe ser incluido en el submódulo git compartido.
 * 
 * @package DevTools
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// CONFIGURACIÓN DE TESTING ESPECÍFICA DEL PLUGIN
// =============================================================================

// Cargar configuración local del plugin
$local_config = include __DIR__ . '/config-local.php';

// Configuración de base de datos para testing (Local by Flywheel)
define('DB_NAME', $local_config['database']['name']);
define('DB_USER', $local_config['database']['user']);
define('DB_PASSWORD', $local_config['database']['password']);
define('DB_HOST', $local_config['database']['host']);
define('DB_CHARSET', $local_config['database']['charset']);
define('DB_COLLATE', $local_config['database']['collate']);

// Prefijo de tablas específico del plugin
$table_prefix = $local_config['database']['table_prefix'];

// URLs de testing
define('WP_HOME', $local_config['urls']['home']);
define('WP_SITEURL', $local_config['urls']['site']);

// Configuración de testing
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Configuración específica del plugin para testing
define('DEV_TOOLS_PLUGIN_FILE', $local_config['plugin']['main_file']);
define('DEV_TOOLS_TESTING', true);

// Configuraciones específicas del plugin
$plugin_config = $local_config['plugin_specific'];

// Definir constantes específicas del plugin si existen
foreach ($plugin_config['required_constants'] as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}

// Función para cargar plugin durante tests
function dev_tools_load_plugin_local() {
    $config = include __DIR__ . '/config-local.php';
    $plugin_file = $config['plugin']['main_file'];
    
    if (file_exists($plugin_file)) {
        include_once $plugin_file;
        
        // Verificar funciones requeridas
        foreach ($config['plugin_specific']['required_functions'] as $function) {
            if (function_exists($function)) {
                error_log("✅ Función requerida encontrada: $function");
            } else {
                error_log("⚠️  Función requerida no encontrada: $function");
            }
        }
        
        // Verificar Custom Post Types
        foreach ($config['plugin_specific']['custom_post_types'] as $post_type) {
            if (post_type_exists($post_type)) {
                error_log("✅ Custom Post Type registrado: $post_type");
            } else {
                error_log("⚠️  Custom Post Type no registrado: $post_type");
            }
        }
        
        // Verificar taxonomías
        foreach ($config['plugin_specific']['custom_taxonomies'] as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                error_log("✅ Taxonomía registrada: $taxonomy");
            } else {
                error_log("⚠️  Taxonomía no registrada: $taxonomy");
            }
        }
    }
}

// Registrar función de carga del plugin
$GLOBALS['dev_tools_plugin_loader'] = 'dev_tools_load_plugin_local';

// Keys y salts para testing
define('AUTH_KEY', 'test-auth-key-' . $local_config['plugin']['slug']);
define('SECURE_AUTH_KEY', 'test-secure-auth-key-' . $local_config['plugin']['slug']);
define('LOGGED_IN_KEY', 'test-logged-in-key-' . $local_config['plugin']['slug']);
define('NONCE_KEY', 'test-nonce-key-' . $local_config['plugin']['slug']);
define('AUTH_SALT', 'test-auth-salt-' . $local_config['plugin']['slug']);
define('SECURE_AUTH_SALT', 'test-secure-auth-salt-' . $local_config['plugin']['slug']);
define('LOGGED_IN_SALT', 'test-logged-in-salt-' . $local_config['plugin']['slug']);
define('NONCE_SALT', 'test-nonce-salt-' . $local_config['plugin']['slug']);
EOL

    success "wp-tests-config-local.php creado"
else
    warning "wp-tests-config-local.php ya existe"
fi

# Crear config-local.php del template
if [ ! -f "config-local.php" ]; then
    if [ -f "config-local-template.php" ]; then
        info "Creando config-local.php desde template..."
        cp config-local-template.php config-local.php
        success "config-local.php creado desde template"
    else
        error "Template config-local-template.php no encontrado"
    fi
else
    warning "config-local.php ya existe"
fi

# =============================================================================
# CREAR DIRECTORIOS LOCALES
# =============================================================================

section "📁 Creando directorios específicos del plugin"

# Crear directorios que NO deben estar en el submódulo compartido
LOCAL_DIRS=(
    "tests/plugin-specific"
    "reports/plugin-specific"
    "logs/plugin-specific" 
    "coverage/plugin-specific"
    "fixtures/plugin-data"
    "mocks/plugin-specific"
)

for dir in "${LOCAL_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        success "Directorio creado: $dir"
        
        # Crear .gitkeep pero no .gitignore (para que funcionen las exclusiones)
        touch "$dir/.gitkeep"
    else
        info "Directorio ya existe: $dir"
    fi
done

# =============================================================================
# CREAR CONFIGURACIÓN PHPUNIT LOCAL
# =============================================================================

section "🧪 Creando configuración PHPUnit local"

if [ ! -f "phpunit-local.xml" ]; then
    info "Creando phpunit-local.xml específico para $PLUGIN_NAME..."
    
    cat > phpunit-local.xml << EOL
<?xml version="1.0" encoding="UTF-8"?>
<phpunit 
    bootstrap="tests/bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    stopOnFailure="false"
    beStrictAboutTestsThatDoNotTestAnything="true"
    beStrictAboutOutputDuringTests="false">
    
    <php>
        <const name="WP_TESTS_CONFIG_FILE_PATH" value="wp-tests-config-local.php"/>
        <const name="WP_TESTS_PHPUNIT" value="1"/>
        <const name="DEV_TOOLS_TESTING_LOCAL" value="1"/>
        <env name="WP_TESTS_SKIP_INSTALL" value="1"/>
    </php>
    
    <testsuites>
        <testsuite name="${PLUGIN_NAME} - Plugin Specific Tests">
            <directory>tests/plugin-specific</directory>
        </testsuite>
        <testsuite name="${PLUGIN_NAME} - Unit Tests">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="${PLUGIN_NAME} - Integration Tests">
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">../</directory>
            <exclude>
                <directory>vendor</directory>
                <directory>node_modules</directory>
                <directory>wordpress-develop</directory>
                <directory>tests</directory>
                <directory>docs</directory>
            </exclude>
        </whitelist>
    </filter>
    
    <logging>
        <log type="coverage-html" target="reports/plugin-specific/coverage"/>
        <log type="coverage-clover" target="reports/plugin-specific/clover.xml"/>
        <log type="junit" target="reports/plugin-specific/junit.xml"/>
    </logging>
</phpunit>
EOL

    success "phpunit-local.xml creado"
else
    warning "phpunit-local.xml ya existe"
fi

# =============================================================================
# CREAR SCRIPT DE TESTING LOCAL
# =============================================================================

section "🚀 Creando script de testing local"

if [ ! -f "run-tests-local.sh" ]; then
    info "Creando run-tests-local.sh..."
    
    cat > run-tests-local.sh << 'EOL'
#!/bin/bash

# Script de testing LOCAL específico del plugin
# Usa configuraciones locales que NO se comparten entre plugins

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info() { echo -e "${YELLOW}ℹ️  $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }

info "Ejecutando tests locales específicos del plugin..."

# Verificar archivos de configuración local
if [ ! -f "wp-tests-config-local.php" ]; then
    error "Configuración local de testing no encontrada. Ejecuta: ./setup-local.sh"
    exit 1
fi

if [ ! -f "config-local.php" ]; then
    error "Configuración local no encontrada. Ejecuta: ./setup-local.sh"
    exit 1
fi

# Ejecutar tests usando configuración local
info "Usando configuración local específica del plugin..."

if [ -f "phpunit-local.xml" ]; then
    vendor/bin/phpunit --configuration=phpunit-local.xml "$@"
else
    # Fallback usando configuración por defecto pero con archivos locales
    vendor/bin/phpunit \
        --bootstrap tests/bootstrap.php \
        --configuration phpunit.xml \
        "$@"
fi

if [ $? -eq 0 ]; then
    success "Tests completados exitosamente"
    info "Reports guardados en: reports/plugin-specific/"
else
    error "Algunos tests fallaron"
    exit 1
fi
EOL

    chmod +x run-tests-local.sh
    success "run-tests-local.sh creado y marcado como ejecutable"
else
    warning "run-tests-local.sh ya existe"
fi

# =============================================================================
# CREAR DOCUMENTACIÓN
# =============================================================================

section "📚 Creando documentación local"

if [ ! -f "LOCAL-SETUP.md" ]; then
    info "Creando documentación LOCAL-SETUP.md..."
    
    cat > LOCAL-SETUP.md << EOL
# Configuración Local Dev-Tools - $PLUGIN_NAME

## 🎯 Propósito

Este directorio contiene configuraciones **específicas del plugin** que:
- ❌ **NO se comparten** entre diferentes plugins que usan dev-tools
- ❌ **NO se incluyen** en el submódulo git compartido  
- ✅ **Son específicas** para $PLUGIN_NAME únicamente

## 📁 Archivos Locales Creados

### Configuración
- \`config-local.php\` - Configuración específica del plugin
- \`wp-tests-config-local.php\` - Configuración de testing local
- \`phpunit-local.xml\` - Configuración PHPUnit específica

### Scripts
- \`setup-local.sh\` - Script de inicialización (este archivo)
- \`run-tests-local.sh\` - Ejecutar tests con configuración local

### Directorios
- \`tests/plugin-specific/\` - Tests específicos de $PLUGIN_NAME
- \`reports/plugin-specific/\` - Reports de testing locales
- \`logs/plugin-specific/\` - Logs específicos del plugin
- \`fixtures/plugin-data/\` - Datos de prueba específicos

## 🚀 Uso

### Ejecutar Tests Locales
\`\`\`bash
# Tests usando configuración local (recomendado)
./run-tests-local.sh

# Tests específicos del plugin únicamente
./run-tests-local.sh tests/plugin-specific/

# Tests con cobertura
./run-tests-local.sh --coverage-html reports/plugin-specific/coverage
\`\`\`

### Añadir Tests Específicos del Plugin
1. Crear archivos en \`tests/plugin-specific/\`
2. Usar \`phpunit-local.xml\` como configuración
3. Los reports se guardan en \`reports/plugin-specific/\`

## ⚠️ Importante

- Estos archivos están en \`.gitignore\` del submódulo
- Cada plugin tendrá su propia configuración local
- NO editar archivos del core compartido para configuraciones específicas

## 🔧 Personalización

Edita \`config-local.php\` para:
- Configurar Custom Post Types específicos
- Definir taxonomías del plugin
- Establecer funciones requeridas
- Configurar constantes específicas
EOL

    success "LOCAL-SETUP.md creado"
else
    warning "LOCAL-SETUP.md ya existe"
fi

# =============================================================================
# RESUMEN FINAL
# =============================================================================

section "✅ Configuración Local Completada"

echo -e "Se han creado los siguientes archivos ${CYAN}específicos para $PLUGIN_NAME${NC}:"
echo -e "  📋 config-local.php"
echo -e "  🧪 wp-tests-config-local.php" 
echo -e "  ⚙️  phpunit-local.xml"
echo -e "  🚀 run-tests-local.sh"
echo -e "  📚 LOCAL-SETUP.md"
echo ""
echo -e "Directorios creados:"
for dir in "${LOCAL_DIRS[@]}"; do
    echo -e "  📁 $dir"
done
echo ""
success "Configuración local inicializada para $PLUGIN_NAME"
warning "Estos archivos NO se comparten entre plugins (están en .gitignore del submódulo)"
info "Para ejecutar tests locales: ./run-tests-local.sh"
echo ""
