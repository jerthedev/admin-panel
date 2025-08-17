<?php

declare(strict_types=1);

namespace E2E\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Color;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class ColorFieldE2ETest extends TestCase
{
    /** @test */
    public function it_serializes_and_fills_like_nova_in_end_to_end_flow(): void
    {
        // Simulate backend field creation
        $field = Color::make('Color', 'color')->help('Pick a color');
        $serialized = $field->jsonSerialize();

        $this->assertEquals('ColorField', $serialized['component']);
        $this->assertEquals('Pick a color', $serialized['helpText']);
        $this->assertArrayNotHasKey('format', $serialized);

        // Simulate a client update
        $request = new Request(['color' => '#abcdef']);
        $user = new User();
        $field->fill($request, $user);

        $this->assertEquals('#abcdef', $user->color);
    }
}

