<?php
/**
 * Test rápido para obtener la URL del admin usando dev_tools_get_admin_url()
 */

echo "🔧 Iniciando test de URLs dinámicas...\n";

// Cargar WordPress
$wp_load_path = dirname(__DIR__, 4) . '/wp-load.php';
echo "Buscando WordPress en: " . $wp_load_path . "\n";

if (file_exists($wp_load_path)) {
    echo "✅ WordPress encontrado, cargando...\n";
    require_once $wp_load_path;
} else {
    die("❌ WordPress no encontrado en: " . $wp_load_path . "\n");
}

// Cargar dev-tools
require_once __DIR__ . '/loader.php';

echo "🔧 Test de URLs dinámicas para Local by Flywheel\n";
echo "================================================\n\n";

// Obtener URLs usando la función dev_tools_get_admin_url()
echo "1. URL base del sitio: " . get_site_url() . "\n";
echo "2. URL admin usando dev_tools_get_admin_url(): " . dev_tools_get_admin_url() . "\n";

// Obtener configuración de dev-tools
$config = dev_tools_config();
$menu_slug = $config->get('dev_tools.menu_slug');
echo "3. Menu slug: " . $menu_slug . "\n";

// URL completa de dev-tools
$dev_tools_url = dev_tools_get_admin_url('tools.php?page=' . $menu_slug);
echo "4. URL de Dev-Tools: " . $dev_tools_url . "\n\n";

echo "✅ Test completado. Usar la URL #4 para acceder a Dev-Tools\n";
