<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use JTD\AdminPanel\Fields\Line;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

/**
 * Line Field Integration Tests
 *
 * Tests the integration between the PHP Line field class and the broader system,
 * ensuring proper data flow, serialization, and Nova-style behavior in real scenarios.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class LineFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users using factory
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_integrates_with_resource_display(): void
    {
        $user = User::find(1);
        $field = Line::make('User Name', 'name');

        $field->resolveForDisplay($user);

        $this->assertEquals('John Doe', $field->value);

        $json = $field->jsonSerialize();
        $this->assertEquals('John Doe', $json['value']);
        $this->assertEquals('User Name', $json['name']);
        $this->assertEquals('LineField', $json['component']);
        $this->assertTrue($json['isLine']);
    }

    /** @test */
    public function it_works_with_nested_attributes(): void
    {
        $user = User::find(1);
        $field = Line::make('Email', 'email');

        $field->resolveForDisplay($user);

        $this->assertEquals('john@example.com', $field->value);
    }

    /** @test */
    public function it_works_with_resolve_callbacks(): void
    {
        $user = User::find(1);
        $field = Line::make('Full Info', null, function ($resource) {
            return $resource->name . ' (' . $resource->email . ')';
        });

        $field->resolveForDisplay($user);

        $this->assertEquals('John Doe (john@example.com)', $field->value);
    }

    /** @test */
    public function it_handles_formatting_in_integration_context(): void
    {
        $user = User::find(1);

        $smallField = Line::make('Email', 'email')->asSmall();
        $headingField = Line::make('Name', 'name')->asHeading();
        $subTextField = Line::make('ID', 'id')->asSubText();

        $smallField->resolveForDisplay($user);
        $headingField->resolveForDisplay($user);
        $subTextField->resolveForDisplay($user);

        // Check values are resolved
        $this->assertEquals('john@example.com', $smallField->value);
        $this->assertEquals('John Doe', $headingField->value);
        $this->assertEquals('1', $subTextField->value);

        // Check formatting flags in serialization
        $smallJson = $smallField->jsonSerialize();
        $headingJson = $headingField->jsonSerialize();
        $subTextJson = $subTextField->jsonSerialize();

        $this->assertTrue($smallJson['asSmall']);
        $this->assertFalse($smallJson['asHeading']);
        $this->assertFalse($smallJson['asSubText']);

        $this->assertFalse($headingJson['asSmall']);
        $this->assertTrue($headingJson['asHeading']);
        $this->assertFalse($headingJson['asSubText']);

        $this->assertFalse($subTextJson['asSmall']);
        $this->assertFalse($subTextJson['asHeading']);
        $this->assertTrue($subTextJson['asSubText']);
    }

    /** @test */
    public function it_integrates_with_form_context_without_filling(): void
    {
        $user = User::find(1);
        $field = Line::make('Display Name', 'name');
        $request = new Request(['name' => 'Jane Doe']);

        // Line fields should not fill model data
        $originalName = $user->name;
        $field->fill($request, $user);

        $this->assertEquals($originalName, $user->name);
        $this->assertEquals('John Doe', $user->name); // Should remain unchanged
    }

    /** @test */
    public function it_works_with_html_content_integration(): void
    {
        $user = User::find(1);
        $htmlContent = '<strong>Active User</strong> <em>Premium</em>';
        
        $field = Line::make('Status HTML', null, fn() => $htmlContent)->asHtml();
        $field->resolveForDisplay($user);

        $this->assertEquals($htmlContent, $field->value);
        
        $json = $field->jsonSerialize();
        $this->assertTrue($json['asHtml']);
        $this->assertEquals($htmlContent, $json['value']);
    }

    /** @test */
    public function it_maintains_nova_api_compatibility_in_integration(): void
    {
        $user = User::find(1);
        
        // Test that all Nova Line field methods work correctly in integration
        $field = Line::make('User Information')
            ->asHeading()
            ->help('This shows user information')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $field->resolveForDisplay($user);

        $json = $field->jsonSerialize();
        
        // Check Nova API compatibility
        $this->assertEquals('User Information', $json['name']);
        $this->assertEquals('LineField', $json['component']);
        $this->assertTrue($json['asHeading']);
        $this->assertTrue($json['isLine']);
        $this->assertEquals('This shows user information', $json['helpText']);
        $this->assertTrue($json['showOnIndex']);
        $this->assertTrue($json['showOnDetail']);
        $this->assertFalse($json['showOnCreation']);
        $this->assertFalse($json['showOnUpdate']);
        $this->assertTrue($json['readonly']);
    }

    /** @test */
    public function it_handles_null_and_empty_values_in_integration(): void
    {
        $user = User::find(1);

        // Test with non-existent attribute
        $field = Line::make('Non Existent', 'non_existent_field');
        $field->resolveForDisplay($user);

        // Should fall back to field name
        $this->assertEquals('Non Existent', $field->value);

        // Test with null value from callback
        $nullField = Line::make('Null Value', null, fn() => null);
        $nullField->resolveForDisplay($user);

        // Should fall back to field name when callback returns null
        $this->assertEquals('Null Value', $nullField->value);
    }

    /** @test */
    public function it_works_in_resource_field_collection(): void
    {
        $user = User::find(1);

        // Simulate how fields would be used in a resource
        $fields = [
            Line::make('User Name', 'name')->asHeading(),
            Line::make('Email Address', 'email')->asSmall(),
            Line::make('User ID', 'id'),
        ];

        // Resolve all fields
        foreach ($fields as $field) {
            $field->resolveForDisplay($user);
        }

        // Check all fields resolved correctly
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);
        $this->assertEquals('1', $fields[2]->value);

        // Check serialization works for all fields
        $serialized = array_map(fn($field) => $field->jsonSerialize(), $fields);

        $this->assertCount(3, $serialized);
        $this->assertTrue($serialized[0]['asHeading']);
        $this->assertTrue($serialized[1]['asSmall']);
        $this->assertFalse($serialized[2]['asHeading']);
        $this->assertFalse($serialized[2]['asSmall']);
    }

    /** @test */
    public function it_handles_complex_data_structures(): void
    {
        $user = User::find(1);

        // Test with complex callback that processes data
        $field = Line::make('User Summary', null, function ($resource) {
            return sprintf(
                '%s (%s) - %s',
                $resource->name,
                $resource->email,
                $resource->is_active ? 'Active' : 'Inactive'
            );
        });

        $field->resolveForDisplay($user);

        $this->assertEquals('John Doe (john@example.com) - Active', $field->value);
    }

    /** @test */
    public function it_integrates_with_authorization_context(): void
    {
        $user = User::find(1);
        $request = new Request();

        $field = Line::make('Sensitive Info', 'email')
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
    public function it_works_with_visibility_controls_in_integration(): void
    {
        $field = Line::make('Conditional Field', 'name')
            ->showOnIndex()
            ->hideFromDetail();

        $user = User::find(1);
        $request = new Request();

        // Test conditional visibility
        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
    }

    /** @test */
    public function it_maintains_field_state_across_operations(): void
    {
        $user = User::find(1);
        $field = Line::make('Stateful Field', 'name')->asHeading();

        // Resolve multiple times
        $field->resolveForDisplay($user);
        $firstValue = $field->value;
        
        $field->resolveForDisplay($user);
        $secondValue = $field->value;

        // Values should be consistent
        $this->assertEquals($firstValue, $secondValue);
        $this->assertEquals('John Doe', $firstValue);
        
        // Formatting should be maintained
        $this->assertTrue($field->asHeading);
        $this->assertFalse($field->asSmall);
        $this->assertFalse($field->asSubText);
    }
}
