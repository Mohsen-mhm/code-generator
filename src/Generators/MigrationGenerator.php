<?php

namespace YourName\CodeGenerator\Generators;

use Illuminate\Support\Str;

class MigrationGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->options['model'] ?? $this->name;
        $tableName = $this->options['table'] ?? Str::snake(Str::pluralStudly($modelName));
        
        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "create_{$tableName}_table";
        $migrationPath = $this->getPath('migrations') . "/{$timestamp}_{$migrationName}.php";
        
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'class' => Str::studly($migrationName),
            'table' => $tableName,
            'fields' => $this->getFieldsAsString($fields, 'migration'),
            'timestamps' => config('code-generator.models.timestamps') ? '$table->timestamps();' : '// No timestamps',
            'softDeletes' => config('code-generator.models.soft_deletes') ? '$table->softDeletes();' : '// No soft deletes',
        ];
        
        $contents = $this->getStubContents('migration', $replacements);
        
        if ($this->writeFile($migrationPath, $contents)) {
            $this->info("Migration [{$migrationName}] created successfully.");
            return true;
        }
        
        return false;
    }
} 