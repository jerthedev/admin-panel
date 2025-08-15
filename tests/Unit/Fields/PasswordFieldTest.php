<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Password Field Unit Tests
 *
 * Tests for Password field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PasswordFieldTest extends TestCase
{
    public function test_password_field_creation(): void
    {
        $field = Password::make('Password');

        $this->assertEquals('Password', $field->name);
        $this->assertEquals('password', $field->attribute);
        $this->assertEquals('PasswordField', $field->component);
    }

    public function test_password_field_with_custom_attribute(): void
    {
        $field = Password::make('User Password', 'user_password');

        $this->assertEquals('User Password', $field->name);
        $this->assertEquals('user_password', $field->attribute);
    }

    public function test_password_field_default_properties(): void
    {
        $field = Password::make('Password');

        $this->assertFalse($field->requireConfirmation);
        $this->assertNull($field->minLength);
        $this->assertFalse($field->showStrengthIndicator);
    }

    public function test_password_field_default_visibility(): void
    {
        $field = Password::make('Password');

        // Password fields should only be shown on forms by default
        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_password_field_confirmation_configuration(): void
    {
        $field = Password::make('Password')->confirmation();

        $this->assertTrue($field->requireConfirmation);
        $this->assertEquals(['confirmed'], $field->rules);
    }

    public function test_password_field_confirmation_false(): void
    {
        $field = Password::make('Password')->confirmation(false);

        $this->assertFalse($field->requireConfirmation);
    }

    public function test_password_field_min_length_configuration(): void
    {
        $field = Password::make('Password')->minLength(8);

        $this->assertEquals(8, $field->minLength);
        $this->assertEquals(['min:8'], $field->rules);
    }

    public function test_password_field_show_strength_indicator(): void
    {
        $field = Password::make('Password')->showStrengthIndicator();

        $this->assertTrue($field->showStrengthIndicator);
    }

    public function test_password_field_show_strength_indicator_false(): void
    {
        $field = Password::make('Password')->showStrengthIndicator(false);

        $this->assertFalse($field->showStrengthIndicator);
    }

    public function test_password_field_resolve_always_returns_null(): void
    {
        $field = Password::make('Password');
        $resource = (object) ['password' => 'secret123'];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_password_field_fill_hashes_password(): void
    {
        $field = Password::make('Password');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password' => 'secret123']);

        $field->fill($request, $model);

        $this->assertTrue(Hash::check('secret123', $model->password));
    }

    public function test_password_field_fill_ignores_empty_value(): void
    {
        $field = Password::make('Password');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password' => '']);

        $field->fill($request, $model);

        $this->assertObjectNotHasProperty('password', $model);
    }

    public function test_password_field_fill_ignores_null_value(): void
    {
        $field = Password::make('Password');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password' => null]);

        $field->fill($request, $model);

        $this->assertObjectNotHasProperty('password', $model);
    }

    public function test_password_field_fill_with_callback(): void
    {
        $field = Password::make('Password')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'custom-hash';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password' => 'secret123']);

        $field->fill($request, $model);

        $this->assertEquals('custom-hash', $model->password);
    }

    public function test_password_field_meta_includes_all_properties(): void
    {
        $field = Password::make('Password')
            ->confirmation()
            ->minLength(8)
            ->showStrengthIndicator();

        $meta = $field->meta();

        $this->assertArrayHasKey('requireConfirmation', $meta);
        $this->assertArrayHasKey('minLength', $meta);
        $this->assertArrayHasKey('showStrengthIndicator', $meta);
        $this->assertTrue($meta['requireConfirmation']);
        $this->assertEquals(8, $meta['minLength']);
        $this->assertTrue($meta['showStrengthIndicator']);
    }

    public function test_password_field_json_serialization(): void
    {
        $field = Password::make('User Password')
            ->minLength(8)  // This will set rules to ['min:8']
            ->showStrengthIndicator()
            ->help('Enter a secure password');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Password', $json['name']);
        $this->assertEquals('user_password', $json['attribute']);
        $this->assertEquals('PasswordField', $json['component']);
        $this->assertEquals(8, $json['minLength']);
        $this->assertTrue($json['showStrengthIndicator']);
        $this->assertEquals(['min:8'], $json['rules']);
        $this->assertEquals('Enter a secure password', $json['helpText']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    public function test_password_field_can_override_visibility(): void
    {
        $field = Password::make('Password')
            ->showOnIndex()
            ->showOnDetail();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
    }

    public function test_password_field_with_chained_configuration(): void
    {
        // When methods are chained, the last one that calls rules() wins
        $field = Password::make('Password')
            ->confirmation()  // Sets rules to ['confirmed']
            ->minLength(8);   // Overwrites rules to ['min:8']

        // The final rules should be from the last method called
        $this->assertEquals(['min:8'], $field->rules);
        $this->assertTrue($field->requireConfirmation);
        $this->assertEquals(8, $field->minLength);
    }

    public function test_password_field_inheritance_from_field(): void
    {
        $field = Password::make('Password');

        // Test that Password field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_password_field_with_placeholder(): void
    {
        $field = Password::make('Password')->placeholder('Enter your password');

        $this->assertEquals('Enter your password', $field->placeholder);
    }

    public function test_password_field_with_help_text(): void
    {
        $field = Password::make('Password')->help('Password must be at least 8 characters');

        $this->assertEquals('Password must be at least 8 characters', $field->helpText);
    }
}
