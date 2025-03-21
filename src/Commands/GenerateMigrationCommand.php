<?php

namespace YourName\CodeGenerator\Commands;

use Illuminate\Console\Command;
use YourName\CodeGenerator\Generators\MigrationGenerator;

class GenerateMigrationCommand extends Command
{
    protected $signature = 'generate:migration {name} 
                            {--schema=} 
                            {--model=} 
                            {--table=} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a migration file';

    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $model = $this->option('model');
        $table = $this->option('table');
        $force = $this->option('force');
        
        $options = [
            'model' => $model,
            'table' => $table,
            'force' => $force,
        ];
        
        app(MigrationGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 