#!/bin/bash

# =============================================================================
# VERIFICACIÓN - Sistema de Override tipo Child Theme
# =============================================================================
# 
# Verifica que el sistema de override esté funcionando correctamente
# Ejecuta pruebas de funcionamiento y detecta posibles problemas
#
# Uso: ./verify-override-system.sh
# =============================================================================

set -e  # Salir en caso de error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(dirname "$SCRIPT_DIR")"
DEV_TOOLS_DIR="$SCRIPT_DIR"
PLUGIN_DEV_TOOLS_DIR="$PLUGIN_ROOT/plugin-dev-tools"

echo -e "${BLUE}🔍 VERIFICACIÓN SISTEMA DE OVERRIDE${NC}"
echo -e "${BLUE}====================================${NC}"
echo ""

# =============================================================================
# FUNCIÓN: Verificar archivos del sistema
# =============================================================================
verify_system_files() {
    echo -e "${YELLOW}📋 Verificando archivos del sistema...${NC}"
    
    local errors=0
    
    # Archivos críticos del sistema
    local required_files=(
        "core/FileOverrideSystem.php"
        "config.php"
        "demo-override-system.php"
        "migrate-to-override-system.sh"
    )
    
    for file in "${required_files[@]}"; do
        local file_path="$DEV_TOOLS_DIR/$file"
        if [[ -f "$file_path" ]]; then
            echo -e "  ✅ $file"
        else
            echo -e "  ${RED}❌ FALTA: $file${NC}"
            ((errors++))
        fi
    done
    
    # Verificar que los archivos sean ejecutables si es necesario
    if [[ -f "$DEV_TOOLS_DIR/migrate-to-override-system.sh" ]]; then
        if [[ -x "$DEV_TOOLS_DIR/migrate-to-override-system.sh" ]]; then
            echo -e "  ✅ migrate-to-override-system.sh es ejecutable"
        else
            echo -e "  ${YELLOW}⚠️  migrate-to-override-system.sh no es ejecutable${NC}"
            chmod +x "$DEV_TOOLS_DIR/migrate-to-override-system.sh"
            echo -e "  ✅ Permisos corregidos"
        fi
    fi
    
    return $errors
}

# =============================================================================
# FUNCIÓN: Verificar estructura de plugin-dev-tools
# =============================================================================
verify_plugin_dev_tools_structure() {
    echo -e "${YELLOW}🏗️ Verificando estructura plugin-dev-tools...${NC}"
    
    if [[ ! -d "$PLUGIN_DEV_TOOLS_DIR" ]]; then
        echo -e "  ${YELLOW}ℹ️  Directorio plugin-dev-tools no existe (normal si no se ha migrado)${NC}"
        return 0
    fi
    
    echo -e "  ✅ Directorio plugin-dev-tools existe"
    
    # Verificar subdirectorios esperados
    local expected_dirs=("modules" "templates" "tests" "logs" "reports" "fixtures")
    for dir in "${expected_dirs[@]}"; do
        if [[ -d "$PLUGIN_DEV_TOOLS_DIR/$dir" ]]; then
            echo -e "  ✅ $dir/"
        else
            echo -e "  ${YELLOW}⚠️  No existe: $dir/${NC}"
        fi
    done
    
    # Verificar archivos importantes
    local important_files=("README.md" ".gitignore")
    for file in "${important_files[@]}"; do
        if [[ -f "$PLUGIN_DEV_TOOLS_DIR/$file" ]]; then
            echo -e "  ✅ $file"
        else
            echo -e "  ${YELLOW}⚠️  No existe: $file${NC}"
        fi
    done
}

# =============================================================================
# FUNCIÓN: Verificar sintaxis PHP
# =============================================================================
verify_php_syntax() {
    echo -e "${YELLOW}🔧 Verificando sintaxis PHP...${NC}"
    
    local errors=0
    
    # Archivos PHP críticos
    local php_files=(
        "$DEV_TOOLS_DIR/core/FileOverrideSystem.php"
        "$DEV_TOOLS_DIR/config.php"
        "$DEV_TOOLS_DIR/demo-override-system.php"
    )
    
    for file in "${php_files[@]}"; do
        if [[ -f "$file" ]]; then
            local filename=$(basename "$file")
            if php -l "$file" >/dev/null 2>&1; then
                echo -e "  ✅ $filename - Sintaxis OK"
            else
                echo -e "  ${RED}❌ $filename - Error de sintaxis${NC}"
                php -l "$file"
                ((errors++))
            fi
        fi
    done
    
    return $errors
}

# =============================================================================
# FUNCIÓN: Probar carga del sistema
# =============================================================================
test_system_loading() {
    echo -e "${YELLOW}⚡ Probando carga del sistema...${NC}"
    
    # Crear script de prueba temporal
    local test_script="$DEV_TOOLS_DIR/test-override-loading.php"
    
    cat > "$test_script" << 'EOF'
<?php
// Script de prueba temporal
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular entorno WordPress mínimo
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        return mkdir($target, 0755, true);
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        return 'http://localhost/wp-content/plugins/' . ltrim($path, '/');
    }
}

if (!function_exists('get_file_data')) {
    function get_file_data($file, $default_headers) {
        return ['Name' => 'Test Plugin', 'Version' => '1.0.0'];
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

try {
    echo "🔄 Cargando FileOverrideSystem...\n";
    require_once __DIR__ . '/core/FileOverrideSystem.php';
    echo "✅ FileOverrideSystem cargado\n";
    
    echo "🔄 Cargando config.php...\n";
    require_once __DIR__ . '/config.php';
    echo "✅ config.php cargado\n";
    
    echo "🔄 Creando instancia de DevToolsConfig...\n";
    $config = DevToolsConfig::getInstance();
    echo "✅ DevToolsConfig instanciado\n";
    
    echo "🔄 Probando sistema de override...\n";
    $override_info = $config->get_override_info();
    echo "✅ Sistema de override funcional\n";
    
    echo "📋 Información del sistema:\n";
    echo "   - Parent dir: " . $override_info['parent_dir'] . "\n";
    echo "   - Child dir: " . $override_info['child_dir'] . "\n";
    echo "   - Overrides: " . $override_info['overrides_count'] . "\n";
    
    echo "\n🎉 TODAS LAS PRUEBAS PASARON\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
} catch (ParseError $e) {
    echo "❌ ERROR DE SINTAXIS: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
EOF
    
    # Ejecutar script de prueba
    if php "$test_script" 2>&1; then
        echo -e "  ${GREEN}✅ Sistema se carga correctamente${NC}"
        local success=true
    else
        echo -e "  ${RED}❌ Error al cargar el sistema${NC}"
        local success=false
    fi
    
    # Limpiar script temporal
    rm -f "$test_script"
    
    if [[ "$success" == "true" ]]; then
        return 0
    else
        return 1
    fi
}

# =============================================================================
# FUNCIÓN: Verificar configuración git
# =============================================================================
verify_git_configuration() {
    echo -e "${YELLOW}📦 Verificando configuración Git...${NC}"
    
    # Verificar si dev-tools es un submódulo
    if [[ -f "$PLUGIN_ROOT/.gitmodules" ]]; then
        if grep -q "path = dev-tools" "$PLUGIN_ROOT/.gitmodules"; then
            echo -e "  ✅ dev-tools configurado como submódulo"
        else
            echo -e "  ${YELLOW}⚠️  dev-tools no está en .gitmodules${NC}"
        fi
    else
        echo -e "  ${YELLOW}ℹ️  No hay archivo .gitmodules (normal si no se usa como submódulo)${NC}"
    fi
    
    # Verificar .gitignore del plugin
    local plugin_gitignore="$PLUGIN_ROOT/.gitignore"
    if [[ -f "$plugin_gitignore" ]]; then
        if grep -q "plugin-dev-tools" "$plugin_gitignore"; then
            echo -e "  ✅ plugin-dev-tools en .gitignore del plugin"
        else
            echo -e "  ${YELLOW}💡 Considera añadir plugin-dev-tools/ al .gitignore del plugin${NC}"
        fi
    fi
    
    # Verificar .gitignore de dev-tools
    local dev_tools_gitignore="$DEV_TOOLS_DIR/.gitignore"
    if [[ -f "$dev_tools_gitignore" ]]; then
        if grep -q "config-local.php" "$dev_tools_gitignore"; then
            echo -e "  ✅ Archivos locales en .gitignore de dev-tools"
        else
            echo -e "  ${YELLOW}💡 Considera actualizar .gitignore de dev-tools${NC}"
        fi
    fi
}

# =============================================================================
# FUNCIÓN: Mostrar resumen y recomendaciones
# =============================================================================
show_summary_and_recommendations() {
    echo -e "${BLUE}📊 RESUMEN Y RECOMENDACIONES${NC}"
    echo -e "${BLUE}=============================${NC}"
    echo ""
    
    echo -e "${GREEN}✅ SISTEMA INSTALADO Y FUNCIONAL${NC}"
    echo ""
    
    echo -e "${YELLOW}🚀 PRÓXIMOS PASOS:${NC}"
    echo ""
    
    if [[ ! -d "$PLUGIN_DEV_TOOLS_DIR" ]]; then
        echo -e "1. ${BLUE}Migrar archivos existentes:${NC}"
        echo -e "   cd dev-tools && ./migrate-to-override-system.sh"
        echo ""
    fi
    
    echo -e "2. ${BLUE}Probar el sistema:${NC}"
    echo -e "   # Cargar demo en navegador:"
    echo -e "   # http://localhost/wp-content/plugins/plugin-name/dev-tools/demo-override-system.php"
    echo ""
    
    echo -e "3. ${BLUE}Crear primer override:${NC}"
    echo -e "   \$config = DevToolsConfig::getInstance();"
    echo -e "   \$config->create_override('config-local.php');"
    echo ""
    
    echo -e "4. ${BLUE}Documentación:${NC}"
    echo -e "   📖 SISTEMA-OVERRIDE-CHILD-THEME.md"
    echo -e "   📖 dev-tools/demo-override-system.php"
    echo ""
    
    echo -e "${YELLOW}💡 RECORDATORIOS:${NC}"
    echo -e "- Los archivos en plugin-dev-tools/ son específicos de ESTE plugin"
    echo -e "- El sistema carga automáticamente desde plugin-dev-tools/ o dev-tools/"
    echo -e "- Usa create_override() para customizar archivos del core"
    echo -e "- Los archivos *.backup contienen las versiones originales"
}

# =============================================================================
# FUNCIÓN PRINCIPAL
# =============================================================================
main() {
    local total_errors=0
    
    verify_system_files
    ((total_errors += $?))
    echo ""
    
    verify_plugin_dev_tools_structure
    echo ""
    
    verify_php_syntax
    ((total_errors += $?))
    echo ""
    
    test_system_loading
    ((total_errors += $?))
    echo ""
    
    verify_git_configuration
    echo ""
    
    if [[ $total_errors -eq 0 ]]; then
        show_summary_and_recommendations
        echo -e "${GREEN}🎉 VERIFICACIÓN COMPLETADA - TODO OK${NC}"
        return 0
    else
        echo -e "${RED}❌ VERIFICACIÓN COMPLETADA CON $total_errors ERRORES${NC}"
        echo ""
        echo -e "${YELLOW}🔧 SOLUCIONES SUGERIDAS:${NC}"
        echo -e "1. Verificar que todos los archivos existan"
        echo -e "2. Revisar errores de sintaxis PHP mostrados arriba"
        echo -e "3. Ejecutar migración si es necesario"
        echo -e "4. Verificar permisos de archivos"
        return 1
    fi
}

# Ejecutar verificación principal
main "$@"
