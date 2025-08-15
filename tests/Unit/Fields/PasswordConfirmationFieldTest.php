<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\PasswordConfirmation;
use JTD\AdminPanel\Tests\TestCase;

/**
 * PasswordConfirmation Field Unit Tests
 *
 * Tests for PasswordConfirmation field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PasswordConfirmationFieldTest extends TestCase
{
    public function test_password_confirmation_field_creation(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        $this->assertEquals('Password Confirmation', $field->name);
        $this->assertEquals('password_confirmation', $field->attribute);
        $this->assertEquals('PasswordConfirmationField', $field->component);
    }

    public function test_password_confirmation_field_with_custom_attribute(): void
    {
        $field = PasswordConfirmation::make('Confirm Password', 'confirm_password');

        $this->assertEquals('Confirm Password', $field->name);
        $this->assertEquals('confirm_password', $field->attribute);
    }

    public function test_password_confirmation_field_default_properties(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        $this->assertNull($field->minLength);
        $this->assertFalse($field->showStrengthIndicator);
    }

    public function test_password_confirmation_field_default_visibility(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        // Password confirmation fields should only be shown on forms by default
        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_password_confirmation_field_min_length_configuration(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->minLength(8);

        $this->assertEquals(8, $field->minLength);
        $this->assertEquals(['min:8'], $field->rules);
    }

    public function test_password_confirmation_field_show_strength_indicator(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->showStrengthIndicator();

        $this->assertTrue($field->showStrengthIndicator);
    }

    public function test_password_confirmation_field_show_strength_indicator_false(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->showStrengthIndicator(false);

        $this->assertFalse($field->showStrengthIndicator);
    }

    public function test_password_confirmation_field_resolve_always_returns_null(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        $resource = (object) ['password_confirmation' => 'secret123'];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_password_confirmation_field_fill_does_not_modify_model(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password_confirmation' => 'secret123']);

        $field->fill($request, $model);

        // Password confirmation fields should not modify the model
        $this->assertObjectNotHasProperty('password_confirmation', $model);
    }

    public function test_password_confirmation_field_fill_with_callback(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'callback-executed';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password_confirmation' => 'secret123']);

        $field->fill($request, $model);

        // If callback is executed, it should set the property
        // If not executed (which is expected for password confirmation), property won't exist
        $this->assertObjectNotHasProperty('password_confirmation', $model);
    }

    public function test_password_confirmation_field_meta_includes_all_properties(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->minLength(8)
            ->showStrengthIndicator();

        $meta = $field->meta();

        $this->assertArrayHasKey('minLength', $meta);
        $this->assertArrayHasKey('showStrengthIndicator', $meta);
        $this->assertEquals(8, $meta['minLength']);
        $this->assertTrue($meta['showStrengthIndicator']);
    }

    public function test_password_confirmation_field_json_serialization(): void
    {
        $field = PasswordConfirmation::make('Confirm Password')
            ->minLength(8)  // This sets rules to ['min:8']
            ->showStrengthIndicator()
            ->help('Re-enter your password');

        $json = $field->jsonSerialize();

        $this->assertEquals('Confirm Password', $json['name']);
        $this->assertEquals('confirm_password', $json['attribute']);
        $this->assertEquals('PasswordConfirmationField', $json['component']);
        $this->assertEquals(8, $json['minLength']);
        $this->assertTrue($json['showStrengthIndicator']);
        $this->assertEquals(['min:8'], $json['rules']);
        $this->assertEquals('Re-enter your password', $json['helpText']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    public function test_password_confirmation_field_can_override_visibility(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->showOnIndex()
            ->showOnDetail();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
    }

    public function test_password_confirmation_field_with_validation_rules(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->minLength(8);

        // Test that the field has the expected rules from the methods called
        $this->assertEquals(['min:8'], $field->rules);
    }

    public function test_password_confirmation_field_inheritance_from_field(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        // Test that PasswordConfirmation field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_password_confirmation_field_with_placeholder(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->placeholder('Confirm your password');

        $this->assertEquals('Confirm your password', $field->placeholder);
    }

    public function test_password_confirmation_field_with_help_text(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->help('Must match the password above');

        $this->assertEquals('Must match the password above', $field->helpText);
    }

    public function test_password_confirmation_field_with_readonly(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->readonly();

        $this->assertTrue($field->readonly);
    }

    public function test_password_confirmation_field_with_nullable(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_password_confirmation_field_purpose_is_validation_only(): void
    {
        // This test documents the intended behavior:
        // PasswordConfirmation fields are for validation only and don't store data
        $field = PasswordConfirmation::make('Password Confirmation');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password_confirmation' => 'secret123']);

        // Fill should not modify the model
        $field->fill($request, $model);
        $this->assertObjectNotHasProperty('password_confirmation', $model);

        // Resolve should always return null for security
        $resource = (object) ['password_confirmation' => 'secret123'];
        $field->resolve($resource);
        $this->assertNull($field->value);

        // Only shown on forms by default
        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }
}
