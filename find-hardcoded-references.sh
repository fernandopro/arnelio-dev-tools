#!/bin/bash

# Script para encontrar y reportar referencias hardcodeadas a "tarokina"
# en el sistema dev-tools (problema de arquitectura plugin-agnóstica)

echo "🔍 BUSCANDO REFERENCIAS HARDCODEADAS A 'TAROKINA'"
echo "==============================================="
echo ""

# Colores para output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Función para contar líneas
count_issues() {
    local count=$1
    if [ $count -eq 0 ]; then
        echo -e "${GREEN}✅ Sin problemas${NC}"
    elif [ $count -le 5 ]; then
        echo -e "${YELLOW}⚠️  $count referencias encontradas${NC}"
    else
        echo -e "${RED}❌ $count referencias encontradas (crítico)${NC}"
    fi
}

# Buscar en archivos PHP críticos (excluyendo tests y templates)
echo -e "${CYAN}📋 1. ARCHIVOS PHP CRÍTICOS (core, modules, loader)${NC}"
php_critical=$(grep -r "tarokina" --include="*.php" \
    core/ modules/ *.php 2>/dev/null | \
    grep -v test | grep -v template | grep -v example | \
    wc -l)
count_issues $php_critical

if [ $php_critical -gt 0 ]; then
    echo "Detalles:"
    grep -r "tarokina" --include="*.php" core/ modules/ *.php 2>/dev/null | \
        grep -v test | grep -v template | grep -v example | \
        head -10
fi
echo ""

# Buscar en archivos de configuración
echo -e "${CYAN}📋 2. ARCHIVOS DE CONFIGURACIÓN${NC}"
config_issues=$(grep -r "tarokina" --include="config*.php" . 2>/dev/null | \
    grep -v template | grep -v example | wc -l)
count_issues $config_issues

if [ $config_issues -gt 0 ]; then
    echo "Detalles:"
    grep -r "tarokina" --include="config*.php" . 2>/dev/null | \
        grep -v template | grep -v example
fi
echo ""

# Buscar en tabs y templates
echo -e "${CYAN}📋 3. TABS Y TEMPLATES${NC}"
tabs_issues=$(grep -r "tarokina" --include="*.php" tabs/ templates/ 2>/dev/null | wc -l)
count_issues $tabs_issues

if [ $tabs_issues -gt 0 ]; then
    echo "Detalles:"
    grep -r "tarokina" --include="*.php" tabs/ templates/ 2>/dev/null
fi
echo ""

# Buscar en JavaScript
echo -e "${CYAN}📋 4. ARCHIVOS JAVASCRIPT${NC}"
js_issues=$(grep -r "tarokina" --include="*.js" src/ dist/ . 2>/dev/null | \
    grep -v test | grep -v node_modules | wc -l)
count_issues $js_issues

if [ $js_issues -gt 0 ]; then
    echo "Detalles:"
    grep -r "tarokina" --include="*.js" src/ dist/ . 2>/dev/null | \
        grep -v test | grep -v node_modules | head -5
fi
echo ""

# Buscar en tests (separado para análisis)
echo -e "${CYAN}📋 5. ARCHIVOS DE TESTS (revisión recomendada)${NC}"
test_issues=$(grep -r "tarokina" --include="*.php" tests/ 2>/dev/null | wc -l)
echo -e "${YELLOW}ℹ️  $test_issues referencias en tests${NC}"

if [ $test_issues -gt 0 ]; then
    echo "Los tests pueden tener referencias para casos específicos."
    echo "Revisar manualmente si deben ser dinámicas:"
    grep -r "tarokina" --include="*.php" tests/ 2>/dev/null | head -5
fi
echo ""

# Calcular total crítico
total_critical=$((php_critical + config_issues + tabs_issues + js_issues))

echo -e "${CYAN}📊 RESUMEN FINAL${NC}"
echo "==================="
echo "Archivos PHP críticos: $php_critical"
echo "Configuración: $config_issues" 
echo "Tabs/Templates: $tabs_issues"
echo "JavaScript: $js_issues"
echo "Tests (revisar): $test_issues"
echo ""

if [ $total_critical -eq 0 ]; then
    echo -e "${GREEN}🎉 ¡SISTEMA COMPLETAMENTE PLUGIN-AGNÓSTICO!${NC}"
    echo "✅ No se encontraron referencias hardcodeadas críticas"
else
    echo -e "${RED}⚠️  REFERENCIAS HARDCODEADAS ENCONTRADAS: $total_critical${NC}"
    echo ""
    echo -e "${YELLOW}🔧 PASOS PARA CORREGIR:${NC}"
    echo "1. Reemplazar referencias hardcodeadas por \$config->get('host.name')"
    echo "2. Usar \$config->get('ajax.action_name') para acciones AJAX"
    echo "3. Usar \$config->get('host.file') para archivo principal"
    echo "4. Verificar que templates sean genéricos"
    echo ""
    echo -e "${CYAN}📝 EJEMPLO DE CORRECCIÓN:${NC}"
    echo "// ❌ INCORRECTO"
    echo "// \$plugin_data = get_plugin_data('/path/tarokina-pro.php');"
    echo ""
    echo "// ✅ CORRECTO"
    echo "// \$config = dev_tools_config();"
    echo "// \$plugin_data = get_plugin_data(\$config->get('host.file'));"
fi

echo ""
echo -e "${GREEN}=== ANÁLISIS COMPLETADO ===${NC}"
