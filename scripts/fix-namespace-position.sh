#!/bin/bash

# 🔧 Namespace Position Fixer - Dev-Tools Arquitectura 3.0
# Corrige la posición de namespaces para que estén inmediatamente después de <?php

echo "🔧 Corrigiendo posición de namespaces en archivos de test..."

# Función para corregir posición de namespace
fix_namespace_position() {
    local file="$1"
    local expected_namespace="$2"
    
    echo "  🔧 Procesando: $file"
    
    # Crear archivo temporal
    local temp_file=$(mktemp)
    
    # Variables de estado
    local found_php_tag=false
    local found_namespace=false
    local namespace_line=""
    
    # Primera pasada: extraer el namespace si existe
    while IFS= read -r line; do
        if [[ $line =~ ^namespace[[:space:]] ]]; then
            namespace_line="$line"
            found_namespace=true
            break
        fi
    done < "$file"
    
    # Si no encontramos namespace, no hacer nada
    if [[ $found_namespace == false ]]; then
        echo "     ⚠️  No namespace found, skipping"
        rm "$temp_file"
        return
    fi
    
    # Segunda pasada: reconstruir archivo con namespace en posición correcta
    {
        local after_php=false
        local namespace_inserted=false
        
        while IFS= read -r line; do
            # Imprimir línea <?php
            if [[ $line =~ ^\<\?php ]]; then
                echo "$line"
                after_php=true
            # Saltar la línea namespace original (la moveremos)
            elif [[ $line =~ ^namespace[[:space:]] ]]; then
                continue
            # Después de <?php y antes de cualquier otra cosa, insertar namespace
            elif [[ $after_php == true && $namespace_inserted == false && $line =~ ^[[:space:]]*$ ]]; then
                # Línea vacía después de <?php - lugar perfecto para namespace
                echo "$namespace_line"
                echo ""
                namespace_inserted=true
                echo "$line"
            elif [[ $after_php == true && $namespace_inserted == false && ! $line =~ ^[[:space:]]*\* && ! $line =~ ^[[:space:]]*\/\* ]]; then
                # No es comentario - insertar namespace antes
                echo "$namespace_line"
                echo ""
                namespace_inserted=true
                echo "$line"
            else
                # Línea normal
                echo "$line"
            fi
        done < "$file"
        
        # Si no insertamos el namespace, agregarlo al final del header
        if [[ $namespace_inserted == false ]]; then
            echo "$namespace_line"
            echo ""
        fi
        
    } > "$temp_file"
    
    # Reemplazar archivo original
    mv "$temp_file" "$file"
    echo "     ✅ Namespace reposicionado"
}

# Corregir archivos específicos con errores
echo "📁 Corrigiendo archivos con errores de namespace..."

fix_namespace_position "tests/database/DatabaseCrudTest.php" "DevTools\\Tests\\Database"
fix_namespace_position "tests/unit/WordPressClassTest.php" "DevTools\\Tests\\Unit"
fix_namespace_position "tests/unit/WordPressAdvancedTestSuite.php" "DevTools\\Tests\\Unit"
fix_namespace_position "tests/unit/MockStubExampleTest.php" "DevTools\\Tests\\Unit"
fix_namespace_position "tests/unit/WorkingMockStubExampleTest.php" "DevTools\\Tests\\Unit"
fix_namespace_position "tests/unit/TarokinaFunctionsTest.php" "DevTools\\Tests\\Unit"
fix_namespace_position "tests/unit/WordPressFullyOperationalTest.php" "DevTools\\Tests\\Unit"
fix_namespace_position "tests/integration/PerformanceTest.php" "DevTools\\Tests\\Integration"
fix_namespace_position "tests/integration/AjaxIntegrationTest.php" "DevTools\\Tests\\Integration"
fix_namespace_position "tests/integration/SimplePerformanceTest.php" "DevTools\\Tests\\Integration"
fix_namespace_position "tests/modules/SiteUrlDetectionModuleTest.php" "DevTools\\Tests\\Modules"
fix_namespace_position "tests/modules/DatabaseConnectionModuleTest.php" "DevTools\\Tests\\Modules"
fix_namespace_position "tests/modules/DashboardModuleTest.php" "DevTools\\Tests\\Modules"

echo "✅ Posiciones de namespace corregidas"
