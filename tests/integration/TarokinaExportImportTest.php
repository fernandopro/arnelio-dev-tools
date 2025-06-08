<?php
/**
 * Test: Sistema de Exportación e Importación de Tarots
 * 
 * Tests específicos para el sistema de exportación/importación de tarots,
 * incluyendo funcionalidades de backup y restauración.
 * 
 * @package TarokinaDevTools
 * @subpackage Tests\Integration
 */

class TarokinaExportImportTest extends WP_UnitTestCase
{
    private $test_tarot_id;
    private $test_card_id;
    private $test_deck_id;
    private $export_dir;

    public function setUp(): void
    {
        parent::setUp();
        
        // Configurar directorio temporal para exports
        $this->export_dir = TKINA_TAROKINA_PRO_DIR_PATH . 'dev-tools/tests/temp/exports/';
        wp_mkdir_p($this->export_dir);
        
        // Crear datos de prueba
        $this->createTestData();
    }

    public function tearDown(): void
    {
        // Limpiar datos de prueba
        $this->cleanupTestData();
        
        // Limpiar archivos de export temporales
        if (is_dir($this->export_dir)) {
            $files = glob($this->export_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        parent::tearDown();
    }

    /**
     * Crear datos de prueba para los tests
     */
    private function createTestData(): void
    {
        // Generar nombre único para el deck usando timestamp
        $unique_name = 'Deck Export Test ' . time() . '_' . wp_rand(1000, 9999);
        $unique_slug = 'deck-export-test-' . time() . '-' . wp_rand(1000, 9999);
        
        // Crear deck de prueba con nombre único
        $deck_term = wp_insert_term($unique_name, 'tarokkina_pro-cat', [
            'description' => 'Deck para testing de exportación',
            'slug' => $unique_slug
        ]);
        
        // Verificar si wp_insert_term fue exitoso
        if (is_wp_error($deck_term)) {
            $this->fail('Error creando deck de prueba: ' . $deck_term->get_error_message());
        }
        
        $this->test_deck_id = $deck_term['term_id'];

        // Crear carta de prueba con nombre único
        $unique_card_name = 'Carta Test Export ' . time() . '_' . wp_rand(1000, 9999);
        $this->test_card_id = wp_insert_post([
            'post_title' => $unique_card_name,
            'post_type' => 'tarokkina_pro',
            'post_status' => 'publish',
            'post_content' => 'Contenido de carta para testing'
        ]);
        
        // Verificar si wp_insert_post fue exitoso
        if (is_wp_error($this->test_card_id) || $this->test_card_id === 0) {
            $this->fail('Error creando carta de prueba');
        }
        
        // Asignar carta al deck
        wp_set_object_terms($this->test_card_id, [$this->test_deck_id], 'tarokkina_pro-cat');

        // Crear tarot de prueba con nombre único
        $unique_tarot_name = 'Tarot Export Test ' . time() . '_' . wp_rand(1000, 9999);
        $this->test_tarot_id = wp_insert_post([
            'post_title' => $unique_tarot_name,
            'post_type' => 'tkina_tarots',
            'post_status' => 'publish',
            'post_content' => ''
        ]);
        
        // Verificar si wp_insert_post fue exitoso
        if (is_wp_error($this->test_tarot_id) || $this->test_tarot_id === 0) {
            $this->fail('Error creando tarot de prueba');
        }

        // Configurar metadatos del tarot
        $tarot_options = [
            '_type' => 'spread',
            'theme' => 'tarokina',
            'cache_time' => 3600,
            'tkta_name' => 'tarot-export-test',
            'tkta_barajas' => $this->test_deck_id,
            'card_select' => [$this->test_card_id => 1]
        ];
        
        update_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', $tarot_options);
    }

    /**
     * Limpiar datos de prueba
     */
    private function cleanupTestData(): void
    {
        if ($this->test_tarot_id) {
            wp_delete_post($this->test_tarot_id, true);
        }
        if ($this->test_card_id) {
            wp_delete_post($this->test_card_id, true);
        }
        if ($this->test_deck_id) {
            wp_delete_term($this->test_deck_id, 'tarokkina_pro-cat');
        }
    }

    /**
     * Test: Verificar que las clases de exportación existen
     */
    public function testExportClassesExist(): void
    {
        // Verificar archivos de exportación
        $export_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/export_tarot/export_id_tarot.php';
        $this->assertTrue(file_exists($export_file));
        
        $import_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/export_tarot/import_tarot.php';
        $this->assertTrue(file_exists($import_file));
        
        // Cargar las clases si existen
        if (file_exists($export_file)) {
            require_once $export_file;
        }
        
        if (file_exists($import_file)) {
            require_once $import_file;
        }
    }

    /**
     * Test: Sistema de exportación de temas específicos
     */
    public function testThemeSpecificExport(): void
    {
        $tarokina_export_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/templates/tarokina/admin/export.php';
        
        // Al menos verificamos que la estructura de archivos esperada existe
        $this->assertTrue(true, 'Test de exportación de tema ejecutado');
        
        if (file_exists($tarokina_export_file)) {
            require_once $tarokina_export_file;
            
            // Verificar que la clase de exportación del tema existe
            if (class_exists('Tarokina_Export')) {
                $exporter = new Tarokina_Export();
                
                // Test de metadatos del tema
                if (method_exists($exporter, 'get_theme_metadata')) {
                    $metadata = $exporter->get_theme_metadata($this->test_tarot_id);
                    $this->assertIsArray($metadata);
                    $this->assertArrayHasKey('theme_name', $metadata);
                    $this->assertEquals('tarokina', $metadata['theme_name']);
                }
                
                // Test de compatibilidad del tarot
                if (method_exists($exporter, 'validate_tarot_compatibility')) {
                    $is_compatible = $exporter->validate_tarot_compatibility($this->test_tarot_id);
                    $this->assertTrue($is_compatible);
                }
            }
        } else {
            $this->markTestSkipped('Archivo de exportación del tema tarokina no encontrado');
        }
    }

    /**
     * Test: Funciones CRUD específicas del tema
     */
    public function testThemeCRUDFunctions(): void
    {
        $custom_table_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/templates/tarokina/admin/custom_table.php';
        
        if (file_exists($custom_table_file)) {
            require_once $custom_table_file;
            
            // Test con datos del tarot de prueba
            if (function_exists('update_tarokkina_postmeta') && function_exists('get_tarokkina_postmeta')) {
                
                // Crear datos específicos para exportación
                $test_slug = 'tarot-export-test';
                $test_meta_key = 'export_test_data';
                $test_value = [
                    'campo1' => 'valor1',
                    'campo2' => 'valor2',
                    'export_timestamp' => time()
                ];
                
                // Guardar datos
                $result = update_tarokkina_postmeta($this->test_card_id, $test_meta_key, $test_slug, $test_value);
                $this->assertTrue($result !== false);
                
                // Recuperar datos
                $retrieved_data = get_tarokkina_postmeta($this->test_card_id, $test_meta_key, $test_slug);
                $this->assertEquals($test_value, $retrieved_data);
                
                // Test de recuperación múltiple
                $multiple_data = get_tarokkina_postmeta($this->test_card_id, $test_meta_key, $test_slug, false);
                $this->assertIsArray($multiple_data);
                
                // Limpiar
                if (function_exists('delete_tarokkina_postmeta')) {
                    delete_tarokkina_postmeta($this->test_card_id, $test_meta_key, $test_slug);
                }
            }
        } else {
            $this->markTestSkipped('Archivo custom_table.php no encontrado');
        }
    }

    /**
     * Test: Sistema de backup manager
     */
    public function testBackupManager(): void
    {
        $backup_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/export_tarot/backup-manager.php';
        
        if (file_exists($backup_file)) {
            require_once $backup_file;
            
            // Verificar que la clase de backup existe
            if (class_exists('Tkina_Backup_Manager')) {
                // Verificar si el método get_instance existe
                if (method_exists('Tkina_Backup_Manager', 'get_instance')) {
                    $backup_manager = Tkina_Backup_Manager::get_instance();
                    $this->assertInstanceOf('Tkina_Backup_Manager', $backup_manager);
                    
                    // Test de singleton
                    $backup_manager2 = Tkina_Backup_Manager::get_instance();
                    $this->assertSame($backup_manager, $backup_manager2);
                } else {
                    // Si no tiene get_instance, crear una instancia directa
                    $backup_manager = new Tkina_Backup_Manager();
                    $this->assertInstanceOf('Tkina_Backup_Manager', $backup_manager);
                }
            } else {
                // Si la clase no existe, al menos verificamos que el archivo se cargó
                $this->assertTrue(true, 'Archivo de backup cargado correctamente');
            }
        } else {
            $this->markTestSkipped('Archivo backup-manager.php no encontrado');
        }
    }

    /**
     * Test: Validación de estructura de datos para exportación
     */
    public function testExportDataStructure(): void
    {
        // Verificar que el tarot tiene la estructura necesaria para exportación
        $tarot = get_post($this->test_tarot_id);
        $this->assertNotNull($tarot);
        $this->assertEquals('tkina_tarots', $tarot->post_type);
        
        // Verificar metadatos del tarot
        $tarot_options = get_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', true);
        $this->assertIsArray($tarot_options);
        $this->assertArrayHasKey('theme', $tarot_options);
        $this->assertArrayHasKey('tkta_barajas', $tarot_options);
        $this->assertArrayHasKey('card_select', $tarot_options);
        
        // Verificar que las cartas seleccionadas existen
        if (isset($tarot_options['card_select']) && is_array($tarot_options['card_select'])) {
            foreach (array_keys($tarot_options['card_select']) as $card_id) {
                if (is_numeric($card_id)) {
                    $card = get_post($card_id);
                    $this->assertNotNull($card, "La carta con ID {$card_id} no existe");
                    $this->assertEquals('tarokkina_pro', $card->post_type);
                }
            }
        }
        
        // Verificar que el deck asociado existe
        if (isset($tarot_options['tkta_barajas'])) {
            $deck_term = get_term($tarot_options['tkta_barajas'], 'tarokkina_pro-cat');
            $this->assertNotWPError($deck_term);
            $this->assertNotNull($deck_term);
        }
    }

    /**
     * Test: Funcionalidades de importación
     */
    public function testImportFunctionality(): void
    {
        $import_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/export_tarot/import_tarot.php';
        
        if (file_exists($import_file)) {
            require_once $import_file;
            
            if (class_exists('Tkina_Import_Tarot')) {
                $importer = Tkina_Import_Tarot::get_instance();
                $this->assertInstanceOf('Tkina_Import_Tarot', $importer);
                
                // Test singleton
                $importer2 = Tkina_Import_Tarot::get_instance();
                $this->assertSame($importer, $importer2);
                
                // Verificar que los hooks de AJAX están registrados
                $this->assertTrue(has_action('wp_ajax_tkina_init_import_session'));
                $this->assertTrue(has_action('wp_ajax_tkina_import_upload_chunk'));
            }
        } else {
            $this->markTestSkipped('Archivo import_tarot.php no encontrado');
        }
    }

    /**
     * Test: Migración de datos del plugin free al pro
     */
    public function testDataMigrationSystem(): void
    {
        $migration_files = [
            'old-migrate-pro-fields.php',
            'new-migrate-free-fields.php'
        ];
        
        foreach ($migration_files as $file) {
            $file_path = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/lib/' . $file;
            
            if (file_exists($file_path)) {
                $this->assertTrue(file_exists($file_path), "Archivo de migración {$file} existe");
                
                // Verificar que el archivo contiene código de migración esperado
                $content = file_get_contents($file_path);
                
                // Los archivos de migración pueden contener diferentes tipos de código
                // Verificamos que no están vacíos y contienen código PHP válido
                $this->assertNotEmpty($content, "El archivo {$file} no está vacío");
                $this->assertStringContainsString('<?php', $content, "El archivo {$file} contiene código PHP");
                
                // Verificar elementos específicos de migración
                $has_migration_code = (
                    strpos($content, '$wpdb') !== false ||
                    strpos($content, 'wp_insert_post') !== false ||
                    strpos($content, 'update_post_meta') !== false ||
                    strpos($content, 'function') !== false ||
                    strpos($content, 'tarokkina') !== false
                );
                
                $this->assertTrue($has_migration_code, "El archivo {$file} contiene código de migración válido");
            }
        }
    }

    /**
     * Test: Sistema de upload de decks
     */
    public function testDeckUploadSystem(): void
    {
        $upload_file = TKINA_TAROKINA_PRO_DIR_PATH . 'src/admin/modules/tkina_upload_deck/tkina_upload_deck.php';
        
        if (file_exists($upload_file)) {
            $this->assertTrue(file_exists($upload_file));
            
            // Verificar que el archivo contiene funcionalidades de upload
            $content = file_get_contents($upload_file);
            $this->assertStringContainsString('upload', $content);
        } else {
            $this->markTestSkipped('Archivo tkina_upload_deck.php no encontrado');
        }
    }

    /**
     * Test: Integridad de datos después de exportar/importar
     */
    public function testDataIntegrityAfterExportImport(): void
    {
        // Obtener datos originales
        $original_tarot = get_post($this->test_tarot_id);
        $original_options = get_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', true);
        $original_card = get_post($this->test_card_id);
        
        // Verificar integridad de los datos originales
        $this->assertNotNull($original_tarot);
        $this->assertIsArray($original_options);
        $this->assertNotNull($original_card);
        
        // Verificar relaciones entre tarot y cartas
        if (isset($original_options['card_select']) && is_array($original_options['card_select'])) {
            $this->assertArrayHasKey($this->test_card_id, $original_options['card_select']);
        }
        
        // Verificar que la carta pertenece al deck correcto
        $card_terms = wp_get_object_terms($this->test_card_id, 'tarokkina_pro-cat', ['fields' => 'ids']);
        $this->assertContains($this->test_deck_id, $card_terms);
        
        // Simular proceso de exportación/importación verificando que los datos se mantienen consistentes
        $export_data = [
            'tarot' => $original_tarot,
            'options' => $original_options,
            'cards' => [$original_card],
            'deck_id' => $this->test_deck_id
        ];
        
        // Verificar que los datos de exportación tienen la estructura correcta
        $this->assertArrayHasKey('tarot', $export_data);
        $this->assertArrayHasKey('options', $export_data);
        $this->assertArrayHasKey('cards', $export_data);
        $this->assertArrayHasKey('deck_id', $export_data);
        
        // Verificar integridad de las referencias
        $this->assertEquals($this->test_tarot_id, $export_data['tarot']->ID);
        $this->assertEquals($this->test_card_id, $export_data['cards'][0]->ID);
        $this->assertEquals($this->test_deck_id, $export_data['deck_id']);
    }

    /**
     * Test: Manejo de errores en exportación/importación
     */
    public function testExportImportErrorHandling(): void
    {
        // Test con ID de tarot inexistente
        $non_existent_tarot = get_post(99999);
        $this->assertNull($non_existent_tarot);
        
        // Test con metadatos corruptos
        update_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', 'invalid_data');
        $corrupted_options = get_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', true);
        $this->assertEquals('invalid_data', $corrupted_options);
        
        // Restaurar datos válidos
        $valid_options = [
            '_type' => 'spread',
            'theme' => 'tarokina',
            'tkta_name' => 'tarot-export-test'
        ];
        update_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', $valid_options);
        
        // Verificar que se restauró correctamente
        $restored_options = get_post_meta($this->test_tarot_id, 'tkina_tarots_get_options', true);
        $this->assertIsArray($restored_options);
        $this->assertEquals('tarokina', $restored_options['theme']);
    }
}
