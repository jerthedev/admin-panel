<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Accessibility;

use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Dashboard Accessibility Tests
 * 
 * Tests for WCAG 2.1 AA compliance, keyboard navigation,
 * screen reader compatibility, and accessibility features.
 */
class DashboardAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate admin user
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true
        ]);
        
        Auth::login($user);
    }

    public function test_dashboard_has_proper_semantic_structure(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test semantic HTML structure
        $response->assertSee('<main', false);
        $response->assertSee('<nav', false);
        $response->assertSee('<header', false);
        $response->assertSee('<section', false);
        
        // Test heading hierarchy
        $response->assertSee('<h1', false);
        $response->assertSeeInOrder(['<h1', '<h2'], false);
    }

    public function test_dashboard_has_proper_aria_labels(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test ARIA landmarks
        $response->assertSee('role="main"', false);
        $response->assertSee('role="navigation"', false);
        $response->assertSee('aria-label="Main navigation"', false);
        $response->assertSee('aria-label="Dashboard content"', false);
        
        // Test ARIA descriptions
        $response->assertSee('aria-describedby=', false);
        $response->assertSee('aria-labelledby=', false);
    }

    public function test_dashboard_navigation_is_keyboard_accessible(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test tabindex attributes
        $response->assertSee('tabindex="0"', false);
        
        // Test focus management
        $response->assertSee('data-focus-trap', false);
        $response->assertSee('data-keyboard-navigation', false);
        
        // Test skip links
        $response->assertSee('Skip to main content', false);
        $response->assertSee('Skip to navigation', false);
    }

    public function test_dashboard_forms_have_proper_labels(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test form labels
        $content = $response->getContent();
        
        // Check that all inputs have associated labels
        preg_match_all('/<input[^>]*id="([^"]*)"/', $content, $inputIds);
        preg_match_all('/<label[^>]*for="([^"]*)"/', $content, $labelFors);
        
        foreach ($inputIds[1] as $inputId) {
            if (!empty($inputId)) {
                $this->assertContains($inputId, $labelFors[1], 
                    "Input with id '{$inputId}' should have a corresponding label");
            }
        }
    }

    public function test_dashboard_images_have_alt_text(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check that all images have alt attributes
        preg_match_all('/<img[^>]*>/', $content, $images);
        
        foreach ($images[0] as $img) {
            $this->assertStringContainsString('alt=', $img, 
                'All images should have alt attributes');
        }
    }

    public function test_dashboard_has_proper_color_contrast(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test high contrast mode support
        $response->assertSee('data-high-contrast', false);
        $response->assertSee('prefers-contrast', false);
        
        // Test color contrast CSS variables
        $response->assertSee('--color-contrast-', false);
    }

    public function test_dashboard_supports_reduced_motion(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test reduced motion support
        $response->assertSee('prefers-reduced-motion', false);
        $response->assertSee('data-reduced-motion', false);
    }

    public function test_dashboard_has_proper_focus_indicators(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test focus indicators
        $response->assertSee('focus:ring', false);
        $response->assertSee('focus:outline', false);
        $response->assertSee('focus-visible:', false);
    }

    public function test_dashboard_error_messages_are_accessible(): void
    {
        // Test with invalid dashboard access
        $response = $this->get('/admin/dashboards/non-existent');
        
        // Test error message accessibility
        $response->assertSee('role="alert"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('aria-atomic="true"', false);
    }

    public function test_dashboard_loading_states_are_announced(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test loading state announcements
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('aria-busy="true"', false);
        $response->assertSee('Loading dashboard', false);
    }

    public function test_dashboard_modals_are_accessible(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test modal accessibility
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('aria-labelledby=', false);
        $response->assertSee('data-focus-trap', false);
    }

    public function test_dashboard_tables_are_accessible(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Test table accessibility
        if (strpos($content, '<table') !== false) {
            $response->assertSee('<caption>', false);
            $response->assertSee('<th scope=', false);
            $response->assertSee('role="table"', false);
        }
    }

    public function test_dashboard_buttons_have_accessible_names(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Check that all buttons have accessible names
        preg_match_all('/<button[^>]*>/', $content, $buttons);
        
        foreach ($buttons[0] as $button) {
            $hasText = !preg_match('/>\s*</', $button);
            $hasAriaLabel = strpos($button, 'aria-label=') !== false;
            $hasAriaLabelledby = strpos($button, 'aria-labelledby=') !== false;
            $hasTitle = strpos($button, 'title=') !== false;
            
            $this->assertTrue(
                $hasText || $hasAriaLabel || $hasAriaLabelledby || $hasTitle,
                'All buttons should have accessible names'
            );
        }
    }

    public function test_dashboard_supports_screen_readers(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test screen reader support
        $response->assertSee('sr-only', false);
        $response->assertSee('screen-reader-text', false);
        $response->assertSee('visually-hidden', false);
        
        // Test ARIA live regions
        $response->assertSee('aria-live=', false);
        $response->assertSee('aria-atomic=', false);
    }

    public function test_dashboard_keyboard_shortcuts_are_documented(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test keyboard shortcuts documentation
        $response->assertSee('data-keyboard-shortcut', false);
        $response->assertSee('aria-keyshortcuts=', false);
        $response->assertSee('accesskey=', false);
    }

    public function test_dashboard_form_validation_is_accessible(): void
    {
        // Test form with validation errors
        $response = $this->post('/admin/dashboards', [
            'invalid_field' => 'invalid_value'
        ]);
        
        // Test validation error accessibility
        $response->assertSee('aria-invalid="true"', false);
        $response->assertSee('aria-describedby=', false);
        $response->assertSee('role="alert"', false);
    }

    public function test_dashboard_responsive_design_maintains_accessibility(): void
    {
        // Test mobile viewport
        $response = $this->get('/admin', [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'
        ]);
        
        $response->assertStatus(200);
        
        // Test mobile accessibility features
        $response->assertSee('viewport', false);
        $response->assertSee('touch-action', false);
        $response->assertSee('data-mobile-accessible', false);
    }

    public function test_dashboard_supports_voice_control(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test voice control support
        $response->assertSee('data-voice-command', false);
        $response->assertSee('aria-label=', false);
        
        // Test unique accessible names for voice commands
        $content = $response->getContent();
        preg_match_all('/aria-label="([^"]*)"/', $content, $ariaLabels);
        
        $uniqueLabels = array_unique($ariaLabels[1]);
        $this->assertCount(
            count($ariaLabels[1]), 
            $uniqueLabels, 
            'All aria-labels should be unique for voice control'
        );
    }

    public function test_dashboard_internationalization_accessibility(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test i18n accessibility
        $response->assertSee('lang=', false);
        $response->assertSee('dir=', false);
        $response->assertSee('translate=', false);
    }

    public function test_dashboard_performance_accessibility(): void
    {
        $startTime = microtime(true);
        
        $response = $this->get('/admin');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Test that page loads within accessibility guidelines (< 3 seconds)
        $this->assertLessThan(3000, $loadTime, 
            'Dashboard should load within 3 seconds for accessibility');
    }

    public function test_dashboard_cognitive_accessibility(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Test cognitive accessibility features
        $response->assertSee('data-simple-mode', false);
        $response->assertSee('data-reading-level', false);
        $response->assertSee('data-complexity-level', false);
        
        // Test clear navigation structure
        $response->assertSee('breadcrumb', false);
        $response->assertSee('data-current-page', false);
    }
}
