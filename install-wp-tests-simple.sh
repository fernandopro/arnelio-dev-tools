#!/usr/bin/env bash
# Instalador simplificado de WordPress Test Suite para Local by WP Engine
# Descarga WordPress Test Suite sin intentar crear la base de datos

echo "🔧 Instalando WordPress Test Suite para Local by WP Engine..."

# Directorio de instalación
WP_TESTS_DIR="/tmp/wordpress-tests-lib"
WP_VERSION=${1:-latest}

# Limpiar instalación previa
if [ -d "$WP_TESTS_DIR" ]; then
    echo "Limpiando instalación previa..."
    rm -rf "$WP_TESTS_DIR"
fi

# Crear directorio
mkdir -p "$WP_TESTS_DIR"

echo "📥 Descargando WordPress Test Suite..."

# Descargar WordPress Test Suite
if [ "$WP_VERSION" = 'latest' ]; then
    WP_TESTS_TAG=$(curl -s https://api.github.com/repos/WordPress/WordPress/releases/latest | grep '"tag_name"' | sed -E 's/.*"([^"]+)".*/\1/')
    if [ -z "$WP_TESTS_TAG" ]; then
        WP_TESTS_TAG="trunk"
    fi
else
    WP_TESTS_TAG="$WP_VERSION"
fi

echo "Descargando WordPress Test Suite versión: $WP_TESTS_TAG"

# Descargar desde WordPress SVN
if command -v svn >/dev/null 2>&1; then
    echo "Usando SVN para descargar..."
    svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ "$WP_TESTS_DIR/includes/"
    svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ "$WP_TESTS_DIR/data/"
else
    echo "SVN no disponible, usando curl..."
    # Descargar archivos esenciales usando curl
    mkdir -p "$WP_TESTS_DIR/includes"
    mkdir -p "$WP_TESTS_DIR/data"
    
    # Archivos esenciales de WordPress Test Suite
    essential_files=(
        "includes/functions.php"
        "includes/install.php"
        "includes/listener.php"
        "includes/bootstrap.php"
        "includes/testcase.php"
        "includes/testcase-rest-api.php"
        "includes/testcase-rest-controller.php"
        "includes/testcase-rest-post-type-controller.php"
        "includes/testcase-xmlrpc.php"
        "includes/exceptions.php"
        "includes/utils.php"
        "includes/spy-rest-server.php"
        "includes/class-wp-rest-test-search-handler.php"
    )
    
    for file in "${essential_files[@]}"; do
        echo "Descargando $file..."
        curl -s "https://develop.svn.wordpress.org/trunk/tests/phpunit/$file" -o "$WP_TESTS_DIR/$file" || echo "⚠️ No se pudo descargar $file"
    done
fi

# Verificar instalación
if [ -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo "✅ WordPress Test Suite instalado correctamente en $WP_TESTS_DIR"
    echo "📁 Archivos instalados:"
    ls -la "$WP_TESTS_DIR/includes/" | head -5
    echo ""
    echo "🎯 Nota: La base de datos debe configurarse manualmente para Local by WP Engine"
    echo "   PHPUnit usará la misma BD que WordPress con prefijo 'test_'"
else
    echo "❌ Error: No se pudo instalar WordPress Test Suite"
    exit 1
fi
