#!/bin/bash

# ===========================================
# 🚀 INSTALADOR AUTOMÁTICO - Tarokina Pro Dev Tools
# ===========================================
# Script para instalar dependencias después de clonar desde GitHub
# Compatible con macOS, Linux y Windows (WSL/Git Bash)

echo "🚀 Instalador Automático - Tarokina Pro Dev Tools"
echo "=================================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar errores
error() {
    echo -e "${RED}❌ Error: $1${NC}"
    exit 1
}

# Función para mostrar éxito
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Función para mostrar información
info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Función para mostrar advertencias
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "package.json" ] || [ ! -f "composer.json" ]; then
    error "Este script debe ejecutarse desde el directorio dev-tools/"
fi

echo "🔍 Verificando requisitos del sistema..."
echo ""

# Verificar Node.js
if ! command -v node &> /dev/null; then
    error "Node.js no está instalado. Instálalo desde: https://nodejs.org/"
fi

NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 16 ]; then
    warning "Node.js versión $NODE_VERSION detectada. Se recomienda versión 16 o superior."
else
    success "Node.js $(node --version) detectado"
fi

# Verificar npm
if ! command -v npm &> /dev/null; then
    error "npm no está instalado. Viene incluido con Node.js."
else
    success "npm $(npm --version) detectado"
fi

# Verificar PHP
if ! command -v php &> /dev/null; then
    error "PHP no está instalado. Se requiere PHP 7.4 o superior."
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
success "PHP $PHP_VERSION detectado"

echo ""
echo "🏗️  Iniciando instalación..."
echo ""

# ==========================================
# INSTALACIÓN DE COMPOSER
# ==========================================
info "Paso 1/3: Configurando Composer..."

# Verificar si composer.phar existe
if [ ! -f "composer.phar" ]; then
    info "Descargando Composer..."
    
    # Descargar composer de forma segura
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === file_get_contents('https://composer.github.io/installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    
    if [ -f "composer.phar" ]; then
        success "Composer descargado correctamente"
    else
        error "Fallo al descargar Composer"
    fi
else
    success "Composer.phar ya existe"
fi

# Instalar dependencias de Composer
info "Instalando dependencias de PHP..."
php composer.phar install --no-interaction --prefer-dist --optimize-autoloader

if [ $? -eq 0 ]; then
    success "Dependencias de PHP instaladas correctamente"
else
    error "Fallo al instalar dependencias de PHP"
fi

echo ""

# ==========================================
# INSTALACIÓN DE NODE.JS MODULES
# ==========================================
info "Paso 2/3: Instalando módulos de Node.js..."

npm install

if [ $? -eq 0 ]; then
    success "Módulos de Node.js instalados correctamente"
else
    error "Fallo al instalar módulos de Node.js"
fi

echo ""

# ==========================================
# COMPILACIÓN INICIAL
# ==========================================
info "Paso 3/3: Compilando assets..."

npm run build

if [ $? -eq 0 ]; then
    success "Assets compilados correctamente"
else
    warning "Fallo al compilar assets (puedes ejecutar 'npm run build' manualmente)"
fi

echo ""
echo "🎉 ¡INSTALACIÓN COMPLETADA!"
echo "=========================="
echo ""
info "Comandos útiles disponibles:"
echo "  📦 npm run build         - Compilar para producción"
echo "  🔧 npm run dev           - Compilar para desarrollo"
echo "  👀 npm run watch         - Compilar y observar cambios"
echo "  🧪 php composer.phar run test  - Ejecutar tests"
echo ""
info "Para ver todos los comandos disponibles:"
echo "  ./composer-commands.sh    - Comandos de Composer"
echo ""
success "¡El sistema Dev Tools está listo para usar!"
echo ""
