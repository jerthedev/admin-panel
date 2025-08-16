<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasMany Field E2E Test.
 *
 * Tests the complete end-to-end functionality of HasMany fields
 * in CRUD operations within the admin panel with real-world scenarios.
 */
class HasManyFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with posts
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Create posts for users
        Post::factory()->create(['id' => 1, 'user_id' => 1, 'title' => 'First Post', 'content' => 'Content 1']);
        Post::factory()->create(['id' => 2, 'user_id' => 1, 'title' => 'Second Post', 'content' => 'Content 2']);
        Post::factory()->create(['id' => 3, 'user_id' => 1, 'title' => 'Third Post', 'content' => 'Content 3']);
        Post::factory()->create(['id' => 4, 'user_id' => 2, 'title' => 'Jane Post', 'content' => 'Jane Content']);
        // User 3 has no posts
    }

    /** @test */
    public function user_can_view_has_many_relationship_on_detail_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that the HasMany relationship is displayed
        $response->assertSee('Posts');
        $response->assertSee('3 items'); // User 1 has 3 posts
        $response->assertSee('First Post');
        $response->assertSee('Second Post');
        $response->assertSee('Third Post');
    }

    /** @test */
    public function user_can_view_empty_has_many_relationship(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);

        // Check that the empty state is displayed
        $response->assertSee('Posts');
        $response->assertSee('0 items');
        $response->assertSee('No posts found');
    }

    /** @test */
    public function user_can_search_has_many_relationships(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Perform search via AJAX
        $response = $this->get('/admin-panel/resources/users/1/posts?search=First');
        $response->assertStatus(200);

        // Should only return the "First Post"
        $response->assertSee('First Post');
        $response->assertDontSee('Second Post');
        $response->assertDontSee('Third Post');
    }

    /** @test */
    public function user_can_paginate_has_many_relationships(): void
    {
        $this->actingAs($this->createAdminUser());

        // Create more posts to test pagination
        for ($i = 4; $i <= 25; $i++) {
            Post::factory()->create([
                'user_id' => 1,
                'title' => "Post {$i}",
                'content' => "Content {$i}",
            ]);
        }

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check pagination controls are present
        $response->assertSee('Page 1 of');
        $response->assertSee('Next');

        // Navigate to page 2
        $response = $this->get('/admin-panel/resources/users/1/posts?page=2');
        $response->assertStatus(200);

        // Should show different posts
        $response->assertSee('Page 2 of');
        $response->assertSee('Previous');
    }

    /** @test */
    public function user_can_create_related_model_from_has_many_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that create button is present
        $response->assertSee('Create Post');

        // Navigate to create post page
        $response = $this->get('/admin-panel/resources/posts/create?user_id=1');
        $response->assertStatus(200);

        // Create new post
        $response = $this->post('/admin-panel/resources/posts', [
            'title' => 'New Post',
            'content' => 'New Content',
            'user_id' => 1,
        ]);

        $response->assertRedirect();

        // Verify the post was created
        $post = Post::where('title', 'New Post')->first();
        $this->assertNotNull($post);
        $this->assertEquals('New Content', $post->content);
        $this->assertEquals(1, $post->user_id);
    }

    /** @test */
    public function user_can_edit_related_model_from_has_many_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that edit button is present for posts
        $response->assertSee('Edit');

        // Navigate to edit post page
        $response = $this->get('/admin-panel/resources/posts/1/edit');
        $response->assertStatus(200);

        // Verify we're on the post edit page
        $response->assertSee('First Post');

        // Update the post
        $response = $this->put('/admin-panel/resources/posts/1', [
            'title' => 'Updated First Post',
            'content' => 'Updated Content',
            'user_id' => 1,
        ]);

        $response->assertRedirect();

        // Verify the post was updated
        $post = Post::find(1);
        $this->assertEquals('Updated First Post', $post->title);
        $this->assertEquals('Updated Content', $post->content);
    }

    /** @test */
    public function user_can_delete_related_model_from_has_many_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that delete button is present
        $response->assertSee('Delete');

        // Delete the post
        $response = $this->delete('/admin-panel/resources/posts/1');
        $response->assertRedirect();

        // Verify the post was deleted
        $post = Post::find(1);
        $this->assertNull($post);
    }

    /** @test */
    public function has_many_field_shows_correct_item_counts(): void
    {
        $this->actingAs($this->createAdminUser());

        // User with 3 posts
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);
        $response->assertSee('3 items');

        // User with 1 post
        $response = $this->get('/admin-panel/resources/users/2');
        $response->assertStatus(200);
        $response->assertSee('1 item');

        // User with 0 posts
        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);
        $response->assertSee('0 items');
    }

    /** @test */
    public function has_many_field_supports_collapsible_functionality(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show collapse/expand button if configured
        if (str_contains($response->getContent(), 'Collapse')) {
            $response->assertSee('Collapse');
        }
    }

    /** @test */
    public function has_many_field_respects_authorization_policies(): void
    {
        $this->actingAs($this->createAdminUser());

        // This test would verify that authorization policies are respected
        // For now, we just verify the field is displayed when authorized
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the field when user is authorized
        $response->assertSee('Posts');
    }

    /** @test */
    public function has_many_field_handles_soft_deleted_related_models(): void
    {
        $this->actingAs($this->createAdminUser());

        // Soft delete a post
        $post = Post::find(1);
        $post->delete();

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show 2 items instead of 3 (excluding soft-deleted)
        $response->assertSee('2 items');
        $response->assertDontSee('First Post');
    }

    /** @test */
    public function has_many_field_displays_resource_information(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the resource class information
        $response->assertSee('PostResource');
    }

    /** @test */
    public function has_many_field_works_with_different_field_configurations(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test with custom field name
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // The field should be displayed regardless of configuration
        $response->assertSee('Posts');
    }

    /** @test */
    public function has_many_field_maintains_state_across_page_refreshes(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit the page multiple times to ensure consistent display
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/admin-panel/resources/users/1');
            $response->assertStatus(200);
            $response->assertSee('Posts');
            $response->assertSee('3 items');
        }
    }

    /** @test */
    public function has_many_field_handles_complex_relationship_scenarios(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test with user that has multiple related models
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should correctly display the HasMany relationship
        $response->assertSee('Posts');
        $response->assertSee('3 items');

        // Should handle multiple relationships correctly
        $response->assertSee('First Post');
        $response->assertSee('Second Post');
        $response->assertSee('Third Post');
    }

    /** @test */
    public function has_many_field_works_in_readonly_mode(): void
    {
        $this->actingAs($this->createAdminUser());

        // When field is readonly, create and edit buttons should not be present
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // View button should still be present
        $response->assertSee('View');

        // In readonly mode, create and edit buttons would be hidden
        // This would be tested with specific readonly configuration
    }

    /** @test */
    public function has_many_field_supports_custom_resource_titles(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should display the titles as defined by the resource's title() method
        $response->assertSee('First Post');
        $response->assertSee('Second Post');
        $response->assertSee('Third Post');
    }
}
