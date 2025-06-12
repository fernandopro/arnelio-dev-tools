#!/bin/bash

# =============================================================================
# Dev-Tools Arquitectura 3.0 - Installation Script
# =============================================================================
# Automated installation script for complete dev-tools setup
# Installs all dependencies and compiles assets for production use
#
# Usage:
#   chmod +x install.sh
#   ./install.sh
# =============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DEV_TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REQUIRED_NODE_VERSION="16"
REQUIRED_PHP_VERSION="7.4"

# =============================================================================
# Utility Functions
# =============================================================================

print_header() {
    echo -e "${BLUE}"
    echo "=================================================="
    echo "  Dev-Tools Arquitectura 3.0 - Installation"
    echo "=================================================="
    echo -e "${NC}"
}

print_section() {
    echo -e "${YELLOW}📦 $1${NC}"
    echo "----------------------------------------"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# =============================================================================
# Validation Functions
# =============================================================================

check_node_version() {
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install Node.js $REQUIRED_NODE_VERSION or higher."
        return 1
    fi
    
    local node_version=$(node -v | sed 's/v//')
    local major_version=$(echo $node_version | cut -d. -f1)
    
    if [ "$major_version" -lt "$REQUIRED_NODE_VERSION" ]; then
        print_error "Node.js version $node_version is too old. Please install Node.js $REQUIRED_NODE_VERSION or higher."
        return 1
    fi
    
    print_success "Node.js $node_version detected"
    return 0
}

check_npm() {
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed. Please install npm."
        return 1
    fi
    
    local npm_version=$(npm -v)
    print_success "npm $npm_version detected"
    return 0
}

check_php_version() {
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install PHP $REQUIRED_PHP_VERSION or higher."
        return 1
    fi
    
    local php_version=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    print_success "PHP $php_version detected"
    return 0
}

check_composer() {
    if ! command -v composer &> /dev/null; then
        print_warning "Composer is not installed. Installing Composer..."
        
        # Install Composer
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --install-dir=/usr/local/bin --filename=composer
        php -r "unlink('composer-setup.php');"
        
        if ! command -v composer &> /dev/null; then
            print_error "Failed to install Composer automatically. Please install it manually."
            return 1
        fi
    fi
    
    local composer_version=$(composer --version | cut -d " " -f 3)
    print_success "Composer $composer_version detected"
    return 0
}

# =============================================================================
# Installation Functions
# =============================================================================

install_npm_dependencies() {
    print_section "Installing Node.js dependencies"
    
    cd "$DEV_TOOLS_DIR"
    
    # Clean npm cache if needed
    if [ -d "node_modules" ]; then
        print_info "Removing existing node_modules..."
        rm -rf node_modules
    fi
    
    if [ -f "package-lock.json" ]; then
        print_info "Removing package-lock.json..."
        rm -f package-lock.json
    fi
    
    # Install dependencies
    print_info "Installing npm dependencies..."
    if npm install; then
        print_success "Node.js dependencies installed successfully"
    else
        print_error "Failed to install Node.js dependencies"
        return 1
    fi
    
    return 0
}

install_composer_dependencies() {
    print_section "Installing PHP dependencies"
    
    cd "$DEV_TOOLS_DIR"
    
    # Remove vendor directory if exists
    if [ -d "vendor" ]; then
        print_info "Removing existing vendor directory..."
        rm -rf vendor
    fi
    
    # Install dependencies
    print_info "Installing Composer dependencies..."
    if composer install --no-dev --optimize-autoloader; then
        print_success "PHP dependencies installed successfully"
    else
        print_error "Failed to install PHP dependencies"
        return 1
    fi
    
    return 0
}

install_dev_dependencies() {
    print_section "Installing development dependencies"
    
    cd "$DEV_TOOLS_DIR"
    
    # Install Composer dev dependencies
    print_info "Installing PHPUnit and testing dependencies..."
    if composer install --optimize-autoloader; then
        print_success "Development dependencies installed successfully"
    else
        print_error "Failed to install development dependencies"
        return 1
    fi
    
    return 0
}

compile_assets() {
    print_section "Compiling assets"
    
    cd "$DEV_TOOLS_DIR"
    
    # Clean previous builds
    print_info "Cleaning previous builds..."
    npm run clean
    
    # Build for development
    print_info "Building development assets..."
    if npm run dev; then
        print_success "Assets compiled successfully"
    else
        print_error "Failed to compile assets"
        return 1
    fi
    
    return 0
}

setup_phpunit() {
    print_section "Setting up PHPUnit"
    
    cd "$DEV_TOOLS_DIR"
    
    # Check if phpunit.xml.dist exists
    if [ ! -f "phpunit.xml.dist" ]; then
        print_error "phpunit.xml.dist not found"
        return 1
    fi
    
    # Copy phpunit.xml.dist to phpunit.xml if it doesn't exist
    if [ ! -f "phpunit.xml" ]; then
        print_info "Creating phpunit.xml from phpunit.xml.dist..."
        cp phpunit.xml.dist phpunit.xml
    fi
    
    # Create tests directory structure if it doesn't exist
    mkdir -p tests/unit
    mkdir -p tests/integration
    mkdir -p coverage
    
    # Test PHPUnit installation
    print_info "Testing PHPUnit installation..."
    if ./vendor/bin/phpunit --version; then
        print_success "PHPUnit is ready"
    else
        print_error "PHPUnit installation failed"
        return 1
    fi
    
    return 0
}

create_dist_structure() {
    print_section "Creating dist directory structure"
    
    cd "$DEV_TOOLS_DIR"
    
    # Ensure dist directories exist
    mkdir -p dist/css
    mkdir -p dist/js
    mkdir -p dist/fonts
    mkdir -p dist/images
    
    print_success "Dist directory structure created"
    return 0
}

verify_installation() {
    print_section "Verifying installation"
    
    cd "$DEV_TOOLS_DIR"
    
    # Check critical files
    local files_to_check=(
        "dist/js/dev-tools.min.js"
        "dist/css/dev-tools-styles.min.css"
        "vendor/autoload.php"
        "vendor/bin/phpunit"
        "node_modules/.bin/webpack"
    )
    
    local all_files_exist=true
    
    for file in "${files_to_check[@]}"; do
        if [ -f "$file" ]; then
            print_success "$file exists"
        else
            print_error "$file is missing"
            all_files_exist=false
        fi
    done
    
    if $all_files_exist; then
        print_success "All critical files are present"
        return 0
    else
        print_error "Some critical files are missing"
        return 1
    fi
}

show_next_steps() {
    echo -e "${GREEN}"
    echo "=================================================="
    echo "  Installation Complete! 🎉"
    echo "=================================================="
    echo -e "${NC}"
    echo
    echo "Dev-Tools Arquitectura 3.0 is now ready to use!"
    echo
    echo "Next steps:"
    echo "  1. Navigate to your WordPress admin panel"
    echo "  2. Go to Dev-Tools menu"
    echo "  3. Start using the development tools"
    echo
    echo "Available commands:"
    echo "  npm run dev      - Build for development"
    echo "  npm run watch    - Watch for changes"
    echo "  npm run build    - Build for production"
    echo "  composer test    - Run PHPUnit tests"
    echo
    echo "For more information, check the documentation in the docs/ folder."
    echo
}

# =============================================================================
# Main Installation Process
# =============================================================================

main() {
    print_header
    
    print_info "Starting installation in: $DEV_TOOLS_DIR"
    echo
    
    # System requirements check
    print_section "Checking system requirements"
    
    if ! check_node_version; then exit 1; fi
    if ! check_npm; then exit 1; fi
    if ! check_php_version; then exit 1; fi
    if ! check_composer; then exit 1; fi
    
    echo
    
    # Installation steps
    if ! install_npm_dependencies; then exit 1; fi
    echo
    
    if ! install_composer_dependencies; then exit 1; fi
    echo
    
    if ! install_dev_dependencies; then exit 1; fi
    echo
    
    if ! create_dist_structure; then exit 1; fi
    echo
    
    if ! compile_assets; then exit 1; fi
    echo
    
    if ! setup_phpunit; then exit 1; fi
    echo
    
    if ! verify_installation; then exit 1; fi
    echo
    
    show_next_steps
}

# Run main function
main "$@"
