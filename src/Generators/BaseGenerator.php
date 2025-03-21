<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use MohsenMhm\CodeGenerator\Traits\GeneratesFiles;
use MohsenMhm\CodeGenerator\Traits\InteractsWithUser;
use MohsenMhm\CodeGenerator\Traits\ParsesSchemas;

abstract class BaseGenerator
{
    use GeneratesFiles, InteractsWithUser, ParsesSchemas;

    protected $filesystem;
    protected $name;
    protected $options;
    protected $schema;
    protected $command;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    protected function getStubPath($stub)
    {
        $customStubPath = config('code-generator.stubs.custom_path') . "/{$stub}.stub";
        
        if (config('code-generator.stubs.use_custom') && file_exists($customStubPath)) {
            return $customStubPath;
        }
        
        return __DIR__ . "/../Stubs/{$stub}.stub";
    }

    protected function getNamespace($type)
    {
        $baseNamespace = config('code-generator.namespace');
        
        switch ($type) {
            case 'model':
                return $baseNamespace . '\\Models';
            case 'controller':
                return $baseNamespace . '\\Http\\Controllers';
            case 'livewire':
                return $baseNamespace . '\\Livewire';
            case 'resource':
                return $baseNamespace . '\\Http\\Resources';
            case 'test':
                return 'Tests';
            default:
                return $baseNamespace;
        }
    }

    protected function getPath($type)
    {
        return config("code-generator.paths.{$type}");
    }

    abstract public function generate();
} 