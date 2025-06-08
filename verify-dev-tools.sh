#!/bin/bash

# ===========================================
# 🔍 VERIFICADOR DEL SISTEMA DEV-TOOLS
# ===========================================
# Script para verificar que el sistema dev-tools esté completamente funcional
# 
# Uso: ./verify-dev-tools.sh

echo "🔍 Verificador del Sistema Dev-Tools"
echo "==================================="
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Contadores
PASSED=0
FAILED=0

# Funciones
check_pass() {
    echo -e "${GREEN}✅ $1${NC}"
    PASSED=$((PASSED + 1))
}

check_fail() {
    echo -e "${RED}❌ $1${NC}"
    FAILED=$((FAILED + 1))
}

check_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "phpunit.xml" ]; then
    echo -e "${RED}❌ Error: Este script debe ejecutarse desde el directorio dev-tools/${NC}"
    exit 1
fi

echo "🔍 Verificando estructura de directorios..."
echo ""

# Verificar directorios críticos
if [ -d "vendor" ]; then
    check_pass "Directorio vendor/ (Composer)"
else
    check_fail "Directorio vendor/ faltante - Ejecuta: composer install"
fi

if [ -d "node_modules" ]; then
    check_pass "Directorio node_modules/ (Node.js)"
else
    check_fail "Directorio node_modules/ faltante - Ejecuta: npm install"
fi

if [ -d "dist" ]; then
    check_pass "Directorio dist/ (Assets compilados)"
else
    check_fail "Directorio dist/ faltante - Ejecuta: npm run dev"
fi

if [ -d "wordpress-develop" ]; then
    check_pass "Framework WordPress oficial"
    if [ -d "wordpress-develop/tests/phpunit" ]; then
        check_pass "Tests PHPUnit de WordPress disponibles"
    else
        check_fail "Tests PHPUnit de WordPress no encontrados"
    fi
else
    check_fail "Framework WordPress faltante - Ejecuta: git clone https://github.com/WordPress/wordpress-develop.git"
fi

echo ""
echo "🔍 Verificando archivos ejecutables..."
echo ""

# Verificar ejecutables críticos
if [ -f "vendor/bin/phpunit" ]; then
    check_pass "PHPUnit ejecutable"
    PHPUNIT_VERSION=$(./vendor/bin/phpunit --version 2>/dev/null | head -1)
    info "Versión: $PHPUNIT_VERSION"
else
    check_fail "PHPUnit no encontrado"
fi

if [ -x "run-tests.sh" ]; then
    check_pass "Script run-tests.sh ejecutable"
else
    check_fail "Script run-tests.sh no ejecutable - Ejecuta: chmod +x run-tests.sh"
fi

if [ -x "install.sh" ]; then
    check_pass "Script install.sh ejecutable"
else
    check_fail "Script install.sh no ejecutable - Ejecuta: chmod +x install.sh"
fi

echo ""
echo "🔍 Verificando archivos de configuración..."
echo ""

# Verificar configuraciones
if [ -f "phpunit.xml" ]; then
    check_pass "Configuración PHPUnit (phpunit.xml)"
else
    check_fail "phpunit.xml faltante"
fi

if [ -f "wp-tests-config.php" ]; then
    check_pass "Configuración WordPress tests"
else
    check_fail "wp-tests-config.php faltante"
fi

if [ -f "package.json" ]; then
    check_pass "Configuración Node.js (package.json)"
else
    check_fail "package.json faltante"
fi

if [ -f "composer.json" ]; then
    check_pass "Configuración Composer (composer.json)"
else
    check_fail "composer.json faltante"
fi

echo ""
echo "🔍 Verificando assets compilados..."
echo ""

# Verificar assets compilados
CRITICAL_ASSETS=(
    "dist/js/dev-tools.min.js"
    "dist/js/dev-utils.min.js"
    "dist/css/dev-tools-styles.min.css"
)

for asset in "${CRITICAL_ASSETS[@]}"; do
    if [ -f "$asset" ]; then
        check_pass "Asset: $(basename $asset)"
        SIZE=$(du -h "$asset" 2>/dev/null | cut -f1)
        info "Tamaño: $SIZE"
    else
        check_fail "Asset faltante: $asset - Ejecuta: npm run dev"
    fi
done

echo ""
echo "🔍 Verificando tests disponibles..."
echo ""

# Verificar tests
UNIT_TESTS=$(find tests/unit -name "*Test.php" 2>/dev/null | wc -l | tr -d ' ')
INTEGRATION_TESTS=$(find tests/integration -name "*Test.php" 2>/dev/null | wc -l | tr -d ' ')

if [ "$UNIT_TESTS" -gt 0 ]; then
    check_pass "Tests unitarios disponibles ($UNIT_TESTS archivos)"
else
    check_fail "No se encontraron tests unitarios"
fi

if [ "$INTEGRATION_TESTS" -gt 0 ]; then
    check_pass "Tests de integración disponibles ($INTEGRATION_TESTS archivos)"
else
    check_fail "No se encontraron tests de integración"
fi

echo ""
echo "🔍 Verificando comandos Node.js..."
echo ""

# Verificar package.json scripts
if command -v npm &> /dev/null; then
    check_pass "npm disponible"
    
    # Verificar scripts críticos
    if npm run 2>/dev/null | grep -q "dev"; then
        check_pass "Script 'npm run dev' disponible"
    else
        check_fail "Script 'npm run dev' no encontrado"
    fi
    
    if npm run 2>/dev/null | grep -q "build"; then
        check_pass "Script 'npm run build' disponible"
    else
        check_fail "Script 'npm run build' no encontrado"
    fi
else
    check_fail "npm no está instalado"
fi

echo ""
echo "📊 RESUMEN DE VERIFICACIÓN"
echo "=========================="
echo ""

TOTAL=$((PASSED + FAILED))
if [ $TOTAL -gt 0 ]; then
    PERCENTAGE=$((PASSED * 100 / TOTAL))
    echo "✅ Pasaron: $PASSED"
    echo "❌ Fallaron: $FAILED"
    echo "📊 Porcentaje: $PERCENTAGE%"
    echo ""
    
    if [ $FAILED -eq 0 ]; then
        echo -e "${GREEN}🎉 ¡SISTEMA COMPLETAMENTE FUNCIONAL!${NC}"
        echo ""
        info "Comandos disponibles:"
        echo "  🧪 ./run-tests.sh --unit"
        echo "  🧪 ./run-tests.sh --integration"
        echo "  🔧 npm run dev"
        echo "  📦 npm run build"
        echo ""
        info "Panel web: http://localhost:10019/wp-admin/tools.php?page=tarokina-dev-tools"
        exit 0
    elif [ $FAILED -le 2 ]; then
        echo -e "${YELLOW}⚠️  Sistema mayormente funcional con advertencias menores${NC}"
        exit 1
    else
        echo -e "${RED}❌ Sistema con problemas significativos${NC}"
        echo ""
        info "Ejecuta ./install.sh para reinstalar dependencias"
        exit 2
    fi
else
    echo -e "${RED}❌ No se pudieron ejecutar las verificaciones${NC}"
    exit 3
fi
