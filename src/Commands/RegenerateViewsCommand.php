<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MohsenMhm\CodeGenerator\Generators\ViewGenerator;

class RegenerateViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:regenerate-views {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate views for a model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->argument('model');
        
        // Get the table name from the model
        $tableName = Str::snake(Str::pluralStudly($model));
        
        // Delete existing views
        $viewPath = resource_path('views/' . Str::kebab(Str::pluralStudly($model)));
        if (File::isDirectory($viewPath)) {
            File::deleteDirectory($viewPath);
            $this->info("Deleted existing views for {$model}");
        }
        
        // Get schema from database table
        $schema = $this->getSchemaFromTable($tableName);
        
        if (empty($schema)) {
            $this->error("Could not determine schema for {$model}. Make sure the table {$tableName} exists.");
            return 1;
        }
        
        // Regenerate views
        $generator = new ViewGenerator($model, $schema, ['force' => true]);
        $generator->generate();
        
        $this->info("Views regenerated for {$model}");
        
        // Output the create view for inspection
        $createViewPath = $viewPath . '/create.blade.php';
        if (File::exists($createViewPath)) {
            $this->info("Contents of create.blade.php:");
            $this->line(File::get($createViewPath));
        }
    }
    
    /**
     * Get the schema from a database table.
     *
     * @param string $tableName
     * @return string
     */
    protected function getSchemaFromTable($tableName)
    {
        if (!Schema::hasTable($tableName)) {
            return '';
        }
        
        $columns = Schema::getColumnListing($tableName);
        $schema = [];
        
        foreach ($columns as $column) {
            $type = Schema::getColumnType($tableName, $column);
            
            // Skip primary key and timestamps
            if ($column === 'id' || in_array($column, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            // Handle foreign keys
            if (Str::endsWith($column, '_id')) {
                $schema[] = "{$column}:foreignId";
            } else {
                $schema[] = "{$column}:{$type}";
            }
        }
        
        return implode(', ', $schema);
    }
} 