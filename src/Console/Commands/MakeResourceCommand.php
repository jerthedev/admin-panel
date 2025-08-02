<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

/**
 * Make Resource Command
 * 
 * Generates a new admin panel resource class with proper structure
 * and field definitions based on the associated model.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class MakeResourceCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:resource {name : The name of the resource}
                            {--model= : The model that the resource applies to}
                            {--force : Create the class even if the resource already exists}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin panel resource';

    /**
     * The type of class being generated.
     */
    protected $type = 'Resource';

    /**
     * Execute the console command.
     */
    public function handle(): ?bool
    {
        $result = parent::handle();

        if ($result !== false) {
            $this->displaySuccessMessage();
        }

        return $result;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__ . '/../stubs/Resource.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Admin\\Resources';
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceModel($stub)
            ->replaceClass($stub, $name);
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceModel(string &$stub): static
    {
        $model = $this->option('model') ?: $this->guessModelName();
        $modelClass = $this->qualifyModel($model);

        $stub = str_replace(
            ['{{ model }}', '{{ modelClass }}', '{{ modelVariable }}'],
            [$model, $modelClass, Str::camel($model)],
            $stub
        );

        return $this;
    }

    /**
     * Guess the model name from the resource name.
     */
    protected function guessModelName(): string
    {
        $name = $this->getNameInput();
        
        // Remove 'Resource' suffix if present
        if (Str::endsWith($name, 'Resource')) {
            $name = Str::substr($name, 0, -8);
        }

        return $name;
    }

    /**
     * Qualify the given model class name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return $rootNamespace . 'Models\\' . $model;
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(): void
    {
        $resourceName = $this->getNameInput();
        $model = $this->option('model') ?: $this->guessModelName();

        $this->info('');
        $this->info("✅ Resource [{$resourceName}] created successfully!");
        $this->info('');
        $this->info('Next steps:');
        $this->line("1. Review the generated resource in app/Admin/Resources/{$resourceName}.php");
        $this->line('2. Customize the fields() method to define your resource fields');
        $this->line('3. Register the resource in your AdminServiceProvider:');
        $this->line('');
        $this->line("   AdminPanel::resources([");
        $this->line("       \\App\\Admin\\Resources\\{$resourceName}::class,");
        $this->line("   ]);");
        $this->line('');
        $this->line("4. Ensure your {$model} model exists and is properly configured");
        $this->line('5. Visit the admin panel to see your new resource');
        $this->info('');
    }
}
