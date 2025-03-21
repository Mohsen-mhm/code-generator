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
        
        $modelName = $this->options['model'] ?? str_replace('Controller', '', $controllerName);
        $modelNamespace = $this->getNamespace('model');
        $controllerNamespace = $this->getNamespace('controller');
        
        $controllerPath = $this->getPath('controllers') . '/' . $controllerName . '.php';
        
        $isApi = $this->options['api'] ?? false;
        $resourceName = $modelName . 'Resource';
        $resourceNamespace = $this->getNamespace('resource');
        
        $viewName = Str::kebab(Str::pluralStudly($modelName));
        $modelVariableSingular = Str::camel($modelName);
        $modelVariablePlural = Str::camel(Str::pluralStudly($modelName));
        
        // Parse schema to find foreign keys
        $fields = $this->parseSchema($this->schema);
        $foreignKeys = $this->getForeignKeys($fields);
        
        $replacements = [
            'namespace' => $controllerNamespace,
            'class' => $controllerName,
            'modelName' => $modelName,
            'modelNamespace' => $modelNamespace,
            'resourceName' => $resourceName,
            'resourceNamespace' => $resourceNamespace,
            'viewName' => $viewName,
            'modelVariableSingular' => $modelVariableSingular,
            'modelVariablePlural' => $modelVariablePlural,
            'validationRules' => $this->generateValidationRules($fields),
            'foreignKeyImports' => $this->generateForeignKeyImports($foreignKeys),
            'foreignKeyVariables' => $this->generateForeignKeyVariables($foreignKeys),
            'createViewParams' => $this->generateCreateViewParams($foreignKeys),
            'editViewParams' => $this->generateEditViewParams($modelVariableSingular, $foreignKeys),
            'fillableFields' => $this->generateFillableFields($fields),
        ];
        
        $stubName = $isApi ? 'api-controller' : 'controller';
        $contents = $this->getStubContents($stubName, $replacements);
        
        if ($this->writeFile($controllerPath, $contents)) {
            $this->info("Controller [{$controllerName}] created successfully.");
            
            // Generate routes if needed
            if ($this->options['routes'] ?? config('code-generator.routes.enabled', true)) {
                $generator = new RoutesGenerator(name: $modelName, options:[
                    'controller' => $controllerName,
                    'api' => $isApi,
                ]);
                $generator->setCommand($this->command);
                $generator->generate();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate validation rules for fields.
     *
     * @param array $fields
     * @return string
     */
    protected function generateValidationRules($fields)
    {
        if (empty($fields)) {
            return "[]";
        }
        
        $rules = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $fieldRules = [];
            
            if (Str::endsWith($name, '_id')) {
                $fieldRules[] = 'required';
                $fieldRules[] = 'exists:' . Str::plural(str_replace('_id', '', $name)) . ',id';
            } elseif ($type === 'string') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
            } elseif ($type === 'text' || $type === 'longText' || $type === 'mediumText') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'string';
            } elseif ($type === 'integer' || $type === 'bigInteger' || $type === 'smallInteger' || $type === 'tinyInteger') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'integer';
            } elseif ($type === 'decimal' || $type === 'float' || $type === 'double') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'numeric';
            } elseif ($type === 'boolean') {
                $fieldRules[] = 'boolean';
            } elseif ($type === 'date' || $type === 'dateTime' || $type === 'timestamp') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'date';
            } elseif ($type === 'time') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'date_format:H:i:s';
            } elseif ($type === 'year') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'date_format:Y';
            } elseif ($type === 'enum') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'string';
            } elseif ($type === 'json') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'json';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            $rules[] = "'{$name}' => [" . implode(', ', array_map(function ($rule) {
                return "'{$rule}'";
            }, $fieldRules)) . "]";
        }
        
        return "[\n            " . implode(",\n            ", $rules) . "\n        ]";
    }
    
    /**
     * Get foreign keys from schema.
     *
     * @param array $fields
     * @return array
     */
    protected function getForeignKeys($fields)
    {
        $foreignKeys = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            if ($type === 'foreignId' || Str::endsWith($name, '_id')) {
                $relatedModel = Str::studly(str_replace('_id', '', $name));
                $foreignKeys[] = [
                    'name' => $name,
                    'model' => $relatedModel,
                    'variable' => Str::camel(Str::pluralStudly($relatedModel)),
                ];
            }
        }
        
        return $foreignKeys;
    }
    
    /**
     * Generate foreign key imports.
     *
     * @param array $foreignKeys
     * @return string
     */
    protected function generateForeignKeyImports($foreignKeys)
    {
        if (empty($foreignKeys)) {
            return "";
        }
        
        $imports = [];
        $modelNamespace = $this->getNamespace('model');
        
        foreach ($foreignKeys as $foreignKey) {
            $imports[] = "use {$modelNamespace}\\{$foreignKey['model']};";
        }
        
        return implode("\n", array_unique($imports));
    }
    
    /**
     * Generate foreign key variables for controller methods.
     *
     * @param array $foreignKeys
     * @return string
     */
    protected function generateForeignKeyVariables($foreignKeys)
    {
        if (empty($foreignKeys)) {
            return "";
        }
        
        $variables = [];
        
        foreach ($foreignKeys as $foreignKey) {
            $model = $foreignKey['model'];
            $name = $foreignKey['name'];
            
            $variables[] = "\${$name}Options = {$model}::pluck('name', 'id')->toArray();";
        }
        
        return implode("\n        ", $variables);
    }
    
    /**
     * Generate create view parameters.
     *
     * @param array $foreignKeys
     * @return string
     */
    protected function generateCreateViewParams($foreignKeys)
    {
        if (empty($foreignKeys)) {
            return '';
        }
        
        $variables = [];
        foreach ($foreignKeys as $foreignKey) {
            $variables[] = "'{$foreignKey['name']}Options'";
        }
        
        return ', compact(' . implode(', ', $variables) . ')';
    }
    
    /**
     * Generate edit view parameters.
     *
     * @param string $modelVariable
     * @param array $foreignKeys
     * @return string
     */
    protected function generateEditViewParams($modelVariable, $foreignKeys)
    {
        $variables = ["'{$modelVariable}'"];
        
        if (!empty($foreignKeys)) {
            foreach ($foreignKeys as $foreignKey) {
                $variables[] = "'{$foreignKey['name']}Options'";
            }
        }
        
        return 'compact(' . implode(', ', $variables) . ')';
    }
    
    /**
     * Generate fillable fields for model.
     *
     * @param array $fields
     * @return string
     */
    protected function generateFillableFields($fields)
    {
        if (empty($fields)) {
            return "[]";
        }
        
        $fillable = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $fillable[] = "'{$name}'";
        }
        
        return "[" . implode(", ", $fillable) . "]";
    }
} 