<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\ViewGenerator;

class GenerateViewsCommand extends Command
{
    protected $signature = 'generate:views {name} 
                            {--schema=} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate views for a model';

    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        
        $options = [
            'force' => $this->option('force'),
        ];
        
        app(ViewGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 