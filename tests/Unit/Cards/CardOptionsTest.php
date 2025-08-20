<?php

declare(strict_types=1);

namespace Tests\Unit\Cards;

use InvalidArgumentException;
use JTD\AdminPanel\Cards\Card;
use PHPUnit\Framework\TestCase;

/**
 * Test Card Options (withMeta) System.
 *
 * Tests the enhanced withMeta() functionality including fluent color/theme methods,
 * meta data validation, advanced theming options, and label configuration.
 */
class CardOptionsTest extends TestCase
{
    private TestCard $card;

    protected function setUp(): void
    {
        $this->card = new TestCard;
    }

    public function test_with_color_sets_color_meta(): void
    {
        $result = $this->card->withColor('primary');

        $this->assertSame($this->card, $result);
        $this->assertEquals('primary', $this->card->meta()['color']);
    }

    public function test_with_background_color_sets_background_color_meta(): void
    {
        $result = $this->card->withBackgroundColor('#3B82F6');

        $this->assertSame($this->card, $result);
        $this->assertEquals('#3B82F6', $this->card->meta()['backgroundColor']);
    }

    public function test_with_text_color_sets_text_color_meta(): void
    {
        $result = $this->card->withTextColor('blue-600');

        $this->assertSame($this->card, $result);
        $this->assertEquals('blue-600', $this->card->meta()['textColor']);
    }

    public function test_with_border_color_sets_border_color_meta(): void
    {
        $result = $this->card->withBorderColor('red-500');

        $this->assertSame($this->card, $result);
        $this->assertEquals('red-500', $this->card->meta()['borderColor']);
    }

    public function test_with_icon_sets_icon_meta(): void
    {
        $result = $this->card->withIcon('ChartBarIcon');

        $this->assertSame($this->card, $result);
        $this->assertEquals('ChartBarIcon', $this->card->meta()['icon']);
    }

    public function test_with_title_sets_title_meta(): void
    {
        $result = $this->card->withTitle('Dashboard Stats');

        $this->assertSame($this->card, $result);
        $this->assertEquals('Dashboard Stats', $this->card->meta()['title']);
    }

    public function test_with_subtitle_sets_subtitle_meta(): void
    {
        $result = $this->card->withSubtitle('Key metrics overview');

        $this->assertSame($this->card, $result);
        $this->assertEquals('Key metrics overview', $this->card->meta()['subtitle']);
    }

    public function test_with_description_sets_description_meta(): void
    {
        $result = $this->card->withDescription('Detailed statistics');

        $this->assertSame($this->card, $result);
        $this->assertEquals('Detailed statistics', $this->card->meta()['description']);
    }

    public function test_with_labels_sets_labels_meta(): void
    {
        $labels = ['status' => 'Active', 'priority' => 'High'];
        $result = $this->card->withLabels($labels);

        $this->assertSame($this->card, $result);
        $this->assertEquals($labels, $this->card->meta()['labels']);
    }

    public function test_refreshable_sets_refreshable_meta(): void
    {
        $result = $this->card->refreshable();

        $this->assertSame($this->card, $result);
        $this->assertTrue($this->card->meta()['refreshable']);

        $this->card->refreshable(false);
        $this->assertFalse($this->card->meta()['refreshable']);
    }

    public function test_refresh_every_sets_refresh_interval_meta(): void
    {
        $result = $this->card->refreshEvery(30);

        $this->assertSame($this->card, $result);
        $this->assertEquals(30, $this->card->meta()['refreshInterval']);
    }

    public function test_with_classes_sets_classes_meta(): void
    {
        $classes = ['custom-card', 'highlighted'];
        $result = $this->card->withClasses($classes);

        $this->assertSame($this->card, $result);
        $this->assertEquals($classes, $this->card->meta()['classes']);
    }

    public function test_with_styles_sets_styles_meta(): void
    {
        $styles = ['backgroundColor' => '#f0f0f0', 'borderRadius' => '8px'];
        $result = $this->card->withStyles($styles);

        $this->assertSame($this->card, $result);
        $this->assertEquals($styles, $this->card->meta()['styles']);
    }

    public function test_with_variant_sets_variant_meta(): void
    {
        $result = $this->card->withVariant('elevated');

        $this->assertSame($this->card, $result);
        $this->assertEquals('elevated', $this->card->meta()['variant']);
    }

    public function test_with_size_sets_size_meta(): void
    {
        $result = $this->card->withSize('lg');

        $this->assertSame($this->card, $result);
        $this->assertEquals('lg', $this->card->meta()['size']);
    }

    public function test_fluent_interface_chaining(): void
    {
        $result = $this->card
            ->withColor('primary')
            ->withTitle('Test Card')
            ->withIcon('ChartIcon')
            ->refreshable()
            ->withVariant('elevated');

        $this->assertSame($this->card, $result);

        $meta = $this->card->meta();
        $this->assertEquals('primary', $meta['color']);
        $this->assertEquals('Test Card', $meta['title']);
        $this->assertEquals('ChartIcon', $meta['icon']);
        $this->assertTrue($meta['refreshable']);
        $this->assertEquals('elevated', $meta['variant']);
    }

    public function test_color_validation_accepts_valid_hex_colors(): void
    {
        $this->card->withColor('#FF0000');
        $this->assertEquals('#FF0000', $this->card->meta()['color']);

        $this->card->withColor('#f00');
        $this->assertEquals('#f00', $this->card->meta()['color']);
    }

    public function test_color_validation_accepts_valid_tailwind_colors(): void
    {
        $this->card->withColor('blue-500');
        $this->assertEquals('blue-500', $this->card->meta()['color']);

        $this->card->withColor('red-100');
        $this->assertEquals('red-100', $this->card->meta()['color']);
    }

    public function test_color_validation_accepts_theme_colors(): void
    {
        $themeColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];

        foreach ($themeColors as $color) {
            $this->card->withColor($color);
            $this->assertEquals($color, $this->card->meta()['color']);
        }
    }

    public function test_color_validation_rejects_invalid_colors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid color format: invalid-color');

        $this->card->withColor('invalid-color');
    }

    public function test_refresh_interval_validation_accepts_valid_intervals(): void
    {
        $this->card->refreshEvery(1);
        $this->assertEquals(1, $this->card->meta()['refreshInterval']);

        $this->card->refreshEvery(3600);
        $this->assertEquals(3600, $this->card->meta()['refreshInterval']);
    }

    public function test_refresh_interval_validation_rejects_invalid_intervals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh interval must be between 1 and 3600 seconds');

        $this->card->refreshEvery(0);
    }

    public function test_variant_validation_rejects_invalid_variants(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid variant 'invalid'. Must be one of: default, bordered, elevated, flat, gradient");

        $this->card->withVariant('invalid');
    }

    public function test_size_validation_rejects_invalid_sizes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid size 'invalid'. Must be one of: sm, md, lg, xl, full");

        $this->card->withSize('invalid');
    }

    public function test_labels_validation_rejects_invalid_labels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Labels must be an associative array of strings');

        $this->card->withLabels(['invalid', 123]);
    }

    public function test_styles_validation_rejects_invalid_css_properties(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CSS property: invalidProperty');

        $this->card->withStyles(['invalidProperty' => 'value']);
    }

    public function test_meta_validation_preserves_valid_data(): void
    {
        $validMeta = [
            'color' => 'primary',
            'refreshInterval' => 30,
            'variant' => 'elevated',
            'customData' => ['key' => 'value'],
        ];

        $this->card->withMeta($validMeta);

        $meta = $this->card->meta();
        $this->assertEquals('primary', $meta['color']);
        $this->assertEquals(30, $meta['refreshInterval']);
        $this->assertEquals('elevated', $meta['variant']);
        $this->assertEquals(['key' => 'value'], $meta['customData']);
    }

    public function test_json_serialization_includes_enhanced_meta(): void
    {
        $this->card
            ->withColor('primary')
            ->withTitle('Enhanced Card')
            ->withVariant('elevated')
            ->refreshable();

        $serialized = $this->card->jsonSerialize();

        $this->assertArrayHasKey('meta', $serialized);
        $this->assertEquals('primary', $serialized['meta']['color']);
        $this->assertEquals('Enhanced Card', $serialized['meta']['title']);
        $this->assertEquals('elevated', $serialized['meta']['variant']);
        $this->assertTrue($serialized['meta']['refreshable']);
    }
}

/**
 * Test Card implementation for testing.
 */
class TestCard extends Card
{
    public string $name = 'Test Card';

    public string $component = 'TestCard';

    public string $uriKey = 'test-card';
}
