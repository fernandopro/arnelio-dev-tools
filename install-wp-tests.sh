#!/usr/bin/env bash
# =============================================================================
# WordPress Test Suite Installer - Dev-Tools Arquitectura 3.0
# =============================================================================
# 
# Script oficial para instalar WordPress Test Suite siguiendo las mejores
# prácticas de WordPress Core Development.
# @see https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
# 
# Uso:
#   bash install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
#
# Ejemplos para Local by WP Engine:
#   bash install-wp-tests.sh local root root localhost latest
#   bash install-wp-tests.sh local root root localhost:3306 latest true
#
# @package DevTools
# @since 3.0.0

if [ $# -lt 3 ]; then
    echo "Uso: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
    echo ""
    echo "Ejemplos:"
    echo "  $0 local root root localhost latest"
    echo "  $0 wordpress_test root root localhost:3306 latest true"
    echo ""
    echo "Parámetros:"
    echo "  db-name: Nombre de la base de datos para tests"
    echo "  db-user: Usuario de la base de datos"
    echo "  db-pass: Contraseña de la base de datos"
    echo "  db-host: Host de la base de datos (default: localhost)"
    echo "  wp-version: Versión de WordPress (default: latest)"
    echo "  skip-database-creation: Saltar creación de DB (default: false)"
    exit 1
fi

# Configuración de parámetros
DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

# Directorio base
BASEDIR=$(dirname "$0")
TMPDIR="$BASEDIR/tmp"
WP_TESTS_DIR="$BASEDIR/wordpress-tests-lib"
WP_CORE_DIR="$BASEDIR/wordpress"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funciones de utilidad
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Función para descargar archivos
download() {
    if [ $(which curl) ]; then
        curl -s "$1" > "$2";
    elif [ $(which wget) ]; then
        wget -nv -O "$2" "$1"
    else
        print_error "No se encontró curl o wget. Por favor instala uno de estos programas."
        exit 1
    fi
}

# Función para obtener la versión de WordPress
get_wp_version() {
    case $WP_VERSION in
        latest|master)
            echo "latest"
            ;;
        trunk)
            echo "trunk"
            ;;
        *)
            # Verificar si es una versión válida
            if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
                echo $WP_VERSION
            else
                print_warning "Versión de WordPress inválida: $WP_VERSION. Usando 'latest'."
                echo "latest"
            fi
            ;;
    esac
}

# Función para instalar WordPress Core
install_wp() {
    local VERSION=$(get_wp_version)
    
    if [ -d $WP_CORE_DIR ]; then
        print_warning "WordPress ya existe en $WP_CORE_DIR"
        return
    fi

    print_status "Descargando WordPress versión $VERSION..."
    
    mkdir -p $WP_CORE_DIR

    if [ $VERSION == 'latest' ]; then
        local ARCHIVE_NAME='latest'
    elif [ $VERSION == 'trunk' ]; then
        local ARCHIVE_NAME='trunk'
    else
        local ARCHIVE_NAME="wordpress-$VERSION"
    fi

    download https://wordpress.org/${ARCHIVE_NAME}.tar.gz $TMPDIR/wordpress.tar.gz
    tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR

    print_success "WordPress $VERSION instalado en $WP_CORE_DIR"
}

# Función para instalar WordPress Test Suite
install_test_suite() {
    local VERSION=$(get_wp_version)
    
    # Crear directorio temporal
    mkdir -p $TMPDIR

    if [ -d $WP_TESTS_DIR ]; then
        print_warning "WordPress Test Suite ya existe en $WP_TESTS_DIR"
        return
    fi

    print_status "Descargando WordPress Test Suite..."

    # Crear directorio para test suite
    mkdir -p $WP_TESTS_DIR

    if [ $VERSION == 'latest' ] || [ $VERSION == 'trunk' ]; then
        download https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ $TMPDIR/includes.tar.gz
        download https://develop.svn.wordpress.org/trunk/tests/phpunit/data/ $TMPDIR/data.tar.gz
    else
        download https://develop.svn.wordpress.org/tags/$VERSION/tests/phpunit/includes/ $TMPDIR/includes.tar.gz
        download https://develop.svn.wordpress.org/tags/$VERSION/tests/phpunit/data/ $TMPDIR/data.tar.gz
    fi

    # Descargar archivos individuales necesarios
    download https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/functions.php $WP_TESTS_DIR/includes/functions.php
    download https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/install.php $WP_TESTS_DIR/includes/install.php
    download https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/bootstrap.php $WP_TESTS_DIR/includes/bootstrap.php
    download https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/testcase.php $WP_TESTS_DIR/includes/testcase.php
    download https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/factory.php $WP_TESTS_DIR/includes/factory.php
    download https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/tests/phpunit/includes/testcase-ajax.php $WP_TESTS_DIR/includes/testcase-ajax.php

    # Crear directorio de data si no existe
    mkdir -p $WP_TESTS_DIR/data
    
    print_success "WordPress Test Suite instalado en $WP_TESTS_DIR"
}

# Función para instalar la base de datos
install_db() {
    if [ ${SKIP_DB_CREATE} = "true" ]; then
        print_warning "Saltando creación de base de datos (skip-database-creation=true)"
        return 0
    fi

    print_status "Configurando base de datos de tests..."

    # Parsear host y puerto
    PARTS=(${DB_HOST//\:/ })
    DB_HOSTNAME=${PARTS[0]}
    DB_PORT=${PARTS[1]}

    # Configurar comando mysql
    EXTRA=""
    if ! [ -z $DB_HOSTNAME ] ; then
        if [ $(echo $DB_HOSTNAME | grep -c "^/") -eq 1 ]; then
            EXTRA=" --socket=$DB_HOSTNAME"
        elif [ $(echo $DB_HOSTNAME | grep -c "\.sock$") -eq 1 ]; then
            EXTRA=" --socket=$DB_HOSTNAME"
        elif [ $(echo $DB_HOSTNAME | grep -c "localhost") -eq 0 ]; then
            EXTRA=" --host=$DB_HOSTNAME --protocol=TCP"
        fi
    fi

    if ! [ -z $DB_PORT ] ; then
        EXTRA="$EXTRA --port=$DB_PORT"
    fi

    # Crear base de datos
    print_status "Creando base de datos '$DB_NAME'..."
    mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA 2>/dev/null

    if [ $? -eq 0 ]; then
        print_success "Base de datos '$DB_NAME' creada exitosamente"
    else
        print_warning "Base de datos '$DB_NAME' ya existe o error en creación"
    fi
}

# Función para generar configuración wp-tests-config.php
generate_config() {
    print_status "Generando configuración de tests..."

    # No sobrescribir si ya existe
    if [ -f "$BASEDIR/wp-tests-config.php" ]; then
        print_warning "wp-tests-config.php ya existe. No se sobrescribirá."
        return
    fi

    # Crear configuración básica
    cat > "$BASEDIR/wp-tests-config.php" << EOF
<?php
/**
 * Configuración generada automáticamente para WordPress Tests
 * Generado por: install-wp-tests.sh
 * Fecha: $(date)
 */

// Configuración de base de datos para tests
define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASSWORD', '$DB_PASS' );
define( 'DB_HOST', '$DB_HOST' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Prefijo de tablas para tests
\$table_prefix = 'wptests_';

// Configuración de WordPress
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_DEBUG', true );

// Configuración de paths
define( 'ABSPATH', dirname( __FILE__ ) . '/wordpress/' );

// Keys y salts para testing
define( 'AUTH_KEY',         'testing-key' );
define( 'SECURE_AUTH_KEY',  'testing-key' );
define( 'LOGGED_IN_KEY',    'testing-key' );
define( 'NONCE_KEY',        'testing-key' );
define( 'AUTH_SALT',        'testing-salt' );
define( 'SECURE_AUTH_SALT', 'testing-salt' );
define( 'LOGGED_IN_SALT',   'testing-salt' );
define( 'NONCE_SALT',       'testing-salt' );
EOF

    print_success "Configuración creada en wp-tests-config.php"
}

# Función principal de instalación
main() {
    print_status "=== Instalador WordPress Test Suite - Dev-Tools 3.0 ==="
    print_status "Base de datos: $DB_NAME"
    print_status "Usuario: $DB_USER"
    print_status "Host: $DB_HOST"
    print_status "Versión WP: $WP_VERSION"
    print_status "=================================================="

    # Crear directorio temporal
    mkdir -p $TMPDIR

    # Instalar componentes
    install_wp
    install_test_suite
    install_db
    generate_config

    # Limpiar archivos temporales
    rm -rf $TMPDIR

    print_success "=== Instalación completada ==="
    print_status "Para ejecutar tests: composer test"
    print_status "o directamente: ./vendor/bin/phpunit"
}

# Verificar dependencias
if ! command -v mysql &> /dev/null && ! command -v mysqladmin &> /dev/null; then
    print_error "MySQL no está instalado o no está en el PATH"
    exit 1
fi

# Ejecutar instalación
main