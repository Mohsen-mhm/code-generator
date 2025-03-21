<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Traits\GeneratesFiles;
use MohsenMhm\CodeGenerator\Traits\ParsesSchemas;
use Illuminate\Support\Str;

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
    public function __construct($name, $schema = '', $options = [])
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->options = $options;
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
     * Get the namespace for a given type.
     *
     * @param string $type
     * @return string
     */
    protected function getNamespace($type)
    {
        $namespaces = [
            'controller' => 'App\\Http\\Controllers',
            'model' => 'App\\Models',
            'resource' => 'App\\Http\\Resources',
            'request' => 'App\\Http\\Requests',
            'factory' => 'Database\\Factories',
            'seeder' => 'Database\\Seeders',
            'policy' => 'App\\Policies',
        ];
        
        return $namespaces[$type] ?? 'App';
    }
    
    /**
     * Get the path for a given type.
     *
     * @param string $type
     * @return string
     */
    protected function getPath($type)
    {
        $paths = [
            'controllers' => app_path('Http/Controllers'),
            'models' => app_path('Models'),
            'resources' => app_path('Http/Resources'),
            'requests' => app_path('Http/Requests'),
            'factories' => database_path('factories'),
            'seeders' => database_path('seeders'),
            'policies' => app_path('Policies'),
            'views' => resource_path('views'),
            'migrations' => database_path('migrations'),
        ];
        
        return $paths[$type] ?? app_path();
    }
    
    /**
     * Get the contents of a stub file.
     *
     * @param string $stub
     * @param array $replacements
     * @return string
     */
    protected function getStubContents($stub, $replacements = [])
    {
        $contents = file_get_contents(__DIR__ . '/../Stubs/' . $stub . '.stub');
        
        foreach ($replacements as $search => $replace) {
            $contents = str_replace('{{ ' . $search . ' }}', $replace, $contents);
        }
        
        return $contents;
    }
    
    /**
     * Write contents to a file.
     *
     * @param string $path
     * @param string $contents
     * @return bool
     */
    protected function writeFile($path, $contents)
    {
        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0755, true);
        }
        
        if ($this->filesystem->exists($path) && !($this->options['force'] ?? false)) {
            $this->error("File [{$path}] already exists!");
            return false;
        }
        
        $this->filesystem->put($path, $contents);
        
        return true;
    }
    
    /**
     * Parse schema string into an array of fields.
     *
     * @param string $schema
     * @return array
     */
    protected function parseSchema($schema)
    {
        if (empty($schema)) {
            return [];
        }
        
        $fields = [];
        
        $parts = explode(',', $schema);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            if (empty($part)) {
                continue;
            }
            
            $fieldParts = explode(':', $part);
            
            $name = trim($fieldParts[0]);
            $type = trim($fieldParts[1] ?? 'string');
            
            $fields[] = [
                'name' => $name,
                'type' => $type,
            ];
        }
        
        return $fields;
    }
    
    /**
     * Get foreign keys from fields.
     *
     * @param array $fields
     * @return array
     */
    protected function getForeignKeys($fields)
    {
        $foreignKeys = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            if ($type === 'foreignId' || Str::endsWith($name, '_id')) {
                $relatedModel = Str::studly(str_replace('_id', '', $name));
                
                $foreignKeys[] = [
                    'name' => $name,
                    'model' => $relatedModel,
                ];
            }
        }
        
        return $foreignKeys;
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
        } else {
            echo $message . PHP_EOL;
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
        } else {
            echo $message . PHP_EOL;
        }
    }
    
    /**
     * Generate the entity.
     *
     * @return bool
     */
    abstract public function generate();
} 