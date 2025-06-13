#!/bin/bash

# ==========================================
# Script de Verificación Pre-Deploy
# Tarokina Pro Plugin - Dev-Tools v3.0
# ==========================================
#
# PROPÓSITO: Verificar que archivos de desarrollo no se incluyan en producción
# USO: ./scripts/verify-production-ready.sh
# AUTOR: Dev-Tools Override System
# VERSIÓN: 1.0

echo "🔍 VERIFICACIÓN PRE-DEPLOY - Plugin Tarokina Pro"
echo "================================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
PLUGIN_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ERRORS=0
WARNINGS=0

echo "📍 Directorio del plugin: $PLUGIN_ROOT"
echo ""

# Función para reportar errores
report_error() {
    echo -e "${RED}❌ ERROR: $1${NC}"
    ((ERRORS++))
}

# Función para reportar advertencias
report_warning() {
    echo -e "${YELLOW}⚠️  WARNING: $1${NC}"
    ((WARNINGS++))
}

# Función para reportar éxito
report_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Función para reportar info
report_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

echo "🧪 VERIFICANDO ARCHIVOS DE DESARROLLO..."
echo "----------------------------------------"

# 1. Verificar que plugin-dev-tools NO esté en git
if git ls-files | grep -q "plugin-dev-tools"; then
    report_error "Directorio plugin-dev-tools está siendo tracked por git"
    echo "   Solución: Asegurar que plugin-dev-tools/ esté en .gitignore"
else
    report_success "plugin-dev-tools NO está tracked por git"
fi

# 2. Verificar que plugin-dev-tools existe localmente (para desarrollo)
if [ -d "$PLUGIN_ROOT/plugin-dev-tools" ]; then
    report_success "plugin-dev-tools existe localmente (OK para desarrollo)"
    
    # Mostrar tamaño del directorio
    SIZE=$(du -sh "$PLUGIN_ROOT/plugin-dev-tools" 2>/dev/null | cut -f1)
    report_info "Tamaño de plugin-dev-tools: $SIZE"
else
    report_warning "plugin-dev-tools NO existe localmente"
    echo "   Esto es normal si no se está desarrollando tests"
fi

# 3. Verificar archivos de desarrollo en git
echo ""
echo "📁 VERIFICANDO ARCHIVOS DE DESARROLLO EN GIT..."
echo "------------------------------------------------"

DEV_FILES=(
    "dev-tools/vendor"
    "dev-tools/node_modules"
    "dev-tools/composer.lock"
    "dev-tools/coverage"
    "node_modules"
    "package-lock.json"
    "composer.lock"
    "coverage"
    ".phpunit.result.cache"
    "*.log"
)

for file in "${DEV_FILES[@]}"; do
    if git ls-files | grep -q "$file"; then
        report_error "Archivo de desarrollo en git: $file"
    fi
done

# 4. Verificar .gitignore
echo ""
echo "📋 VERIFICANDO .GITIGNORE..."
echo "-----------------------------"

if [ -f "$PLUGIN_ROOT/.gitignore" ]; then
    if grep -q "plugin-dev-tools/" "$PLUGIN_ROOT/.gitignore"; then
        report_success ".gitignore incluye plugin-dev-tools/"
    else
        report_error ".gitignore NO incluye plugin-dev-tools/"
        echo "   Solución: Agregar 'plugin-dev-tools/' a .gitignore"
    fi
else
    report_warning "Archivo .gitignore no existe"
fi

# 5. Verificar tamaño del plugin
echo ""
echo "📊 ANÁLISIS DE TAMAÑO DEL PLUGIN..."
echo "-----------------------------------"

TOTAL_SIZE=$(du -sh "$PLUGIN_ROOT" 2>/dev/null | cut -f1)
report_info "Tamaño total del directorio: $TOTAL_SIZE"

# Mostrar directorios más grandes
echo ""
echo "📁 Directorios más grandes:"
du -sh "$PLUGIN_ROOT"/* 2>/dev/null | sort -hr | head -10

# 6. Verificar archivos sensibles
echo ""
echo "🔒 VERIFICANDO ARCHIVOS SENSIBLES..."
echo "------------------------------------"

SENSITIVE_PATTERNS=(
    "*.env"
    "*config-local*"
    "*secret*"
    "*private*"
    "wp-tests-config.php"
    "ai-submodule-helper.sh"
    "install-dev-tools.sh"
    "test-override-function.sh"
)

for pattern in "${SENSITIVE_PATTERNS[@]}"; do
    # Buscar archivos que coinciden con el patrón
    while IFS= read -r -d '' file; do
        # Verificar si el archivo está siendo trackeado por git
        if git ls-files --error-unmatch "$file" >/dev/null 2>&1; then
            report_warning "Archivos sensibles encontrados en git: $(basename "$file")"
        fi
    done < <(find "$PLUGIN_ROOT" -name "$pattern" -not -path "*/plugin-dev-tools/*" -print0 2>/dev/null)
done

# 7. Resumen final
echo ""
echo "📋 RESUMEN DE VERIFICACIÓN"
echo "=========================="

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}🎉 ¡PLUGIN LISTO PARA PRODUCCIÓN!${NC}"
    echo -e "${GREEN}   - Sin errores detectados${NC}"
    echo -e "${GREEN}   - Sin advertencias${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Plugin mayormente listo, pero con advertencias:${NC}"
    echo -e "${YELLOW}   - $WARNINGS advertencia(s) encontrada(s)${NC}"
    echo -e "${YELLOW}   - Revisar y considerar corregir antes de deploy${NC}"
    exit 1
else
    echo -e "${RED}❌ PLUGIN NO LISTO PARA PRODUCCIÓN${NC}"
    echo -e "${RED}   - $ERRORS error(es) crítico(s)${NC}"
    echo -e "${RED}   - $WARNINGS advertencia(s)${NC}"
    echo -e "${RED}   - CORREGIR antes de deploy${NC}"
    exit 2
fi
