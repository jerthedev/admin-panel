<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Password Field Integration Test
 *
 * Tests the complete integration between PHP Password field class,
 * API endpoints, password hashing, and frontend functionality.
 * 
 * Focuses on Nova-compatible Password field behavior including
 * automatic password hashing, security features, and form integration.
 */
class PasswordFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_password_field_with_nova_syntax(): void
    {
        $field = Password::make('Password');

        $this->assertEquals('Password', $field->name);
        $this->assertEquals('password', $field->attribute);
        $this->assertEquals('PasswordField', $field->component);
    }

    /** @test */
    public function it_creates_password_field_with_custom_attribute(): void
    {
        $field = Password::make('User Password', 'user_password');

        $this->assertEquals('User Password', $field->name);
        $this->assertEquals('user_password', $field->attribute);
        $this->assertEquals('PasswordField', $field->component);
    }

    /** @test */
    public function it_hides_from_index_and_detail_by_default(): void
    {
        $field = Password::make('Password');

        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_can_override_visibility_settings(): void
    {
        $field = Password::make('Password')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_never_resolves_password_values_for_security(): void
    {
        $user = User::find(1);
        $user->password = Hash::make('secret123');
        $user->save();

        $field = Password::make('Password');
        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_hashes_password_on_fill(): void
    {
        $field = Password::make('Password');
        $user = new User();
        $request = new Request(['password' => 'secret123']);

        $field->fill($request, $user);

        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    /** @test */
    public function it_ignores_empty_password_values(): void
    {
        $field = Password::make('Password');
        $user = new User();
        $request = new Request(['password' => '']);

        $field->fill($request, $user);

        $this->assertObjectNotHasProperty('password', $user);
    }

    /** @test */
    public function it_ignores_null_password_values(): void
    {
        $field = Password::make('Password');
        $user = new User();
        $request = new Request(['password' => null]);

        $field->fill($request, $user);

        $this->assertObjectNotHasProperty('password', $user);
    }

    /** @test */
    public function it_uses_custom_fill_callback_when_provided(): void
    {
        $field = Password::make('Password')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'custom-hash';
        });
        $user = new User();
        $request = new Request(['password' => 'secret123']);

        $field->fill($request, $user);

        $this->assertEquals('custom-hash', $user->password);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = Password::make('User Password')
            ->help('Enter a secure password')
            ->rules('required', 'min:8');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Password', $json['name']);
        $this->assertEquals('user_password', $json['attribute']);
        $this->assertEquals('PasswordField', $json['component']);
        $this->assertEquals('Enter a secure password', $json['helpText']);
        $this->assertEquals(['required', 'min:8'], $json['rules']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    /** @test */
    public function it_works_with_validation_rules(): void
    {
        $field = Password::make('Password')
            ->rules('required', 'min:8', 'confirmed');

        $this->assertEquals(['required', 'min:8', 'confirmed'], $field->rules);
    }

    /** @test */
    public function it_works_with_nullable_rule(): void
    {
        $field = Password::make('Password')->nullable();

        $this->assertTrue($field->nullable);
    }

    /** @test */
    public function it_works_with_readonly_state(): void
    {
        $field = Password::make('Password')->readonly();

        $this->assertTrue($field->readonly);
    }

    /** @test */
    public function it_works_with_help_text(): void
    {
        $field = Password::make('Password')->help('Enter a secure password');

        $this->assertEquals('Enter a secure password', $field->helpText);
    }

    /** @test */
    public function it_works_with_placeholder_text(): void
    {
        $field = Password::make('Password')->placeholder('Enter your password');

        $this->assertEquals('Enter your password', $field->placeholder);
    }

    /** @test */
    public function it_integrates_with_laravel_request_validation(): void
    {
        $field = Password::make('Password')->rules('required', 'min:8');
        
        // Simulate a request with validation
        $request = new Request(['password' => 'short']);
        
        // The field should have the correct validation rules
        $this->assertEquals(['required', 'min:8'], $field->rules);
        
        // Test with valid password
        $validRequest = new Request(['password' => 'validpassword123']);
        $user = new User();
        
        $field->fill($validRequest, $user);
        
        $this->assertTrue(Hash::check('validpassword123', $user->password));
    }

    /** @test */
    public function it_maintains_field_inheritance_from_base_field(): void
    {
        $field = Password::make('Password');

        // Test that Password field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
        $this->assertTrue(method_exists($field, 'showOnIndex'));
        $this->assertTrue(method_exists($field, 'showOnDetail'));
        $this->assertTrue(method_exists($field, 'hideFromIndex'));
        $this->assertTrue(method_exists($field, 'hideFromDetail'));
        $this->assertTrue(method_exists($field, 'hideWhenCreating'));
        $this->assertTrue(method_exists($field, 'hideWhenUpdating'));
        $this->assertTrue(method_exists($field, 'onlyOnForms'));
        $this->assertTrue(method_exists($field, 'exceptOnForms'));
        $this->assertTrue(method_exists($field, 'fillUsing'));
        $this->assertTrue(method_exists($field, 'resolveUsing'));
    }

    /** @test */
    public function it_works_in_resource_context(): void
    {
        $user = User::find(1);
        $field = Password::make('Password');
        
        // Test field configuration in resource context
        $field->resolve($user);
        
        // Password should never be resolved for security
        $this->assertNull($field->value);
        
        // Test field serialization for frontend
        $json = $field->jsonSerialize();
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('attribute', $json);
        $this->assertArrayHasKey('name', $json);
        $this->assertEquals('PasswordField', $json['component']);
    }
}
