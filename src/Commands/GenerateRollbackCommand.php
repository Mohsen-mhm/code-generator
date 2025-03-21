<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:rollback {name} 
                            {--all : Rollback all files} 
                            {--controller : Rollback controller} 
                            {--model : Rollback model} 
                            {--migration : Rollback migration} 
                            {--factory : Rollback factory} 
                            {--seeder : Rollback seeder} 
                            {--resource : Rollback resource} 
                            {--request : Rollback form request} 
                            {--test : Rollback test} 
                            {--view : Rollback views}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback generated files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $all = $this->option('all');

        if ($all || $this->option('model')) {
            $this->rollbackModel($name);
        }

        if ($all || $this->option('controller')) {
            $this->rollbackController($name);
        }

        if ($all || $this->option('migration')) {
            $this->rollbackMigration($name);
        }

        if ($all || $this->option('factory')) {
            $this->rollbackFactory($name);
        }

        if ($all || $this->option('seeder')) {
            $this->rollbackSeeder($name);
        }

        if ($all || $this->option('resource')) {
            $this->rollbackResource($name);
        }

        if ($all || $this->option('request')) {
            $this->rollbackRequest($name);
        }

        if ($all || $this->option('test')) {
            $this->rollbackTest($name);
        }

        if ($all || $this->option('view')) {
            $this->rollbackViews($name);
        }

        $this->info('Rollback completed!');
    }

    /**
     * Rollback a model.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackModel($name)
    {
        $path = app_path('Models/' . $name . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Model [{$name}] rolled back successfully.");
        } else {
            $this->warn("Model [{$name}] not found.");
        }
    }

    /**
     * Rollback a controller.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackController($name)
    {
        $controllerName = $name . 'Controller';
        $path = app_path('Http/Controllers/' . $controllerName . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Controller [{$controllerName}] rolled back successfully.");
        } else {
            $this->warn("Controller [{$controllerName}] not found.");
        }
    }

    /**
     * Rollback a migration.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackMigration($name)
    {
        $tableName = Str::snake(Str::pluralStudly($name));
        $migrationPath = database_path('migrations');
        
        $migrations = File::glob($migrationPath . '/*_create_' . $tableName . '_table.php');
        
        if (!empty($migrations)) {
            foreach ($migrations as $migration) {
                File::delete($migration);
                $this->info("Migration [" . basename($migration) . "] rolled back successfully.");
            }
        } else {
            $this->warn("Migration for [{$tableName}] not found.");
        }
    }

    /**
     * Rollback a factory.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackFactory($name)
    {
        $factoryName = $name . 'Factory';
        $path = database_path('factories/' . $factoryName . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Factory [{$factoryName}] rolled back successfully.");
        } else {
            $this->warn("Factory [{$factoryName}] not found.");
        }
    }

    /**
     * Rollback a seeder.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackSeeder($name)
    {
        $seederName = $name . 'Seeder';
        $path = database_path('seeders/' . $seederName . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Seeder [{$seederName}] rolled back successfully.");
        } else {
            $this->warn("Seeder [{$seederName}] not found.");
        }
    }

    /**
     * Rollback a resource.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackResource($name)
    {
        $resourceName = $name . 'Resource';
        $path = app_path('Http/Resources/' . $resourceName . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Resource [{$resourceName}] rolled back successfully.");
        } else {
            $this->warn("Resource [{$resourceName}] not found.");
        }
    }

    /**
     * Rollback a request.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackRequest($name)
    {
        $requestName = $name . 'Request';
        $path = app_path('Http/Requests/' . $requestName . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Request [{$requestName}] rolled back successfully.");
        } else {
            $this->warn("Request [{$requestName}] not found.");
        }
    }

    /**
     * Rollback a test.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackTest($name)
    {
        $testName = $name . 'Test';
        $path = base_path('tests/Feature/' . $testName . '.php');
        
        if (File::exists($path)) {
            File::delete($path);
            $this->info("Test [{$testName}] rolled back successfully.");
        } else {
            $this->warn("Test [{$testName}] not found.");
        }
    }

    /**
     * Rollback views.
     *
     * @param string $name
     * @return void
     */
    protected function rollbackViews($name)
    {
        $viewName = Str::kebab(Str::pluralStudly($name));
        $viewPath = resource_path('views/' . $viewName);
        
        if (File::isDirectory($viewPath)) {
            File::deleteDirectory($viewPath);
            $this->info("Views for [{$name}] rolled back successfully.");
        } else {
            $this->warn("Views for [{$name}] not found.");
        }
    }
} 