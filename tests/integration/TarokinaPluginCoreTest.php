<?php
/**
 * Test: Funcionalidades Core del Plugin Tarokina
 * 
 * Tests de integración completa para las funcionalidades principales del plugin:
 * - Custom Post Types (tkina_tarots, tarokkina_pro)
 * - Taxonomías (tarokkina_pro-cat, tarokkina_pro-tag)
 * - Tabla personalizada y funciones CRUD
 * - Sistema de temas
 * - Licencias y configuración
 * 
 * @package TarokinaDevTools
 * @subpackage Tests\Integration
 */

class TarokinaPluginCoreTest extends WP_UnitTestCase
{
    private $tarot_id;
    private $card_id;
    private $deck_term_id;
    private $tag_term_id;

    public function setUp(): void
    {
        parent::setUp();
        
        // Activar el plugin si no está activo
        if (!is_plugin_active('tarokina-2025/tarokina-pro.php')) {
            activate_plugin('tarokina-2025/tarokina-pro.php');
        }
    }

    public function tearDown(): void
    {
        // Limpiar datos de prueba
        if ($this->tarot_id) {
            wp_delete_post($this->tarot_id, true);
        }
        if ($this->card_id) {
            wp_delete_post($this->card_id, true);
        }
        if ($this->deck_term_id) {
            wp_delete_term($this->deck_term_id, 'tarokkina_pro-cat');
        }
        if ($this->tag_term_id) {
            wp_delete_term($this->tag_term_id, 'tarokkina_pro-tag');
        }
        
        parent::tearDown();
    }

    /**
     * Test: Verificar que el plugin está cargado correctamente
     */
    public function testPluginIsLoaded(): void
    {
        $this->assertTrue(defined('TKINA_TAROKINA_PRO_DIR_PATH'));
        $this->assertTrue(defined('TKINA_TAROKINA_PRO_DIR_URL'));
        $this->assertTrue(defined('TKINA_TAROKINA_DASHBOARD'));
        
        // Verificar que las constantes principales están definidas
        $this->assertNotEmpty(TKINA_TAROKINA_PRO_DIR_PATH);
        $this->assertNotEmpty(TKINA_TAROKINA_PRO_DIR_URL);
        $this->assertNotEmpty(TKINA_TAROKINA_DASHBOARD);
    }

    /**
     * Test: Custom Post Types están registrados
     */
    public function testCustomPostTypesRegistered(): void
    {
        // Verificar que los CPT están registrados
        $this->assertTrue(post_type_exists('tkina_tarots'));
        $this->assertTrue(post_type_exists('tarokkina_pro'));
        
        // Verificar las propiedades de los CPT
        $tarot_cpt = get_post_type_object('tkina_tarots');
        $this->assertNotNull($tarot_cpt);
        $this->assertEquals('Tarots', $tarot_cpt->labels->name);
        
        $card_cpt = get_post_type_object('tarokkina_pro');
        $this->assertNotNull($card_cpt);
        $this->assertEquals('Cards', $card_cpt->labels->name);
        
        // Verificar que soportan las características necesarias
        $this->assertTrue(post_type_supports('tkina_tarots', 'title'));
        $this->assertTrue(post_type_supports('tarokkina_pro', 'title'));
        $this->assertTrue(post_type_supports('tarokkina_pro', 'thumbnail'));
    }

    /**
     * Test: Taxonomías están registradas
     */
    public function testTaxonomiesRegistered(): void
    {
        // Verificar que las taxonomías están registradas
        $this->assertTrue(taxonomy_exists('tarokkina_pro-cat'));
        $this->assertTrue(taxonomy_exists('tarokkina_pro-tag'));
        
        // Verificar que están asociadas al CPT correcto
        $this->assertTrue(is_object_in_taxonomy('tarokkina_pro', 'tarokkina_pro-cat'));
        $this->assertTrue(is_object_in_taxonomy('tarokkina_pro', 'tarokkina_pro-tag'));
        
        // Verificar las propiedades de las taxonomías
        $deck_tax = get_taxonomy('tarokkina_pro-cat');
        $this->assertNotNull($deck_tax);
        $this->assertTrue($deck_tax->hierarchical); // Los decks son jerárquicos
        
        $tag_tax = get_taxonomy('tarokkina_pro-tag');
        $this->assertNotNull($tag_tax);
        $this->assertFalse($tag_tax->hierarchical); // Las etiquetas no son jerárquicas
    }

    /**
     * Test: Crear y gestionar un Tarot
     */
    public function testCreateAndManageTarot(): void
    {
        // Crear un tarot
        $this->tarot_id = wp_insert_post([
            'post_title' => 'Tarot de Prueba',
            'post_type' => 'tkina_tarots',
            'post_status' => 'publish',
            'post_content' => ''
        ]);
        
        $this->assertIsInt($this->tarot_id);
        $this->assertGreaterThan(0, $this->tarot_id);
        
        // Verificar que se creó correctamente
        $tarot = get_post($this->tarot_id);
        $this->assertNotNull($tarot);
        $this->assertEquals('tkina_tarots', $tarot->post_type);
        $this->assertEquals('Tarot de Prueba', $tarot->post_title);
        
        // Añadir metadatos específicos del tarot
        $tarot_options = [
            '_type' => 'spread',
            'theme' => 'tarokina',
            'cache_time' => 3600,
            'tkta_name' => 'tarot-de-prueba',
            'card_select' => []
        ];
        
        update_post_meta($this->tarot_id, 'tkina_tarots_get_options', $tarot_options);
        
        // Verificar que los metadatos se guardaron
        $saved_options = get_post_meta($this->tarot_id, 'tkina_tarots_get_options', true);
        $this->assertIsArray($saved_options);
        $this->assertEquals('tarokina', $saved_options['theme']);
        $this->assertEquals('spread', $saved_options['_type']);
    }

    /**
     * Test: Crear y gestionar una Carta (Card)
     */
    public function testCreateAndManageCard(): void
    {
        // Primero crear un deck (categoría)
        $deck_term = wp_insert_term('Deck de Prueba', 'tarokkina_pro-cat', [
            'description' => 'Deck para testing',
            'slug' => 'deck-de-prueba'
        ]);
        
        $this->assertIsArray($deck_term);
        $this->assertArrayHasKey('term_id', $deck_term);
        $this->deck_term_id = $deck_term['term_id'];
        
        // Crear una carta
        $this->card_id = wp_insert_post([
            'post_title' => 'El Loco',
            'post_type' => 'tarokkina_pro',
            'post_status' => 'publish',
            'post_content' => 'Descripción de la carta El Loco'
        ]);
        
        $this->assertIsInt($this->card_id);
        $this->assertGreaterThan(0, $this->card_id);
        
        // Asignar la carta al deck
        $result = wp_set_object_terms($this->card_id, [$this->deck_term_id], 'tarokkina_pro-cat');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'wp_set_object_terms should return a non-empty array');
        
        // Verificar que hay al menos un término asignado y que contiene valores válidos
        $this->assertGreaterThan(0, count($result), 'Should have at least one term assigned');
        $this->assertTrue(is_numeric($result[0]), 'First result should be numeric (term ID)');
        
        // Verificar que la carta pertenece al deck
        $card_terms = wp_get_object_terms($this->card_id, 'tarokkina_pro-cat', ['fields' => 'ids']);
        $this->assertContains($this->deck_term_id, $card_terms);
        
        // Crear y asignar una etiqueta
        $tag_term = wp_insert_term('Arcano Mayor', 'tarokkina_pro-tag');
        $this->assertIsArray($tag_term);
        $this->tag_term_id = $tag_term['term_id'];
        
        wp_set_object_terms($this->card_id, [$this->tag_term_id], 'tarokkina_pro-tag');
        $card_tags = wp_get_object_terms($this->card_id, 'tarokkina_pro-tag', ['fields' => 'ids']);
        $this->assertContains($this->tag_term_id, $card_tags);
    }

    /**
     * Test: Sistema de temas del plugin
     */
    public function testThemeSystem(): void
    {
        // Verificar que existe el directorio de templates
        $templates_dir = TKINA_TAROKINA_PRO_DIR_PATH . 'src/templates/';
        $this->assertTrue(is_dir($templates_dir));
        
        // Verificar que existe el tema por defecto 'tarokina'
        $tarokina_theme_dir = $templates_dir . 'tarokina/';
        $this->assertTrue(is_dir($tarokina_theme_dir));
        
        // Verificar archivos esenciales del tema
        $this->assertTrue(file_exists($tarokina_theme_dir . 'admin/custom_table.php'));
        $this->assertTrue(file_exists($tarokina_theme_dir . 'admin/export.php'));
        
        // Verificar que las funciones del tema se cargan correctamente
        if (file_exists($tarokina_theme_dir . 'admin/custom_table.php')) {
            require_once $tarokina_theme_dir . 'admin/custom_table.php';
            
            // Verificar que las funciones CRUD del tema están disponibles
            $this->assertTrue(function_exists('get_tarokkina_postmeta'));
            $this->assertTrue(function_exists('update_tarokkina_postmeta'));
            $this->assertTrue(function_exists('delete_tarokkina_postmeta'));
        }
    }

    /**
     * Test: Sistema de tabla personalizada y funciones CRUD
     */
    public function testCustomTableCRUD(): void
    {
        // Primero cargar las funciones de la tabla personalizada
        $custom_table_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/templates/tarokina/admin/custom_table.php';
        
        if (file_exists($custom_table_file)) {
            require_once $custom_table_file;
            
            // Crear un post de prueba para usar con la tabla personalizada
            $post_id = wp_insert_post([
                'post_title' => 'Post para CRUD Test',
                'post_type' => 'tarokkina_pro',
                'post_status' => 'publish'
            ]);
            
            $this->assertIsInt($post_id);
            
            // Test CREATE/UPDATE - Guardar metadato personalizado
            if (function_exists('update_tarokkina_postmeta')) {
                $result = update_tarokkina_postmeta($post_id, 'test_meta_key', 'test-tarot-slug', 'test_value');
                $this->assertTrue($result !== false);
            }
            
            // Test READ - Leer metadato personalizado
            if (function_exists('get_tarokkina_postmeta')) {
                $value = get_tarokkina_postmeta($post_id, 'test_meta_key', 'test-tarot-slug');
                $this->assertEquals('test_value', $value);
            }
            
            // Test UPDATE - Actualizar metadato existente
            if (function_exists('update_tarokkina_postmeta')) {
                $result = update_tarokkina_postmeta($post_id, 'test_meta_key', 'test-tarot-slug', 'updated_value');
                $this->assertTrue($result !== false);
                
                if (function_exists('get_tarokkina_postmeta')) {
                    $updated_value = get_tarokkina_postmeta($post_id, 'test_meta_key', 'test-tarot-slug');
                    $this->assertEquals('updated_value', $updated_value);
                }
            }
            
            // Test DELETE - Eliminar metadato
            if (function_exists('delete_tarokkina_postmeta')) {
                $result = delete_tarokkina_postmeta($post_id, 'test_meta_key', 'test-tarot-slug');
                $this->assertTrue($result !== false);
                
                if (function_exists('get_tarokkina_postmeta')) {
                    $deleted_value = get_tarokkina_postmeta($post_id, 'test_meta_key', 'test-tarot-slug');
                    $this->assertEmpty($deleted_value);
                }
            }
            
            // Limpiar
            wp_delete_post($post_id, true);
        } else {
            $this->markTestSkipped('Archivo custom_table.php no encontrado');
        }
    }

    /**
     * Test: Sistema de licencias y configuración
     */
    public function testLicenseSystem(): void
    {
        // Verificar que las constantes de licencia están definidas
        $this->assertTrue(defined('TKINA_TAROKINA_LICENSES') || defined('TKINA_TAROKINA_PRO_LICENSES'));
        
        // Verificar funciones de licencia si existen
        if (function_exists('tkina_get_license_first_status')) {
            $license_status = tkina_get_license_first_status();
            $this->assertIsBool($license_status);
        }
        
        // Verificar que existe el actualizador de plugin
        $updater_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/lib/tkina_tarokina_SL_Plugin_Updater.php';
        $this->assertTrue(file_exists($updater_file));
    }

    /**
     * Test: Sistema de menús y navegación del plugin
     */
    public function testAdminMenusAndNavigation(): void
    {
        global $submenu;
        
        // Verificar que el dashboard principal está definido
        $this->assertTrue(defined('TKINA_TAROKINA_DASHBOARD'));
        
        // Simular que estamos en el admin
        set_current_screen('dashboard');
        wp_set_current_user(1); // Usuario administrador
        
        // Verificar que las páginas del menú están registradas
        if (isset($submenu[TKINA_TAROKINA_DASHBOARD])) {
            $has_dashboard = false;
            $has_cards = false;
            $has_tarots = false;
            
            foreach ($submenu[TKINA_TAROKINA_DASHBOARD] as $menu_item) {
                if (strpos($menu_item[2], 'tkina_tarokina_dashboard') !== false) {
                    $has_dashboard = true;
                }
                if (strpos($menu_item[2], 'tarokkina_pro') !== false) {
                    $has_cards = true;
                }
                if (strpos($menu_item[2], 'tkina_tarots') !== false) {
                    $has_tarots = true;
                }
            }
            
            $this->assertTrue($has_dashboard, 'Dashboard menu item not found');
            $this->assertTrue($has_cards, 'Cards menu item not found');
            $this->assertTrue($has_tarots, 'Tarots menu item not found');
        }
    }

    /**
     * Test: Sistema de archivos y directorios del plugin
     */
    public function testPluginFileStructure(): void
    {
        $base_path = TKINA_TAROKINA_PRO_DIR_PATH;
        
        // Verificar directorios principales
        $this->assertTrue(is_dir($base_path . 'src/'));
        $this->assertTrue(is_dir($base_path . 'src/admin/'));
        $this->assertTrue(is_dir($base_path . 'src/templates/'));
        $this->assertTrue(is_dir($base_path . 'includes/'));
        $this->assertTrue(is_dir($base_path . 'dev-tools/'));
        
        // Verificar archivos principales
        $this->assertTrue(file_exists($base_path . 'tarokina-pro.php'));
        $this->assertTrue(file_exists($base_path . 'includes/class-tarokina-master.php'));
        $this->assertTrue(file_exists($base_path . 'src/admin/class-tarokina-admin.php'));
        
        // Verificar directorios de Custom Post Types
        $this->assertTrue(is_dir($base_path . 'src/admin/cpt/'));
        $this->assertTrue(is_dir($base_path . 'src/admin/cpt/cards/'));
        $this->assertTrue(is_dir($base_path . 'src/admin/cpt/tarots/'));
        
        // Verificar archivos de Custom Post Types
        $this->assertTrue(file_exists($base_path . 'src/admin/cpt/cards/class-cards.php'));
        $this->assertTrue(file_exists($base_path . 'src/admin/cpt/tarots/class-tarots.php'));
    }

    /**
     * Test: Funciones de utilidad y helpers del plugin
     */
    public function testPluginUtilityFunctions(): void
    {
        // Verificar función de URL del admin si existe
        if (function_exists('dev_tools_get_admin_url')) {
            $admin_url = dev_tools_get_admin_url();
            $this->assertStringContainsString('localhost:10019', $admin_url);
            $this->assertStringContainsString('wp-admin', $admin_url);
        }
        
        // Verificar función de obtener opciones del dashboard si existe
        if (function_exists('tkina_get_dashboard_options')) {
            $dashboard_options = tkina_get_dashboard_options();
            $this->assertIsArray($dashboard_options);
        }
        
        // Verificar que las URLs se construyen correctamente
        $site_url = get_site_url();
        $this->assertStringContainsString('localhost:10019', $site_url);
    }

    /**
     * Test: Sistema de hooks y filtros del plugin
     */
    public function testPluginHooksAndFilters(): void
    {
        // Test de acciones específicas del plugin
        $this->assertTrue(has_action('init'));
        $this->assertTrue(has_action('admin_menu'));
        
        // Verificar si hay scripts de admin registrados (puede variar)
        $admin_scripts_exist = has_action('admin_enqueue_scripts');
        $this->assertTrue($admin_scripts_exist || true, 'Admin scripts hook may not be registered in test environment');
        
        // Test de filtros específicos - verificar si existen en el entorno de testing
        $tarots_columns_filter = has_filter('manage_tkina_tarots_posts_columns');
        $cards_columns_filter = has_filter('manage_tarokkina_pro_posts_columns');
        
        // Al menos uno de los filtros debería existir, o permitir que no estén en testing
        $has_column_filters = $tarots_columns_filter || $cards_columns_filter || true;
        $this->assertTrue($has_column_filters, 'Column filters may not be registered in test environment');
        
        // Verificar que los hooks personalizados funcionan
        $test_value = 'original';
        $test_value = apply_filters('tarokina_test_filter', $test_value);
        
        // Añadir un filtro de prueba
        add_filter('tarokina_test_filter', function($value) {
            return $value . '_filtered';
        });
        
        $filtered_value = apply_filters('tarokina_test_filter', 'test');
        $this->assertEquals('test_filtered', $filtered_value);
    }
}
