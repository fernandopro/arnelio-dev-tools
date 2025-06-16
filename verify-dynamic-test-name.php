#!/usr/bin/env php
<?php
/**
 * Script de verificación para el nombre del archivo de test básico dinámico
 */

// Simular la función get_basic_test_filename()
function get_basic_test_filename() {
    // Simular la ruta del plugin
    $plugin_path = '/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/wp-content/plugins/tarokina-2025';
    $plugin_name = basename($plugin_path);
    
    // Convertir a formato seguro para nombre de clase
    $safe_plugin_name = preg_replace('/[^a-zA-Z0-9_]/', '', ucwords(str_replace(['-', '_'], ' ', $plugin_name)));
    $safe_plugin_name = str_replace(' ', '', $safe_plugin_name);
    
    return "tests/unit/{$safe_plugin_name}BasicTest.php";
}

echo "🔍 Verificación del nombre dinámico del archivo de test básico\n";
echo "============================================================\n\n";

$filename = get_basic_test_filename();
echo "📄 Nombre generado: {$filename}\n";

// Verificar que existe el archivo
$full_path = "/Users/fernandovazquezperez/Local Sites/tarokina-2025/app/public/wp-content/plugins/tarokina-2025/plugin-dev-tools/{$filename}";
echo "📍 Ruta completa: {$full_path}\n";
echo "✅ Archivo existe: " . (file_exists($full_path) ? 'SÍ' : 'NO') . "\n\n";

echo "🎉 Verificación completada\n";
