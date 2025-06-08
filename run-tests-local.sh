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
