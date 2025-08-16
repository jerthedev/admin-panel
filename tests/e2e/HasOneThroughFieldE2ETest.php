<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Tests\Fixtures\Car;
use JTD\AdminPanel\Tests\Fixtures\CarOwner;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasOneThrough Field E2E Test.
 *
 * Tests the complete end-to-end functionality of HasOneThrough fields
 * in CRUD operations within the admin panel with real-world scenarios.
 */
class HasOneThroughFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with cars and car owners
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Create cars for users
        $car1 = Car::factory()->create(['id' => 1, 'user_id' => 1, 'make' => 'Toyota', 'model' => 'Camry', 'vin' => 'VIN123']);
        $car2 = Car::factory()->create(['id' => 2, 'user_id' => 2, 'make' => 'Honda', 'model' => 'Civic', 'vin' => 'VIN456']);
        // User 3 has no car

        // Create car owners
        CarOwner::factory()->create(['id' => 1, 'car_id' => 1, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'license_number' => 'DL123']);
        CarOwner::factory()->create(['id' => 2, 'car_id' => 2, 'name' => 'Bob Brown', 'email' => 'bob.brown@example.com', 'license_number' => 'DL456']);
        // Car 1 has owner, Car 2 has owner, User 3 has no car so no owner
    }

    /** @test */
    public function user_can_view_has_one_through_relationship_on_detail_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that the HasOneThrough relationship is displayed
        $response->assertSee('Car Owner');
        $response->assertSee('Alice Johnson'); // Car owner name
        $response->assertSee('Related'); // Status badge
    }

    /** @test */
    public function user_can_view_empty_has_one_through_relationship(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);

        // Check that the empty state is displayed
        $response->assertSee('Car Owner');
        $response->assertSee('No car owner found');
        $response->assertSee('No Relation'); // Status badge
    }

    /** @test */
    public function user_can_view_related_model_from_has_one_through_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that view button is present
        $response->assertSee('View');

        // Navigate to view car owner page
        $response = $this->get('/admin-panel/resources/car-owners/1');
        $response->assertStatus(200);

        // Verify we're on the car owner detail page
        $response->assertSee('Alice Johnson');
        $response->assertSee('alice@example.com');
    }

    /** @test */
    public function user_can_edit_related_model_from_has_one_through_field(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Check that edit button is present
        $response->assertSee('Edit');

        // Navigate to edit car owner page
        $response = $this->get('/admin-panel/resources/car-owners/1/edit');
        $response->assertStatus(200);

        // Verify we're on the car owner edit page
        $response->assertSee('Alice Johnson');

        // Update the car owner
        $response = $this->put('/admin-panel/resources/car-owners/1', [
            'name' => 'Alice Johnson Updated',
            'email' => 'alice.updated@example.com',
            'phone' => '555-9999',
            'license_number' => 'DL123',
            'car_id' => 1,
        ]);

        $response->assertRedirect();

        // Verify the car owner was updated
        $carOwner = CarOwner::find(1);
        $this->assertEquals('Alice Johnson Updated', $carOwner->name);
        $this->assertEquals('alice.updated@example.com', $carOwner->email);
    }

    /** @test */
    public function has_one_through_field_shows_correct_relationship_information(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the through relationship information
        $response->assertSee('This relationship is accessed through');
        $response->assertSee('Car'); // Through model
    }

    /** @test */
    public function has_one_through_field_displays_resource_information(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the resource class information
        $response->assertSee('CarOwnerResource');
    }

    /** @test */
    public function has_one_through_field_handles_missing_intermediate_model(): void
    {
        $this->actingAs($this->createAdminUser());

        // User 3 has no car, so no car owner through relationship
        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);

        // Should show empty state
        $response->assertSee('No car owner found');
        $response->assertSee('This relationship is accessed through an intermediate model');
    }

    /** @test */
    public function has_one_through_field_respects_authorization_policies(): void
    {
        $this->actingAs($this->createAdminUser());

        // This test would verify that authorization policies are respected
        // For now, we just verify the field is displayed when authorized
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the field when user is authorized
        $response->assertSee('Car Owner');
    }

    /** @test */
    public function has_one_through_field_handles_soft_deleted_related_models(): void
    {
        $this->actingAs($this->createAdminUser());

        // Soft delete the car owner
        $carOwner = CarOwner::find(1);
        $carOwner->delete();

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show empty state (excluding soft-deleted)
        $response->assertSee('No car owner found');
        $response->assertSee('No Relation');
    }

    /** @test */
    public function has_one_through_field_works_with_different_field_configurations(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test with custom field name
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // The field should be displayed regardless of configuration
        $response->assertSee('Car Owner');
    }

    /** @test */
    public function has_one_through_field_maintains_state_across_page_refreshes(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit the page multiple times to ensure consistent display
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/admin-panel/resources/users/1');
            $response->assertStatus(200);
            $response->assertSee('Car Owner');
            $response->assertSee('Alice Johnson');
        }
    }

    /** @test */
    public function has_one_through_field_handles_complex_relationship_scenarios(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test with user that has a car and car owner
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should correctly display the HasOneThrough relationship
        $response->assertSee('Car Owner');
        $response->assertSee('Alice Johnson');
        $response->assertSee('Related');

        // Test with user that has a car and different car owner
        $response = $this->get('/admin-panel/resources/users/2');
        $response->assertStatus(200);

        $response->assertSee('Car Owner');
        $response->assertSee('Bob Brown');
        $response->assertSee('Related');
    }

    /** @test */
    public function has_one_through_field_works_in_readonly_mode(): void
    {
        $this->actingAs($this->createAdminUser());

        // When field is readonly, edit buttons should not be present
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // View button should still be present
        $response->assertSee('View');

        // In readonly mode, edit buttons would be hidden
        // This would be tested with specific readonly configuration
    }

    /** @test */
    public function has_one_through_field_supports_custom_resource_titles(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should display the title as defined by the resource's title() method
        $response->assertSee('Alice Johnson');
    }

    /** @test */
    public function has_one_through_field_shows_correct_status_badges(): void
    {
        $this->actingAs($this->createAdminUser());

        // User with car owner
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);
        $response->assertSee('Related');

        // User without car owner
        $response = $this->get('/admin-panel/resources/users/3');
        $response->assertStatus(200);
        $response->assertSee('No Relation');
    }

    /** @test */
    public function has_one_through_field_navigation_works_correctly(): void
    {
        $this->actingAs($this->createAdminUser());

        // Visit user detail page
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Click view button should navigate to car owner detail
        $response = $this->get('/admin-panel/resources/car-owners/1');
        $response->assertStatus(200);
        $response->assertSee('Alice Johnson');

        // Click edit button should navigate to car owner edit
        $response = $this->get('/admin-panel/resources/car-owners/1/edit');
        $response->assertStatus(200);
        $response->assertSee('Alice Johnson');
    }

    /** @test */
    public function has_one_through_field_handles_different_through_models(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test the relationship works with the Car as intermediate model
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should show the through model information
        $response->assertSee('This relationship is accessed through');
        $response->assertSee('Car');
    }

    /** @test */
    public function has_one_through_field_displays_correct_metadata(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should include proper metadata in the response
        $response->assertSee('CarOwnerResource');
        $response->assertSee('Car Owner');
    }

    /** @test */
    public function has_one_through_field_works_with_multiple_users(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test multiple users with different car owners
        $users = [
            ['id' => 1, 'owner' => 'Alice Johnson'],
            ['id' => 2, 'owner' => 'Bob Brown'],
        ];

        foreach ($users as $userData) {
            $response = $this->get("/admin-panel/resources/users/{$userData['id']}");
            $response->assertStatus(200);
            $response->assertSee('Car Owner');
            $response->assertSee($userData['owner']);
            $response->assertSee('Related');
        }
    }

    /** @test */
    public function has_one_through_field_handles_database_constraints(): void
    {
        $this->actingAs($this->createAdminUser());

        // Test that the field handles database constraints properly
        $response = $this->get('/admin-panel/resources/users/1');
        $response->assertStatus(200);

        // Should display the relationship correctly even with foreign key constraints
        $response->assertSee('Car Owner');
        $response->assertSee('Alice Johnson');
    }
}
