<?php
/**
 * Test Unitario: Dev Tools - Sistema de Cache
 * 
 * Tests de lógica de cache y limpieza sin dependencias complejas de WordPress.
 * Verifica funcionalidades de archivos, directorios y limpieza de datos.
 * 
 * @package DevTools
 * @subpackage Tests\Unit
 * @since 1.0.0
 */

class DevToolsCacheTest extends WP_UnitTestCase {

    private $test_cache_dir;
    private $test_files = [];

    /**
     * Configuración inicial para cada test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Crear directorio temporal para tests
        $this->test_cache_dir = sys_get_temp_dir() . '/dev_tools_cache_test_' . uniqid();
        if (!file_exists($this->test_cache_dir)) {
            mkdir($this->test_cache_dir, 0755, true);
        }
    }

    /**
     * Limpieza después de cada test
     */
    public function tearDown(): void {
        // Limpiar archivos de prueba
        foreach ($this->test_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Limpiar directorio temporal
        if (file_exists($this->test_cache_dir)) {
            $this->removeDirectory($this->test_cache_dir);
        }
        
        parent::tearDown();
    }

    /**
     * Test: Verificar creación de archivos de cache
     */
    public function test_cache_file_creation() {
        $cache_file = $this->test_cache_dir . '/test_cache.json';
        $cache_data = ['test' => 'data', 'timestamp' => time()];
        
        // Crear archivo de cache
        $json_data = json_encode($cache_data);
        $result = file_put_contents($cache_file, $json_data);
        
        $this->assertNotFalse($result, 'El archivo de cache debe crearse exitosamente');
        $this->assertFileExists($cache_file, 'El archivo de cache debe existir');
        $this->test_files[] = $cache_file;
        
        // Verificar contenido
        $retrieved_data = json_decode(file_get_contents($cache_file), true);
        $this->assertEquals($cache_data, $retrieved_data, 'Los datos deben ser idénticos');
    }

    /**
     * Test: Verificar lectura de archivos de cache
     */
    public function test_cache_file_reading() {
        $cache_file = $this->test_cache_dir . '/read_test.txt';
        $test_content = 'Este es contenido de prueba para cache';
        
        // Crear archivo
        file_put_contents($cache_file, $test_content);
        $this->test_files[] = $cache_file;
        
        // Leer archivo
        $read_content = file_get_contents($cache_file);
        
        $this->assertIsString($read_content, 'El contenido leído debe ser string');
        $this->assertEquals($test_content, $read_content, 'El contenido debe coincidir');
        $this->assertGreaterThan(0, filesize($cache_file), 'El archivo debe tener contenido');
    }

    /**
     * Test: Verificar eliminación de archivos de cache
     */
    public function test_cache_file_deletion() {
        $cache_file = $this->test_cache_dir . '/delete_test.log';
        
        // Crear archivo
        file_put_contents($cache_file, 'contenido temporal');
        $this->assertFileExists($cache_file, 'El archivo debe existir inicialmente');
        
        // Eliminar archivo
        $deleted = unlink($cache_file);
        
        $this->assertTrue($deleted, 'La eliminación debe ser exitosa');
        $this->assertFileDoesNotExist($cache_file, 'El archivo no debe existir después de eliminarlo');
    }

    /**
     * Test: Verificar listado de archivos en directorio
     */
    public function test_cache_directory_listing() {
        // Crear varios archivos de prueba
        $files = ['file1.txt', 'file2.json', 'file3.log'];
        foreach ($files as $filename) {
            $filepath = $this->test_cache_dir . '/' . $filename;
            file_put_contents($filepath, 'contenido de ' . $filename);
            $this->test_files[] = $filepath;
        }
        
        // Listar archivos
        $directory_files = scandir($this->test_cache_dir);
        $directory_files = array_diff($directory_files, ['.', '..']); // Remover . y ..
        
        $this->assertCount(3, $directory_files, 'Debe haber exactamente 3 archivos');
        
        foreach ($files as $filename) {
            $this->assertContains($filename, $directory_files, "El archivo {$filename} debe estar en el listado");
        }
    }

    /**
     * Test: Verificar cálculo de tamaño de archivos
     */
    public function test_cache_file_size_calculation() {
        $test_content = str_repeat('A', 1024); // 1KB de contenido
        $cache_file = $this->test_cache_dir . '/size_test.txt';
        
        file_put_contents($cache_file, $test_content);
        $this->test_files[] = $cache_file;
        
        $file_size = filesize($cache_file);
        
        $this->assertEquals(1024, $file_size, 'El tamaño del archivo debe ser 1024 bytes');
        $this->assertIsInt($file_size, 'El tamaño debe ser un entero');
        $this->assertGreaterThan(0, $file_size, 'El tamaño debe ser mayor que 0');
    }

    /**
     * Test: Verificar limpieza masiva de archivos
     */
    public function test_cache_bulk_cleanup() {
        // Crear múltiples archivos
        $created_files = [];
        for ($i = 1; $i <= 5; $i++) {
            $filepath = $this->test_cache_dir . "/bulk_file_{$i}.tmp";
            file_put_contents($filepath, "contenido del archivo {$i}");
            $created_files[] = $filepath;
            $this->test_files[] = $filepath;
        }
        
        // Verificar que se crearon
        foreach ($created_files as $file) {
            $this->assertFileExists($file, 'Cada archivo debe existir antes de la limpieza');
        }
        
        // Simular limpieza masiva
        $deleted_count = 0;
        foreach ($created_files as $file) {
            if (unlink($file)) {
                $deleted_count++;
            }
        }
        
        $this->assertEquals(5, $deleted_count, 'Deben eliminarse todos los archivos');
        
        // Verificar que se eliminaron
        foreach ($created_files as $file) {
            $this->assertFileDoesNotExist($file, 'Cada archivo debe estar eliminado después de la limpieza');
        }
    }

    /**
     * Test: Verificar validación de extensiones de archivo
     */
    public function test_cache_file_extension_validation() {
        $valid_extensions = ['json', 'txt', 'log', 'cache'];
        $test_files = [
            'data.json' => true,
            'log.txt' => true,
            'debug.log' => true,
            'temp.cache' => true,
            'script.php' => false,
            'style.css' => false,
            'image.jpg' => false
        ];
        
        foreach ($test_files as $filename => $should_be_valid) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $is_valid = in_array($extension, $valid_extensions);
            
            if ($should_be_valid) {
                $this->assertTrue($is_valid, "El archivo {$filename} debe ser válido");
            } else {
                $this->assertFalse($is_valid, "El archivo {$filename} no debe ser válido");
            }
        }
    }

    /**
     * Método auxiliar para eliminar directorios recursivamente
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
