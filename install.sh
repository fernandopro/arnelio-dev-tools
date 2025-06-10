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

