<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateLivewireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:livewire {name} 
                            {--schema=} 
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Livewire components';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $force = $this->option('force');
        
        // Check if Livewire is installed
        if (!class_exists('Livewire\Component')) {
            $this->error('Livewire is not installed. Please install Livewire first.');
            return;
        }
        
        $this->generateIndexComponent($name, $schema, $force);
        $this->generateFormComponent($name, $schema, $force);
        
        $this->info("Livewire components for [{$name}] generated successfully.");
    }
    
    /**
     * Generate the index component.
     *
     * @param string $name
     * @param string $schema
     * @param bool $force
     * @return void
     */
    protected function generateIndexComponent($name, $schema, $force)
    {
        $componentName = Str::studly($name) . 'Index';
        $viewName = Str::kebab($name) . '-index';
        $modelVariable = Str::camel($name);
        $modelVariablePlural = Str::camel(Str::pluralStudly($name));
        
        // Generate component class
        $componentPath = app_path('Http/Livewire/' . $componentName . '.php');
        
        if (File::exists($componentPath) && !$force) {
            $this->warn("Livewire component [{$componentName}] already exists. Use --force to overwrite.");
            return;
        }
        
        $componentStub = File::get(__DIR__ . '/../../stubs/livewire-index.stub');
        $componentStub = str_replace(
            ['{{ componentName }}', '{{ modelName }}', '{{ modelVariablePlural }}', '{{ viewName }}'],
            [$componentName, $name, $modelVariablePlural, $viewName],
            $componentStub
        );
        
        File::ensureDirectoryExists(dirname($componentPath));
        File::put($componentPath, $componentStub);
        
        // Generate component view
        $viewPath = resource_path('views/livewire/' . $viewName . '.blade.php');
        
        if (File::exists($viewPath) && !$force) {
            $this->warn("Livewire view [{$viewName}] already exists. Use --force to overwrite.");
            return;
        }
        
        $viewStub = File::get(__DIR__ . '/../../stubs/livewire-index-view.stub');
        $viewStub = str_replace(
            ['{{ modelName }}', '{{ modelVariablePlural }}', '{{ modelVariable }}'],
            [$name, $modelVariablePlural, $modelVariable],
            $viewStub
        );
        
        File::ensureDirectoryExists(dirname($viewPath));
        File::put($viewPath, $viewStub);
        
        $this->info("Livewire index component [{$componentName}] generated successfully.");
    }
    
    /**
     * Generate the form component.
     *
     * @param string $name
     * @param string $schema
     * @param bool $force
     * @return void
     */
    protected function generateFormComponent($name, $schema, $force)
    {
        $componentName = Str::studly($name) . 'Form';
        $viewName = Str::kebab($name) . '-form';
        $modelVariable = Str::camel($name);
        
        // Generate component class
        $componentPath = app_path('Http/Livewire/' . $componentName . '.php');
        
        if (File::exists($componentPath) && !$force) {
            $this->warn("Livewire component [{$componentName}] already exists. Use --force to overwrite.");
            return;
        }
        
        $componentStub = File::get(__DIR__ . '/../../stubs/livewire-form.stub');
        $componentStub = str_replace(
            ['{{ componentName }}', '{{ modelName }}', '{{ modelVariable }}', '{{ viewName }}'],
            [$componentName, $name, $modelVariable, $viewName],
            $componentStub
        );
        
        File::ensureDirectoryExists(dirname($componentPath));
        File::put($componentPath, $componentStub);
        
        // Generate component view
        $viewPath = resource_path('views/livewire/' . $viewName . '.blade.php');
        
        if (File::exists($viewPath) && !$force) {
            $this->warn("Livewire view [{$viewName}] already exists. Use --force to overwrite.");
            return;
        }
        
        $viewStub = File::get(__DIR__ . '/../../stubs/livewire-form-view.stub');
        $viewStub = str_replace(
            ['{{ modelName }}', '{{ modelVariable }}'],
            [$name, $modelVariable],
            $viewStub
        );
        
        File::ensureDirectoryExists(dirname($viewPath));
        File::put($viewPath, $viewStub);
        
        $this->info("Livewire form component [{$componentName}] generated successfully.");
    }
} 