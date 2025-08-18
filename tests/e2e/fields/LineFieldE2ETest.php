<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use JTD\AdminPanel\Fields\Line;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Line Field E2E Tests
 *
 * End-to-end tests for Line field covering real-world usage scenarios,
 * complete data flow from PHP backend through API to frontend display.
 * Tests Nova v5 API compatibility in realistic application contexts.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class LineFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with various data for comprehensive E2E testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'is_admin' => false,
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_active' => false,
            'is_admin' => true,
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'is_active' => true,
            'is_admin' => false,
        ]);
    }

    /** @test */
    public function it_displays_line_field_in_user_resource_index(): void
    {
        $users = User::all();
        
        // Create line fields as they would appear in a resource
        $statusField = Line::make('Status', null, function ($user) {
            return $user->is_active ? 'Active User' : 'Inactive User';
        })->asSmall();

        $adminField = Line::make('Role', null, function ($user) {
            return $user->is_admin ? 'Administrator' : 'Regular User';
        })->asHeading();

        // Resolve fields for each user (simulating resource index)
        foreach ($users as $user) {
            $statusField->resolveForDisplay($user);
            $adminField->resolveForDisplay($user);

            // Verify field values are correctly resolved
            if ($user->id === 1) {
                $this->assertEquals('Active User', $statusField->value);
                $this->assertEquals('Regular User', $adminField->value);
            } elseif ($user->id === 2) {
                $this->assertEquals('Inactive User', $statusField->value);
                $this->assertEquals('Administrator', $adminField->value);
            }
        }

        // Verify field serialization for frontend
        $statusJson = $statusField->jsonSerialize();
        $adminJson = $adminField->jsonSerialize();

        $this->assertEquals('LineField', $statusJson['component']);
        $this->assertTrue($statusJson['asSmall']);
        $this->assertTrue($statusJson['isLine']);
        $this->assertTrue($statusJson['readonly']);

        $this->assertEquals('LineField', $adminJson['component']);
        $this->assertTrue($adminJson['asHeading']);
        $this->assertTrue($adminJson['isLine']);
        $this->assertTrue($adminJson['readonly']);
    }

    /** @test */
    public function it_handles_line_field_in_user_detail_view(): void
    {
        $user = User::find(1);
        
        // Create detailed line fields for user detail view
        $fields = [
            Line::make('Full Name', 'name')->asHeading(),
            Line::make('Email Address', 'email'),
            Line::make('Account Status', null, function ($user) {
                $status = $user->is_active ? 'Active' : 'Inactive';
                $role = $user->is_admin ? 'Admin' : 'User';
                return "{$status} {$role}";
            })->asSubText(),
            Line::make('User ID', 'id')->asSmall(),
        ];

        // Resolve all fields for detail view
        foreach ($fields as $field) {
            $field->resolveForDisplay($user);
        }

        // Verify field values
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);
        $this->assertEquals('Active User', $fields[2]->value);
        $this->assertEquals('1', $fields[3]->value);

        // Verify field formatting is preserved
        $this->assertTrue($fields[0]->asHeading);
        $this->assertFalse($fields[0]->asSmall);
        $this->assertFalse($fields[0]->asSubText);

        $this->assertFalse($fields[1]->asHeading);
        $this->assertFalse($fields[1]->asSmall);
        $this->assertFalse($fields[1]->asSubText);

        $this->assertFalse($fields[2]->asHeading);
        $this->assertFalse($fields[2]->asSmall);
        $this->assertTrue($fields[2]->asSubText);

        $this->assertFalse($fields[3]->asHeading);
        $this->assertTrue($fields[3]->asSmall);
        $this->assertFalse($fields[3]->asSubText);
    }

    /** @test */
    public function it_works_with_html_content_in_real_scenarios(): void
    {
        $user = User::find(2); // Jane Smith (admin)
        
        $htmlField = Line::make('Rich Status', null, function ($user) {
            $status = $user->is_active ? 
                '<span style="color: green;">✓ Active</span>' : 
                '<span style="color: red;">✗ Inactive</span>';
            
            $role = $user->is_admin ? 
                '<strong>Administrator</strong>' : 
                '<em>Regular User</em>';
            
            return $status . ' - ' . $role;
        })->asHtml();

        $htmlField->resolveForDisplay($user);

        $expectedHtml = '<span style="color: red;">✗ Inactive</span> - <strong>Administrator</strong>';
        $this->assertEquals($expectedHtml, $htmlField->value);

        // Verify HTML flag is set for frontend
        $json = $htmlField->jsonSerialize();
        $this->assertTrue($json['asHtml']);
        $this->assertEquals($expectedHtml, $json['value']);
    }

    /** @test */
    public function it_handles_conditional_visibility_in_real_usage(): void
    {
        $activeUser = User::find(1);
        $inactiveUser = User::find(2);
        
        $conditionalField = Line::make('Admin Panel Access', null, function ($user) {
            return $user->is_admin ? 'Full Access' : 'Limited Access';
        })
        ->canSee(function ($request, $resource) {
            return $resource->is_active;
        })
        ->showOnDetail();

        // Test with active user (should be visible)
        $this->assertTrue($conditionalField->authorizedToSee(request(), $activeUser));
        $conditionalField->resolveForDisplay($activeUser);
        $this->assertEquals('Limited Access', $conditionalField->value);

        // Test with inactive user (should not be visible)
        $this->assertFalse($conditionalField->authorizedToSee(request(), $inactiveUser));
    }

    /** @test */
    public function it_performs_well_with_multiple_line_fields(): void
    {
        $users = User::all();
        
        // Create multiple line fields (simulating a complex resource)
        $fields = [];
        for ($i = 1; $i <= 10; $i++) {
            $fields[] = Line::make("Field {$i}", null, function ($user) use ($i) {
                return "Value {$i} for {$user->name}";
            });
        }

        $startTime = microtime(true);

        // Resolve all fields for all users
        foreach ($users as $user) {
            foreach ($fields as $field) {
                $field->resolveForDisplay($user);
            }
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should complete quickly even with many fields and users
        $this->assertLessThan(100, $executionTime, 'Line field resolution should be performant');

        // Verify last field resolved correctly
        $lastField = end($fields);
        $this->assertEquals('Value 10 for Bob Wilson', $lastField->value);
    }

    /** @test */
    public function it_integrates_with_resource_authorization(): void
    {
        $adminUser = User::find(2);
        $regularUser = User::find(1);
        
        $sensitiveField = Line::make('Sensitive Data', null, function ($user) {
            return "Sensitive info for {$user->name}";
        })
        ->canSee(function ($request, $resource) {
            // Only show to admins or the user themselves
            $currentUser = $request->user() ?? $resource;
            return $currentUser->is_admin || $currentUser->id === $resource->id;
        });

        // Test admin can see any user's sensitive data
        $this->assertTrue($sensitiveField->authorizedToSee(request(), $adminUser));
        $this->assertTrue($sensitiveField->authorizedToSee(request(), $regularUser));

        // Simulate non-admin user context
        $restrictedField = Line::make('Admin Only', 'email')
            ->canSee(function ($request, $resource) {
                return $resource->is_admin;
            });

        $this->assertTrue($restrictedField->authorizedToSee(request(), $adminUser));
        $this->assertFalse($restrictedField->authorizedToSee(request(), $regularUser));
    }

    /** @test */
    public function it_handles_edge_cases_in_production_scenarios(): void
    {
        // Test with user that has null/empty values
        $user = User::factory()->create([
            'name' => '',
            'email' => 'empty@example.com', // Email cannot be null due to database constraints
            'is_active' => true,
        ]);

        $nameField = Line::make('Name', 'name');
        $emailField = Line::make('Email', 'email');
        $fallbackField = Line::make('Display Name', null, function ($user) {
            return $user->name ?: ($user->email !== 'empty@example.com' ? $user->email : 'Unknown User');
        });

        $nameField->resolveForDisplay($user);
        $emailField->resolveForDisplay($user);
        $fallbackField->resolveForDisplay($user);

        // Should fall back to field name when value is empty
        $this->assertEquals('Name', $nameField->value);
        $this->assertEquals('empty@example.com', $emailField->value);
        $this->assertEquals('Unknown User', $fallbackField->value);
    }

    /** @test */
    public function it_maintains_nova_compatibility_in_real_world_usage(): void
    {
        $user = User::find(1);
        
        // Test complete Nova-style field configuration
        $field = Line::make('User Summary', null, function ($resource) {
            return "{$resource->name} ({$resource->email})";
        })
            ->asHeading()
            ->help('Shows user summary information')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $field->resolveForDisplay($user);

        // Verify Nova API compatibility
        $this->assertEquals('John Doe (john@example.com)', $field->value);
        $this->assertTrue($field->asHeading);
        $this->assertEquals('Shows user summary information', $field->helpText);
        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);

        // Verify JSON serialization includes all Nova properties
        $json = $field->jsonSerialize();
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('value', $json);
        $this->assertArrayHasKey('asHeading', $json);
        $this->assertArrayHasKey('helpText', $json);
        $this->assertArrayHasKey('showOnIndex', $json);
        $this->assertArrayHasKey('showOnDetail', $json);
        $this->assertArrayHasKey('showOnCreation', $json);
        $this->assertArrayHasKey('showOnUpdate', $json);
        $this->assertArrayHasKey('isLine', $json);
        $this->assertArrayHasKey('readonly', $json);
    }
}
