<?php
/**
 * Tests de integración para Base de Datos y Transients - DevTools
 * 
 * @package TarokinaPro
 * @subpackage DevTools
 * @author Tarokina Team
 * @version 1.0.0
 */

/**
 * Clase de test para funcionalidades de base de datos y transients
 * Utiliza DevToolsTestCase que previene deadlocks durante ejecución masiva
 */
class DevToolsDatabaseAndTransientsTest extends DevToolsTestCase
{
    private $verbose_mode = false;
    private $test_counter = 0;
    
    /**
     * Setup ejecutado antes de cada test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Detectar modo verbose
        $this->verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
        $this->test_counter++;
        
        // ANTI-DEADLOCK: Configurar isolation level para evitar deadlocks
        global $wpdb;
        $wpdb->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
        
        // Limpiar transients antes de cada test
        delete_transient('dev_tools_test_transient');
        delete_transient('dev_tools_cache_test');
        delete_site_transient('dev_tools_network_test');
        
        // Limpiar caché de objeto si está disponible
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Cleanup ejecutado después de cada test
     */
    protected function tearDown(): void
    {
        // ANTI-DEADLOCK: Realizar commit para liberar locks
        global $wpdb;
        $wpdb->query("COMMIT");
        
        // Limpiar transients después de cada test
        delete_transient('dev_tools_test_transient');
        delete_transient('dev_tools_cache_test');
        delete_site_transient('dev_tools_network_test');
        
        // Limpiar caché de objeto si está disponible
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        parent::tearDown();
    }

    /**
     * Test de funcionalidad básica de transients
     */
    public function testTransientBasicFunctionality(): void
    {
        $this->displayTestHeader("Funcionalidad Básica de Transients");
        
        // Configurar transient
        $key = 'dev_tools_test_transient';
        $value = ['status' => 'success', 'data' => 'test_data'];
        $expiration = 3600; // 1 hora

        $this->logAction("Configurando transient", [
            'key' => $key,
            'expiration' => $expiration . ' segundos',
            'data_type' => 'array'
        ]);

        // Establecer transient
        $set_result = set_transient($key, $value, $expiration);
        $this->assertTrue($set_result, 'set_transient debería retornar true');
        $this->logResult("set_transient", $set_result ? "✅ SUCCESS" : "❌ FAILED");

        // Obtener transient
        $retrieved_value = get_transient($key);
        $this->assertEquals($value, $retrieved_value, 'El valor del transient debería coincidir');
        $this->logResult("get_transient", $retrieved_value ? "✅ RETRIEVED" : "❌ NOT FOUND");

        // Verificar que existe
        $this->assertNotFalse($retrieved_value, 'get_transient no debería retornar false');

        // Eliminar transient
        $delete_result = delete_transient($key);
        $this->assertTrue($delete_result, 'delete_transient debería retornar true');
        $this->logResult("delete_transient", $delete_result ? "✅ DELETED" : "❌ FAILED");

        // Verificar que fue eliminado
        $deleted_value = get_transient($key);
        $this->assertFalse($deleted_value, 'El transient eliminado debería retornar false');
        $this->logResult("verification", $deleted_value === false ? "✅ CONFIRMED DELETED" : "❌ STILL EXISTS");
        
        $this->displayTestSummary("Basic Transients", 5);
    }

    /**
     * Test de transients con datos complejos
     */
    public function testTransientComplexData(): void
    {
        $this->displayTestHeader("Transients con Datos Complejos");
        
        $complex_data = [
            'meta' => [
                'timestamp' => time(),
                'version' => '1.0.0'
            ],
            'results' => [
                ['id' => 1, 'status' => 'active'],
                ['id' => 2, 'status' => 'inactive']
            ],
            'settings' => [
                'enabled' => true,
                'debug' => false,
                'cache_duration' => 3600
            ]
        ];

        $this->logAction("Configurando datos complejos", [
            'keys' => array_keys($complex_data),
            'total_items' => count($complex_data['results']),
            'data_size' => strlen(serialize($complex_data)) . ' bytes'
        ]);

        // Establecer transient con datos complejos
        set_transient('dev_tools_cache_test', $complex_data, 1800);
        $this->logResult("set_complex_transient", "✅ STORED");

        // Recuperar y verificar estructura
        $retrieved = get_transient('dev_tools_cache_test');
        
        $this->assertIsArray($retrieved, 'Los datos complejos deberían ser un array');
        $this->assertArrayHasKey('meta', $retrieved, 'Debería contener clave meta');
        $this->assertArrayHasKey('results', $retrieved, 'Debería contener clave results');
        $this->assertArrayHasKey('settings', $retrieved, 'Debería contener clave settings');
        
        // Verificar datos específicos
        $this->assertEquals('1.0.0', $retrieved['meta']['version']);
        $this->assertTrue($retrieved['settings']['enabled']);
        $this->assertCount(2, $retrieved['results']);
        
        $this->logResult("data_integrity", "✅ ALL KEYS VERIFIED");
        $this->logResult("version_check", $retrieved['meta']['version']);
        $this->logResult("results_count", count($retrieved['results']) . " items");
        
        $this->displayTestSummary("Complex Data Transients", 6);
    }

    /**
     * Test de site transients (multisite)
     */
    public function testSiteTransients(): void
    {
        $this->displayTestHeader("Site Transients (Multisite)");
        
        $network_data = [
            'network_id' => 1,
            'sites' => ['site1.test', 'site2.test'],
            'global_settings' => ['feature_x' => true]
        ];

        $this->logAction("Configurando site transient", [
            'network_id' => $network_data['network_id'],
            'sites_count' => count($network_data['sites']),
            'has_settings' => !empty($network_data['global_settings'])
        ]);

        // Site transients (para multisite/network)
        set_site_transient('dev_tools_network_test', $network_data, 7200);
        $this->logResult("set_site_transient", "✅ STORED");
        
        $retrieved = get_site_transient('dev_tools_network_test');
        $this->assertEquals($network_data, $retrieved, 'Los site transients deberían funcionar correctamente');
        $this->logResult("get_site_transient", $retrieved ? "✅ RETRIEVED" : "❌ NOT FOUND");
        
        // Eliminar site transient
        delete_site_transient('dev_tools_network_test');
        $deleted_check = get_site_transient('dev_tools_network_test');
        $this->assertFalse($deleted_check);
        $this->logResult("delete_site_transient", $deleted_check === false ? "✅ DELETED" : "❌ STILL EXISTS");
        
        $this->displayTestSummary("Site Transients", 2);
    }

    /**
     * Test de opciones de WordPress
     */
    public function testWordPressOptions(): void
    {
        $this->displayTestHeader("WordPress Options API");
        
        $option_name = 'dev_tools_test_option';
        $option_value = [
            'feature_enabled' => true,
            'last_updated' => current_time('mysql'),
            'settings' => [
                'mode' => 'development',
                'debug_level' => 2
            ]
        ];

        $this->logAction("Configurando WordPress option", [
            'option_name' => $option_name,
            'keys' => array_keys($option_value),
            'mode' => $option_value['settings']['mode']
        ]);

        // Agregar opción
        $add_result = add_option($option_name, $option_value);
        $this->assertTrue($add_result, 'add_option debería retornar true para nueva opción');
        $this->logResult("add_option", "✅ ADDED");

        // Obtener opción
        $retrieved = get_option($option_name);
        $this->assertEquals($option_value, $retrieved, 'get_option debería retornar el valor correcto');
        $this->logResult("get_option", "✅ RETRIEVED with all data");

        // Actualizar opción
        $option_value['settings']['debug_level'] = 3;
        $update_result = update_option($option_name, $option_value);
        $this->assertTrue($update_result, 'update_option debería retornar true');
        $this->logResult("update_option", "✅ debug_level changed to 3");

        // Verificar actualización
        $updated = get_option($option_name);
        $this->assertEquals(3, $updated['settings']['debug_level']);
        $this->logResult("verify_update", "✅ debug_level = {$updated['settings']['debug_level']}");

        // Eliminar opción
        $delete_result = delete_option($option_name);
        $this->assertTrue($delete_result, 'delete_option debería retornar true');
        $this->logResult("delete_option", "✅ DELETED");

        // Verificar eliminación (con valor por defecto)
        $default_value = 'not_found';
        $deleted = get_option($option_name, $default_value);
        $this->assertEquals($default_value, $deleted);
        $this->logResult("verify_deletion", "✅ returns default: {$default_value}");
        
        $this->displayTestSummary("WordPress Options", 6);
    }

    /**
     * Test de acceso directo a base de datos
     */
    public function testDirectDatabaseAccess(): void
    {
        $this->displayTestHeader("Acceso Directo a Base de Datos");
        
        global $wpdb;

        // Verificar que tenemos acceso a $wpdb
        $this->assertInstanceOf('wpdb', $wpdb, '$wpdb debería estar disponible');
        $this->logAction("Verificando acceso a \$wpdb", [
            'class' => get_class($wpdb),
            'prefix' => $wpdb->prefix,
            'db_name' => $wpdb->dbname
        ]);

        // Test de consulta básica
        $table_name = $wpdb->prefix . 'options';
        $query = $wpdb->prepare("SELECT option_name FROM {$table_name} WHERE option_name = %s", 'blogname');
        $result = $wpdb->get_var($query);
        
        $this->assertNotNull($result, 'La consulta debería retornar resultado');
        $this->assertEquals('blogname', $result);
        $this->logResult("basic_query", "✅ blogname option found");

        // Test de inserción y eliminación (usando tabla temporal)
        $test_option = 'dev_tools_db_test_' . time();
        $test_value = 'test_value_' . rand(1000, 9999);
        
        $this->logAction("Testing CRUD operations", [
            'option_name' => $test_option,
            'test_value' => $test_value
        ]);
        
        // Insertar usando WordPress API (más seguro)
        add_option($test_option, $test_value);
        $this->logResult("add_option", "✅ INSERTED");
        
        // Verificar con consulta directa
        $direct_query = $wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            $test_option
        );
        $direct_result = $wpdb->get_var($direct_query);
        
        $this->assertEquals($test_value, $direct_result, 'El acceso directo a BD debería funcionar');
        $this->logResult("direct_query_verification", "✅ VERIFIED via \$wpdb");
        
        // Limpiar
        delete_option($test_option);
        $this->logResult("cleanup", "✅ DELETED");
        
        $this->displayTestSummary("Database Access", 3);
    }

    /**
     * Test de metadata de posts
     */
    public function testPostMetadata(): void
    {
        $this->displayTestHeader("Post Metadata Management");
        
        // Crear post de prueba usando factory
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post for DevTools',
            'post_content' => 'Content for testing metadata',
            'post_status' => 'publish'
        ]);

        $this->assertIsInt($post_id, 'El post debería crearse correctamente');
        $this->assertGreaterThan(0, $post_id);
        
        $this->logAction("Post creado con WordPress Factory", [
            'post_id' => $post_id,
            'post_title' => 'Test Post for DevTools',
            'post_status' => 'publish'
        ]);

        // Agregar metadata
        $meta_key = 'dev_tools_test_meta';
        $meta_value = ['config' => 'test', 'enabled' => true];
        
        $meta_result = add_post_meta($post_id, $meta_key, $meta_value);
        $this->assertIsInt($meta_result, 'add_post_meta debería retornar meta_id');
        $this->logResult("add_post_meta", "✅ meta_id: {$meta_result}");

        // Obtener metadata
        $retrieved_meta = get_post_meta($post_id, $meta_key, true);
        $this->assertEquals($meta_value, $retrieved_meta);
        $this->logResult("get_post_meta", "✅ RETRIEVED correctly");

        // Actualizar metadata
        $new_value = ['config' => 'updated', 'enabled' => false];
        $update_result = update_post_meta($post_id, $meta_key, $new_value);
        $this->assertTrue($update_result, 'update_post_meta debería retornar true');
        $this->logResult("update_post_meta", "✅ UPDATED");

        // Verificar actualización
        $updated_meta = get_post_meta($post_id, $meta_key, true);
        $this->assertEquals($new_value, $updated_meta);
        $this->logResult("verify_update", "✅ config=updated, enabled=false");

        // Eliminar metadata
        $delete_result = delete_post_meta($post_id, $meta_key);
        $this->assertTrue($delete_result, 'delete_post_meta debería retornar true');
        $this->logResult("delete_post_meta", "✅ DELETED");
        
        $this->displayTestSummary("Post Metadata", 7);

        // El post se limpia automáticamente al final del test
    }
    
    /**
     * Mostrar header de test con información descriptiva
     */
    private function displayTestHeader(string $test_name): void
    {
        if (!$this->verbose_mode) return;
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🧪 TEST #{$this->test_counter}: {$test_name}\n";
        echo str_repeat("=", 70) . "\n";
    }
    
    /**
     * Log de acción específica
     */
    private function logAction(string $action, array $details = []): void
    {
        if (!$this->verbose_mode) return;
        
        echo "📋 {$action}\n";
        if (!empty($details)) {
            foreach ($details as $key => $value) {
                $display_value = is_array($value) ? implode(', ', $value) : $value;
                echo "   └─ {$key}: {$display_value}\n";
            }
        }
    }
    
    /**
     * Log de resultado de operación
     */
    private function logResult(string $operation, string $result): void
    {
        if (!$this->verbose_mode) return;
        
        echo "   🔍 {$operation}: {$result}\n";
    }
    
    /**
     * Mostrar resumen del test
     */
    private function displayTestSummary(string $test_name, int $assertions): void
    {
        if (!$this->verbose_mode) return;
        
        echo "📊 RESUMEN {$test_name}: {$assertions} assertions ejecutadas ✅\n";
        echo str_repeat("-", 70) . "\n";
    }
}
