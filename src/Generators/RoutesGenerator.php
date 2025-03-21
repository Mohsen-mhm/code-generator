<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class RoutesGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->options['model'] ?? $this->name;
        $controllerName = $this->options['controller'] ?? $modelName . 'Controller';
        $routeName = Str::kebab(Str::pluralStudly($modelName));
        
        $isApi = $this->options['api'] ?? false;
        $routesFile = $isApi ? 'api.php' : 'web.php';
        $routesPath = base_path("routes/{$routesFile}");
        
        // Check if routes file exists
        if (!File::exists($routesPath)) {
            $this->error("Routes file [{$routesFile}] does not exist.");
            return false;
        }
        
        // Check if route already exists
        $routeContent = File::get($routesPath);
        $controllerClass = $this->getNamespace('controller') . '\\' . $controllerName;
        
        if (Str::contains($routeContent, $controllerClass) && !($this->options['force'] ?? false)) {
            $this->info("Routes for [{$controllerClass}] already exist. Use --force to overwrite.");
            return false;
        }
        
        // Generate route content
        $routeDefinition = $this->generateRouteDefinition($routeName, $controllerName, $isApi);
        
        // Check if we already have a middleware group for this type
        $middlewareGroup = $isApi ? "['api']" : "['web']";
        $groupPattern = "/Route::middleware\({$middlewareGroup}\)->group\(function \(\) {.*?}\);/s";
        
        if (preg_match($groupPattern, $routeContent)) {
            // Add to existing group
            $routeContent = preg_replace_callback(
                $groupPattern,
                function ($matches) use ($routeDefinition) {
                    // Check if the group is empty
                    if (strpos($matches[0], "function () {\n}") !== false) {
                        // Replace empty group with new content
                        return str_replace("function () {\n}", "function () {\n    {$routeDefinition}\n}", $matches[0]);
                    }
                    
                    // Insert before the closing bracket
                    $lastBrace = strrpos($matches[0], '}');
                    $before = substr($matches[0], 0, $lastBrace);
                    $after = substr($matches[0], $lastBrace);
                    
                    return $before . "    {$routeDefinition}\n" . $after;
                },
                $routeContent
            );
        } else {
            // Create new group
            $newGroup = "Route::middleware({$middlewareGroup})->group(function () {\n    {$routeDefinition}\n});\n";
            $routeContent .= "\n" . $newGroup;
        }
        
        // Write to file
        File::put($routesPath, $routeContent);
        
        $this->info("Routes for [{$controllerName}] added successfully.");
        return true;
    }
    
    /**
     * Generate route definition.
     *
     * @param string $routeName
     * @param string $controllerName
     * @param bool $isApi
     * @return string
     */
    protected function generateRouteDefinition($routeName, $controllerName, $isApi)
    {
        $controllerClass = $this->getNamespace('controller') . '\\' . $controllerName . '::class';
        
        if ($isApi) {
            return "Route::apiResource('{$routeName}', {$controllerClass});";
        }
        
        return "Route::resource('{$routeName}', {$controllerClass});";
    }
} 