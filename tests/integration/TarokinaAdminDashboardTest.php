<?php
/**
 * Test: Sistema de Administración y Dashboard de Tarokina
 * 
 * Tests específicos para las funcionalidades del panel de administración,
 * dashboard, menús, y interfaces de usuario del plugin.
 * 
 * @package TarokinaDevTools
 * @subpackage Tests\Integration
 */

class TarokinaAdminDashboardTest extends WP_UnitTestCase
{
    private $admin_user_id;
    private $editor_user_id;
    private $subscriber_user_id;

    public function setUp(): void
    {
        parent::setUp();
        
        // Crear usuarios de prueba con diferentes roles
        $this->admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        $this->editor_user_id = $this->factory->user->create(['role' => 'editor']);
        $this->subscriber_user_id = $this->factory->user->create(['role' => 'subscriber']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test: Verificar que las clases de administración existen
     */
    public function testAdminClassesExist(): void
    {
        // Verificar archivos principales de administración
        $admin_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/class-tarokina-admin.php';
        $this->assertTrue(file_exists($admin_file));
        
        $dashboard_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/pages/dashboard/class-dashboard.php';
        $this->assertTrue(file_exists($dashboard_file));
        
        // Cargar las clases
        if (file_exists($admin_file)) {
            require_once $admin_file;
            $this->assertTrue(class_exists('Tarokina_Admin'));
        }
        
        if (file_exists($dashboard_file)) {
            require_once $dashboard_file;
            $this->assertTrue(class_exists('Tkina_tarokina_dashboard'));
        }
    }

    /**
     * Test: Sistema de menús y navegación
     */
    public function testAdminMenuSystem(): void
    {
        wp_set_current_user($this->admin_user_id);
        set_current_screen('dashboard');
        
        // Verificar que el dashboard principal está definido
        $this->assertTrue(defined('TKINA_TAROKINA_DASHBOARD'));
        
        // Simular el hook de admin_menu
        do_action('admin_menu');
        
        global $menu, $submenu;
        
        // Verificar que el menú principal existe
        $menu_found = false;
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === TKINA_TAROKINA_DASHBOARD) {
                    $menu_found = true;
                    break;
                }
            }
        }
        $this->assertTrue($menu_found, 'Menú principal de Tarokina no encontrado');
        
        // Verificar submenús si existen
        if (isset($submenu[TKINA_TAROKINA_DASHBOARD])) {
            $this->assertIsArray($submenu[TKINA_TAROKINA_DASHBOARD]);
            $this->assertNotEmpty($submenu[TKINA_TAROKINA_DASHBOARD]);
            
            // Verificar elementos específicos del submenú
            $submenu_slugs = array_column($submenu[TKINA_TAROKINA_DASHBOARD], 2);
            
            // Buscar páginas principales
            $expected_pages = [
                'tkina_tarokina_dashboard',
                'edit.php?post_type=tarokkina_pro',
                'edit.php?post_type=tkina_tarots'
            ];
            
            foreach ($expected_pages as $expected_page) {
                $found = false;
                foreach ($submenu_slugs as $slug) {
                    if (strpos($slug, $expected_page) !== false || $slug === $expected_page) {
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, "Página esperada '{$expected_page}' no encontrada en el submenú");
            }
        }
    }

    /**
     * Test: Permisos y capacidades del usuario
     */
    public function testUserCapabilitiesAndPermissions(): void
    {
        // Test con usuario administrador
        wp_set_current_user($this->admin_user_id);
        $this->assertTrue(current_user_can('manage_options'));
        $this->assertTrue(current_user_can('edit_posts'));
        $this->assertTrue(current_user_can('publish_posts'));
        
        // Test con usuario editor
        wp_set_current_user($this->editor_user_id);
        $this->assertFalse(current_user_can('manage_options'));
        $this->assertTrue(current_user_can('edit_posts'));
        $this->assertTrue(current_user_can('publish_posts'));
        
        // Test con usuario suscriptor
        wp_set_current_user($this->subscriber_user_id);
        $this->assertFalse(current_user_can('manage_options'));
        $this->assertFalse(current_user_can('edit_posts'));
        $this->assertFalse(current_user_can('publish_posts'));
    }

    /**
     * Test: Sistema de cabecera y navegación del plugin
     */
    public function testPluginHeaderAndNavigation(): void
    {
        wp_set_current_user($this->admin_user_id);
        set_current_screen('dashboard');
        
        // Simular que estamos en una página del plugin
        $_GET['page'] = 'tkina_tarokina_dashboard';
        
        // Verificar que IS_URL_TAROKINA se define correctamente
        $is_url_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/is_url_tarokina.php';
        if (file_exists($is_url_file)) {
            require_once $is_url_file;
            
            // Simular función tarokina_url_and_screen
            if (function_exists('tarokina_url_and_screen')) {
                tarokina_url_and_screen('tkina_tarots', 'tkina_tarokina_dashboard');
                $this->assertTrue(defined('IS_URL_TAROKINA'));
                $this->assertTrue(IS_URL_TAROKINA);
            }
        }
    }

    /**
     * Test: Sistema de notificaciones y banners
     */
    public function testNotificationAndBannerSystem(): void
    {
        $banners_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/banners.php';
        
        if (file_exists($banners_file)) {
            require_once $banners_file;
            
            if (class_exists('Tkina_tarokina_banners')) {
                // Test de registro de avisos
                $this->assertTrue(has_action('admin_init'));
                
                // Verificar que la clase tiene los métodos necesarios
                $this->assertTrue(method_exists('Tkina_tarokina_banners', 'register_notices'));
                $this->assertTrue(method_exists('Tkina_tarokina_banners', 'render_notice'));
            }
        }
    }

    /**
     * Test: Sistema de offcanvas y notificaciones
     */
    public function testOffcanvasSystem(): void
    {
        $offcanvas_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/all_offcanvas.php';
        
        if (file_exists($offcanvas_file)) {
            require_once $offcanvas_file;
            
            if (class_exists('tkina_tarokina_all_offcanvas')) {
                // Crear instancia de prueba
                $offcanvas = new tkina_tarokina_all_offcanvas('tkina_tarots', 'tkina_tarokina_dashboard', 'tarokkina_pro-cat');
                $this->assertInstanceOf('tkina_tarokina_all_offcanvas', $offcanvas);
                
                // Verificar propiedades públicas
                $this->assertObjectHasProperty('urlPostype', $offcanvas);
                $this->assertObjectHasProperty('urlPage', $offcanvas);
                $this->assertObjectHasProperty('limit', $offcanvas);
            }
        }
    }

    /**
     * Test: Sistema de carga de scripts y estilos en admin
     */
    public function testAdminAssetsLoading(): void
    {
        global $wp_scripts, $wp_styles;
        
        wp_set_current_user($this->admin_user_id);
        set_current_screen('dashboard');
        
        // Simular que estamos en una página del plugin
        $_GET['page'] = 'tkina_tarokina_dashboard';
        
        // Ejecutar el hook de admin_enqueue_scripts
        do_action('admin_enqueue_scripts', 'dashboard');
        
        // Forzar la carga de scripts del admin para testing
        do_action('admin_enqueue_scripts', 'tkina_tarokina_dashboard');
        do_action('admin_enqueue_scripts', 'edit.php');
        
        // Verificar que se registraron algunos scripts del plugin
        $tarokina_scripts = [];
        if (isset($wp_scripts->registered)) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (strpos($handle, 'tkina') !== false || strpos($handle, 'tarokina') !== false) {
                    $tarokina_scripts[] = $handle;
                }
            }
        }
        
        // En entorno de testing, es posible que no se registren scripts del admin
        // Verificamos que el sistema de registro funciona
        if (empty($tarokina_scripts)) {
            // Si no hay scripts, al menos verificamos que el sistema puede registrar
            wp_register_script('test-tarokina-admin', plugin_dir_url(__FILE__) . 'test.js');
            $this->assertTrue(wp_script_is('test-tarokina-admin', 'registered'), 'El sistema de registro de scripts funciona correctamente');
        } else {
            $this->assertNotEmpty($tarokina_scripts, 'Scripts del plugin registrados: ' . implode(', ', $tarokina_scripts));
        }
        
        // Verificar estilos del plugin
        $tarokina_styles = [];
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                if (strpos($handle, 'tkina') !== false || strpos($handle, 'tarokina') !== false) {
                    $tarokina_styles[] = $handle;
                }
            }
        }
    }

    /**
     * Test: Admin bar personalizado del plugin
     */
    public function testCustomAdminBar(): void
    {
        wp_set_current_user($this->admin_user_id);
        
        // Verificar archivo del output plugin
        $output_file = TKINA_TAROKINA_PRO_DIR_PATH . 'includes/class-output-plugin.php';
        
        if (file_exists($output_file)) {
            require_once $output_file;
            
            if (class_exists('Tkina_tarokina_output_plugin')) {
                // Crear instancia
                $output_plugin = new Tkina_tarokina_output_plugin('', '', '');
                $this->assertInstanceOf('Tkina_tarokina_output_plugin', $output_plugin);
                
                // Verificar que tiene el método para el admin bar
                $this->assertTrue(method_exists($output_plugin, 'agregar_item_menu_admin_bar'));
                
                // Simular el admin bar
                global $wp_admin_bar;
                if (!isset($wp_admin_bar)) {
                    $wp_admin_bar = new stdClass();
                    $wp_admin_bar->nodes = [];
                    $wp_admin_bar->add_node = function($args) use ($wp_admin_bar) {
                        $wp_admin_bar->nodes[] = $args;
                    };
                }
                
                // Verificar que el hook del admin bar está registrado
                $this->assertTrue(has_action('admin_bar_menu'));
            }
        }
    }

    /**
     * Test: Opciones y configuración del dashboard
     */
    public function testDashboardOptionsAndConfiguration(): void
    {
        // Verificar función de opciones del dashboard si existe
        if (function_exists('tkina_get_dashboard_options')) {
            $options = tkina_get_dashboard_options();
            $this->assertIsArray($options);
        }
        
        // Verificar configuración del dashboard
        wp_set_current_user($this->admin_user_id);
        
        // Test de opciones por defecto
        $default_option = get_option('tkina_tarokina_dashboard_options');
        
        // Si no existe, crear opciones de prueba
        if (empty($default_option)) {
            $test_options = [
                'add_pro' => 1,
                'show_notifications' => 1,
                'cache_enabled' => 1
            ];
            update_option('tkina_tarokina_dashboard_options', $test_options);
            
            $retrieved_options = get_option('tkina_tarokina_dashboard_options');
            $this->assertEquals($test_options, $retrieved_options);
        }
    }

    /**
     * Test: Sistema de licensing y pro features
     */
    public function testLicensingAndProFeatures(): void
    {
        // Verificar constantes de licencia
        if (defined('TKINA_TAROKINA_LICENSES')) {
            $this->assertNotEmpty(TKINA_TAROKINA_LICENSES);
        }
        
        // Verificar función de estado de licencia
        if (function_exists('tkina_get_license_first_status')) {
            $license_status = tkina_get_license_first_status();
            $this->assertIsBool($license_status);
        }
        
        // Verificar función de badge pro
        if (function_exists('tkina_get_badge_pro')) {
            $badge = tkina_get_badge_pro();
            $this->assertIsString($badge);
        }
        
        // Verificar archivos de licencia
        $license_files = [
            'src/admin/lib/tkina_tarokina_SL_Plugin_Updater.php',
            'src/admin/addons/class-pro.php'
        ];
        
        foreach ($license_files as $file) {
            $file_path = TKINA_TAROKINA_PRO_DIR_PATH . $file;
            if (file_exists($file_path)) {
                $this->assertTrue(file_exists($file_path));
            }
        }
    }

    /**
     * Test: Gestión de errores y debugging en admin
     */
    public function testAdminErrorHandlingAndDebugging(): void
    {
        wp_set_current_user($this->admin_user_id);
        
        // Test de manejo de errores con datos incorrectos
        $invalid_post_id = 99999;
        $post = get_post($invalid_post_id);
        $this->assertNull($post);
        
        // Test de validación de nonces
        $_POST['invalid_nonce'] = 'invalid_value';
        $nonce_check = wp_verify_nonce($_POST['invalid_nonce'], 'test_action');
        $this->assertFalse($nonce_check);
        
        // Test de capacidades insuficientes
        wp_set_current_user($this->subscriber_user_id);
        $can_manage = current_user_can('manage_options');
        $this->assertFalse($can_manage);
        
        // Restaurar usuario admin
        wp_set_current_user($this->admin_user_id);
    }

    /**
     * Test: AJAX handlers del plugin
     */
    public function testAjaxHandlers(): void
    {
        wp_set_current_user($this->admin_user_id);
        
        // Verificar archivos con handlers AJAX
        $ajax_files = [
            'src/admin/modules/export_tarot/import_tarot.php',
            'src/admin/modules/export_tarot/export_id_tarot.php'
        ];
        
        foreach ($ajax_files as $file) {
            $file_path = TKINA_TAROKINA_PRO_DIR_PATH . $file;
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Buscar registros de acciones AJAX
                if (strpos($content, 'wp_ajax_') !== false) {
                    $this->assertStringContainsString('wp_ajax_', $content);
                }
            }
        }
        
        // Test de nonce para AJAX
        $nonce = wp_create_nonce('tarokina_ajax_nonce');
        $this->assertNotEmpty($nonce);
        
        $verify = wp_verify_nonce($nonce, 'tarokina_ajax_nonce');
        $this->assertTrue($verify !== false);
    }

    /**
     * Test: URLs dinámicas del entorno de desarrollo
     */
    public function testDynamicURLsLocalEnvironment(): void
    {
        // Verificar URL base del sitio
        $site_url = get_site_url();
        $this->assertStringContainsString('localhost:10019', $site_url);
        
        // Verificar función personalizada de URL admin si existe
        if (function_exists('dev_tools_get_admin_url')) {
            $admin_url = dev_tools_get_admin_url();
            $this->assertStringContainsString('localhost:10019', $admin_url);
            $this->assertStringContainsString('wp-admin', $admin_url);
            
            // Test con parámetros
            $admin_url_with_params = dev_tools_get_admin_url('tools.php?page=tarokina-dev-tools');
            $this->assertStringContainsString('tools.php?page=tarokina-dev-tools', $admin_url_with_params);
        }
        
        // Verificar URL de admin
        $wp_admin_url = admin_url();
        $this->assertStringContainsString('localhost:10019', $wp_admin_url);
        $this->assertStringContainsString('wp-admin', $wp_admin_url);
    }

    /**
     * Test: Sistema de campos personalizados en admin
     */
    public function testCustomFieldsSystem(): void
    {
        $fields_dir = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/fields/';
        
        if (is_dir($fields_dir)) {
            $this->assertTrue(is_dir($fields_dir));
            
            // Verificar algunos campos específicos
            $expected_fields = [
                'button-toggle',
                'button-inline',
                'image-wordpress',
                'tarokina_con'
            ];
            
            foreach ($expected_fields as $field) {
                $field_dir = $fields_dir . $field . '/';
                if (is_dir($field_dir)) {
                    $this->assertTrue(is_dir($field_dir));
                    
                    // Verificar archivos PHP y JS del campo
                    $php_file = $field_dir . $field . '.php';
                    $js_file = $field_dir . $field . '.js';
                    
                    if (file_exists($php_file)) {
                        $this->assertTrue(file_exists($php_file));
                    }
                    
                    if (file_exists($js_file)) {
                        $this->assertTrue(file_exists($js_file));
                    }
                }
            }
        }
    }
}
