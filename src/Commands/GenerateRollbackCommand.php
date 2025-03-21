<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateRollbackCommand extends Command
{
    protected $signature = 'generate:rollback {name} 
                            {--all : Delete all generated files} 
                            {--model : Delete model} 
                            {--controller : Delete controller} 
                            {--migration : Delete migration} 
                            {--factory : Delete factory}
                            {--resource : Delete API resource} 
                            {--livewire : Delete Livewire component} 
                            {--test : Delete tests} 
                            {--routes : Remove routes}
                            {--views : Delete views}
                            {--force : Force deletion without confirmation}';

    protected $description = 'Rollback and delete generated files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $all = $this->option('all');
        $force = $this->option('force');
        
        if (!$force) {
            if (!$this->confirm("Are you sure you want to delete generated files for '{$name}'? This action cannot be undone.", false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $deleted = [];
        
        // Delete model
        if ($all || $this->option('model')) {
            $modelName = Str::studly($name);
            $modelPath = $this->getModelPath($modelName);
            
            if (File::exists($modelPath)) {
                File::delete($modelPath);
                $deleted[] = "Model: {$modelPath}";
            }
        }
        
        // Delete controller
        if ($all || $this->option('controller')) {
            $controllerName = Str::studly($name) . 'Controller';
            $controllerPath = $this->getControllerPath($controllerName);
            
            if (File::exists($controllerPath)) {
                File::delete($controllerPath);
                $deleted[] = "Controller: {$controllerPath}";
            }
        }
        
        // Delete migration
        if ($all || $this->option('migration')) {
            $tableName = Str::snake(Str::pluralStudly($name));
            $migrationPattern = database_path("migrations/*_create_{$tableName}_table.php");
            $migrationFiles = glob($migrationPattern);
            
            foreach ($migrationFiles as $migrationFile) {
                File::delete($migrationFile);
                $deleted[] = "Migration: {$migrationFile}";
            }
        }
        
        // Delete factory
        if ($all || $this->option('factory')) {
            $factoryName = Str::studly($name) . 'Factory';
            $factoryPath = database_path("factories/{$factoryName}.php");
            
            if (File::exists($factoryPath)) {
                File::delete($factoryPath);
                $deleted[] = "Factory: {$factoryPath}";
            }
        }
        
        // Delete resource
        if ($all || $this->option('resource')) {
            $resourceName = Str::studly($name) . 'Resource';
            $resourcePath = app_path("Http/Resources/{$resourceName}.php");
            
            if (File::exists($resourcePath)) {
                File::delete($resourcePath);
                $deleted[] = "Resource: {$resourcePath}";
            }
        }
        
        // Delete Livewire component
        if ($all || $this->option('livewire')) {
            $livewireName = Str::studly($name);
            $livewirePath = app_path("Livewire/{$livewireName}.php");
            
            if (File::exists($livewirePath)) {
                File::delete($livewirePath);
                $deleted[] = "Livewire: {$livewirePath}";
            }
            
            $livewireViewPath = resource_path("views/livewire/" . Str::kebab($livewireName) . ".blade.php");
            
            if (File::exists($livewireViewPath)) {
                File::delete($livewireViewPath);
                $deleted[] = "Livewire View: {$livewireViewPath}";
            }
        }
        
        // Delete tests
        if ($all || $this->option('test')) {
            $testName = Str::studly($name) . 'Test';
            $featureTestPath = base_path("tests/Feature/{$testName}.php");
            $unitTestPath = base_path("tests/Unit/{$testName}.php");
            
            if (File::exists($featureTestPath)) {
                File::delete($featureTestPath);
                $deleted[] = "Feature Test: {$featureTestPath}";
            }
            
            if (File::exists($unitTestPath)) {
                File::delete($unitTestPath);
                $deleted[] = "Unit Test: {$unitTestPath}";
            }
        }
        
        // Delete views
        if ($all || $this->option('views')) {
            $viewName = Str::kebab(Str::pluralStudly($name));
            $viewPath = resource_path("views/{$viewName}");
            
            if (File::isDirectory($viewPath)) {
                File::deleteDirectory($viewPath);
                $deleted[] = "Views: {$viewPath}";
            }
        }
        
        // Remove routes
        if ($all || $this->option('routes')) {
            $resourceName = Str::kebab(Str::pluralStudly($name));
            $controllerName = Str::studly($name) . 'Controller';
            
            $webRoutesPath = base_path('routes/web.php');
            $apiRoutesPath = base_path('routes/api.php');
            
            if (File::exists($webRoutesPath)) {
                $this->removeRoutes($webRoutesPath, $resourceName, $controllerName);
                $this->cleanEmptyRouteGroups($webRoutesPath);
                $deleted[] = "Routes: Removed from web.php";
            }
            
            if (File::exists($apiRoutesPath)) {
                $this->removeRoutes($apiRoutesPath, $resourceName, $controllerName);
                $this->cleanEmptyRouteGroups($apiRoutesPath);
                $deleted[] = "Routes: Removed from api.php";
            }
        }
        
        if (empty($deleted)) {
            $this->info("No files found to delete for '{$name}'.");
        } else {
            $this->info("The following files were deleted:");
            foreach ($deleted as $file) {
                $this->line("  - {$file}");
            }
        }
        
        return 0;
    }
    
    /**
     * Get the path to the model file.
     */
    protected function getModelPath($modelName)
    {
        return app_path("Models/{$modelName}.php");
    }
    
    /**
     * Get the path to the controller file.
     */
    protected function getControllerPath($controllerName)
    {
        return app_path("Http/Controllers/{$controllerName}.php");
    }
    
    /**
     * Remove routes from a routes file.
     */
    protected function removeRoutes($routesPath, $resourceName, $controllerName)
    {
        $content = File::get($routesPath);
        
        // Pattern to match resource route definitions
        $patterns = [
            "/Route::resource\(['\"]" . preg_quote($resourceName, '/') . "['\"],.*?{$controllerName}.*?\);/",
            "/Route::apiResource\(['\"]" . preg_quote($resourceName, '/') . "['\"],.*?{$controllerName}.*?\);/",
        ];
        
        // Also try to remove the entire group if it only contains our route
        $groupPatterns = [
            "/Route::prefix\(['\"].*?['\"]\)->middleware\(\[.*?\]\)->group\(function \(\) {[\s\n]*Route::(?:api)?resource\(['\"]" . preg_quote($resourceName, '/') . "['\"],.*?{$controllerName}.*?\);[\s\n]*}\);/",
            "/Route::middleware\(\[.*?\]\)->group\(function \(\) {[\s\n]*Route::(?:api)?resource\(['\"]" . preg_quote($resourceName, '/') . "['\"],.*?{$controllerName}.*?\);[\s\n]*}\);/",
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        foreach ($groupPatterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Remove any double blank lines
        $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);
        
        File::put($routesPath, $content);
    }
    
    /**
     * Clean up empty route groups.
     */
    protected function cleanEmptyRouteGroups($routesPath)
    {
        $content = File::get($routesPath);
        
        // Pattern to match empty route groups
        $emptyGroupPatterns = [
            "/Route::middleware\(\[.*?\]\)->group\(function \(\) {\s*\n\s*}\);/",
            "/Route::prefix\(['\"].*?['\"]\)->middleware\(\[.*?\]\)->group\(function \(\) {\s*\n\s*}\);/",
            "/Route::group\(\[.*?\], function \(\) {\s*\n\s*}\);/",
        ];
        
        foreach ($emptyGroupPatterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Remove any double blank lines
        $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);
        
        File::put($routesPath, $content);
    }
} 