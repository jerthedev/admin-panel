<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Color;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class ColorFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_color_field_with_nova_syntax(): void
    {
        $field = Color::make('Color');

        $this->assertEquals('Color', $field->name);
        $this->assertEquals('color', $field->attribute);
        $this->assertEquals('ColorField', $field->component);
    }

    /** @test */
    public function it_resolves_and_fills_values(): void
    {
        $user = User::factory()->create(['color' => '#123abc']);

        $field = Color::make('Color', 'color');
        $field->resolve($user);
        $this->assertEquals('#123abc', $field->value);

        $request = new Request(['color' => '#abcdef']);
        $field->fill($request, $user);
        $this->assertEquals('#abcdef', $user->color);
    }

    /** @test */
    public function it_serializes_for_frontend_without_extra_meta(): void
    {
        $field = Color::make('Brand Color')->help('Pick a color')->rules('required');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Brand Color', $serialized['name']);
        $this->assertEquals('brand_color', $serialized['attribute']);
        $this->assertEquals('ColorField', $serialized['component']);
        $this->assertEquals('Pick a color', $serialized['helpText']);
        $this->assertContains('required', $serialized['rules']);

        $this->assertArrayNotHasKey('format', $serialized);
        $this->assertArrayNotHasKey('withAlpha', $serialized);
        $this->assertArrayNotHasKey('palette', $serialized);
        $this->assertArrayNotHasKey('showPreview', $serialized);
        $this->assertArrayNotHasKey('swatches', $serialized);
    }
}

