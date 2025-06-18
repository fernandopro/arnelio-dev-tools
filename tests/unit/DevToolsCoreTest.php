<?php
/**
 * Dev-Tools Core System Test
 * 
 * Verifica que el sistema central de Dev-Tools funciona correctamente
 * 
 * @package DevTools
 * @subpackage Tests
 */

class DevToolsCoreTest extends WP_UnitTestCase {

    /**
     * Test que verifica que la clase DevToolsConfig existe y es accesible
     */
    public function test_devtools_config_class_exists() {
        $this->assertTrue(
            class_exists('DevToolsConfig'),
            'La clase DevToolsConfig debe existir para que el sistema funcione'
        );
    }

    /**
     * Test que verifica que el loader principal existe
     */
    public function test_devtools_loader_file_exists() {
        $loader_path = dirname(dirname(__DIR__)) . '/loader.php';
        $this->assertFileExists(
            $loader_path,
            'El archivo loader.php debe existir en el directorio dev-tools'
        );
    }

    /**
     * Test que verifica que el directorio de módulos existe
     */
    public function test_modules_directory_exists() {
        $modules_path = dirname(dirname(__DIR__)) . '/modules';
        $this->assertDirectoryExists(
            $modules_path,
            'El directorio modules debe existir para cargar los módulos'
        );
    }

    /**
     * Test que verifica que el sistema AJAX está disponible
     */
    public function test_ajax_handler_class_exists() {
        // Cargar el loader si no está cargado
        $loader_path = dirname(dirname(__DIR__)) . '/loader.php';
        if (file_exists($loader_path)) {
            require_once $loader_path;
        }

        $this->assertTrue(
            class_exists('DevToolsAjaxHandler'),
            'La clase DevToolsAjaxHandler debe existir para manejar peticiones AJAX'
        );
    }

    /**
     * Test que verifica que el archivo de configuración tiene la estructura correcta
     */
    public function test_config_file_structure() {
        $config_path = dirname(dirname(__DIR__)) . '/config/config.php';
        
        if (file_exists($config_path)) {
            $config_data = include $config_path;
            
            $this->assertIsArray(
                $config_data,
                'El archivo de configuración debe retornar un array'
            );
            
            $this->assertArrayHasKey(
                'paths',
                $config_data,
                'La configuración debe tener una sección de paths'
            );
        } else {
            $this->markTestSkipped('Archivo de configuración no encontrado');
        }
    }

    /**
     * Test que verifica que WordPress hooks están disponibles
     */
    public function test_wordpress_hooks_available() {
        $this->assertTrue(
            function_exists('add_action'),
            'La función add_action de WordPress debe estar disponible'
        );
        
        $this->assertTrue(
            function_exists('wp_enqueue_script'),
            'La función wp_enqueue_script debe estar disponible para cargar assets'
        );
    }

    /**
     * Test de integración básica del sistema
     */
    public function test_basic_system_integration() {
        // Simular la carga del sistema
        $loader_path = dirname(dirname(__DIR__)) . '/loader.php';
        
        if (file_exists($loader_path)) {
            // Capturar cualquier output para evitar problemas en tests
            ob_start();
            
            try {
                require_once $loader_path;
                $output = ob_get_clean();
                
                // Si llegamos aquí sin errores, el sistema se carga correctamente
                $this->assertTrue(true, 'El sistema Dev-Tools se carga sin errores');
                
            } catch (Exception $e) {
                ob_get_clean();
                $this->fail('Error al cargar el sistema Dev-Tools: ' . $e->getMessage());
            }
        } else {
            $this->markTestSkipped('Archivo loader.php no encontrado');
        }
    }
}
