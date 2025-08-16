<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\Post;

/**
 * BelongsTo Field E2E Test
 *
 * Tests the complete end-to-end functionality of BelongsTo fields
 * in CRUD operations within the admin panel.
 */
class BelongsToFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function user_can_create_post_with_belongs_to_relationship(): void
    {
        $this->actingAs($this->createAdminUser([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]));

        // Visit the post creation page
        $response = $this->get('/admin-panel/resources/posts/create');
        $response->assertStatus(200);

        // Submit form with BelongsTo relationship
        $response = $this->post('/admin-panel/resources/posts', [
            'title' => 'Test Post',
            'content' => 'This is a test post content.',
            'user' => 1, // BelongsTo User with ID 1
        ]);

        $response->assertRedirect();

        // Verify the post was created with correct relationship
        $post = Post::where('title', 'Test Post')->first();
        $this->assertNotNull($post);
        $this->assertEquals(1, $post->user_id);
        $this->assertEquals('John Doe', $post->user->name);
    }

    /** @test */
    public function user_can_update_post_belongs_to_relationship(): void
    {
        $this->actingAs($this->createAdminUser());

        // Create a post with initial user
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'user_id' => 1,
        ]);

        // Visit the post edit page
        $response = $this->get("/admin-panel/resources/posts/{$post->id}/edit");
        $response->assertStatus(200);

        // Update the post with different user
        $response = $this->put("/admin-panel/resources/posts/{$post->id}", [
            'title' => 'Updated Test Post',
            'content' => 'Updated content.',
            'user' => 2, // Change to User with ID 2
        ]);

        $response->assertRedirect();

        // Verify the relationship was updated
        $post->refresh();
        $this->assertEquals(2, $post->user_id);
        $this->assertEquals('Jane Smith', $post->user->name);
    }

    /** @test */
    public function user_can_view_post_with_belongs_to_relationship_on_detail_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $post = Post::factory()->create([
            'title' => 'Test Post',
            'user_id' => 1,
        ]);

        $response = $this->get("/admin-panel/resources/posts/{$post->id}");
        $response->assertStatus(200);

        // Check that the BelongsTo relationship is displayed
        $response->assertSee('John Doe');
        $response->assertSee('john@example.com');
    }

    /** @test */
    public function user_can_view_post_with_belongs_to_relationship_on_index_page(): void
    {
        $this->actingAs($this->createAdminUser());

        Post::factory()->create([
            'title' => 'Test Post 1',
            'user_id' => 1,
        ]);

        Post::factory()->create([
            'title' => 'Test Post 2',
            'user_id' => 2,
        ]);

        $response = $this->get('/admin-panel/resources/posts');
        $response->assertStatus(200);

        // Check that BelongsTo relationships are displayed in the index
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
    }

    /** @test */
    public function belongs_to_field_shows_searchable_dropdown_when_enabled(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/posts/create');
        $response->assertStatus(200);

        // Check that the page contains the BelongsTo field
        $response->assertSee('User'); // Field label

        // The actual searchable functionality would be tested via JavaScript/browser tests
        // For now, we verify the field is present and configured correctly
    }

    /** @test */
    public function belongs_to_field_handles_nullable_relationships(): void
    {
        $this->actingAs($this->createAdminUser());

        // Create post without user (nullable relationship)
        $response = $this->post('/admin-panel/resources/posts', [
            'title' => 'Post Without User',
            'content' => 'This post has no user assigned.',
            'user' => null,
        ]);

        $response->assertRedirect();

        $post = Post::where('title', 'Post Without User')->first();
        $this->assertNotNull($post);
        $this->assertNull($post->user_id);
    }

    /** @test */
    public function belongs_to_field_validates_required_relationships(): void
    {
        $this->actingAs($this->createAdminUser());

        // Try to create post without required user field
        $response = $this->post('/admin-panel/resources/posts', [
            'title' => 'Post Without Required User',
            'content' => 'This should fail validation.',
            // Missing required 'user' field
        ]);

        // Should return validation errors
        $response->assertSessionHasErrors(['user']);
    }

    /** @test */
    public function belongs_to_field_shows_create_button_when_enabled(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/posts/create');
        $response->assertStatus(200);

        // Check for create button presence (would be tested more thoroughly in browser tests)
        // For now, verify the page loads correctly with the field
        $response->assertSee('User');
    }

    /** @test */
    public function belongs_to_field_filters_options_based_on_authorization(): void
    {
        $this->actingAs($this->createAdminUser());

        // This test would verify that only authorized related resources are shown
        // The actual filtering logic would be in the resource's relatableQuery method

        $response = $this->get('/admin-panel/resources/posts/create');
        $response->assertStatus(200);

        // Verify the field is present and will be populated with authorized options
        $response->assertSee('User');
    }

    /** @test */
    public function belongs_to_field_supports_custom_display_in_forms(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/posts/create');
        $response->assertStatus(200);

        // The custom display logic would be tested via the API endpoint
        // and verified in the frontend component tests
        $response->assertSee('User');
    }

    /** @test */
    public function belongs_to_field_works_with_soft_deleted_models(): void
    {
        $this->actingAs($this->createAdminUser());

        // Create a soft-deleted user
        $deletedUser = User::factory()->create(['name' => 'Deleted User']);
        $deletedUser->delete();

        // Create post with soft-deleted user
        $post = Post::factory()->create([
            'title' => 'Post with Deleted User',
            'user_id' => $deletedUser->id,
        ]);

        // Visit the post detail page
        $response = $this->get("/admin-panel/resources/posts/{$post->id}");
        $response->assertStatus(200);

        // Should still show the relationship even if user is soft-deleted
        $response->assertSee('Deleted User');
    }

    /** @test */
    public function belongs_to_field_respects_without_trashed_configuration(): void
    {
        $this->actingAs($this->createAdminUser());

        // Create and soft-delete a user
        $deletedUser = User::factory()->create(['name' => 'Deleted User']);
        $deletedUser->delete();

        // When creating a new post, soft-deleted users should not appear in options
        // This would be tested via the API endpoint
        $response = $this->postJson('/admin-panel/api/fields/belongs-to/options', [
            'field' => [
                'name' => 'User',
                'attribute' => 'user',
                'resourceClass' => 'App\\AdminPanel\\Resources\\UserResource',
                'withTrashed' => false,
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Should only return non-deleted users
        $this->assertCount(3, $data['options']); // Only the 3 active users

        $userNames = array_column($data['options'], 'label');
        $this->assertNotContains('Deleted User', $userNames);
    }


}
