<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Gravatar;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Gravatar Field Unit Tests
 *
 * Tests for Gravatar field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class GravatarFieldTest extends TestCase
{
    public function test_gravatar_field_creation(): void
    {
        $field = Gravatar::make('Avatar');

        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('avatar', $field->attribute);
        $this->assertEquals('GravatarField', $field->component);
    }

    public function test_gravatar_field_with_custom_attribute(): void
    {
        $field = Gravatar::make('Profile Picture', 'profile_picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
    }

    public function test_gravatar_field_default_properties(): void
    {
        $field = Gravatar::make('Avatar');

        $this->assertNull($field->emailAttribute);
        $this->assertEquals(80, $field->size);
        $this->assertEquals('mp', $field->defaultFallback);
        $this->assertEquals('g', $field->rating);
        $this->assertFalse($field->squared);
        $this->assertTrue($field->rounded); // Set in constructor
    }

    public function test_gravatar_field_from_email_configuration(): void
    {
        $field = Gravatar::make('Avatar')->fromEmail('user_email');

        $this->assertEquals('user_email', $field->emailAttribute);
    }

    public function test_gravatar_field_size_configuration(): void
    {
        $field = Gravatar::make('Avatar')->size(120);

        $this->assertEquals(120, $field->size);
    }

    public function test_gravatar_field_size_limits(): void
    {
        // Test minimum size limit
        $field = Gravatar::make('Avatar')->size(0);
        $this->assertEquals(1, $field->size);

        // Test maximum size limit
        $field = Gravatar::make('Avatar')->size(3000);
        $this->assertEquals(2048, $field->size);

        // Test valid size
        $field = Gravatar::make('Avatar')->size(200);
        $this->assertEquals(200, $field->size);
    }

    public function test_gravatar_field_default_image_configuration(): void
    {
        $field = Gravatar::make('Avatar')->defaultImage('identicon');

        $this->assertEquals('identicon', $field->defaultFallback);
    }

    public function test_gravatar_field_rating_configuration(): void
    {
        $field = Gravatar::make('Avatar')->rating('pg');

        $this->assertEquals('pg', $field->rating);
    }

    public function test_gravatar_field_squared_configuration(): void
    {
        $field = Gravatar::make('Avatar')->squared();

        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded); // Should disable rounded when squared
    }

    public function test_gravatar_field_squared_false(): void
    {
        $field = Gravatar::make('Avatar')->squared(false);

        $this->assertFalse($field->squared);
    }

    public function test_gravatar_field_rounded_configuration(): void
    {
        $field = Gravatar::make('Avatar')->rounded();

        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared); // Should disable squared when rounded
    }

    public function test_gravatar_field_rounded_false(): void
    {
        $field = Gravatar::make('Avatar')->rounded(false);

        $this->assertFalse($field->rounded);
    }

    public function test_gravatar_field_generate_gravatar_url(): void
    {
        $field = Gravatar::make('Avatar')
            ->size(100)
            ->defaultImage('identicon')
            ->rating('pg');

        $url = $field->generateGravatarUrl('test@example.com');

        $expectedHash = md5('test@example.com');
        $this->assertStringContainsString($expectedHash, $url);
        $this->assertStringContainsString('s=100', $url);
        $this->assertStringContainsString('d=identicon', $url);
        $this->assertStringContainsString('r=pg', $url);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $url);
    }

    public function test_gravatar_field_generate_gravatar_url_normalizes_email(): void
    {
        $field = Gravatar::make('Avatar');

        $url1 = $field->generateGravatarUrl('TEST@EXAMPLE.COM');
        $url2 = $field->generateGravatarUrl('test@example.com');
        $url3 = $field->generateGravatarUrl('  test@example.com  ');

        // All should generate the same hash
        $this->assertEquals($url1, $url2);
        $this->assertEquals($url2, $url3);
    }

    public function test_gravatar_field_resolve_with_email_attribute(): void
    {
        $field = Gravatar::make('Avatar')
            ->fromEmail('email')
            ->size(150);

        $resource = (object) ['email' => 'user@example.com'];

        $field->resolve($resource);

        $expectedHash = md5('user@example.com');
        $this->assertStringContainsString($expectedHash, $field->value);
        $this->assertStringContainsString('s=150', $field->value);
    }

    public function test_gravatar_field_resolve_without_email_attribute(): void
    {
        $field = Gravatar::make('Avatar');
        $resource = (object) ['avatar' => 'some-value'];

        $field->resolve($resource);

        $this->assertEquals('some-value', $field->value);
    }

    public function test_gravatar_field_resolve_with_empty_email(): void
    {
        $field = Gravatar::make('Avatar')->fromEmail('email');
        $resource = (object) ['email' => ''];

        $field->resolve($resource);

        $this->assertEquals('', $field->value);
    }

    public function test_gravatar_field_resolve_with_null_email(): void
    {
        $field = Gravatar::make('Avatar')->fromEmail('email');
        $resource = (object) ['email' => null];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_gravatar_field_fill_does_not_modify_model(): void
    {
        $field = Gravatar::make('Avatar');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['avatar' => 'some-value']);

        $field->fill($request, $model);

        // Gravatar fields don't fill the model directly
        $this->assertObjectNotHasProperty('avatar', $model);
    }

    public function test_gravatar_field_fill_with_callback(): void
    {
        $callbackExecuted = false;
        $field = Gravatar::make('Avatar')->fillUsing(function ($request, $model, $attribute) use (&$callbackExecuted) {
            $callbackExecuted = true;
            $model->{$attribute} = 'custom-value';
        });

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['avatar' => 'some-value']);

        $field->fill($request, $model);

        $this->assertTrue($callbackExecuted);
        $this->assertEquals('custom-value', $model->avatar);
    }

    public function test_gravatar_field_meta_includes_all_properties(): void
    {
        $field = Gravatar::make('Avatar')
            ->fromEmail('user_email')
            ->size(200)
            ->defaultImage('retro')
            ->rating('r')
            ->squared();

        $meta = $field->meta();

        $this->assertArrayHasKey('emailAttribute', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('defaultFallback', $meta);
        $this->assertArrayHasKey('rating', $meta);
        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('rounded', $meta);
        $this->assertEquals('user_email', $meta['emailAttribute']);
        $this->assertEquals(200, $meta['size']);
        $this->assertEquals('retro', $meta['defaultFallback']);
        $this->assertEquals('r', $meta['rating']);
        $this->assertTrue($meta['squared']);
        $this->assertFalse($meta['rounded']);
    }

    public function test_gravatar_field_json_serialization(): void
    {
        $field = Gravatar::make('User Avatar')
            ->fromEmail('email')
            ->size(120)
            ->defaultImage('wavatar')
            ->rating('pg')
            ->rounded()
            ->help('Gravatar based on email address');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Avatar', $json['name']);
        $this->assertEquals('user_avatar', $json['attribute']);
        $this->assertEquals('GravatarField', $json['component']);
        $this->assertEquals('email', $json['emailAttribute']);
        $this->assertEquals(120, $json['size']);
        $this->assertEquals('wavatar', $json['defaultFallback']);
        $this->assertEquals('pg', $json['rating']);
        $this->assertFalse($json['squared']);
        $this->assertTrue($json['rounded']);
        $this->assertEquals('Gravatar based on email address', $json['helpText']);
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

    public function test_gravatar_field_complex_configuration(): void
    {
        $field = Gravatar::make('Team Member Gravatar')
            ->fromEmail('work_email')
            ->size(256)
            ->defaultImage('robohash')
            ->rating('x')
            ->squared();

        // Test all configurations are set
        $this->assertEquals('work_email', $field->emailAttribute);
        $this->assertEquals(256, $field->size);
        $this->assertEquals('robohash', $field->defaultFallback);
        $this->assertEquals('x', $field->rating);
        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);

        // Test URL generation with all options
        $url = $field->generateGravatarUrl('team@company.com');
        $this->assertStringContainsString('s=256', $url);
        $this->assertStringContainsString('d=robohash', $url);
        $this->assertStringContainsString('r=x', $url);
    }
}
