<?php

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Rebuild and Publish Admin Panel Assets Command
 *
 * This command rebuilds the admin panel assets (CSS/JS) and publishes them
 * to the main application. Useful for development workflow.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @version 1.0.0
 */
class RebuildAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin-panel:rebuild-assets
                            {--force : Force republish assets even if they exist}
                            {--dev : Run in development mode with watch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild and publish admin panel assets (CSS/JS)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔨 Rebuilding Admin Panel Assets...');

        // Get package path
        $packagePath = dirname(dirname(dirname(__DIR__)));

        if (!file_exists($packagePath . '/package.json')) {
            $this->error('❌ Package directory not found or missing package.json');
            $this->line("Expected path: {$packagePath}");
            return self::FAILURE;
        }

        $this->line("📁 Package path: {$packagePath}");

        // Step 1: Install npm dependencies if needed
        if (!file_exists($packagePath . '/node_modules')) {
            $this->info('📦 Installing npm dependencies...');
            $result = Process::path($packagePath)->run('npm install');

            if ($result->failed()) {
                $this->error('❌ Failed to install npm dependencies');
                $this->line($result->errorOutput());
                return self::FAILURE;
            }

            $this->info('✅ npm dependencies installed');
        }

        // Step 2: Build assets
        $this->info('🏗️  Building assets...');

        $buildCommand = $this->option('dev') ? 'npm run dev' : 'npm run build';
        $result = Process::path($packagePath)->run($buildCommand);

        if ($result->failed()) {
            $this->error('❌ Failed to build assets');
            $this->line($result->errorOutput());
            return self::FAILURE;
        }

        $this->info('✅ Assets built successfully');

        // Show build output summary
        $output = $result->output();
        if (preg_match('/assets\/.*\.css\s+([0-9.]+\s+kB)/', $output, $cssMatch)) {
            $this->line("   📄 CSS: {$cssMatch[1]}");
        }
        if (preg_match('/assets\/.*\.js\s+([0-9.]+\s+kB)/', $output, $jsMatch)) {
            $this->line("   📄 JS: {$jsMatch[1]}");
        }

        // Step 3: Clean old published assets
        $this->info('🧹 Cleaning old published assets...');
        $publicPath = public_path('vendor/admin-panel');
        if (file_exists($publicPath)) {
            $this->line("   Removing: {$publicPath}");
            \Illuminate\Support\Facades\File::deleteDirectory($publicPath);
        }

        // Step 4: Publish assets to main application
        $this->info('📤 Publishing assets to main application...');

        $publishOptions = ['--tag' => 'admin-panel-assets'];
        if ($this->option('force')) {
            $publishOptions['--force'] = true;
        }

        $this->call('vendor:publish', $publishOptions);

        // Step 5: Verify published assets
        $this->info('🔍 Verifying published assets...');

        $publicPath = public_path('vendor/admin-panel');
        if (!file_exists($publicPath)) {
            $this->error('❌ Published assets directory not found');
            return self::FAILURE;
        }

        $assetFiles = glob($publicPath . '/assets/*');
        $manifestFile = $publicPath . '/.vite/manifest.json';

        $this->info('✅ Published ' . count($assetFiles) . ' asset files');
        $this->info('✅ Manifest: ' . (file_exists($manifestFile) ? 'Present' : 'Missing'));

        // Step 6: Show completion summary
        $this->newLine();
        $this->info('🎉 Admin Panel Assets Rebuilt & Published Successfully!');
        $this->newLine();

        $this->line('<comment>Next steps:</comment>');
        $this->line('• Visit /admin to test the updated interface');
        $this->line('• Hard refresh browser (Cmd+Shift+R) to clear cache');

        if ($this->option('dev')) {
            $this->line('• Development mode: Assets will auto-rebuild on changes');
        }

        return self::SUCCESS;
    }
}
