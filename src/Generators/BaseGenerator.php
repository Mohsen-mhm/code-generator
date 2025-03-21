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
        $config = config('code-generator.namespaces');
        
        switch ($type) {
            case 'model':
                return $config['model'] ?? 'App\\Models';
            case 'controller':
                return $config['controller'] ?? 'App\\Http\\Controllers';
            case 'request':
                return $config['request'] ?? 'App\\Http\\Requests';
            case 'resource':
                return $config['resource'] ?? 'App\\Http\\Resources';
            case 'factory':
                return $config['factory'] ?? 'Database\\Factories';
            case 'seeder':
                return $config['seeder'] ?? 'Database\\Seeders';
            case 'test':
                return $config['test'] ?? 'Tests\\Feature';
            default:
                return 'App';
        }
    }
    
    /**
     * Get the path for a given type.
     *
     * @param string $type
     * @return string
     */
    protected function getPath($type)
    {
        $config = config('code-generator.paths');
        
        switch ($type) {
            case 'model':
                return app_path($config['model'] ?? 'Models');
            case 'controller':
                return app_path($config['controller'] ?? 'Http/Controllers');
            case 'request':
                return app_path($config['request'] ?? 'Http/Requests');
            case 'resource':
                return app_path($config['resource'] ?? 'Http/Resources');
            case 'factory':
                return database_path($config['factory'] ?? 'factories');
            case 'seeder':
                return database_path($config['seeder'] ?? 'seeders');
            case 'migration':
                return database_path($config['migration'] ?? 'migrations');
            case 'test':
                return base_path($config['test'] ?? 'tests/Feature');
            case 'views':
                return resource_path($config['views'] ?? 'views');
            default:
                return app_path();
        }
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
        $stubPath = __DIR__ . '/../Stubs/' . $stub . '.stub';
        
        // Check if the stub exists in the package
        if (!file_exists($stubPath)) {
            // Try to find the stub in the vendor directory
            $stubPath = base_path('vendor/mohsen-mhm/code-generator/src/Stubs/' . $stub . '.stub');
            
            // If still not found, try the published stubs
            if (!file_exists($stubPath)) {
                $stubPath = base_path('/src/Stubs/code-generator/' . $stub . '.stub');
            }
        }
        
        if (!file_exists($stubPath)) {
            throw new \Exception("Stub file [{$stub}.stub] not found.");
        }
        
        $contents = file_get_contents($stubPath);
        
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
        if (file_exists($path) && !($this->options['force'] ?? false)) {
            $this->error("File [{$path}] already exists. Use --force to overwrite.");
            return false;
        }
        
        $directory = dirname($path);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($path, $contents);
        
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
        
        $schemaFields = explode(',', $schema);
        foreach ($schemaFields as $field) {
            $fieldParts = explode(':', $field);
            $fieldName = trim($fieldParts[0]);
            $fieldType = isset($fieldParts[1]) ? trim($fieldParts[1]) : 'string';
            
            $fields[] = [
                'name' => $fieldName,
                'type' => $fieldType,
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
            if (Str::endsWith($field['name'], '_id') || $field['type'] === 'foreignId') {
                $relation = Str::beforeLast($field['name'], '_id');
                $model = Str::studly($relation);
                
                $foreignKeys[] = [
                    'name' => $field['name'],
                    'relation' => $relation,
                    'model' => $model,
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