<?php

namespace YourName\CodeGenerator\Commands;

use Illuminate\Console\Command;
use YourName\CodeGenerator\Generators\LivewireGenerator;

class GenerateLivewireCommand extends Command
{
    protected $signature = 'generate:livewire {name} 
                            {--schema=} 
                            {--model=} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a Livewire component';

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
        
        app(LivewireGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setSchema($schema)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 