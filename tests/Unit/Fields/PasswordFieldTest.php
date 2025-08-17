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



    public function test_password_field_default_visibility(): void
    {
        $field = Password::make('Password');

        // Password fields should only be shown on forms by default
        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
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



    public function test_password_field_json_serialization(): void
    {
        $field = Password::make('User Password')
            ->help('Enter a secure password');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Password', $json['name']);
        $this->assertEquals('user_password', $json['attribute']);
        $this->assertEquals('PasswordField', $json['component']);
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
