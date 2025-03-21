<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Traits\GeneratesFiles;
use MohsenMhm\CodeGenerator\Traits\ParsesSchemas;

abstract class BaseGenerator
{
    use GeneratesFiles, ParsesSchemas {
        ParsesSchemas::formatSchemaFieldsAsString insteadof GeneratesFiles;
        GeneratesFiles::getFieldsAsString insteadof ParsesSchemas;
    }
    
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;
    
    /**
     * The console command instance.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;
    
    /**
     * The name of the entity.
     *
     * @var string
     */
    protected $name;
    
    /**
     * The schema string.
     *
     * @var string|null
     */
    protected $schema;
    
    /**
     * The options array.
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * Create a new generator instance.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }
    
    /**
     * Set the console command instance.
     *
     * @param \Illuminate\Console\Command $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
        
        return $this;
    }
    
    /**
     * Set the name of the entity.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * Set the schema string.
     *
     * @param string|null $schema
     * @return $this
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        
        return $this;
    }
    
    /**
     * Set the options array.
     *
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        
        return $this;
    }
    
    /**
     * Get the namespace for a type.
     *
     * @param string $type
     * @return string
     */
    protected function getNamespace($type)
    {
        $namespaces = [
            'model' => config('code-generator.namespaces.model', 'App\\Models'),
            'controller' => config('code-generator.namespaces.controller', 'App\\Http\\Controllers'),
            'resource' => config('code-generator.namespaces.resource', 'App\\Http\\Resources'),
            'factory' => config('code-generator.namespaces.factory', 'Database\\Factories'),
            'test' => config('code-generator.namespaces.test', 'Tests'),
            'livewire' => config('code-generator.namespaces.livewire', 'App\\Livewire'),
        ];
        
        return $namespaces[$type] ?? 'App';
    }
    
    /**
     * Get the path for a type.
     *
     * @param string $type
     * @return string
     */
    protected function getPath($type)
    {
        $paths = [
            'models' => config('code-generator.paths.models', app_path('Models')),
            'controllers' => config('code-generator.paths.controllers', app_path('Http/Controllers')),
            'resources' => config('code-generator.paths.resources', app_path('Http/Resources')),
            'migrations' => config('code-generator.paths.migrations', database_path('migrations')),
            'factories' => config('code-generator.paths.factories', database_path('factories')),
            'tests' => config('code-generator.paths.tests', base_path('tests')),
            'livewire' => config('code-generator.paths.livewire', app_path('Livewire')),
            'views' => config('code-generator.paths.views', resource_path('views')),
        ];
        
        return $paths[$type] ?? app_path();
    }
    
    /**
     * Output an info message.
     *
     * @param string $message
     * @return void
     */
    protected function info($message)
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
    
    /**
     * Output an error message.
     *
     * @param string $message
     * @return void
     */
    protected function error($message)
    {
        if ($this->command) {
            $this->command->error($message);
        }
    }
    
    /**
     * Generate the entity.
     *
     * @return bool
     */
    abstract public function generate();
} 