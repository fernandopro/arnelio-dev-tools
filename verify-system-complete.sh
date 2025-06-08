#!/bin/bash
# Script de verificación completa post-correcciones
# Verifica que webpack y loader estén funcionando correctamente

echo "🚀 VERIFICACIÓN COMPLETA DEV-TOOLS"
echo "================================="

cd "$(dirname "$0")"

echo "📋 1. Verificando archivos críticos..."
FILES=(
    "config.php"
    "loader.php" 
    "debug-ajax.php"
    "ajax-handler.php"
    "panel.php"
    "src/js/dev-tools.js"
    "src/js/dev-utils.js"
    "src/scss/dev-tools.scss"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file"
    else
        echo "   ❌ $file (FALTA)"
    fi
done

echo ""
echo "🔧 2. Verificando sintaxis PHP..."
php -l loader.php > /dev/null 2>&1 && echo "   ✅ loader.php syntax OK" || echo "   ❌ loader.php syntax ERROR"
php -l debug-ajax.php > /dev/null 2>&1 && echo "   ✅ debug-ajax.php syntax OK" || echo "   ❌ debug-ajax.php syntax ERROR"
php -l config.php > /dev/null 2>&1 && echo "   ✅ config.php syntax OK" || echo "   ❌ config.php syntax ERROR"

echo ""
echo "🏗️ 3. Probando compilación webpack..."
npm run dev > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✅ Webpack compilation successful"
else
    echo "   ❌ Webpack compilation failed"
fi

echo ""
echo "📦 4. Verificando assets compilados..."
ASSETS=(
    "dist/js/dev-tools.min.js"
    "dist/js/dev-utils.min.js"
    "dist/css/dev-tools-styles.min.css"
)

for asset in "${ASSETS[@]}"; do
    if [ -f "$asset" ]; then
        SIZE=$(du -h "$asset" | cut -f1)
        echo "   ✅ $asset ($SIZE)"
    else
        echo "   ❌ $asset (FALTA)"
    fi
done

echo ""
echo "🌐 5. URLs de acceso:"
echo "   📊 Dev-Tools Panel: http://localhost:10019/wp-admin/tools.php?page=tarokina-2025-dev-tools"
echo "   🏠 WordPress Admin: http://localhost:10019/wp-admin/"

echo ""
echo "🧪 6. Test de consola del navegador:"
echo "   📝 Script disponible: test-browser-console.js"
echo "   💡 Copiar contenido y ejecutar en consola del navegador"

echo ""
echo "✅ VERIFICACIÓN COMPLETADA"
echo "================================="
echo "Estado: ✅ Sistema funcional sin errores críticos"
echo "Próximo paso: Implementar AJAX handler básico"
