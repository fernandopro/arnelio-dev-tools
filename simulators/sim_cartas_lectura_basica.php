<?php
/**
 * Simulador: Lectura de Cartas Básica
 * Descripción: Simula una lectura básica de 3 cartas para testing
 * Módulo: cartas
 * Autor: Tarokina Pro Team
 * Versión: 1.0.0
 * Fecha: 2025-06-02
 */

// Protección de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simula una lectura básica de 3 cartas
 * 
 * @param array $params Parámetros de configuración
 * @return array Resultado de la simulación
 */
function simulate_lectura_basica($params = []) {
    // Configuración por defecto
    $defaults = [
        'num_cartas' => 3,
        'tipo_baraja' => 'rider_waite',
        'posiciones' => ['pasado', 'presente', 'futuro'],
        'invertidas' => true
    ];
    
    $args = wp_parse_args($params, $defaults);
    
    // Baraja simulada (solo nombres para testing)
    $cartas_disponibles = [
        'El Loco',
        'El Mago',
        'La Suma Sacerdotisa',
        'La Emperatriz',
        'El Emperador',
        'El Hierofante',
        'Los Enamorados',
        'El Carro',
        'La Justicia',
        'El Ermitaño',
        'La Rueda de la Fortuna',
        'La Fuerza',
        'El Colgado',
        'La Muerte',
        'La Templanza',
        'El Diablo',
        'La Torre',
        'La Estrella',
        'La Luna',
        'El Sol',
        'El Juicio',
        'El Mundo'
    ];
    
    // Generar lectura aleatoria
    $lectura = [];
    $cartas_usadas = [];
    
    for ($i = 0; $i < $args['num_cartas']; $i++) {
        // Seleccionar carta aleatoria no usada
        do {
            $carta_index = array_rand($cartas_disponibles);
            $carta_nombre = $cartas_disponibles[$carta_index];
        } while (in_array($carta_index, $cartas_usadas));
        
        $cartas_usadas[] = $carta_index;
        
        // Determinar si está invertida (50% probabilidad si está habilitado)
        $invertida = $args['invertidas'] ? (rand(0, 1) === 1) : false;
        
        // Obtener posición
        $posicion = isset($args['posiciones'][$i]) ? $args['posiciones'][$i] : "Posición " . ($i + 1);
        
        $lectura[] = [
            'posicion' => $posicion,
            'carta' => $carta_nombre,
            'numero' => $carta_index,
            'invertida' => $invertida,
            'significado_base' => get_significado_simulado($carta_nombre, $invertida),
            'descripcion' => get_descripcion_simulada($carta_nombre)
        ];
    }
    
    // Generar interpretación general
    $interpretacion = generar_interpretacion_simulada($lectura);
    
    return [
        'success' => true,
        'data' => [
            'configuracion' => $args,
            'cartas' => $lectura,
            'interpretacion' => $interpretacion,
            'timestamp' => current_time('mysql'),
            'session_id' => wp_generate_uuid4()
        ],
        'message' => 'Lectura simulada generada correctamente'
    ];
}

/**
 * Obtiene un significado simulado para una carta
 */
function get_significado_simulado($carta, $invertida = false) {
    $significados_base = [
        'El Loco' => 'Nuevos comienzos, espontaneidad, inocencia',
        'El Mago' => 'Manifestación, poder personal, acción',
        'La Suma Sacerdotisa' => 'Intuición, sabiduría interior, misterio',
        'La Emperatriz' => 'Fertilidad, abundancia, naturaleza',
        'El Emperador' => 'Autoridad, estructura, control'
        // Agregar más según necesidad
    ];
    
    $significado = isset($significados_base[$carta]) ? $significados_base[$carta] : 'Significado en desarrollo';
    
    if ($invertida) {
        $significado = "Bloqueado: " . $significado;
    }
    
    return $significado;
}

/**
 * Obtiene una descripción simulada para una carta
 */
function get_descripcion_simulada($carta) {
    $descripciones = [
        'El Loco' => 'Una figura joven camina hacia el precipicio con total confianza.',
        'El Mago' => 'Un mago apunta hacia el cielo y la tierra, canalizando energía.',
        'La Suma Sacerdotisa' => 'Una mujer sabia sentada entre dos columnas.'
        // Agregar más según necesidad
    ];
    
    return isset($descripciones[$carta]) ? $descripciones[$carta] : 'Descripción de ' . $carta;
}

/**
 * Genera una interpretación general simulada
 */
function generar_interpretacion_simulada($lectura) {
    $interpretaciones_base = [
        "Esta lectura sugiere un período de transición importante en tu vida.",
        "Las cartas indican la necesidad de equilibrio entre acción e introspección.",
        "Se vislumbra un camino de crecimiento personal y nuevas oportunidades.",
        "Es momento de confiar en tu intuición y tomar decisiones conscientes.",
        "Las energías se alinean para favorecer cambios positivos."
    ];
    
    $interpretacion_base = $interpretaciones_base[array_rand($interpretaciones_base)];
    
    // Agregar detalles basados en las cartas
    $num_invertidas = count(array_filter($lectura, function($carta) {
        return $carta['invertida'];
    }));
    
    if ($num_invertidas > 1) {
        $interpretacion_base .= " Hay energías bloqueadas que requieren atención.";
    } else {
        $interpretacion_base .= " Las energías fluyen de manera armoniosa.";
    }
    
    return $interpretacion_base;
}

/**
 * Función de prueba rápida para el simulador
 */
function test_simulador_lectura_basica() {
    $resultado = simulate_lectura_basica();
    
    if ($resultado['success']) {
        echo "<h3>Simulación de Lectura Básica</h3>";
        echo "<p><strong>Configuración:</strong></p>";
        echo "<pre>" . print_r($resultado['data']['configuracion'], true) . "</pre>";
        
        echo "<p><strong>Cartas:</strong></p>";
        foreach ($resultado['data']['cartas'] as $carta) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h4>" . $carta['posicion'] . "</h4>";
            echo "<p><strong>" . $carta['carta'] . "</strong>" . ($carta['invertida'] ? ' (Invertida)' : '') . "</p>";
            echo "<p>" . $carta['significado_base'] . "</p>";
            echo "<p><em>" . $carta['descripcion'] . "</em></p>";
            echo "</div>";
        }
        
        echo "<p><strong>Interpretación:</strong></p>";
        echo "<p>" . $resultado['data']['interpretacion'] . "</p>";
    } else {
        echo "<p>Error en la simulación</p>";
    }
}

// Si se ejecuta directamente, mostrar test
if (isset($_GET['test_simulador']) && $_GET['test_simulador'] === 'lectura_basica') {
    test_simulador_lectura_basica();
}
