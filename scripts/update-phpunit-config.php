#!/usr/bin/env php
<?php
/**
 * Script para actualizar phpunit-plugin-only.xml a configuración moderna
 * 
 * Este script actualiza archivos phpunit-plugin-only.xml existentes que usen
 * la configuración antigua de PHPUnit (<filter>, <whitelist>) a la nueva
 * configuración moderna (<coverage>).
 * 
 * Uso:
 *   php update-phpunit-config.php
 * 
 * @version 1.0
 */

// Incluir el sistema de override
require_once __DIR__ . '/../includes/Core/FileOverrideSystem.php';

echo "🔧 ACTUALIZADOR DE CONFIGURACIÓN PHPUNIT\n";
echo "========================================\n\n";

try {
    // Inicializar sistema de override
    $override_system = FileOverrideSystem::getInstance();
    $info = $override_system->get_system_info();
    
    echo "📁 Plugin detectado: " . basename($info['plugin_root']) . "\n";
    echo "📂 Directorio override: " . $info['child_dir'] . "\n\n";
    
    // Verificar si existe plugin-dev-tools
    if (!$info['child_exists']) {
        echo "❌ Error: Directorio plugin-dev-tools no existe.\n";
        echo "   Ejecuta primero create-override-structure.php\n";
        exit(1);
    }
    
    // Verificar archivo phpunit-plugin-only.xml
    $phpunit_file = $info['child_dir'] . '/phpunit-plugin-only.xml';
    
    if (!file_exists($phpunit_file)) {
        echo "📝 Archivo phpunit-plugin-only.xml no existe, creando...\n";
        $created = $override_system->create_modern_phpunit_config();
        
        if ($created) {
            echo "✅ Archivo phpunit-plugin-only.xml creado con configuración moderna\n";
        } else {
            echo "❌ Error creando phpunit-plugin-only.xml\n";
            exit(1);
        }
    } else {
        echo "📝 Verificando configuración existente...\n";
        
        $existing_content = file_get_contents($phpunit_file);
        
        // Verificar si usa configuración antigua
        $has_old_filter = strpos($existing_content, '<filter>') !== false;
        $has_old_whitelist = strpos($existing_content, '<whitelist') !== false;
        $has_modern_coverage = strpos($existing_content, '<coverage') !== false;
        $has_old_bootstrap = strpos($existing_content, 'bootstrap="../dev-tools/tests/bootstrap.php"') !== false;
        
        if ($has_old_filter || $has_old_whitelist || $has_old_bootstrap || !$has_modern_coverage) {
            echo "⚠️  Configuración antigua detectada:\n";
            
            if ($has_old_filter) echo "   - Usa <filter> (obsoleto)\n";
            if ($has_old_whitelist) echo "   - Usa <whitelist> (obsoleto)\n";
            if ($has_old_bootstrap) echo "   - Bootstrap path incorrecto\n";
            if (!$has_modern_coverage) echo "   - Falta configuración <coverage> moderna\n";
            
            echo "\n🔄 Actualizando a configuración moderna...\n";
            
            // Backup del archivo original
            $backup_file = $phpunit_file . '.backup-' . date('Y-m-d-H-i-s');
            copy($phpunit_file, $backup_file);
            echo "💾 Backup creado: " . basename($backup_file) . "\n";
            
            // Actualizar con configuración moderna
            $updated = $override_system->create_modern_phpunit_config(true);
            
            if ($updated) {
                echo "✅ Configuración actualizada a formato moderno\n";
                echo "📋 Cambios aplicados:\n";
                echo "   - Bootstrap: tests/bootstrap.php\n";
                echo "   - Logging: <junit> y <testdoxText>\n";
                echo "   - Coverage: <coverage> en lugar de <filter>\n";
                echo "   - Configuración compatible con PHPUnit 9+\n";
            } else {
                echo "❌ Error actualizando configuración\n";
                exit(1);
            }
        } else {
            echo "✅ La configuración ya está actualizada (formato moderno)\n";
        }
    }
    
    echo "\n🧪 VERIFICACIÓN FINAL\n";
    echo "====================\n";
    
    // Verificar que los archivos esenciales existen
    $essential_files = [
        'tests/bootstrap.php',
        'tests/wp-tests-config.php',
        'phpunit-plugin-only.xml'
    ];
    
    $all_good = true;
    foreach ($essential_files as $file) {
        $full_path = $info['child_dir'] . '/' . $file;
        if (file_exists($full_path)) {
            echo "✅ $file - OK\n";
        } else {
            echo "❌ $file - FALTANTE\n";
            $all_good = false;
        }
    }
    
    if ($all_good) {
        echo "\n🎉 ACTUALIZACIÓN COMPLETADA\n";
        echo "============================\n";
        echo "Tu configuración de PHPUnit está actualizada y lista para usar.\n\n";
        echo "📚 Comandos disponibles:\n";
        echo "   # Ejecutar todos los tests del plugin:\n";
        echo "   cd plugin-dev-tools && php ../dev-tools/vendor/phpunit/phpunit/phpunit\n\n";
        echo "   # Ejecutar con opciones específicas:\n";
        echo "   php ../dev-tools/vendor/phpunit/phpunit/phpunit --verbose\n";
        echo "   php ../dev-tools/vendor/phpunit/phpunit/phpunit --testdox\n";
        echo "   php ../dev-tools/vendor/phpunit/phpunit/phpunit --coverage-text\n\n";
        echo "   # Ejecutar test específico:\n";
        echo "   php ../dev-tools/vendor/phpunit/phpunit/phpunit tests/unit/MiTestEspecifico.php\n\n";
    } else {
        echo "\n⚠️  Algunos archivos faltan. Ejecuta create-override-structure.php\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
