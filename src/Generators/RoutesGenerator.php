<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class RoutesGenerator extends BaseGenerator
{
    public function generate()
    {
        if (!config('code-generator.routes.enabled', true)) {
            return false;
        }
        
        $modelName = $this->options['model'] ?? $this->name;
        $controllerName = $this->options['controller'] ?? $modelName . 'Controller';
        $isApi = $this->options['api'] ?? false;
        
        $routeFile = $isApi 
            ? base_path(config('code-generator.routes.api_file', 'routes/api.php'))
            : base_path(config('code-generator.routes.file', 'routes/web.php'));
            
        $resourceName = Str::kebab(Str::pluralStudly($modelName));
        $controllerClass = $this->getNamespace('controller') . '\\' . $controllerName;
        
        // Remove App\ from the beginning if it exists
        $controllerClass = str_replace('App\\', '', $controllerClass);
        
        // Prepare route definition
        if ($isApi) {
            $prefix = config('code-generator.routes.api_version_prefix');
            $middleware = implode("', '", config('code-generator.routes.api_middleware', ['api']));
            
            $routeDefinition = PHP_EOL . "Route::prefix('{$prefix}')->middleware(['{$middleware}'])->group(function () {" . PHP_EOL;
            $routeDefinition .= "    Route::apiResource('{$resourceName}', \\{$controllerClass}::class);" . PHP_EOL;
            $routeDefinition .= "});" . PHP_EOL;
        } else {
            $middleware = implode("', '", config('code-generator.routes.middleware', ['web']));
            
            if (config('code-generator.routes.prefix')) {
                $routeDefinition = PHP_EOL . "Route::prefix('{$resourceName}')->middleware(['{$middleware}'])->group(function () {" . PHP_EOL;
                $routeDefinition .= "    Route::resource('{$resourceName}', \\{$controllerClass}::class);" . PHP_EOL;
                $routeDefinition .= "});" . PHP_EOL;
            } else {
                $routeDefinition = PHP_EOL . "Route::middleware(['{$middleware}'])->group(function () {" . PHP_EOL;
                $routeDefinition .= "    Route::resource('{$resourceName}', \\{$controllerClass}::class);" . PHP_EOL;
                $routeDefinition .= "});" . PHP_EOL;
            }
        }
        
        // Check if route file exists
        if (!$this->filesystem->exists($routeFile)) {
            $this->error("Route file {$routeFile} does not exist.");
            return false;
        }
        
        // Check if route already exists
        $routeContents = $this->filesystem->get($routeFile);
        if (Str::contains($routeContents, "Route::resource('{$resourceName}'") || 
            Str::contains($routeContents, "Route::apiResource('{$resourceName}'")) {
            $this->info("Routes for {$resourceName} already exist in {$routeFile}.");
            return false;
        }
        
        // Append route to file
        $this->filesystem->append($routeFile, $routeDefinition);
        $this->info("Routes for {$resourceName} added to {$routeFile}.");
        
        return true;
    }
} 