<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Hidden;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Hidden Field Integration Tests
 *
 * Tests the integration between the PHP Hidden field class and Laravel,
 * ensuring proper data flow, API compatibility, and Nova-style behavior.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HiddenFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_hidden_field_with_nova_syntax(): void
    {
        $field = Hidden::make('Slug');

        $this->assertEquals('Slug', $field->name);
        $this->assertEquals('slug', $field->attribute);
        $this->assertEquals('HiddenField', $field->component);
    }

    /** @test */
    public function it_creates_hidden_field_with_custom_attribute(): void
    {
        $field = Hidden::make('CSRF Token', 'csrf_token');

        $this->assertEquals('CSRF Token', $field->name);
        $this->assertEquals('csrf_token', $field->attribute);
        $this->assertEquals('HiddenField', $field->component);
    }

    /** @test */
    public function it_resolves_and_fills_values(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $field = Hidden::make('User ID', 'id');
        $field->resolve($user);
        $this->assertEquals($user->id, $field->value);

        $request = new Request(['id' => 999]);
        $field->fill($request, $user);
        $this->assertEquals(999, $user->id);
    }

    /** @test */
    public function it_handles_default_values_nova_style(): void
    {
        // Test static default
        $field1 = Hidden::make('Type')->default('user');
        $user = new User();
        
        $field1->resolve($user);
        $resolvedValue = $field1->resolveValue($user);
        $this->assertEquals('user', $resolvedValue);

        // Test callable default (Nova style)
        $field2 = Hidden::make('User ID', 'user_id')->default(function ($request) {
            return 123;
        });
        
        $user2 = new User();
        $field2->resolve($user2);
        $resolvedValue2 = $field2->resolveValue($user2);
        $this->assertEquals(123, $resolvedValue2);
    }

    /** @test */
    public function it_fills_model_with_callable_default_when_no_request_value(): void
    {
        $field = Hidden::make('User ID', 'user_id')->default(function ($request) {
            return $request->user()->id ?? 456;
        });

        $user = new User();
        $request = new Request(); // No user_id in request

        $field->fill($request, $user);
        $this->assertEquals(456, $user->user_id);
    }

    /** @test */
    public function it_prioritizes_request_value_over_default(): void
    {
        $field = Hidden::make('User ID', 'user_id')->default(function ($request) {
            return 999;
        });

        $user = new User();
        $request = new Request(['user_id' => 123]);

        $field->fill($request, $user);
        $this->assertEquals(123, $user->user_id);
    }

    /** @test */
    public function it_serializes_for_inertia_with_resolved_callable_defaults(): void
    {
        $field = Hidden::make('Token', 'token')->default(function ($request) {
            return 'generated-token-' . time();
        });

        $serialized = $field->jsonSerialize();

        $this->assertArrayHasKey('default', $serialized);
        $this->assertStringContains('generated-token-', $serialized['default']);
        $this->assertIsString($serialized['default']); // Should be resolved, not callable
    }

    /** @test */
    public function it_has_correct_visibility_defaults(): void
    {
        $field = Hidden::make('Token');

        $serialized = $field->jsonSerialize();

        $this->assertFalse($serialized['showOnIndex']);
        $this->assertFalse($serialized['showOnDetail']);
        $this->assertTrue($serialized['showOnCreation']);
        $this->assertTrue($serialized['showOnUpdate']);
    }

    /** @test */
    public function it_can_override_visibility_settings(): void
    {
        $field = Hidden::make('Token')
            ->showOnIndex()
            ->showOnDetail();

        $serialized = $field->jsonSerialize();

        $this->assertTrue($serialized['showOnIndex']);
        $this->assertTrue($serialized['showOnDetail']);
        $this->assertTrue($serialized['showOnCreation']);
        $this->assertTrue($serialized['showOnUpdate']);
    }

    /** @test */
    public function it_works_with_validation_rules(): void
    {
        $field = Hidden::make('Token')
            ->rules('required', 'string', 'min:10');

        $serialized = $field->jsonSerialize();

        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('string', $serialized['rules']);
        $this->assertContains('min:10', $serialized['rules']);
    }

    /** @test */
    public function it_handles_fill_callback_override(): void
    {
        $field = Hidden::make('Token')
            ->fillUsing(function ($request, $model, $attribute) {
                $model->{$attribute} = 'custom-fill-value';
            });

        $user = new User();
        $request = new Request(['token' => 'request-value']);

        $field->fill($request, $user);
        $this->assertEquals('custom-fill-value', $user->token);
    }

    /** @test */
    public function it_handles_resolve_callback_override(): void
    {
        $field = Hidden::make('Display Name', 'name', function ($resource, $attribute) {
            return strtoupper($resource->{$attribute});
        });

        $user = User::factory()->create(['name' => 'john doe']);
        $field->resolve($user);

        $this->assertEquals('JOHN DOE', $field->value);
    }

    /** @test */
    public function it_provides_complete_nova_api_compatibility(): void
    {
        // Test all Nova Hidden field patterns from documentation
        
        // Basic usage: Hidden::make('Slug')
        $field1 = Hidden::make('Slug');
        $this->assertEquals('HiddenField', $field1->component);
        
        // Default value: Hidden::make('Slug')->default(Str::random(64))
        $field2 = Hidden::make('Slug')->default('abc123');
        $this->assertEquals('abc123', $field2->default);
        
        // Callable default: Hidden::make('User', 'user_id')->default(function ($request) { return $request->user()->id; })
        $field3 = Hidden::make('User', 'user_id')->default(function ($request) {
            return $request->user()->id ?? 1;
        });
        $this->assertIsCallable($field3->default);
        
        // Test serialization works for all patterns
        $serialized1 = $field1->jsonSerialize();
        $serialized2 = $field2->jsonSerialize();
        $serialized3 = $field3->jsonSerialize();
        
        $this->assertEquals('HiddenField', $serialized1['component']);
        $this->assertEquals('abc123', $serialized2['default']);
        $this->assertEquals(1, $serialized3['default']); // Callable should be resolved
    }
}
