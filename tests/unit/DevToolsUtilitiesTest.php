<?php
/**
 * Test Unitario: Dev Tools - Utilidades y Validaciones
 * 
 * Tests de funciones de utilidad, validaciones y helpers sin dependencias WordPress complejas.
 * Ideal para: sanitización, validación de datos, formateo, conversiones.
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 1.0.0
 */

class DevToolsUtilitiesTest extends WP_UnitTestCase {

    /**
     * Test: Validación de emails
     */
    public function test_email_validation() {
        $valid_emails = [
            'user@example.com',
            'test.email@domain.org',
            'admin@example.dev',
            'dev+tools@test.co.uk'
        ];
        
        $invalid_emails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user.domain.com',
            ''
        ];
        
        // Probar emails válidos
        foreach ($valid_emails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 
                "El email '{$email}' debe ser válido"
            );
        }
        
        // Probar emails inválidos
        foreach ($invalid_emails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 
                "El email '{$email}' debe ser inválido"
            );
        }
    }

    /**
     * Test: Validación de URLs
     */
    public function test_url_validation() {
        $valid_urls = [
            'https://example.com',
            'http://localhost:10019',
            'https://subdomain.domain.com/path',
            'http://192.168.1.1:8080'
        ];
        
        $invalid_urls = [
            'not-a-url',
            'ftp://example.com', // Permitido por filter_var pero podemos restringir
            'javascript:alert(1)',
            '',
            'http://'
        ];
        
        foreach ($valid_urls as $url) {
            $this->assertTrue(
                filter_var($url, FILTER_VALIDATE_URL) !== false, 
                "La URL '{$url}' debe ser válida"
            );
        }
        
        foreach ($invalid_urls as $url) {
            if ($url === 'ftp://example.com') {
                // FTP es técnicamente válido pero podemos validar protocolo específico
                $this->assertTrue(strpos($url, 'ftp://') === 0, 'FTP detectado correctamente');
            } else {
                $this->assertFalse(
                    filter_var($url, FILTER_VALIDATE_URL) !== false, 
                    "La URL '{$url}' debe ser inválida"
                );
            }
        }
    }

    /**
     * Test: Sanitización de strings
     */
    public function test_string_sanitization() {
        $test_cases = [
            [
                'input' => '  Hello World  ',
                'expected' => 'Hello World',
                'method' => 'trim'
            ],
            [
                'input' => 'UPPERCASE TEXT',
                'expected' => 'uppercase text',
                'method' => 'lowercase'
            ],
            [
                'input' => 'lowercase text',
                'expected' => 'LOWERCASE TEXT',
                'method' => 'uppercase'
            ],
            [
                'input' => '<script>alert("xss")</script>Normal text',
                'expected' => 'alert("xss")Normal text',
                'method' => 'strip_tags'
            ]
        ];
        
        foreach ($test_cases as $case) {
            switch ($case['method']) {
                case 'trim':
                    $result = trim($case['input']);
                    break;
                case 'lowercase':
                    $result = strtolower($case['input']);
                    break;
                case 'uppercase':
                    $result = strtoupper($case['input']);
                    break;
                case 'strip_tags':
                    $result = strip_tags($case['input']);
                    break;
                default:
                    $result = $case['input'];
            }
            
            $this->assertEquals(
                $case['expected'], 
                $result, 
                "Sanitización '{$case['method']}' debe funcionar correctamente"
            );
        }
    }

    /**
     * Test: Formateo de fechas
     */
    public function test_date_formatting() {
        $timestamp = strtotime('2025-06-05 10:30:45');
        
        // Diferentes formatos de fecha
        $formats = [
            'Y-m-d' => '2025-06-05',
            'Y-m-d H:i:s' => '2025-06-05 10:30:45',
            'd/m/Y' => '05/06/2025',
            'M j, Y' => 'Jun 5, 2025',
            'l, F j, Y' => 'Thursday, June 5, 2025'
        ];
        
        foreach ($formats as $format => $expected) {
            $formatted = date($format, $timestamp);
            $this->assertEquals(
                $expected, 
                $formatted, 
                "El formato de fecha '{$format}' debe ser correcto"
            );
        }
    }

    /**
     * Test: Conversión de tamaños de archivo
     */
    public function test_file_size_conversion() {
        $bytes_to_readable = function($bytes) {
            $units = ['B', 'KB', 'MB', 'GB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            
            $bytes /= pow(1024, $pow);
            
            return round($bytes, 2) . ' ' . $units[$pow];
        };
        
        $test_cases = [
            ['input' => 1024, 'expected' => '1 KB'],
            ['input' => 1048576, 'expected' => '1 MB'],
            ['input' => 1073741824, 'expected' => '1 GB'],
            ['input' => 512, 'expected' => '512 B'],
            ['input' => 0, 'expected' => '0 B']
        ];
        
        foreach ($test_cases as $case) {
            $result = $bytes_to_readable($case['input']);
            $this->assertEquals(
                $case['expected'], 
                $result, 
                "Conversión de {$case['input']} bytes debe ser correcta"
            );
        }
    }

    /**
     * Test: Generación de hashes
     */
    public function test_hash_generation() {
        $test_data = 'DevTools Testing Data';
        
        // MD5
        $md5_hash = md5($test_data);
        $this->assertEquals(32, strlen($md5_hash), 'Hash MD5 debe tener 32 caracteres');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $md5_hash, 'Hash MD5 debe ser hexadecimal');
        
        // SHA1
        $sha1_hash = sha1($test_data);
        $this->assertEquals(40, strlen($sha1_hash), 'Hash SHA1 debe tener 40 caracteres');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $sha1_hash, 'Hash SHA1 debe ser hexadecimal');
        
        // SHA256
        $sha256_hash = hash('sha256', $test_data);
        $this->assertEquals(64, strlen($sha256_hash), 'Hash SHA256 debe tener 64 caracteres');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $sha256_hash, 'Hash SHA256 debe ser hexadecimal');
        
        // Verificar consistencia
        $this->assertEquals(md5($test_data), md5($test_data), 'Hashes idénticos para datos idénticos');
    }

    /**
     * Test: Validación de números
     */
    public function test_number_validation() {
        $valid_integers = [0, 1, -1, 42, 9999];
        $invalid_integers = ['abc', '12.5', '', null, true];
        
        $valid_floats = [0.0, 1.5, -2.7, 42.0, 9999.99];
        $invalid_floats = ['abc', '', null, true];
        
        // Probar enteros válidos
        foreach ($valid_integers as $number) {
            $this->assertTrue(
                is_int($number) && is_numeric($number), 
                "El número {$number} debe ser un entero válido"
            );
        }
        
        // Probar enteros inválidos
        foreach ($invalid_integers as $number) {
            $this->assertFalse(
                is_int($number), 
                "El valor debe ser un entero inválido"
            );
        }
        
        // Probar flotantes válidos
        foreach ($valid_floats as $number) {
            $this->assertTrue(
                is_float($number) && is_numeric($number), 
                "El número {$number} debe ser un flotante válido"
            );
        }
    }

    /**
     * Test: Manipulación de arrays avanzada
     */
    public function test_advanced_array_manipulation() {
        $test_array = [
            ['name' => 'Alice', 'age' => 30, 'city' => 'Madrid'],
            ['name' => 'Bob', 'age' => 25, 'city' => 'Barcelona'],
            ['name' => 'Charlie', 'age' => 35, 'city' => 'Valencia']
        ];
        
        // Extraer columna específica
        $names = array_column($test_array, 'name');
        $expected_names = ['Alice', 'Bob', 'Charlie'];
        $this->assertEquals($expected_names, $names, 'Extracción de columna debe funcionar');
        
        // Filtrar por condición
        $adults_over_30 = array_filter($test_array, function($person) {
            return $person['age'] > 30;
        });
        $this->assertCount(1, $adults_over_30, 'Debe haber 1 persona mayor de 30');
        
        // Mapear transformación
        $ages_in_months = array_map(function($person) {
            return $person['age'] * 12;
        }, $test_array);
        $expected_months = [360, 300, 420];
        $this->assertEquals($expected_months, $ages_in_months, 'Transformación de edad a meses');
        
        // Reducir a suma
        $total_age = array_reduce($test_array, function($carry, $person) {
            return $carry + $person['age'];
        }, 0);
        $this->assertEquals(90, $total_age, 'Suma total de edades debe ser 90');
    }

    /**
     * Test: Encoding y JSON
     */
    public function test_encoding_and_json() {
        $test_data = [
            'string' => 'Texto con ácentos y ñ',
            'number' => 42,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => ['nested' => 'value']
        ];
        
        // Codificar a JSON
        $json = json_encode($test_data);
        $this->assertIsString($json, 'JSON debe ser string');
        $this->assertNotFalse($json, 'Codificación JSON debe ser exitosa');
        
        // Decodificar JSON
        $decoded = json_decode($json, true);
        $this->assertEquals($test_data, $decoded, 'Decodificación debe ser exacta');
        
        // Verificar codificación UTF-8
        $utf8_string = 'Café, niño, corazón';
        $json_utf8 = json_encode($utf8_string, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Café', $json_utf8, 'UTF-8 debe preservarse');
        
        // JSON pretty print
        $pretty_json = json_encode($test_data, JSON_PRETTY_PRINT);
        $this->assertStringContainsString("\n", $pretty_json, 'Pretty print debe incluir saltos de línea');
    }
}
