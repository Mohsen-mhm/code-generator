<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;

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
        $force = $this->option('force');
        $api = $this->option('api');
        $all = $this->option('all');

        if ($all || $this->option('model')) {
            $this->call('generate:model', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('controller')) {
            $this->call('generate:controller', [
                'name' => $name,
                '--schema' => $schema,
                '--api' => $api,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('migration')) {
            $this->call('generate:migration', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('factory')) {
            $this->call('generate:factory', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('seeder')) {
            $this->call('generate:seeder', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('resource')) {
            $this->call('generate:resource', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('request')) {
            $this->call('generate:request', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('test')) {
            $this->call('generate:test', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        if ($all || $this->option('view')) {
            $this->call('generate:views', [
                'name' => $name,
                '--schema' => $schema,
                '--force' => $force,
            ]);
        }

        $this->info('Code generation completed!');
    }
} 