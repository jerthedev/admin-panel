<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use JTD\AdminPanel\Fields\Gravatar;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Gravatar Field Integration Tests
 *
 * Tests that validate the integration between PHP Gravatar field class
 * and Vue component, including serialization, meta data, and Nova compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class GravatarFieldIntegrationTest extends TestCase
{
    /** @test */
    public function it_serializes_gravatar_field_for_frontend(): void
    {
        $field = Gravatar::make('Profile Avatar', 'user_email')
            ->squared()
            ->help('Gravatar based on email address');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Profile Avatar', $serialized['name']);
        $this->assertEquals('__gravatar_computed__', $serialized['attribute']); // Special attribute indicating computed field
        $this->assertEquals('GravatarField', $serialized['component']);
        $this->assertEquals('user_email', $serialized['emailColumn']);
        $this->assertTrue($serialized['squared']);
        $this->assertFalse($serialized['rounded']);
        $this->assertEquals('Gravatar based on email address', $serialized['helpText']);
    }

    /** @test */
    public function it_provides_correct_meta_data_for_vue_component(): void
    {
        $field = Gravatar::make('Avatar', 'email_address')->rounded();

        $meta = $field->meta();

        $this->assertArrayHasKey('emailColumn', $meta);
        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('rounded', $meta);
        $this->assertEquals('email_address', $meta['emailColumn']);
        $this->assertFalse($meta['squared']);
        $this->assertTrue($meta['rounded']);
    }

    /** @test */
    public function it_resolves_gravatar_url_from_model_data(): void
    {
        $field = Gravatar::make('Avatar');
        $model = (object) ['email' => 'test@example.com'];

        $field->resolve($model);

        $this->assertNotNull($field->value);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $field->value);
        $this->assertStringContains(md5('test@example.com'), $field->value);
    }

    /** @test */
    public function it_resolves_gravatar_url_from_custom_email_column(): void
    {
        $field = Gravatar::make('Avatar', 'work_email');
        $model = (object) ['work_email' => 'work@company.com'];

        $field->resolve($model);

        $this->assertNotNull($field->value);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $field->value);
        $this->assertStringContains(md5('work@company.com'), $field->value);
    }

    /** @test */
    public function it_handles_missing_email_gracefully(): void
    {
        $field = Gravatar::make('Avatar');
        $model = (object) ['name' => 'John Doe']; // No email field

        $field->resolve($model);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_does_not_fill_model_on_form_submission(): void
    {
        $field = Gravatar::make('Avatar');
        $model = new \stdClass();
        $request = new Request(['avatar' => 'some-value']);

        $field->fill($request, $model);

        // Gravatar fields don't correspond to database columns
        $this->assertObjectNotHasProperty('avatar', $model);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_gravatar_field(): void
    {
        // Test Nova's basic syntax: Gravatar::make()
        $field1 = Gravatar::make('Gravatar');
        $this->assertEquals('Gravatar', $field1->name);
        $this->assertEquals('email', $field1->emailColumn);
        $this->assertEquals('__gravatar_computed__', $field1->attribute);

        // Test Nova's syntax with custom email column: Gravatar::make('Avatar', 'email_address')
        $field2 = Gravatar::make('Avatar', 'email_address');
        $this->assertEquals('Avatar', $field2->name);
        $this->assertEquals('email_address', $field2->emailColumn);
        $this->assertEquals('__gravatar_computed__', $field2->attribute);

        // Test component name matches Nova
        $this->assertEquals('GravatarField', $field1->component);
        $this->assertEquals('GravatarField', $field2->component);
    }

    /** @test */
    public function it_supports_nova_display_methods(): void
    {
        $field = Gravatar::make('Avatar', 'email_address');

        // Test squared method
        $field->squared();
        $this->assertTrue($field->squared);
        $this->assertFalse($field->rounded);

        // Test rounded method
        $field->rounded();
        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared);
    }

    /** @test */
    public function it_generates_consistent_gravatar_urls(): void
    {
        $field1 = Gravatar::make('Avatar');
        $field2 = Gravatar::make('Profile Picture', 'user_email');

        $model1 = (object) ['email' => 'test@example.com'];
        $model2 = (object) ['user_email' => 'test@example.com'];

        $field1->resolve($model1);
        $field2->resolve($model2);

        // Both should generate the same URL for the same email
        $this->assertEquals($field1->value, $field2->value);
    }

    /** @test */
    public function it_handles_email_normalization(): void
    {
        $field = Gravatar::make('Avatar');

        $model1 = (object) ['email' => 'TEST@EXAMPLE.COM'];
        $model2 = (object) ['email' => 'test@example.com'];
        $model3 = (object) ['email' => '  test@example.com  '];

        $field->resolve($model1);
        $url1 = $field->value;

        $field->resolve($model2);
        $url2 = $field->value;

        $field->resolve($model3);
        $url3 = $field->value;

        // All should generate the same URL
        $this->assertEquals($url1, $url2);
        $this->assertEquals($url2, $url3);
    }

    /** @test */
    public function it_integrates_with_inertia_response_format(): void
    {
        $field = Gravatar::make('User Avatar', 'email')
            ->squared()
            ->help('Profile picture from Gravatar');

        $model = (object) ['email' => 'user@example.com'];
        $field->resolve($model);

        $serialized = $field->jsonSerialize();

        // Verify all data needed for Vue component is present
        $this->assertArrayHasKey('name', $serialized);
        $this->assertArrayHasKey('component', $serialized);
        $this->assertArrayHasKey('emailColumn', $serialized);
        $this->assertArrayHasKey('squared', $serialized);
        $this->assertArrayHasKey('rounded', $serialized);
        $this->assertArrayHasKey('value', $serialized);
        $this->assertArrayHasKey('helpText', $serialized);

        // Verify values are correct
        $this->assertEquals('User Avatar', $serialized['name']);
        $this->assertEquals('GravatarField', $serialized['component']);
        $this->assertEquals('email', $serialized['emailColumn']);
        $this->assertTrue($serialized['squared']);
        $this->assertFalse($serialized['rounded']);
        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $serialized['value']);
        $this->assertEquals('Profile picture from Gravatar', $serialized['helpText']);
    }
}
