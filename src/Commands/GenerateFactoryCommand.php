<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\FactoryGenerator;

class GenerateFactoryCommand extends Command
{
    protected $signature = 'generate:factory {name} 
                            {--schema=} 
                            {--model=} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a model factory';

    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $model = $this->option('model');
        $force = $this->option('force');
        
        $options = [
            'model' => $model,
            'force' => $force,
        ];
        
        app(FactoryGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 