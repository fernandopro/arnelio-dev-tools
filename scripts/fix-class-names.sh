#!/bin/bash

# 🔧 PSR-4 Class Rename Fixer - Dev-Tools Arquitectura 3.0
# Corrige nombres de clases para que coincidan exactamente con PSR-4

echo "🔧 Renombrando clases para compliance PSR-4..."

# Función para renombrar clases en archivos
fix_class_names() {
    local file="$1"
    local expected_namespace="$2"
    local expected_class="$3"
    
    # Obtener nombre de archivo sin extensión
    local filename=$(basename "$file" .php)
    
    # Si el nombre de archivo es diferente a la clase esperada, usamos el filename
    if [[ "$expected_class" == "" ]]; then
        expected_class="$filename"
    fi
    
    echo "  🔧 Procesando: $file"
    echo "     Namespace: $expected_namespace"
    echo "     Clase: $expected_class"
    
    # Crear archivo temporal
    local temp_file=$(mktemp)
    
    # Buscar y reemplazar declaraciones de clase
    sed -E "s/^class [A-Za-z0-9_]+/class $expected_class/" "$file" > "$temp_file"
    
    # Reemplazar archivo original si hay cambios
    if ! cmp -s "$file" "$temp_file"; then
        mv "$temp_file" "$file"
        echo "     ✅ Clase renombrada a: $expected_class"
    else
        rm "$temp_file"
        echo "     ✅ Ya está correcto"
    fi
}

# Directorio base de tests
TESTS_DIR="$(dirname "$0")/../tests"

echo "📁 Procesando tests/unit/"
fix_class_names "$TESTS_DIR/unit/WordPressClassTest.php" "DevTools\\Tests\\Unit" "WordPressClassTest"
fix_class_names "$TESTS_DIR/unit/WordPressAdvancedTestSuite.php" "DevTools\\Tests\\Unit" "WordPressAdvancedTestSuite"
fix_class_names "$TESTS_DIR/unit/MockStubExampleTest.php" "DevTools\\Tests\\Unit" "MockStubExampleTest"
fix_class_names "$TESTS_DIR/unit/WorkingMockStubExampleTest.php" "DevTools\\Tests\\Unit" "WorkingMockStubExampleTest"
fix_class_names "$TESTS_DIR/unit/TarokinaFunctionsTest.php" "DevTools\\Tests\\Unit" "TarokinaFunctionsTest"
fix_class_names "$TESTS_DIR/unit/TarokinaBugFixTest.php" "DevTools\\Tests\\Unit" "TarokinaBugFixTest"
fix_class_names "$TESTS_DIR/unit/WordPressFullyOperationalTest.php" "DevTools\\Tests\\Unit" "WordPressFullyOperationalTest"

echo "📁 Procesando tests/modules/"
fix_class_names "$TESTS_DIR/modules/DashboardModuleTest.php" "DevTools\\Tests\\Modules" "DashboardModuleTest"

echo "📁 Procesando tests/includes/"
fix_class_names "$TESTS_DIR/includes/Helpers.php" "DevTools\\Tests" "DevToolsTestHelpers"
fix_class_names "$TESTS_DIR/includes/TestCase.php" "DevTools\\Tests" "DevToolsTestCase"

echo "✅ Nombres de clase corregidos para PSR-4"
