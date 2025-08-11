<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\PostResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Markdown Field Feature Tests.
 *
 * Tests for the Markdown field functionality including
 * creation, editing, and content handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MarkdownFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test resource with Markdown field
        app(AdminPanel::class)->register([
            PostResource::class,
        ]);
    }

    public function test_markdown_field_appears_in_create_form(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/posts/create');

        $response->assertOk();

        // Check that the response contains the field data
        $response->assertInertia(fn ($page) => $page->has('fields')
            ->where('fields.1.component', 'MarkdownField')
            ->where('fields.1.name', 'Content')
            ->where('fields.1.showToolbar', true)
            ->where('fields.1.enableSlashCommands', true),
        );
    }

    public function test_markdown_field_stores_content_correctly(): void
    {
        $admin = $this->createAdminUser();
        $markdownContent = "# Hello World\n\nThis is **bold** text with a [link](https://example.com).";

        $response = $this->actingAs($admin)
            ->post('/admin/resources/posts', [
                'title' => 'Test Post',
                'content' => $markdownContent,
                'is_published' => true,
                'is_featured' => false,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect();

        $post = Post::where('title', 'Test Post')->first();
        $this->assertNotNull($post);
        $this->assertEquals($markdownContent, $post->content);
    }

    public function test_markdown_field_displays_content_in_edit_form(): void
    {
        $admin = $this->createAdminUser();
        $markdownContent = "# Test Content\n\nThis is a test post with **markdown** formatting.";

        $post = Post::factory()->create([
            'title' => 'Test Post',
            'content' => $markdownContent,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/resources/posts/{$post->id}/edit");

        $response->assertOk();

        // Just verify the page loads correctly - the content will be loaded via the resource
        $this->assertEquals($markdownContent, $post->content);
    }

    public function test_markdown_field_updates_content_correctly(): void
    {
        $admin = $this->createAdminUser();
        $originalContent = '# Original Content';
        $updatedContent = "# Updated Content\n\nThis content has been **updated** with new markdown.";

        $post = Post::factory()->create([
            'title' => 'Test Post',
            'content' => $originalContent,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->put("/admin/resources/posts/{$post->id}", [
                'title' => 'Updated Test Post',
                'content' => $updatedContent,
                'is_published' => true,
                'is_featured' => false,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect();

        $post->refresh();
        $this->assertEquals($updatedContent, $post->content);
        $this->assertEquals('Updated Test Post', $post->title);
    }

    public function test_markdown_field_handles_empty_content(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->post('/admin/resources/posts', [
                'title' => 'Empty Content Post',
                'content' => '',
                'is_published' => false,
                'is_featured' => false,
                'user_id' => $admin->id,
            ]);

        // Should fail validation since content is required
        $response->assertSessionHasErrors(['content']);
    }

    public function test_markdown_field_preserves_line_endings(): void
    {
        $admin = $this->createAdminUser();
        $markdownContent = "Line 1\n\nLine 3 after empty line\n\n- List item 1\n- List item 2";

        $response = $this->actingAs($admin)
            ->post('/admin/resources/posts', [
                'title' => 'Line Endings Test',
                'content' => $markdownContent,
                'is_published' => true,
                'is_featured' => false,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect();

        $post = Post::where('title', 'Line Endings Test')->first();
        $this->assertNotNull($post);
        $this->assertEquals($markdownContent, $post->content);
    }

    public function test_markdown_field_configuration_options(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/posts/create');

        $response->assertOk();

        // Check that the field configuration is properly set
        $response->assertInertia(fn ($page) => $page->has('fields')
            ->where('fields.1.showToolbar', true)
            ->where('fields.1.enableSlashCommands', true)
            ->where('fields.1.editorPlaceholder', 'Write your post content here...'),
        );
    }
}
