<?php
/**
 * Endpoint para generar nonces válidos desde el navegador
 * Solo funciona para usuarios autenticados
 */

// Agregar endpoint para generar nonce válido
add_action('wp_ajax_generate_dev_tools_nonce', function() {
    // Verificar que el usuario esté logueado y sea admin
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'Unauthorized - must be logged in admin'
        ]), 403);
    }
    
    $config = dev_tools_config();
    $nonce_action = $config->get('ajax.nonce_action');
    $fresh_nonce = wp_create_nonce($nonce_action);
    
    wp_die(json_encode([
        'success' => true,
        'data' => [
            'nonce' => $fresh_nonce,
            'nonce_action' => $nonce_action,
            'user_id' => get_current_user_id(),
            'user_login' => wp_get_current_user()->user_login,
            'timestamp' => current_time('c')
        ]
    ]));
});

// Agregar también para usuarios no logueados (para debugging)
add_action('wp_ajax_nopriv_generate_dev_tools_nonce', function() {
    wp_die(json_encode([
        'success' => false,
        'message' => 'Must be logged in to generate nonce'
    ]), 401);
});
