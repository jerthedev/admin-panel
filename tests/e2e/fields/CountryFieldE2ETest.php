<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Country as CountryField;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Country Field E2E Test
 *
 * Exercises end-to-end field behavior without UI (Playwright covers UI).
 */
class CountryFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed users for test
        User::factory()->create(['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com']);
    }

    /** @test */
    public function it_serializes_and_fills_country_field_end_to_end(): void
    {
        $field = CountryField::make('Country', 'country_code')->searchable()->help('Select your country');

        // Serialize
        $json = $field->jsonSerialize();
        $this->assertEquals('CountryField', $json['component']);
        $this->assertEquals('country_code', $json['attribute']);
        $this->assertTrue($json['searchable']);
        $this->assertArrayHasKey('options', $json);
        $this->assertEquals('United States', $json['options']['US']);

        // Fill into model
        $user = new User(['name' => 'Bob', 'email' => 'bob@example.com']);
        $request = new Request(['country_code' => 'GB']);
        $field->fill($request, $user);

        $this->assertEquals('GB', $user->country_code);
    }
}
