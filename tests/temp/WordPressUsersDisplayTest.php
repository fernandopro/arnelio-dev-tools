<?php

/**
 * Test para mostrar todos los usuarios de WordPress en formato tabla
 */

require_once __DIR__ . '/../DevToolsTestCase.php';

class WordPressUsersDisplayTest extends DevToolsTestCase
{
    /**
     * Test principal: mostrar tabla de usuarios de WordPress
     */
    public function testShowWordPressUsersTable(): void
    {
        echo "\n" . str_repeat("=", 100) . "\n";
        echo "USUARIOS DE WORDPRESS - TABLA COMPLETA\n";
        echo str_repeat("=", 100) . "\n\n";

        // Obtener todos los usuarios
        $users = get_users([
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);

        if (empty($users)) {
            echo "⚠️  No se encontraron usuarios en WordPress.\n";
            echo "💡 Esto es extraño, debería haber al menos un usuario administrador.\n\n";
            return;
        }

        echo "👥 TOTAL DE USUARIOS: " . count($users) . "\n\n";

        // Mostrar tabla principal de usuarios
        $this->displayUsersTable($users);

        // Mostrar estadísticas por roles
        $this->displayUserStatistics($users);

        // Mostrar información adicional
        $this->displayAdditionalInfo();

        // Verificación para PHPUnit
        $this->assertTrue(count($users) > 0, 'Se encontraron usuarios en WordPress');
    }

    /**
     * Mostrar tabla principal de usuarios
     */
    private function displayUsersTable($users): void
    {
        echo "📋 TABLA DE USUARIOS:\n";
        echo str_repeat("-", 120) . "\n";
        printf("| %-5s | %-20s | %-25s | %-20s | %-15s | %-20s |\n",
            'ID', 'USUARIO', 'EMAIL', 'NOMBRE COMPLETO', 'ROL', 'REGISTRADO'
        );
        echo "+" . str_repeat("-", 7) . "+" . str_repeat("-", 22) . "+" . str_repeat("-", 27) . "+" . str_repeat("-", 22) . "+" . str_repeat("-", 17) . "+" . str_repeat("-", 22) . "+\n";

        foreach ($users as $user) {
            // Obtener roles del usuario
            $roles = $user->roles;
            $main_role = !empty($roles) ? $roles[0] : 'Sin rol';
            
            // Nombre completo
            $full_name = trim($user->first_name . ' ' . $user->last_name);
            if (empty($full_name)) {
                $full_name = $user->display_name;
            }
            
            // Fecha de registro
            $registered = date('Y-m-d H:i', strtotime($user->user_registered));
            
            printf("| %-5d | %-20s | %-25s | %-20s | %-15s | %-20s |\n",
                $user->ID,
                substr($user->user_login, 0, 20),
                substr($user->user_email, 0, 25),
                substr($full_name, 0, 20),
                substr($main_role, 0, 15),
                $registered
            );
        }

        echo "+" . str_repeat("-", 7) . "+" . str_repeat("-", 22) . "+" . str_repeat("-", 27) . "+" . str_repeat("-", 22) . "+" . str_repeat("-", 17) . "+" . str_repeat("-", 22) . "+\n\n";
    }

    /**
     * Mostrar estadísticas por roles
     */
    private function displayUserStatistics($users): void
    {
        echo "📊 ESTADÍSTICAS POR ROLES:\n";
        echo str_repeat("-", 60) . "\n";

        // Contar usuarios por rol
        $role_counts = [];
        $total_active = 0;
        $total_inactive = 0;

        foreach ($users as $user) {
            // Contar por roles
            if (!empty($user->roles)) {
                foreach ($user->roles as $role) {
                    if (!isset($role_counts[$role])) {
                        $role_counts[$role] = 0;
                    }
                    $role_counts[$role]++;
                }
            } else {
                if (!isset($role_counts['sin_rol'])) {
                    $role_counts['sin_rol'] = 0;
                }
                $role_counts['sin_rol']++;
            }

            // Verificar si el usuario está activo (último login reciente)
            $last_login = get_user_meta($user->ID, 'last_login', true);
            if ($last_login && (time() - strtotime($last_login)) < (30 * DAY_IN_SECONDS)) {
                $total_active++;
            } else {
                $total_inactive++;
            }
        }

        // Mostrar tabla de roles
        printf("| %-25s | %-10s | %-15s |\n", 'ROL', 'CANTIDAD', 'PORCENTAJE');
        echo "+" . str_repeat("-", 27) . "+" . str_repeat("-", 12) . "+" . str_repeat("-", 17) . "+\n";

        foreach ($role_counts as $role => $count) {
            $percentage = round(($count / count($users)) * 100, 1);
            $role_name = $this->getRoleDisplayName($role);
            
            printf("| %-25s | %-10d | %-15s |\n",
                substr($role_name, 0, 25),
                $count,
                $percentage . '%'
            );
        }

        echo "+" . str_repeat("-", 27) . "+" . str_repeat("-", 12) . "+" . str_repeat("-", 17) . "+\n\n";

        // Resumen de actividad
        echo "📈 RESUMEN DE ACTIVIDAD:\n";
        echo "• Total de usuarios: " . count($users) . "\n";
        echo "• Usuarios potencialmente activos (últimos 30 días): {$total_active}\n";
        echo "• Usuarios inactivos o sin datos: {$total_inactive}\n\n";
    }

    /**
     * Obtener nombre descriptivo del rol
     */
    private function getRoleDisplayName($role): string
    {
        $role_names = [
            'administrator' => 'Administrador',
            'editor' => 'Editor',
            'author' => 'Autor',
            'contributor' => 'Contribuidor',
            'subscriber' => 'Suscriptor',
            'shop_manager' => 'Gestor de Tienda',
            'customer' => 'Cliente',
            'sin_rol' => 'Sin Rol Asignado'
        ];

        return $role_names[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Mostrar información adicional del sistema
     */
    private function displayAdditionalInfo(): void
    {
        echo "🔧 INFORMACIÓN DEL SISTEMA:\n";
        echo str_repeat("-", 60) . "\n";

        // Configuración de usuarios
        $anyone_can_register = get_option('users_can_register');
        $default_role = get_option('default_role');
        $admin_email = get_option('admin_email');

        echo "• Registro abierto: " . ($anyone_can_register ? 'Sí' : 'No') . "\n";
        echo "• Rol por defecto: " . $this->getRoleDisplayName($default_role) . "\n";
        echo "• Email del administrador: {$admin_email}\n";

        // Información de la base de datos
        global $wpdb;
        $table_name = $wpdb->users;
        $user_meta_table = $wpdb->usermeta;

        echo "• Tabla de usuarios: {$table_name}\n";
        echo "• Tabla de metadatos: {$user_meta_table}\n";

        // Enlaces útiles
        echo "\n💡 ENLACES DE GESTIÓN:\n";
        echo "• Gestionar usuarios: /wp-admin/users.php\n";
        echo "• Añadir nuevo usuario: /wp-admin/user-new.php\n";
        echo "• Configuración general: /wp-admin/options-general.php\n";
        echo "• Fecha del análisis: " . current_time('Y-m-d H:i:s') . "\n\n";
    }

    /**
     * Test adicional: verificar capacidades de usuarios
     */
    public function testUserCapabilities(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "CAPACIDADES DE USUARIOS POR ROL\n";
        echo str_repeat("=", 80) . "\n";

        // Obtener todos los roles disponibles
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $roles = $wp_roles->get_names();

        foreach ($roles as $role_key => $role_name) {
            $role_obj = get_role($role_key);
            
            if (!$role_obj) {
                continue;
            }

            echo "\n🎭 ROL: {$role_name} ({$role_key})\n";
            echo str_repeat("-", 50) . "\n";

            $capabilities = $role_obj->capabilities;
            $cap_count = 0;

            echo "Capacidades principales:\n";
            foreach ($capabilities as $cap => $granted) {
                if ($granted && $cap_count < 10) { // Mostrar solo las primeras 10
                    echo "• {$cap}\n";
                    $cap_count++;
                }
            }

            if (count($capabilities) > 10) {
                $remaining = count($capabilities) - 10;
                echo "... y {$remaining} capacidades más\n";
            }

            echo "Total de capacidades: " . count($capabilities) . "\n";
        }

        echo "\n✅ Verificación de capacidades completada.\n";
        echo str_repeat("=", 80) . "\n\n";

        $this->assertTrue(count($roles) > 0, 'Se encontraron roles en el sistema');
    }

    /**
     * Test de exportación: generar JSON de usuarios
     */
    public function testExportUsersAsJson(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "EXPORTACIÓN JSON - USUARIOS DE WORDPRESS\n";
        echo str_repeat("=", 80) . "\n\n";

        // Obtener usuarios con campos específicos
        $users = get_users([
            'fields' => 'all',
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);

        if (empty($users)) {
            echo "⚠️  No hay usuarios para exportar.\n\n";
            return;
        }

        // Preparar datos para JSON
        $export_data = [];
        
        foreach ($users as $user) {
            $export_data[] = [
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $user->roles,
                'registered' => $user->user_registered,
                'url' => $user->user_url,
                'status' => $user->user_status
            ];
        }

        echo "📄 DATOS EN FORMATO JSON:\n";
        echo str_repeat("-", 50) . "\n";
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        echo "✅ Exportación JSON completada.\n";
        echo "📊 Total de usuarios exportados: " . count($export_data) . "\n";
        echo str_repeat("=", 80) . "\n\n";

        $this->assertTrue(count($export_data) > 0, 'Datos de usuarios exportados correctamente');
    }
}
