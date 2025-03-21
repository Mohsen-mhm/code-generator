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
        
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'namespace' => $modelNamespace,
            'class' => $modelName,
            'table' => Str::snake(Str::pluralStudly($modelName)),
            'fillable' => $this->getFieldsAsString($fields, 'fillable'),
            'casts' => $this->getFieldsAsString($fields, 'casts'),
            'timestamps' => config('code-generator.models.timestamps') ? 'public $timestamps = true;' : 'public $timestamps = false;',
            'softDeletes' => config('code-generator.models.soft_deletes') ? "use SoftDeletes;\n    " : '',
            'softDeletesImport' => config('code-generator.models.soft_deletes') ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '',
        ];
        
        $contents = $this->getStubContents('model', $replacements);
        
        if ($this->writeFile($modelPath, $contents)) {
            $this->info("Model [{$modelName}] created successfully.");
            return true;
        }
        
        return false;
    }
} 