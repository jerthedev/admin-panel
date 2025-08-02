<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Uninstall Command
 *
 * Handles the clean removal of the admin panel package including
 * removing published assets, configurations, and admin directory structure.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class UninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:uninstall
                            {--force : Skip confirmation prompts}
                            {--keep-config : Keep published configuration file}
                            {--keep-admin : Keep app/Admin directory structure}
                            {--force-remove-admin : Force removal of app/Admin even if it contains user files}
                            {--keep-assets : Keep published assets}';

    /**
     * The console command description.
     */
    protected $description = 'Uninstall the JTD AdminPanel package and clean up files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🗑️  JTD AdminPanel Uninstaller');
        $this->newLine();

        // Confirmation prompt unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to uninstall JTD AdminPanel? This will remove files and configurations.')) {
                $this->info('Uninstall cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('🧹 Starting cleanup process...');
        $this->newLine();

        // Remove published assets
        if (!$this->option('keep-assets')) {
            $this->removePublishedAssets();
        }

        // Remove published configuration
        if (!$this->option('keep-config')) {
            $this->removePublishedConfig();
        }

        // Remove admin directory structure
        if (!$this->option('keep-admin')) {
            $this->removeAdminDirectory();
        }

        // Remove admin guard from auth config
        $this->removeAdminGuard();

        $this->displayCompletionMessage();

        return self::SUCCESS;
    }

    /**
     * Remove published assets.
     */
    protected function removePublishedAssets(): void
    {
        $this->info('📦 Removing published assets...');

        $assetsPath = public_path('vendor/admin-panel');

        if (File::exists($assetsPath)) {
            File::deleteDirectory($assetsPath);
            $this->info('✅ Published assets removed from public/vendor/admin-panel/');
        } else {
            $this->line('   No published assets found');
        }
    }

    /**
     * Remove published configuration.
     */
    protected function removePublishedConfig(): void
    {
        $this->info('📝 Removing published configuration...');

        $configPath = config_path('admin-panel.php');

        if (File::exists($configPath)) {
            File::delete($configPath);
            $this->info('✅ Configuration removed from config/admin-panel.php');
        } else {
            $this->line('   No published configuration found');
        }
    }

    /**
     * Remove admin directory structure intelligently.
     */
    protected function removeAdminDirectory(): void
    {
        $this->info('📁 Checking admin directory structure...');

        // Always remove AdminServiceProvider if it exists
        $this->removeAdminServiceProvider();

        // Handle app/Admin directory intelligently
        $this->handleAdminDirectory();
    }

    /**
     * Remove AdminServiceProvider if it exists.
     */
    protected function removeAdminServiceProvider(): void
    {
        $providerPath = base_path('app/Providers/AdminServiceProvider.php');

        if (File::exists($providerPath)) {
            File::delete($providerPath);
            $this->info('✅ Removed app/Providers/AdminServiceProvider.php');
            $this->comment('   Remember to remove from config/app.php providers array if manually added');
        } else {
            $this->line('   No AdminServiceProvider found (auto-discovery was used)');
        }
    }

    /**
     * Handle app/Admin directory based on content.
     */
    protected function handleAdminDirectory(): void
    {
        $adminPath = base_path('app/Admin');

        if (!File::exists($adminPath)) {
            $this->line('   No app/Admin directory found');
            return;
        }

        // Force removal if flag is set
        if ($this->option('force-remove-admin')) {
            File::deleteDirectory($adminPath);
            $this->info('✅ Force removed app/Admin directory and all contents');
            return;
        }

        // Check for user-created files
        $userFiles = $this->findUserFiles($adminPath);

        if (empty($userFiles)) {
            // Directory is empty or contains only package-generated files
            File::deleteDirectory($adminPath);
            $this->info('✅ Removed empty app/Admin directory');
        } else {
            // Directory contains user files - preserve it
            $this->warn('⚠️  app/Admin directory contains user files - preserving directory');
            $this->line('   User files found:');
            foreach (array_slice($userFiles, 0, 5) as $file) {
                $this->line("   • {$file}");
            }
            if (count($userFiles) > 5) {
                $this->line("   • ... and " . (count($userFiles) - 5) . " more files");
            }
            $this->newLine();
            $this->comment('💡 Use --force-remove-admin to remove directory with user files');
        }
    }

    /**
     * Find user-created files in admin directory.
     */
    protected function findUserFiles(string $adminPath): array
    {
        $userFiles = [];

        // Directories that typically contain user files
        $userDirectories = [
            'Resources',
            'Pages',
            'Metrics',
            'Actions',
            'Filters'
        ];

        foreach ($userDirectories as $dir) {
            $dirPath = $adminPath . '/' . $dir;
            if (File::exists($dirPath)) {
                $files = File::allFiles($dirPath);
                foreach ($files as $file) {
                    $relativePath = str_replace($adminPath . '/', '', $file->getPathname());
                    $userFiles[] = $relativePath;
                }
            }
        }

        return $userFiles;
    }

    /**
     * Remove admin guard from auth configuration.
     */
    protected function removeAdminGuard(): void
    {
        $this->info('🔐 Cleaning up authentication configuration...');

        $authConfigPath = config_path('auth.php');

        if (!File::exists($authConfigPath)) {
            $this->line('   No auth configuration found');
            return;
        }

        $authConfig = File::get($authConfigPath);

        // Check if admin guard exists
        if (strpos($authConfig, "'admin' =>") === false) {
            $this->line('   No admin guard found in auth configuration');
            return;
        }

        $this->warn('⚠️  Admin guard found in config/auth.php');
        $this->line('   Please manually remove the admin guard configuration if no longer needed');
        $this->line('   The package cannot safely modify this file automatically');
    }

    /**
     * Display completion message.
     */
    protected function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('🎉 JTD AdminPanel uninstall completed!');
        $this->newLine();

        $this->info('✅ Cleanup Summary:');
        $this->line('   • Published assets: ' . ($this->option('keep-assets') ? 'Kept' : 'Removed'));
        $this->line('   • Configuration file: ' . ($this->option('keep-config') ? 'Kept' : 'Removed'));
        $this->line('   • Admin directory: ' . $this->getAdminDirectoryStatus());
        $this->line('   • Auth guard: Manual cleanup required');
        $this->newLine();

        $this->info('📋 Manual Steps Required:');
        $this->line('1. Remove the package: composer remove jerthedev/admin-panel');
        $this->line('2. Review config/auth.php for admin guard cleanup');
        $this->line('3. Check for any custom admin-related code in your application');
        $this->newLine();

        $this->comment('💡 Thank you for using JTD AdminPanel!');
    }

    /**
     * Get admin directory status for summary.
     */
    protected function getAdminDirectoryStatus(): string
    {
        if ($this->option('keep-admin')) {
            return 'Kept (--keep-admin flag)';
        }

        if ($this->option('force-remove-admin')) {
            return 'Force removed';
        }

        $adminPath = base_path('app/Admin');
        if (!File::exists($adminPath)) {
            return 'Not found';
        }

        $userFiles = $this->findUserFiles($adminPath);
        if (empty($userFiles)) {
            return 'Removed (empty)';
        } else {
            return 'Preserved (contains user files)';
        }
    }
}
