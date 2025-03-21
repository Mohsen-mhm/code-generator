<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\TestGenerator;

class GenerateTestCommand extends Command
{
    protected $signature = 'generate:test {name} 
                            {--model=} 
                            {--feature : Generate a feature test} 
                            {--unit : Generate a unit test} 
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a test class';

    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->option('model');
        $feature = $this->option('feature');
        $unit = $this->option('unit');
        $force = $this->option('force');
        
        // Default to feature test if neither is specified
        if (!$feature && !$unit) {
            $feature = true;
        }
        
        $options = [
            'model' => $model,
            'feature' => $feature,
            'force' => $force,
        ];
        
        app(TestGenerator::class)
            ->setCommand($this)
            ->setName($name)
            ->setOptions($options)
            ->generate();
        
        return 0;
    }
} 