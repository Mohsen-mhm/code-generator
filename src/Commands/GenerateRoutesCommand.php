<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\RoutesGenerator;

class GenerateRoutesCommand extends Command
{
    protected $signature = 'generate:routes {name} 
                            {--controller=} 
                            {--model=} 
                            {--api : Generate API routes} 
                            {--force : Overwrite existing routes}';

    protected $description = 'Generate routes for a resource';

    public function handle()
    {
        $name = $this->argument('name');
        $controller = $this->option('controller');
        $model = $this->option('model');
        $api = $this->option('api');
        $force = $this->option('force');
        
        $options = [
            'controller' => $controller,
            'model' => $model,
            'api' => $api,
            'force' => $force,
        ];
        
        app(RoutesGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 