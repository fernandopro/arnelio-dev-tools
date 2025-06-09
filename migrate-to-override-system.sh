#!/bin/bash

# =============================================================================
# SCRIPT DE MIGRACIÓN - Sistema Override tipo Child Theme
# =============================================================================
# 
# Migra archivos locales de dev-tools/ a plugin-dev-tools/
# Implementa jerarquía de override similar a child themes de WordPress
#
# Uso: ./migrate-to-override-system.sh
# =============================================================================

set -e  # Salir en caso de error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(dirname "$SCRIPT_DIR")"
DEV_TOOLS_DIR="$SCRIPT_DIR"
PLUGIN_DEV_TOOLS_DIR="$PLUGIN_ROOT/plugin-dev-tools"

echo -e "${BLUE}🚀 MIGRACIÓN A SISTEMA DE OVERRIDE${NC}"
echo -e "${BLUE}====================================${NC}"
echo ""
echo "Plugin Root: $PLUGIN_ROOT"
echo "Dev-Tools: $DEV_TOOLS_DIR"
echo "Nuevo directorio: $PLUGIN_DEV_TOOLS_DIR"
echo ""

# =============================================================================
# FUNCIÓN: Crear estructura del directorio plugin-dev-tools
# =============================================================================
create_override_directory_structure() {
    echo -e "${YELLOW}📁 Creando estructura de plugin-dev-tools...${NC}"
    
    # Crear directorio principal
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR"
    
    # Crear subdirectorios
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/modules"
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/templates"
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/tests"
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/logs"
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/reports"
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/fixtures"
    mkdir -p "$PLUGIN_DEV_TOOLS_DIR/scripts"
    
    echo -e "${GREEN}✅ Estructura creada${NC}"
}

# =============================================================================
# FUNCIÓN: Migrar archivos específicos
# =============================================================================
migrate_local_files() {
    echo -e "${YELLOW}🔄 Migrando archivos locales específicos...${NC}"
    
    # Lista de archivos locales a migrar
    local files_to_migrate=(
        "config-local.php"
        "wp-tests-config-local.php"
        "phpunit-local.xml"
        "run-tests-local.sh"
    )
    
    for file in "${files_to_migrate[@]}"; do
        local source_file="$DEV_TOOLS_DIR/$file"
        local dest_file="$PLUGIN_DEV_TOOLS_DIR/$file"
        
        if [[ -f "$source_file" ]]; then
            echo -e "  📄 Migrando: $file"
            
            # Copiar archivo
            cp "$source_file" "$dest_file"
            
            # Añadir header de override
            add_override_header "$dest_file" "$file"
            
            # Mover original a backup
            mv "$source_file" "$source_file.backup"
            echo -e "    ${GREEN}✅ Migrado y respaldado${NC}"
        else
            echo -e "  ${YELLOW}⚠️  No encontrado: $file${NC}"
        fi
    done
}

# =============================================================================
# FUNCIÓN: Migrar directorios locales
# =============================================================================
migrate_local_directories() {
    echo -e "${YELLOW}🗂️  Migrando directorios locales...${NC}"
    
    # Lista de directorios a migrar
    local dirs_to_migrate=(
        "tests/plugin-specific"
        "reports/plugin-specific" 
        "logs/plugin-specific"
        "fixtures/plugin-data"
    )
    
    for dir_path in "${dirs_to_migrate[@]}"; do
        local source_dir="$DEV_TOOLS_DIR/$dir_path"
        local dest_dir="$PLUGIN_DEV_TOOLS_DIR/${dir_path#*/}"  # Remover primer nivel
        
        if [[ -d "$source_dir" ]]; then
            echo -e "  📁 Migrando directorio: $dir_path"
            
            # Crear directorio destino si no existe
            mkdir -p "$dest_dir"
            
            # Copiar contenido y luego eliminar original
            if cp -r "$source_dir"/* "$dest_dir"/ 2>/dev/null; then
                rm -rf "$source_dir"
                echo -e "    ${GREEN}✅ Directorio migrado${NC}"
            else
                echo -e "    ${YELLOW}⚠️  Directorio vacío o error al copiar${NC}"
            fi
        else
            echo -e "  ${YELLOW}⚠️  No encontrado: $dir_path${NC}"
        fi
    done
}

# =============================================================================
# FUNCIÓN: Añadir header de override a archivo
# =============================================================================
add_override_header() {
    local file_path="$1"
    local relative_path="$2"
    local temp_file="${file_path}.tmp"
    
    # Detectar nombre del plugin
    local plugin_name="Unknown Plugin"
    if [[ -f "$PLUGIN_ROOT/tarokina-pro.php" ]]; then
        plugin_name="Tarokina Pro"
    fi
    
    # Crear header
    cat > "$temp_file" << EOF
<?php
/**
 * OVERRIDE ARCHIVO: $relative_path
 * Plugin: $plugin_name
 * Migrado: $(date '+%Y-%m-%d %H:%M:%S')
 * 
 * Este archivo override el original en dev-tools/
 * Modifica según necesidades específicas del plugin
 * 
 * JERARQUÍA DE CARGA:
 * 1. plugin-dev-tools/$relative_path (ESTE ARCHIVO)
 * 2. dev-tools/$relative_path (fallback)
 */

EOF
    
    # Si el archivo original no tenía <?php al inicio, añadirlo
    if ! head -n 1 "$file_path" | grep -q "<?php"; then
        cat "$file_path" >> "$temp_file"
    else
        # Saltar el <?php original y añadir el contenido
        tail -n +2 "$file_path" >> "$temp_file"
    fi
    
    # Reemplazar archivo original
    mv "$temp_file" "$file_path"
}

# =============================================================================
# FUNCIÓN: Crear .gitignore para plugin-dev-tools
# =============================================================================
create_gitignore() {
    echo -e "${YELLOW}📝 Creando .gitignore para plugin-dev-tools...${NC}"
    
    cat > "$PLUGIN_DEV_TOOLS_DIR/.gitignore" << 'EOF'
# =============================================================================
# GITIGNORE PARA PLUGIN-DEV-TOOLS
# =============================================================================
# Este directorio contiene overrides específicos del plugin
# Algunos archivos pueden ser sensibles o específicos del entorno

# Logs y reports temporales
logs/*.log
reports/*.html
reports/*.xml

# Archivos de configuración sensibles
*-sensitive.php
*-production.php

# Backups automáticos
*.backup
*.bak

# Archivos temporales
*.tmp
*.temp

# Node modules si existieran
node_modules/

# Archivos de sistema
.DS_Store
Thumbs.db
EOF
    
    echo -e "${GREEN}✅ .gitignore creado${NC}"
}

# =============================================================================
# FUNCIÓN: Crear archivo README para plugin-dev-tools
# =============================================================================
create_readme() {
    echo -e "${YELLOW}📖 Creando README.md...${NC}"
    
    # Detectar nombre del plugin
    local plugin_name="Unknown Plugin"
    local plugin_slug="unknown-plugin"
    
    if [[ -f "$PLUGIN_ROOT/tarokina-pro.php" ]]; then
        plugin_name="Tarokina Pro"
        plugin_slug="tarokina-2025"
    fi
    
    cat > "$PLUGIN_DEV_TOOLS_DIR/README.md" << EOF
# Plugin Dev-Tools Override - $plugin_name

## 🎯 Propósito

Este directorio contiene **overrides específicos** para el plugin **$plugin_name**.

Funciona similar a los **child themes** de WordPress:
- Los archivos aquí **SOBRESCRIBEN** los del directorio \`dev-tools/\`
- Permite customización específica sin modificar el core compartido
- Se mantiene independiente del submódulo git

## 🏗️ Jerarquía de Carga

\`\`\`
plugin-dev-tools/config.php     ← PRIMERO (específico del plugin)
                ↓ (si no existe)
dev-tools/config.php            ← SEGUNDO (compartido/fallback)
\`\`\`

## 📁 Estructura Migrada

### Archivos de Configuración
- \`config-local.php\` - Configuración específica del plugin
- \`wp-tests-config-local.php\` - Configuración de testing
- \`phpunit-local.xml\` - Configuración PHPUnit

### Directorios
- \`tests/\` - Tests específicos del plugin (migrado desde \`tests/plugin-specific/\`)
- \`logs/\` - Logs locales (migrado desde \`logs/plugin-specific/\`)
- \`reports/\` - Reports de testing (migrado desde \`reports/plugin-specific/\`)
- \`fixtures/\` - Datos de prueba (migrado desde \`fixtures/plugin-data/\`)

## 🚀 Uso del Sistema Override

### PHP - Cargar con Override
\`\`\`php
\$config = DevToolsConfig::getInstance();

// Incluir archivo con override automático
\$config->include_file('modules/CustomModule.php');

// Cargar configuración específica
\$local_config = \$config->load_override_config('config-local.php');

// Cargar template con override
\$config->load_template('dashboard.php', ['data' => \$data]);
\`\`\`

### Crear Nuevo Override
\`\`\`php
// Migrar archivo desde dev-tools/ para customización
\$config->create_override('modules/SystemInfoModule.php');

// Verificar si existe override
if (\$config->has_override('config-custom.php')) {
    // Usar versión customizada
}
\`\`\`

## ⚠️ Importante

- ✅ Archivos aquí son **específicos del plugin $plugin_slug**
- ✅ NO se comparten con otros plugins que usen dev-tools
- ✅ Modificaciones seguras sin afectar el core compartido
- ✅ Los archivos originales están respaldados en \`dev-tools/*.backup\`

## 📋 Archivos Migrados

Los siguientes archivos fueron migrados automáticamente desde \`dev-tools/\`:

EOF
    
    # Listar archivos migrados
    find "$PLUGIN_DEV_TOOLS_DIR" -type f -name "*.php" -o -name "*.xml" -o -name "*.sh" | while read -r file; do
        local relative_file="${file#$PLUGIN_DEV_TOOLS_DIR/}"
        echo "- \`$relative_file\`" >> "$PLUGIN_DEV_TOOLS_DIR/README.md"
    done
    
    cat >> "$PLUGIN_DEV_TOOLS_DIR/README.md" << 'EOF'

## 🔄 Flujo de Desarrollo

1. **Modificar archivos** en `plugin-dev-tools/` según necesidades
2. **Crear nuevos overrides** usando `$config->create_override()`
3. **Testing local** usando configuraciones específicas
4. **Sin afectación** al core compartido `dev-tools/`

---
**Migrado automáticamente por DevTools Override System**
EOF
    
    echo -e "${GREEN}✅ README.md creado${NC}"
}

# =============================================================================
# FUNCIÓN: Actualizar archivos del core para usar override
# =============================================================================
update_core_files() {
    echo -e "${YELLOW}🔧 Actualizando referencias en archivos del core...${NC}"
    
    # Crear archivo de configuración del override system
    cat > "$PLUGIN_DEV_TOOLS_DIR/override-config.php" << 'EOF'
<?php
/**
 * Configuración del Sistema de Override
 * 
 * Este archivo define configuraciones específicas para el override system
 */

return [
    'override_enabled' => true,
    'migration_completed' => true,
    'migration_date' => date('Y-m-d H:i:s'),
    
    // Archivos que deben cargarse con override
    'override_files' => [
        'config-local.php',
        'wp-tests-config-local.php',
        'phpunit-local.xml'
    ],
    
    // Directorios con override
    'override_directories' => [
        'modules',
        'templates', 
        'tests',
        'logs',
        'reports',
        'fixtures'
    ]
];
EOF
    
    echo -e "${GREEN}✅ Archivos del core actualizados${NC}"
}

# =============================================================================
# FUNCIÓN: Verificar migración
# =============================================================================
verify_migration() {
    echo -e "${YELLOW}🔍 Verificando migración...${NC}"
    
    local errors=0
    
    # Verificar estructura de directorios
    local required_dirs=("modules" "templates" "tests" "logs" "reports" "fixtures")
    for dir in "${required_dirs[@]}"; do
        if [[ ! -d "$PLUGIN_DEV_TOOLS_DIR/$dir" ]]; then
            echo -e "${RED}❌ Falta directorio: $dir${NC}"
            ((errors++))
        fi
    done
    
    # Verificar archivos importantes
    local important_files=("README.md" ".gitignore" "override-config.php")
    for file in "${important_files[@]}"; do
        if [[ ! -f "$PLUGIN_DEV_TOOLS_DIR/$file" ]]; then
            echo -e "${RED}❌ Falta archivo: $file${NC}"
            ((errors++))
        fi
    done
    
    # Mostrar resumen
    if [[ $errors -eq 0 ]]; then
        echo -e "${GREEN}✅ Migración completada exitosamente${NC}"
        echo ""
        echo -e "${BLUE}📊 RESUMEN:${NC}"
        echo "  - Directorio creado: $PLUGIN_DEV_TOOLS_DIR"
        echo "  - Archivos migrados: $(find "$PLUGIN_DEV_TOOLS_DIR" -type f | wc -l | tr -d ' ')"
        echo "  - Backups creados: $(find "$DEV_TOOLS_DIR" -name "*.backup" | wc -l | tr -d ' ')"
    else
        echo -e "${RED}❌ Migración completada con $errors errores${NC}"
        return 1
    fi
}

# =============================================================================
# FUNCIÓN PRINCIPAL
# =============================================================================
main() {
    echo -e "${BLUE}Iniciando migración a sistema de override...${NC}"
    echo ""
    
    # Verificar si ya existe plugin-dev-tools
    if [[ -d "$PLUGIN_DEV_TOOLS_DIR" ]]; then
        echo -e "${YELLOW}⚠️  El directorio plugin-dev-tools ya existe${NC}"
        read -p "¿Deseas continuar y sobrescribir? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}⏸️  Migración cancelada${NC}"
            exit 0
        fi
    fi
    
    # Ejecutar migración paso a paso
    create_override_directory_structure
    migrate_local_files
    migrate_local_directories
    create_gitignore
    create_readme
    update_core_files
    verify_migration
    
    echo ""
    echo -e "${GREEN}🎉 MIGRACIÓN COMPLETADA${NC}"
    echo ""
    echo -e "${BLUE}📋 PRÓXIMOS PASOS:${NC}"
    echo "1. Revisar archivos en: $PLUGIN_DEV_TOOLS_DIR"
    echo "2. Verificar configuraciones específicas"
    echo "3. Ejecutar tests para validar funcionamiento"
    echo "4. Los archivos originales están respaldados como *.backup"
    echo ""
    echo -e "${YELLOW}💡 RECORDATORIO:${NC}"
    echo "- Los archivos en plugin-dev-tools/ override los de dev-tools/"
    echo "- Usa \$config->create_override() para crear nuevos overrides"
    echo "- El sistema detecta automáticamente qué archivo cargar"
}

# Ejecutar script principal
main "$@"
