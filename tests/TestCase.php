<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use JTD\AdminPanel\AdminPanelServiceProvider;
use JTD\AdminPanel\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base Test Case
 *
 * Base test case for all admin panel tests providing common
 * setup, utilities, and helper methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'JTD\\AdminPanel\\Tests\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            AdminPanelServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure admin panel
        config()->set('admin-panel.auth.guard', 'web');
        config()->set('admin-panel.auth.user_model', User::class);
        config()->set('admin-panel.path', 'admin');
        config()->set('admin-panel.middleware', ['web']);

        // Configure media library for testing
        config()->set('admin-panel.media_library', [
            'default_disk' => 'public',
            'auto_cleanup' => true,
            'file_size_limits' => [
                'file' => 10240,
                'image' => 5120,
                'avatar' => 2048,
            ],
            'default_conversions' => [
                'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop', 'quality' => 85],
                'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain', 'quality' => 90],
                'large' => ['width' => 1200, 'height' => 1200, 'fit' => 'contain', 'quality' => 90],
            ],
            'avatar_conversions' => [
                'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop', 'quality' => 85],
                'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop', 'quality' => 90],
                'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop', 'quality' => 90],
            ],
            'responsive_images' => [
                'enabled' => true,
                'breakpoints' => ['sm' => 640, 'md' => 768, 'lg' => 1024, 'xl' => 1280],
                'quality' => 85,
            ],
            'accepted_mime_types' => [
                'file' => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'text/csv',
                    'application/zip',
                    'application/x-zip-compressed',
                ],
                'image' => [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                    'image/svg+xml',
                ],
                'avatar' => [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/webp',
                ],
            ],
        ]);

        // Configure authentication
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        config()->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
    }

    protected function setUpDatabase(): void
    {
        // Create countries table first for foreign key reference
        Schema::create('countries', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code', 2)->unique();
            $table->string('continent');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null');
            // Add columns for field testing
            $table->string('avatar')->nullable(); // For Avatar field E2E tests
            $table->string('theme_song')->nullable(); // For Audio field E2E tests
            $table->json('permissions')->nullable(); // For BooleanGroup field E2E tests
            $table->json('features')->nullable(); // For BooleanGroup field E2E tests
            $table->text('code')->nullable(); // For Code field E2E tests
            $table->json('config')->nullable(); // For Code field E2E tests (JSON)
            $table->string('color')->nullable(); // For Color field tests
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('addresses', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('street');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create cars table for HasOneThrough relationship testing
        Schema::create('cars', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('vin')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create car_owners table for HasOneThrough relationship testing
        Schema::create('car_owners', function ($table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('license_number')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create roles table for BelongsToMany relationship testing
        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create role_user pivot table for BelongsToMany relationship testing
        Schema::create('role_user', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at')->nullable();
            $table->string('assigned_by')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // Create images table for MorphOne relationship testing
        Schema::create('images', function ($table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->integer('size')->nullable();
            $table->string('mime_type')->nullable();
            $table->morphs('imageable'); // Creates imageable_type and imageable_id
            $table->timestamps();
            $table->softDeletes();
        });

        // Create comments table for MorphMany relationship testing
        Schema::create('comments', function ($table) {
            $table->id();
            $table->text('content');
            $table->string('author_name');
            $table->string('author_email');
            $table->boolean('is_approved')->default(true);
            $table->nullableMorphs('commentable'); // Creates nullable commentable_type and commentable_id
            $table->timestamps();
            $table->softDeletes();
        });

        // Create tags table for MorphToMany relationship testing
        Schema::create('tags', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create taggables pivot table for MorphToMany relationship testing
        Schema::create('taggables', function ($table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->morphs('taggable'); // Creates taggable_type and taggable_id
            $table->text('notes')->nullable(); // Example pivot field
            $table->integer('priority')->default(0); // Example pivot field
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function createAdminUser(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function actingAsAdmin(array $attributes = []): static
    {
        $admin = $this->createAdminUser($attributes);
        $this->actingAs($admin);

        return $this;
    }

    protected function actingAsUser(array $attributes = []): static
    {
        $user = $this->createUser($attributes);
        $this->actingAs($user);

        return $this;
    }

    protected function assertDatabaseHasModel($model): static
    {
        $this->assertDatabaseHas($model->getTable(), [
            $model->getKeyName() => $model->getKey(),
        ]);

        return $this;
    }

    protected function assertDatabaseMissingModel($model): static
    {
        $this->assertDatabaseMissing($model->getTable(), [
            $model->getKeyName() => $model->getKey(),
        ]);

        return $this;
    }

    protected function assertRedirectToLogin(): static
    {
        $this->assertRedirect(route('admin-panel.login'));

        return $this;
    }

    protected function assertRedirectToDashboard(): static
    {
        $this->assertRedirect(route('admin-panel.dashboard'));

        return $this;
    }

    protected function assertInertiaComponent(string $component): static
    {
        $this->assertInertia(fn (AssertableInertia $page) => $page->component($component));

        return $this;
    }

    protected function assertInertiaHas(string $key): static
    {
        $this->assertInertia(fn (AssertableInertia $page) => $page->has($key));

        return $this;
    }

    protected function assertInertiaCount(string $key, int $count): static
    {
        $this->assertInertia(fn (AssertableInertia $page) => $page->count($key, $count));

        return $this;
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertThat(
            $haystack,
            $this->stringContains($needle),
            $message
        );
    }

    /**
     * Get expected status code for non-admin user access based on configuration.
     */
    protected function expectedNonAdminStatusCode(): int
    {
        // If allow_all_authenticated is true (Nova-like), non-admin users get access (200)
        // If false, they should get 403 Forbidden
        return config('admin-panel.auth.allow_all_authenticated', true) ? 200 : 403;
    }

    /**
     * Check if non-admin users should have admin access based on configuration.
     */
    protected function nonAdminShouldHaveAccess(): bool
    {
        return config('admin-panel.auth.allow_all_authenticated', true);
    }

    /**
     * Assert response status based on admin panel configuration.
     */
    protected function assertNonAdminResponse($response): void
    {
        $expectedStatus = $this->expectedNonAdminStatusCode();
        $response->assertStatus($expectedStatus);
    }
}
