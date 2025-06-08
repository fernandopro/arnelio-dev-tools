<?php
/**
 * Tests de integración para Factories y Datos - DevTools
 * 
 * @package TarokinaPro
 * @subpackage DevTools
 * @author Tarokina Team
 * @version 1.0.0
 */

/**
 * Clase de test para factories y generación de datos de WordPress
 * Utiliza el framework oficial de WordPress PHPUnit
 */
class DevToolsFactoriesAndDataTest extends WP_UnitTestCase
{
    /**
     * Test de factory de usuarios
     */
    public function testUserFactory(): void
    {
        // Crear usuario simple
        $user_id = $this->factory->user->create();
        $this->assertIsInt($user_id);
        $this->assertGreaterThan(0, $user_id);

        // Crear usuario con datos específicos
        $admin_id = $this->factory->user->create([
            'user_login' => 'dev_tools_admin',
            'user_email' => 'admin@devtools.test',
            'user_nicename' => 'devtools-admin',
            'display_name' => 'DevTools Administrator',
            'role' => 'administrator'
        ]);

        $admin = get_user_by('id', $admin_id);
        $this->assertEquals('dev_tools_admin', $admin->user_login);
        $this->assertEquals('admin@devtools.test', $admin->user_email);
        $this->assertTrue($admin->has_cap('manage_options'));

        // Crear múltiples usuarios
        $user_ids = $this->factory->user->create_many(3, [
            'role' => 'editor'
        ]);

        $this->assertCount(3, $user_ids);
        foreach ($user_ids as $id) {
            $user = get_user_by('id', $id);
            $this->assertTrue($user->has_cap('edit_posts'));
            $this->assertFalse($user->has_cap('manage_options'));
        }
    }

    /**
     * Test de factory de posts
     */
    public function testPostFactory(): void
    {
        // Crear post simple
        $post_id = $this->factory->post->create();
        $this->assertIsInt($post_id);

        $post = get_post($post_id);
        $this->assertEquals('publish', $post->post_status);
        $this->assertEquals('post', $post->post_type);

        // Crear post con datos específicos
        $custom_post_id = $this->factory->post->create([
            'post_title' => 'DevTools Custom Post',
            'post_content' => 'This is custom content for testing',
            'post_excerpt' => 'Custom excerpt',
            'post_status' => 'draft',
            'post_type' => 'page',
            'menu_order' => 5
        ]);

        $custom_post = get_post($custom_post_id);
        $this->assertEquals('DevTools Custom Post', $custom_post->post_title);
        $this->assertEquals('draft', $custom_post->post_status);
        $this->assertEquals('page', $custom_post->post_type);
        $this->assertEquals(5, $custom_post->menu_order);

        // Crear múltiples posts
        $post_ids = $this->factory->post->create_many(5, [
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);

        $this->assertCount(5, $post_ids);
        
        // Verificar que todos fueron creados
        $published_posts = get_posts([
            'post__in' => $post_ids,
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $this->assertCount(5, $published_posts);
    }

    /**
     * Test de factory de comentarios
     */
    public function testCommentFactory(): void
    {
        // Crear post para los comentarios
        $post_id = $this->factory->post->create([
            'post_title' => 'Post for Comments',
            'comment_status' => 'open'
        ]);

        // Crear comentario simple
        $comment_id = $this->factory->comment->create([
            'comment_post_ID' => $post_id
        ]);

        $this->assertIsInt($comment_id);
        
        $comment = get_comment($comment_id);
        $this->assertEquals($post_id, $comment->comment_post_ID);
        $this->assertEquals('1', $comment->comment_approved);

        // Crear comentario con datos específicos
        $custom_comment_id = $this->factory->comment->create([
            'comment_post_ID' => $post_id,
            'comment_author' => 'DevTools Tester',
            'comment_author_email' => 'tester@devtools.test',
            'comment_content' => 'This is a test comment from DevTools',
            'comment_approved' => '0' // Pendiente de aprobación
        ]);

        $custom_comment = get_comment($custom_comment_id);
        $this->assertEquals('DevTools Tester', $custom_comment->comment_author);
        $this->assertEquals('0', $custom_comment->comment_approved);
        $this->assertStringContainsString('DevTools', $custom_comment->comment_content);

        // Crear comentarios anidados
        $parent_comment_id = $this->factory->comment->create([
            'comment_post_ID' => $post_id,
            'comment_content' => 'Parent comment'
        ]);

        $child_comment_id = $this->factory->comment->create([
            'comment_post_ID' => $post_id,
            'comment_parent' => $parent_comment_id,
            'comment_content' => 'Child comment'
        ]);

        $child_comment = get_comment($child_comment_id);
        $this->assertEquals($parent_comment_id, $child_comment->comment_parent);
    }

    /**
     * Test de factory de categorías
     */
    public function testCategoryFactory(): void
    {
        // Crear categoría simple
        $category_id = $this->factory->category->create();
        $this->assertIsInt($category_id);

        $category = get_category($category_id);
        $this->assertNotEmpty($category->name);

        // Crear categoría con datos específicos
        $custom_cat_id = $this->factory->category->create([
            'name' => 'DevTools Category',
            'slug' => 'dev-tools-cat',
            'description' => 'Category for DevTools testing'
        ]);

        $custom_category = get_category($custom_cat_id);
        $this->assertEquals('DevTools Category', $custom_category->name);
        $this->assertEquals('dev-tools-cat', $custom_category->slug);
        $this->assertStringContainsString('DevTools', $custom_category->description);

        // Crear categorías jerárquicas
        $parent_cat_id = $this->factory->category->create([
            'name' => 'Parent Category'
        ]);

        $child_cat_id = $this->factory->category->create([
            'name' => 'Child Category',
            'parent' => $parent_cat_id
        ]);

        $child_category = get_category($child_cat_id);
        $this->assertEquals($parent_cat_id, $child_category->parent);

        // Verificar jerarquía
        $category_children = get_categories([
            'parent' => $parent_cat_id,
            'hide_empty' => false
        ]);
        
        $this->assertCount(1, $category_children);
        $this->assertEquals('Child Category', $category_children[0]->name);
    }

    /**
     * Test de factory de tags
     */
    public function testTagFactory(): void
    {
        // Crear tag simple
        $tag_id = $this->factory->tag->create();
        $this->assertIsInt($tag_id);

        $tag = get_tag($tag_id);
        $this->assertNotEmpty($tag->name);

        // Crear tag con datos específicos
        $custom_tag_id = $this->factory->tag->create([
            'name' => 'DevTools Tag',
            'slug' => 'dev-tools-tag',
            'description' => 'Tag for DevTools testing'
        ]);

        $custom_tag = get_tag($custom_tag_id);
        $this->assertEquals('DevTools Tag', $custom_tag->name);
        $this->assertEquals('dev-tools-tag', $custom_tag->slug);

        // Crear múltiples tags
        $tag_ids = $this->factory->tag->create_many(3);
        $this->assertCount(3, $tag_ids);

        $tags = get_tags([
            'include' => $tag_ids,
            'hide_empty' => false
        ]);
        
        $this->assertCount(3, $tags);
    }

    /**
     * Test de relaciones entre objetos usando factories
     */
    public function testObjectRelationships(): void
    {
        // Crear usuario
        $author_id = $this->factory->user->create([
            'display_name' => 'DevTools Author'
        ]);

        // Crear categorías y tags
        $category_id = $this->factory->category->create([
            'name' => 'Tech Articles'
        ]);
        
        // Crear tags individuales con nombres específicos
        $tag_ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $tag_ids[] = $this->factory->tag->create([
                'name' => "DevTag{$i}",
                'slug' => "dev-tag-{$i}"
            ]);
        }

        // Crear post relacionado
        $post_id = $this->factory->post->create([
            'post_title' => 'DevTools Integration Article',
            'post_author' => $author_id,
            'post_category' => [$category_id]
        ]);

        // Asignar tags al post usando wp_set_post_terms que es más confiable
        $tag_assignment = wp_set_post_terms($post_id, $tag_ids, 'post_tag');
        $this->assertNotInstanceOf('WP_Error', $tag_assignment, 'La asignación de tags no debe ser un error');

        // Verificar relaciones
        $post = get_post($post_id);
        $this->assertEquals($author_id, $post->post_author);

        // Verificar autor
        $author = get_user_by('id', $author_id);
        $this->assertEquals('DevTools Author', $author->display_name);

        // Verificar categoría
        $post_categories = get_the_category($post_id);
        $this->assertCount(1, $post_categories);
        $this->assertEquals('Tech Articles', $post_categories[0]->name);

        // Verificar tags
        $post_tags = get_the_tags($post_id);
        $this->assertCount(3, $post_tags);

        // Crear comentarios para el post
        $comment_ids = $this->factory->comment->create_many(2, [
            'comment_post_ID' => $post_id,
            'comment_approved' => '1'
        ]);

        $comments = get_comments([
            'post_id' => $post_id,
            'status' => 'approve'
        ]);
        
        $this->assertCount(2, $comments);
    }

    /**
     * Test de factories con custom post types
     */
    public function testCustomPostTypeFactory(): void
    {
        // Registrar custom post type
        register_post_type('dev_tools_item', [
            'public' => true,
            'label' => 'DevTools Items',
            'supports' => ['title', 'editor', 'custom-fields']
        ]);

        // Crear posts del tipo personalizado
        $custom_post_id = $this->factory->post->create([
            'post_type' => 'dev_tools_item',
            'post_title' => 'Custom DevTools Item',
            'post_status' => 'publish'
        ]);

        $custom_post = get_post($custom_post_id);
        $this->assertEquals('dev_tools_item', $custom_post->post_type);
        $this->assertEquals('Custom DevTools Item', $custom_post->post_title);

        // Agregar meta fields al custom post
        add_post_meta($custom_post_id, 'dev_tools_setting', 'enabled');
        add_post_meta($custom_post_id, 'dev_tools_priority', 10);

        $setting = get_post_meta($custom_post_id, 'dev_tools_setting', true);
        $priority = get_post_meta($custom_post_id, 'dev_tools_priority', true);

        $this->assertEquals('enabled', $setting);
        $this->assertEquals(10, $priority);
    }

    /**
     * Test de factories con taxonomías personalizadas
     */
    public function testCustomTaxonomyFactory(): void
    {
        // Registrar taxonomía personalizada
        register_taxonomy('dev_tools_type', 'post', [
            'hierarchical' => true,
            'label' => 'DevTools Types',
            'public' => true
        ]);

        // Crear términos de la taxonomía personalizada usando term factory
        $term_id = $this->factory->term->create([
            'taxonomy' => 'dev_tools_type',
            'name' => 'Testing Type',
            'slug' => 'testing-type'
        ]);

        $this->assertIsInt($term_id);

        $term = get_term($term_id, 'dev_tools_type');
        $this->assertEquals('Testing Type', $term->name);
        $this->assertEquals('dev_tools_type', $term->taxonomy);

        // Crear post y asignar taxonomía personalizada
        $post_id = $this->factory->post->create([
            'post_title' => 'Post with Custom Taxonomy'
        ]);

        wp_set_post_terms($post_id, [$term_id], 'dev_tools_type');

        // Verificar asignación
        $post_terms = get_the_terms($post_id, 'dev_tools_type');
        $this->assertCount(1, $post_terms);
        $this->assertEquals('Testing Type', $post_terms[0]->name);
    }

    /**
     * Test de performance con muchos objetos
     */
    public function testFactoryPerformance(): void
    {
        $start_time = microtime(true);

        // Crear múltiples objetos
        $user_ids = $this->factory->user->create_many(10);
        $post_ids = $this->factory->post->create_many(20);
        $comment_ids = $this->factory->comment->create_many(30, [
            'comment_post_ID' => $post_ids[0] // Todos en el primer post
        ]);
        $category_ids = $this->factory->category->create_many(5);

        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;

        // Verificar que se crearon todos los objetos
        $this->assertCount(10, $user_ids);
        $this->assertCount(20, $post_ids);
        $this->assertCount(30, $comment_ids);
        $this->assertCount(5, $category_ids);

        // Performance check (debería ser relativamente rápido)
        $this->assertLessThan(5.0, $execution_time, 'La creación de objetos debería ser rápida');

        // Verificar algunos objetos al azar
        $random_user = get_user_by('id', $user_ids[array_rand($user_ids)]);
        $random_post = get_post($post_ids[array_rand($post_ids)]);
        $random_comment = get_comment($comment_ids[array_rand($comment_ids)]);

        $this->assertInstanceOf('WP_User', $random_user);
        $this->assertInstanceOf('WP_Post', $random_post);
        $this->assertInstanceOf('WP_Comment', $random_comment);
    }
}
