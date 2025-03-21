<?php

namespace YourName\CodeGenerator\Generators;

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
        
        $modelName = $this->options['model'] ?? Str::singular(str_replace('Controller', '', $controllerName));
        $modelNamespace = $this->getNamespace('model') . '\\' . $modelName;
        $modelVariable = Str::camel($modelName);
        
        $resourceName = $this->options['resource'] ?? $modelName . 'Resource';
        $resourceNamespace = $this->getNamespace('resource') . '\\' . $resourceName;
        
        $isApi = $this->options['api'] ?? false;
        $stubName = $isApi ? 'api-controller' : 'controller';
        
        $replacements = [
            'namespace' => $controllerNamespace,
            'class' => $controllerName,
            'model' => $modelName,
            'modelNamespace' => $modelNamespace,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => Str::plural($modelVariable),
            'resourceName' => $resourceName,
            'resourceNamespace' => $resourceNamespace,
            'useResource' => $isApi ? "use {$resourceNamespace};" : '',
            'viewPath' => Str::kebab(Str::plural(str_replace('Controller', '', $controllerName))),
        ];
        
        $contents = $this->getStubContents($stubName, $replacements);
        
        if ($this->writeFile($controllerPath, $contents)) {
            $this->info("Controller [{$controllerName}] created successfully.");
            return true;
        }
        
        return false;
    }
} 