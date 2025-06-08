<?php
/**
 * Interface para módulos de Dev Tools - Arquitectura 3.0
 * Define el contrato que deben implementar todos los módulos
 * 
 * @package DevTools\Core\Interfaces
 * @version 3.0.0
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface que deben implementar todos los módulos de dev-tools
 * Garantiza consistencia y permite carga automática de módulos
 */
interface DevToolsModuleInterface {
    
    /**
     * Obtener información del módulo
     * 
     * @return array {
     *     @type string $name        Nombre del módulo
     *     @type string $version     Versión del módulo
     *     @type string $description Descripción del módulo
     *     @type array  $dependencies Lista de dependencias requeridas
     *     @type array  $capabilities Capacidades requeridas del usuario
     * }
     */
    public function getModuleInfo(): array;
    
    /**
     * Inicializar el módulo
     * Se ejecuta durante la carga del sistema
     * 
     * @param DevToolsConfig $config Configuración del sistema
     * @return bool True si la inicialización fue exitosa
     */
    public function initialize(DevToolsConfig $config): bool;
    
    /**
     * Verificar si el módulo puede ejecutarse
     * Verifica dependencias, permisos y requisitos
     * 
     * @return bool True si el módulo puede ejecutarse
     */
    public function canRun(): bool;
    
    /**
     * Registrar hooks de WordPress
     * Se ejecuta después de la inicialización exitosa
     * 
     * @return void
     */
    public function registerHooks(): void;
    
    /**
     * Registrar comandos AJAX del módulo
     * 
     * @param DevToolsAjaxHandler $ajaxHandler Manejador AJAX del sistema
     * @return void
     */
    public function registerAjaxCommands(DevToolsAjaxHandler $ajaxHandler): void;
    
    /**
     * Obtener configuración por defecto del módulo
     * 
     * @return array Configuración por defecto
     */
    public function getDefaultConfig(): array;
    
    /**
     * Validar configuración del módulo
     * 
     * @param array $config Configuración a validar
     * @return bool True si la configuración es válida
     */
    public function validateConfig(array $config): bool;
    
    /**
     * Ejecutar cuando el módulo se activa
     * 
     * @return bool True si la activación fue exitosa
     */
    public function onActivate(): bool;
    
    /**
     * Ejecutar cuando el módulo se desactiva
     * 
     * @return bool True si la desactivación fue exitosa
     */
    public function onDeactivate(): bool;
    
    /**
     * Obtener estado actual del módulo
     * 
     * @return array {
     *     @type bool   $active      Si el módulo está activo
     *     @type bool   $initialized Si el módulo está inicializado
     *     @type string $status      Estado textual del módulo
     *     @type array  $errors      Lista de errores si los hay
     * }
     */
    public function getStatus(): array;
    
    /**
     * Limpiar recursos del módulo
     * Se ejecuta cuando el sistema se desactiva
     * 
     * @return void
     */
    public function cleanup(): void;
}
