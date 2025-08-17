<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Gravatar;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Gravatar Field Unit Tests
 *
 * Tests for Nova-compatible Gravatar field class including validation, visibility,
 * and value handling. Tests 100% Nova API compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class GravatarFieldTest extends TestCase
{
    public function test_gravatar_field_creation_with_default_email_column(): void
    {
        $field = Gravatar::make('Avatar');

        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('__gravatar_computed__', $field->attribute); // Special attribute indicating computed field
        $this->assertEquals('GravatarField', $field->component);
        $this->assertEquals('email', $field->emailColumn); // Defaults to 'email' like Nova
    }

    public function test_gravatar_field_creation_with_custom_email_column(): void
    {
        $field = Gravatar::make('Avatar', 'email_address');

        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('__gravatar_computed__', $field->attribute); // Special attribute indicating computed field
        $this->assertEquals('email_address', $field->emailColumn);
    }

    public function test_gravatar_field_default_properties(): void
    {
        $field = Gravatar::make('Avatar');

        $this->assertEquals('email', $field->emailColumn);
        $this->assertFalse($field->squared);
        $this->assertTrue($field->rounded); // Rounded by default like Nova
    }

    public function test_gravatar_field_nova_api_compatibility(): void
    {
        // Test Nova's basic syntax: Gravatar::make()
        $field1 = Gravatar::make('Avatar');
        $this->assertEquals('Avatar', $field1->name);
        $this->assertEquals('email', $field1->emailColumn);

        // Test Nova's syntax with custom email column: Gravatar::make('Avatar', 'email_address')
        $field2 = Gravatar::make('Avatar', 'email_address');
        $this->assertEquals('Avatar', $field2->name);
        $this->assertEquals('email_address', $field2->emailColumn);
    }

    public function test_gravatar_field_squared_method(): void
    {
        $field = Gravatar::make('Avatar')->squared();

        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded); // Should disable rounded when squared
    }

    public function test_gravatar_field_rounded_method(): void
    {
        $field = Gravatar::make('Avatar')->squared()->rounded();

        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared); // Should disable squared when rounded
    }

    public function test_gravatar_field_generate_gravatar_url(): void
    {
        $field = Gravatar::make('Avatar');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('generateGravatarUrl');
        $method->setAccessible(true);

        $url = $method->invoke($field, 'test@example.com');

        $expectedHash = md5('test@example.com');
        $this->assertEquals("https://www.gravatar.com/avatar/{$expectedHash}", $url);
    }

    public function test_gravatar_field_resolve_with_email(): void
    {
        $field = Gravatar::make('Avatar');
        $resource = (object) ['email' => 'user@example.com'];

        $field->resolve($resource);

        $expectedHash = md5('user@example.com');
        $expectedUrl = "https://www.gravatar.com/avatar/{$expectedHash}";
        $this->assertEquals($expectedUrl, $field->value);
    }

    public function test_gravatar_field_resolve_with_custom_email_column(): void
    {
        $field = Gravatar::make('Avatar', 'user_email');
        $resource = (object) ['user_email' => 'custom@example.com'];

        $field->resolve($resource);

        $expectedHash = md5('custom@example.com');
        $expectedUrl = "https://www.gravatar.com/avatar/{$expectedHash}";
        $this->assertEquals($expectedUrl, $field->value);
    }

    public function test_gravatar_field_resolve_without_email(): void
    {
        $field = Gravatar::make('Avatar');
        $resource = (object) ['name' => 'John Doe']; // No email field

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_gravatar_field_resolve_with_empty_email(): void
    {
        $field = Gravatar::make('Avatar');
        $resource = (object) ['email' => ''];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_gravatar_field_generate_gravatar_url_normalizes_email(): void
    {
        $field = Gravatar::make('Avatar');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($field);
        $method = $reflection->getMethod('generateGravatarUrl');
        $method->setAccessible(true);

        $url1 = $method->invoke($field, 'TEST@EXAMPLE.COM');
        $url2 = $method->invoke($field, 'test@example.com');
        $url3 = $method->invoke($field, '  test@example.com  ');

        // All should generate the same hash
        $this->assertEquals($url1, $url2);
        $this->assertEquals($url2, $url3);
    }

    public function test_gravatar_field_fill_does_not_modify_model(): void
    {
        $field = Gravatar::make('Avatar');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['avatar' => 'some-value']);

        $field->fill($request, $model);

        // Gravatar fields don't fill the model - they don't correspond to database columns
        $this->assertObjectNotHasProperty('avatar', $model);
    }

    public function test_gravatar_field_meta_includes_properties(): void
    {
        $field = Gravatar::make('Avatar', 'user_email')->squared();

        $meta = $field->meta();

        $this->assertEquals('user_email', $meta['emailColumn']);
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']);
    }

    public function test_gravatar_field_inheritance_from_field(): void
    {
        $field = Gravatar::make('Avatar');

        // Test that Gravatar field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_gravatar_field_nova_examples(): void
    {
        // Test Nova documentation examples exactly

        // Using the "email" column...
        $field1 = Gravatar::make('Gravatar');
        $this->assertEquals('Gravatar', $field1->name);
        $this->assertEquals('email', $field1->emailColumn);

        // Using the "email_address" column...
        $field2 = Gravatar::make('Avatar', 'email_address');
        $this->assertEquals('Avatar', $field2->name);
        $this->assertEquals('email_address', $field2->emailColumn);

        // Test squared method
        $field3 = Gravatar::make('Avatar', 'email_address')->squared();
        $this->assertTrue($field3->squared);
        $this->assertFalse($field3->rounded);
    }
}
