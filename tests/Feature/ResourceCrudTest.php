<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\UserResource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Resource CRUD Feature Tests
 *
 * Tests for admin panel resource CRUD operations including
 * index, create, store, show, edit, update, and destroy.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test resource
        app(AdminPanel::class)->register([
            UserResource::class,
        ]);
    }

    public function test_resource_index_displays_resources(): void
    {
        $admin = $this->createAdminUser();
        $users = User::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/users');

        $response->assertOk();
    }

    public function test_resource_index_search_functionality(): void
    {
        $admin = $this->createAdminUser();
        $john = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $jane = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->actingAs($admin)
            ->get('/admin/resources/users?search=John');

        $response->assertOk();
    }

    public function test_resource_index_sorting(): void
    {
        $admin = $this->createAdminUser();
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $response = $this->actingAs($admin)
            ->get('/admin/resources/users?sort_field=name&sort_direction=asc');

        $response->assertOk();
    }

    public function test_resource_create_page_displays(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/users/create');

        $response->assertOk();
    }

    public function test_resource_can_be_created(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->post('/admin/resources/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'is_admin' => false,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    public function test_resource_creation_validation(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->post('/admin/resources/users', [
                'name' => '',
                'email' => 'invalid-email',
                'password' => '123', // Too short
            ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_resource_show_page_displays(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get("/admin/resources/users/{$user->id}");

        $response->assertOk();
    }

    public function test_resource_edit_page_displays(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get("/admin/resources/users/{$user->id}/edit");

        $response->assertOk();
    }

    public function test_resource_can_be_updated(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin)
            ->put("/admin/resources/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'is_admin' => true,
                'is_active' => false,
            ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertTrue($user->is_admin);
        $this->assertFalse($user->is_active);
    }

    public function test_resource_update_validation(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)
            ->put("/admin/resources/users/{$user->id}", [
                'name' => '',
                'email' => 'existing@example.com', // Duplicate email
            ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_resource_can_be_deleted(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->delete("/admin/resources/users/{$user->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_nonexistent_resource_returns_404(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/nonexistent');

        $response->assertNotFound();
    }

    public function test_nonexistent_resource_record_returns_404(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/users/99999');

        $response->assertNotFound();
    }

    public function test_non_admin_user_resource_access_respects_config(): void
    {
        $user = $this->createUser(['is_admin' => false]);

        $response = $this->actingAs($user)
            ->get('/admin/resources/users');

        // Assert based on configuration
        $this->assertNonAdminResponse($response);
    }

    public function test_guest_user_redirected_to_login(): void
    {
        $response = $this->get('/admin/resources/users');

        $response->assertRedirect();
    }
}
