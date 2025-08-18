<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Slug;
use JTD\AdminPanel\Tests\TestCase;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Slug Field E2E Test
 *
 * Tests the complete end-to-end functionality of Slug fields
 * including database operations, field behavior, and real-world scenarios.
 *
 * Focuses on field integration and data flow rather than
 * web interface testing (which is handled by Playwright tests).
 */
class SlugFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with various slug scenarios
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com'
        ]);
    }

    /** @test */
    public function it_handles_complete_slug_creation_workflow(): void
    {
        $field = Slug::make('Article Slug')
            ->from('title')
            ->separator('-')
            ->maxLength(100)
            ->required();

        // Simulate creating a new article with auto-generated slug
        $model = new \stdClass();
        $request = new Request([
            'title' => 'How to Build Amazing Web Applications',
            'article_slug' => '' // Empty slug should auto-generate
        ]);

        $field->fill($request, $model);

        $this->assertEquals('how-to-build-amazing-web-applications', $model->article_slug);
        $this->assertLessThanOrEqual(100, strlen($model->article_slug));
    }

    /** @test */
    public function it_handles_manual_slug_override_workflow(): void
    {
        $field = Slug::make('Post Slug')
            ->from('title')
            ->separator('_');

        // User provides custom slug
        $model = new \stdClass();
        $request = new Request([
            'title' => 'My Great Article',
            'post_slug' => 'custom-article-slug'
        ]);

        $field->fill($request, $model);

        // Should use provided slug (cleaned with underscore separator)
        $this->assertEquals('custom_article_slug', $model->post_slug);
    }

    /** @test */
    public function it_handles_slug_validation_and_cleaning(): void
    {
        $field = Slug::make('URL Slug')->maxLength(50);

        $model = new \stdClass();
        $request = new Request([
            'url_slug' => 'Messy Slug With Spaces & Special Characters!@#'
        ]);

        $field->fill($request, $model);

        // Should clean and validate
        $this->assertEquals('messy-slug-with-spaces-special-characters-at', $model->url_slug);
        $this->assertLessThanOrEqual(50, strlen($model->url_slug));
    }

    /** @test */
    public function it_handles_blog_post_creation_scenario(): void
    {
        $field = Slug::make('Slug')
            ->from('title')
            ->separator('-')
            ->maxLength(75)
            ->unique('posts', 'slug');

        // Test multiple blog posts with similar titles
        $scenarios = [
            ['title' => 'Getting Started with Laravel', 'expected' => 'getting-started-with-laravel'],
            ['title' => 'Advanced Laravel Techniques', 'expected' => 'advanced-laravel-techniques'],
            ['title' => 'Laravel & Vue.js Integration', 'expected' => 'laravel-vuejs-integration']
        ];

        foreach ($scenarios as $scenario) {
            $model = new \stdClass();
            $request = new Request([
                'title' => $scenario['title'],
                'slug' => ''
            ]);

            $field->fill($request, $model);

            $this->assertEquals($scenario['expected'], $model->slug);
            $this->assertLessThanOrEqual(75, strlen($model->slug));
        }
    }

    /** @test */
    public function it_handles_e_commerce_product_scenario(): void
    {
        $field = Slug::make('Product Slug')
            ->from('name')
            ->separator('_')
            ->maxLength(50)
            ->lowercase(true);

        // E-commerce product with complex name
        $model = new \stdClass();
        $request = new Request([
            'name' => 'Premium Wireless Headphones v2.0 - Noise Cancelling',
            'product_slug' => ''
        ]);

        $field->fill($request, $model);

        $this->assertStringContainsString('premium_wireless_headphones', $model->product_slug);
        $this->assertLessThanOrEqual(50, strlen($model->product_slug));
        $this->assertEquals(strtolower($model->product_slug), $model->product_slug);
    }

    /** @test */
    public function it_handles_user_profile_slug_scenario(): void
    {
        $field = Slug::make('Username')
            ->separator('-')
            ->maxLength(30)
            ->unique('users', 'username');

        // User registration with display name
        $model = new \stdClass();
        $request = new Request([
            'username' => 'John Doe Jr.'
        ]);

        $field->fill($request, $model);

        $this->assertEquals('john-doe-jr', $model->username);
        $this->assertLessThanOrEqual(30, strlen($model->username));
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $model->username);
    }

    /** @test */
    public function it_handles_seo_friendly_url_scenario(): void
    {
        $field = Slug::make('SEO Slug')
            ->from('title')
            ->separator('-')
            ->maxLength(60);

        // SEO-optimized content creation
        $model = new \stdClass();
        $request = new Request([
            'title' => '10 Best Practices for Web Development in 2024',
            'seo_slug' => ''
        ]);

        $field->fill($request, $model);

        $this->assertEquals('10-best-practices-for-web-development-in-2024', $model->seo_slug);
        $this->assertLessThanOrEqual(60, strlen($model->seo_slug));
        
        // Should be SEO-friendly (no special characters, proper separators)
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $model->seo_slug);
    }

    /** @test */
    public function it_handles_multilingual_content_scenario(): void
    {
        $field = Slug::make('Slug')
            ->from('title')
            ->separator('-');

        // Content with international characters
        $scenarios = [
            ['title' => 'Café & Restaurant Guide', 'expected' => 'cafe-restaurant-guide'],
            ['title' => 'Naïve Approach to Programming', 'expected' => 'naive-approach-to-programming'],
            ['title' => 'Résumé Building Tips', 'expected' => 'resume-building-tips']
        ];

        foreach ($scenarios as $scenario) {
            $model = new \stdClass();
            $request = new Request([
                'title' => $scenario['title'],
                'slug' => ''
            ]);

            $field->fill($request, $model);

            $this->assertEquals($scenario['expected'], $model->slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $model->slug);
        }
    }

    /** @test */
    public function it_handles_edge_cases_and_error_scenarios(): void
    {
        $field = Slug::make('Slug')->from('title');

        // Empty title should result in empty slug
        $model1 = new \stdClass();
        $request1 = new Request(['title' => '', 'slug' => '']);
        $field->fill($request1, $model1);
        $this->assertEquals('', $model1->slug);

        // Only special characters
        $model2 = new \stdClass();
        $request2 = new Request(['slug' => '!@#$%^&*()']);
        $field->fill($request2, $model2);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]*$/', $model2->slug);

        // Very long title with maxLength
        $longField = Slug::make('Slug')->from('title')->maxLength(20);
        $model3 = new \stdClass();
        $request3 = new Request([
            'title' => 'This is a very long title that should be truncated',
            'slug' => ''
        ]);
        $longField->fill($request3, $model3);
        $this->assertLessThanOrEqual(20, strlen($model3->slug));
    }

    /** @test */
    public function it_integrates_with_form_validation_workflow(): void
    {
        $field = Slug::make('Slug')
            ->required()
            ->unique('posts', 'slug')
            ->maxLength(100);

        // Test field serialization for frontend
        $serialized = $field->jsonSerialize();

        $this->assertArrayHasKey('rules', $serialized);
        $this->assertContains('required', $serialized['rules']);
        $this->assertEquals(100, $serialized['maxLength']);
        $this->assertEquals('posts', $serialized['uniqueTable']);
        $this->assertEquals('slug', $serialized['uniqueColumn']);
    }

    /** @test */
    public function it_handles_real_world_cms_scenario(): void
    {
        // Simulate a CMS where pages have auto-generated slugs from titles
        $field = Slug::make('Page Slug')
            ->from('page_title')
            ->separator('-')
            ->maxLength(80)
            ->unique('pages', 'slug');

        $pages = [
            'About Us - Company Information',
            'Contact & Support Information',
            'Privacy Policy & Terms of Service',
            'Blog - Latest News & Updates'
        ];

        foreach ($pages as $pageTitle) {
            $model = new \stdClass();
            $request = new Request([
                'page_title' => $pageTitle,
                'page_slug' => ''
            ]);

            $field->fill($request, $model);

            // Verify slug is URL-friendly and within limits
            $this->assertLessThanOrEqual(80, strlen($model->page_slug));
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $model->page_slug);
            $this->assertStringNotContainsString('--', $model->page_slug); // No double hyphens
            $this->assertStringNotContainsString(' ', $model->page_slug); // No spaces
        }
    }
}
