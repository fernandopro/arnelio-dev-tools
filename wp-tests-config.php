<?php
/**
 * WordPress Test Configuration File
 * 
 * Configuración oficial para WordPress PHPUnit Test Suite
 * Compatible con Local by WP Engine usando DatabaseConnectionModule
 * 
 * @package DevTools
 * @subpackage Tests
 * @since 3.0.0
 * @version 3.0.0
 * 
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */

// =============================================================================
// CONFIGURACIÓN USANDO DATABASE CONNECTION MODULE
// =============================================================================

// Cargar el DatabaseConnectionModule para obtener configuración automática
require_once dirname(__FILE__) . '/modules/DatabaseConnectionModule.php';

// Crear instancia del módulo de conexión
$db_module = new DatabaseConnectionModule(true); // Debug habilitado para tests
$env_info = $db_module->get_environment_info();

// =============================================================================
// CONFIGURACIÓN DE BASE DE DATOS PARA TESTS
// =============================================================================

// Usar la MISMA base de datos de WordPress pero con prefijo diferente ('test_')
