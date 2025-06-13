<?php
/**
 * Ejemplo corregido de Mocks y Stubs funcional
 * Dev-Tools Arquitectura 3.0 - Testing Framework
 */

class WorkingMockStubExampleTest extends DevToolsTestCase {

namespace DevTools\Tests\Unit;

    
    private $db_mock;
    private $user_service_stub;
    private $logger_mock;
    
    public function setUp(): void {
        parent::setUp();
        
        // ✅ STUB - Crear servicio de usuario personalizado
        $this->user_service_stub = $this->createUserServiceStub();
        
        // ✅ MOCK - Usar wpdb que sí está disponible
        global $wpdb;
        $this->db_mock = $this->createMock(get_class($wpdb));
        
        // ✅ MOCK - Crear logger personalizado
        $this->logger_mock = $this->createLoggerMock();
    }
    
    /**
     * ✅ STUB EXAMPLE - Datos fijos de usuario
     */
    public function test_user_service_with_stub() {
        // El stub proporciona datos predefinidos
        $user_data = $this->user_service_stub->getUserData(123);
        
        // Verificar que devuelve siempre los mismos datos
        $this->assertEquals('admin@test.com', $user_data['email']);
        $this->assertEquals('Admin User', $user_data['name']);
        $this->assertTrue($user_data['is_admin']);
        
        // Los stubs son predecibles
        $same_data = $this->user_service_stub->getUserData(999);
        $this->assertEquals($user_data, $same_data, 'Stub devuelve datos consistentes');
    }
    
    /**
     * ✅ MOCK EXAMPLE - Verificar comportamiento de database
     */
    public function test_database_query_with_mock() {
        // Configurar expectativas del mock
        $this->db_mock->expects($this->once())
                      ->method('prepare')
                      ->with(
                          $this->stringContains('SELECT'),
                          $this->equalTo(123)
                      )
                      ->willReturn('SELECT * FROM users WHERE id = 123');
        
        $this->db_mock->expects($this->once())
                      ->method('get_row')
                      ->willReturn((object)[
                          'id' => 123,
                          'name' => 'Test User',
                          'email' => 'test@example.com'
                      ]);
        
        // Crear el servicio que usa la database
        $db_service = new DatabaseUserService($this->db_mock);
        $user = $db_service->findUser(123);
        
        // Verificar resultado
        $this->assertEquals(123, $user->id);
        $this->assertEquals('Test User', $user->name);
        
        // ✅ Las expectativas del mock se verifican automáticamente
    }
    
    /**
     * ✅ MOCK EXAMPLE - Verificar logging con parámetros específicos
     */
    public function test_logger_behavior_with_mock() {
        // Configurar expectativas específicas
        $this->logger_mock->expects($this->exactly(2))
                          ->method('log')
                          ->withConsecutive(
                              ['info', $this->stringContains('User login')],
                              ['warning', $this->stringContains('Failed attempt')]
                          );
        
        // Crear servicio que debe usar el logger
        $auth_service = new AuthService($this->logger_mock);
        
        // Simular intentos de login
        $auth_service->attemptLogin('user@test.com', 'correct_password');
        $auth_service->attemptLogin('user@test.com', 'wrong_password');
        
        // ✅ El mock verifica que se llamaron los métodos correctamente
    }
    
    /**
     * ✅ COMBINADO - Stub para datos, Mock para comportamiento
     */
    public function test_complete_user_workflow() {
        // Stub para datos de usuario consistentes
        $user_data = $this->user_service_stub->getUserData(123);
        
        // Mock para verificar que se guarde en database
        $this->db_mock->expects($this->once())
                      ->method('update')
                      ->with(
                          'wp_users',
                          $this->arrayHasKey('last_login'),
                          ['ID' => 123]
                      )
                      ->willReturn(1);
        
        // Mock para verificar logging
        $this->logger_mock->expects($this->once())
                          ->method('log')
                          ->with('info', $this->stringContains('User 123 logged in'));
        
        // Crear servicio completo
        $user_manager = new UserManager(
            $this->user_service_stub,
            $this->db_mock,
            $this->logger_mock
        );
        
        // Ejecutar workflow completo
        $result = $user_manager->processLogin(123);
        
        // Verificar resultado
        $this->assertTrue($result);
    }
    
    /**
     * Helper para crear stub de servicio de usuario
     */
    private function createUserServiceStub() {
        $stub = $this->createStub(UserServiceInterface::class);
        
        // ✅ Configurar respuestas predefinidas
        $stub->method('getUserData')
             ->willReturn([
                 'id' => 123,
                 'name' => 'Admin User',
                 'email' => 'admin@test.com',
                 'is_admin' => true,
                 'last_login' => '2025-06-12 10:30:00'
             ]);
        
        $stub->method('userExists')
             ->willReturn(true);
        
        return $stub;
    }
    
    /**
     * Helper para crear mock de logger
     */
    private function createLoggerMock() {
        return $this->createMock(LoggerInterface::class);
    }
}

// =====================================
// INTERFACES Y CLASES DE APOYO
// =====================================

/**
 * Interfaz que podemos mockear fácilmente
 */
interface UserServiceInterface {
    public function getUserData($user_id);
    public function userExists($user_id);
}

/**
 * Interfaz para logger
 */
interface LoggerInterface {
    public function log($level, $message);
}

/**
 * Servicio que usa database (ejemplo)
 */
class WorkingMockStubExampleTest {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findUser($user_id) {
        $sql = $this->db->prepare('SELECT * FROM users WHERE id = %d', $user_id);
        return $this->db->get_row($sql);
    }
}

/**
 * Servicio de autenticación (ejemplo)
 */
class WorkingMockStubExampleTest {
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function attemptLogin($email, $password) {
        if ($password === 'correct_password') {
            $this->logger->log('info', "User login successful: {$email}");
            return true;
        } else {
            $this->logger->log('warning', "Failed attempt for: {$email}");
            return false;
        }
    }
}

/**
 * Manager completo de usuarios (ejemplo)
 */
class WorkingMockStubExampleTest {
    private $user_service;
    private $db;
    private $logger;
    
    public function __construct($user_service, $db, $logger) {
        $this->user_service = $user_service;
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function processLogin($user_id) {
        $user_data = $this->user_service->getUserData($user_id);
        
        if ($user_data) {
            // Actualizar last_login
            $this->db->update(
                'wp_users',
                ['last_login' => current_time('mysql')],
                ['ID' => $user_id]
            );
            
            // Log del evento
            $this->logger->log('info', "User {$user_id} logged in successfully");
            
            return true;
        }
        
        return false;
    }
}
