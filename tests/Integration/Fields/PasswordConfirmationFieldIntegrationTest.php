<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\PasswordConfirmation;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * PasswordConfirmation Field Integration Test
 *
 * Tests the complete integration between PHP PasswordConfirmation field class,
 * API endpoints, validation, and frontend functionality.
 * 
 * Focuses on Nova API compatibility and proper field behavior in the context
 * of password confirmation workflows.
 */
class PasswordConfirmationFieldIntegrationTest extends TestCase
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
    public function it_creates_password_confirmation_field_with_nova_syntax(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        $this->assertEquals('Password Confirmation', $field->name);
        $this->assertEquals('password_confirmation', $field->attribute);
        $this->assertEquals('PasswordConfirmationField', $field->component);
    }

    /** @test */
    public function it_creates_field_with_custom_attribute(): void
    {
        $field = PasswordConfirmation::make('Confirm Password', 'confirm_password');

        $this->assertEquals('Confirm Password', $field->name);
        $this->assertEquals('confirm_password', $field->attribute);
    }

    /** @test */
    public function it_sets_only_on_forms_by_default(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_can_override_default_visibility(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->showOnIndex()
            ->showOnDetail();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_accepts_validation_rules(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->rules('required', 'confirmed');

        $this->assertEquals(['required', 'confirmed'], $field->rules);
    }

    /** @test */
    public function it_accepts_help_text(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->help('Must match the password above');

        $this->assertEquals('Must match the password above', $field->helpText);
    }

    /** @test */
    public function it_accepts_placeholder_text(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->placeholder('Re-enter your password');

        $this->assertEquals('Re-enter your password', $field->placeholder);
    }

    /** @test */
    public function it_can_be_set_as_readonly(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->readonly();

        $this->assertTrue($field->readonly);
    }

    /** @test */
    public function it_can_be_set_as_nullable(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->nullable();

        $this->assertTrue($field->nullable);
    }

    /** @test */
    public function it_always_resolves_to_null_for_security(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        $user = User::find(1);
        
        // Even if the resource has a password_confirmation value, it should resolve to null
        $user->password_confirmation = 'secret123';
        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_does_not_fill_model_on_request(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        $user = new User();
        $request = new Request(['password_confirmation' => 'secret123']);

        $field->fill($request, $user);

        // Password confirmation fields should not modify the model
        $this->assertFalse(isset($user->password_confirmation));
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = PasswordConfirmation::make('Confirm Password')
            ->rules('required', 'confirmed')
            ->help('Re-enter your password')
            ->placeholder('Confirm password');

        $json = $field->jsonSerialize();

        $this->assertEquals('Confirm Password', $json['name']);
        $this->assertEquals('confirm_password', $json['attribute']);
        $this->assertEquals('PasswordConfirmationField', $json['component']);
        $this->assertEquals(['required', 'confirmed'], $json['rules']);
        $this->assertEquals('Re-enter your password', $json['helpText']);
        $this->assertEquals('Confirm password', $json['placeholder']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    /** @test */
    public function it_works_with_fill_using_callback(): void
    {
        $callbackExecuted = false;
        
        $field = PasswordConfirmation::make('Password Confirmation')
            ->fillUsing(function ($request, $model, $attribute) use (&$callbackExecuted) {
                $callbackExecuted = true;
                // Even with a callback, password confirmation should not modify the model
            });

        $user = new User();
        $request = new Request(['password_confirmation' => 'secret123']);

        $field->fill($request, $user);

        // The base fill method should not execute the callback for password confirmation
        $this->assertFalse($callbackExecuted);
        $this->assertFalse(isset($user->password_confirmation));
    }

    /** @test */
    public function it_maintains_nova_field_inheritance(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        // Test that it inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
        $this->assertTrue(method_exists($field, 'showOnIndex'));
        $this->assertTrue(method_exists($field, 'hideFromIndex'));
        $this->assertTrue(method_exists($field, 'onlyOnForms'));
    }

    /** @test */
    public function it_supports_nova_field_chaining(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->rules('required', 'confirmed')
            ->help('Must match password')
            ->placeholder('Confirm password')
            ->nullable()
            ->readonly();

        $this->assertEquals(['required', 'confirmed'], $field->rules);
        $this->assertEquals('Must match password', $field->helpText);
        $this->assertEquals('Confirm password', $field->placeholder);
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
    }

    /** @test */
    public function it_returns_empty_meta_array(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        
        $meta = $field->meta();
        
        $this->assertIsArray($meta);
        $this->assertEmpty($meta);
    }

    /** @test */
    public function it_supports_custom_meta_data(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->withMeta(['customKey' => 'customValue']);

        $meta = $field->meta();

        $this->assertArrayHasKey('customKey', $meta);
        $this->assertEquals('customValue', $meta['customKey']);
    }

    /** @test */
    public function it_integrates_with_typical_password_workflow(): void
    {
        // Simulate a typical password change form with password and password_confirmation
        $passwordField = \JTD\AdminPanel\Fields\Password::make('Password')
            ->rules('required', 'min:8', 'confirmed');
            
        $confirmationField = PasswordConfirmation::make('Password Confirmation')
            ->rules('required');

        // Both fields should be configured for forms only
        $this->assertTrue($passwordField->showOnCreation);
        $this->assertTrue($passwordField->showOnUpdate);
        $this->assertFalse($passwordField->showOnIndex);
        $this->assertFalse($passwordField->showOnDetail);

        $this->assertTrue($confirmationField->showOnCreation);
        $this->assertTrue($confirmationField->showOnUpdate);
        $this->assertFalse($confirmationField->showOnIndex);
        $this->assertFalse($confirmationField->showOnDetail);

        // Confirmation field should not store data
        $user = new User();
        $request = new Request([
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $confirmationField->fill($request, $user);
        
        // Password confirmation should not be set on the model
        $this->assertFalse(isset($user->password_confirmation));
    }
}
