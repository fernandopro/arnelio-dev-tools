#!/bin/bash

# =============================================================================
# TAROKINA PRO - SCRIPT DE TESTS SIMPLIFICADO
# =============================================================================
# 
# Script unificado para ejecutar tests de WordPress usando PHPUnit oficial
# 
# Uso:
#   ./run-tests.sh              # Ejecutar todos los tests
#   ./run-tests.sh --unit       # Solo tests unitarios
#   ./run-tests.sh --integration # Solo tests de integración
#   ./run-tests.sh --others     # Tests en otros directorios (excepto unit/ e integration/)
#   ./run-tests.sh --filter=Foo # Ejecutar tests específicos
#   ./run-tests.sh --verbose    # Modo verbose
#   ./run-tests.sh --coverage   # Con coverage
# 
# @package TarokinaPro
# @subpackage DevTools
# =============================================================================

set -e  # Salir en caso de error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}" 2>/dev/null || echo ".")" && pwd)"
cd "$SCRIPT_DIR"

# =============================================================================
# FUNCIONES DE UTILIDAD COMPATIBLES
# =============================================================================

# Función para generar caracteres repetidos (compatible sin seq)
repeat_char() {
    local char="$1"
    local count="$2"
    local result=""
    local i=0
    while [ $i -lt $count ]; do
        result="${result}${char}"
        i=$((i + 1))
    done
    echo "$result"
}

print_header() {
    echo -e "\n${CYAN}===============================================================================${NC}"
    echo -e "${CYAN}🧪 TAROKINA PRO - TESTS UNITARIOS DE WORDPRESS${NC}"
    echo -e "${CYAN}===============================================================================${NC}\n"
}

print_section() {
    echo -e "\n${PURPLE}$1${NC}"
    local len=${#1}
    local separator=$(repeat_char "=" $len)
    echo -e "${PURPLE}${separator}${NC}\n"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# =============================================================================
# VERIFICACIONES PREVIAS
# =============================================================================

check_requirements() {
    print_section "🔍 Verificando requisitos previos"
    
    # Verificar que estamos en el directorio correcto
    if [ ! -f "phpunit.xml" ]; then
        error "phpunit.xml no encontrado. ¿Estás en el directorio correcto?"
        exit 1
    fi
    
    # Verificar Composer
    if [ ! -f "vendor/bin/phpunit" ]; then
        error "PHPUnit no encontrado. Ejecuta: composer install"
        exit 1
    fi
    
    # Verificar WordPress test framework oficial
    if [ ! -d "wordpress-develop/tests/phpunit" ]; then
        error "WordPress test framework oficial no encontrado. Ejecuta: git clone https://github.com/WordPress/wordpress-develop"
        exit 1
    fi
    
    # Verificar configuración de tests
    if [ ! -f "wp-tests-config.php" ]; then
        error "wp-tests-config.php no encontrado"
        exit 1
    fi
    
    # Verificar directorios de tests
    if [ ! -d "tests/unit" ] && [ ! -d "tests/integration" ]; then
        error "Directorios de tests no encontrados (tests/unit o tests/integration)"
        exit 1
    fi
    
    success "Verificaciones completadas"
}

# =============================================================================
# DETECCIÓN DE PHP
# =============================================================================

detect_php() {
    # Buscar PHP en rutas comunes de WordPress Local by Flywheel
    PHP_CMD=""
    # Rutas específicas para Local by Flywheel y macOS con Homebrew
    for php_path in \
        /usr/local/bin/php \
        /opt/homebrew/bin/php \
        /usr/bin/php \
        /opt/local/bin/php \
        /Applications/Local\ by\ Flywheel.app/Contents/Resources/extraResources/bin/php/php \
        $(command -v php 2>/dev/null) \
        php; do
        
        if [ -n "$php_path" ] && [ -x "$php_path" ] 2>/dev/null; then
            PHP_CMD="$php_path"
            break
        fi
    done
    
    if [ -z "$PHP_CMD" ]; then
        error "PHP no encontrado en ninguna ruta conocida"
        error "Rutas verificadas:"
        error "  - /usr/local/bin/php"
        error "  - /opt/homebrew/bin/php"
        error "  - /usr/bin/php"
        error "  - command -v php"
        exit 1
    fi
}

# =============================================================================
# INFORMACIÓN DEL SISTEMA
# =============================================================================

show_config_info() {
    print_section "⚙️  Configuración del entorno"
    
    info "Framework: WordPress PHPUnit oficial"
    info "Bootstrap: wordpress-develop/tests/phpunit/includes/bootstrap.php"
    info "Configuración: phpunit.xml"
    info "Tests unitarios: tests/unit/"
    info "Tests integración: tests/integration/"
    info "Base de datos: local@localhost (prefijo: wp_test_)"
    info "WordPress: Framework completo cargado"
    
    # Mostrar PHP y PHPUnit version usando la detección previa
    if [ -n "$PHP_CMD" ]; then
        PHP_VERSION=$($PHP_CMD -r "echo PHP_VERSION;" 2>/dev/null || echo "Detectado")
        info "PHP: $PHP_VERSION (usando $PHP_CMD)"
    else
        warning "PHP: No encontrado"
    fi
    
    if [ -f "./vendor/bin/phpunit" ]; then
        # Evitar usar head que puede no estar disponible
        PHPUNIT_VERSION=$(./vendor/bin/phpunit --version 2>/dev/null | sed -n '1p' || echo "PHPUnit disponible")
        info "PHPUnit: $PHPUNIT_VERSION"
    else
        info "PHPUnit: No encontrado"
    fi
}

# =============================================================================
# EJECUCIÓN DE TESTS
# =============================================================================

run_tests() {
    print_section "🚀 Ejecutando tests de WordPress"
    
    # Verificar que PHP esté disponible
    if [ -z "$PHP_CMD" ]; then
        error "PHP no encontrado. No se pueden ejecutar los tests."
        return 1
    fi
    
    # Preparar comando PHPUnit con ruta explícita de PHP
    local phpunit_cmd="$PHP_CMD ./vendor/bin/phpunit"
    local args=""
    
    # Procesar argumentos especiales
    for arg in "$@"; do
        case $arg in
            --unit)
                args="$args --testsuite=unit"
                info "Ejecutando solo tests unitarios"
                ;;
            --integration)
                args="$args --testsuite=integration"
                info "Ejecutando solo tests de integración"
                ;;
            --others)
                args="$args --testsuite=tarokina-other-tests"
                info "Ejecutando tests otros (cualquier directorio excepto unit/ e integration/)"
                ;;
            --all)
                args="$args --testsuite=all-tests"
                info "Ejecutando todos los tests (unitarios + integración + otros)"
                ;;
            *)
                args="$args $arg"
                ;;
        esac
    done
    
    # Añadir argumentos procesados
    if [ -n "$args" ]; then
        phpunit_cmd="$phpunit_cmd$args"
    fi
    
    info "Comando: $phpunit_cmd"
    echo
    
    # Ejecutar tests
    if $phpunit_cmd; then
        echo
        success "Tests completados exitosamente"
        return 0
    else
        echo
        error "Algunos tests fallaron"
        return 1
    fi
}

# =============================================================================
# FUNCIONES DE AYUDA
# =============================================================================

show_help() {
    cat << EOF
${CYAN}TAROKINA PRO - SCRIPT DE TESTS SIMPLIFICADO${NC}

${YELLOW}USO:${NC}
  ./run-tests.sh [opciones]

${YELLOW}OPCIONES COMUNES:${NC}
  --help                    Mostrar esta ayuda
  --filter=PATTERN          Ejecutar solo tests que coincidan con el patrón
  --group=GROUP             Ejecutar solo tests del grupo especificado
  --verbose                 Salida verbose (formato estable sin emojis UTF-8)
  --coverage-html=DIR       Generar reporte de coverage HTML
  --coverage-text           Mostrar coverage en texto
  --stop-on-failure         Parar en el primer fallo
  --stop-on-error           Parar en el primer error

${YELLOW}EJEMPLOS:${NC}
  ./run-tests.sh                                    # Todos los tests
  ./run-tests.sh --filter=TarokinaWordPress         # Tests específicos
  ./run-tests.sh --group=integration               # Tests de integración
  ./run-tests.sh --verbose                         # Salida detallada estable
  ./run-tests.sh --coverage-html=coverage          # Con coverage HTML
  ./run-tests.sh --stop-on-failure                 # Parar en fallos

${YELLOW}GRUPOS DISPONIBLES:${NC}
  tarokina        - Todos los tests del plugin
  integration     - Tests de integración
  wordpress       - Tests del framework WordPress
  factories       - Tests de data factories
  hooks           - Tests de hooks y filters

${YELLOW}ARCHIVOS IMPORTANTES:${NC}
  phpunit.xml           - Configuración principal de PHPUnit
  wp-tests-config.php   - Configuración de WordPress para tests
  tests/wordpress/      - Directorio de tests

${YELLOW}BASE DE DATOS:${NC}
  Usa la misma BD del sitio (local) con prefijo diferente (wp_test_)
  para evitar conflictos con datos reales del sitio.

EOF
}

# =============================================================================
# FUNCIÓN PRINCIPAL
# =============================================================================

main() {
    # Verificar si se solicita ayuda
    if [[ "$1" == "--help" || "$1" == "-h" ]]; then
        show_help
        exit 0
    fi
    
    # Detectar PHP antes que nada
    detect_php
    
    # Mostrar header
    print_header
    
    # Mostrar timestamp (compatible sin date)
    if command -v date >/dev/null 2>&1; then
        info "Inicio: $(date '+%Y-%m-%d %H:%M:%S')"
    else
        info "Inicio: $($PHP_CMD -r "echo date('Y-m-d H:i:s');" 2>/dev/null || echo 'Now')"
    fi
    
    # Verificar requisitos
    check_requirements
    
    # Mostrar configuración
    show_config_info
    
    # Ejecutar tests
    if run_tests "$@"; then
        echo
        success "🎉 Todos los tests completados exitosamente"
        exit 0
    else
        echo
        error "💥 Algunos tests fallaron"
        exit 1
    fi
}

# Ejecutar función principal con todos los argumentos
main "$@"
