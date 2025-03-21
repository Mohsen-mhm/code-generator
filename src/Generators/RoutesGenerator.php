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
        
        // Generate route definition
        $routeDefinition = $this->generateRouteDefinition($routeName, $controllerName, $isApi);
        $controllerClass = $this->getNamespace('controller') . '\\' . $controllerName;
        
        // Check if route already exists
        $routeContent = File::get($routesPath);
        
        // Check for exact route definition
        if (Str::contains($routeContent, $routeDefinition) && !($this->options['force'] ?? false)) {
            $this->info("Route for [{$routeName}] already exists. Use --force to overwrite.");
            return false;
        }
        
        // Check for controller reference to avoid duplicates
        if (Str::contains($routeContent, $controllerClass) && !($this->options['force'] ?? false)) {
            $this->info("Route for controller [{$controllerClass}] already exists. Use --force to overwrite.");
            return false;
        }
        
        // If force option is used, remove existing routes for this controller
        if ($this->options['force'] ?? false) {
            $routeContent = $this->removeExistingRoutes($routeContent, $routeName, $controllerClass);
        }
        
        // Check if we already have a middleware group for this type
        $middlewareGroup = $isApi ? "['api']" : "['web']";
        $hasExistingGroup = Str::contains($routeContent, "Route::middleware({$middlewareGroup})");
        
        if ($hasExistingGroup) {
            // Find the last middleware group of this type
            $lastGroupPos = strrpos($routeContent, "Route::middleware({$middlewareGroup})");
            $endPos = strpos($routeContent, "});", $lastGroupPos);
            
            if ($endPos !== false) {
                // Insert before the closing bracket of the last group
                $routeContent = substr_replace(
                    $routeContent,
                    "    {$routeDefinition}\n    ",
                    $endPos,
                    0
                );
            } else {
                // Fallback: add new group
                $routeContent .= "\n\nRoute::middleware({$middlewareGroup})->group(function () {\n    {$routeDefinition}\n});\n";
            }
        } else {
            // Create new group
            $routeContent .= "\n\nRoute::middleware({$middlewareGroup})->group(function () {\n    {$routeDefinition}\n});\n";
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
    
    /**
     * Remove existing routes for the given controller.
     *
     * @param string $content
     * @param string $routeName
     * @param string $controllerClass
     * @return string
     */
    protected function removeExistingRoutes($content, $routeName, $controllerClass)
    {
        // Pattern to match resource route definitions
        $patterns = [
            "/Route::resource\(['\"]" . preg_quote($routeName, '/') . "['\"],\s*" . preg_quote($controllerClass, '/') . ".*?\);/",
            "/Route::apiResource\(['\"]" . preg_quote($routeName, '/') . "['\"],\s*" . preg_quote($controllerClass, '/') . ".*?\);/",
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Clean up empty route groups
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
        
        return $content;
    }
} 