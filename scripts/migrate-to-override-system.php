#!/usr/bin/env php
<?php
/**
 * Script para migrar archivos existentes al sistema plugin-dev-tools
 * 
 * Este script:
 * 1. Detecta archivos locales en dev-tools que deben ser movidos
 * 2. Los migra a plugin-dev-tools/ manteniendo la estructura
 * 3. Actualiza referencias y configuraciones
 * 4. Limpia archivos locales del submódulo
 * 
 * Uso:
 *   php scripts/migrate-to-override-system.php
 */

// Incluir el sistema de override
require_once __DIR__ . '/../includes/Core/FileOverrideSystem.php';

echo "🔄 Dev-Tools Override System - Migración Automática\n";
echo "======================================================\n\n";

try {
    $override = FileOverrideSystem::getInstance();
    $info = $override->get_system_info();
    
    echo "📍 Migrando desde: " . $info['parent_dir'] . "\n";
    echo "📍 Migrando hacia: " . $info['child_dir'] . "\n\n";
    
    // Archivos que típicamente necesitan migración
    $migration_candidates = [
        'tests/wp-tests-config.php',
        'tests/phpunit.xml',
        'tests/bootstrap-local.php',
        'config/config-local.php',
        'config/plugin-specific.php',
    ];
    
    // Buscar archivos adicionales que no son del core
    $additional_files = find_non_core_files($info['parent_dir']);
    $migration_candidates = array_merge($migration_candidates, $additional_files);
    
    $migrated_count = 0;
    $skipped_count = 0;
    
    // Crear estructura si no existe
    if (!$info['child_exists']) {
        echo "📦 Creando estructura plugin-dev-tools...\n";
        $override->create_child_structure();
        echo "✅ Estructura creada\n\n";
    }
    
    echo "🔍 Analizando archivos para migración...\n\n";
    
    foreach ($migration_candidates as $relative_path) {
        $source_file = $info['parent_dir'] . '/' . $relative_path;
        $target_file = $info['child_dir'] . '/' . $relative_path;
        
        if (file_exists($source_file)) {
            echo "📄 Migrando: {$relative_path}\n";
            
            // Crear directorio destino si no existe
            $target_dir = dirname($target_file);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            // Leer contenido del archivo fuente
            $content = file_get_contents($source_file);
            
            // Añadir header de migración para archivos PHP
            if (pathinfo($source_file, PATHINFO_EXTENSION) === 'php') {
                $header = "<?php\n";
                $header .= "/**\n";
                $header .= " * ARCHIVO MIGRADO AL SISTEMA OVERRIDE\n";
                $header .= " * \n";
                $header .= " * Este archivo fue migrado desde: dev-tools/{$relative_path}\n";
                $header .= " * Fecha de migración: " . date('Y-m-d H:i:s') . "\n";
                $header .= " * \n";
                $header .= " * Ahora este archivo es específico del plugin y no afectará\n";
                $header .= " * otros plugins que usen el framework dev-tools.\n";
                $header .= " * \n";
                $header .= " * Para revertir a la versión compartida, elimina este archivo.\n";
                $header .= " */\n\n";
                
                // Remover el <?php opening tag del contenido original
                $content = preg_replace('/^<\?php\s*/', '', $content);
                $content = $header . $content;
            }
            
            // Escribir archivo en destino
            if (file_put_contents($target_file, $content)) {
                echo "   ✅ Migrado a: plugin-dev-tools/{$relative_path}\n";
                
                // Crear backup del original antes de eliminarlo
                $backup_file = $source_file . '.backup-' . date('Ymd-His');
                copy($source_file, $backup_file);
                
                // Eliminar del submódulo (solo si está en .gitignore o es local)
                if (is_file_safe_to_remove($source_file, $info['parent_dir'])) {
                    unlink($source_file);
                    echo "   🗑️ Eliminado del submódulo (backup creado)\n";
                } else {
                    echo "   ⚠️ Original mantenido (archivo del core)\n";
                }
                
                $migrated_count++;
            } else {
                echo "   ❌ Error migrando archivo\n";
            }
            
            echo "\n";
        } else {
            $skipped_count++;
        }
    }
    
    // Verificar y migrar tests personalizados
    echo "🧪 Buscando tests personalizados...\n";
    $custom_tests = find_custom_test_files($info['parent_dir'] . '/tests');
    
    foreach ($custom_tests as $test_file) {
        $relative_path = str_replace($info['parent_dir'] . '/', '', $test_file);
        echo "📄 Migrando test personalizado: {$relative_path}\n";
        
        if ($override->create_override($relative_path, true)) {
            echo "   ✅ Test migrado exitosamente\n";
            $migrated_count++;
        } else {
            echo "   ❌ Error migrando test\n";
        }
    }
    
    // Crear configuración de migración
    echo "\n⚙️ Creando configuración post-migración...\n";
    $migration_config = [
        'migration_date' => date('Y-m-d H:i:s'),
        'migrated_files' => $migrated_count,
        'system_version' => '3.0',
        'notes' => [
            'Los archivos migrados están ahora en plugin-dev-tools/',
            'Los backups se encuentran en dev-tools/ con extensión .backup-*',
            'Para revertir, elimina los archivos de plugin-dev-tools/',
        ]
    ];
    
    file_put_contents(
        $info['child_dir'] . '/migration-info.json',
        json_encode($migration_config, JSON_PRETTY_PRINT)
    );
    
    echo "✅ Información de migración guardada\n\n";
    
    // Resumen final
    echo "🎉 MIGRACIÓN COMPLETADA\n";
    echo "=======================\n\n";
    echo "📊 Resumen:\n";
    echo "   - Archivos migrados: {$migrated_count}\n";
    echo "   - Archivos omitidos: {$skipped_count}\n";
    echo "   - Sistema override: ✅ Activo\n";
    echo "   - Estructura plugin-dev-tools: ✅ Lista\n\n";
    
    echo "🚀 Próximos pasos:\n";
    echo "   1. Verificar migración: ls -la plugin-dev-tools/\n";
    echo "   2. Ejecutar tests: dev-tools/vendor/bin/phpunit -c plugin-dev-tools/tests/phpunit.xml\n";
    echo "   3. Revisar configuración: plugin-dev-tools/config/\n";
    echo "   4. Eliminar backups cuando esté seguro: rm dev-tools/*.backup-*\n\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la migración: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Encuentra archivos que no son del core y necesitan migración
 */
function find_non_core_files($parent_dir) {
    $non_core_files = [];
    
    // Archivos que típicamente son locales/personalizados
    $local_patterns = [
        'tests/*-local.php',
        'tests/fixtures/*.json',
        'config/*-local.php',
        'config/*-specific.php',
        'logs/*.log',
        'reports/*',
    ];
    
    foreach ($local_patterns as $pattern) {
        $files = glob($parent_dir . '/' . $pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                $non_core_files[] = str_replace($parent_dir . '/', '', $file);
            }
        }
    }
    
    return array_unique($non_core_files);
}

/**
 * Encuentra archivos de test personalizados (no del framework)
 */
function find_custom_test_files($tests_dir) {
    if (!is_dir($tests_dir)) {
        return [];
    }
    
    $custom_tests = [];
    $core_test_patterns = [
        'DatabaseTest.php',
        'MockStubExampleTest.php',
        'WordPressClassTest.php',
        'WorkingMockStubExampleTest.php',
        'DevToolsTestCase.php',
    ];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tests_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $basename = $file->getBasename();
            
            // Si no es un test del core, es personalizado
            if (!in_array($basename, $core_test_patterns)) {
                $custom_tests[] = $file->getPathname();
            }
        }
    }
    
    return $custom_tests;
}

/**
 * Verifica si un archivo es seguro de eliminar del submódulo
 */
function is_file_safe_to_remove($file_path, $parent_dir) {
    // Solo eliminar si el archivo está en .gitignore o es claramente local
    $gitignore_file = $parent_dir . '/.gitignore';
    if (file_exists($gitignore_file)) {
        $gitignore_content = file_get_contents($gitignore_file);
        $relative_path = str_replace($parent_dir . '/', '', $file_path);
        
        // Verificar si está en .gitignore
        if (strpos($gitignore_content, $relative_path) !== false) {
            return true;
        }
    }
    
    // Patrones de archivos seguros de eliminar
    $safe_patterns = [
        '/-local\.php$/',
        '/-specific\.php$/',
        '/\.log$/',
        '/\.cache$/',
        '/\.tmp$/',
    ];
    
    foreach ($safe_patterns as $pattern) {
        if (preg_match($pattern, $file_path)) {
            return true;
        }
    }
    
    return false;
}

echo "✅ ¡Migración al sistema override completada!\n";
