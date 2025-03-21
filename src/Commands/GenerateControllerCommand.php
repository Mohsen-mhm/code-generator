<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\ControllerGenerator;

class GenerateControllerCommand extends Command
{
    protected $signature = 'generate:controller {name} 
                            {--model=} 
                            {--resource=} 
                            {--api : Generate an API controller} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a controller class';

    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->option('model');
        $resource = $this->option('resource');
        $api = $this->option('api');
        $force = $this->option('force');
        
        $options = [
            'model' => $model,
            'resource' => $resource,
            'api' => $api,
            'force' => $force,
        ];
        
        app(ControllerGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 