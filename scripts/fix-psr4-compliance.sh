#!/bin/bash

# 🔧 PSR-4 Compliance Fixer - Dev-Tools Arquitectura 3.0
# Corrige automáticamente las clases de test para cumplir con PSR-4

echo "🔧 Corrigiendo compliance PSR-4 en archivos de test..."

# Función para agregar namespace a archivos PHP
fix_psr4_compliance() {
    local file="$1"
    local namespace="$2"
    
    # Verificar si ya tiene namespace
    if grep -q "^namespace " "$file"; then
        echo "  ✅ $file ya tiene namespace"
        return
    fi
    
    # Crear archivo temporal
    local temp_file=$(mktemp)
    
    # Procesar archivo línea por línea
    {
        local in_header=true
        local namespace_added=false
        
        while IFS= read -r line; do
            echo "$line"
            
            # Agregar namespace después del header de comentarios pero antes de requires/class
            if [[ $in_header == true && ($line =~ ^(require|class|interface|trait) || $line =~ ^\s*$ && $namespace_added == false) ]]; then
                if [[ $line =~ ^(require|class|interface|trait) ]]; then
                    echo ""
                    echo "namespace $namespace;"
                    echo ""
                    namespace_added=true
                    in_header=false
                fi
            fi
            
            # Detectar final del header de comentarios
            if [[ $line =~ ^\s*\*/ ]]; then
                in_header=false
            fi
            
        done < "$file"
    } > "$temp_file"
    
    # Reemplazar archivo original
    mv "$temp_file" "$file"
    echo "  🔧 Corregido: $file"
}

# Directorio base de tests
TESTS_DIR="$(dirname "$0")/../tests"

# Corregir archivos en tests/database/
echo "📁 Procesando tests/database/"
find "$TESTS_DIR/database" -name "*.php" -type f | while read -r file; do
    fix_psr4_compliance "$file" "DevTools\\Tests\\Database"
done

# Corregir archivos en tests/unit/
echo "📁 Procesando tests/unit/"
find "$TESTS_DIR/unit" -name "*.php" -type f | while read -r file; do
    fix_psr4_compliance "$file" "DevTools\\Tests\\Unit"
done

# Corregir archivos en tests/integration/
echo "📁 Procesando tests/integration/"
find "$TESTS_DIR/integration" -name "*.php" -type f | while read -r file; do
    fix_psr4_compliance "$file" "DevTools\\Tests\\Integration"
done

# Corregir archivos en tests/modules/
echo "📁 Procesando tests/modules/"
find "$TESTS_DIR/modules" -name "*.php" -type f | while read -r file; do
    fix_psr4_compliance "$file" "DevTools\\Tests\\Modules"
done

echo "✅ PSR-4 compliance corregido para todos los archivos de test"
