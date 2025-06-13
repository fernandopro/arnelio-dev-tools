<?php
/**
 * Test para verificar qué clases de WordPress están disponibles
 */

require_once dirname(__DIR__) . '/includes/TestCase.php';

class WordPressClassTest extends DevToolsTestCase {


    
    /**
     * Verificar disponibilidad de clases de WordPress
     */
    public function test_wordpress_classes_availability() {
        $wp_classes = [
            'WP_User',
            'WP_Post',
            'WP_Query',
            'wpdb',
            'WP_Error',
            'WP_HTTP',
            'WP_Filesystem',
            'WP_Ajax_Response'
        ];
        
        $available_classes = [];
        $missing_classes = [];
        
        foreach ($wp_classes as $class) {
            if (class_exists($class)) {
                $available_classes[] = $class;
            } else {
                $missing_classes[] = $class;
            }
        }
        
        // Mostrar información
        if (!empty($available_classes)) {
            $this->addToAssertionCount(1); // Evitar "risky test"
            echo "\n✅ Clases disponibles:\n";
            foreach ($available_classes as $class) {
                echo "   - {$class}\n";
            }
        }
        
        if (!empty($missing_classes)) {
            echo "\n❌ Clases NO disponibles:\n";
            foreach ($missing_classes as $class) {
                echo "   - {$class}\n";
            }
        }
        
        // Verificar que al menos wpdb esté disponible (lo más básico)
        $this->assertTrue(class_exists('wpdb'), 'wpdb debería estar disponible en tests');
    }
    
    /**
     * Test para verificar funciones globales de WordPress
     */
    public function test_wordpress_functions_availability() {
        $wp_functions = [
            'wp_insert_user',
            'get_user_by',
            'wp_set_current_user',
            'is_user_logged_in',
            'current_user_can',
            'add_action',
            'do_action',
            'apply_filters'
        ];
        
        $available_functions = [];
        $missing_functions = [];
        
        foreach ($wp_functions as $function) {
            if (function_exists($function)) {
                $available_functions[] = $function;
            } else {
                $missing_functions[] = $function;
            }
        }
        
        if (!empty($available_functions)) {
            echo "\n✅ Funciones disponibles:\n";
            foreach ($available_functions as $function) {
                echo "   - {$function}()\n";
            }
        }
        
        if (!empty($missing_functions)) {
            echo "\n❌ Funciones NO disponibles:\n";
            foreach ($missing_functions as $function) {
                echo "   - {$function}()\n";
            }
        }
        
        // Verificar que al menos add_action esté disponible
        $this->assertTrue(function_exists('add_action'), 'add_action() debería estar disponible');
    }
}
