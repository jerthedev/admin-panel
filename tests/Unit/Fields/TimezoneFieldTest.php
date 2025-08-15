<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Timezone;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Timezone Field Unit Tests
 *
 * Tests for Timezone field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TimezoneFieldTest extends TestCase
{
    public function test_timezone_field_creation(): void
    {
        $field = Timezone::make('Timezone');

        $this->assertEquals('Timezone', $field->name);
        $this->assertEquals('timezone', $field->attribute);
        $this->assertEquals('TimezoneField', $field->component);
    }

    public function test_timezone_field_with_custom_attribute(): void
    {
        $field = Timezone::make('User Timezone', 'user_timezone');

        $this->assertEquals('User Timezone', $field->name);
        $this->assertEquals('user_timezone', $field->attribute);
    }

    public function test_timezone_field_default_properties(): void
    {
        $field = Timezone::make('Timezone');

        $this->assertTrue($field->searchable);
        $this->assertFalse($field->groupByRegion);
        $this->assertFalse($field->onlyCommon);
    }

    public function test_timezone_field_searchable_configuration(): void
    {
        $field = Timezone::make('Timezone')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_timezone_field_searchable_default(): void
    {
        $field = Timezone::make('Timezone');

        $this->assertTrue($field->searchable);
    }

    public function test_timezone_field_group_by_region_configuration(): void
    {
        $field = Timezone::make('Timezone')->groupByRegion();

        $this->assertTrue($field->groupByRegion);
    }

    public function test_timezone_field_group_by_region_false(): void
    {
        $field = Timezone::make('Timezone')->groupByRegion(false);

        $this->assertFalse($field->groupByRegion);
    }

    public function test_timezone_field_only_common_configuration(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon();

        $this->assertTrue($field->onlyCommon);
    }

    public function test_timezone_field_only_common_false(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon(false);

        $this->assertFalse($field->onlyCommon);
    }

    public function test_timezone_field_get_timezones_all(): void
    {
        $field = Timezone::make('Timezone');

        $timezones = $field->getTimezones();

        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);
        $this->assertArrayHasKey('America/New_York', $timezones);
        $this->assertArrayHasKey('Europe/London', $timezones);
        $this->assertArrayHasKey('Asia/Tokyo', $timezones);
    }

    public function test_timezone_field_get_timezones_common_only(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon();

        $timezones = $field->getTimezones();

        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);

        // Should contain common timezones
        $this->assertArrayHasKey('America/New_York', $timezones);
        $this->assertArrayHasKey('Europe/London', $timezones);
        $this->assertArrayHasKey('Asia/Tokyo', $timezones);

        // Should be smaller than full list
        $allTimezones = Timezone::make('Timezone')->getTimezones();
        $this->assertLessThan(count($allTimezones), count($timezones));
    }

    public function test_timezone_field_get_timezones_excludes_deprecated(): void
    {
        $field = Timezone::make('Timezone');

        $timezones = $field->getTimezones();

        // Should not contain deprecated timezones (those without '/')
        foreach (array_keys($timezones) as $identifier) {
            $this->assertStringContainsString('/', $identifier, "Timezone {$identifier} should contain '/'");
        }
    }

    public function test_timezone_field_get_timezones_formats_names(): void
    {
        $field = Timezone::make('Timezone');

        $timezones = $field->getTimezones();

        // Test specific formatting examples
        $this->assertStringContainsString('New York', $timezones['America/New_York']);
        $this->assertStringContainsString('America', $timezones['America/New_York']);
        $this->assertStringContainsString('London', $timezones['Europe/London']);
        $this->assertStringContainsString('Europe', $timezones['Europe/London']);
    }

    public function test_timezone_field_fill_validates_timezone(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['timezone' => 'America/New_York']);

        $field->fill($request, $model);

        $this->assertEquals('America/New_York', $model->timezone);
    }

    public function test_timezone_field_fill_handles_empty_value(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['timezone' => '']);

        $field->fill($request, $model);

        $this->assertEquals('', $model->timezone);
    }

    public function test_timezone_field_fill_with_callback(): void
    {
        $field = Timezone::make('Timezone')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'UTC';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['timezone' => 'America/New_York']);

        $field->fill($request, $model);

        $this->assertEquals('UTC', $model->timezone);
    }

    public function test_timezone_field_meta_includes_all_properties(): void
    {
        $field = Timezone::make('Timezone')
            ->searchable(false)
            ->groupByRegion()
            ->onlyCommon();

        $meta = $field->meta();

        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('groupByRegion', $meta);
        $this->assertArrayHasKey('onlyCommon', $meta);
        $this->assertArrayHasKey('timezones', $meta);
        $this->assertFalse($meta['searchable']);
        $this->assertTrue($meta['groupByRegion']);
        $this->assertTrue($meta['onlyCommon']);
        $this->assertIsArray($meta['timezones']);
    }

    public function test_timezone_field_json_serialization(): void
    {
        $field = Timezone::make('User Timezone')
            ->searchable()
            ->groupByRegion()
            ->onlyCommon()
            ->required()
            ->help('Select your timezone');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Timezone', $json['name']);
        $this->assertEquals('user_timezone', $json['attribute']);
        $this->assertEquals('TimezoneField', $json['component']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['groupByRegion']);
        $this->assertTrue($json['onlyCommon']);
        $this->assertIsArray($json['timezones']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select your timezone', $json['helpText']);
    }

    public function test_timezone_field_common_timezones_functionality(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon();

        $commonTimezones = $field->getTimezones();

        // Test that common timezones are returned when onlyCommon is true
        $this->assertIsArray($commonTimezones);
        $this->assertArrayHasKey('UTC', $commonTimezones);
        $this->assertArrayHasKey('America/New_York', $commonTimezones);
        $this->assertArrayHasKey('Europe/London', $commonTimezones);
        $this->assertArrayHasKey('Asia/Tokyo', $commonTimezones);
    }

    public function test_timezone_field_inheritance_from_field(): void
    {
        $field = Timezone::make('Timezone');

        // Test that Timezone field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_timezone_field_with_validation_rules(): void
    {
        $field = Timezone::make('Timezone')
            ->rules('timezone');

        $this->assertEquals(['timezone'], $field->rules);
    }

    public function test_timezone_field_resolve_preserves_value(): void
    {
        $field = Timezone::make('Timezone');
        $resource = (object) ['timezone' => 'America/Los_Angeles'];

        $field->resolve($resource);

        $this->assertEquals('America/Los_Angeles', $field->value);
    }

    public function test_timezone_field_get_timezones_grouped(): void
    {
        $field = Timezone::make('Timezone');

        $groupedTimezones = $field->getTimezonesGrouped();

        $this->assertIsArray($groupedTimezones);
        $this->assertNotEmpty($groupedTimezones);

        // Should have regional groups
        $this->assertArrayHasKey('America', $groupedTimezones);
        $this->assertArrayHasKey('Europe', $groupedTimezones);
        $this->assertArrayHasKey('Asia', $groupedTimezones);

        // Each region should contain timezones
        $this->assertIsArray($groupedTimezones['America']);
        $this->assertArrayHasKey('America/New_York', $groupedTimezones['America']);
        $this->assertArrayHasKey('America/Los_Angeles', $groupedTimezones['America']);

        $this->assertIsArray($groupedTimezones['Europe']);
        $this->assertArrayHasKey('Europe/London', $groupedTimezones['Europe']);
        $this->assertArrayHasKey('Europe/Paris', $groupedTimezones['Europe']);

        // Test that regions are sorted
        $regions = array_keys($groupedTimezones);
        $sortedRegions = $regions;
        sort($sortedRegions);
        $this->assertEquals($sortedRegions, $regions);
    }

    public function test_timezone_field_get_timezones_grouped_with_common_only(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon();

        $groupedTimezones = $field->getTimezonesGrouped();

        $this->assertIsArray($groupedTimezones);
        $this->assertNotEmpty($groupedTimezones);

        // Should contain common timezone regions
        $this->assertArrayHasKey('America', $groupedTimezones);
        $this->assertArrayHasKey('Europe', $groupedTimezones);
        $this->assertArrayHasKey('Asia', $groupedTimezones);

        // Should be smaller than full grouped list
        $allGroupedTimezones = Timezone::make('Timezone')->getTimezonesGrouped();
        $this->assertLessThanOrEqual(count($allGroupedTimezones), count($groupedTimezones));
    }

    public function test_timezone_field_meta_uses_grouped_when_enabled(): void
    {
        $field = Timezone::make('Timezone')->groupByRegion();

        $meta = $field->meta();

        $this->assertArrayHasKey('timezones', $meta);
        $this->assertIsArray($meta['timezones']);
        $this->assertTrue($meta['groupByRegion']);

        // When groupByRegion is true, timezones should be grouped
        $this->assertArrayHasKey('America', $meta['timezones']);
        $this->assertArrayHasKey('Europe', $meta['timezones']);
    }

    public function test_timezone_field_comprehensive_method_coverage(): void
    {
        $field = Timezone::make('Timezone');

        // Test all public methods to ensure complete coverage
        $this->assertTrue(method_exists($field, 'searchable'));
        $this->assertTrue(method_exists($field, 'groupByRegion'));
        $this->assertTrue(method_exists($field, 'onlyCommon'));
        $this->assertTrue(method_exists($field, 'getTimezones'));
        $this->assertTrue(method_exists($field, 'getTimezonesGrouped'));
        $this->assertTrue(method_exists($field, 'meta'));

        // Call all methods to ensure they're covered
        $field->searchable();
        $field->groupByRegion();
        $field->onlyCommon();

        $timezones = $field->getTimezones();
        $this->assertIsArray($timezones);

        $groupedTimezones = $field->getTimezonesGrouped();
        $this->assertIsArray($groupedTimezones);

        $meta = $field->meta();
        $this->assertIsArray($meta);

        // Test method chaining
        $chainedField = Timezone::make('Timezone')
            ->searchable()
            ->groupByRegion()
            ->onlyCommon();

        $this->assertTrue($chainedField->searchable);
        $this->assertTrue($chainedField->groupByRegion);
        $this->assertTrue($chainedField->onlyCommon);
    }
}
