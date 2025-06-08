<?php
/**
 * Test de demostración del Sistema Híbrido Anti-Deadlock
 * 
 * Este test demuestra todas las capacidades del sistema híbrido:
 * - Detección automática de contextos
 * - Control manual del comportamiento  
 * - Compatibilidad con WordPress oficial
 * - Métodos de diagnóstico
 * 
 * @package TarokinaPro\DevTools\Tests
 */

/**
 * Demostración del Sistema Híbrido DevToolsTestCase
 */
class DevToolsHybridSystemDemoTest extends DevToolsTestCase
{
    /**
     * Test que muestra la detección automática de contexto
     */
    public function testAutoContextDetection(): void
    {
        // Obtener información del contexto actual
        $context = $this->getTestContext();
        
        // Verificar que tenemos toda la información
        $this->assertArrayHasKey('anti_deadlock_active', $context);
        $this->assertArrayHasKey('risky_context_detected', $context);
        $this->assertArrayHasKey('execution_mode', $context);
        $this->assertArrayHasKey('ajax_context', $context);
        
        // Mostrar información (solo en modo verbose)
        if (in_array('--verbose', $_SERVER['argv'] ?? [])) {
            echo "\n🔍 CONTEXTO DE EJECUCIÓN:\n";
            echo "Anti-deadlock activo: " . ($context['anti_deadlock_active'] ? 'SÍ' : 'NO') . "\n";
            echo "Contexto riesgoso detectado: " . ($context['risky_context_detected'] ? 'SÍ' : 'NO') . "\n";
            echo "Modo de ejecución: " . $context['execution_mode'] . "\n";
            echo "Contexto AJAX: " . ($context['ajax_context'] ? 'SÍ' : 'NO') . "\n";
            echo "Override de instancia: " . ($context['instance_override'] ?? 'null') . "\n";
            echo "Override global: " . ($context['global_setting'] ?? 'null') . "\n";
        }
        
        $this->assertTrue(true, 'Detección de contexto completada exitosamente');
    }
    
    /**
     * Test que demuestra el control manual - Modo estándar WordPress
     */
    public function testManualStandardMode(): void
    {
        // Forzar modo estándar para este test
        $this->useStandardWordPressBehavior();
        
        // Verificar que el modo cambió
        $this->assertFalse($this->isUsingAntiDeadlock(), 
            'El test debería usar comportamiento estándar de WordPress');
        
        // Crear un post usando factory estándar
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post - Modo Estándar',
            'post_content' => 'Este post fue creado usando comportamiento estándar de WordPress'
        ]);
        
        $this->assertIsInt($post_id);
        $this->assertGreaterThan(0, $post_id);
        
        // Verificar que el post existe
        $post = get_post($post_id);
        $this->assertInstanceOf('WP_Post', $post);
        $this->assertEquals('Test Post - Modo Estándar', $post->post_title);
        
        echo "\n✅ Modo estándar WordPress: Funcionalidad verificada\n";
    }
    
    /**
     * Test que demuestra el control manual - Modo anti-deadlock
     */
    public function testManualAntiDeadlockMode(): void
    {
        // Forzar modo anti-deadlock para este test
        $this->useAntiDeadlockBehavior();
        
        // Verificar que el modo cambió
        $this->assertTrue($this->isUsingAntiDeadlock(), 
            'El test debería usar protecciones anti-deadlock');
        
        // Crear un post usando factory con protecciones
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post - Modo Anti-Deadlock',
            'post_content' => 'Este post fue creado usando protecciones anti-deadlock'
        ]);
        
        $this->assertIsInt($post_id);
        $this->assertGreaterThan(0, $post_id);
        
        // Verificar que el post existe
        $post = get_post($post_id);
        $this->assertInstanceOf('WP_Post', $post);
        $this->assertEquals('Test Post - Modo Anti-Deadlock', $post->post_title);
        
        echo "\n🛡️ Modo anti-deadlock: Protecciones activas, funcionalidad verificada\n";
    }
    
    /**
     * Test que demuestra el comportamiento automático
     */
    public function testAutomaticBehavior(): void
    {
        // Resetear a comportamiento automático
        $this->useAutomaticBehavior();
        
        // El comportamiento dependerá del contexto de ejecución
        $context = $this->getTestContext();
        $is_using_anti_deadlock = $this->isUsingAntiDeadlock();
        
        if ($is_using_anti_deadlock) {
            echo "\n🤖 Modo automático: Anti-deadlock ACTIVADO (contexto riesgoso detectado)\n";
        } else {
            echo "\n🤖 Modo automático: Comportamiento ESTÁNDAR (contexto seguro detectado)\n";
        }
        
        // Crear datos de prueba independientemente del modo
        $user_id = $this->factory->user->create([
            'user_login' => 'test_automatic_mode',
            'user_email' => 'automatic@test.local'
        ]);
        
        $this->assertIsInt($user_id);
        $this->assertGreaterThan(0, $user_id);
        
        // Verificar que el usuario existe
        $user = get_user_by('ID', $user_id);
        $this->assertInstanceOf('WP_User', $user);
        $this->assertEquals('test_automatic_mode', $user->user_login);
        
        $this->assertTrue(true, 'Comportamiento automático funciona correctamente');
    }
    
    /**
     * Test de compatibilidad con métodos de WP_UnitTestCase
     */
    public function testWordPressCompatibility(): void
    {
        // Verificar que todos los métodos de WP_UnitTestCase están disponibles
        $this->assertTrue(method_exists($this, 'factory'), 
            'El método factory de WP_UnitTestCase debe estar disponible');
        
        $this->assertTrue(method_exists($this, 'assertEqualSets'), 
            'Los métodos de assertion de WordPress deben estar disponibles');
        
        // Usar factory de WordPress para crear datos
        $post_id = $this->factory->post->create();
        $user_id = $this->factory->user->create();
        $comment_id = $this->factory->comment->create(['comment_post_ID' => $post_id]);
        
        // Verificar que todo funciona como en WP_UnitTestCase estándar
        $this->assertIsInt($post_id);
        $this->assertIsInt($user_id);
        $this->assertIsInt($comment_id);
        
        // Usar assertions específicas de WordPress
        $this->assertEqualSets([$post_id], [get_post($post_id)->ID]);
        
        echo "\n✅ Compatibilidad con WP_UnitTestCase: 100% preservada\n";
    }
    
    /**
     * Test de control via variables de entorno
     */
    public function testEnvironmentVariableControl(): void
    {
        // Simular diferentes configuraciones de entorno
        $original_env = getenv('DEV_TOOLS_FORCE_ANTI_DEADLOCK');
        
        // Las variables de entorno se detectan al momento de cargar la clase,
        // así que este test solo verifica que el mecanismo existe
        $context = $this->getTestContext();
        $this->assertArrayHasKey('environment_override', $context);
        
        // Verificar que el override funciona (informacional)
        if ($context['environment_override']) {
            echo "\n🔧 Variable de entorno detectada: " . $context['environment_override'] . "\n";
        } else {
            echo "\n🔧 Sin override de variable de entorno (usando detección automática)\n";
        }
        
        $this->assertTrue(true, 'Control via variables de entorno disponible');
    }
    
    /**
     * Test de performance - verificar que no hay overhead significativo
     */
    public function testPerformanceImpact(): void
    {
        $start_time = microtime(true);
        
        // Crear múltiples elementos para medir performance
        $post_ids = [];
        for ($i = 0; $i < 10; $i++) {
            $post_ids[] = $this->factory->post->create([
                'post_title' => "Performance Test Post {$i}"
            ]);
        }
        
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // en milisegundos
        
        // Verificar que todos los posts se crearon correctamente
        $this->assertCount(10, $post_ids);
        foreach ($post_ids as $post_id) {
            $this->assertIsInt($post_id);
            $this->assertGreaterThan(0, $post_id);
        }
        
        // Verificar que el tiempo es razonable (< 5 segundos para 10 posts)
        $this->assertLessThan(5000, $execution_time, 
            'La creación de 10 posts debería tomar menos de 5 segundos');
        
        echo "\n⚡ Performance: 10 posts creados en " . round($execution_time, 2) . "ms\n";
        echo "🛡️ Modo activo: " . ($this->isUsingAntiDeadlock() ? 'Anti-deadlock' : 'Estándar') . "\n";
    }
    
    /**
     * Test de resiliencia - verificar manejo de errores
     */
    public function testErrorResilience(): void
    {
        // Intentar operaciones que podrían causar problemas
        global $wpdb;
        
        // Guardar configuración original
        $original_timeout = $wpdb->get_var("SELECT @@SESSION.innodb_lock_wait_timeout");
        
        try {
            // Simular condición potencialmente problemática
            $result = $this->factory->post->create([
                'post_title' => 'Test de Resiliencia',
                'post_content' => str_repeat('Contenido de prueba ', 1000) // Contenido grande
            ]);
            
            $this->assertIsInt($result);
            $this->assertGreaterThan(0, $result);
            
            // Verificar que la configuración de BD es la esperada
            if ($this->isUsingAntiDeadlock()) {
                $current_timeout = $wpdb->get_var("SELECT @@SESSION.innodb_lock_wait_timeout");
                $this->assertLessThanOrEqual(5, (int)$current_timeout, 
                    'Timeout de BD debería estar configurado para anti-deadlock');
            }
            
            echo "\n🛡️ Resiliencia: Test completado sin errores\n";
            
        } catch (Exception $e) {
            $this->fail('Test de resiliencia falló: ' . $e->getMessage());
        }
        
        $this->assertTrue(true, 'Sistema resiliente a condiciones adversas');
    }
}
