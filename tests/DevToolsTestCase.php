<?php
/**
 * Clase Base de Testing - Dev-Tools Arquitectura 3.0
 * 
 * Clase base PLUGIN-AGNÓSTICA para tests de Dev-Tools.
 * Extiende WP_UnitTestCase con funcionalidades específicas para la Arquitectura 3.0.
 * 
 * Características:
 * - 100% independiente del plugin host
 * 
 * @package DevTools\Tests
 * @since Arquitectura 3.0
 * @author Dev-Tools Team
 */

if (!class_exists('DevToolsTestCase')) {

    class DevToolsTestCase extends WP_UnitTestCase 
    {}
        
}
