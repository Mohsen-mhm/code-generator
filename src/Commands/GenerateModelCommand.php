<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\ModelGenerator;

class GenerateModelCommand extends Command
{
    protected $signature = 'generate:model {name} 
                            {--schema=} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a model class';

    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $force = $this->option('force');
        
        $options = [
            'force' => $force,
        ];
        
        app(ModelGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 