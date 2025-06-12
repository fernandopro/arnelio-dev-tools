# Dev-Tools Arquitectura 3.0 - Installation Guide

## Quick Installation

### Option 1: Full Automated Installation (Recommended)
```bash
# From the dev-tools directory
chmod +x install.sh
./install.sh
```

### Option 2: NPM Command
```bash
npm run install:full
```

### Option 3: Composer Command
```bash
composer install:full
```

### Option 4: Quick Installation (Manual Steps)
```bash
npm run install:quick
```

## What the Installation Script Does

### ✅ System Requirements Check
- Verifies Node.js ≥16.0.0
- Verifies npm ≥8.0.0
- Verifies PHP ≥7.4
- Installs Composer if missing

### 📦 Dependencies Installation
- **Node.js dependencies**: Webpack, Babel, Bootstrap 5, etc.
- **PHP dependencies**: PHPUnit, WordPress testing framework
- **Development tools**: Testing utilities, code coverage

### 🏗️ Asset Compilation
- Compiles JavaScript modules (ES6+ → ES5)
- Compiles SCSS → Minified CSS
- Creates production-ready dist/ folder
- Optimizes Bootstrap 5 components

### 🧪 Testing Setup
- Configures PHPUnit testing environment
- Creates test directory structure
- Sets up code coverage reporting
- Validates installation integrity

## Manual Installation (Step by Step)

If you prefer to install manually or the automated script fails:

### 1. Install Node.js Dependencies
```bash
cd dev-tools
npm install
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Compile Assets
```bash
npm run build
```

### 4. Setup Testing
```bash
# Copy PHPUnit config if needed
cp phpunit.xml.dist phpunit.xml

# Create test directories
mkdir -p tests/unit tests/integration coverage

# Test PHPUnit
./vendor/bin/phpunit --version
```

## Verification

After installation, verify everything works:

### Check Critical Files
```bash
# JavaScript bundle
ls -la dist/js/dev-tools.min.js

# CSS bundle
ls -la dist/css/dev-tools-styles.min.css

# PHP autoloader
ls -la vendor/autoload.php

# PHPUnit
./vendor/bin/phpunit --version
```

### Test Compilation
```bash
npm run dev      # Development build
npm run build    # Production build
npm run watch    # Watch mode
```

### Test PHPUnit
```bash
composer test           # Run all tests
npm run test:unit      # Unit tests only
npm run test:coverage  # With coverage report
```

## Available Commands

### NPM Scripts
```bash
npm run install:full    # Full automated installation
npm run install:quick   # Quick installation (deps + build)
npm run dev            # Development build
npm run watch          # Watch for changes
npm run build          # Production build
npm run clean          # Clean dist folder
npm run test           # Run PHPUnit tests
npm run test:coverage  # Run tests with coverage
```

### Composer Scripts
```bash
composer install:full   # Full automated installation
composer install:deps  # Install dependencies only
composer test          # Run PHPUnit tests
composer test:unit     # Unit tests only
composer test:coverage # Tests with coverage
```

## Troubleshooting

### Node.js Version Issues
```bash
# Check version
node -v

# If too old, update via Node Version Manager (nvm)
nvm install 18
nvm use 18
```

### Permission Issues
```bash
# Make install script executable
chmod +x install.sh

# Fix npm permissions (if needed)
sudo chown -R $(whoami) ~/.npm
```

### Composer Issues
```bash
# Update Composer
composer self-update

# Clear cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor composer.lock
composer install
```

### Webpack Issues
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

### PHPUnit Issues
```bash
# Check PHPUnit installation
./vendor/bin/phpunit --version

# Regenerate autoloader
composer dump-autoload

# Check PHP version compatibility
php -v
```

## Directory Structure After Installation

```
dev-tools/
├── dist/                    # Compiled assets (created by webpack)
│   ├── css/
│   │   └── dev-tools-styles.min.css
│   ├── js/
│   │   └── dev-tools.min.js
│   └── fonts/
├── vendor/                  # PHP dependencies (created by composer)
│   ├── autoload.php
│   ├── bin/phpunit
│   └── ...
├── node_modules/           # Node.js dependencies (created by npm)
├── coverage/               # Test coverage reports
├── tests/                  # Test files
│   ├── unit/
│   └── integration/
├── install.sh              # Installation script
├── package.json           # NPM configuration
├── composer.json          # Composer configuration
└── phpunit.xml            # PHPUnit configuration
```

## Next Steps

1. **Navigate to WordPress Admin** → Dev-Tools menu
2. **Start development** with `npm run watch`
3. **Run tests** with `composer test`
4. **Check documentation** in `docs/` folder

## Support

If you encounter issues:

1. Check the installation log for error messages
2. Verify system requirements
3. Try manual installation steps
4. Check the `logs/php/error.log` in your Local by WP engine site
