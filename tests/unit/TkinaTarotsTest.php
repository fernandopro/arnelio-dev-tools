<?php
/**
 * Test para la clase Tkina_tarokina_tarots
 * 
 * Ejemplo de cómo usar Dev-Tools Arquitectura 3.0 para testear 
 * las funciones del plugin principal.
 * 
 * @package DevTools\Tests
 */

use PHPUnit\Framework\TestCase;

class TkinaTarotsTest extends TestCase {

    /**
     * Instancia de la clase a testear
     * @var Tkina_tarokina_tarots|null
     */
    private $tarots_instance;

    /**
     * Setup ejecutado antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Asegurar que WordPress está cargado
        if (!function_exists('add_action')) {
            $this->markTestSkipped('WordPress no está disponible en este test');
        }

        // Mock de constantes necesarias si no existen
        if (!defined('TKINA_TAROKINA_PRO_DIR_PATH')) {
            define('TKINA_TAROKINA_PRO_DIR_PATH', '/test/path/');
        }

        // NO cargar la clase completa, solo verificar que existe
        // En su lugar, usaremos reflexión y mocks para testear métodos específicos
    }

    /**
     * Test 1: Verificar que podemos acceder a los métodos de la clase
     * mediante el objeto global que ya existe
     * 
     * @group basic
     */
    public function test_class_exists() {
        // La clase ya está cargada por el sistema WordPress
        // Verificar usando el objeto global
        global $Tkina_tarokina_tarots;
        
        if (isset($Tkina_tarokina_tarots)) {
            $this->assertInstanceOf(
                'Tkina_tarokina_tarots',
                $Tkina_tarokina_tarots,
                'La instancia global debe existir'
            );
        } else {
            // Si no existe globalmente, al menos verificar que la clase está definida
            $this->assertTrue(
                class_exists('Tkina_tarokina_tarots'),
                'La clase Tkina_tarokina_tarots debe estar definida'
            );
        }
    }

    /**
     * Test 2: Probar la función tkina_convert_values_string_int()
     * Esta es una función pura, perfecta para empezar
     * 
     * @group utilities
     */
    public function test_tkina_convert_values_string_int() {
        // Verificar que la clase existe antes de testear
        if (!class_exists('Tkina_tarokina_tarots')) {
            $this->markTestSkipped('Clase Tkina_tarokina_tarots no disponible');
        }

        // PASO 1: Preparar datos de prueba individuales
        $test_cases = [
            // [input, expected_output, description]
            ['123', 123, 'String numérico entero'],
            ['123.45', 123.45, 'String numérico float'],
            ['hello', 'hello', 'String no numérico'],
            [456, 456, 'Número entero ya convertido'],
            [789.01, 789.01, 'Número float ya convertido'],
        ];

        // PASO 2: Crear método de reflexión para acceder a función privada
        $reflection = new ReflectionClass('Tkina_tarokina_tarots');
        $method = $reflection->getMethod('tkina_convert_values_string_int');
        $method->setAccessible(true);

        // PASO 3: Crear instancia temporal para llamar al método
        $instance = $this->getMockBuilder('Tkina_tarokina_tarots')
                         ->disableOriginalConstructor()
                         ->getMock();

        // PASO 4: Ejecutar tests individuales
        foreach ($test_cases as [$input, $expected, $description]) {
            $result = $method->invoke($instance, $input);
            $this->assertEquals(
                $expected, 
                $result,
                "Conversión fallida para {$description}: " . print_r($input, true)
            );
        }

        // PASO 5: Test de array complejo
        $array_input = ['number' => '100', 'text' => 'hello', 'float' => '3.14'];
        $array_expected = ['number' => 100, 'text' => 'hello', 'float' => 3.14];
        $array_result = $method->invoke($instance, $array_input);
        $this->assertEquals(
            $array_expected,
            $array_result,
            'Conversión de array con valores mixtos'
        );

        // Test adicional: verificar tipos específicos
        $string_int = $method->invoke($instance, '42');
        $this->assertIsInt($string_int, 'String "42" debe convertirse a integer');

        $string_float = $method->invoke($instance, '42.5');
        $this->assertIsFloat($string_float, 'String "42.5" debe convertirse a float');
    }

    /**
     * Test 3: Probar la función arrays_are_different()
     * 
     * @group utilities
     */
    public function test_arrays_are_different() {
        // Crear método de reflexión
        $reflection = new ReflectionClass('Tkina_tarokina_tarots');
        $method = $reflection->getMethod('arrays_are_different');
        $method->setAccessible(true);

        $instance = $this->getMockBuilder('Tkina_tarokina_tarots')
                         ->disableOriginalConstructor()
                         ->getMock();

        // Arrays idénticos
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['a' => 1, 'b' => 2];
        $result = $method->invoke($instance, $array1, $array2);
        $this->assertTrue($result, 'Arrays idénticos deben ser iguales');

        // Arrays diferentes
        $array3 = ['a' => 1, 'b' => 3];
        $result = $method->invoke($instance, $array1, $array3);
        $this->assertFalse($result, 'Arrays diferentes deben ser diferentes');

        // Arrays con orden diferente pero mismo contenido
        $array4 = ['b' => 2, 'a' => 1];
        $result = $method->invoke($instance, $array1, $array4);
        $this->assertTrue($result, 'Arrays con mismo contenido pero diferente orden deben ser iguales');
    }

    /**
     * Test 4: Probar constantes de la clase
     * 
     * @group basic
     */
    public function test_class_constants() {
        $reflection = new ReflectionClass('Tkina_tarokina_tarots');
        
        // Verificar que las constantes existen
        $this->assertTrue($reflection->hasConstant('NAME_PAGE'));
        $this->assertTrue($reflection->hasConstant('NAME_OPTION'));
        $this->assertTrue($reflection->hasConstant('ID_FORM'));

        // Verificar valores
        $this->assertEquals('tkina_tarots', $reflection->getConstant('NAME_PAGE'));
        $this->assertEquals('tkina_tarots_get_options', $reflection->getConstant('NAME_OPTION'));
        $this->assertEquals('post', $reflection->getConstant('ID_FORM'));
    }

    /**
     * Test 5: Verificar que los métodos públicos existen
     * 
     * @group basic
     */
    public function test_public_methods_exist() {
        $reflection = new ReflectionClass('Tkina_tarokina_tarots');
        
        $expected_methods = [
            'tkina_tarots',
            'add_tkina_tarots_submenu',
            'tkina_tarots_menu_position',
            'tkina_pro_filter_posts_columns',
            'tkina_pro_realestate_columns',
            'tkina_pro_realestate_column',
            'form_register_fields',
            'save_post_1_options',
            'save_post_2_theme',
            'tkina_tarots_scripts',
            'tkina_tarots_item',
            'disable_tkina_tarots_comments',
            'disable_tkina_tarots_pings'
        ];

        foreach ($expected_methods as $method_name) {
            $this->assertTrue(
                $reflection->hasMethod($method_name),
                "El método público '{$method_name}' debe existir"
            );
        }
    }

    /**
     * Cleanup después de cada test
     */
    protected function tearDown(): void {
        $this->tarots_instance = null;
        parent::tearDown();
    }
}
