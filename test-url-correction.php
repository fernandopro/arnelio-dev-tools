<?php
/**
 * Test de URLs Generadas - Para verificar corrección
 */

// Simular las funciones de WordPress necesarias para el test
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        $file = str_replace('\\', '/', $file);
        $file = preg_replace('|/+|', '/', $file);
        
        // Para Local by Flywheel: buscar la ruta del plugin
        if (strpos($file, 'wp-content/plugins/') !== false) {
            $plugin_dir = substr($file, strpos($file, 'wp-content/plugins/'));
            $plugin_dir = dirname($plugin_dir);
            return 'http://localhost:10019/' . $plugin_dir . '/';
        }
        
        return 'http://localhost:10019/wp-content/plugins/tarokina-2025/dev-tools/';
    }
}

echo "=== TEST URL CORRECTION ===\n\n";

$plugin_url = plugin_dir_url(__FILE__);

echo "Plugin URL: " . $plugin_url . "\n";
echo "CSS URL: " . $plugin_url . 'dist/css/dev-tools-styles.min.css' . "\n";
echo "Main JS URL: " . $plugin_url . 'dist/js/dev-tools.min.js' . "\n";
echo "Dashboard JS URL: " . $plugin_url . 'dist/js/dashboard.min.js' . "\n";

echo "\n=== EXPECTED URLS ===\n";
echo "Should be: http://localhost:10019/wp-content/plugins/tarokina-2025/dev-tools/dist/js/dashboard.min.js\n";

echo "\n=== FIN TEST ===\n";
