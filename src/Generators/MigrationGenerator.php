<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class MigrationGenerator extends BaseGenerator
{
    public function generate()
    {
        $tableName = Str::snake(Str::pluralStudly($this->name));
        $className = 'Create' . Str::studly($tableName) . 'Table';

        // Check if a migration for this table already exists
        $existingMigration = $this->findExistingMigration($tableName);

        if ($existingMigration && !($this->options['force'] ?? false)) {
            $this->info("Migration for table [{$tableName}] already exists at [{$existingMigration}].");
            $this->info("Use --force option to overwrite.");
            return false;
        }

        $timestamp = Carbon::now()->format('Y_m_d_His');
        $filename = $timestamp . '_create_' . $tableName . '_table.php';

        // Use existing path if found (with --force), otherwise create a new path
        $migrationPath = $existingMigration ?: $this->getPath('migrations') . '/' . $filename;

        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);

        $replacements = [
            'class' => $className,
            'table' => $tableName,
            'fields' => $this->generateFields($fields),
        ];

        $contents = $this->getStubContents('migration', $replacements);

        if ($this->writeFile($migrationPath, $contents)) {
            $this->info("Migration [{$migrationPath}] created successfully.");
            return true;
        }

        return false;
    }

    /**
     * Generate migration fields.
     *
     * @param array $fields
     * @return string
     */
    protected function generateFields($fields)
    {
        if (empty($fields)) {
            return '$table->id();' . PHP_EOL . '            $table->timestamps();';
        }

        $migrationFields = ['$table->id();'];
        $hasDeletedAt = false;

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            // Check for soft deletes
            if ($name === 'deleted_at') {
                $hasDeletedAt = true;
                continue;
            }

            $migrationField = $this->getMigrationField($field);

            if ($migrationField) {
                $migrationFields[] = $migrationField;
            }
        }

        // Add timestamps
        $migrationFields[] = '$table->timestamps();';

        // Add soft deletes if needed
        if ($hasDeletedAt) {
            $migrationFields[] = '$table->softDeletes();';
        }

        return implode(PHP_EOL . '            ', $migrationFields);
    }

    /**
     * Get migration field for a given field.
     *
     * @param string $name
     * @param string $type
     * @return string|null
     */
    protected function getMigrationField($field)
    {
        // Extract data from the $field array
        $name = $field['name'];
        $type = $field['type'];

        // Check if the field is nullable
        $nullable = Str::contains($type, 'nullable') ? '->nullable()' : '';
        $type = Str::before($type, ':');

        switch ($type) {
            case 'string':
                return "\$table->string('{$name}'){$nullable};";
            case 'text':
            case 'longText':
            case 'mediumText':
                return "\$table->{$type}('{$name}'){$nullable};";
            case 'integer':
            case 'bigInteger':
            case 'smallInteger':
            case 'tinyInteger':
                return "\$table->{$type}('{$name}'){$nullable};";
            case 'decimal':
                return "\$table->decimal('{$name}', 8, 2){$nullable};";
            case 'float':
            case 'double':
                return "\$table->{$type}('{$name}'){$nullable};";
            case 'boolean':
                return "\$table->boolean('{$name}'){$nullable};";
            case 'date':
            case 'dateTime':
            case 'time':
            case 'timestamp':
                return "\$table->{$type}('{$name}'){$nullable};";
            case 'json':
            case 'jsonb':
                return "\$table->json('{$name}'){$nullable};";
            case 'uuid':
                return "\$table->uuid('{$name}'){$nullable};";
            case 'foreignId':
                $table = Str::plural(Str::beforeLast($name, '_id'));
                return "\$table->foreignId('{$name}'){$nullable}->constrained('{$table}')->onDelete('cascade');";
            default:
                return null;
        }
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
