<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Make Dashboard Command.
 *
 * Artisan command for creating Nova-compatible dashboard classes
 * with proper stubs and auto-registration support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MakeDashboardCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:dashboard
                            {name : The name of the dashboard}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin panel dashboard';

    /**
     * The type of class being generated.
     */
    protected $type = 'Dashboard';

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->getNameInput();

        // Generate the dashboard class
        if (parent::handle() === false) {
            return 1;
        }

        $this->info("Dashboard [{$name}] created successfully.");

        // Provide registration instructions
        $this->comment('');
        $this->comment('Next steps:');
        $this->comment('1. Register your dashboard in a service provider:');
        $this->comment("   AdminPanel::dashboards([\\App\\Admin\\Dashboards\\{$name}::class]);");
        $this->comment('');
        $this->comment('2. Or add it to your AdminServiceProvider:');
        $this->comment('   protected function dashboards(): array');
        $this->comment('   {');
        $this->comment('       return [');
        $this->comment("           \\App\\Admin\\Dashboards\\{$name}::make(),");
        $this->comment('       ];');
        $this->comment('   }');

        return 0;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__.'/../stubs/Dashboard.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Admin\\Dashboards';
    }

    /**
     * Get the destination class path.
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
        $stub = $this->replaceUriKey($stub, $name);

        return $stub;
    }

    /**
     * Replace the URI key for the given stub.
     */
    protected function replaceUriKey($stub, $name): string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        $uriKey = Str::kebab($class);

        return str_replace(['{{ uriKey }}', '{{uriKey}}'], $uriKey, $stub);
    }
}
