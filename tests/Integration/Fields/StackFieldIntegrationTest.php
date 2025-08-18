<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use JTD\AdminPanel\Fields\Stack;
use JTD\AdminPanel\Fields\Line;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\BelongsTo;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\Fixtures\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

/**
 * Stack Field Integration Tests
 *
 * Tests the integration between the PHP Stack field class and the broader system,
 * ensuring proper field composition, data flow, serialization, and Nova-style behavior.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class StackFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test country
        Country::factory()->create([
            'id' => 1,
            'name' => 'United States',
            'code' => 'US'
        ]);

        // Create test users using factory
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'country_id' => 1,
        ]);
    }

    /** @test */
    public function it_integrates_with_resource_display(): void
    {
        $user = User::find(1);
        $field = Stack::make('User Info')
            ->fields([
                Text::make('Name'),
                Line::make('Email', 'email')->asSmall(),
            ]);

        $field->resolveForDisplay($user);

        $this->assertNull($field->value); // Stack field itself has no value

        $fields = $field->getFields();
        $this->assertCount(2, $fields);
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);

        $json = $field->jsonSerialize();
        $this->assertEquals('User Info', $json['name']);
        $this->assertEquals('StackField', $json['component']);
        $this->assertTrue($json['readonly']);
        $this->assertTrue($json['nullable']);
        $this->assertArrayHasKey('fields', $json);
        $this->assertCount(2, $json['fields']);
    }

    /** @test */
    public function it_works_with_line_method_integration(): void
    {
        $user = User::find(1);
        $field = Stack::make('User Details')
            ->line('User Status', fn($r) => 'Status: ' . ($r->is_active ? 'Active' : 'Inactive'))
            ->line('User ID', fn($r) => 'ID: ' . $r->id);

        $field->resolveForDisplay($user);

        $fields = $field->getFields();
        $this->assertCount(2, $fields);
        $this->assertInstanceOf(Line::class, $fields[0]);
        $this->assertInstanceOf(Line::class, $fields[1]);
        $this->assertEquals('Status: Active', $fields[0]->value);
        $this->assertEquals('ID: 1', $fields[1]->value);
    }

    /** @test */
    public function it_handles_mixed_field_types_integration(): void
    {
        $user = User::find(1);
        $field = Stack::make('Complete Profile')
            ->addField(Text::make('Name'))
            ->line('Email', 'email')
            ->line('Active User', fn($r) => $r->is_active ? 'Active' : 'Inactive');

        $field->resolveForDisplay($user);

        $fields = $field->getFields();
        $this->assertCount(3, $fields);

        $this->assertInstanceOf(Text::class, $fields[0]);
        $this->assertInstanceOf(Line::class, $fields[1]);
        $this->assertInstanceOf(Line::class, $fields[2]);

        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);
        $this->assertEquals('Active', $fields[2]->value);
    }

    /** @test */
    public function it_integrates_with_form_context_without_filling(): void
    {
        $user = User::find(1);
        $field = Stack::make('User Info')
            ->addField(Text::make('Name'))
            ->line('Email', 'email');

        $request = new Request(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        // Stack fields should not fill model data
        $originalName = $user->name;
        $originalEmail = $user->email;

        $field->fill($request, $user);

        $this->assertEquals($originalName, $user->name);
        $this->assertEquals($originalEmail, $user->email);
        $this->assertEquals('John Doe', $user->name); // Should remain unchanged
        $this->assertEquals('john@example.com', $user->email); // Should remain unchanged
    }

    /** @test */
    public function it_maintains_nova_api_compatibility_in_integration(): void
    {
        $user = User::find(1);
        
        // Test that all Nova Stack field methods work correctly in integration
        $field = Stack::make('User Profile')
            ->fields([
                Text::make('Name')->showOnIndex(),
                Line::make('Email', 'email')->asSmall(),
                Line::make('Status', 'status')->asHeading(),
            ])
            ->help('Complete user profile information')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $field->resolveForDisplay($user);

        $json = $field->jsonSerialize();
        
        // Check Nova API compatibility
        $this->assertEquals('User Profile', $json['name']);
        $this->assertEquals('StackField', $json['component']);
        $this->assertEquals('Complete user profile information', $json['helpText']);
        $this->assertTrue($json['showOnIndex']);
        $this->assertTrue($json['showOnDetail']);
        $this->assertFalse($json['showOnCreation']);
        $this->assertFalse($json['showOnUpdate']);
        $this->assertTrue($json['readonly']);
        $this->assertTrue($json['nullable']);
        
        // Check nested fields
        $this->assertCount(3, $json['fields']);
        $this->assertEquals('Name', $json['fields'][0]['name']);
        $this->assertEquals('Email', $json['fields'][1]['name']);
        $this->assertEquals('Status', $json['fields'][2]['name']);
        $this->assertTrue($json['fields'][1]['asSmall']);
        $this->assertTrue($json['fields'][2]['asHeading']);
    }

    /** @test */
    public function it_handles_complex_nested_data_structures(): void
    {
        $user = User::find(1);

        $field = Stack::make('Advanced Profile')
            ->line('Full Name', 'name')
            ->line('Email Address', 'email')
            ->line('User Info', fn($r) => 'User #' . $r->id)
            ->line('Account Type', fn($r) => $r->is_active ? 'Active Account' : 'Inactive Account');

        $field->resolveForDisplay($user);

        $fields = $field->getFields();
        $this->assertCount(4, $fields);

        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);
        $this->assertEquals('User #1', $fields[2]->value);
        $this->assertEquals('Active Account', $fields[3]->value);
    }

    /** @test */
    public function it_works_in_resource_field_collection(): void
    {
        $user = User::find(1);

        // Simulate how fields would be used in a resource alongside other fields
        $fields = [
            Text::make('ID', 'id'),
            Stack::make('User Summary')
                ->line('Name', 'name')
                ->line('Email', 'email')
                ->line('User ID', fn($r) => 'ID: ' . $r->id),
            Text::make('Created At', 'created_at'),
        ];

        // Resolve all fields (Text fields use resolve(), Stack uses resolveForDisplay)
        foreach ($fields as $field) {
            if ($field instanceof Stack) {
                $field->resolveForDisplay($user);
            } else {
                $field->resolve($user);
            }
        }

        // Check stack field resolved correctly
        $stackField = $fields[1];
        $this->assertInstanceOf(Stack::class, $stackField);

        $stackFields = $stackField->getFields();
        $this->assertCount(3, $stackFields);
        $this->assertEquals('John Doe', $stackFields[0]->value);
        $this->assertEquals('john@example.com', $stackFields[1]->value);
        $this->assertEquals('ID: 1', $stackFields[2]->value);

        // Check serialization works for all fields including stack
        $serialized = array_map(fn($field) => $field->jsonSerialize(), $fields);

        $this->assertCount(3, $serialized);
        $this->assertEquals('StackField', $serialized[1]['component']);
        $this->assertArrayHasKey('fields', $serialized[1]);
        $this->assertCount(3, $serialized[1]['fields']);
    }

    /** @test */
    public function it_integrates_with_authorization_context(): void
    {
        $user = User::find(1);
        $request = new Request();
        
        $field = Stack::make('Conditional Stack')
            ->fields([
                Text::make('Name'),
                Line::make('Email', 'email'),
            ])
            ->canSee(function ($request, $resource) {
                return $resource->is_active === true;
            });

        // Test authorization
        $this->assertTrue($field->authorizedToSee($request, $user));

        // Change status and test again
        $user->is_active = false;
        $this->assertFalse($field->authorizedToSee($request, $user));
    }

    /** @test */
    public function it_handles_empty_fields_in_integration(): void
    {
        $user = User::find(1);
        $field = Stack::make('Empty Stack');

        $field->resolveForDisplay($user);

        $this->assertEquals([], $field->getFields());
        $this->assertNull($field->value);
        
        $json = $field->jsonSerialize();
        $this->assertEquals([], $json['fields']);
    }

    /** @test */
    public function it_maintains_field_state_across_operations(): void
    {
        $user = User::find(1);
        $field = Stack::make('Stateful Stack')
            ->line('Name', 'name')
            ->line('Email', 'email');

        // Resolve multiple times
        $field->resolveForDisplay($user);
        $firstFields = $field->getFields();

        $field->resolveForDisplay($user);
        $secondFields = $field->getFields();

        // Field count should be consistent
        $this->assertCount(2, $firstFields);
        $this->assertCount(2, $secondFields);

        // Values should be consistent
        $this->assertEquals($firstFields[0]->value, $secondFields[0]->value);
        $this->assertEquals($firstFields[1]->value, $secondFields[1]->value);
        $this->assertEquals('John Doe', $firstFields[0]->value);
        $this->assertEquals('john@example.com', $firstFields[1]->value);
    }

    /** @test */
    public function it_works_with_fluent_method_chaining(): void
    {
        $user = User::find(1);
        
        $field = Stack::make('Chained Stack')
            ->line('Header', 'name')
            ->addField(Text::make('Email'))
            ->line('Footer', fn($r) => 'User ID: ' . $r->id)
            ->help('Fluently built stack')
            ->showOnIndex();

        $field->resolveForDisplay($user);

        $fields = $field->getFields();
        $this->assertCount(3, $fields);
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);
        $this->assertEquals('User ID: 1', $fields[2]->value);
        
        $this->assertEquals('Fluently built stack', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    /** @test */
    public function it_handles_field_resolution_errors_gracefully(): void
    {
        $user = User::find(1);
        
        $field = Stack::make('Error Handling Stack')
            ->line('Valid Field', 'name')
            ->line('Invalid Field', 'non_existent_attribute')
            ->line('Callback Field', fn($r) => $r->name . ' processed');

        $field->resolveForDisplay($user);

        $fields = $field->getFields();
        $this->assertCount(3, $fields);
        
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('Invalid Field', $fields[1]->value); // Falls back to field name
        $this->assertEquals('John Doe processed', $fields[2]->value);
    }

    /** @test */
    public function it_integrates_with_different_resolve_methods(): void
    {
        $user = User::find(1);

        $field = Stack::make('Mixed Resolution')
            ->addField(Text::make('Name')) // Uses resolve()
            ->line('Email Line', 'email'); // Uses resolveForDisplay()

        // Test both resolve methods
        $field->resolve($user);
        $fields = $field->getFields();

        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);

        // Test resolveForDisplay
        $field->resolveForDisplay($user);
        $fieldsAfterDisplay = $field->getFields();

        $this->assertEquals('John Doe', $fieldsAfterDisplay[0]->value);
        $this->assertEquals('john@example.com', $fieldsAfterDisplay[1]->value);
    }
}
