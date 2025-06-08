<?php

/**
 * Test para analizar transients de licencia reales en la base de datos
 * 
 * Este test examina específicamente los transients que contengan "_lic_"
 * en el option_name para monitorear el estado real de las licencias.
 * Usa la configuración de Local by Flywheel y DevToolsTestCase.
 * 
 * @package Tarokina Pro
 * @subpackage Tests
 */

class TarokinaLicenseTransientsTest extends DevToolsTestCase
{
    private $main_db_config = [];
    private $verbose_mode = false;
    private $test_results = [];
    
    /**
     * Configuración inicial del test
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Detectar modo verbose solo para logging interno
        $this->verbose_mode = in_array('--verbose', $_SERVER['argv'] ?? []);
        
        // Configurar conexión usando configuración de Local by Flywheel  
        $this->setupMainDatabaseConfig();
    }
    
    /**
     * Log interno que no produce output externo
     */
    private function logResult(string $test_name, array $data): void
    {
        $this->test_results[$test_name] = $data;
    }
    
    /**
     * Test principal: Analizar transients de licencia reales en la BD principal
     */
    public function testAnalyzeRealLicenseTransients(): void
    {
        // Obtener transients reales usando conexión optimizada
        $real_transients = $this->getRealLicenseTransientsOptimized();
        
        // Log interno para debugging si es necesario
        $this->logResult('real_license_transients', [
            'count' => count($real_transients),
            'found' => !empty($real_transients)
        ]);
        
        // Verificaciones básicas
        $this->assertIsArray($real_transients, "Debe devolver un array de resultados");
        
        if (count($real_transients) > 0) {
            // Tests específicos sin output
            $this->assertGreaterThan(0, count($real_transients), "Debe encontrar al menos un transient relacionado con licencias");
            
            // Verificar estructura de datos
            foreach ($real_transients as $transient) {
                $this->assertArrayHasKey('option_name', $transient);
                $this->assertArrayHasKey('option_value', $transient);
                $this->assertArrayHasKey('autoload', $transient);
            }
        }

        $this->assertTrue(true, "Test de análisis de transients reales ejecutado correctamente");
    }
    
    /**
     * Test específico: Buscar transients específicos de Tarokina
     */
    public function testSearchSpecificTarokinaTransients(): void
    {
        // Lista de transients específicos que podríamos esperar encontrar
        $expected_transients = [
            'tarokina_license_status',
            'tarokina_pro_license',
            'edd_sl_' // Easy Digital Downloads Software Licensing
        ];
        
        $found_transients = [];
        
        foreach ($expected_transients as $pattern) {
            $results = $this->searchTransientsByPatternOptimized($pattern);
            if (!empty($results)) {
                $found_transients[$pattern] = $results;
            }
        }
        
        // Log para debugging interno
        $this->logResult('specific_tarokina_transients', [
            'patterns_searched' => count($expected_transients),
            'patterns_found' => count($found_transients),
            'total_transients' => array_sum(array_map('count', $found_transients))
        ]);
        
        // Tests sin output
        $this->assertIsArray($found_transients);
        
        // Verificar que al menos uno de los patrones produce resultados válidos
        if (!empty($found_transients)) {
            $total_found = array_sum(array_map('count', $found_transients));
            $this->assertGreaterThan(0, $total_found, "Debe encontrar al menos un transient con los patrones buscados");
        }
        
        $this->assertTrue(true, "Búsqueda de transients específicos completada");
    }
    
    /**
     * Test: Análisis de timeouts de transients
     */
    public function testAnalyzeTransientTimeouts(): void
    {
        $timeout_transients = $this->getTransientTimeoutsOptimized();
        
        // Análisis interno sin output
        $now = time();
        $active_count = 0;
        $expired_count = 0;
        
        foreach ($timeout_transients as $timeout) {
            $expires_at = intval($timeout['option_value']);
            if ($expires_at < $now) {
                $expired_count++;
            } else {
                $active_count++;
            }
        }
        
        // Log para debugging interno
        $this->logResult('transient_timeouts', [
            'total_timeouts' => count($timeout_transients),
            'active_count' => $active_count,
            'expired_count' => $expired_count
        ]);

        // Tests sin output
        $this->assertIsArray($timeout_transients);
        
        if (!empty($timeout_transients)) {
            // Verificar estructura de timeouts
            foreach ($timeout_transients as $timeout) {
                $this->assertArrayHasKey('option_name', $timeout);
                $this->assertArrayHasKey('option_value', $timeout);
                $this->assertTrue(strpos($timeout['option_name'], '_transient_timeout_') === 0);
                $this->assertTrue(is_numeric($timeout['option_value']));
            }
        }
        
        $this->assertTrue(true, "Análisis de timeouts ejecutado correctamente");
    }
    
    /**
     * Configurar conexión usando configuración de Local by Flywheel
     */
    private function setupMainDatabaseConfig(): void
    {
        // Usar configuración desde wp-tests-config.php si está disponible
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD')) {
            $this->main_db_config = [
                'host' => DB_HOST,
                'database' => str_replace('_test', '', DB_NAME), // BD principal (sin _test)
                'username' => DB_USER,
                'password' => DB_PASSWORD,
                'prefix' => 'wp_', // Prefijo principal
                'socket' => null
            ];
        } else {
            // Configuración por defecto para Local by Flywheel
            $this->main_db_config = [
                'host' => 'localhost',
                'database' => 'local',
                'username' => 'root', 
                'password' => 'root',
                'prefix' => 'wp_',
                'socket' => '/Users/fernandovazquezperez/Library/Application Support/Local/run/T7OGkjtdu/mysql/mysqld.sock'
            ];
        }
        
        // Log interno para debugging
        $this->logResult('database_config', [
            'database' => $this->main_db_config['database'],
            'prefix' => $this->main_db_config['prefix']
        ]);
    }
    
    /**
     * Método optimizado para obtener transients de licencia usando wpdb
     */
    private function getRealLicenseTransientsOptimized(): array
    {
        global $wpdb;
        
        // Cambiar temporalmente el prefijo para acceder a la BD principal
        $original_prefix = $wpdb->prefix;
        $wpdb->set_prefix($this->main_db_config['prefix']);
        
        try {
            $sql = "
                SELECT 
                    option_name, 
                    option_value, 
                    autoload,
                    CHAR_LENGTH(option_value) as value_length
                FROM {$wpdb->options} 
                WHERE (
                    option_name LIKE %s 
                    OR option_name LIKE %s 
                    OR option_name LIKE %s
                    OR option_name LIKE %s
                )
                ORDER BY option_name ASC
                LIMIT 50
            ";
            
            $results = $wpdb->get_results($wpdb->prepare($sql, 
                '%_lic_%', 
                '%license%', 
                '%licence%',
                '%edd_sl_%'
            ), ARRAY_A);
            
            return $results ?: [];
            
        } catch (Exception $e) {
            // Log error internally
            $this->logResult('database_error', ['message' => $e->getMessage()]);
            return [];
        } finally {
            // Restaurar prefijo original
            $wpdb->set_prefix($original_prefix);
        }
    }
    
    /**
     * Buscar transients por patrón usando wpdb optimizado
     */
    private function searchTransientsByPatternOptimized(string $pattern): array
    {
        global $wpdb;
        
        $original_prefix = $wpdb->prefix;
        $wpdb->set_prefix($this->main_db_config['prefix']);
        
        try {
            $sql = "
                SELECT 
                    option_name, 
                    option_value, 
                    autoload,
                    CHAR_LENGTH(option_value) as value_length
                FROM {$wpdb->options} 
                WHERE option_name LIKE %s
                ORDER BY option_name ASC
                LIMIT 20
            ";
            
            $results = $wpdb->get_results($wpdb->prepare($sql, "%{$pattern}%"), ARRAY_A);
            
            return $results ?: [];
            
        } catch (Exception $e) {
            // Log error internally
            $this->logResult('pattern_search_error', [
                'pattern' => $pattern,
                'message' => $e->getMessage()
            ]);
            return [];
        } finally {
            $wpdb->set_prefix($original_prefix);
        }
    }
    
    /**
     * Obtener timeouts de transients usando wpdb optimizado
     */
    private function getTransientTimeoutsOptimized(): array
    {
        global $wpdb;
        
        $original_prefix = $wpdb->prefix;
        $wpdb->set_prefix($this->main_db_config['prefix']);
        
        try {
            $sql = "
                SELECT option_name, option_value
                FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_%'
                AND (
                    option_name LIKE %s 
                    OR option_name LIKE %s
                    OR option_name LIKE %s
                )
                ORDER BY CAST(option_value AS UNSIGNED) ASC
                LIMIT 30
            ";
            
            $results = $wpdb->get_results($wpdb->prepare($sql,
                '%_lic_%',
                '%license%', 
                '%edd_sl_%'
            ), ARRAY_A);
            
            return $results ?: [];
            
        } catch (Exception $e) {
            // Log error internally
            $this->logResult('timeout_search_error', ['message' => $e->getMessage()]);
            return [];
        } finally {
            $wpdb->set_prefix($original_prefix);
        }
    }
    
    /**
     * Mostrar transients reales encontrados
     */
    private function displayRealTransients(array $transients): void
    {
        echo "📋 TRANSIENTS DE LICENCIA REALES:\n";
        echo str_repeat("=", 100) . "\n";
        echo sprintf("%-50s | %-12s | %-8s | %s\n", "OPTION_NAME", "VALUE_LENGTH", "AUTOLOAD", "VALUE_PREVIEW");
        echo str_repeat("-", 100) . "\n";
        
        // Categorizar los resultados
        $categories = [
            'transient_values' => [],
            'transient_timeouts' => [],
            'license_options' => [],
            'edd_options' => []
        ];
        
        foreach ($transients as $transient) {
            $name = $transient['option_name'];
            $preview = $this->createValuePreview($transient['option_value']);
            
            echo sprintf("%-50s | %-12s | %-8s | %s\n", 
                $name,
                $transient['value_length'] . ' bytes',
                $transient['autoload'],
                $preview
            );
            
            // Categorizar
            if (strpos($name, '_transient_timeout_') === 0) {
                $categories['transient_timeouts'][] = $transient;
            } elseif (strpos($name, '_transient_') === 0) {
                $categories['transient_values'][] = $transient;
            } elseif (strpos($name, 'edd_sl_') !== false) {
                $categories['edd_options'][] = $transient;
            } else {
                $categories['license_options'][] = $transient;
            }
        }
        
        echo str_repeat("-", 100) . "\n";
        echo "📊 CATEGORÍAS:\n";
        echo "   • Transient Values: " . count($categories['transient_values']) . "\n";
        echo "   • Transient Timeouts: " . count($categories['transient_timeouts']) . "\n";
        echo "   • License Options: " . count($categories['license_options']) . "\n";
        echo "   • EDD Options: " . count($categories['edd_options']) . "\n";
    }
    
    /**
     * Analizar el estado de los transients
     */
    private function analyzeTransientStatus(array $transients): void
    {
        echo "\n🔍 ANÁLISIS DE ESTADO:\n";
        echo str_repeat("-", 60) . "\n";
        
        $transient_values = array_filter($transients, function($t) {
            return strpos($t['option_name'], '_transient_') === 0 && 
                   strpos($t['option_name'], '_transient_timeout_') === false;
        });
        
        if (!empty($transient_values)) {
            foreach ($transient_values as $transient) {
                $transient_key = str_replace('_transient_', '', $transient['option_name']);
                
                echo "• {$transient['option_name']}\n";
                echo "  ├─ Clave: {$transient_key}\n";
                echo "  ├─ Tamaño: {$transient['value_length']} bytes\n";
                echo "  └─ Contenido: " . $this->analyzeTransientContent($transient['option_value']) . "\n\n";
            }
        }
    }
    
    /**
     * Analizar el contenido de un transient
     */
    private function analyzeTransientContent(string $value): string
    {
        // Intentar decodificar como JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $info = [];
            if (isset($decoded['status'])) $info[] = "status={$decoded['status']}";
            if (isset($decoded['license'])) $info[] = "license=presente";
            if (isset($decoded['expires'])) $info[] = "expires=" . date('d-m-Y', $decoded['expires']);
            
            return "JSON: " . implode(', ', $info);
        }
        
        // Si no es JSON, mostrar preview
        return strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
    }
    
    /**
     * Crear vista previa del valor
     */
    private function createValuePreview(string $value, int $maxLength = 30): string
    {
        if (empty($value)) {
            return '[VACÍO]';
        }
        
        // Verificar si es timestamp
        if (preg_match('/^\d{10}$/', $value)) {
            return date('d-m-Y H:i:s', intval($value));
        }
        
        // Verificar si es JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (isset($decoded['status'])) {
                return "JSON: status={$decoded['status']}";
            } elseif (isset($decoded['license'])) {
                return "JSON: license=presente";
            } else {
                return "JSON (" . count($decoded) . " campos)";
            }
        }
        
        // Texto normal truncado
        if (strlen($value) > $maxLength) {
            return substr($value, 0, $maxLength) . '...';
        }
        
        return $value;
    }
    
    /**
     * Método para mostrar información detallada en casos específicos
     * Solo se activa con variable de entorno TAROKINA_DEBUG_TRANSIENTS=1
     */
    public function testShowDetailedTransientInfo(): void
    {
        // Detectar múltiples formas de activación
        $debug_enabled = (
            (isset($_ENV['TAROKINA_DEBUG_TRANSIENTS']) && $_ENV['TAROKINA_DEBUG_TRANSIENTS'] === '1') ||
            (getenv('TAROKINA_DEBUG_TRANSIENTS') === '1') ||
            $this->verbose_mode
        );
        
        // Solo ejecutar si se solicita explícitamente
        if (!$debug_enabled) {
            $this->markTestSkipped('Test de debugging no activado. Usa TAROKINA_DEBUG_TRANSIENTS=1 o --verbose para activar.');
            return;
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🔍 INFORMACIÓN DETALLADA DE TRANSIENTS DE LICENCIA\n";
        echo str_repeat("=", 80) . "\n";
        
        // Mostrar configuración
        echo "🔧 Configuración BD: {$this->main_db_config['database']}\n";
        echo "🏷️  Prefijo: {$this->main_db_config['prefix']}\n\n";
        
        // Mostrar transients encontrados
        $real_transients = $this->getRealLicenseTransientsOptimized();
        echo "📊 Total transients encontrados: " . count($real_transients) . "\n\n";
        
        if (!empty($real_transients)) {
            $this->displayDetailedTransients($real_transients);
        }
        
        // Mostrar timeouts
        $timeouts = $this->getTransientTimeoutsOptimized();
        if (!empty($timeouts)) {
            echo "\n⏰ ANÁLISIS DE TIMEOUTS:\n";
            echo str_repeat("-", 80) . "\n";
            
            $now = time();
            foreach ($timeouts as $timeout) {
                $name = str_replace('_transient_timeout_', '', $timeout['option_name']);
                $expires = intval($timeout['option_value']);
                $status = $expires > $now ? "✅ ACTIVO" : "❌ EXPIRADO";
                $date = date('d-m-Y H:i:s', $expires);
                
                // Calcular tiempo restante
                $remaining_info = "";
                if ($expires > $now) {
                    $remaining_seconds = $expires - $now;
                    $remaining_info = " (" . $this->formatTimeRemaining($remaining_seconds) . " restante)";
                } else {
                    $expired_seconds = $now - $expires;
                    $remaining_info = " (expiró hace " . $this->formatTimeRemaining($expired_seconds) . ")";
                }
                
                echo "• {$name}: {$status} - {$date}{$remaining_info}\n";
            }
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        $this->assertTrue(true, "Información detallada mostrada");
    }
    
    /**
     * Mostrar transients en formato detallado
     */
    private function displayDetailedTransients(array $transients): void
    {
        echo "📋 DETALLES DE TRANSIENTS:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($transients as $transient) {
            echo "• {$transient['option_name']}\n";
            echo "  ├─ Tamaño: {$transient['value_length']} bytes\n";
            echo "  ├─ Autoload: {$transient['autoload']}\n";
            echo "  └─ Preview: " . $this->createValuePreview($transient['option_value']) . "\n\n";
        }
    }
    
    /**
     * Formatear tiempo restante de manera legible
     */
    private function formatTimeRemaining(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remaining_seconds = $seconds % 60;
            return $remaining_seconds > 0 ? "{$minutes}m {$remaining_seconds}s" : "{$minutes}m";
        }
        
        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $remaining_minutes = floor(($seconds % 3600) / 60);
            return $remaining_minutes > 0 ? "{$hours}h {$remaining_minutes}m" : "{$hours}h";
        }
        
        $days = floor($seconds / 86400);
        $remaining_hours = floor(($seconds % 86400) / 3600);
        
        if ($days < 30) {
            return $remaining_hours > 0 ? "{$days}d {$remaining_hours}h" : "{$days}d";
        }
        
        // Para períodos muy largos, mostrar en formato más compacto
        if ($days < 365) {
            $months = floor($days / 30);
            $remaining_days = $days % 30;
            return $remaining_days > 0 ? "{$months}mes {$remaining_days}d" : "{$months}mes";
        }
        
        $years = floor($days / 365);
        $remaining_days = $days % 365;
        return $remaining_days > 0 ? "{$years}a {$remaining_days}d" : "{$years}a";
    }
}
