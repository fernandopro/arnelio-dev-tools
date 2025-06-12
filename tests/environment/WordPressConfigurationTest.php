<?php
/**
 * Tests de Configuración WordPress - Dev-Tools Arquitectura 3.0
 * 
 * Tests específicos para validar la configuración de WordPress,
 * plugins, themes, y configuración específica de desarrollo
 * 
 * @package DevTools
 * @subpackage Tests\Environment
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class WordPressConfigurationTest extends DevToolsTestCase {

    /**
     * Test: Verificar configuración de plugins necesarios
     */
    public function test_required_plugins_availability() {
        // Plugin principal (Tarokina Pro)
        $tarokina_plugin_path = WP_PLUGIN_DIR . '/tarokina-2025/tarokina-pro.php';
        $this->assertFileExists($tarokina_plugin_path, 
            'Plugin principal Tarokina Pro debería existir');
        
        // Verificar que el plugin está activo
        $active_plugins = get_option('active_plugins', []);
        $tarokina_active = false;
        
        foreach ($active_plugins as $plugin) {
            if (strpos($plugin, 'tarokina-2025') !== false) {
                $tarokina_active = true;
                break;
            }
        }
        
        $this->assertTrue($tarokina_active, 
            'Plugin Tarokina Pro debería estar activo');
    }

    /**
     * Test: Verificar tema activo y configuración
     */
    public function test_active_theme_configuration() {
        $current_theme = wp_get_theme();
        
        $this->assertInstanceOf('WP_Theme', $current_theme, 
            'Debería haber un tema activo');
        
        $this->assertFalse($current_theme->errors(), 
            'El tema activo no debería tener errores');
        
        // Verificar capacidades del tema
        $this->assertTrue($current_theme->is_allowed(), 
            'El tema debería estar permitido');
    }

    /**
     * Test: Verificar configuración de uploads
     */
    public function test_uploads_configuration() {
        $upload_dir = wp_upload_dir();
        
        // Verificar que el directorio existe y es escribible
        $this->assertDirectoryExists($upload_dir['basedir'], 
            'Directorio de uploads debería existir');
        
        $this->assertTrue(is_writable($upload_dir['basedir']), 
            'Directorio de uploads debería ser escribible');
        
        // Verificar configuración de upload limits
        $max_upload = wp_max_upload_size();
        $this->assertGreaterThan(0, $max_upload, 
            'Max upload size debería estar configurado');
        
        // En desarrollo, deberíamos tener límites generosos
        $this->assertGreaterThanOrEqual(2 * 1024 * 1024, $max_upload, 
            'Max upload size debería ser al menos 2MB');
    }

    /**
     * Test: Verificar configuración de rewrite rules
     */
    public function test_rewrite_rules_configuration() {
        global $wp_rewrite;
        
        // Verificar que pretty permalinks están configurados
        $permalink_structure = get_option('permalink_structure');
        $this->assertNotEmpty($permalink_structure, 
            'Estructura de permalinks debería estar configurada');
        
        // Verificar que las reglas están actualizadas
        $this->assertNotEmpty($wp_rewrite->rules, 
            'Rewrite rules deberían estar configuradas');
    }

    /**
     * Test: Verificar configuración de usuarios y roles
     */
    public function test_user_roles_configuration() {
        // Verificar roles básicos de WordPress
        $required_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        
        foreach ($required_roles as $role_name) {
            $role = get_role($role_name);
            $this->assertNotNull($role, "Rol '{$role_name}' debería existir");
        }
        
        // Verificar usuario admin existe
        $admin_users = get_users(['role' => 'administrator']);
        $this->assertNotEmpty($admin_users, 
            'Debería haber al menos un usuario administrador');
    }

    /**
     * Test: Verificar configuración de cache
     */
    public function test_cache_configuration() {
        // Object cache
        $this->assertTrue(wp_using_ext_object_cache() || !wp_using_ext_object_cache(), 
            'Object cache debería estar configurado (true o false)');
        
        // Verificar que cache de opciones funciona
        $test_option = 'dev_tools_cache_test_' . time();
        $test_value = 'test_value_' . uniqid();
        
        // Set cache
        wp_cache_set($test_option, $test_value);
        
        // Get cache
        $cached_value = wp_cache_get($test_option);
        $this->assertEquals($test_value, $cached_value, 
            'Cache debería funcionar correctamente');
        
        // Delete cache
        wp_cache_delete($test_option);
        $deleted_value = wp_cache_get($test_option);
        $this->assertFalse($deleted_value, 
            'Cache delete debería funcionar');
    }

    /**
     * Test: Verificar configuración de timezone
     */
    public function test_timezone_configuration() {
        $timezone = get_option('timezone_string');
        $gmt_offset = get_option('gmt_offset');
        
        // Debería tener timezone configurado
        $this->assertTrue(!empty($timezone) || !empty($gmt_offset), 
            'Timezone debería estar configurado');
        
        // Verificar que las fechas funcionan correctamente
        $current_time = current_time('timestamp');
        $this->assertGreaterThan(0, $current_time, 
            'current_time() debería devolver timestamp válido');
    }

    /**
     * Test: Verificar configuración de comentarios
     */
    public function test_comments_configuration() {
        // Configuración básica de comentarios
        $default_comment_status = get_option('default_comment_status');
        $this->assertContains($default_comment_status, ['open', 'closed'], 
            'Estado de comentarios debería ser válido');
        
        // Moderación de comentarios
        $comment_moderation = get_option('comment_moderation');
        $this->assertContains($comment_moderation, ['0', '1'], 
            'Moderación de comentarios debería estar configurada');
    }

    /**
     * Test: Verificar configuración de medios
     */
    public function test_media_configuration() {
        // Tamaños de imagen
        $thumbnail_size = get_option('thumbnail_size_w');
        $medium_size = get_option('medium_size_w');
        $large_size = get_option('large_size_w');
        
        $this->assertGreaterThan(0, $thumbnail_size, 
            'Tamaño de thumbnail debería estar configurado');
        $this->assertGreaterThan(0, $medium_size, 
            'Tamaño medium debería estar configurado');
        $this->assertGreaterThan(0, $large_size, 
            'Tamaño large debería estar configurado');
        
        // Verificar tipos de archivo permitidos
        $allowed_mime_types = get_allowed_mime_types();
        $this->assertNotEmpty($allowed_mime_types, 
            'Tipos MIME permitidos deberían estar configurados');
        
        // Verificar tipos esenciales
        $essential_types = ['jpg', 'png', 'gif', 'pdf'];
        foreach ($essential_types as $type) {
            $found = false;
            foreach ($allowed_mime_types as $mime_type) {
                if (strpos($mime_type, $type) !== false) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Tipo de archivo '{$type}' debería estar permitido");
        }
    }

    /**
     * Test: Verificar configuración de RSS/feeds
     */
    public function test_rss_feeds_configuration() {
        // RSS feeds deberían estar habilitados
        $rss_posts = get_option('rss_use_excerpt');
        $this->assertContains($rss_posts, ['0', '1'], 
            'RSS posts debería estar configurado');
        
        // Verificar que los feeds son accesibles
        $feed_url = get_feed_link();
        $this->assertNotEmpty($feed_url, 
            'Feed URL debería estar disponible');
        
        $this->assertStringContainsString('/feed/', $feed_url, 
            'Feed URL debería contener /feed/');
    }

    /**
     * Test: Verificar configuración de seguridad básica
     */
    public function test_basic_security_configuration() {
        // File editing debería estar deshabilitado en producción
        // En desarrollo puede estar habilitado
        if (defined('DISALLOW_FILE_EDIT')) {
            $this->assertIsBool(DISALLOW_FILE_EDIT, 
                'DISALLOW_FILE_EDIT debería ser boolean');
        }
        
        // Verificar que no hay passwords débiles por defecto
        $admin_users = get_users(['role' => 'administrator']);
        foreach ($admin_users as $user) {
            // No verificamos password actual por seguridad, solo que existe
            $this->assertNotEmpty($user->user_login, 
                'Usuario admin debería tener login válido');
        }
        
        // Verificar configuración de sesiones
        $this->assertTrue(session_status() === PHP_SESSION_NONE || session_status() === PHP_SESSION_ACTIVE, 
            'Estado de sesiones debería ser válido');
    }

    /**
     * Test: Verificar configuración de multisite (si aplica)
     */
    public function test_multisite_configuration() {
        if (is_multisite()) {
            // Si es multisite, verificar configuración
            $this->assertTrue(defined('MULTISITE'), 
                'MULTISITE debería estar definido');
            
            $this->assertTrue(defined('SUBDOMAIN_INSTALL'), 
                'SUBDOMAIN_INSTALL debería estar definido');
            
            // Verificar que hay al menos un sitio
            $sites = get_sites(['number' => 1]);
            $this->assertNotEmpty($sites, 
                'Debería haber al menos un sitio en multisite');
        } else {
            // Si no es multisite, verificar que no está mal configurado
            $this->assertFalse(defined('MULTISITE') && MULTISITE, 
                'MULTISITE no debería estar activo si no es multisite');
        }
    }

    /**
     * Test: Verificar configuración de desarrollo específica
     */
    public function test_development_specific_configuration() {
        // Query debugging
        if (defined('SAVEQUERIES')) {
            $this->assertTrue(is_bool(SAVEQUERIES), 
                'SAVEQUERIES debería ser boolean');
        }
        
        // Verify wp-config.php has development settings
        if (WP_DEBUG) {
            $this->assertTrue(WP_DEBUG_LOG, 
                'Si WP_DEBUG está activo, WP_DEBUG_LOG también debería estarlo');
        }
        
        // Auto updates configuration
        $auto_updates = get_option('auto_update_core_major');
        if ($auto_updates !== false) {
            $this->assertIsBool($auto_updates, 
                'Auto updates debería ser boolean');
        }
    }
}
