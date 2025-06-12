# Guía de Mocks y Stubs - Dev-Tools Arquitectura 3.0

## Conceptos Fundamentales

### Stubs (Dobles de Prueba)
Los **Stubs** son objetos que **reemplazan** dependencias reales con implementaciones simplificadas que devuelven valores predefinidos.

### Mocks (Objetos Simulados)
Los **Mocks** son objetos que **verifican** cómo interactúa tu código con sus dependencias, además de proporcionar respuestas.

---

## Aplicaciones Específicas en Dev-Tools

### 🔧 Stubs - Casos de Uso

#### 1. Simular Respuestas de APIs Externas

```php
/**
 * Stub para simular respuestas de APIs externas
 * Útil para testing de módulos que consumen APIs
 */
class ExternalApiStub {
    private $responses = [];
    
    public function setResponse($endpoint, $response) {
        $this->responses[$endpoint] = $response;
    }
    
    public function get($endpoint) {
        return $this->responses[$endpoint] ?? ['error' => 'Not found'];
    }
}

// Ejemplo de uso en test
public function test_api_integration_module() {
    $api_stub = new ExternalApiStub();
    $api_stub->setResponse('/user/123', [
        'id' => 123,
        'name' => 'Test User',
        'status' => 'active'
    ]);
    
    $module = new ApiIntegrationModule($api_stub);
    $result = $module->getUserData(123);
    
    $this->assertEquals('Test User', $result['name']);
}
```

#### 2. Reemplazar Consultas a Base de Datos

```php
/**
 * Stub para simular operaciones de base de datos
 * Evita dependencias de la base de datos real en tests
 */
class DatabaseStub {
    private $data = [];
    
    public function get($table, $id) {
        return $this->data[$table][$id] ?? null;
    }
    
    public function insert($table, $data) {
        $id = count($this->data[$table] ?? []) + 1;
        $this->data[$table][$id] = array_merge($data, ['id' => $id]);
        return $id;
    }
    
    public function seedTestData() {
        $this->data['dev_tools_logs'] = [
            1 => ['id' => 1, 'level' => 'info', 'message' => 'Test log'],
            2 => ['id' => 2, 'level' => 'error', 'message' => 'Test error']
        ];
    }
}

// Ejemplo en test de LogsModule
public function test_logs_retrieval() {
    $db_stub = new DatabaseStub();
    $db_stub->seedTestData();
    
    $logs_module = new LogsModule($db_stub);
    $logs = $logs_module->getErrorLogs();
    
    $this->assertCount(1, $logs);
    $this->assertEquals('error', $logs[0]['level']);
}
```

#### 3. Simular Sistemas de Archivos

```php
/**
 * Stub para operaciones de sistema de archivos
 * Útil para testing sin crear archivos reales
 */
class FileSystemStub {
    private $files = [];
    private $directories = [];
    
    public function fileExists($path) {
        return isset($this->files[$path]);
    }
    
    public function readFile($path) {
        return $this->files[$path] ?? false;
    }
    
    public function writeFile($path, $content) {
        $this->files[$path] = $content;
        return true;
    }
    
    public function createDirectory($path) {
        $this->directories[$path] = true;
        return true;
    }
    
    public function seedFiles() {
        $this->files['/dev-tools/config/settings.json'] = '{"debug": true}';
        $this->files['/dev-tools/logs/error.log'] = 'Sample error log';
    }
}

// Ejemplo en test de FileManagerModule
public function test_config_file_reading() {
    $fs_stub = new FileSystemStub();
    $fs_stub->seedFiles();
    
    $file_manager = new FileManagerModule($fs_stub);
    $config = $file_manager->getConfig();
    
    $this->assertTrue($config['debug']);
}
```

#### 4. Proporcionar Configuraciones Fijas

```php
/**
 * Stub para configuraciones del sistema
 * Proporciona valores fijos para testing
 */
class ConfigStub {
    private $config = [
        'debug' => true,
        'log_level' => 'info',
        'cache_enabled' => false,
        'api_endpoints' => [
            'users' => 'https://api.test.com/users',
            'logs' => 'https://api.test.com/logs'
        ],
        'modules' => [
            'dashboard' => true,
            'system_info' => true,
            'cache_manager' => false
        ]
    ];
    
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
}

// Ejemplo en test de ConfigManager
public function test_module_activation_status() {
    $config_stub = new ConfigStub();
    
    $config_manager = new ConfigManager($config_stub);
    
    $this->assertTrue($config_manager->isModuleActive('dashboard'));
    $this->assertFalse($config_manager->isModuleActive('cache_manager'));
}
```

### 🎯 Mocks - Casos de Uso

#### 1. Verificar Métodos AJAX

```php
/**
 * Mock para verificar llamadas AJAX correctas
 * Comprueba que los endpoints se llamen con los parámetros correctos
 */
public function test_ajax_handler_processes_requests() {
    // Mock del AJAX handler
    $ajax_mock = $this->createMock(DevToolsAjaxHandler::class);
    
    // Expectativa: debe llamarse handle_request con parámetros específicos
    $ajax_mock->expects($this->once())
              ->method('handle_request')
              ->with(
                  $this->equalTo('system_info'),
                  $this->isType('array')
              )
              ->willReturn(['status' => 'success', 'data' => []]);
    
    $module = new SystemInfoModule($ajax_mock);
    $result = $module->processAjaxRequest('system_info', []);
    
    $this->assertEquals('success', $result['status']);
}

/**
 * Mock para verificar registro de comandos AJAX
 */
public function test_module_registers_ajax_commands() {
    $ajax_mock = $this->createMock(DevToolsAjaxHandler::class);
    
    // Verificar que se registren todos los comandos esperados
    $ajax_mock->expects($this->exactly(3))
              ->method('register_command')
              ->withConsecutive(
                  ['get_system_info'],
                  ['clear_cache'],
                  ['toggle_debug']
              );
    
    $module = new SystemInfoModule($ajax_mock);
    $module->register_ajax_commands();
}
```

#### 2. Comprobar Registro de Logs

```php
/**
 * Mock para verificar que se registren logs esperados
 * Confirma que eventos importantes se logueen correctamente
 */
public function test_error_handling_logs_correctly() {
    $logger_mock = $this->createMock(DevToolsLogger::class);
    
    // Expectativa: debe loguearse un error específico
    $logger_mock->expects($this->once())
                ->method('error')
                ->with(
                    $this->stringContains('Database connection failed'),
                    $this->isType('array')
                );
    
    $db_module = new DatabaseModule($logger_mock);
    
    // Simular error de conexión
    $db_module->connect('invalid_host');
}

/**
 * Mock para verificar niveles de log correctos
 */
public function test_cache_operations_log_appropriately() {
    $logger_mock = $this->createMock(DevToolsLogger::class);
    
    // Verificar logs de diferentes niveles
    $logger_mock->expects($this->once())
                ->method('info')
                ->with($this->equalTo('Cache cleared successfully'));
    
    $logger_mock->expects($this->once())
                ->method('debug')
                ->with($this->stringContains('Cache stats:'));
    
    $cache_module = new CacheModule($logger_mock);
    $cache_module->clearCache();
}
```

#### 3. Verificar Acciones de WordPress

```php
/**
 * Mock para verificar que se ejecuten acciones de WordPress
 * Confirma que hooks y filters se apliquen correctamente
 */
public function test_module_registers_wordpress_hooks() {
    // Mock de WordPress functions
    $wp_mock = $this->createMock(WordPressFunctions::class);
    
    // Verificar que se registren los hooks esperados
    $wp_mock->expects($this->exactly(2))
            ->method('add_action')
            ->withConsecutive(
                ['wp_ajax_dev_tools_action', $this->isType('callable')],
                ['admin_enqueue_scripts', $this->isType('callable')]
            );
    
    $wp_mock->expects($this->once())
            ->method('add_filter')
            ->with('dev_tools_modules', $this->isType('callable'));
    
    $module = new DashboardModule($wp_mock);
    $module->register_hooks();
}

/**
 * Mock para verificar ejecución de acciones específicas
 */
public function test_plugin_activation_triggers_setup() {
    $wp_mock = $this->createMock(WordPressFunctions::class);
    
    // Verificar que se ejecute do_action con el hook correcto
    $wp_mock->expects($this->once())
            ->method('do_action')
            ->with('dev_tools_activated', $this->isType('array'));
    
    $activator = new DevToolsActivator($wp_mock);
    $activator->activate();
}
```

#### 4. Confirmar Envío de Notificaciones

```php
/**
 * Mock para verificar envío de notificaciones
 * Comprueba que las notificaciones se envíen en los momentos correctos
 */
public function test_error_notifications_sent() {
    $notifier_mock = $this->createMock(DevToolsNotifier::class);
    
    // Verificar que se envíe notificación de error crítico
    $notifier_mock->expects($this->once())
                  ->method('send_notification')
                  ->with(
                      $this->equalTo('critical_error'),
                      $this->stringContains('System failure detected'),
                      $this->equalTo(['type' => 'error', 'priority' => 'high'])
                  );
    
    $monitor = new SystemMonitor($notifier_mock);
    $monitor->handle_critical_error('Database connection lost');
}

/**
 * Mock para verificar notificaciones de éxito
 */
public function test_success_notifications_sent() {
    $notifier_mock = $this->createMock(DevToolsNotifier::class);
    
    // Verificar múltiples tipos de notificación
    $notifier_mock->expects($this->exactly(2))
                  ->method('send_notification')
                  ->withConsecutive(
                      ['cache_cleared', $this->isType('string')],
                      ['system_optimized', $this->isType('string')]
                  );
    
    $maintenance = new MaintenanceModule($notifier_mock);
    $maintenance->run_optimization();
}
```

---

## Patrones de Implementación

### Patrón de Factory para Stubs

```php
/**
 * Factory para crear stubs consistentes
 */
class DevToolsStubFactory {
    public static function createDatabaseStub() {
        $stub = new DatabaseStub();
        $stub->seedTestData();
        return $stub;
    }
    
    public static function createConfigStub($environment = 'testing') {
        $stub = new ConfigStub();
        if ($environment === 'testing') {
            $stub->set('debug', true);
            $stub->set('log_level', 'debug');
        }
        return $stub;
    }
    
    public static function createFileSystemStub() {
        $stub = new FileSystemStub();
        $stub->seedFiles();
        return $stub;
    }
}
```

### Patrón de Builder para Mocks

```php
/**
 * Builder para configurar mocks complejos
 */
class DevToolsMockBuilder {
    private $mock;
    
    public function createAjaxHandlerMock() {
        $this->mock = $this->createMock(DevToolsAjaxHandler::class);
        return $this;
    }
    
    public function expectsCommandRegistration($commands) {
        $this->mock->expects($this->exactly(count($commands)))
                   ->method('register_command')
                   ->withConsecutive(...array_map(function($cmd) {
                       return [$cmd];
                   }, $commands));
        return $this;
    }
    
    public function expectsRequestHandling($action, $response) {
        $this->mock->expects($this->once())
                   ->method('handle_request')
                   ->with($action)
                   ->willReturn($response);
        return $this;
    }
    
    public function build() {
        return $this->mock;
    }
}

// Uso del builder
$ajax_mock = (new DevToolsMockBuilder())
    ->createAjaxHandlerMock()
    ->expectsCommandRegistration(['get_info', 'clear_cache'])
    ->expectsRequestHandling('get_info', ['status' => 'success'])
    ->build();
```

---

## Mejores Prácticas

### Para Stubs:
1. **Datos realistas**: Usa datos que representen casos reales
2. **Estado consistente**: Mantén un estado predecible entre tests
3. **Configuración simple**: Facilita la configuración de diferentes escenarios
4. **Aislamiento**: Cada test debe configurar su propio stub

### Para Mocks:
1. **Expectativas claras**: Define exactamente qué comportamiento esperas
2. **Verificación específica**: Verifica parámetros exactos, no solo que se llame
3. **Fallos informativos**: Proporciona mensajes de error claros
4. **No sobre-verificar**: Solo verifica comportamientos importantes

### Integración con Dev-Tools:
1. **Usar base classes**: Extiende `DevToolsTestCase` para funcionalidad común
2. **Configuración en setUp**: Prepara stubs/mocks en métodos `setUp()`
3. **Limpieza en tearDown**: Limpia estado en métodos `tearDown()`
4. **Grupos de tests**: Organiza tests por tipo de dependencia

---

## Ejemplo Completo: Test de Módulo con Stubs y Mocks

```php
<?php
/**
 * Ejemplo completo combinando Stubs y Mocks
 */
class CacheModuleTest extends DevToolsTestCase {
    private $config_stub;
    private $logger_mock;
    private $notifier_mock;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Stubs para dependencias de datos
        $this->config_stub = DevToolsStubFactory::createConfigStub();
        
        // Mocks para verificar comportamiento
        $this->logger_mock = $this->createMock(DevToolsLogger::class);
        $this->notifier_mock = $this->createMock(DevToolsNotifier::class);
    }
    
    public function test_cache_clear_process() {
        // Configurar stub
        $this->config_stub->set('cache_enabled', true);
        
        // Configurar mocks con expectativas
        $this->logger_mock->expects($this->once())
                          ->method('info')
                          ->with('Cache clearing started');
        
        $this->notifier_mock->expects($this->once())
                           ->method('send_notification')
                           ->with('cache_cleared', $this->isType('string'));
        
        // Ejecutar test
        $cache_module = new CacheModule(
            $this->config_stub,
            $this->logger_mock,
            $this->notifier_mock
        );
        
        $result = $cache_module->clearCache();
        
        // Verificaciones
        $this->assertTrue($result['success']);
        $this->assertStringContains('cleared', $result['message']);
    }
}
```

---

Esta documentación proporciona una base sólida para implementar Mocks y Stubs en el framework de testing de Dev-Tools Arquitectura 3.0, asegurando tests robustos y mantenibles.
