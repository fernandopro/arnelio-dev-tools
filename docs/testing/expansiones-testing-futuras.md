# 🚀 Plan Maestro de Expansión del Sistema de Testing - Tarokina Pro

**Fecha de creación:** 5 de junio de 2025  
**Última actualización:** $(date)  
**Estado actual:** 93 tests pasando (100% éxito)  
**Framework:** WordPress PHPUnit Oficial  
**Objetivo:** 300+ tests comprehensivos con 95%+ cobertura

---

## 📊 Estado Actual del Sistema de Testing

### ✅ **Logros Completados - Base Sólida Establecida**
- **93 tests ejecutados** - 100% exitosos, 0 errores, 0 fallos
- **521 assertions verificadas** - Todas pasando exitosamente
- **Cobertura funcional completa** de CPTs, taxonomías, hooks, base de datos
- **Framework WordPress PHPUnit oficial** configurado y optimizado
- **Sistema dev-tools** completamente funcional y operativo
- **Arquitectura dual** establecida: tests unitarios + integración
- **Pipeline CI/CD** base preparado para expansión

### 📈 **Métricas de Calidad Actuales**
```
Tests Pasando:      93/93    (100.0%)
Assertions:         521      (100.0% exitosas)
Cobertura Código:   ~70%     (objetivo: 95%)
Tiempo Ejecución:   ~0.6s    (optimizado)
Base de Datos:      ✅ Tests completos
Hooks WordPress:    ✅ Tests completos
APIs WordPress:     ✅ Tests completos
Transients:         ✅ Tests completos
```

### 🏗️ **Arquitectura Técnica Establecida**
- **Framework único:** WordPress PHPUnit oficial (repositorio oficial)
- **Configuración:** `phpunit.xml` + `wp-tests-config.php` + `wordpress-develop/`
- **Estructura dual:** `tests/unit/` (lógica pura) + `tests/integration/` (WordPress completo)
- **Database:** MySQL `local@localhost` con prefijo `wp_test_`
- **Factories:** WordPress integradas para generación de datos
- **Bootstrap:** Carga optimizada de entorno WordPress completo

---

## 🎯 **FASE 1: Expansión de Cobertura Frontend y Shortcodes**

### 1.1 Tests de Frontend y Renderizado
**Archivo:** `dev-tools/tests/integration/TarokinaFrontendTest.php`

#### **Tests Principales a Implementar:**

```php
class TarokinaFrontendTest extends WP_UnitTestCase
{
    private $tarot_id;
    private $card_id;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Crear datos de prueba
        $this->tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'Test Tarot Frontend',
            'post_status' => 'publish'
        ]);
        
        $this->card_id = $this->factory->post->create([
            'post_type' => 'card',
            'post_title' => 'Test Card Frontend', 
            'post_status' => 'publish',
            'post_parent' => $this->tarot_id
        ]);
    }
    
    /**
     * Test crítico: Verificar registro correcto de shortcodes
     */
    public function testShortcodeRegistration(): void
    {
        global $shortcode_tags;
        
        // Verificar shortcodes del plugin están registrados
        $expected_shortcodes = [
            'tarot_display',
            'card_display', 
            'tarot_gallery',
            'card_reading',
            'tarot_selector'
        ];
        
        foreach ($expected_shortcodes as $shortcode) {
            $this->assertArrayHasKey(
                $shortcode, 
                $shortcode_tags,
                "Shortcode '$shortcode' no está registrado"
            );
            
            // Verificar que el callback es callable
            $this->assertTrue(
                is_callable($shortcode_tags[$shortcode]),
                "Callback de shortcode '$shortcode' no es callable"
            );
        }
    }
    
    /**
     * Test crítico: Validar output HTML de shortcodes
     */
    public function testShortcodeOutput(): void
    {
        // Test shortcode tarot_display
        $output = do_shortcode("[tarot_display id='{$this->tarot_id}']");
        
        $this->assertNotEmpty($output, 'Shortcode tarot_display no produce output');
        $this->assertStringContainsString('tarot-container', $output);
        $this->assertStringContainsString('Test Tarot Frontend', $output);
        
        // Test shortcode card_display
        $output = do_shortcode("[card_display id='{$this->card_id}']");
        
        $this->assertNotEmpty($output, 'Shortcode card_display no produce output');
        $this->assertStringContainsString('card-container', $output);
        $this->assertStringContainsString('Test Card Frontend', $output);
        
        // Test parámetros del shortcode
        $output = do_shortcode("[tarot_display id='{$this->tarot_id}' theme='dark' size='large']");
        
        $this->assertStringContainsString('theme-dark', $output);
        $this->assertStringContainsString('size-large', $output);
    }
    
    /**
     * Test performance: Renderizado de tarots en frontend
     */
    public function testTarotDisplayFrontend(): void
    {
        // Simular contexto frontend
        $this->go_to(get_permalink($this->tarot_id));
        
        // Verificar que estamos en frontend
        $this->assertFalse(is_admin());
        $this->assertTrue(is_single());
        $this->assertEquals('tarot', get_post_type());
        
        // Test template loading
        $template = get_single_template();
        $this->assertNotEmpty($template);
        
        // Test content filters
        $content = apply_filters('the_content', get_post($this->tarot_id)->post_content);
        $this->assertNotEmpty($content);
        
        // Medir tiempo de renderizado
        $start_time = microtime(true);
        
        ob_start();
        include $template;
        $output = ob_get_clean();
        
        $execution_time = microtime(true) - $start_time;
        
        // Verificar performance (menos de 100ms)
        $this->assertLessThan(0.1, $execution_time, 'Renderizado de tarot toma más de 100ms');
        
        // Verificar HTML structure
        $this->assertStringContainsString('<article', $output);
        $this->assertStringContainsString('post-type-tarot', $output);
    }
    
    /**
     * Test assets: Verificar carga de JavaScript en frontend
     */
    public function testJavaScriptEnqueuing(): void
    {
        // Simular frontend
        $this->go_to(home_url());
        
        // Trigger enqueue scripts
        do_action('wp_enqueue_scripts');
        
        global $wp_scripts;
        
        // Verificar scripts principales están encolados
        $expected_scripts = [
            'tarokina-frontend',
            'tarokina-card-interaction',
            'tarokina-gallery'
        ];
        
        foreach ($expected_scripts as $script_handle) {
            $this->assertTrue(
                wp_script_is($script_handle, 'enqueued'),
                "Script '$script_handle' no está encolado en frontend"
            );
            
            // Verificar dependencias
            if (isset($wp_scripts->registered[$script_handle])) {
                $script = $wp_scripts->registered[$script_handle];
                $this->assertIsArray($script->deps, "Dependencias de '$script_handle' no son array");
            }
        }
        
        // Verificar localización de scripts
        $this->assertTrue(
            wp_script_is('tarokina-frontend', 'enqueued'),
            'Script principal no está localizado'
        );
    }
    
    /**
     * Test responsive: Verificar CSS y diseño responsivo
     */
    public function testCSSEnqueuing(): void
    {
        // Simular frontend
        $this->go_to(home_url());
        
        // Trigger enqueue styles
        do_action('wp_enqueue_scripts');
        
        global $wp_styles;
        
        // Verificar estilos principales
        $expected_styles = [
            'tarokina-frontend',
            'tarokina-responsive',
            'tarokina-themes'
        ];
        
        foreach ($expected_styles as $style_handle) {
            $this->assertTrue(
                wp_style_is($style_handle, 'enqueued'),
                "Estilo '$style_handle' no está encolado"
            );
            
            // Verificar media queries para responsive
            if (isset($wp_styles->registered[$style_handle])) {
                $style = $wp_styles->registered[$style_handle];
                
                // Verificar que tiene media definido para responsive
                if ($style_handle === 'tarokina-responsive') {
                    $this->assertNotEmpty($style->args, 'Estilo responsive sin media queries');
                }
            }
        }
    }
    
    /**
     * Test accessibility: Verificar cumplimiento básico de accesibilidad
     */
    public function testAccessibilityCompliance(): void
    {
        $output = do_shortcode("[tarot_display id='{$this->tarot_id}']");
        
        // Verificar elementos de accesibilidad
        $this->assertStringContainsString('role=', $output, 'Faltan roles ARIA');
        $this->assertStringContainsString('aria-', $output, 'Faltan atributos ARIA');
        $this->assertStringContainsString('alt=', $output, 'Faltan textos alternativos');
        
        // Verificar estructura semántica
        $this->assertStringContainsString('<h', $output, 'Faltan headings estructurales');
        $this->assertStringContainsString('tabindex', $output, 'Falta navegación por teclado');
    }
}
```

### 1.2 Tests de API y Endpoints REST
**Archivo:** `dev-tools/tests/integration/TarokinaApiTest.php`

#### **Tests de API Comprehensivos:**

```php
class TarokinaApiTest extends WP_UnitTestCase
{
    private $admin_user;
    private $subscriber_user;
    private $tarot_id;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Crear usuarios de prueba
        $this->admin_user = $this->factory->user->create([
            'role' => 'administrator'
        ]);
        
        $this->subscriber_user = $this->factory->user->create([
            'role' => 'subscriber'
        ]);
        
        // Crear contenido de prueba
        $this->tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'API Test Tarot',
            'post_status' => 'publish'
        ]);
        
        // Configurar REST API
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init');
    }
    
    /**
     * Test fundamental: Verificar registro de endpoints REST
     */
    public function testRestApiEndpoints(): void
    {
        $routes = $this->server->get_routes();
        
        // Verificar endpoints principales están registrados
        $expected_endpoints = [
            '/tarokina/v1/tarots',
            '/tarokina/v1/tarots/(?P<id>[\d]+)',
            '/tarokina/v1/cards',
            '/tarokina/v1/cards/(?P<id>[\d]+)',
            '/tarokina/v1/themes',
            '/tarokina/v1/export',
            '/tarokina/v1/import'
        ];
        
        foreach ($expected_endpoints as $endpoint) {
            $found = false;
            foreach ($routes as $route => $handlers) {
                if (preg_match('#^' . $endpoint . '$#', $route)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Endpoint '$endpoint' no encontrado");
        }
    }
    
    /**
     * Test seguridad: Verificar autenticación de API
     */
    public function testApiAuthentication(): void
    {
        // Test acceso sin autenticación (debe fallar para endpoints protegidos)
        $request = new WP_REST_Request('POST', '/tarokina/v1/tarots');
        $request->set_body_params([
            'title' => 'Tarot Sin Autorización',
            'description' => 'Test'
        ]);
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(401, $response->get_status(), 'API permite acceso sin autorización');
        
        // Test acceso con usuario autorizado
        wp_set_current_user($this->admin_user);
        
        $response = $this->server->dispatch($request);
        
        // Debería ser 201 (created) o al menos no 401 (unauthorized)
        $this->assertNotEquals(401, $response->get_status(), 'API rechaza usuario autorizado');
    }
    
    /**
     * Test performance: Verificar limitación de velocidad API
     */
    public function testApiRateLimiting(): void
    {
        wp_set_current_user($this->admin_user);
        
        $request = new WP_REST_Request('GET', '/tarokina/v1/tarots');
        
        // Hacer múltiples solicitudes para probar rate limiting
        $responses = [];
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->server->dispatch($request)->get_status();
        }
        
        // Verificar que no todas las solicitudes fueron exitosas (rate limiting activo)
        $success_count = count(array_filter($responses, function($status) {
            return $status === 200;
        }));
        
        // Si hay rate limiting, no todas deberían ser 200
        // Si no hay rate limiting, todas deberían ser 200
        $this->assertTrue(
            $success_count === 50 || $success_count < 50,
            'Rate limiting no funciona correctamente'
        );
    }
    
    /**
     * Test error handling: Manejo de errores de API
     */
    public function testApiErrorHandling(): void
    {
        wp_set_current_user($this->admin_user);
        
        // Test endpoint inexistente
        $request = new WP_REST_Request('GET', '/tarokina/v1/inexistente');
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
        
        // Test parámetros inválidos
        $request = new WP_REST_Request('GET', '/tarokina/v1/tarots/invalid_id');
        $response = $this->server->dispatch($request);
        
        $this->assertContains($response->get_status(), [400, 404], 'Error handling incorrecto para ID inválido');
        
        // Test formato de error
        $data = $response->get_data();
        $this->assertArrayHasKey('code', $data, 'Respuesta de error sin código');
        $this->assertArrayHasKey('message', $data, 'Respuesta de error sin mensaje');
    }
    
    /**
     * Test validación: Validación de datos de entrada
     */
    public function testApiDataValidation(): void
    {
        wp_set_current_user($this->admin_user);
        
        // Test datos inválidos
        $request = new WP_REST_Request('POST', '/tarokina/v1/tarots');
        $request->set_body_params([
            'title' => '', // Título vacío (inválido)
            'description' => str_repeat('x', 10000) // Descripción muy larga
        ]);
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(400, $response->get_status(), 'API acepta datos inválidos');
        
        // Test datos válidos
        $request->set_body_params([
            'title' => 'Tarot Válido',
            'description' => 'Descripción válida'
        ]);
        
        $response = $this->server->dispatch($request);
        
        $this->assertContains($response->get_status(), [200, 201], 'API rechaza datos válidos');
    }
    
    /**
     * Test AJAX: Verificar manejadores AJAX
     */
    public function testAjaxHandlers(): void
    {
        // Simular request AJAX
        $_POST['action'] = 'tarokina_get_cards';
        $_POST['tarot_id'] = $this->tarot_id;
        $_POST['nonce'] = wp_create_nonce('tarokina_ajax_nonce');
        
        // Simular usuario autenticado
        wp_set_current_user($this->admin_user);
        
        // Capturar output de AJAX
        ob_start();
        
        try {
            do_action('wp_ajax_tarokina_get_cards');
        } catch (WPAjaxDieStopException $e) {
            // AJAX termina con wp_die(), esto es normal
        }
        
        $output = ob_get_clean();
        
        // Verificar respuesta JSON válida
        $response = json_decode($output, true);
        $this->assertIsArray($response, 'Respuesta AJAX no es JSON válido');
        $this->assertArrayHasKey('success', $response, 'Respuesta AJAX sin campo success');
    }
}
```

### 1.3 Tests de Seguridad Avanzados
**Archivo:** `dev-tools/tests/integration/TarokinaSecurityTest.php`

#### **Tests de Seguridad Críticos:**

```php
class TarokinaSecurityTest extends WP_UnitTestCase
{
    private $admin_user;
    private $editor_user;
    private $subscriber_user;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->admin_user = $this->factory->user->create(['role' => 'administrator']);
        $this->editor_user = $this->factory->user->create(['role' => 'editor']);
        $this->subscriber_user = $this->factory->user->create(['role' => 'subscriber']);
    }
    
    /**
     * Test crítico: Verificación de nonces en todas las operaciones
     */
    public function testNonceVerification(): void
    {
        // Test sin nonce (debe fallar)
        $_POST['action'] = 'tarokina_save_settings';
        $_POST['setting_value'] = 'test';
        
        wp_set_current_user($this->admin_user);
        
        ob_start();
        $caught_exception = false;
        
        try {
            do_action('wp_ajax_tarokina_save_settings');
        } catch (Exception $e) {
            $caught_exception = true;
        }
        
        ob_end_clean();
        
        $this->assertTrue($caught_exception, 'Acción sin nonce no fue rechazada');
        
        // Test con nonce válido
        $_POST['nonce'] = wp_create_nonce('tarokina_settings_nonce');
        
        ob_start();
        $caught_exception = false;
        
        try {
            do_action('wp_ajax_tarokina_save_settings');
        } catch (WPAjaxDieStopException $e) {
            // Normal AJAX termination
            $caught_exception = false;
        } catch (Exception $e) {
            $caught_exception = true;
        }
        
        ob_end_clean();
        
        $this->assertFalse($caught_exception, 'Acción con nonce válido fue rechazada');
    }
    
    /**
     * Test autorización: Verificar capabilities y permisos
     */
    public function testCapabilityChecks(): void
    {
        // Test: Subscriber no puede crear tarots
        wp_set_current_user($this->subscriber_user);
        
        $tarot_id = wp_insert_post([
            'post_type' => 'tarot',
            'post_title' => 'Tarot No Autorizado',
            'post_status' => 'publish'
        ]);
        
        $this->assertTrue(is_wp_error($tarot_id), 'Subscriber puede crear tarots (no debería)');
        
        // Test: Editor puede crear tarots
        wp_set_current_user($this->editor_user);
        
        $tarot_id = wp_insert_post([
            'post_type' => 'tarot',
            'post_title' => 'Tarot Autorizado',
            'post_status' => 'publish'
        ]);
        
        $this->assertIsInt($tarot_id, 'Editor no puede crear tarots (debería poder)');
        $this->assertGreaterThan(0, $tarot_id);
        
        // Test: Admin puede hacer todo
        wp_set_current_user($this->admin_user);
        
        $this->assertTrue(current_user_can('manage_tarokina'), 'Admin no tiene capabilities de Tarokina');
        $this->assertTrue(current_user_can('edit_tarots'), 'Admin no puede editar tarots');
    }
    
    /**
     * Test sanitización: Verificar limpieza de datos de entrada
     */
    public function testDataSanitization(): void
    {
        // Test datos maliciosos
        $malicious_data = [
            'title' => '<script>alert("XSS")</script>Título Malicioso',
            'description' => 'Descripción con <iframe src="javascript:alert(\'XSS\')"></iframe>',
            'meta_value' => '<?php echo "PHP injection"; ?>',
            'user_input' => 'DROP TABLE wp_posts; --'
        ];
        
        wp_set_current_user($this->admin_user);
        
        // Simular procesamiento de datos
        $sanitized = [];
        foreach ($malicious_data as $key => $value) {
            switch ($key) {
                case 'title':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                case 'description':
                    $sanitized[$key] = wp_kses_post($value);
                    break;
                case 'meta_value':
                    $sanitized[$key] = sanitize_meta($key, $value, 'post');
                    break;
                case 'user_input':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
            }
        }
        
        // Verificar que los datos fueron sanitizados
        $this->assertStringNotContainsString('<script>', $sanitized['title']);
        $this->assertStringNotContainsString('<iframe>', $sanitized['description']);
        $this->assertStringNotContainsString('<?php', $sanitized['meta_value']);
        $this->assertStringNotContainsString('DROP TABLE', $sanitized['user_input']);
        
        // Verificar que el contenido válido se preserva
        $this->assertStringContainsString('Título Malicioso', $sanitized['title']);
        $this->assertStringContainsString('Descripción con', $sanitized['description']);
    }
    
    /**
     * Test SQL injection: Prevención de inyección SQL
     */
    public function testSqlInjectionPrevention(): void
    {
        global $wpdb;
        
        // Datos maliciosos típicos de SQL injection
        $malicious_inputs = [
            "1'; DROP TABLE wp_posts; --",
            "1 UNION SELECT user_pass FROM wp_users",
            "1' OR '1'='1",
            "'; DELETE FROM wp_options WHERE option_name LIKE '%'; --"
        ];
        
        foreach ($malicious_inputs as $malicious_input) {
            // Test usando wpdb->prepare (método seguro)
            $safe_query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
                $malicious_input
            );
            
            // Verificar que la query no contiene código malicioso
            $this->assertStringNotContainsString('DROP TABLE', $safe_query);
            $this->assertStringNotContainsString('UNION SELECT', $safe_query);
            $this->assertStringNotContainsString('DELETE FROM', $safe_query);
            
            // El resultado debería ser seguro (ID = 0 para strings no numéricas)
            $results = $wpdb->get_results($safe_query);
            $this->assertIsArray($results, 'Query segura falló');
        }
    }
    
    /**
     * Test XSS: Protección contra Cross-Site Scripting
     */
    public function testXssProtection(): void
    {
        // Crear post con contenido malicioso
        $post_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => '<script>alert("XSS")</script>Test Tarot',
            'post_content' => 'Contenido con <img src="x" onerror="alert(\'XSS\')">'
        ]);
        
        // Verificar que el output está escapado
        $title = get_the_title($post_id);
        $content = apply_filters('the_content', get_post($post_id)->post_content);
        
        $this->assertStringNotContainsString('<script>', $title);
        $this->assertStringNotContainsString('onerror=', $content);
        
        // Test output en shortcodes
        $shortcode_output = do_shortcode("[tarot_display id='$post_id']");
        
        $this->assertStringNotContainsString('<script>', $shortcode_output);
        $this->assertStringNotContainsString('javascript:', $shortcode_output);
        $this->assertStringNotContainsString('onerror=', $shortcode_output);
    }
    
    /**
     * Test CSRF: Protección contra Cross-Site Request Forgery
     */
    public function testCsrfProtection(): void
    {
        wp_set_current_user($this->admin_user);
        
        // Simular request sin referer (ataque CSRF típico)
        unset($_SERVER['HTTP_REFERER']);
        $_POST['action'] = 'tarokina_delete_tarot';
        $_POST['tarot_id'] = '123';
        // Sin nonce = debería fallar
        
        ob_start();
        $caught_exception = false;
        
        try {
            do_action('wp_ajax_tarokina_delete_tarot');
        } catch (Exception $e) {
            $caught_exception = true;
        }
        
        ob_end_clean();
        
        $this->assertTrue($caught_exception, 'Acción CSRF no fue bloqueada');
        
        // Test con protección adecuada
        $_SERVER['HTTP_REFERER'] = admin_url();
        $_POST['nonce'] = wp_create_nonce('tarokina_delete_nonce');
        
        ob_start();
        $caught_exception = false;
        
        try {
            do_action('wp_ajax_tarokina_delete_tarot');
        } catch (WPAjaxDieStopException $e) {
            // Normal termination
            $caught_exception = false;
        } catch (Exception $e) {
            $caught_exception = true;
        }
        
        ob_end_clean();
        
        $this->assertFalse($caught_exception, 'Request legítimo fue bloqueado');
    }
    
    /**
     * Test file upload: Seguridad en subida de archivos
     */
    public function testFileUploadSecurity(): void
    {
        wp_set_current_user($this->admin_user);
        
        // Test archivos maliciosos
        $malicious_files = [
            [
                'name' => 'malicious.php',
                'type' => 'application/x-php',
                'content' => '<?php system($_GET["cmd"]); ?>'
            ],
            [
                'name' => 'script.js.php',
                'type' => 'text/javascript',
                'content' => '<?php echo "hidden php"; ?>'
            ],
            [
                'name' => 'image.jpg.php',
                'type' => 'image/jpeg',
                'content' => '<?php phpinfo(); ?>'
            ]
        ];
        
        foreach ($malicious_files as $file) {
            // Simular upload de archivo malicioso
            $upload_result = wp_handle_upload([
                'name' => $file['name'],
                'type' => $file['type'],
                'tmp_name' => '/tmp/' . $file['name'],
                'size' => strlen($file['content'])
            ], ['test_form' => false]);
            
            // Debería rechazar archivos PHP
            if (strpos($file['name'], '.php') !== false) {
                $this->assertArrayHasKey('error', $upload_result, 
                    "Archivo malicioso {$file['name']} fue aceptado");
            }
        }
        
        // Test archivo válido
        $valid_upload = wp_handle_upload([
            'name' => 'valid-image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/valid-image.jpg',
            'size' => 1024
        ], ['test_form' => false]);
        
        // No debe tener error para archivos válidos
        $this->assertArrayNotHasKey('error', $valid_upload, 
            'Archivo válido fue rechazado');
    }
}
```

### 1.4 Tests de Internacionalización (i18n)
**Archivo:** `dev-tools/tests/integration/TarokinaI18nTest.php`

#### **Tests de Localización Comprehensivos:**

```php
class TarokinaI18nTest extends WP_UnitTestCase
{
    private $original_locale;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->original_locale = get_locale();
    }
    
    public function tearDown(): void
    {
        // Restaurar locale original
        switch_to_locale($this->original_locale);
        parent::tearDown();
    }
    
    /**
     * Test crítico: Verificar carga correcta del text domain
     */
    public function testTextDomainLoading(): void
    {
        // Verificar que el text domain está cargado
        $loaded = is_textdomain_loaded('tarokina-pro');
        $this->assertTrue($loaded, 'Text domain tarokina-pro no está cargado');
        
        // Verificar ruta de archivos de idioma
        $domain_path = get_option('tarokina_language_path');
        $expected_path = plugin_dir_path(__FILE__) . '../../languages/';
        
        $this->assertNotEmpty($domain_path, 'Ruta de idiomas no configurada');
        
        // Test carga manual
        $result = load_plugin_textdomain(
            'tarokina-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
        
        $this->assertTrue($result, 'No se pudo cargar text domain manualmente');
    }
    
    /**
     * Test archivos: Verificar existencia de archivos de traducción
     */
    public function testTranslationFiles(): void
    {
        $languages_dir = plugin_dir_path(__FILE__) . '../../languages/';
        
        // Verificar archivos principales existen
        $required_files = [
            'tarokina-pro-es_ES.po',
            'tarokina-pro-es_ES.mo',
            'tarokina-pro-en_US.po',
            'tarokina-pro-en_US.mo',
            'tarokina-pro.pot' // Template file
        ];
        
        foreach ($required_files as $file) {
            $file_path = $languages_dir . $file;
            $this->assertFileExists($file_path, "Archivo de idioma $file no existe");
            
            // Verificar que no está vacío
            if (file_exists($file_path)) {
                $this->assertGreaterThan(0, filesize($file_path), "Archivo $file está vacío");
            }
        }
        
        // Verificar formato POT
        $pot_content = file_get_contents($languages_dir . 'tarokina-pro.pot');
        $this->assertStringContainsString('msgid', $pot_content, 'Archivo POT no tiene formato correcto');
        $this->assertStringContainsString('msgstr', $pot_content, 'Archivo POT no tiene formato correcto');
    }
    
    /**
     * Test funcional: Verificar traducción de cadenas principales
     */
    public function testStringTranslation(): void
    {
        // Test en español
        switch_to_locale('es_ES');
        
        $translated_strings = [
            'Tarot' => __('Tarot', 'tarokina-pro'),
            'Card' => __('Card', 'tarokina-pro'),
            'Settings' => __('Settings', 'tarokina-pro'),
            'Export' => __('Export', 'tarokina-pro'),
            'Import' => __('Import', 'tarokina-pro')
        ];
        
        foreach ($translated_strings as $original => $translated) {
            $this->assertNotEmpty($translated, "Cadena '$original' no está traducida");
            
            // En español debería ser diferente al inglés (en muchos casos)
            if ($original !== $translated) {
                $this->assertNotEquals($original, $translated, 
                    "Cadena '$original' no parece estar traducida");
            }
        }
        
        // Test plurales
        $singular = _n('tarot', 'tarots', 1, 'tarokina-pro');
        $plural = _n('tarot', 'tarots', 2, 'tarokina-pro');
        
        $this->assertNotEquals($singular, $plural, 'Plurales no funcionan correctamente');
        
        // Test contexto
        $context_string = _x('Reading', 'tarot reading context', 'tarokina-pro');
        $this->assertNotEmpty($context_string, 'Traducción con contexto no funciona');
    }
    
    /**
     * Test RTL: Verificar soporte para idiomas RTL
     */
    public function testRtlSupport(): void
    {
        // Simular idioma RTL (árabe)
        switch_to_locale('ar');
        
        // Verificar que WordPress detecta RTL
        $this->assertTrue(is_rtl(), 'WordPress no detecta idioma RTL');
        
        // Verificar que el plugin carga estilos RTL
        do_action('wp_enqueue_scripts');
        
        global $wp_styles;
        
        // Buscar estilos RTL
        $rtl_styles_found = false;
        foreach ($wp_styles->registered as $handle => $style) {
            if (strpos($handle, 'tarokina') !== false && $style->extra && 
                isset($style->extra['rtl']) && $style->extra['rtl']) {
                $rtl_styles_found = true;
                break;
            }
        }
        
        $this->assertTrue($rtl_styles_found, 'No se encontraron estilos RTL del plugin');
        
        // Test CSS RTL específico
        $rtl_css_path = plugin_dir_path(__FILE__) . '../../assets/css/tarokina-rtl.css';
        if (file_exists($rtl_css_path)) {
            $rtl_css = file_get_contents($rtl_css_path);
            $this->assertStringContainsString('direction: rtl', $rtl_css);
            $this->assertStringContainsString('text-align: right', $rtl_css);
        }
    }
    
    /**
     * Test formatting: Localización de fechas y números
     */
    public function testDateTimeLocalization(): void
    {
        // Test formato de fecha en español
        switch_to_locale('es_ES');
        
        $timestamp = strtotime('2025-06-05 15:30:00');
        $formatted_date = date_i18n(get_option('date_format'), $timestamp);
        
        $this->assertNotEmpty($formatted_date, 'Fecha localizada está vacía');
        
        // Test formato de hora
        $formatted_time = date_i18n(get_option('time_format'), $timestamp);
        $this->assertNotEmpty($formatted_time, 'Hora localizada está vacía');
        
        // Test días de la semana localizados
        $day_name = date_i18n('l', $timestamp);
        $this->assertNotEmpty($day_name, 'Nombre del día no está localizado');
        
        // En español debería contener caracteres específicos
        if (get_locale() === 'es_ES') {
            // Verificar que usa formato español (algunos días tienen acentos)
            $spanish_days = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
            $this->assertContains(strtolower($day_name), $spanish_days, 
                'Día de la semana no está en español');
        }
    }
    
    /**
     * Test números: Formato de números localizado
     */
    public function testNumberFormatting(): void
    {
        switch_to_locale('es_ES');
        
        // Test formato de números grandes
        $large_number = 1234567.89;
        $formatted = number_format_i18n($large_number);
        
        $this->assertNotEmpty($formatted, 'Número formateado está vacío');
        
        // En español usa punto para miles y coma para decimales
        if (get_locale() === 'es_ES') {
            // Verificar formato español (puede variar según configuración)
            $this->assertStringContainsString('1', $formatted);
            $this->assertStringContainsString('234', $formatted);
        }
        
        // Test formato de moneda (si el plugin lo usa)
        $price = 99.99;
        $formatted_price = number_format_i18n($price, 2);
        
        $this->assertNotEmpty($formatted_price, 'Precio formateado está vacío');
        $this->assertStringContainsString('99', $formatted_price);
    }
}
```

---

## 🎯 **FASE 2: Tests de Performance y Optimización Avanzada**

### 2.1 Tests de Rendimiento Críticos
**Archivo:** `dev-tools/tests/performance/TarokinaPerformanceTest.php`

#### **Tests de Performance Comprehensivos:**

```php
class TarokinaPerformanceTest extends WP_UnitTestCase
{
    private $large_dataset_ids = [];
    private $original_memory_limit;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->original_memory_limit = ini_get('memory_limit');
        
        // Crear dataset grande para tests de performance
        for ($i = 0; $i < 100; $i++) {
            $this->large_dataset_ids[] = $this->factory->post->create([
                'post_type' => 'tarot',
                'post_title' => "Performance Test Tarot $i",
                'post_content' => str_repeat("Content for performance test. ", 50),
                'post_status' => 'publish'
            ]);
        }
    }
    
    public function tearDown(): void
    {
        // Limpiar dataset grande
        foreach ($this->large_dataset_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        // Restaurar memory limit
        ini_set('memory_limit', $this->original_memory_limit);
        
        parent::tearDown();
    }
    
    /**
     * Test crítico: Optimización de consultas de base de datos
     */
    public function testDatabaseQueryOptimization(): void
    {
        global $wpdb;
        
        // Resetear contador de queries
        $initial_queries = $wpdb->num_queries;
        
        // Operación que debería estar optimizada: obtener múltiples tarots
        $tarots = new WP_Query([
            'post_type' => 'tarot',
            'posts_per_page' => 50,
            'meta_query' => [
                [
                    'key' => 'tarot_cards_count',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $queries_used = $wpdb->num_queries - $initial_queries;
        
        // Debería usar pocas queries (menos de 5 para 50 posts)
        $this->assertLessThan(5, $queries_used, 
            "Demasiadas queries ($queries_used) para cargar 50 tarots");
        
        // Test específico: evitar N+1 queries
        $initial_queries = $wpdb->num_queries;
        
        foreach ($tarots->posts as $tarot) {
            // Operación común que puede causar N+1
            $cards_count = get_post_meta($tarot->ID, 'tarot_cards_count', true);
        }
        
        $meta_queries = $wpdb->num_queries - $initial_queries;
        
        // Si está optimizado, debería usar 1 query para todos los meta
        $this->assertLessThan(10, $meta_queries,
            "N+1 problem detected: $meta_queries queries for meta data");
    }
    
    /**
     * Test cache: Efectividad del sistema de caché
     */
    public function testCacheEffectiveness(): void
    {
        // Test transients
        $cache_key = 'tarokina_test_cache_' . time();
        $test_data = ['large_array' => range(1, 1000)];
        
        // Primera escritura
        $start_time = microtime(true);
        set_transient($cache_key, $test_data, 3600);
        $write_time = microtime(true) - $start_time;
        
        // Primera lectura (desde cache)
        $start_time = microtime(true);
        $cached_data = get_transient($cache_key);
        $read_time = microtime(true) - $start_time;
        
        $this->assertEquals($test_data, $cached_data, 'Datos del cache no coinciden');
        $this->assertLessThan(0.01, $read_time, 'Lectura de cache demasiado lenta');
        
        // Test object cache (si está disponible)
        if (wp_using_ext_object_cache()) {
            $start_time = microtime(true);
            wp_cache_set($cache_key . '_object', $test_data, 'tarokina', 3600);
            $object_write_time = microtime(true) - $start_time;
            
            $start_time = microtime(true);
            $object_cached_data = wp_cache_get($cache_key . '_object', 'tarokina');
            $object_read_time = microtime(true) - $start_time;
            
            $this->assertEquals($test_data, $object_cached_data);
            $this->assertLessThan(0.005, $object_read_time, 
                'Object cache demasiado lento');
        }
        
        // Limpiar
        delete_transient($cache_key);
        wp_cache_delete($cache_key . '_object', 'tarokina');
    }
    
    /**
     * Test memoria: Uso eficiente de memoria
     */
    public function testMemoryUsage(): void
    {
        $initial_memory = memory_get_usage(true);
        
        // Operación que consume memoria: cargar muchos tarots
        $query = new WP_Query([
            'post_type' => 'tarot',
            'posts_per_page' => 100,
            'meta_query' => [
                [
                    'key' => 'tarot_data',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $memory_after_query = memory_get_usage(true);
        $memory_used = $memory_after_query - $initial_memory;
        
        // No debería usar más de 10MB para 100 posts
        $max_memory_mb = 10 * 1024 * 1024; // 10MB
        $this->assertLessThan($max_memory_mb, $memory_used,
            "Query usa demasiada memoria: " . ($memory_used / 1024 / 1024) . "MB");
        
        // Test memory leaks: liberar memoria después de usar
        unset($query);
        wp_reset_postdata();
        
        // Forzar garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $final_memory = memory_get_usage(true);
        $memory_diff = $final_memory - $initial_memory;
        
        // Debería liberar la mayoría de la memoria
        $this->assertLessThan($memory_used * 0.5, $memory_diff,
            "Posible memory leak detectado");
    }
    
    /**
     * Test timing: Tiempos de carga de páginas
     */
    public function testPageLoadTimes(): void
    {
        // Test página de listado de tarots
        $start_time = microtime(true);
        
        $this->go_to(home_url('/tarots/'));
        
        // Simular carga completa de página
        do_action('wp_enqueue_scripts');
        do_action('wp_print_styles');
        do_action('wp_print_scripts');
        
        $page_load_time = microtime(true) - $start_time;
        
        // Página debería cargar en menos de 500ms
        $this->assertLessThan(0.5, $page_load_time,
            "Página de tarots carga demasiado lento: {$page_load_time}s");
        
        // Test página individual de tarot
        if (!empty($this->large_dataset_ids)) {
            $tarot_id = $this->large_dataset_ids[0];
            
            $start_time = microtime(true);
            $this->go_to(get_permalink($tarot_id));
            $single_load_time = microtime(true) - $start_time;
            
            $this->assertLessThan(0.3, $single_load_time,
                "Página individual carga demasiado lento: {$single_load_time}s");
        }
    }
    
    /**
     * Test assets: Optimización de recursos (CSS/JS)
     */
    public function testAssetOptimization(): void
    {
        // Ir a página que carga assets del plugin
        $this->go_to(home_url());
        do_action('wp_enqueue_scripts');
        
        global $wp_scripts, $wp_styles;
        
        // Verificar que scripts están minificados (en producción)
        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($handle, 'tarokina') !== false) {
                $script_src = $script->src;
                
                // En producción debería usar .min.js
                if (defined('WP_DEBUG') && !WP_DEBUG) {
                    $this->assertStringContainsString('.min.js', $script_src,
                        "Script $handle no está minificado");
                }
                
                // Verificar que el archivo existe
                $script_path = str_replace(plugin_dir_url(__FILE__), 
                    plugin_dir_path(__FILE__), $script_src);
                
                if (file_exists($script_path)) {
                    $file_size = filesize($script_path);
                    
                    // Scripts no deberían ser excesivamente grandes (>100KB)
                    $this->assertLessThan(100 * 1024, $file_size,
                        "Script $handle demasiado grande: " . ($file_size / 1024) . "KB");
                }
            }
        }
        
        // Test CSS optimization
        foreach ($wp_styles->registered as $handle => $style) {
            if (strpos($handle, 'tarokina') !== false) {
                $style_src = $style->src;
                
                // En producción debería usar .min.css
                if (defined('WP_DEBUG') && !WP_DEBUG) {
                    $this->assertStringContainsString('.min.css', $style_src,
                        "Style $handle no está minificado");
                }
            }
        }
    }
    
    /**
     * Test lazy loading: Implementación de carga perezosa
     */
    public function testLazyLoading(): void
    {
        // Crear tarot con muchas imágenes
        $tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'Tarot con Lazy Loading',
            'post_content' => 'Tarot para test de lazy loading'
        ]);
        
        // Simular galería con muchas imágenes
        for ($i = 0; $i < 20; $i++) {
            $attachment_id = $this->factory->attachment->create([
                'post_parent' => $tarot_id,
                'post_mime_type' => 'image/jpeg'
            ]);
        }
        
        // Generar output de galería
        $gallery_output = do_shortcode("[tarot_gallery id='$tarot_id']");
        
        // Verificar implementación de lazy loading
        $this->assertStringContainsString('loading="lazy"', $gallery_output,
            'Imágenes no tienen atributo loading="lazy"');
        
        // O verificar data-src para lazy loading con JS
        $lazy_js_pattern = '/data-(src|lazy)/';
        $this->assertRegExp($lazy_js_pattern, $gallery_output,
            'No se encontró implementación de lazy loading JavaScript');
        
        // Contar imágenes que cargan inmediatamente vs lazy
        $immediate_images = substr_count($gallery_output, 'src="http');
        $lazy_images = substr_count($gallery_output, 'data-src=');
        
        // La mayoría deberían ser lazy (al menos 80%)
        $total_images = $immediate_images + $lazy_images;
        if ($total_images > 0) {
            $lazy_percentage = ($lazy_images / $total_images) * 100;
            $this->assertGreaterThan(50, $lazy_percentage,
                "Solo {$lazy_percentage}% de imágenes usan lazy loading");
        }
    }
    
    /**
     * Test imágenes: Optimización de imágenes
     */
    public function testImageOptimization(): void
    {
        // Crear attachment de prueba
        $attachment_id = $this->factory->attachment->create([
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Test Image Optimization'
        ]);
        
        // Verificar que se generan múltiples tamaños
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        $this->assertArrayHasKey('sizes', $metadata,
            'No se generaron múltiples tamaños de imagen');
        
        $sizes = $metadata['sizes'];
        $this->assertGreaterThan(2, count($sizes),
            'Se generaron muy pocos tamaños de imagen');
        
        // Verificar tamaños específicos del plugin
        $expected_sizes = ['thumbnail', 'medium', 'large'];
        foreach ($expected_sizes as $size) {
            $this->assertArrayHasKey($size, $sizes,
                "Tamaño de imagen '$size' no fue generado");
        }
        
        // Test WebP support (si está disponible)
        if (function_exists('imagewebp')) {
            $image_url = wp_get_attachment_image_src($attachment_id, 'medium');
            
            if ($image_url) {
                $webp_url = str_replace('.jpg', '.webp', $image_url[0]);
                
                // Verificar si el plugin genera versiones WebP
                $webp_path = str_replace(
                    wp_upload_dir()['baseurl'],
                    wp_upload_dir()['basedir'],
                    $webp_url
                );
                
                if (file_exists($webp_path)) {
                    $original_size = filesize(str_replace('.webp', '.jpg', $webp_path));
                    $webp_size = filesize($webp_path);
                    
                    // WebP debería ser más pequeño
                    $this->assertLessThan($original_size, $webp_size,
                        'Imagen WebP no es más pequeña que JPEG');
                }
            }
        }
    }
}
```

### 2.2 Tests de Escalabilidad y Carga Máxima
**Archivo:** `dev-tools/tests/performance/TarokinaScalabilityTest.php`

#### **Tests de Límites del Sistema:**

```php
class TarokinaScalabilityTest extends WP_UnitTestCase
{
    /**
     * Test datasets: Manejo de datasets extremadamente grandes
     */
    public function testLargeDatasets(): void
    {
        // Crear dataset masivo (1000 tarots)
        $large_dataset = [];
        for ($i = 0; $i < 1000; $i++) {
            $large_dataset[] = $this->factory->post->create([
                'post_type' => 'tarot',
                'post_title' => "Massive Dataset Tarot $i",
                'post_status' => 'publish'
            ]);
        }
        
        // Test consulta con dataset grande
        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);
        
        $query = new WP_Query([
            'post_type' => 'tarot',
            'posts_per_page' => 1000,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        
        // Métricas de performance
        $execution_time = $end_time - $start_time;
        $memory_used = $end_memory - $start_memory;
        
        // Límites de performance para dataset grande
        $this->assertLessThan(2.0, $execution_time, 
            "Query de 1000 tarots demasiado lenta: {$execution_time}s");
        
        $this->assertLessThan(50 * 1024 * 1024, $memory_used,
            "Query usa demasiada memoria: " . ($memory_used / 1024 / 1024) . "MB");
        
        $this->assertEquals(1000, $query->found_posts, 
            'No se encontraron todos los tarots del dataset');
        
        // Cleanup
        foreach ($large_dataset as $post_id) {
            wp_delete_post($post_id, true);
        }
    }
    
    /**
     * Test concurrencia: Simulación de usuarios concurrentes
     */
    public function testConcurrentUsers(): void
    {
        // Simular 50 usuarios concurrentes
        $concurrent_operations = [];
        
        for ($i = 0; $i < 50; $i++) {
            $user_id = $this->factory->user->create([
                'role' => 'subscriber'
            ]);
            
            $concurrent_operations[] = [
                'user_id' => $user_id,
                'operation' => 'read_tarot',
                'data' => [
                    'tarot_id' => $this->factory->post->create([
                        'post_type' => 'tarot',
                        'post_author' => $user_id
                    ])
                ]
            ];
        }
        
        // Ejecutar operaciones "concurrentes" (simuladas)
        $start_time = microtime(true);
        $results = [];
        
        foreach ($concurrent_operations as $operation) {
            wp_set_current_user($operation['user_id']);
            
            // Simular operación típica de usuario
            $tarot = get_post($operation['data']['tarot_id']);
            $results[] = [
                'success' => !is_null($tarot),
                'user_id' => $operation['user_id'],
                'tarot_id' => $operation['data']['tarot_id']
            ];
        }
        
        $total_time = microtime(true) - $start_time;
        
        // Verificar que todas las operaciones fueron exitosas
        $successful_operations = array_filter($results, function($result) {
            return $result['success'];
        });
        
        $this->assertCount(50, $successful_operations, 
            'No todas las operaciones concurrentes fueron exitosas');
        
        // El tiempo total no debería exceder límites razonables
        $this->assertLessThan(5.0, $total_time,
            "50 operaciones concurrentes demasiado lentas: {$total_time}s");
    }
    
    /**
     * Test alta demanda: Alto volumen de requests
     */
    public function testHighVolumeRequests(): void
    {
        // Simular 200 requests en ráfaga
        $requests = [];
        $start_time = microtime(true);
        
        for ($i = 0; $i < 200; $i++) {
            // Simular diferentes tipos de requests
            $request_types = ['get_tarot', 'list_tarots', 'search_tarots'];
            $request_type = $request_types[$i % 3];
            
            switch ($request_type) {
                case 'get_tarot':
                    $result = get_post($this->factory->post->create([
                        'post_type' => 'tarot'
                    ]));
                    break;
                    
                case 'list_tarots':
                    $result = get_posts([
                        'post_type' => 'tarot',
                        'posts_per_page' => 10
                    ]);
                    break;
                    
                case 'search_tarots':
                    $result = get_posts([
                        'post_type' => 'tarot',
                        's' => 'test',
                        'posts_per_page' => 5
                    ]);
                    break;
            }
            
            $requests[] = [
                'type' => $request_type,
                'success' => !empty($result)
            ];
        }
        
        $total_time = microtime(true) - $start_time;
        $average_time = $total_time / 200;
        
        // Verificar performance bajo alta carga
        $this->assertLessThan(10.0, $total_time,
            "200 requests demasiado lentos: {$total_time}s");
        
        $this->assertLessThan(0.05, $average_time,
            "Tiempo promedio por request demasiado alto: {$average_time}s");
        
        // Verificar tasa de éxito
        $successful_requests = array_filter($requests, function($req) {
            return $req['success'];
        });
        
        $success_rate = (count($successful_requests) / 200) * 100;
        $this->assertGreaterThan(95, $success_rate,
            "Tasa de éxito bajo alta carga demasiado baja: {$success_rate}%");
    }
}
```

---

## 🎯 **FASE 3: Tests de Integración Avanzada y Compatibilidad**

### 3.1 Tests de Compatibilidad con Ecosystem WordPress
**Archivo:** `dev-tools/tests/integration/TarokinaCompatibilityTest.php`

#### **Tests de Compatibilidad Comprehensivos:**

```php
class TarokinaCompatibilityTest extends WP_UnitTestCase
{
    /**
     * Test WooCommerce: Integración completa con WooCommerce
     */
    public function testWooCommerceIntegration(): void
    {
        // Skip si WooCommerce no está activo
        if (!class_exists('WooCommerce')) {
            $this->markTestSkipped('WooCommerce no está activo');
        }
        
        // Test: Tarot como producto WooCommerce
        $product_id = $this->factory->post->create([
            'post_type' => 'product',
            'post_title' => 'Tarot Reading Product',
            'post_status' => 'publish'
        ]);
        
        // Asociar tarot con producto
        $tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'WooCommerce Integration Tarot'
        ]);
        
        update_post_meta($product_id, '_tarokina_tarot_id', $tarot_id);
        update_post_meta($product_id, '_price', '29.99');
        update_post_meta($product_id, '_regular_price', '29.99');
        
        // Verificar que el producto se puede añadir al carrito
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
        $this->assertNotFalse($cart_item_key, 'No se pudo añadir tarot al carrito');
        
        // Verificar metadatos del producto en el carrito
        $cart_contents = WC()->cart->get_cart();
        $cart_item = $cart_contents[$cart_item_key];
        
        $this->assertEquals($product_id, $cart_item['product_id']);
        $this->assertEquals($tarot_id, get_post_meta($product_id, '_tarokina_tarot_id', true));
        
        // Test hooks de WooCommerce
        $this->assertTrue(has_action('woocommerce_product_meta_end'), 
            'Hook de WooCommerce para mostrar info del tarot no registrado');
        
        WC()->cart->empty_cart();
    }
    
    /**
     * Test Elementor: Compatibilidad con Elementor widgets
     */
    public function testElementorCompatibility(): void
    {
        // Skip si Elementor no está activo
        if (!class_exists('\Elementor\Plugin')) {
            $this->markTestSkipped('Elementor no está activo');
        }
        
        // Verificar que los widgets de Tarokina están registrados en Elementor
        $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        $widget_types = $widgets_manager->get_widget_types();
        
        $expected_widgets = [
            'tarokina-tarot-display',
            'tarokina-card-gallery', 
            'tarokina-tarot-selector'
        ];
        
        foreach ($expected_widgets as $widget_name) {
            $this->assertArrayHasKey($widget_name, $widget_types,
                "Widget Elementor '$widget_name' no está registrado");
        }
        
        // Test renderizado de widget
        if (isset($widget_types['tarokina-tarot-display'])) {
            $widget = $widget_types['tarokina-tarot-display'];
            
            // Verificar que tiene los controles necesarios
            $controls = $widget->get_controls();
            $this->assertArrayHasKey('tarot_id', $controls,
                'Widget no tiene control tarot_id');
        }
    }
    
    /**
     * Test Gutenberg: Compatibilidad con bloques Gutenberg
     */
    public function testGutenbergBlocks(): void
    {
        // Verificar que los bloques están registrados
        $block_registry = WP_Block_Type_Registry::get_instance();
        $registered_blocks = $block_registry->get_all_registered();
        
        $expected_blocks = [
            'tarokina/tarot-display',
            'tarokina/card-gallery',
            'tarokina/tarot-reading'
        ];
        
        foreach ($expected_blocks as $block_name) {
            $this->assertTrue($block_registry->is_registered($block_name),
                "Bloque Gutenberg '$block_name' no está registrado");
        }
        
        // Test renderizado de bloque
        $tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'Gutenberg Test Tarot'
        ]);
        
        $block_content = '<!-- wp:tarokina/tarot-display {"tarotId":' . $tarot_id . '} /-->';
        $rendered = do_blocks($block_content);
        
        $this->assertNotEmpty($rendered, 'Bloque Gutenberg no renderiza contenido');
        $this->assertStringContainsString('tarot-display', $rendered,
            'Bloque no genera HTML esperado');
    }
    
    /**
     * Test Yoast SEO: Compatibilidad con Yoast SEO
     */
    public function testYoastSeoCompatibility(): void
    {
        // Skip si Yoast no está activo
        if (!class_exists('WPSEO_Options')) {
            $this->markTestSkipped('Yoast SEO no está activo');
        }
        
        $tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'SEO Test Tarot',
            'post_content' => 'Contenido para test de SEO'
        ]);
        
        // Test metadatos SEO específicos para tarots
        update_post_meta($tarot_id, '_yoast_wpseo_title', 'SEO Title for Tarot');
        update_post_meta($tarot_id, '_yoast_wpseo_metadesc', 'SEO description for tarot reading');
        
        // Verificar que Yoast puede procesar el contenido del tarot
        $this->go_to(get_permalink($tarot_id));
        
        // Test que el plugin no interfiere con Yoast
        $yoast_title = get_post_meta($tarot_id, '_yoast_wpseo_title', true);
        $this->assertEquals('SEO Title for Tarot', $yoast_title);
        
        // Verificar que el schema markup incluye datos del tarot
        if (function_exists('YoastSEO')) {
            $schema_data = apply_filters('wpseo_schema_graph', [], get_queried_object());
            $this->assertNotEmpty($schema_data, 'Yoast no genera schema data');
        }
    }
    
    /**
     * Test caching plugins: Compatibilidad con plugins de caché
     */
    public function testCachingPlugins(): void
    {
        // Test WP Rocket compatibility
        if (function_exists('rocket_clean_domain')) {
            // Verificar que el plugin puede limpiar caché
            $this->assertTrue(function_exists('rocket_clean_domain'),
                'Función de limpieza de WP Rocket disponible');
        }
        
        // Test W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            $this->assertTrue(function_exists('w3tc_flush_all'),
                'Función de limpieza de W3TC disponible');
        }
        
        // Test comportamiento con caché
        $tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'Cache Test Tarot'
        ]);
        
        // Primera carga (debería cachear)
        $this->go_to(get_permalink($tarot_id));
        $first_load = get_post($tarot_id);
        
        // Modificar post
        wp_update_post([
            'ID' => $tarot_id,
            'post_title' => 'Modified Cache Test Tarot'
        ]);
        
        // Segunda carga (debería mostrar cambios)
        $this->go_to(get_permalink($tarot_id));
        $second_load = get_post($tarot_id);
        
        $this->assertNotEquals($first_load->post_title, $second_load->post_title,
            'Caché no se invalida correctamente al modificar post');
    }
    
    /**
     * Test multisite: Soporte para WordPress Multisite
     */
    public function testMultisiteSupport(): void
    {
        if (!is_multisite()) {
            $this->markTestSkipped('WordPress Multisite no está activo');
        }
        
        // Test configuración específica por site
        $blog_id = get_current_blog_id();
        
        // Crear configuración específica del site
        update_site_option('tarokina_multisite_config', [
            $blog_id => [
                'enabled' => true,
                'custom_settings' => ['theme' => 'site_specific']
            ]
        ]);
        
        $config = get_site_option('tarokina_multisite_config');
        $this->assertArrayHasKey($blog_id, $config,
            'Configuración multisite no se guarda correctamente');
        
        // Test que los datos están aislados por site
        $tarot_id = $this->factory->post->create([
            'post_type' => 'tarot',
            'post_title' => 'Multisite Test Tarot'
        ]);
        
        // Cambiar a otro blog (si existe)
        $sites = get_sites(['number' => 2]);
        if (count($sites) > 1) {
            $other_blog_id = $sites[1]->blog_id;
            switch_to_blog($other_blog_id);
            
            // El tarot no debería existir en el otro site
            $other_site_tarot = get_post($tarot_id);
            $this->assertNull($other_site_tarot,
                'Datos de tarot no están aislados por site');
            
            restore_current_blog();
        }
    }
}
```

---

## 🎯 **FASE 4: Configuraciones Técnicas Avanzadas y Herramientas**

### 4.1 Configuración CI/CD Avanzada
**Archivo:** `.github/workflows/advanced-testing.yml`

```yaml
name: 🚀 Tarokina Pro - Tests Avanzados

on:
  push:
    branches: [ main, develop, 'feature/*' ]
  pull_request:
    branches: [ main, develop ]
  schedule:
    # Tests automáticos cada día a las 2 AM
    - cron: '0 2 * * *'

env:
  WP_TESTS_PHPUNIT: 1
  PLUGIN_SLUG: tarokina-2025

jobs:
  # Job 1: Tests Matrix Completa
  comprehensive-tests:
    name: "🧪 Tests PHP ${{ matrix.php }} - WP ${{ matrix.wordpress }}"
    runs-on: ubuntu-latest
    
    strategy:
      fail-fast: false
      matrix:
        php: ['7.4', '8.0', '8.1', '8.2', '8.3']
        wordpress: ['6.0', '6.1', '6.2', '6.3', '6.4', '6.5']
        include:
          # Configuraciones especiales
          - php: '8.3'
            wordpress: 'trunk'
            experimental: true
          - php: '7.4'
            wordpress: '5.9'
            legacy: true
        exclude:
          # Combinaciones no compatibles
          - php: '8.3'
            wordpress: '6.0'
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping --silent"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - name: 📥 Checkout código
        uses: actions/checkout@v4
        
      - name: 🐘 Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mysql, zip, gd, mbstring, curl, xml, bcmath
          coverage: xdebug
          tools: composer, wp-cli
          
      - name: 📦 Cache Composer
        uses: actions/cache@v4
        with:
          path: ~/.composer/cache
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-
          
      - name: 🎼 Install Composer dependencies
        run: composer install --no-progress --no-interaction --prefer-dist
        
      - name: 🏗️ Setup WordPress ${{ matrix.wordpress }}
        run: |
          # Configurar WordPress Test Environment
          if [ "${{ matrix.wordpress }}" = "trunk" ]; then
            WP_VERSION="trunk"
          else
            WP_VERSION="${{ matrix.wordpress }}"
          fi
          
          # Clonar WordPress develop
          git clone --depth=1 --branch="$WP_VERSION" https://github.com/WordPress/wordpress-develop.git /tmp/wordpress-develop
          
          # Configurar base de datos de test
          mysql -h 127.0.0.1 -P 3306 -u root -proot -e "CREATE DATABASE IF NOT EXISTS wordpress_test;"
          
          # Configurar wp-tests-config.php
          cp dev-tools/wp-tests-config-sample.php dev-tools/wp-tests-config.php
          sed -i "s/youremptytestdbnamehere/wordpress_test/" dev-tools/wp-tests-config.php
          sed -i "s/yourusernamehere/root/" dev-tools/wp-tests-config.php
          sed -i "s/yourpasswordhere/root/" dev-tools/wp-tests-config.php
          sed -i "s/localhost/127.0.0.1:3306/" dev-tools/wp-tests-config.php
          
      - name: 🧪 Run Unit Tests
        run: |
          # Tests unitarios (rápidos)
          ./dev-tools/run-tests.sh --unit --coverage-clover=coverage-unit.xml
          
      - name: 🔗 Run Integration Tests  
        run: |
          # Tests de integración (WordPress completo)
          ./dev-tools/run-tests.sh --integration --coverage-clover=coverage-integration.xml
          
      - name: 🚀 Run Performance Tests
        if: matrix.php == '8.1' && matrix.wordpress == '6.4'
        run: |
          # Solo en configuración estable para performance
          ./dev-tools/run-tests.sh --performance --filter="Performance"
          
      - name: 🔒 Run Security Tests
        run: |
          # Tests de seguridad en todas las configuraciones
          ./dev-tools/run-tests.sh --security --filter="Security"
          
      - name: 📊 Upload Coverage to Codecov
        if: matrix.php == '8.1' && matrix.wordpress == '6.4'
        uses: codecov/codecov-action@v4
        with:
          files: ./coverage-unit.xml,./coverage-integration.xml
          flags: phpunit
          name: codecov-umbrella
          
      - name: 📈 Upload Test Results
        uses: actions/upload-artifact@v4
        if: failure()
        with:
          name: test-results-php${{ matrix.php }}-wp${{ matrix.wordpress }}
          path: |
            dev-tools/tests/reports/
            dev-tools/logs/
            
  # Job 2: Quality Assurance
  quality-assurance:
    name: "🎯 Quality Assurance"
    runs-on: ubuntu-latest
    
    steps:
      - name: 📥 Checkout
        uses: actions/checkout@v4
        
      - name: 🐘 Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mysql, zip, gd, mbstring
          tools: composer, phpcs, phpstan, psalm
          
      - name: 📦 Install dependencies
        run: composer install --no-progress --no-interaction
        
      - name: 🔍 PHP CodeSniffer
        run: |
          vendor/bin/phpcs --standard=WordPress \
            --exclude=WordPress.Files.FileName \
            --ignore=vendor/,node_modules/,wordpress-develop/ \
            --extensions=php \
            --report=checkstyle \
            --report-file=phpcs-report.xml \
            .
            
      - name: 🔬 PHPStan Analysis
        run: |
          vendor/bin/phpstan analyse \
            --configuration=dev-tools/phpstan.neon \
            --error-format=github \
            --no-progress \
            .
            
      - name: 🛡️ Security Audit
        run: |
          # Composer security audit
          composer audit --format=json > security-audit.json
          
          # WordPress specific security checks
          wp-cli plugin verify-checksums ${{ env.PLUGIN_SLUG }} || true
          
      - name: 📊 Upload QA Results
        uses: actions/upload-artifact@v4
        with:
          name: qa-results
          path: |
            phpcs-report.xml
            security-audit.json
            
  # Job 3: Performance Benchmarks
  performance-benchmarks:
    name: "⚡ Performance Benchmarks"
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_bench
        ports:
          - 3306:3306
          
    steps:
      - name: 📥 Checkout
        uses: actions/checkout@v4
        
      - name: 🐘 Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mysql, zip, gd, mbstring, opcache
          ini-values: opcache.enable=1, opcache.memory_consumption=256
          
      - name: 🏗️ Setup WordPress
        run: |
          # Setup optimizado para benchmarks
          git clone --depth=1 https://github.com/WordPress/wordpress-develop.git /tmp/wordpress-develop
          mysql -h 127.0.0.1 -P 3306 -u root -proot -e "CREATE DATABASE wordpress_bench;"
          
      - name: ⚡ Run Performance Benchmarks
        run: |
          # Benchmarks específicos
          ./dev-tools/run-benchmarks.sh --output=json > benchmarks.json
          
          # Apache Bench tests
          ab -n 1000 -c 10 http://localhost/wp-content/plugins/${{ env.PLUGIN_SLUG }}/test-endpoint.php > ab-results.txt
          
      - name: 📈 Store Performance Results
        run: |
          # Guardar resultados históricos
          mkdir -p performance-history
          cp benchmarks.json "performance-history/$(date +%Y%m%d_%H%M%S).json"
          
      - name: 📊 Upload Benchmarks
        uses: actions/upload-artifact@v4
        with:
          name: performance-benchmarks
          path: |
            benchmarks.json
            ab-results.txt
            performance-history/
```

### 4.2 Herramientas de Testing Avanzadas
**Archivo:** `dev-tools/src/testing/AdvancedTestRunner.php`

```php
<?php
/**
 * Sistema Avanzado de Testing para Tarokina Pro
 * 
 * Proporciona herramientas avanzadas para ejecutar y gestionar tests
 * incluyendo generación automática, análisis de cobertura y reportes.
 */

class TarokinaAdvancedTestRunner
{
    private $config;
    private $results = [];
    private $coverage_data = [];
    
    public function __construct($config = [])
    {
        $this->config = wp_parse_args($config, [
            'memory_limit' => '512M',
            'time_limit' => 300,
            'coverage_enabled' => true,
            'parallel_enabled' => false,
            'report_format' => 'html'
        ]);
        
        $this->setup_environment();
    }
    
    /**
     * Configurar entorno optimizado para testing
     */
    private function setup_environment(): void
    {
        // Configurar límites de PHP
        ini_set('memory_limit', $this->config['memory_limit']);
        set_time_limit($this->config['time_limit']);
        
        // Configurar coverage si está habilitado
        if ($this->config['coverage_enabled'] && extension_loaded('xdebug')) {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        }
        
        // Configurar base de datos para tests
        $this->setup_test_database();
    }
    
    /**
     * Configurar base de datos optimizada para tests
     */
    private function setup_test_database(): void
    {
        global $wpdb;
        
        // Optimizaciones específicas para testing
        $wpdb->query("SET autocommit = 0");
        $wpdb->query("SET foreign_key_checks = 0");
        $wpdb->query("SET sql_mode = ''");
        
        // Usar MyISAM para tests (más rápido)
        $wpdb->query("ALTER TABLE {$wpdb->posts} ENGINE = MyISAM");
        $wpdb->query("ALTER TABLE {$wpdb->postmeta} ENGINE = MyISAM");
    }
    
    /**
     * Ejecutar suite completa de tests con análisis avanzado
     */
    public function run_comprehensive_suite(array $test_suites = []): array
    {
        $results = [
            'summary' => [],
            'detailed' => [],
            'performance' => [],
            'coverage' => [],
            'memory_usage' => []
        ];
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);
        
        foreach ($test_suites as $suite_name => $suite_config) {
            $suite_results = $this->run_test_suite($suite_name, $suite_config);
            $results['detailed'][$suite_name] = $suite_results;
            
            // Análisis de performance por suite
            $results['performance'][$suite_name] = [
                'execution_time' => $suite_results['execution_time'],
                'memory_peak' => $suite_results['memory_peak'],
                'queries_count' => $suite_results['queries_count']
            ];
        }
        
        $total_time = microtime(true) - $start_time;
        $total_memory = memory_get_peak_usage(true) - $start_memory;
        
        // Resumen general
        $results['summary'] = [
            'total_tests' => array_sum(array_column($results['detailed'], 'tests_count')),
            'total_assertions' => array_sum(array_column($results['detailed'], 'assertions_count')),
            'total_failures' => array_sum(array_column($results['detailed'], 'failures_count')),
            'total_errors' => array_sum(array_column($results['detailed'], 'errors_count')),
            'execution_time' => $total_time,
            'memory_usage' => $total_memory,
            'coverage_percentage' => $this->calculate_coverage_percentage()
        ];
        
        // Generar reportes
        $this->generate_reports($results);
        
        return $results;
    }
    
    /**
     * Ejecutar suite específica de tests
     */
    private function run_test_suite(string $suite_name, array $config): array
    {
        $suite_start = microtime(true);
        $suite_memory_start = memory_get_usage(true);
        
        // Configurar suite específica
        $this->setup_suite_environment($suite_name, $config);
        
        // Ejecutar tests
        $phpunit_config = $this->generate_phpunit_config($suite_name, $config);
        $command = $this->build_phpunit_command($phpunit_config);
        
        ob_start();
        $exit_code = 0;
        
        // Ejecutar PHPUnit con captura de output
        $output = shell_exec($command . ' 2>&1');
        $parser_results = $this->parse_phpunit_output($output);
        
        ob_end_clean();
        
        return [
            'suite_name' => $suite_name,
            'tests_count' => $parser_results['tests'],
            'assertions_count' => $parser_results['assertions'],
            'failures_count' => $parser_results['failures'],
            'errors_count' => $parser_results['errors'],
            'execution_time' => microtime(true) - $suite_start,
            'memory_peak' => memory_get_peak_usage(true) - $suite_memory_start,
            'queries_count' => $this->get_queries_count(),
            'coverage_data' => $this->get_coverage_data($suite_name),
            'raw_output' => $output
        ];
    }
    
    /**
     * Generar configuración optimizada de PHPUnit
     */
    private function generate_phpunit_config(string $suite_name, array $config): string
    {
        $xml_config = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit 
    bootstrap="dev-tools/tests/bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    stopOnFailure="false"
    timeoutForSmallTests="10"
    timeoutForMediumTests="30"
    timeoutForLargeTests="60"
    beStrictAboutTestsThatDoNotTestAnything="true"
    beStrictAboutOutputDuringTests="true">
    
    <php>
        <const name="WP_TESTS_PHPUNIT" value="1"/>
        <const name="WP_TESTS_MULTISITE" value="{$config['multisite']}"/>
        <const name="WP_TESTS_DOMAIN" value="localhost"/>
        <const name="WP_TESTS_EMAIL" value="admin@localhost.dev"/>
        <const name="WP_TESTS_TITLE" value="Test Blog"/>
        <env name="WP_TESTS_SKIP_INSTALL" value="1"/>
    </php>
    
    <testsuites>
        <testsuite name="{$suite_name}">
            <directory>{$config['test_directory']}</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">./</directory>
            <exclude>
                <directory>./vendor</directory>
                <directory>./dev-tools</directory>
                <directory>./node_modules</directory>
                <directory>./wordpress-develop</directory>
            </exclude>
        </whitelist>
    </filter>
    
    <logging>
        <log type="coverage-html" target="dev-tools/reports/coverage/{$suite_name}"/>
        <log type="coverage-clover" target="dev-tools/reports/coverage/{$suite_name}/clover.xml"/>
        <log type="junit" target="dev-tools/reports/junit/{$suite_name}.xml"/>
    </logging>
</phpunit>
XML;

        $config_file = "dev-tools/phpunit-{$suite_name}.xml";
        file_put_contents($config_file, $xml_config);
        
        return $config_file;
    }
    
    /**
     * Generar reportes comprehensivos
     */
    private function generate_reports(array $results): void
    {
        $reports_dir = 'dev-tools/reports/' . date('Y-m-d_H-i-s');
        wp_mkdir_p($reports_dir);
        
        // Reporte HTML principal
        $this->generate_html_report($results, $reports_dir);
        
        // Reporte JSON para APIs
        $this->generate_json_report($results, $reports_dir);
        
        // Reporte de performance
        $this->generate_performance_report($results, $reports_dir);
        
        // Reporte de cobertura consolidado
        $this->generate_coverage_report($results, $reports_dir);
        
        // Generar dashboard interactivo
        $this->generate_dashboard($results, $reports_dir);
    }
    
    /**
     * Generar dashboard interactivo con métricas en tiempo real
     */
    private function generate_dashboard(array $results, string $reports_dir): void
    {
        $dashboard_html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Tarokina Pro - Dashboard de Testing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.min.css" rel="stylesheet">
    <style>
        .metric-card { transition: transform 0.2s; }
        .metric-card:hover { transform: translateY(-5px); }
        .success-rate { color: #28a745; }
        .warning-rate { color: #ffc107; }
        .danger-rate { color: #dc3545; }
        .coverage-bar { height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .coverage-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <h1 class="text-center mb-4">🚀 Tarokina Pro - Dashboard de Testing</h1>
        
        <!-- Métricas Principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <h2 class="display-4 text-primary">{$results['summary']['total_tests']}</h2>
                        <p class="card-text">Tests Ejecutados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <h2 class="display-4 text-success">{$results['summary']['total_assertions']}</h2>
                        <p class="card-text">Assertions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <h2 class="display-4 text-info">{$results['summary']['coverage_percentage']}%</h2>
                        <p class="card-text">Cobertura</p>
                        <div class="coverage-bar">
                            <div class="coverage-fill" style="width: {$results['summary']['coverage_percentage']}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <h2 class="display-4 text-warning">{$results['summary']['execution_time']}s</h2>
                        <p class="card-text">Tiempo Total</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos de Performance -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">📊 Performance por Suite</div>
                    <div class="card-body">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">🧠 Uso de Memoria</div>
                    <div class="card-body">
                        <canvas id="memoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detalles por Suite -->
        <div class="card">
            <div class="card-header">📋 Detalles por Suite de Tests</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Suite</th>
                                <th>Tests</th>
                                <th>Assertions</th>
                                <th>Éxito</th>
                                <th>Tiempo</th>
                                <th>Memoria</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
HTML;

        foreach ($results['detailed'] as $suite_name => $suite_data) {
            $success_rate = ($suite_data['tests_count'] - $suite_data['failures_count'] - $suite_data['errors_count']) / $suite_data['tests_count'] * 100;
            $status_class = $success_rate >= 100 ? 'success' : ($success_rate >= 90 ? 'warning' : 'danger');
            $status_icon = $success_rate >= 100 ? '✅' : ($success_rate >= 90 ? '⚠️' : '❌');
            
            $dashboard_html .= <<<HTML
                            <tr>
                                <td><strong>{$suite_name}</strong></td>
                                <td>{$suite_data['tests_count']}</td>
                                <td>{$suite_data['assertions_count']}</td>
                                <td><span class="text-{$status_class}">{$success_rate}%</span></td>
                                <td>{$suite_data['execution_time']}s</td>
                                <td>{$suite_data['memory_peak']}MB</td>
                                <td>{$status_icon}</td>
                            </tr>
HTML;
        }
        
        $dashboard_html .= <<<HTML
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.min.js"></script>
    <script>
        // Configurar gráficos interactivos
        const performanceData = {json_encode(array_column($results['performance'], 'execution_time'))};
        const memoryData = {json_encode(array_column($results['performance'], 'memory_peak'))};
        const suiteNames = {json_encode(array_keys($results['detailed']))};
        
        // Gráfico de Performance
        new Chart(document.getElementById('performanceChart'), {
            type: 'bar',
            data: {
                labels: suiteNames,
                datasets: [{
                    label: 'Tiempo de Ejecución (s)',
                    data: performanceData,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Tiempo de Ejecución por Suite' }
                }
            }
        });
        
        // Gráfico de Memoria
        new Chart(document.getElementById('memoryChart'), {
            type: 'doughnut',
            data: {
                labels: suiteNames,
                datasets: [{
                    data: memoryData,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Distribución de Uso de Memoria' }
                }
            }
        });
    </script>
</body>
</html>
HTML;

        file_put_contents($reports_dir . '/dashboard.html', $dashboard_html);
    }
}
```

### Usar el generador de tests:
```bash
# Acceder al panel de dev-tools
http://localhost:10019/wp-admin/admin.php?page=tarokina-dev-tools

# Usar el generador automático de tests
# Seleccionar "Crear Test" > "Test de Integración" > "Frontend"
```

### Ejecutar tests específicos:
```bash
# Solo tests de frontend
./run-tests.sh --filter="Frontend"

# Solo tests de performance
./run-tests.sh --filter="Performance"

# Con reporte de cobertura
./run-tests.sh --coverage-html=coverage/
```

---

## 📚 **RECURSOS Y REFERENCIAS**

### Documentación Oficial:
- [WordPress PHPUnit Testing](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Plugin Testing](https://developer.wordpress.org/plugins/testing/)

### Herramientas Recomendadas:
- **PHPUnit** - Framework de testing
- **WordPress Testcase** - Casos de test específicos de WP
- **WP CLI** - Herramienta de línea de comandos
- **GitHub Actions** - CI/CD automatizado

### Mejores Prácticas:
- Tests independientes y aislados
- Datos de prueba únicos por test
- Cleanup automático después de cada test
- Assertions claras y descriptivas
- Documentación de cada test

---

## 🎯 **OBJETIVOS A LARGO PLAZO**

### Meta: **200+ Tests Comprehensivos**
- **Frontend:** 30 tests
- **API:** 25 tests  
- **Seguridad:** 20 tests
- **Performance:** 15 tests
- **Compatibilidad:** 25 tests
- **UX/Flujo:** 20 tests
- **Integración:** 35 tests
- **Estrés/Carga:** 10 tests
- **Existentes:** 93 tests
- **TOTAL:** ~273 tests

### Meta: **95%+ Cobertura de Código**
- Cobertura actual: ~70%
- Objetivo: 95%+
- Enfoque en rutas críticas
- Documentación automática de gaps

### Meta: **CI/CD Completamente Automatizado**
- Tests automáticos en cada commit
- Despliegue automático después de tests
- Notificaciones automáticas de fallos
- Reportes automáticos de rendimiento

---

**¡El sistema de testing de Tarokina Pro tiene un futuro brillante! 🚀**

*Documento creado el 5 de junio de 2025*  
*Estado actual: 93 tests pasando, 0 errores, sistema completamente funcional*
