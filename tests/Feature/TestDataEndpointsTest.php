<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Test Data Endpoints Test
 *
 * Tests the automated test data creation and cleanup endpoints.
 */
class TestDataEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we're in testing environment
        $this->app['env'] = 'testing';
    }

    /** @test */
    public function it_can_get_test_data_status()
    {
        $response = $this->getJson('/admin/api/test/status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'environment',
                        'table_counts',
                        'database',
                    ],
                    'timestamp',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('testing', $response->json('data.environment'));
    }

    /** @test */
    public function it_can_setup_admin_demo_data()
    {
        $response = $this->postJson('/admin/api/test/setup-admin-demo', [
            'users' => 5,
            'posts' => 10,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'users' => [
                            'count',
                            'ids',
                            'first_user_id',
                        ],
                        'posts' => [
                            'count',
                            'ids',
                            'first_post_id',
                        ],
                    ],
                    'timestamp',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(5, $response->json('data.users.count'));
        $this->assertEquals(10, $response->json('data.posts.count'));
    }

    /** @test */
    public function it_can_cleanup_test_data()
    {
        // First create some data
        $this->postJson('/admin/api/test/setup-admin-demo', [
            'users' => 3,
            'posts' => 5,
        ]);

        // Then cleanup
        $response = $this->postJson('/admin/api/test/cleanup');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'cleaned_tables',
                        'total_records_removed',
                    ],
                    'timestamp',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data.cleaned_tables'));
    }

    /** @test */
    public function it_can_seed_field_examples()
    {
        $response = $this->postJson('/admin/api/test/seed-field-examples');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'text_fields',
                        'datetime_fields',
                        'validation_fields',
                        'rich_content_fields',
                        'relationship_fields',
                        'specialized_fields',
                    ],
                    'timestamp',
                ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_can_create_specific_scenarios()
    {
        $response = $this->postJson('/admin/api/test/scenarios/validation-errors');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data',
                    'timestamp',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertStringContains('validation-errors', $response->json('message'));
    }

    /** @test */
    public function it_rejects_access_in_production_environment()
    {
        // Simulate production environment
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $response = $this->getJson('/admin/api/test/status');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_allows_access_when_explicitly_enabled()
    {
        // Simulate production but with test endpoints enabled
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
        config(['admin-panel.enable_test_endpoints' => true]);

        $response = $this->getJson('/admin/api/test/status');

        $response->assertStatus(200);
    }
}
