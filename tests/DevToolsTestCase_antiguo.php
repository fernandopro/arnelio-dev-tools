<?php

/**
 * Clase base de test HÍBRIDA con protección anti-deadlock INTELIGENTE
 * 
 * FILOSOFÍA HÍBRIDA:
 * ✅ Mantiene 100% compatibilidad con WP_UnitTestCase oficial de WordPress
 * ✅ Añade protecciones anti-deadlock SOLO cuando es necesario  
 * ✅ Detecta automáticamente contextos problemáticos vs. seguros
 * ✅ Permite override manual del comportamiento en tests específicos
 * ✅ No afecta futuras actualizaciones de WordPress PHPUnit
 * ✅ Fácil migración de vuelta a WP_UnitTestCase estándar
 * 
 * CONTROL GRANULAR:
 * - Variables de entorno: DEV_TOOLS_FORCE_ANTI_DEADLOCK=1/0
 * - Constantes PHP: DEV_TOOLS_DISABLE_ANTI_DEADLOCK=true/false (definida en bootstrap.php)
 * - Constantes PHP: DEV_TOOLS_FORCE_ANTI_DEADLOCK=true/false (definida en bootstrap.php)
 * - Métodos de instancia: useStandardWordPressBehavior() / useAntiDeadlockBehavior()
 * 
 * EJEMPLOS DE USO:
 * 
 * // Test estándar (automático)
 * class MyTest extends DevToolsTestCase {
 *     public function testSomething() { ... }
 * }
 * 
 * // Forzar comportamiento WordPress estándar
 * class MyStandardTest extends DevToolsTestCase {
 *     protected function setUp(): void {
 *         $this->useStandardWordPressBehavior();
 *         parent::setUp();
 *     }
 * }
 * 
 * // Forzar protecciones anti-deadlock
 * class MyProblematicTest extends DevToolsTestCase {
 *     protected function setUp(): void {
 *         $this->useAntiDeadlockBehavior();
 *         parent::setUp();
 *     }
 * }
 * 
 * @package TarokinaPro\DevTools\Tests
 * @extends WP_UnitTestCase (framework oficial de WordPress)
 */

if (!class_exists('DevToolsTestCase')) {

    class DevToolsTestCase extends WP_UnitTestCase 
    {
        // =====================================================================
        // PROPIEDADES DE CONFIGURACIÓN
        // =====================================================================
        
        /** @var bool|null Cache de decisión para protecciones anti-deadlock */
        private static $use_anti_deadlock = null;
        
        /** @var bool|null Override temporal para tests específicos */
        private $instance_override = null;
        
        /** @var array Cache de configuración de base de datos */
        private static $db_config_cache = [];
        
        // =====================================================================
        // MÉTODOS PRINCIPALES DE INTERCEPTACIÓN
        // =====================================================================
        
        /**
         * Override CONDICIONAL de tearDownAfterClass
         * Decide dinámicamente entre comportamiento estándar vs. anti-deadlock
         */
        public static function tearDownAfterClass(): void 
        {
            if (self::shouldUseAntiDeadlock()) {
                // MODO SEGURO: Usar protecciones anti-deadlock
                self::safeDeleteAllData();
                
                // Ejecutar resto de limpieza estándar (sin _delete_all_data)
                self::flush_cache();
                
                // Limpiar transients específicos
                if (function_exists('delete_transient')) {
                    delete_transient('doing_cron');
                }
                
                // Restaurar configuración de BD original
                self::restoreDbConfiguration();
                
            } else {
                // MODO ESTÁNDAR: Comportamiento 100% oficial de WordPress
                parent::tearDownAfterClass();
            }
        }
        
        /**
         * Setup con protecciones CONDICIONALES
         * Solo aplica configuraciones anti-deadlock cuando es necesario
         */
        protected function setUp(): void 
        {
            parent::setUp();
            
            // Aplicar configuraciones anti-deadlock solo si es necesario
            if ($this->shouldUseAntiDeadlockForInstance()) {
                $this->configureAntiDeadlockDatabase();
            }
        }
        
        /**
         * TearDown con limpieza CONDICIONAL
         */
        protected function tearDown(): void 
        {
            if ($this->shouldUseAntiDeadlockForInstance()) {
                // Limpieza suave sin operaciones que causen deadlock
                $this->cleanupTestDataSafely();
            }
            
            parent::tearDown();
        }
        
        // =====================================================================
        // MÉTODOS DE CONTROL MANUAL
        // =====================================================================
        
        /**
         * Forzar uso de comportamiento estándar de WordPress
         * Para tests que sabemos que son seguros
         */
        public function useStandardWordPressBehavior(): void
        {
            $this->instance_override = false;
        }
        
        /**
         * Forzar uso de protecciones anti-deadlock
         * Para tests problemáticos o entornos de masa
         */
        public function useAntiDeadlockBehavior(): void
        {
            $this->instance_override = true;
        }
        
        /**
         * Resetear a comportamiento automático (detección inteligente)
         */
        public function useAutomaticBehavior(): void
        {
            $this->instance_override = null;
        }
        
        // =====================================================================
        // LÓGICA DE DETECCIÓN INTELIGENTE
        // =====================================================================
        
        /**
         * Detecta si se deben usar protecciones anti-deadlock (nivel clase)
         * 
         * @return bool
         */
        private static function shouldUseAntiDeadlock(): bool
        {
            // Cache la decisión para evitar múltiples evaluaciones
            if (self::$use_anti_deadlock !== null) {
                return self::$use_anti_deadlock;
            }
            
            // 1. Override por constante PHP (prioridad alta)
            // Esta constante se define en bootstrap.php
            if (defined('DEV_TOOLS_DISABLE_ANTI_DEADLOCK') && DEV_TOOLS_DISABLE_ANTI_DEADLOCK === true) {
                return self::$use_anti_deadlock = false;
            }
            
            // 2. Override forzado por constante PHP (prioridad alta)
            if (defined('DEV_TOOLS_FORCE_ANTI_DEADLOCK') && DEV_TOOLS_FORCE_ANTI_DEADLOCK === true) {
                return self::$use_anti_deadlock = true;
            }
            
            // 3. Override por variable de entorno (prioridad alta)
            $env_force = getenv('DEV_TOOLS_FORCE_ANTI_DEADLOCK');
            if ($env_force !== false) {
                return self::$use_anti_deadlock = ($env_force === '1' || $env_force === 'true');
            }
            
            // 4. Detección automática de contextos problemáticos
            $risky_context = self::detectRiskyExecutionContext();
            
            return self::$use_anti_deadlock = $risky_context;
        }
        
        /**
         * Detecta si se deben usar protecciones anti-deadlock (nivel instancia)
         * 
         * @return bool
         */
        private function shouldUseAntiDeadlockForInstance(): bool
        {
            // 1. Override manual de instancia tiene prioridad máxima
            if ($this->instance_override !== null) {
                return $this->instance_override;
            }
            
            // 2. Usar decisión de clase
            return self::shouldUseAntiDeadlock();
        }
        
        /**
         * Detecta contextos de ejecución que pueden causar deadlocks
         * 
         * @return bool
         */
        private static function detectRiskyExecutionContext(): bool
        {
            // Ejecución via AJAX (panel dev-tools)
            if (defined('DOING_AJAX') && DOING_AJAX) {
                return true;
            }
            
            // Múltiples archivos de test (mass execution)
            if (self::isRunningMultipleTestFiles()) {
                return true;
            }
            
            // Ejecución con paralelización
            if (self::isParallelExecution()) {
                return true;
            }
            
            // Entorno con alta concurrencia de BD
            if (self::isHighConcurrencyEnvironment()) {
                return true;
            }
            
            // Por defecto, usar modo seguro en desarrollo
            return true;
        }
        
        /**
         * Detecta si se están ejecutando múltiples archivos de test
         * 
         * @return bool
         */
        private static function isRunningMultipleTestFiles(): bool
        {
            global $argv;
            
            if (!is_array($argv)) {
                return false;
            }
            
            // Buscar patrones que indiquen ejecución masiva
            $argv_string = implode(' ', $argv);
            
            // PHPUnit ejecutando todos los tests
            if (strpos($argv_string, '--testsuite') !== false) {
                return true;
            }
            
            // Múltiples archivos de test especificados
            $test_file_count = 0;
            foreach ($argv as $arg) {
                if (strpos($arg, 'Test.php') !== false) {
                    $test_file_count++;
                }
            }
            
            return $test_file_count > 1;
        }
        
        /**
         * Detecta ejecución en paralelo
         * 
         * @return bool
         */
        private static function isParallelExecution(): bool
        {
            return defined('PHPUNIT_PARALLEL') || 
                   getenv('PHPUNIT_PARALLEL') !== false ||
                   strpos(implode(' ', $GLOBALS['argv'] ?? []), '--parallel') !== false;
        }
        
        /**
         * Detecta entorno con alta concurrencia
         * 
         * @return bool
         */
        private static function isHighConcurrencyEnvironment(): bool
        {
            global $wpdb;
            
            if (!$wpdb) {
                return false;
            }
            
            // Verificar número de conexiones activas
            $result = $wpdb->get_var("SHOW STATUS LIKE 'Threads_connected'");
            if ($result && is_numeric($result) && $result > 10) {
                return true;
            }
            
            return false;
        }
        
        // =====================================================================
        // IMPLEMENTACIÓN ANTI-DEADLOCK
        // =====================================================================
        
        /**
         * Configurar base de datos para prevenir deadlocks
         */
        private function configureAntiDeadlockDatabase(): void
        {
            global $wpdb;
            
            if (!$wpdb) {
                return;
            }
            
            try {
                // Guardar configuración original
                if (empty(self::$db_config_cache)) {
                    // Usar variable compatible con MySQL 8.0+
                    $isolation = $wpdb->get_var("SELECT @@SESSION.transaction_isolation");
                    if (!$isolation) {
                        // Fallback para versiones anteriores
                        $isolation = $wpdb->get_var("SELECT @@SESSION.tx_isolation");
                    }
                    
                    $lock_timeout = $wpdb->get_var("SELECT @@SESSION.innodb_lock_wait_timeout");
                    
                    self::$db_config_cache = [
                        'isolation' => $isolation ?: 'REPEATABLE READ',
                        'lock_timeout' => $lock_timeout ?: 50
                    ];
                }
                
                // Configurar isolation level menos restrictivo
                $wpdb->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
                
                // Reducir timeout de locks para fallar rápido
                $wpdb->query("SET SESSION innodb_lock_wait_timeout = 3");
                
            } catch (Exception $e) {
                // Silenciar errores de configuración de BD
                error_log("DevToolsTestCase: Error configurando BD anti-deadlock: " . $e->getMessage());
            }
        }
        
        /**
         * Restaurar configuración original de base de datos
         */
        private static function restoreDbConfiguration(): void
        {
            global $wpdb;
            
            if (!$wpdb || empty(self::$db_config_cache)) {
                return;
            }
            
            try {
                // Restaurar isolation level original
                if (isset(self::$db_config_cache['isolation'])) {
                    $isolation = self::$db_config_cache['isolation'];
                    if ($isolation) {
                        // Normalizar formato para compatibilidad MySQL
                        $isolation = str_replace('-', ' ', $isolation);
                        $wpdb->query("SET SESSION TRANSACTION ISOLATION LEVEL $isolation");
                    }
                }
                
                // Restaurar lock timeout original
                if (isset(self::$db_config_cache['lock_timeout'])) {
                    $timeout = self::$db_config_cache['lock_timeout'];
                    if ($timeout) {
                        $wpdb->query("SET SESSION innodb_lock_wait_timeout = $timeout");
                    }
                }
                
            } catch (Exception $e) {
                error_log("DevToolsTestCase: Error restaurando configuración BD: " . $e->getMessage());
            }
        }
        
        /**
         * Limpieza de datos de test de forma segura (sin deadlocks)
         */
        private function cleanupTestDataSafely(): void
        {
            // Limpiar solo datos específicos del test actual
            // Evitar operaciones masivas que puedan causar deadlock
            
            // Limpiar transients de test
            if (function_exists('delete_transient')) {
                $test_transients = [
                    'dev_tools_test_data',
                    'test_cache_data',
                    'phpunit_test_data'
                ];
                
                foreach ($test_transients as $transient) {
                    delete_transient($transient);
                }
            }
        }
        
        /**
         * Implementación segura de _delete_all_data sin deadlocks
         */
        private static function safeDeleteAllData(): void
        {
            global $wpdb;
            
            if (!$wpdb) {
                return;
            }
            
            try {
                // Usar estrategia de limpieza por lotes pequeños
                self::safeDeleteTestUsers();
                self::safeDeleteTestPosts();
                self::safeDeleteTestOptions();
                
            } catch (Exception $e) {
                error_log("DevToolsTestCase: Error en limpieza segura: " . $e->getMessage());
                
                // Fallback: intentar limpieza mínima
                try {
                    self::emergencyCleanup();
                } catch (Exception $fallback_error) {
                    error_log("DevToolsTestCase: Error en limpieza de emergencia: " . $fallback_error->getMessage());
                }
            }
        }
        
        /**
         * Eliminar usuarios de test de forma segura
         */
        private static function safeDeleteTestUsers(): void
        {
            global $wpdb;
            
            // Solo eliminar usuarios creados en las últimas 2 horas
            $recent_time = date('Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS);
            
            // Eliminar en lotes pequeños para evitar locks largos
            $batch_size = 5;
            $deleted_count = 0;
            $max_deletions = 50; // Límite de seguridad
            
            do {
                // Buscar usuarios de test recientes (con lock mínimo)
                $user_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT ID FROM {$wpdb->users} 
                    WHERE user_login LIKE %s 
                    AND user_registered > %s
                    AND ID != 1 
                    ORDER BY ID 
                    LIMIT %d
                ", 'dev_tools_test_%', $recent_time, $batch_size));
                
                if (empty($user_ids)) {
                    break;
                }
                
                // Eliminar lote actual
                foreach ($user_ids as $user_id) {
                    wp_delete_user($user_id);
                    $deleted_count++;
                    
                    // Pequeña pausa para reducir presión en BD
                    usleep(10000); // 10ms
                }
                
            } while (count($user_ids) === $batch_size && $deleted_count < $max_deletions);
        }
        
        /**
         * Eliminar posts de test de forma segura
         */
        private static function safeDeleteTestPosts(): void
        {
            global $wpdb;
            
            // Solo eliminar posts de test recientes
            $recent_time = date('Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS);
            
            $post_ids = $wpdb->get_col($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_title LIKE %s 
                AND post_date > %s
                ORDER BY ID 
                LIMIT 20
            ", 'Test%', $recent_time));
            
            foreach ($post_ids as $post_id) {
                wp_delete_post($post_id, true);
                usleep(5000); // 5ms pausa
            }
        }
        
        /**
         * Eliminar opciones de test de forma segura
         */
        private static function safeDeleteTestOptions(): void
        {
            global $wpdb;
            
            // Eliminar opciones de test específicas
            $test_options = [
                'dev_tools_test_%',
                'phpunit_test_%',
                '_test_%'
            ];
            
            foreach ($test_options as $pattern) {
                $wpdb->query($wpdb->prepare("
                    DELETE FROM {$wpdb->options} 
                    WHERE option_name LIKE %s 
                    LIMIT 50
                ", $pattern));
                
                usleep(5000); // 5ms pausa
            }
        }
        
        /**
         * Limpieza mínima de emergencia
         */
        private static function emergencyCleanup(): void
        {
            // Solo limpiar transients críticos
            if (function_exists('delete_transient')) {
                delete_transient('doing_cron');
                delete_transient('dev_tools_test_data');
            }
            
            // Limpiar cache de objeto
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
        }
        
        // =====================================================================
        // MÉTODOS DE UTILIDAD Y FACTORY
        // =====================================================================
        
        /**
         * Factory para crear tests con comportamiento estándar
         * 
         * @param string $test_class_name
         * @return DevToolsTestCase
         */
        public static function createStandardTest($test_class_name = null): self
        {
            $instance = new static();
            $instance->useStandardWordPressBehavior();
            return $instance;
        }
        
        /**
         * Factory para crear tests con protecciones anti-deadlock
         * 
         * @param string $test_class_name
         * @return DevToolsTestCase
         */
        public static function createAntiDeadlockTest($test_class_name = null): self
        {
            $instance = new static();
            $instance->useAntiDeadlockBehavior();
            return $instance;
        }
        
        /**
         * Obtiene información completa del contexto de ejecución del test
         * 
         * @return array
         */
        public function getTestContext(): array
        {
            return [
                'anti_deadlock_active' => $this->shouldUseAntiDeadlockForInstance(),
                'risky_context_detected' => self::detectRiskyExecutionContext(),
                'execution_mode' => $this->shouldUseAntiDeadlockForInstance() ? 'anti-deadlock' : 'standard',
                'ajax_context' => defined('DOING_AJAX') && DOING_AJAX,
                'instance_override' => $this->instance_override,
                'global_setting' => self::$use_anti_deadlock,
                'environment_override' => getenv('DEV_TOOLS_FORCE_ANTI_DEADLOCK'),
                'multiple_files' => self::isRunningMultipleTestFiles(),
                'parallel_execution' => self::isParallelExecution(),
                'high_concurrency' => self::isHighConcurrencyEnvironment()
            ];
        }
        
        /**
         * Verifica si el test actual está usando protecciones anti-deadlock
         * 
         * @return bool
         */
        public function isUsingAntiDeadlock(): bool
        {
            return $this->shouldUseAntiDeadlockForInstance();
        }

        /**
         * Método de diagnóstico para verificar configuración actual
         * 
         * @return array
         */
        public function getDiagnosticInfo(): array
        {
            return [
                'use_anti_deadlock_class' => self::shouldUseAntiDeadlock(),
                'use_anti_deadlock_instance' => $this->shouldUseAntiDeadlockForInstance(),
                'instance_override' => $this->instance_override,
                'risky_context' => self::detectRiskyExecutionContext(),
                'is_ajax' => defined('DOING_AJAX') && DOING_AJAX,
                'multiple_files' => self::isRunningMultipleTestFiles(),
                'parallel_execution' => self::isParallelExecution(),
                'high_concurrency' => self::isHighConcurrencyEnvironment(),
                'db_config_cached' => !empty(self::$db_config_cache),
                'constants_defined' => [
                    'DEV_TOOLS_DISABLE_ANTI_DEADLOCK' => defined('DEV_TOOLS_DISABLE_ANTI_DEADLOCK') ? DEV_TOOLS_DISABLE_ANTI_DEADLOCK : 'not_defined',
                    'DEV_TOOLS_FORCE_ANTI_DEADLOCK' => defined('DEV_TOOLS_FORCE_ANTI_DEADLOCK') ? DEV_TOOLS_FORCE_ANTI_DEADLOCK : 'not_defined',
                    'DEV_TOOLS_TESTS_VERBOSE' => defined('DEV_TOOLS_TESTS_VERBOSE') ? DEV_TOOLS_TESTS_VERBOSE : 'not_defined',
                    'DEV_TOOLS_TESTS_DEBUG' => defined('DEV_TOOLS_TESTS_DEBUG') ? DEV_TOOLS_TESTS_DEBUG : 'not_defined'
                ]
            ];
        }
        
        /**
         * Log de información de diagnóstico (para debugging)
         */
        protected function logDiagnosticInfo(): void
        {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $info = $this->getDiagnosticInfo();
                error_log("DevToolsTestCase Diagnostic: " . json_encode($info, JSON_PRETTY_PRINT));
            }
        }
    }
}
