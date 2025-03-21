<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\ResourceGenerator;

class GenerateResourceCommand extends Command
{
    protected $signature = 'generate:resource {name} 
                            {--schema=} 
                            {--model=} 
                            {--collection : Generate a resource collection} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate an API resource';

    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $model = $this->option('model');
        $collection = $this->option('collection');
        $force = $this->option('force');
        
        $options = [
            'model' => $model,
            'collection' => $collection,
            'force' => $force,
        ];
        
        app(ResourceGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 