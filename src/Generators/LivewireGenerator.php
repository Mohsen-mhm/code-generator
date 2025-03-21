<?php

namespace YourName\CodeGenerator\Generators;

use Illuminate\Support\Str;

class LivewireGenerator extends BaseGenerator
{
    public function generate()
    {
        $componentName = $this->name;
        $componentClass = Str::studly($componentName);
        $componentNamespace = $this->getNamespace('livewire');
        $componentPath = $this->getPath('livewire') . '/' . $componentClass . '.php';
        
        $fields = $this->parseSchema($this->schema);
        $modelName = $this->options['model'] ?? Str::singular($componentClass);
        $modelNamespace = $this->getNamespace('model') . '\\' . $modelName;
        
        // Generate the component class
        $replacements = [
            'namespace' => $componentNamespace,
            'class' => $componentClass,
            'model' => $modelName,
            'modelNamespace' => $modelNamespace,
            'modelVariable' => Str::camel($modelName),
            'properties' => $this->generateProperties($fields),
            'rules' => $this->generateRules($fields),
            'resetProperties' => $this->generateResetProperties($fields),
        ];
        
        $contents = $this->getStubContents('livewire', $replacements);
        
        if ($this->writeFile($componentPath, $contents)) {
            $this->info("Livewire component [{$componentClass}] created successfully.");
            
            // Generate the view if needed
            if (config('code-generator.livewire.include_views')) {
                $this->generateView($componentName, $fields);
            }
            
            return true;
        }
        
        return false;
    }
    
    protected function generateProperties($fields)
    {
        if (empty($fields)) {
            return '// No properties defined';
        }
        
        $properties = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $properties[] = "public \${$name};";
        }
        
        return implode(PHP_EOL . '    ', $properties);
    }
    
    protected function generateRules($fields)
    {
        if (empty($fields)) {
            return 'return [];';
        }
        
        $rules = $this->getFieldsAsString($fields, 'validation');
        
        return "return [
            {$rules}
        ];";
    }
    
    protected function generateResetProperties($fields)
    {
        if (empty($fields)) {
            return '// No properties to reset';
        }
        
        $resets = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $resets[] = "\$this->{$name} = null;";
        }
        
        return implode(PHP_EOL . '        ', $resets);
    }
    
    protected function generateView($componentName, $fields)
    {
        $viewName = Str::kebab($componentName);
        $viewPath = resource_path('views/livewire/' . $viewName . '.blade.php');
        
        $formFields = $this->getFieldsAsString($fields, 'form');
        $tableColumns = $this->getFieldsAsString($fields, 'table');
        
        $replacements = [
            'formFields' => $formFields,
            'tableColumns' => $tableColumns,
            'modelVariable' => Str::camel(Str::singular($componentName)),
            'title' => Str::title(str_replace('-', ' ', $viewName)),
        ];
        
        $contents = $this->getStubContents('livewire-view', $replacements);
        
        if ($this->writeFile($viewPath, $contents)) {
            $this->info("Livewire view [{$viewName}.blade.php] created successfully.");
            return true;
        }
        
        return false;
    }
} 