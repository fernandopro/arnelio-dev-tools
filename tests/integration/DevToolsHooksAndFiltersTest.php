<?php
/**
 * Tests de integración para Hooks y Filtros - DevTools
 * 
 * @package TarokinaPro
 * @subpackage DevTools
 * @author Tarokina Team
 * @version 1.0.0
 */

/**
 * Clase de test para funcionalidades de hooks y filtros de WordPress
 * Utiliza DevToolsTestCase que previene deadlocks durante ejecución masiva
 */
class DevToolsHooksAndFiltersTest extends DevToolsTestCase
{
    /**
     * Variable para tracking de hooks ejecutados
     */
    private $hooks_fired = [];

    /**
     * Variable para datos de filtros
     */
    private $filter_data = [];

    /**
     * Setup ejecutado antes de cada test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Resetear tracking
        $this->hooks_fired = [];
        $this->filter_data = [];
    }

    /**
     * Cleanup ejecutado después de cada test
     */
    protected function tearDown(): void
    {
        // Limpiar todos los hooks agregados durante tests
        remove_all_actions('dev_tools_test_action');
        remove_all_filters('dev_tools_test_filter');
        remove_all_actions('dev_tools_complex_action');
        remove_all_filters('dev_tools_complex_filter');
        
        parent::tearDown();
    }

    /**
     * Test básico de actions (hooks)
     */
    public function testBasicActions(): void
    {
        $action_name = 'dev_tools_test_action';
        $test_data = 'action_test_data';

        // Callback para capturar el action
        $callback = function($data) {
            $this->hooks_fired[] = $data;
        };

        // Agregar action
        add_action($action_name, $callback, 10, 1);

        // Verificar que el hook está registrado
        $this->assertTrue(has_action($action_name), 'El action debería estar registrado');

        // Ejecutar action
        do_action($action_name, $test_data);

        // Verificar que se ejecutó
        $this->assertContains($test_data, $this->hooks_fired, 'El action debería haberse ejecutado');
        $this->assertCount(1, $this->hooks_fired, 'El action debería ejecutarse exactamente una vez');
    }

    /**
     * Test básico de filters
     */
    public function testBasicFilters(): void
    {
        $filter_name = 'dev_tools_test_filter';
        $original_value = 'original_text';
        $expected_value = 'modified_text';

        // Callback para modificar el valor
        $callback = function($value) {
            return str_replace('original', 'modified', $value);
        };

        // Agregar filter
        add_filter($filter_name, $callback, 10, 1);

        // Verificar que el filter está registrado
        $this->assertTrue(has_filter($filter_name), 'El filter debería estar registrado');

        // Aplicar filter
        $result = apply_filters($filter_name, $original_value);

        // Verificar modificación
        $this->assertEquals($expected_value, $result, 'El filter debería modificar el valor');
        $this->assertNotEquals($original_value, $result, 'El valor debería haber cambiado');
    }

    /**
     * Test de actions con múltiples parámetros
     */
    public function testMultiParameterActions(): void
    {
        $action_name = 'dev_tools_complex_action';
        
        // Callback que acepta múltiples parámetros
        $callback = function($param1, $param2, $param3) {
            $this->filter_data['param1'] = $param1;
            $this->filter_data['param2'] = $param2;
            $this->filter_data['param3'] = $param3;
        };

        // Agregar action con 3 parámetros
        add_action($action_name, $callback, 10, 3);

        // Ejecutar con múltiples parámetros
        do_action($action_name, 'value1', ['array_data'], 42);

        // Verificar que todos los parámetros se recibieron
        $this->assertEquals('value1', $this->filter_data['param1']);
        $this->assertEquals(['array_data'], $this->filter_data['param2']);
        $this->assertEquals(42, $this->filter_data['param3']);
    }

    /**
     * Test de filters con múltiples callbacks
     */
    public function testMultipleFilterCallbacks(): void
    {
        $filter_name = 'dev_tools_complex_filter';
        $original_value = 'start';

        // Primer callback (prioridad 10)
        add_filter($filter_name, function($value) {
            return $value . '_first';
        }, 10);

        // Segundo callback (prioridad 5 - se ejecuta antes)
        add_filter($filter_name, function($value) {
            return $value . '_early';
        }, 5);

        // Tercer callback (prioridad 20 - se ejecuta después)
        add_filter($filter_name, function($value) {
            return $value . '_late';
        }, 20);

        // Aplicar filtros
        $result = apply_filters($filter_name, $original_value);

        // Los filtros deberían ejecutarse en orden de prioridad: 5, 10, 20
        $expected = 'start_early_first_late';
        $this->assertEquals($expected, $result, 'Los filtros deberían ejecutarse en orden de prioridad');
    }

    /**
     * Test de hooks condicionales
     */
    public function testConditionalHooks(): void
    {
        $action_name = 'dev_tools_conditional_action';
        $condition_met = false;

        // Callback condicional
        $callback = function($data) use (&$condition_met) {
            if ($data['enable'] === true) {
                $condition_met = true;
                $this->hooks_fired[] = 'conditional_executed';
            }
        };

        add_action($action_name, $callback);

        // Ejecutar con condición false
        do_action($action_name, ['enable' => false]);
        $this->assertFalse($condition_met, 'El hook condicional no debería ejecutarse');

        // Ejecutar con condición true
        do_action($action_name, ['enable' => true]);
        $this->assertTrue($condition_met, 'El hook condicional debería ejecutarse');
        $this->assertContains('conditional_executed', $this->hooks_fired);
    }

    /**
     * Test de remoción de hooks
     */
    public function testHookRemoval(): void
    {
        $action_name = 'dev_tools_removal_test';
        
        // Callback nombrado (usando closure con referencia)
        $callback = function($data) {
            $this->hooks_fired[] = $data;
        };

        // Agregar action
        add_action($action_name, $callback, 10, 1);
        $this->assertTrue(has_action($action_name), 'El action debería estar registrado');

        // Ejecutar una vez
        do_action($action_name, 'first_execution');
        $this->assertCount(1, $this->hooks_fired);

        // Remover action
        remove_action($action_name, $callback, 10);
        $this->assertFalse(has_action($action_name), 'El action debería estar removido');

        // Ejecutar nuevamente - no debería hacer nada
        do_action($action_name, 'second_execution');
        $this->assertCount(1, $this->hooks_fired, 'No deberían agregarse más ejecuciones');
    }

    /**
     * Test de hooks de WordPress existentes
     */
    public function testWordPressBuiltinHooks(): void
    {
        $wp_hook_fired = false;

        // Hook en init (aunque ya pasó, podemos testearlo)
        $callback = function() use (&$wp_hook_fired) {
            $wp_hook_fired = true;
        };

        // Agregar hook
        add_action('wp_loaded', $callback);

        // Simular la ejecución del hook
        do_action('wp_loaded');

        $this->assertTrue($wp_hook_fired, 'Los hooks nativos de WordPress deberían funcionar');
    }

    /**
     * Test de filtros con modificación de objetos
     */
    public function testObjectFilters(): void
    {
        $filter_name = 'dev_tools_object_filter';
        
        // Objeto de prueba
        $test_object = (object) [
            'name' => 'original_name',
            'value' => 100,
            'enabled' => false
        ];

        // Filter que modifica el objeto
        add_filter($filter_name, function($obj) {
            $obj->name = 'filtered_name';
            $obj->value *= 2;
            $obj->enabled = true;
            $obj->new_property = 'added_by_filter';
            return $obj;
        });

        // Aplicar filter
        $result = apply_filters($filter_name, $test_object);

        // Verificar modificaciones
        $this->assertEquals('filtered_name', $result->name);
        $this->assertEquals(200, $result->value);
        $this->assertTrue($result->enabled);
        $this->assertEquals('added_by_filter', $result->new_property);
    }

    /**
     * Test de hooks con datos complejos
     */
    public function testComplexDataHooks(): void
    {
        $action_name = 'dev_tools_complex_data_action';
        $complex_data = [
            'meta' => [
                'timestamp' => time(),
                'user_id' => 1,
                'source' => 'dev_tools_test'
            ],
            'payload' => [
                'action' => 'process_data',
                'items' => [
                    ['id' => 1, 'status' => 'pending'],
                    ['id' => 2, 'status' => 'completed']
                ]
            ],
            'config' => [
                'debug' => true,
                'timeout' => 30
            ]
        ];

        // Callback que procesa datos complejos
        $callback = function($data) {
            $this->filter_data['received_meta'] = $data['meta'];
            $this->filter_data['item_count'] = count($data['payload']['items']);
            $this->filter_data['debug_enabled'] = $data['config']['debug'];
        };

        add_action($action_name, $callback);

        // Ejecutar con datos complejos
        do_action($action_name, $complex_data);

        // Verificar procesamiento
        $this->assertEquals($complex_data['meta'], $this->filter_data['received_meta']);
        $this->assertEquals(2, $this->filter_data['item_count']);
        $this->assertTrue($this->filter_data['debug_enabled']);
    }
}
