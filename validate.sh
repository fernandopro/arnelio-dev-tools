#!/bin/bash

# =============================================================================
# Dev-Tools Arquitectura 3.0 - Installation Validator
# =============================================================================
# Validates that dev-tools installation was successful and all components work
#
# Usage:
#   chmod +x validate.sh
#   ./validate.sh
# =============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DEV_TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# =============================================================================
# Utility Functions
# =============================================================================

print_header() {
    echo -e "${BLUE}"
    echo "=================================================="
    echo "  Dev-Tools Arquitectura 3.0 - Validation"
    echo "=================================================="
    echo -e "${NC}"
}

print_section() {
    echo -e "${YELLOW}🔍 $1${NC}"
    echo "----------------------------------------"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# =============================================================================
# Validation Functions
# =============================================================================

validate_file_exists() {
    local file_path="$1"
    local description="$2"
    
    if [ -f "$file_path" ]; then
        local size=$(ls -lh "$file_path" | awk '{print $5}')
        print_success "$description exists ($size)"
        return 0
    else
        print_error "$description is missing: $file_path"
        return 1
    fi
}

validate_directory_exists() {
    local dir_path="$1"
    local description="$2"
    
    if [ -d "$dir_path" ]; then
        local count=$(find "$dir_path" -type f | wc -l | tr -d ' ')
        print_success "$description exists ($count files)"
        return 0
    else
        print_error "$description is missing: $dir_path"
        return 1
    fi
}

validate_command_exists() {
    local command="$1"
    local description="$2"
    
    if [ -x "$command" ]; then
        print_success "$description is executable"
        return 0
    else
        print_error "$description is not executable: $command"
        return 1
    fi
}

validate_npm_build() {
    print_section "Testing NPM build process"
    
    cd "$DEV_TOOLS_DIR"
    
    # Test development build
    print_info "Testing development build..."
    if npm run dev &> /dev/null; then
        print_success "Development build works"
    else
        print_error "Development build failed"
        return 1
    fi
    
    # Test production build
    print_info "Testing production build..."
    if npm run build &> /dev/null; then
        print_success "Production build works"
    else
        print_error "Production build failed"
        return 1
    fi
    
    return 0
}

validate_phpunit() {
    print_section "Testing PHPUnit"
    
    cd "$DEV_TOOLS_DIR"
    
    # Test PHPUnit version
    print_info "Testing PHPUnit version..."
    local phpunit_version=$(./vendor/bin/phpunit --version 2>/dev/null)
    if [ $? -eq 0 ]; then
        print_success "PHPUnit: $phpunit_version"
    else
        print_error "PHPUnit version check failed"
        return 1
    fi
    
    # Test autoloader
    print_info "Testing Composer autoloader..."
    if php -r "require_once 'vendor/autoload.php'; echo 'Autoloader works\n';" 2>/dev/null; then
        print_success "Composer autoloader works"
    else
        print_error "Composer autoloader failed"
        return 1
    fi
    
    return 0
}

validate_webpack_config() {
    print_section "Validating Webpack configuration"
    
    cd "$DEV_TOOLS_DIR"
    
    # Check webpack config syntax
    print_info "Checking webpack.config.js syntax..."
    if node -c webpack.config.js 2>/dev/null; then
        print_success "webpack.config.js syntax is valid"
    else
        print_error "webpack.config.js has syntax errors"
        return 1
    fi
    
    return 0
}

validate_asset_quality() {
    print_section "Validating asset quality"
    
    cd "$DEV_TOOLS_DIR"
    
    # Check JavaScript minification
    local js_file="dist/js/dev-tools.min.js"
    if [ -f "$js_file" ]; then
        local js_size=$(stat -f%z "$js_file" 2>/dev/null || stat -c%s "$js_file" 2>/dev/null)
        if [ "$js_size" -gt 1000 ]; then
            print_success "JavaScript bundle size: ${js_size} bytes"
        else
            print_error "JavaScript bundle seems too small: ${js_size} bytes"
            return 1
        fi
    fi
    
    # Check CSS minification
    local css_file="dist/css/dev-tools-styles.min.css"
    if [ -f "$css_file" ]; then
        local css_size=$(stat -f%z "$css_file" 2>/dev/null || stat -c%s "$css_file" 2>/dev/null)
        if [ "$css_size" -gt 1000 ]; then
            print_success "CSS bundle size: ${css_size} bytes"
        else
            print_error "CSS bundle seems too small: ${css_size} bytes"
            return 1
        fi
    fi
    
    return 0
}

generate_validation_report() {
    print_section "Generating validation report"
    
    cd "$DEV_TOOLS_DIR"
    
    local report_file="validation-report.txt"
    
    cat > "$report_file" << EOF
Dev-Tools Arquitectura 3.0 - Validation Report
==============================================
Generated: $(date)
Directory: $DEV_TOOLS_DIR

System Information:
- Node.js: $(node -v)
- npm: $(npm -v)
- PHP: $(php -v | head -n 1)
- Composer: $(composer --version | head -n 1)

File Sizes:
- JavaScript: $(ls -lh dist/js/dev-tools.min.js 2>/dev/null | awk '{print $5}' || echo "Not found")
- CSS: $(ls -lh dist/css/dev-tools-styles.min.css 2>/dev/null | awk '{print $5}' || echo "Not found")

Dependencies:
- node_modules: $(find node_modules -type f 2>/dev/null | wc -l | tr -d ' ') files
- vendor: $(find vendor -type f 2>/dev/null | wc -l | tr -d ' ') files

PHPUnit:
$(./vendor/bin/phpunit --version 2>/dev/null || echo "PHPUnit not available")

Validation completed successfully!
EOF

    print_success "Validation report generated: $report_file"
    return 0
}

# =============================================================================
# Main Validation Process
# =============================================================================

main() {
    print_header
    
    print_info "Validating installation in: $DEV_TOOLS_DIR"
    echo
    
    local validation_passed=true
    
    # File structure validation
    print_section "Validating file structure"
    
    # Critical files
    if ! validate_file_exists "package.json" "package.json"; then validation_passed=false; fi
    if ! validate_file_exists "composer.json" "composer.json"; then validation_passed=false; fi
    if ! validate_file_exists "webpack.config.js" "webpack.config.js"; then validation_passed=false; fi
    if ! validate_file_exists "phpunit.xml.dist" "phpunit.xml.dist"; then validation_passed=false; fi
    
    # Generated files
    if ! validate_file_exists "dist/js/dev-tools.min.js" "JavaScript bundle"; then validation_passed=false; fi
    if ! validate_file_exists "dist/css/dev-tools-styles.min.css" "CSS bundle"; then validation_passed=false; fi
    if ! validate_file_exists "vendor/autoload.php" "Composer autoloader"; then validation_passed=false; fi
    
    # Directories
    if ! validate_directory_exists "node_modules" "Node.js modules"; then validation_passed=false; fi
    if ! validate_directory_exists "vendor" "PHP vendor"; then validation_passed=false; fi
    if ! validate_directory_exists "dist" "Built assets"; then validation_passed=false; fi
    
    # Executables
    if ! validate_command_exists "vendor/bin/phpunit" "PHPUnit"; then validation_passed=false; fi
    
    echo
    
    # Functional validation
    if ! validate_webpack_config; then validation_passed=false; fi
    echo
    
    if ! validate_npm_build; then validation_passed=false; fi
    echo
    
    if ! validate_phpunit; then validation_passed=false; fi
    echo
    
    if ! validate_asset_quality; then validation_passed=false; fi
    echo
    
    # Generate report
    generate_validation_report
    echo
    
    # Final result
    if $validation_passed; then
        echo -e "${GREEN}"
        echo "=================================================="
        echo "  ✅ VALIDATION SUCCESSFUL!"
        echo "=================================================="
        echo -e "${NC}"
        echo
        echo "Dev-Tools Arquitectura 3.0 is properly installed and ready to use!"
        echo
        echo "You can now:"
        echo "  • Use 'npm run watch' for development"
        echo "  • Run 'composer test' for testing"
        echo "  • Access the WordPress admin Dev-Tools menu"
        echo
    else
        echo -e "${RED}"
        echo "=================================================="
        echo "  ❌ VALIDATION FAILED!"
        echo "=================================================="
        echo -e "${NC}"
        echo
        echo "Some components are not working properly."
        echo "Please check the errors above and:"
        echo "  • Re-run the installation script: ./install.sh"
        echo "  • Check system requirements"
        echo "  • Review the validation report: validation-report.txt"
        echo
        exit 1
    fi
}

# Run main function
main "$@"
