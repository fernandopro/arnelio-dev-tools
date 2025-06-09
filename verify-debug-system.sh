#!/bin/bash
# Script de verificación del Sistema de Debug WordPress Dinámico
# Parte del núcleo de Dev-Tools Arquitectura 3.0
# Plugin-agnóstico - Detecta automáticamente la ruta base

echo "🔧 === VERIFICACIÓN SISTEMA DEBUG WORDPRESS DINÁMICO ==="
echo ""

# Detectar directorio base de dev-tools (plugin-agnóstico)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEV_TOOLS_DIR="$SCRIPT_DIR"

echo "📁 Directorio dev-tools detectado: $DEV_TOOLS_DIR"
echo ""

# Verificar que el archivo existe
DEBUG_FILE="$DEV_TOOLS_DIR/core/DebugWordPressDynamic.php"
if [ -f "$DEBUG_FILE" ]; then
    echo "✅ Archivo core encontrado: DebugWordPressDynamic.php"
else
    echo "❌ ERROR: Archivo core NO encontrado"
    echo "   Ruta esperada: $DEBUG_FILE"
    exit 1
fi

# Verificar que está cargado en loader.php
LOADER_FILE="$DEV_TOOLS_DIR/loader.php"
if [ -f "$LOADER_FILE" ] && grep -q "DebugWordPressDynamic.php" "$LOADER_FILE"; then
    echo "✅ Sistema integrado en loader.php"
else
    echo "❌ ERROR: Sistema NO integrado en loader.php"
    echo "   Archivo loader: $LOADER_FILE"
    exit 1
fi

# Verificar sintaxis PHP
echo ""
echo "📋 Verificando sintaxis PHP..."
php -l "$DEBUG_FILE"
if [ $? -eq 0 ]; then
    echo "✅ Sintaxis PHP válida"
else
    echo "❌ ERROR: Sintaxis PHP inválida"
    exit 1
fi

# Verificar documentación
DOC_FILE="$DEV_TOOLS_DIR/docs/DEBUG-WORDPRESS-DYNAMIC.md"
if [ -f "$DOC_FILE" ]; then
    echo "✅ Documentación encontrada: DEBUG-WORDPRESS-DYNAMIC.md"
else
    echo "❌ ERROR: Documentación NO encontrada"
    echo "   Ruta esperada: $DOC_FILE"
    exit 1
fi

# Verificar script de prueba (puede estar en plugin padre)
PLUGIN_DIR="$(dirname "$DEV_TOOLS_DIR")"
TEST_FILE="$PLUGIN_DIR/test-debug-system-consolidated.js"
if [ -f "$TEST_FILE" ]; then
    echo "✅ Script de prueba encontrado: test-debug-system-consolidated.js"
    echo "   Ubicación: $TEST_FILE"
else
    echo "⚠️  AVISO: Script de prueba NO encontrado (opcional)"
    echo "   Ruta esperada: $TEST_FILE"
fi

# Verificar actualización en estado de arquitectura
ESTADO_FILE="$DEV_TOOLS_DIR/docs/ESTADO-ARQUITECTURA-3.0.md"
if [ -f "$ESTADO_FILE" ] && grep -q "DebugWordPressDynamic.php" "$ESTADO_FILE"; then
    echo "✅ Estado de arquitectura actualizado"
else
    echo "❌ ERROR: Estado de arquitectura NO actualizado"
    echo "   Archivo estado: $ESTADO_FILE"
    exit 1
fi

# Verificar compilación dev-tools
DIST_DIR="$DEV_TOOLS_DIR/dist"
if [ -d "$DIST_DIR" ] && [ "$(ls -A $DIST_DIR)" ]; then
    echo "✅ Dev-tools compilado correctamente"
else
    echo "❌ ERROR: Dev-tools NO compilado"
    echo "   Directorio dist: $DIST_DIR"
    echo "   Ejecuta: cd $DEV_TOOLS_DIR && npm run dev"
    exit 1
fi

echo ""
echo "🎯 === INSTRUCCIONES DE PRUEBA ==="
echo ""
echo "1. 🌐 DEBUG VISUAL (en WordPress admin):"
echo "   • Configuración general: ?debug_config=1"
echo "   • Debug de URLs: ?debug_urls=1"
echo "   • Ejemplo: /wp-admin/tools.php?page=dev_tools&debug_config=1"
echo ""
echo "2. 🧪 DEBUG PROGRAMÁTICO (consola del navegador):"
if [ -f "$TEST_FILE" ]; then
    echo "   • Ejecutar contenido de: $TEST_FILE"
else
    echo "   • Usar: DevToolsDebugWordPressDynamic.getInstance().get_debug_url_data()"
fi
echo ""
echo "3. 📋 FUNCIONES PHP DISPONIBLES (automáticas):"
echo "   • get_debug_url_data()"
echo "   • validate_url_consistency(\$urls, \$config)"
echo "   • log_url_issues(\$issues, \$context)"
echo "   • get_debug_validation_nonce()"
echo ""
echo "4. 🔗 ENDPOINTS AJAX (automáticos):"
echo "   • wp_ajax_debug_validate_urls"
echo "   • wp_ajax_debug_url_generation"
echo ""
echo "5. 📖 DOCUMENTACIÓN COMPLETA:"
echo "   • $DOC_FILE"
echo ""
echo "6. 🔧 ERROR LOG (Local by Flywheel):"
echo "   • Monitorear: tail -f \"/Users/[usuario]/Local Sites/[sitio]/logs/php/error.log\""
echo ""
echo "✅ === SISTEMA DE DEBUG LISTO PARA USO ==="
echo "🚀 Disponible automáticamente en todos los plugins que usen Dev-Tools"
echo "📍 Instalado en: $DEV_TOOLS_DIR"
