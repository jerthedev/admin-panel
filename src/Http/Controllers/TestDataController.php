<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JTD\AdminPanel\Tests\Factories\UserFactory;
use JTD\AdminPanel\Tests\Factories\PostFactory;
use JTD\AdminPanel\Tests\Support\TestDataSeeder;

/**
 * Test Data Controller
 *
 * Provides API endpoints for automated test data creation and cleanup.
 * Only available in testing environments for security.
 */
class TestDataController extends Controller
{
    /**
     * Setup admin demo data using existing factories.
     */
    public function setupAdminDemo(Request $request): JsonResponse
    {
        try {
            $counts = [
                'users' => $request->input('users', 10),
                'posts' => $request->input('posts', 25),
            ];

            $data = [];

            // Create users using existing UserFactory
            if ($counts['users'] > 0) {
                $users = UserFactory::new()->count($counts['users'])->create();
                $data['users'] = [
                    'count' => $users->count(),
                    'ids' => $users->pluck('id')->toArray(),
                    'first_user_id' => $users->first()->id ?? null,
                ];
            }

            // Create posts using existing PostFactory
            if ($counts['posts'] > 0) {
                $posts = PostFactory::new()->count($counts['posts'])->create();
                $data['posts'] = [
                    'count' => $posts->count(),
                    'ids' => $posts->pluck('id')->toArray(),
                    'first_post_id' => $posts->first()->id ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Admin demo data created successfully',
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin demo data',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Cleanup all test data from database.
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $cleanedTables = [];
            $totalRecords = 0;

            // Get all tables that might contain test data
            $tables = ['users', 'posts', 'media', 'failed_jobs', 'personal_access_tokens'];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        DB::table($table)->truncate();
                        $cleanedTables[] = $table;
                        $totalRecords += $count;
                    }
                }
            }

            // Reset auto-increment counters (database-specific)
            foreach ($cleanedTables as $table) {
                if (Schema::hasTable($table)) {
                    $this->resetAutoIncrement($table);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Test data cleanup completed',
                'data' => [
                    'cleaned_tables' => $cleanedTables,
                    'total_records_removed' => $totalRecords,
                ],
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup test data',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Seed comprehensive field examples for all field types.
     */
    public function seedFieldExamples(Request $request): JsonResponse
    {
        try {
            $seeder = new TestDataSeeder();
            $data = $seeder->seedFieldExamples();

            return response()->json([
                'success' => true,
                'message' => 'Field examples seeded successfully',
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to seed field examples',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Get current test data status.
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $status = [];
            $tables = ['users', 'posts', 'media'];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $status[$table] = DB::table($table)->count();
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'environment' => app()->environment(),
                    'table_counts' => $status,
                    'database' => config('database.default'),
                ],
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get test data status',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Create specific test scenarios.
     */
    public function createScenario(Request $request, string $scenario): JsonResponse
    {
        try {
            $seeder = new TestDataSeeder();
            $data = $seeder->createScenario($scenario, $request->all());

            return response()->json([
                'success' => true,
                'message' => "Scenario '{$scenario}' created successfully",
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to create scenario '{$scenario}'",
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Reset auto-increment counter for a table (database-specific).
     */
    protected function resetAutoIncrement(string $table): void
    {
        $driver = DB::getDriverName();

        try {
            switch ($driver) {
                case 'mysql':
                    DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                    break;
                case 'pgsql':
                    // PostgreSQL uses sequences
                    $sequences = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_default LIKE 'nextval%'", [$table]);
                    foreach ($sequences as $sequence) {
                        $sequenceName = "{$table}_{$sequence->column_name}_seq";
                        DB::statement("ALTER SEQUENCE {$sequenceName} RESTART WITH 1");
                    }
                    break;
                case 'sqlite':
                    // SQLite auto-resets AUTOINCREMENT when table is empty
                    // But we can delete from sqlite_sequence if it exists
                    try {
                        DB::statement("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
                    } catch (\Exception $e) {
                        // sqlite_sequence might not exist, which is fine
                    }
                    break;
                case 'sqlsrv':
                    DB::statement("DBCC CHECKIDENT ('{$table}', RESEED, 0)");
                    break;
                default:
                    // For other databases, just skip auto-increment reset
                    break;
            }
        } catch (\Exception $e) {
            // If auto-increment reset fails, log but don't fail the entire cleanup
            \Log::warning("Failed to reset auto-increment for table {$table}: " . $e->getMessage());
        }
    }
}
