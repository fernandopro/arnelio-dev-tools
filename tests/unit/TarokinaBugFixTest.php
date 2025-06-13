<?php
/**
 * Test que demuestra cómo usar tests para guiar modificaciones al código principal
 * 
 * @package DevTools\Tests
 */

namespace DevTools\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Exception;

class TarokinaBugFixTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        
        // Mock para sanitize_title() (función de WordPress)
        if (!function_exists('sanitize_title')) {
            function sanitize_title($title) {
                // Simulación básica de sanitize_title de WordPress
                $title = strip_tags($title);
                $title = preg_replace('/[^a-z0-9\s\-_]/i', '', $title);
                $title = preg_replace('/\s+/', '-', $title);
                $title = trim($title, '-');
                return strtolower($title);
            }
        }
    }

    /**
     * Test que reproduce un bug: función de validación de slug de tarot
     * 
     * @group bug-fix
     */
    public function test_tarot_slug_validation_bug() {
        // PROBLEMA ENCONTRADO: Los slugs de tarot permiten caracteres inválidos
        
        // Función actual simulada (como está en el código ahora - CON BUG)
        $validate_slug_buggy = function($slug) {
            // BUG: Solo hace trim, no valida caracteres especiales
            return trim($slug);
        };

        // Función corregida (como DEBERÍA estar)
        $validate_slug_fixed = function($slug) {
            $slug = trim($slug);
            // Corregir: sanitizar el slug correctamente
            $slug = sanitize_title($slug);
            // Corregir: no permitir slugs vacíos
            if (empty($slug)) {
                return false;
            }
            // Corregir: no permitir slugs muy largos - APLICAR LÍMITE DESPUÉS DE SANITIZAR
            if (strlen($slug) > 200) {
                $slug = substr($slug, 0, 200);
                // Si después del corte queda vacío, rechazar
                if (empty(trim($slug))) {
                    return false;
                }
            }
            return $slug;
        };

        // TESTS QUE FALLAN CON LA VERSIÓN BUGGY
        
        // 1. Slug con caracteres especiales
        $input_with_special_chars = "Mi Tarot <script>alert('hack')</script>";
        
        $buggy_result = $validate_slug_buggy($input_with_special_chars);
        $this->assertStringContainsString('<script>', $buggy_result, 'CONFIRMADO: versión buggy permite scripts');
        
        $fixed_result = $validate_slug_fixed($input_with_special_chars);
        $this->assertStringNotContainsString('<script>', $fixed_result, 'Versión corregida debe limpiar scripts');
        $this->assertEquals('mi-tarot-alerthack', $fixed_result, 'Debe generar slug limpio');

        // 2. Slug vacío
        $empty_input = "   ";
        $buggy_empty = $validate_slug_buggy($empty_input);
        $this->assertEquals('', $buggy_empty, 'CONFIRMADO: versión buggy permite slugs vacíos');
        
        $fixed_empty = $validate_slug_fixed($empty_input);
        $this->assertFalse($fixed_empty, 'Versión corregida debe rechazar slugs vacíos');

        // 3. Slug muy largo
        $long_input = str_repeat('a', 250);
        $buggy_long = $validate_slug_buggy($long_input);
        $this->assertEquals(250, strlen($buggy_long), 'CONFIRMADO: versión buggy permite slugs largos');
        
        $fixed_long = $validate_slug_fixed($long_input);
        $this->assertIsString($fixed_long, 'Versión corregida debe retornar string cortado');
        $this->assertEquals(200, strlen($fixed_long), 'Slug debe estar limitado a 200 caracteres');

        echo "\n🐛 Bug reproduced and fix validated: ✅";
    }

    /**
     * Test que simula una mejora en la función de conversión de barajas
     * 
     * @group enhancement
     */
    public function test_barajas_array_improvement() {
        // MEJORA: La función arr_barajas() debería cachear mejor y manejar errores
        
        // Función original simulada
        $get_barajas_original = function() {
            // Simulamos que a veces falla la consulta
            if (rand(1, 10) > 8) {
                return false; // BUG: No maneja errores
            }
            return [1 => 'Tarot Clásico', 2 => 'Tarot Marsella'];
        };

        // Función mejorada
        $get_barajas_improved = function() {
            static $cache = null;
            
            if ($cache !== null) {
                return $cache; // MEJORA: Cache estático
            }
            
            try {
                // Simulamos consulta de base de datos
                $result = [1 => 'Tarot Clásico', 2 => 'Tarot Marsella', 3 => 'Tarot Rider'];
                
                if (empty($result)) {
                    // MEJORA: Retornar default en lugar de false
                    $cache = [1 => 'Default Tarot'];
                    return $cache;
                }
                
                $cache = $result;
                return $cache;
                
            } catch (Exception $e) {
                // MEJORA: Manejo de errores
                error_log('Error getting barajas: ' . $e->getMessage());
                return [1 => 'Default Tarot'];
            }
        };

        // TESTS
        $result = $get_barajas_improved();
        $this->assertIsArray($result, 'Debe retornar siempre un array');
        $this->assertNotEmpty($result, 'Array no debe estar vacío');
        $this->assertArrayHasKey(1, $result, 'Debe tener al menos la clave 1');

        // Test de cache
        $first_call = $get_barajas_improved();
        $second_call = $get_barajas_improved();
        $this->assertEquals($first_call, $second_call, 'Cache debe funcionar');

        echo "\n🚀 Enhancement validated: ✅";
    }

    /**
     * Test de integración: verificar que los cambios funcionan juntos
     * 
     * @group integration
     */
    public function test_integrated_tarot_creation() {
        // SIMULACIÓN: Crear un tarot completo con validaciones
        
        $create_tarot = function($name, $deck_id = 1) {
            // Validar slug
            $slug = trim($name);
            $slug = sanitize_title($slug);
            
            if (empty($slug)) {
                return ['error' => 'Slug vacío no permitido'];
            }
            
            if (strlen($slug) > 200) {
                return ['error' => 'Slug muy largo'];
            }
            
            // Validar deck
            $available_decks = [1 => 'Tarot Clásico', 2 => 'Tarot Marsella', 3 => 'Tarot Rider'];
            
            if (!isset($available_decks[$deck_id])) {
                return ['error' => 'Deck no válido'];
            }
            
            // Simular creación exitosa
            return [
                'success' => true,
                'id' => rand(1000, 9999),
                'slug' => $slug,
                'name' => $name,
                'deck' => $available_decks[$deck_id],
                'created_at' => current_time('mysql')
            ];
        };

        // TESTS DE INTEGRACIÓN
        
        // 1. Creación exitosa
        $result = $create_tarot('Mi Tarot Especial', 2);
        $this->assertTrue($result['success'], 'Creación debe ser exitosa');
        $this->assertEquals('mi-tarot-especial', $result['slug'], 'Slug debe estar sanitizado');
        $this->assertEquals('Tarot Marsella', $result['deck'], 'Deck debe ser correcto');

        // 2. Error por slug inválido
        $bad_result = $create_tarot('   ', 1);
        $this->assertArrayHasKey('error', $bad_result, 'Debe retornar error');
        $this->assertStringContainsString('Slug vacío', $bad_result['error']);

        // 3. Error por deck inválido
        $bad_deck = $create_tarot('Tarot Válido', 999);
        $this->assertArrayHasKey('error', $bad_deck, 'Debe retornar error por deck');
        $this->assertStringContainsString('Deck no válido', $bad_deck['error']);

        echo "\n🔧 Integration test passed: ✅";
    }
}
