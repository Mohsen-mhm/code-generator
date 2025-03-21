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
        
        $fillable = $this->getFillableFields($fields);
        $casts = $this->getCastsFields($fields);
        
        $replacements = [
            'namespace' => $modelNamespace,
            'class' => $modelName,
            'table' => $tableName,
            'fillable' => $this->getFieldsAsString($fillable, 'fillable'),
            'casts' => $this->getFieldsAsString($casts, 'casts'),
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
     * Get fillable fields from schema.
     *
     * @param array $fields
     * @return array
     */
    protected function getFillableFields($fields)
    {
        if (!config('code-generator.models.fillable', true)) {
            return [];
        }
        
        $fillable = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $fillable[] = [
                'name' => $name,
            ];
        }
        
        return $fillable;
    }
    
    /**
     * Get casts fields from schema.
     *
     * @param array $fields
     * @return array
     */
    protected function getCastsFields($fields)
    {
        if (!config('code-generator.models.casts', true)) {
            return [];
        }
        
        $casts = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $castType = $this->getCastType($type);
            
            if ($castType) {
                $casts[] = [
                    'name' => $name,
                    'type' => $castType,
                ];
            }
        }
        
        return $casts;
    }
    
    /**
     * Get cast type for a field type.
     *
     * @param string $type
     * @return string|null
     */
    protected function getCastType($type)
    {
        $castMap = [
            'integer' => 'integer',
            'bigInteger' => 'integer',
            'smallInteger' => 'integer',
            'tinyInteger' => 'integer',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal',
            'boolean' => 'boolean',
            'date' => 'date',
            'dateTime' => 'datetime',
            'timestamp' => 'timestamp',
            'time' => 'string',
            'json' => 'array',
            'jsonb' => 'array',
        ];
        
        return $castMap[$type] ?? null;
    }
} 