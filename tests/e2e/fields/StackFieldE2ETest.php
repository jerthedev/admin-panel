<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Fields;

use JTD\AdminPanel\Fields\Stack;
use JTD\AdminPanel\Fields\Line;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Stack Field E2E Tests
 *
 * End-to-end tests for Stack field covering real-world usage scenarios,
 * complete data flow from PHP backend through API to frontend display.
 * Tests Nova v5 API compatibility in realistic application contexts.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class StackFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test countries
        Country::factory()->create([
            'id' => 1,
            'name' => 'United States',
            'code' => 'US'
        ]);

        Country::factory()->create([
            'id' => 2,
            'name' => 'Canada',
            'code' => 'CA'
        ]);
        
        // Create test users with various data for comprehensive E2E testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'is_admin' => false,
            'country_id' => 1,
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_active' => false,
            'is_admin' => true,
            'country_id' => 2,
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'is_active' => true,
            'is_admin' => false,
            'country_id' => 1,
        ]);
    }

    /** @test */
    public function it_displays_stack_field_in_user_resource_index(): void
    {
        $users = User::all();
        
        // Create stack field as it would appear in a resource index
        $userSummaryStack = Stack::make('User Summary')
            ->line('Name', 'name')
            ->line('Status', function ($user) {
                return $user->is_active ? '✓ Active' : '✗ Inactive';
            })
            ->line('Role', function ($user) {
                return $user->is_admin ? 'Administrator' : 'User';
            });

        // Resolve stack for each user (simulating resource index)
        foreach ($users as $user) {
            $userSummaryStack->resolveForDisplay($user);

            $fields = $userSummaryStack->getFields();
            $this->assertCount(3, $fields);

            // Verify field values are correctly resolved
            if ($user->id === 1) {
                $this->assertEquals('John Doe', $fields[0]->value);
                $this->assertEquals('✓ Active', $fields[1]->value);
                $this->assertEquals('User', $fields[2]->value);
            } elseif ($user->id === 2) {
                $this->assertEquals('Jane Smith', $fields[0]->value);
                $this->assertEquals('✗ Inactive', $fields[1]->value);
                $this->assertEquals('Administrator', $fields[2]->value);
            }
        }

        // Verify stack serialization for frontend
        $json = $userSummaryStack->jsonSerialize();
        $this->assertEquals('StackField', $json['component']);
        $this->assertTrue($json['readonly']);
        $this->assertTrue($json['nullable']);
        $this->assertArrayHasKey('fields', $json);
        $this->assertCount(3, $json['fields']);
    }

    /** @test */
    public function it_handles_complex_stack_in_user_detail_view(): void
    {
        $user = User::find(1);
        
        // Create comprehensive stack for user detail view
        $detailStack = Stack::make('User Profile')
            ->addField(Text::make('Full Name', 'name'))
            ->line('Contact', 'email')
            ->line('Account Status', function ($user) {
                $status = $user->is_active ? 'Active Account' : 'Inactive Account';
                $role = $user->is_admin ? ' (Administrator)' : ' (Regular User)';
                return $status . $role;
            })
            ->line('User ID', function ($user) {
                return 'ID: ' . $user->id;
            })
            ->line('Country', function ($user) {
                return $user->country ? $user->country->name : 'Unknown';
            });

        $detailStack->resolveForDisplay($user);

        $fields = $detailStack->getFields();
        $this->assertCount(5, $fields);

        // Verify field values and types
        $this->assertInstanceOf(Text::class, $fields[0]);
        $this->assertEquals('John Doe', $fields[0]->value);

        $this->assertInstanceOf(Line::class, $fields[1]);
        $this->assertEquals('john@example.com', $fields[1]->value);

        $this->assertInstanceOf(Line::class, $fields[2]);
        $this->assertEquals('Active Account (Regular User)', $fields[2]->value);

        $this->assertInstanceOf(Line::class, $fields[3]);
        $this->assertEquals('ID: 1', $fields[3]->value);

        $this->assertInstanceOf(Line::class, $fields[4]);
        $this->assertEquals('United States', $fields[4]->value);
    }

    /** @test */
    public function it_works_with_formatted_line_fields_in_stack(): void
    {
        $user = User::find(2); // Jane Smith (admin, inactive)
        
        $formattedStack = Stack::make('Formatted User Info')
            ->line('User Name', 'name')
            ->line('Status Badge', function ($user) {
                return $user->is_active ? 'ACTIVE' : 'INACTIVE';
            })
            ->line('Role Badge', function ($user) {
                return $user->is_admin ? 'ADMIN' : 'USER';
            })
            ->line('Contact Info', 'email');

        // Apply formatting to line fields after creation
        $formattedStack->getFields()[0]->asHeading();
        $formattedStack->getFields()[1]->asSmall();
        $formattedStack->getFields()[2]->asSmall();
        $formattedStack->getFields()[3]->asSubText();

        $formattedStack->resolveForDisplay($user);

        $fields = $formattedStack->getFields();
        
        // Verify formatting is preserved
        $this->assertTrue($fields[0]->asHeading);
        $this->assertTrue($fields[1]->asSmall);
        $this->assertTrue($fields[2]->asSmall);
        $this->assertTrue($fields[3]->asSubText);

        // Verify values
        $this->assertEquals('Jane Smith', $fields[0]->value);
        $this->assertEquals('INACTIVE', $fields[1]->value);
        $this->assertEquals('ADMIN', $fields[2]->value);
        $this->assertEquals('jane@example.com', $fields[3]->value);

        // Verify serialization includes formatting
        $json = $formattedStack->jsonSerialize();
        $this->assertTrue($json['fields'][0]['asHeading']);
        $this->assertTrue($json['fields'][1]['asSmall']);
        $this->assertTrue($json['fields'][2]['asSmall']);
        $this->assertTrue($json['fields'][3]['asSubText']);
    }

    /** @test */
    public function it_handles_conditional_visibility_in_real_usage(): void
    {
        $activeUser = User::find(1);
        $inactiveUser = User::find(2);
        
        $conditionalStack = Stack::make('Admin Panel')
            ->line('Access Level', function ($user) {
                return $user->is_admin ? 'Full Access' : 'Limited Access';
            })
            ->line('Status', function ($user) {
                return $user->is_active ? 'Online' : 'Offline';
            })
            ->canSee(function ($request, $resource) {
                return $resource->is_active; // Only show for active users
            });

        // Test with active user (should be visible)
        $this->assertTrue($conditionalStack->authorizedToSee(request(), $activeUser));
        $conditionalStack->resolveForDisplay($activeUser);
        
        $fields = $conditionalStack->getFields();
        $this->assertEquals('Limited Access', $fields[0]->value);
        $this->assertEquals('Online', $fields[1]->value);

        // Test with inactive user (should not be visible)
        $this->assertFalse($conditionalStack->authorizedToSee(request(), $inactiveUser));
    }

    /** @test */
    public function it_performs_well_with_complex_stacks(): void
    {
        $users = User::all();
        
        // Create complex stack with multiple field types
        $complexStack = Stack::make('Complex User Data')
            ->addField(Text::make('Name'))
            ->line('Email', 'email')
            ->line('Status', function ($user) {
                return $user->is_active ? 'Active' : 'Inactive';
            })
            ->line('Role', function ($user) {
                return $user->is_admin ? 'Admin' : 'User';
            })
            ->line('Country', function ($user) {
                return $user->country ? $user->country->name : 'Unknown';
            })
            ->line('Summary', function ($user) {
                return sprintf(
                    '%s (%s) - %s %s from %s',
                    $user->name,
                    $user->email,
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->is_admin ? 'Admin' : 'User',
                    $user->country ? $user->country->name : 'Unknown'
                );
            });

        $startTime = microtime(true);

        // Resolve stack for all users
        foreach ($users as $user) {
            $complexStack->resolveForDisplay($user);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should complete quickly even with complex stacks
        $this->assertLessThan(150, $executionTime, 'Stack field resolution should be performant');

        // Verify last resolution was correct
        $fields = $complexStack->getFields();
        $this->assertCount(6, $fields);
        $this->assertEquals('Bob Wilson', $fields[0]->value);
        $this->assertEquals('bob@example.com', $fields[1]->value);
    }

    /** @test */
    public function it_integrates_with_resource_authorization(): void
    {
        $adminUser = User::find(2);
        $regularUser = User::find(1);
        
        $adminStack = Stack::make('Admin Information')
            ->line('Admin Status', function ($user) {
                return $user->is_admin ? 'Administrator' : 'Regular User';
            })
            ->line('Permissions', function ($user) {
                return $user->is_admin ? 'Full Access' : 'Limited Access';
            })
            ->canSee(function ($request, $resource) {
                // Only show admin info to admins
                return $resource->is_admin;
            });

        // Test admin can see admin stack
        $this->assertTrue($adminStack->authorizedToSee(request(), $adminUser));
        $adminStack->resolveForDisplay($adminUser);
        
        $fields = $adminStack->getFields();
        $this->assertEquals('Administrator', $fields[0]->value);
        $this->assertEquals('Full Access', $fields[1]->value);

        // Test regular user cannot see admin stack
        $this->assertFalse($adminStack->authorizedToSee(request(), $regularUser));
    }

    /** @test */
    public function it_handles_edge_cases_in_production_scenarios(): void
    {
        // Test with user that has null/empty relationships
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
            'country_id' => null, // No country
        ]);

        $edgeCaseStack = Stack::make('Edge Case Stack')
            ->line('Name', 'name')
            ->line('Country', function ($user) {
                return $user->country ? $user->country->name : 'No Country';
            })
            ->line('Fallback', function ($user) {
                return $user->nonexistent_field ?? 'Default Value';
            });

        $edgeCaseStack->resolveForDisplay($user);

        $fields = $edgeCaseStack->getFields();
        $this->assertEquals('Test User', $fields[0]->value);
        $this->assertEquals('No Country', $fields[1]->value);
        $this->assertEquals('Default Value', $fields[2]->value);
    }

    /** @test */
    public function it_maintains_nova_compatibility_in_real_world_usage(): void
    {
        $user = User::find(1);
        
        // Test complete Nova-style stack configuration
        $novaStack = Stack::make('Nova Compatible Stack')
            ->fields([
                Text::make('Name')->showOnIndex(),
                Line::make('Email', 'email')->asSmall(),
                Line::make('Status', null, function ($user) {
                    return $user->is_active ? 'Active' : 'Inactive';
                })->asHeading(),
            ])
            ->help('Nova-style stack field')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $novaStack->resolveForDisplay($user);

        // Verify Nova API compatibility
        $this->assertEquals('Nova-style stack field', $novaStack->helpText);
        $this->assertTrue($novaStack->showOnIndex);
        $this->assertTrue($novaStack->showOnDetail);
        $this->assertFalse($novaStack->showOnCreation);
        $this->assertFalse($novaStack->showOnUpdate);

        // Verify JSON serialization includes all Nova properties
        $json = $novaStack->jsonSerialize();
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('fields', $json);
        $this->assertArrayHasKey('helpText', $json);
        $this->assertArrayHasKey('showOnIndex', $json);
        $this->assertArrayHasKey('showOnDetail', $json);
        $this->assertArrayHasKey('showOnCreation', $json);
        $this->assertArrayHasKey('showOnUpdate', $json);
        $this->assertArrayHasKey('readonly', $json);
        $this->assertArrayHasKey('nullable', $json);

        // Verify nested fields maintain their properties
        $this->assertCount(3, $json['fields']);
        $this->assertEquals('TextField', $json['fields'][0]['component']);
        $this->assertEquals('LineField', $json['fields'][1]['component']);
        $this->assertEquals('LineField', $json['fields'][2]['component']);
        $this->assertTrue($json['fields'][1]['asSmall']);
        $this->assertTrue($json['fields'][2]['asHeading']);
    }

    /** @test */
    public function it_works_with_multiple_stacks_in_resource(): void
    {
        $user = User::find(1);
        
        // Simulate multiple stacks in a single resource
        $stacks = [
            Stack::make('Basic Info')
                ->line('Name', 'name')
                ->line('Email', 'email'),

            Stack::make('Status Info')
                ->line('Active', fn($u) => $u->is_active ? 'Yes' : 'No')
                ->line('Admin', fn($u) => $u->is_admin ? 'Yes' : 'No'),

            Stack::make('Location Info')
                ->line('Country', fn($u) => $u->country ? $u->country->name : 'Unknown')
                ->line('Country Code', fn($u) => $u->country ? $u->country->code : 'N/A'),
        ];

        // Resolve all stacks
        foreach ($stacks as $stack) {
            $stack->resolveForDisplay($user);
        }

        // Verify each stack resolved correctly
        $basicFields = $stacks[0]->getFields();
        $this->assertEquals('John Doe', $basicFields[0]->value);
        $this->assertEquals('john@example.com', $basicFields[1]->value);

        $statusFields = $stacks[1]->getFields();
        $this->assertEquals('Yes', $statusFields[0]->value);
        $this->assertEquals('No', $statusFields[1]->value);

        $locationFields = $stacks[2]->getFields();
        $this->assertEquals('United States', $locationFields[0]->value);
        $this->assertEquals('US', $locationFields[1]->value);

        // Verify serialization works for all stacks
        $serialized = array_map(fn($stack) => $stack->jsonSerialize(), $stacks);
        $this->assertCount(3, $serialized);
        
        foreach ($serialized as $stackJson) {
            $this->assertEquals('StackField', $stackJson['component']);
            $this->assertArrayHasKey('fields', $stackJson);
            $this->assertCount(2, $stackJson['fields']);
        }
    }
}
