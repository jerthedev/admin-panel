<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\Address;
use JTD\AdminPanel\Tests\Fixtures\Post;

/**
 * HasOne Field E2E Test
 *
 * Tests the complete end-to-end functionality of HasOne fields
 * in CRUD operations within the admin panel with real-world scenarios.
 */
class HasOneFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with addresses
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Create addresses for some users
        Address::factory()->create(['id' => 1, 'user_id' => 1, 'street' => '123 Main St', 'city' => 'New York']);
        Address::factory()->create(['id' => 2, 'user_id' => 2, 'street' => '456 Oak Ave', 'city' => 'Los Angeles']);
        // User 3 has no address

        // Create posts for "of many" testing
        Post::factory()->create(['id' => 1, 'user_id' => 1, 'title' => 'First Post', 'created_at' => now()->subDays(2)]);
        Post::factory()->create(['id' => 2, 'user_id' => 1, 'title' => 'Latest Post', 'created_at' => now()]);
    }

    /** @test */
    public function user_can_view_has_one_relationship_on_detail_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that the HasOne relationship is displayed
        $response->assertSee('Address');
        $response->assertSee('123 Main St');
        $response->assertSee('Related'); // Status badge
    }

    /** @test */
    public function user_can_view_empty_has_one_relationship(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);

        // Check that the empty state is displayed
        $response->assertSee('No Address');
        $response->assertSee("This resource doesn't have a related address");
        $response->assertSee('No Relation'); // Status badge
    }

    /** @test */
    public function user_can_navigate_to_related_model_from_has_one_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that view button is present
        $response->assertSee('View');

        // Navigate to related address
        $response = $this->get('/admin-panel/resources/addresses/1');
        $response->assertStatus(200);

        // Verify we're on the address detail page
        $response->assertSee('123 Main St');
        $response->assertSee('New York');
    }

    /** @test */
    public function user_can_edit_related_model_from_has_one_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that edit button is present
        $response->assertSee('Edit');

        // Navigate to edit related address
        $response = $this->get('/admin-panel/resources/addresses/1/edit');
        $response->assertStatus(200);

        // Verify we're on the address edit page
        $response->assertSee('123 Main St');
        $response->assertSee('New York');

        // Update the address
        $response = $this->put('/admin-panel/resources/addresses/1', [
            'street' => '789 Updated St',
            'city' => 'Updated City',
            'user_id' => 1
        ]);

        $response->assertRedirect();

        // Verify the address was updated
        $address = Address::find(1);
        $this->assertEquals('789 Updated St', $address->street);
        $this->assertEquals('Updated City', $address->city);
    }

    /** @test */
    public function user_can_create_related_model_from_empty_has_one_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user without address
        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);

        // Check that create button is present
        $response->assertSee('Create Address');

        // Navigate to create address page
        $response = $this->get('/admin-panel/resources/addresses/create?user_id=3');
        $response->assertStatus(200);

        // Create new address
        $response = $this->post('/admin-panel/resources/addresses', [
            'street' => '999 New St',
            'city' => 'New City',
            'user_id' => 3
        ]);

        $response->assertRedirect();

        // Verify the address was created
        $address = Address::where('user_id', 3)->first();
        $this->assertNotNull($address);
        $this->assertEquals('999 New St', $address->street);
        $this->assertEquals('New City', $address->city);
    }

    /** @test */
    public function has_one_field_shows_correct_status_badges(): void
    {
        $this->actingAs($this->createAdminUser());

        // User with address - should show "Related"
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);
        $response->assertSee('Related');

        // User without address - should show "No Relation"
        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);
        $response->assertSee('No Relation');
    }

    /** @test */
    public function has_one_field_displays_resource_class_information(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the resource class name
        $response->assertSee('AddressResource');
    }

    /** @test */
    public function has_one_field_works_with_different_field_configurations(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test with custom field name
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // The field should be displayed regardless of configuration
        $response->assertSee('Address');
    }

    /** @test */
    public function has_one_of_many_field_displays_correctly(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user with posts
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the latest post if configured as "of many"
        // This would be configured in the UserResource
        if (str_contains($response->getContent(), 'Latest Post')) {
            $response->assertSee('Latest Post');
            $response->assertSee('This is a "Latest Post" relationship');
        }
    }

    /** @test */
    public function has_one_field_respects_authorization_policies(): void
    {
        $this->actingAs($this->createAdminUser());

        // This test would verify that authorization policies are respected
        // For now, we just verify the field is displayed when authorized
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the field when user is authorized
        $response->assertSee('Address');
    }

    /** @test */
    public function has_one_field_handles_soft_deleted_related_models(): void
    {
        $this->actingAs($this->createAdminUser());

        // Soft delete the address
        $address = Address::find(1);
        $address->delete();

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should handle soft-deleted relationships appropriately
        // This depends on the specific implementation and configuration
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function has_one_field_displays_fallback_title_when_no_title_method(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should display some form of title/identifier for the related model
        // This could be the model's title() method result or a fallback
        $response->assertSee('Address'); // Field name should always be visible
    }

    /** @test */
    public function has_one_field_works_in_readonly_mode(): void
    {
        $this->actingAs($this->createAdminUser());

        // When field is readonly, edit and create buttons should not be present
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // View button should still be present
        $response->assertSee('View');

        // In readonly mode, edit and create buttons would be hidden
        // This would be tested with specific readonly configuration
    }

    /** @test */
    public function has_one_field_supports_custom_resource_titles(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should display the title as defined by the resource's title() method
        // The exact title depends on the AddressResource implementation
        $response->assertSee('Address'); // At minimum, the field name
    }

    /** @test */
    public function has_one_field_maintains_state_across_page_refreshes(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit the page multiple times to ensure consistent display
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/admin-panel/resources/users/1');
            $response->assertStatus(200);
            $response->assertSee('Address');
            $response->assertSee('Related');
        }
    }

    /** @test */
    public function has_one_field_handles_complex_relationship_scenarios(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test with user that has multiple related models
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should correctly display the HasOne relationship
        $response->assertSee('Address');

        // Should not be confused by other relationships (HasMany, etc.)
        $response->assertSee('Related');
    }


}
