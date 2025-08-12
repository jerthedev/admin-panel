<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Menu\Badge;
use JTD\AdminPanel\Tests\TestCase;

class BadgeTest extends TestCase
{
    public function test_badge_can_be_created_with_make(): void
    {
        $badge = Badge::make('New!', 'info');

        $this->assertEquals('New!', $badge->value);
        $this->assertEquals('info', $badge->type);
    }

    public function test_badge_defaults_to_primary_type(): void
    {
        $badge = Badge::make('5');

        $this->assertEquals('5', $badge->value);
        $this->assertEquals('primary', $badge->type);
    }

    public function test_badge_can_be_created_with_constructor(): void
    {
        $badge = new Badge('Warning!', 'warning');

        $this->assertEquals('Warning!', $badge->value);
        $this->assertEquals('warning', $badge->type);
    }

    public function test_badge_can_change_type(): void
    {
        $badge = Badge::make('10')
            ->type('success');

        $this->assertEquals('success', $badge->type);
    }

    public function test_badge_supports_fluent_chaining(): void
    {
        $badge = Badge::make('Alert')
            ->type('danger')
            ->type('warning'); // Should override

        $this->assertEquals('Alert', $badge->value);
        $this->assertEquals('warning', $badge->type);
    }

    public function test_badge_is_json_serializable(): void
    {
        $badge = Badge::make('Test', 'info');

        $json = $badge->jsonSerialize();

        $this->assertEquals([
            'value' => 'Test',
            'type' => 'info',
        ], $json);
    }

    public function test_badge_to_array(): void
    {
        $badge = Badge::make('Count', 'success');

        $array = $badge->toArray();

        $this->assertEquals([
            'value' => 'Count',
            'type' => 'success',
        ], $array);
    }

    public function test_badge_to_string(): void
    {
        $badge = Badge::make('Badge Text');

        $this->assertEquals('Badge Text', (string) $badge);
    }

    public function test_badge_with_numeric_value(): void
    {
        $badge = Badge::make(42, 'primary');

        $this->assertEquals(42, $badge->value);
        $this->assertEquals('primary', $badge->type);
    }

    public function test_badge_with_closure_value(): void
    {
        $badge = Badge::make(fn() => 'Dynamic Value', 'info');

        $this->assertInstanceOf(\Closure::class, $badge->value);
        $this->assertEquals('info', $badge->type);
    }

    public function test_badge_resolve_closure_value(): void
    {
        $badge = Badge::make(fn() => 'Resolved', 'success');

        $resolved = $badge->resolve();

        $this->assertEquals('Resolved', $resolved);
    }

    public function test_badge_resolve_static_value(): void
    {
        $badge = Badge::make('Static', 'primary');

        $resolved = $badge->resolve();

        $this->assertEquals('Static', $resolved);
    }

    public function test_badge_supports_all_types(): void
    {
        $types = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach ($types as $type) {
            $badge = Badge::make('Test', $type);
            $this->assertEquals($type, $badge->type);
        }
    }
}
