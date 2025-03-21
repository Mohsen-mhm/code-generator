<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class ModelGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->name;
        $modelNamespace = $this->getNamespace('model');
        $modelPath = $this->getPath('models') . '/' . $modelName . '.php';
        
        $tableName = $this->options['table'] ?? Str::snake(Str::pluralStudly($modelName));
        
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'namespace' => $modelNamespace,
            'class' => $modelName,
            'table' => $tableName,
            'fillable' => $this->generateFillable($fields),
            'casts' => $this->generateCasts($fields),
            'relationships' => $this->generateRelationships($fields),
            'imports' => $this->generateImports($fields),
            'traits' => $this->generateTraits($fields),
            'timestamps' => config('code-generator.models.timestamps') ? 'public $timestamps = true;' : 'public $timestamps = false;',
            'softDeletesImport' => config('code-generator.models.soft_deletes') ? 'use Illuminate\\Database\\Eloquent\\SoftDeletes;' : '',
            'softDeletes' => config('code-generator.models.soft_deletes') ? ', SoftDeletes' : '',
        ];
        
        $contents = $this->getStubContents('model', $replacements);
        
        if ($this->writeFile($modelPath, $contents)) {
            $this->info("Model [{$modelName}] created successfully.");
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate fillable attributes.
     *
     * @param array $fields
     * @return string
     */
    protected function generateFillable($fields)
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
    
    /**
     * Generate casts attributes.
     *
     * @param array $fields
     * @return string
     */
    protected function generateCasts($fields)
    {
        if (empty($fields)) {
            return "[]";
        }
        
        $casts = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys
            if ($name === 'id') {
                continue;
            }
            
            // Add type-specific casts
            switch ($type) {
                case 'boolean':
                    $casts[] = "'{$name}' => 'boolean'";
                    break;
                case 'integer':
                case 'bigInteger':
                case 'smallInteger':
                case 'tinyInteger':
                    $casts[] = "'{$name}' => 'integer'";
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $casts[] = "'{$name}' => 'float'";
                    break;
                case 'date':
                    $casts[] = "'{$name}' => 'date'";
                    break;
                case 'dateTime':
                case 'timestamp':
                    $casts[] = "'{$name}' => 'datetime'";
                    break;
                case 'json':
                case 'jsonb':
                    $casts[] = "'{$name}' => 'array'";
                    break;
            }
        }
        
        if (empty($casts)) {
            return "[]";
        }
        
        return "[" . implode(", ", $casts) . "]";
    }
    
    /**
     * Generate relationships.
     *
     * @param array $fields
     * @return string
     */
    protected function generateRelationships($fields)
    {
        if (empty($fields)) {
            return "";
        }
        
        $relationships = [];
        $tableName = Str::snake(Str::pluralStudly($this->name));
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            if ($type === 'foreignId' || Str::endsWith($name, '_id')) {
                $relatedModel = Str::studly(str_replace('_id', '', $name));
                $relationName = Str::camel($relatedModel);
                
                $relationships[] = <<<PHP
    /**
     * Get the {$relationName} that owns the {$this->name}.
     */
    public function {$relationName}()
    {
        return \$this->belongsTo({$relatedModel}::class);
    }
PHP;
            }
        }
        
        return implode("\n\n", $relationships);
    }
    
    /**
     * Generate imports.
     *
     * @param array $fields
     * @return string
     */
    protected function generateImports($fields)
    {
        if (empty($fields)) {
            return "";
        }
        
        $imports = [];
        $modelNamespace = $this->getNamespace('model');
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            if ($type === 'foreignId' || Str::endsWith($name, '_id')) {
                $relatedModel = Str::studly(str_replace('_id', '', $name));
                $imports[] = "use {$modelNamespace}\\{$relatedModel};";
            }
        }
        
        if (empty($imports)) {
            return "";
        }
        
        return implode("\n", array_unique($imports));
    }
    
    /**
     * Generate traits.
     *
     * @param array $fields
     * @return string
     */
    protected function generateTraits($fields)
    {
        $traits = [];
        
        // Check if we need SoftDeletes
        foreach ($fields as $field) {
            if ($field['name'] === 'deleted_at') {
                $traits[] = "use SoftDeletes;";
                break;
            }
        }
        
        if (empty($traits)) {
            return "";
        }
        
        return implode("\n    ", $traits);
    }
} 
} 