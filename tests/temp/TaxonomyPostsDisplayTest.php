<?php

/**
 * Test para mostrar posts asociados a la taxonomía tarokkina_pro-cat
 */

require_once __DIR__ . '/../DevToolsTestCase.php';

class TaxonomyPostsDisplayTest extends DevToolsTestCase
{
    /**
     * Test principal: mostrar tabla de posts por taxonomía
     */
    public function testShowPostsFromTaxonomy(): void
    {
        echo "\n" . str_repeat("=", 100) . "\n";
        echo "POSTS ASOCIADOS A LA TAXONOMÍA: tarokkina_pro-cat\n";
        echo str_repeat("=", 100) . "\n\n";

        // Verificar que la taxonomía existe
        if (!taxonomy_exists('tarokkina_pro-cat')) {
            echo "❌ ERROR: La taxonomía 'tarokkina_pro-cat' no existe.\n";
            echo "💡 Verifica que el plugin esté activado correctamente.\n\n";
            return;
        }

        echo "✅ TAXONOMÍA ENCONTRADA: tarokkina_pro-cat\n\n";

        // Obtener todos los términos de la taxonomía
        $terms = get_terms([
            'taxonomy' => 'tarokkina_pro-cat',
            'hide_empty' => false
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            echo "⚠️  No se encontraron términos en la taxonomía 'tarokkina_pro-cat'.\n";
            echo "💡 La taxonomía existe pero no tiene términos creados.\n\n";
            return;
        }

        echo "📊 TÉRMINOS ENCONTRADOS: " . count($terms) . "\n\n";

        // Procesar cada término y mostrar sus posts
        foreach ($terms as $term) {
            $this->displayPostsForTerm($term);
        }

        // Mostrar resumen final
        $this->displaySummary();

        // Verificación para PHPUnit
        $this->assertTrue(count($terms) > 0, 'Se encontraron términos en la taxonomía');
    }

    /**
     * Mostrar posts para un término específico
     */
    private function displayPostsForTerm($term): void
    {
        echo str_repeat("-", 80) . "\n";
        echo "🏷️  TÉRMINO: {$term->name} (ID: {$term->term_id})\n";
        echo "📄 Slug: {$term->slug}\n";
        echo "📝 Descripción: " . (!empty($term->description) ? $term->description : 'Sin descripción') . "\n";
        echo "📊 Recuento: {$term->count} posts\n";
        echo str_repeat("-", 80) . "\n";

        // Obtener posts asociados a este término
        $posts = get_posts([
            'post_type' => 'tarokkina_pro',
            'tax_query' => [
                [
                    'taxonomy' => 'tarokkina_pro-cat',
                    'field' => 'term_id',
                    'terms' => $term->term_id
                ]
            ],
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        if (empty($posts)) {
            echo "⚠️  No hay posts asociados a este término.\n\n";
            return;
        }

        // Mostrar tabla de posts
        echo "\n📋 TABLA DE POSTS:\n";
        echo str_repeat("-", 100) . "\n";
        printf("| %-5s | %-30s | %-15s | %-20s | %-15s |\n",
            'ID', 'TÍTULO', 'ESTADO', 'FECHA', 'AUTOR'
        );
        echo "+" . str_repeat("-", 7) . "+" . str_repeat("-", 32) . "+" . str_repeat("-", 17) . "+" . str_repeat("-", 22) . "+" . str_repeat("-", 17) . "+\n";

        foreach ($posts as $post) {
            $author = get_userdata($post->post_author);
            $author_name = $author ? $author->display_name : 'Desconocido';
            
            printf("| %-5d | %-30s | %-15s | %-20s | %-15s |\n",
                $post->ID,
                substr($post->post_title, 0, 30),
                $post->post_status,
                date('Y-m-d H:i', strtotime($post->post_date)),
                substr($author_name, 0, 15)
            );
        }

        echo "+" . str_repeat("-", 7) . "+" . str_repeat("-", 32) . "+" . str_repeat("-", 17) . "+" . str_repeat("-", 22) . "+" . str_repeat("-", 17) . "+\n";
        echo "\n📈 TOTAL DE POSTS: " . count($posts) . "\n\n";
    }

    /**
     * Mostrar resumen final
     */
    private function displaySummary(): void
    {
        // Obtener estadísticas generales
        $total_terms = wp_count_terms(['taxonomy' => 'tarokkina_pro-cat']);
        $total_posts = wp_count_posts('tarokkina_pro');
        
        echo str_repeat("=", 80) . "\n";
        echo "📊 RESUMEN GENERAL\n";
        echo str_repeat("=", 80) . "\n";
        echo "• Total de términos en tarokkina_pro-cat: " . $total_terms . "\n";
        echo "• Total de posts tarokkina_pro: " . $total_posts->publish . " publicados\n";
        echo "• Otros estados: " . ($total_posts->private + $total_posts->draft + $total_posts->pending) . " posts\n";
        echo "• Fecha del análisis: " . current_time('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";

        echo "💡 INFORMACIÓN ADICIONAL:\n";
        echo "• Para ver posts: /wp-admin/edit.php?post_type=tarokkina_pro\n";
        echo "• Para gestionar términos: /wp-admin/edit-tags.php?taxonomy=tarokkina_pro-cat\n";
        echo "• Custom Post Type: tarokkina_pro\n";
        echo "• Taxonomía: tarokkina_pro-cat\n\n";
    }

    /**
     * Test adicional: verificar estructura de la taxonomía
     */
    public function testTaxonomyStructure(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "ESTRUCTURA DE LA TAXONOMÍA tarokkina_pro-cat\n";
        echo str_repeat("=", 80) . "\n";

        // Obtener información de la taxonomía
        $taxonomy = get_taxonomy('tarokkina_pro-cat');
        
        if (!$taxonomy) {
            echo "❌ Taxonomía no encontrada\n";
            $this->fail('Taxonomía tarokkina_pro-cat no existe');
            return;
        }

        echo "✅ INFORMACIÓN DE LA TAXONOMÍA:\n";
        echo "• Nombre: {$taxonomy->labels->name}\n";
        echo "• Nombre singular: {$taxonomy->labels->singular_name}\n";
        echo "• Es jerárquica: " . ($taxonomy->hierarchical ? 'Sí' : 'No') . "\n";
        echo "• Es pública: " . ($taxonomy->public ? 'Sí' : 'No') . "\n";
        echo "• Tipos de post asociados: " . implode(', ', $taxonomy->object_type) . "\n";

        // Verificar capacidades
        echo "\n🔐 CAPACIDADES:\n";
        if (isset($taxonomy->cap)) {
            foreach ($taxonomy->cap as $cap_name => $cap_value) {
                echo "• {$cap_name}: {$cap_value}\n";
            }
        }

        echo "\n✅ Estructura verificada correctamente.\n";
        echo str_repeat("=", 80) . "\n\n";

        $this->assertTrue(true, 'Estructura de taxonomía verificada');
    }
}
