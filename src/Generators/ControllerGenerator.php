<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class ControllerGenerator extends BaseGenerator
{
    public function generate()
    {
        $controllerName = $this->name;
        
        if (!Str::endsWith($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }
        
        $controllerNamespace = $this->getNamespace('controller');
        $controllerPath = $this->getPath('controllers') . '/' . $controllerName . '.php';
        
        $modelName = $this->options['model'] ?? str_replace('Controller', '', $controllerName);
        $modelNamespace = $this->getNamespace('model') . '\\' . $modelName;
        $modelVariable = Str::camel($modelName);
        $modelVariablePlural = Str::camel(Str::pluralStudly($modelName));
        $viewPath = Str::kebab(Str::pluralStudly($modelName));
        
        $isApi = $this->options['api'] ?? false;
        $resourceName = $this->options['resource'] ?? $modelName . 'Resource';
        $resourceNamespace = $this->getNamespace('resource') . '\\' . $resourceName;
        
        $stubName = $isApi ? 'api-controller' : 'controller';
        
        $replacements = [
            'namespace' => $controllerNamespace,
            'class' => $controllerName,
            'model' => $modelName,
            'modelNamespace' => $modelNamespace,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'viewPath' => $viewPath,
            'useResource' => $isApi ? "use {$resourceNamespace};" : '',
            'resourceName' => $resourceName,
        ];
        
        $contents = $this->getStubContents($stubName, $replacements);
        
        if ($this->writeFile($controllerPath, $contents)) {
            $this->info("Controller [{$controllerName}] created successfully.");
            
            // Generate routes if needed
            if ($this->options['routes'] ?? config('code-generator.routes.enabled', true)) {
                app(RoutesGenerator::class)
                    ->setCommand($this->command)
                    ->setName($modelName)
                    ->setOptions([
                        'controller' => $controllerName,
                        'api' => $isApi,
                    ])
                    ->generate();
            }
            
            return true;
        }
        
        return false;
    }
} 