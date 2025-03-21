<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MohsenMhm\CodeGenerator\Generators\ControllerGenerator;
use MohsenMhm\CodeGenerator\Generators\LivewireGenerator;
use MohsenMhm\CodeGenerator\Generators\MigrationGenerator;
use MohsenMhm\CodeGenerator\Generators\ModelGenerator;
use MohsenMhm\CodeGenerator\Generators\ResourceGenerator;
use MohsenMhm\CodeGenerator\Generators\RoutesGenerator;
use MohsenMhm\CodeGenerator\Generators\TestGenerator;
use MohsenMhm\CodeGenerator\Generators\FactoryGenerator;
use MohsenMhm\CodeGenerator\Generators\ViewGenerator;
use MohsenMhm\CodeGenerator\Generators\RequestGenerator;
use MohsenMhm\CodeGenerator\Generators\SeederGenerator;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate {name} 
                            {--schema=} 
                            {--all : Generate all files} 
                            {--controller : Generate a controller} 
                            {--model : Generate a model} 
                            {--migration : Generate a migration} 
                            {--factory : Generate a factory} 
                            {--seeder : Generate a seeder} 
                            {--resource : Generate a resource} 
                            {--request : Generate a form request} 
                            {--test : Generate a test} 
                            {--view : Generate views} 
                            {--api : Generate API controller and resource} 
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate code files based on a schema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $options = [
            'force' => $this->option('force'),
            'api' => $this->option('api'),
            'model' => $name,
        ];

        $all = $this->option('all');

        if ($all || $this->option('model')) {
            $generator = new ModelGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('controller')) {
            $generator = new ControllerGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('migration')) {
            $generator = new MigrationGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('factory')) {
            $generator = new FactoryGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('seeder')) {
            $generator = new SeederGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('resource')) {
            $generator = new ResourceGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('request')) {
            $generator = new RequestGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('test')) {
            $generator = new TestGenerator($name, $schema, $options);
            $generator->generate();
        }

        if ($all || $this->option('view')) {
            $generator = new ViewGenerator($name, $schema, $options);
            $generator->generate();
        }

        $this->info('Code generation completed!');
    }
} 