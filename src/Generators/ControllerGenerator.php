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
        ];
        
        $stubName = $isApi ? 'api-controller' : 'controller';
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
    
    /**
     * Generate validation rules based on schema.
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
            $modifiers = $field['modifiers'] ?? [];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $fieldRules = [];
            
            // Required by default unless nullable
            if (!in_array('nullable', $modifiers)) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            // Add type-specific rules
            switch ($type) {
                case 'string':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
                case 'text':
                case 'mediumText':
                case 'longText':
                    $fieldRules[] = 'string';
                    break;
                case 'integer':
                case 'bigInteger':
                case 'smallInteger':
                case 'tinyInteger':
                    $fieldRules[] = 'integer';
                    break;
                case 'float':
                case 'double':
                case 'decimal':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'dateTime':
                case 'timestamp':
                    $fieldRules[] = 'date';
                    break;
                case 'time':
                    $fieldRules[] = 'date_format:H:i:s';
                    break;
                case 'json':
                case 'jsonb':
                    $fieldRules[] = 'array';
                    break;
                case 'foreignId':
                    $fieldRules[] = 'exists:' . Str::snake(Str::pluralStudly(str_replace('_id', '', $name))) . ',id';
                    break;
            }
            
            // Add special rules based on field name
            if (Str::contains($name, 'email')) {
                $fieldRules[] = 'email';
            }
            
            if (Str::contains($name, 'password')) {
                $fieldRules[] = 'min:8';
            }
            
            if (Str::endsWith($name, '_id') && !in_array('exists:', $fieldRules)) {
                $tableName = Str::snake(Str::pluralStudly(str_replace('_id', '', $name)));
                $fieldRules[] = "exists:{$tableName},id";
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
            return '';
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
            return '';
        }
        
        $variables = [];
        
        foreach ($foreignKeys as $foreignKey) {
            $variables[] = "\${$foreignKey['name']}Options = {$foreignKey['model']}::pluck('name', 'id')->toArray();";
        }
        
        return implode("\n        ", array_unique($variables));
    }
} 