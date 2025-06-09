#!/bin/bash

# ===========================================
# 🚀 INSTALADOR AUTOMÁTICO - Dev Tools Arquitectura 3.0
# ===========================================
# Script plugin-agnóstico para instalar dependencias después de clonar desde GitHub
# Incluye: Node.js modules, Composer, WordPress testing framework
# Compatible con macOS, Linux y Windows (WSL/Git Bash)
#
# Uso: ./install.sh
# 
# Instalará automáticamente:
# 1. Dependencias de Node.js (npm install)
# 2. Dependencias de PHP (composer install)
# 3. Framework oficial de WordPress para testing
# 4. Compilación inicial de assets (npm run dev)

echo "🚀 Instalador Automático - Dev Tools Arquitectura 3.0"
echo "====================================================="
echo "📦 Instalación completa del entorno de desarrollo"
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

# ==========================================
# DETECCIÓN AUTOMÁTICA DEL PLUGIN PADRE
# ==========================================
info "Detectando configuración del plugin padre..."

# Detectar directorio del plugin padre
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
PLUGIN_NAME="$(basename "$PLUGIN_DIR")"

success "Plugin detectado: $PLUGIN_NAME"
info "Directorio plugin: $PLUGIN_DIR"
info "Directorio dev-tools: $SCRIPT_DIR"

# Verificar que existe el sistema de debug
if [ -f "$SCRIPT_DIR/core/DebugWordPressDynamic.php" ]; then
    success "Sistema de Debug WordPress Dinámico encontrado"
else
    warning "Sistema de debug no encontrado (se instalará con la carga automática)"
fi

echo ""
echo "🏗️  Iniciando instalación completa..."
echo ""

# ==========================================
# INSTALACIÓN DE NODE.JS MODULES
# ==========================================
info "Paso 1/4: Instalando módulos de Node.js..."

npm install

if [ $? -eq 0 ]; then
    success "Módulos de Node.js instalados correctamente"
else
    error "Fallo al instalar módulos de Node.js"
fi

echo ""

# ==========================================
# INSTALACIÓN DE COMPOSER
# ==========================================
info "Paso 2/4: Configurando Composer..."

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
# DESCARGA DEL FRAMEWORK OFICIAL DE WORDPRESS
# ==========================================
info "Paso 3/4: Descargando framework oficial de WordPress para testing..."

# Verificar si wordpress-develop ya existe
if [ -d "wordpress-develop" ]; then
    warning "El directorio wordpress-develop ya existe, omitiendo descarga"
    success "Framework de WordPress ya está disponible"
else
    info "Clonando repositorio wordpress-develop (esto puede tomar varios minutos)..."
    
    # Clonar el repositorio oficial de WordPress
    git clone --depth=1 https://github.com/WordPress/wordpress-develop.git
    
    if [ $? -eq 0 ] && [ -d "wordpress-develop" ]; then
        success "Framework de WordPress descargado correctamente"
        info "Tamaño del framework: $(du -sh wordpress-develop 2>/dev/null | cut -f1 || echo 'N/A')"
    else
        error "Fallo al descargar el framework de WordPress"
    fi
fi

echo ""

# ==========================================
# COMPILACIÓN INICIAL
# ==========================================
info "Paso 4/4: Compilando assets para desarrollo..."

npm run dev

if [ $? -eq 0 ]; then
    success "Assets compilados correctamente para desarrollo"
else
    warning "Fallo al compilar assets (puedes ejecutar 'npm run dev' manualmente)"
fi

echo ""
echo "🎉 ¡INSTALACIÓN COMPLETADA!"
echo "=========================="
echo ""
success "🧪 Sistema de testing listo - Framework WordPress oficial descargado"
success "📦 Dependencias de Node.js y PHP instaladas"
success "🔧 Assets compilados para desarrollo"
success "🔍 Sistema de Debug WordPress Dinámico integrado"
echo ""
info "Comandos útiles disponibles:"
echo "  🔧 npm run dev                    - Compilar para desarrollo (CRÍTICO para dev-tools)"
echo "  📦 npm run build                  - Compilar para producción"
echo "  👀 npm run watch                  - Compilar y observar cambios"
echo "  🧪 ./run-tests.sh                 - Ejecutar todos los tests"
echo "  🧪 ./run-tests.sh --unit          - Ejecutar solo tests unitarios"
echo "  🧪 ./run-tests.sh --integration   - Ejecutar tests de integración"
echo "  ✅ ./verify-debug-system.sh       - Verificar sistema de debug"
echo ""
warning "⚠️  RECORDATORIO CRÍTICO:"
echo "   El sistema dev-tools requiere 'npm run dev' para funcionar correctamente."
echo "   Siempre ejecuta este comando antes de trabajar con dev-tools."
echo ""
info "🔍 Sistema de Debug disponible:"
echo "   • Debug visual: ?debug_config=1 y ?debug_urls=1"
echo "   • Funciones PHP: get_debug_url_data(), validate_url_consistency()"
echo "   • Endpoints AJAX: wp_ajax_debug_validate_urls"
echo "   • Documentación: docs/DEBUG-WORDPRESS-DYNAMIC.md"
echo ""
info "📍 Panel de dev-tools disponible en:"
echo "   🌐 [URL dinámica] - Usar sistema de debug para URLs específicas"
echo "   📋 Formato: /wp-admin/tools.php?page=[dev_tools_slug]"
echo "   🔧 Debug de URLs: Agregar ?debug_config=1 a cualquier página admin"
echo ""

# ==========================================
# VERIFICACIÓN FINAL DEL SISTEMA
# ==========================================
info "🔍 Ejecutando verificación final del sistema..."

if [ -f "verify-debug-system.sh" ]; then
    echo ""
    info "Ejecutando verificador de debug..."
    bash verify-debug-system.sh
    echo ""
else
    warning "Script de verificación no encontrado (verify-debug-system.sh)"
fi

success "¡El sistema Dev Tools Arquitectura 3.0 está completamente listo para usar!"
echo ""
info "🎯 Próximos pasos recomendados:"
echo "   1. Activar el plugin en WordPress"
echo "   2. Ir al admin y usar ?debug_config=1 para ver URLs dinámicas"
echo "   3. Acceder al panel de dev-tools desde Herramientas > Dev Tools"
echo "   4. Revisar la documentación en docs/"
echo ""
