<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\Heading;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Heading Field E2E Test
 *
 * Tests the complete end-to-end functionality of Heading fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior rather than
 * web interface testing (which is handled by Playwright tests).
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HeadingFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users for form context
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => true,
            'is_active' => true,
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_handles_simple_text_heading_in_form_context(): void
    {
        $field = Heading::make('User Information');
        $user = User::find(1);

        // Resolve field for display
        $field->resolveForDisplay($user);

        $this->assertEquals('User Information', $field->value);
        $this->assertEquals('user_information', $field->attribute);
        $this->assertEquals('HeadingField', $field->component);
        $this->assertFalse($field->asHtml);
        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_handles_html_heading_in_form_context(): void
    {
        $htmlContent = '<div class="bg-blue-50 border-l-4 border-blue-400 p-4"><h3 class="text-lg font-medium text-blue-900">Important Information</h3><p class="text-blue-700">Please review all fields carefully before submitting.</p></div>';
        
        $field = Heading::make($htmlContent)->asHtml();
        $user = User::find(1);

        // Resolve field for display
        $field->resolveForDisplay($user);

        $this->assertEquals($htmlContent, $field->value);
        $this->assertTrue($field->asHtml);
        $this->assertEquals('HeadingField', $field->component);
    }

    /** @test */
    public function it_handles_complex_form_scenario_with_multiple_headings(): void
    {
        // Create a realistic form scenario with multiple heading fields
        $personalInfoHeading = Heading::make('Personal Information');
        $accountSettingsHeading = Heading::make('<h2 class="text-xl font-semibold text-gray-900">Account Settings</h2>')->asHtml();
        $dangerZoneHeading = Heading::make('<div class="bg-red-50 border border-red-200 rounded-md p-4"><h3 class="text-lg font-medium text-red-900">Danger Zone</h3><p class="text-red-700">Actions in this section cannot be undone.</p></div>')->asHtml();

        $user = User::find(1);

        // Resolve all headings
        $personalInfoHeading->resolveForDisplay($user);
        $accountSettingsHeading->resolveForDisplay($user);
        $dangerZoneHeading->resolveForDisplay($user);

        // Verify personal info heading
        $this->assertEquals('Personal Information', $personalInfoHeading->value);
        $this->assertFalse($personalInfoHeading->asHtml);
        $this->assertFalse($personalInfoHeading->showOnIndex);

        // Verify account settings heading
        $this->assertStringContains('<h2 class="text-xl font-semibold text-gray-900">Account Settings</h2>', $accountSettingsHeading->value);
        $this->assertTrue($accountSettingsHeading->asHtml);

        // Verify danger zone heading
        $this->assertStringContains('bg-red-50', $dangerZoneHeading->value);
        $this->assertStringContains('Danger Zone', $dangerZoneHeading->value);
        $this->assertTrue($dangerZoneHeading->asHtml);
    }

    /** @test */
    public function it_handles_heading_with_custom_visibility_settings(): void
    {
        $field = Heading::make('Admin Only Section')
            ->showOnIndex()
            ->hideFromDetail()
            ->hideWhenCreating()
            ->showOnUpdating();

        $user = User::find(1);
        $field->resolveForDisplay($user);

        $this->assertEquals('Admin Only Section', $field->value);
        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_handles_heading_with_help_text(): void
    {
        $field = Heading::make('Configuration Section')
            ->help('This section contains advanced configuration options');

        $user = User::find(1);
        $field->resolveForDisplay($user);

        $this->assertEquals('Configuration Section', $field->value);
        $this->assertEquals('This section contains advanced configuration options', $field->helpText);
    }

    /** @test */
    public function it_serializes_correctly_for_api_responses(): void
    {
        $field = Heading::make('<div class="alert alert-info"><strong>Note:</strong> Changes will take effect immediately.</div>')
            ->asHtml()
            ->showOnIndex()
            ->help('Information banner');

        $user = User::find(1);
        $field->resolveForDisplay($user);

        $json = $field->jsonSerialize();

        // Verify all necessary data is included for frontend
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('attribute', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('value', $json);
        $this->assertArrayHasKey('asHtml', $json);
        $this->assertArrayHasKey('isHeading', $json);
        $this->assertArrayHasKey('showOnIndex', $json);
        $this->assertArrayHasKey('showOnDetail', $json);
        $this->assertArrayHasKey('showOnCreation', $json);
        $this->assertArrayHasKey('showOnUpdate', $json);
        $this->assertArrayHasKey('helpText', $json);

        // Verify values
        $this->assertStringContains('<div class="alert alert-info">', $json['name']);
        $this->assertEquals('HeadingField', $json['component']);
        $this->assertTrue($json['asHtml']);
        $this->assertTrue($json['isHeading']);
        $this->assertTrue($json['showOnIndex']);
        $this->assertEquals('Information banner', $json['helpText']);
    }

    /** @test */
    public function it_handles_edge_cases_in_real_world_scenarios(): void
    {
        // Test empty heading
        $emptyHeading = Heading::make('');
        $user = User::find(1);
        $emptyHeading->resolveForDisplay($user);
        $this->assertEquals('', $emptyHeading->value);

        // Test heading with special characters
        $specialHeading = Heading::make('Données Utilisateur & Paramètres');
        $specialHeading->resolveForDisplay($user);
        $this->assertEquals('Données Utilisateur & Paramètres', $specialHeading->value);

        // Test heading with HTML entities
        $entityHeading = Heading::make('<p>Price: &euro;100 &amp; up</p>')->asHtml();
        $entityHeading->resolveForDisplay($user);
        $this->assertStringContains('&euro;100 &amp; up', $entityHeading->value);
    }

    /** @test */
    public function it_maintains_performance_with_multiple_headings(): void
    {
        $user = User::find(1);
        $headings = [];

        // Create multiple heading fields
        for ($i = 1; $i <= 10; $i++) {
            $headings[] = Heading::make("Section {$i}");
        }

        $startTime = microtime(true);

        // Resolve all headings
        foreach ($headings as $heading) {
            $heading->resolveForDisplay($user);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete quickly (under 100ms for 10 headings)
        $this->assertLessThan(0.1, $executionTime);

        // Verify all headings resolved correctly
        foreach ($headings as $index => $heading) {
            $this->assertEquals("Section " . ($index + 1), $heading->value);
        }
    }

    /** @test */
    public function it_integrates_with_form_validation_context(): void
    {
        // Heading fields should not interfere with form validation
        $field = Heading::make('Required Fields Section');
        $user = User::find(1);

        // Simulate form request
        $request = new \Illuminate\Http\Request([
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);

        // Fill should be a no-op for heading fields
        $field->fill($request, $user);

        // User should remain unchanged
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);

        // Field should still resolve correctly
        $field->resolveForDisplay($user);
        $this->assertEquals('Required Fields Section', $field->value);
    }

    /** @test */
    public function it_handles_conditional_display_scenarios(): void
    {
        $adminHeading = Heading::make('Administrator Tools')
            ->canSee(function ($request, $resource) {
                return $resource->is_admin ?? false;
            });

        $userHeading = Heading::make('User Settings')
            ->canSee(function ($request, $resource) {
                return !($resource->is_admin ?? false);
            });

        $adminUser = User::find(1); // is_admin = true
        $regularUser = User::find(2); // is_admin = false

        // Test admin heading visibility
        $adminHeading->resolveForDisplay($adminUser);
        $this->assertEquals('Administrator Tools', $adminHeading->value);

        // Test user heading visibility
        $userHeading->resolveForDisplay($regularUser);
        $this->assertEquals('User Settings', $userHeading->value);
    }

    /** @test */
    public function it_supports_dynamic_content_scenarios(): void
    {
        // Test heading with dynamic content based on resource
        $dynamicHeading = Heading::make('Welcome Back', 'welcome', function ($resource) {
            return "Welcome back, {$resource->name}!";
        });

        $user = User::find(1);
        $dynamicHeading->resolve($user);

        $this->assertEquals('Welcome back, John Doe!', $dynamicHeading->value);
    }

    /** @test */
    public function it_handles_internationalization_scenarios(): void
    {
        // Test heading with different languages/encodings
        $unicodeHeading = Heading::make('用户信息 / Información del Usuario / معلومات المستخدم');
        $user = User::find(1);
        $unicodeHeading->resolveForDisplay($user);

        $this->assertEquals('用户信息 / Información del Usuario / معلومات المستخدم', $unicodeHeading->value);
        $this->assertEquals('用户信息_/_información_del_usuario_/_معلومات_المستخدم', $unicodeHeading->attribute);
    }
}
