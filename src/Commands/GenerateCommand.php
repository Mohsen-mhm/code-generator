<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MohsenMhm\CodeGenerator\Generators\ControllerGenerator;
use MohsenMhm\CodeGenerator\Generators\LivewireGenerator;
use MohsenMhm\CodeGenerator\Generators\MigrationGenerator;
use MohsenMhm\CodeGenerator\Generators\ModelGenerator;
use MohsenMhm\CodeGenerator\Generators\ResourceGenerator;
use MohsenMhm\CodeGenerator\Generators\RoutesGenerator;
use MohsenMhm\CodeGenerator\Generators\TestGenerator;

class GenerateCommand extends Command
{
    protected $signature = 'generate {name} 
                            {--schema=} 
                            {--all : Generate all components} 
                            {--model : Generate a model} 
                            {--controller : Generate a controller} 
                            {--migration : Generate a migration} 
                            {--resource : Generate an API resource} 
                            {--livewire : Generate a Livewire component} 
                            {--test : Generate tests} 
                            {--routes : Generate routes}
                            {--api : Generate API components} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate multiple components for your Laravel application';

    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $all = $this->option('all');
        $force = $this->option('force');
        
        $options = [
            'force' => $force,
            'api' => $this->option('api'),
        ];
        
        if ($all || $this->option('model')) {
            $this->generateModel($name, $schema, $options);
        }
        
        if ($all || $this->option('migration')) {
            $this->generateMigration($name, $schema, $options);
        }
        
        if ($all || $this->option('controller')) {
            $this->generateController($name, $schema, $options);
        }
        
        if ($all || $this->option('resource')) {
            $this->generateResource($name, $schema, $options);
        }
        
        if ($all || $this->option('livewire')) {
            $this->generateLivewire($name, $schema, $options);
        }
        
        if ($all || $this->option('test')) {
            $this->generateTest($name, $schema, $options);
        }
        
        if ($all || $this->option('routes')) {
            $this->generateRoutes($name, $options);
        }
        
        return 0;
    }
    
    protected function generateModel($name, $schema, $options)
    {
        $modelName = Str::singular(Str::studly($name));
        
        app(ModelGenerator::class)
            ->setCommand($this)
            ->setName($modelName)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
    }
    
    protected function generateMigration($name, $schema, $options)
    {
        $modelName = Str::singular(Str::studly($name));
        
        app(MigrationGenerator::class)
            ->setCommand($this)
            ->setName($modelName)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
    }
    
    protected function generateController($name, $schema, $options)
    {
        $controllerName = Str::studly($name);
        
        if (!Str::endsWith($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }
        
        app(ControllerGenerator::class)
            ->setCommand($this)
            ->setName($controllerName)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
    }
    
    protected function generateResource($name, $schema, $options)
    {
        $resourceName = Str::singular(Str::studly($name));
        
        if (!Str::endsWith($resourceName, 'Resource')) {
            $resourceName .= 'Resource';
        }
        
        app(ResourceGenerator::class)
            ->setCommand($this)
            ->setName($resourceName)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
    }
    
    protected function generateLivewire($name, $schema, $options)
    {
        $componentName = Str::studly($name);
        
        app(LivewireGenerator::class)
            ->setCommand($this)
            ->setName($componentName)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
    }
    
    protected function generateTest($name, $schema, $options)
    {
        $testName = Str::studly($name);
        
        if (!Str::endsWith($testName, 'Test')) {
            $testName .= 'Test';
        }
        
        app(TestGenerator::class)
            ->setCommand($this)
            ->setName($testName)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
    }
    
    protected function generateRoutes($name, $options)
    {
        app(RoutesGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setOptions($options)
            ->generate();
    }
} 