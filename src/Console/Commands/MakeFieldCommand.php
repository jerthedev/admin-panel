<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\GeneratorCommand;

/**
 * Make Field Command
 * 
 * Generates a new custom field class for the admin panel.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class MakeFieldCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:field {name : The name of the field}
                            {--force : Create the class even if the field already exists}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin panel field';

    /**
     * The type of class being generated.
     */
    protected $type = 'Field';

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
        return __DIR__ . '/../stubs/Field.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Admin\\Fields';
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(): void
    {
        $fieldName = $this->getNameInput();

        $this->info('');
        $this->info("✅ Field [{$fieldName}] created successfully!");
        $this->info('');
        $this->info('Next steps:');
        $this->line("1. Review the generated field in app/Admin/Fields/{$fieldName}.php");
        $this->line('2. Implement the field logic in the resolve() method');
        $this->line('3. Create a corresponding Vue.js component if needed');
        $this->line('4. Use the field in your resources:');
        $this->line('');
        $this->line("   {$fieldName}::make('Field Label')");
        $this->line("       ->rules('required')");
        $this->line("       ->help('Field description');");
        $this->info('');
    }
}
