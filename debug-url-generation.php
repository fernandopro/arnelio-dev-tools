<?php
/**
 * Debug URL Generation - Verificar construcción de URLs
 */

// Cargar WordPress
require_once '../../../wp-config.php';

// Cargar sistema de configuración
require_once __DIR__ . '/config.php';
$config = new DevToolsConfig();

echo "=== DEBUG URL GENERATION ===\n\n";

echo "1. __FILE__: " . __FILE__ . "\n";
echo "2. __DIR__: " . __DIR__ . "\n";
echo "3. plugins_url('', __FILE__): " . plugins_url('', __FILE__) . "\n";
echo "4. plugins_url('', __FILE__) . '/': " . plugins_url('', __FILE__) . '/' . "\n";
echo "5. plugins_url() base: " . plugins_url() . "\n";
echo "6. plugin_dir_url(__FILE__): " . plugin_dir_url(__FILE__) . "\n";

// Probar diferentes métodos de construcción de URL
echo "\n=== MÉTODOS ALTERNATIVOS ===\n";

// Método 1: Usando plugin_dir_url()
$url_method1 = plugin_dir_url(__FILE__);
echo "Método 1 (plugin_dir_url): " . $url_method1 . "\n";
echo "CSS URL Método 1: " . $url_method1 . 'dist/css/dev-tools-styles.min.css' . "\n";
echo "JS URL Método 1: " . $url_method1 . 'dist/js/dashboard.min.js' . "\n";

// Método 2: Construcción manual desde directorio del plugin padre
$plugin_dir = dirname(__DIR__);
$plugin_basename = plugin_basename($plugin_dir);
$url_method2 = plugins_url('', $plugin_dir . '/dummy.php') . '/dev-tools/';
echo "\nMétodo 2 (construcción manual): " . $url_method2 . "\n";
echo "CSS URL Método 2: " . $url_method2 . 'dist/css/dev-tools-styles.min.css' . "\n";
echo "JS URL Método 2: " . $url_method2 . 'dist/js/dashboard.min.js' . "\n";

// Método 3: Usando configuración dinámica
$site_url = get_site_url();
$host_plugin_slug = $config->get('host.slug');
$url_method3 = $site_url . '/wp-content/plugins/' . $host_plugin_slug . '/dev-tools/';
echo "\nMétodo 3 (configuración dinámica): " . $url_method3 . "\n";
echo "CSS URL Método 3: " . $url_method3 . 'dist/css/dev-tools-styles.min.css' . "\n";
echo "JS URL Método 3: " . $url_method3 . 'dist/js/dashboard.min.js' . "\n";

echo "\n=== FIN DEBUG ===\n";
