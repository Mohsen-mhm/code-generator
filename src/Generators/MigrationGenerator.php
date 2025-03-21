<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MigrationGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->options['model'] ?? $this->name;
        $tableName = $this->options['table'] ?? Str::snake(Str::pluralStudly($modelName));
        
        // Check if migration already exists
        $existingMigration = $this->findExistingMigration($tableName);
        
        if ($existingMigration) {
            if ($this->options['force'] ?? false) {
                // If --force is used, delete the existing migration
                $this->filesystem->delete($existingMigration);
                $this->info("Deleted existing migration for table '{$tableName}'.");
            } else {
                $this->info("Migration for table '{$tableName}' already exists. Use --force to overwrite.");
                return false;
            }
        }
        
        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "create_{$tableName}_table";
        $migrationPath = $this->getPath('migrations') . "/{$timestamp}_{$migrationName}.php";
        
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'class' => Str::studly($migrationName),
            'table' => $tableName,
            'fields' => $this->formatSchemaFieldsAsString($fields, 'migration'),
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
    
    /**
     * Find an existing migration for the given table.
     *
     * @param string $tableName
     * @return string|null The full path to the migration file if found, null otherwise
     */
    protected function findExistingMigration($tableName)
    {
        $migrationsPath = $this->getPath('migrations');
        $files = File::glob($migrationsPath . '/*.php');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Check for create_table_name_table.php pattern
            if (Str::contains($filename, "create_{$tableName}_table")) {
                return $file;
            }
            
            // Also check the content of the file for the table name
            $content = File::get($file);
            if (Str::contains($content, "Schema::create('{$tableName}'") || 
                Str::contains($content, "Schema::create(\"{$tableName}\"")) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * Check if a migration for the given table already exists.
     *
     * @param string $tableName
     * @return bool
     */
    protected function migrationExists($tableName)
    {
        return $this->findExistingMigration($tableName) !== null;
    }
} 
