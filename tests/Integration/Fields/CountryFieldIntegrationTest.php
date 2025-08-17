<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Country as CountryField;
use JTD\AdminPanel\Tests\Fixtures\Country;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Country Field Integration Test
 *
 * Validates PHP/Inertia/Vue interoperability for Country field meta payload
 * and basic hydration behavior following Nova API compatibility.
 */
class CountryFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed a couple of countries and users
        Country::factory()->create(['id' => 1, 'name' => 'United States', 'code' => 'US']);
        Country::factory()->create(['id' => 2, 'name' => 'Canada', 'code' => 'CA']);

        User::factory()->create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'country_id' => 1]);
        User::factory()->create(['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'country_id' => 2]);
        User::factory()->create(['id' => 3, 'name' => 'Bob', 'email' => 'bob@example.com', 'country_id' => null]);
    }

    /** @test */
    public function it_creates_country_field_with_nova_syntax(): void
    {
        $field = CountryField::make('Country Code', 'country_code');

        $this->assertEquals('Country Code', $field->name);
        $this->assertEquals('country_code', $field->attribute);
        $this->assertEquals('CountryField', $field->component);

        $meta = $field->meta();
        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertEquals('United States', $meta['options']['US']);
    }

    /** @test */
    public function it_can_be_made_searchable(): void
    {
        $field = CountryField::make('Country')->searchable();
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->meta()['searchable']);
    }

    /** @test */
    public function it_resolves_value_from_model_attribute(): void
    {
        // Use a simple model attribute country_code separate from relation
        $user = new User(['name' => 'Test', 'email' => 't@example.com']);
        $user->setRawAttributes(['id' => 10, 'country_code' => 'US']);

        $field = CountryField::make('Country', 'country_code');
        $field->resolve($user);

        $this->assertEquals('US', $field->value);
    }

    /** @test */
    public function it_fills_model_from_request(): void
    {
        $user = new User;
        $request = new Request(['country_code' => 'CA']);

        $field = CountryField::make('Country', 'country_code');
        $field->fill($request, $user);

        $this->assertEquals('CA', $user->country_code);
    }

    /** @test */
    public function it_serializes_for_frontend_correctly(): void
    {
        $field = CountryField::make('Country', 'country_code')->searchable()->help('Select country');

        $json = $field->jsonSerialize();

        $this->assertEquals('Country', $json['name']);
        $this->assertEquals('country_code', $json['attribute']);
        $this->assertEquals('CountryField', $json['component']);
        $this->assertTrue($json['searchable']);
        $this->assertEquals('Select country', $json['helpText']);
        $this->assertArrayHasKey('options', $json);
        $this->assertEquals('United States', $json['options']['US']);
    }
}
