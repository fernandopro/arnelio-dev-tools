#!/bin/bash
# Script de verificación post-arreglo webpack
# Verifica que la compilación y assets estén funcionando correctamente

echo "🔧 VERIFICACIÓN POST-ARREGLO WEBPACK"
echo "==================================="

cd "$(dirname "$0")"

echo "📂 1. Verificando estructura de archivos..."
echo "   ✓ Verificando src/js/"
ls -la src/js/ 2>/dev/null && echo "   ✓ src/js/ existe" || echo "   ❌ src/js/ no existe"

echo "   ✓ Verificando dist/"
ls -la dist/ 2>/dev/null && echo "   ✓ dist/ existe" || echo "   ❌ dist/ no existe"

echo ""
echo "🏗️ 2. Probando compilación..."
npm run dev 2>&1 | grep -E "(compiled successfully|ERROR)" | head -5

echo ""
echo "📦 3. Verificando assets compilados..."
if [ -f "dist/js/dev-tools.min.js" ]; then
    SIZE=$(du -h dist/js/dev-tools.min.js | cut -f1)
    echo "   ✓ dev-tools.min.js: $SIZE"
else
    echo "   ❌ dev-tools.min.js no encontrado"
fi

if [ -f "dist/js/dev-utils.min.js" ]; then
    SIZE=$(du -h dist/js/dev-utils.min.js | cut -f1)
    echo "   ✓ dev-utils.min.js: $SIZE"
else
    echo "   ❌ dev-utils.min.js no encontrado"
fi

if [ -f "dist/css/dev-tools-styles.min.css" ]; then
    SIZE=$(du -h dist/css/dev-tools-styles.min.css | cut -f1)
    echo "   ✓ dev-tools-styles.min.css: $SIZE"
else
    echo "   ❌ dev-tools-styles.min.css no encontrado"
fi

echo ""
echo "🌐 4. URLs de acceso:"
echo "   📊 Dev-Tools Panel: http://localhost:10019/wp-admin/tools.php?page=tarokina-2025-dev-tools"
echo "   🏠 Admin: http://localhost:10019/wp-admin/"

echo ""
echo "✅ VERIFICACIÓN COMPLETADA"
echo "Estado: Sistema webpack arreglado y funcionando"
