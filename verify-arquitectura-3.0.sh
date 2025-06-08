#!/bin/bash

# Script de verificación completa - Arquitectura 3.0
# Verifica que todos los componentes estén en su lugar

echo "=== VERIFICACIÓN ARQUITECTURA 3.0 DEV-TOOLS ==="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para verificar archivos
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅ $1${NC}"
        return 0
    else
        echo -e "${RED}❌ $1${NC}"
        return 1
    fi
}

# Función para verificar directorios
check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✅ $1/${NC}"
        return 0
    else
        echo -e "${RED}❌ $1/${NC}"
        return 1
    fi
}

# Verificar estructura core
echo -e "${BLUE}🏗️  ESTRUCTURA CORE${NC}"
check_file "config.php"
check_file "loader.php"
check_file "ajax-handler.php"
check_file "debug-ajax.php"
check_dir "core"
check_file "core/interfaces/DevToolsModuleInterface.php"
check_file "core/DevToolsModuleBase.php"
check_file "core/DevToolsModuleManager.php"
echo ""

# Verificar módulos
echo -e "${BLUE}📦 MÓDULOS${NC}"
check_dir "modules"
check_file "modules/DashboardModule.php"
echo ""

# Verificar assets compilados
echo -e "${BLUE}🎨 ASSETS COMPILADOS${NC}"
check_dir "dist"
check_dir "dist/js"
check_dir "dist/css"
check_file "dist/js/dev-tools.min.js"
check_file "dist/js/dev-utils.min.js"
check_file "dist/js/dashboard.min.js"
check_file "dist/css/dev-tools-styles.min.css"
echo ""

# Verificar sources
echo -e "${BLUE}📝 CÓDIGO FUENTE${NC}"
check_dir "src"
check_dir "src/js"
check_file "src/js/dev-tools.js"
check_file "src/js/dev-utils.js"
check_file "src/js/dashboard.js"
echo ""

# Verificar documentación
echo -e "${BLUE}📚 DOCUMENTACIÓN${NC}"
check_dir "docs"
check_file "docs/ANALISIS-REFACTORIZACION-2025-06-08.md"
echo ""

# Verificar tests
echo -e "${BLUE}🧪 SISTEMA DE TESTS${NC}"
check_file "test-arquitectura-3.0.js"
check_dir "tests"
echo ""

# Verificar configuración de build
echo -e "${BLUE}⚙️  CONFIGURACIÓN BUILD${NC}"
check_file "webpack.config.js"
check_file "package.json"
echo ""

# Verificar sintaxis PHP
echo -e "${BLUE}🔍 VERIFICACIÓN SINTAXIS PHP${NC}"
php_files=(
    "config.php"
    "loader.php"
    "ajax-handler.php"
    "debug-ajax.php"
    "core/DevToolsModuleBase.php"
    "core/DevToolsModuleManager.php"
    "core/interfaces/DevToolsModuleInterface.php"
    "modules/DashboardModule.php"
)

syntax_errors=0
for file in "${php_files[@]}"; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo -e "${GREEN}✅ $file - sintaxis OK${NC}"
        else
            echo -e "${RED}❌ $file - ERROR DE SINTAXIS${NC}"
            php -l "$file"
            ((syntax_errors++))
        fi
    fi
done
echo ""

# Verificar tamaños de assets
echo -e "${BLUE}📊 TAMAÑOS DE ASSETS${NC}"
if [ -f "dist/js/dashboard.min.js" ]; then
    size=$(wc -c < "dist/js/dashboard.min.js")
    if [ $size -gt 100 ]; then
        echo -e "${GREEN}✅ dashboard.min.js: ${size} bytes${NC}"
    else
        echo -e "${YELLOW}⚠️  dashboard.min.js: ${size} bytes (muy pequeño)${NC}"
    fi
fi

if [ -f "dist/js/dev-tools.min.js" ]; then
    size=$(wc -c < "dist/js/dev-tools.min.js")
    echo -e "${GREEN}✅ dev-tools.min.js: ${size} bytes${NC}"
fi

if [ -f "dist/css/dev-tools-styles.min.css" ]; then
    size=$(wc -c < "dist/css/dev-tools-styles.min.css")
    echo -e "${GREEN}✅ dev-tools-styles.min.css: ${size} bytes${NC}"
fi
echo ""

# Verificar configuración webpack
echo -e "${BLUE}📦 CONFIGURACIÓN WEBPACK${NC}"
if [ -f "webpack.config.js" ]; then
    if grep -q "dashboard.*dashboard.js" webpack.config.js; then
        echo -e "${GREEN}✅ Dashboard entry point configurado${NC}"
    else
        echo -e "${YELLOW}⚠️  Dashboard entry point no encontrado${NC}"
    fi
    
    if grep -q "DevToolsModuleBase" modules/DashboardModule.php; then
        echo -e "${GREEN}✅ DashboardModule extiende DevToolsModuleBase${NC}"
    else
        echo -e "${RED}❌ DashboardModule no extiende DevToolsModuleBase${NC}"
    fi
fi
echo ""

# Estado final
echo -e "${BLUE}🎯 ESTADO FINAL${NC}"
if [ $syntax_errors -eq 0 ]; then
    echo -e "${GREEN}✅ Todos los archivos PHP tienen sintaxis correcta${NC}"
    echo -e "${GREEN}✅ Arquitectura 3.0 lista para pruebas${NC}"
    echo ""
    echo -e "${YELLOW}📋 SIGUIENTES PASOS:${NC}"
    echo "1. Ir a /wp-admin/tools.php?page=tarokina-2025-dev-tools"
    echo "2. Abrir consola del navegador (F12)"
    echo "3. Ejecutar: cat test-arquitectura-3.0.js | pbcopy"
    echo "4. Pegar en consola y verificar resultados"
    echo ""
    echo -e "${BLUE}🌐 URL esperada:${NC}"
    echo "http://localhost:10019/wp-admin/tools.php?page=tarokina-2025-dev-tools"
else
    echo -e "${RED}❌ Errores de sintaxis encontrados: $syntax_errors${NC}"
    echo "Corregir errores antes de continuar"
fi

echo ""
echo "=== VERIFICACIÓN COMPLETADA ==="
