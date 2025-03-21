<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class SeederGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->name;
        $seederName = $modelName . 'Seeder';
        $modelNamespace = $this->getNamespace('model');

        $seederPath = $this->getPath('seeders') . '/' . $seederName . '.php';

        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);

        $replacements = [
            'namespace' => $this->getNamespace('seeder'),
            'modelNamespace' => $modelNamespace,
            'modelName' => $modelName,
            'seederName' => $seederName,
            'count' => 10, // Default value
        ];

        $contents = $this->getStubContents('seeder', $replacements);

        if ($this->writeFile($seederPath, $contents)) {
            $this->info("Seeder [{$seederName}] created successfully.");

            // Update DatabaseSeeder.php
            $this->updateDatabaseSeeder($seederName);

            return true;
        }

        return false;
    }

    /**
     * Update DatabaseSeeder to call this seeder.
     *
     * @param string $seederName
     * @return bool
     */
    protected function updateDatabaseSeeder($seederName)
    {
        // Get Laravel's base path (the application root directory)
        $basePath = app()->basePath();
        $databaseSeederPath = $basePath . '/database/seeders/DatabaseSeeder.php';

        if (!file_exists($databaseSeederPath)) {
            $this->error("DatabaseSeeder.php not found at {$databaseSeederPath}. Could not update it.");
            return false;
        }

        $content = file_get_contents($databaseSeederPath);

        // Check if the seeder is already called
        if (Str::contains($content, $seederName . '::class')) {
            $this->info("DatabaseSeeder already calls {$seederName}.");
            return true;
        }

        // Find the run method and add the new seeder call
        if (preg_match('/public function run\(\).*?{(.*?)}/s', $content, $matches)) {
            $runMethodContent = $matches[1];
            $newRunMethodContent = $runMethodContent;

            if (Str::contains($runMethodContent, '$this->call(')) {
                // Find the last call statement
                if (preg_match_all('/\$this->call\(.*?\);/s', $runMethodContent, $calls)) {
                    $lastCall = end($calls[0]);
                    $newRunMethodContent = str_replace(
                        $lastCall,
                        $lastCall . "\n        \$this->call(" . $seederName . "::class);",
                        $runMethodContent
                    );
                }
            } else {
                // No call statements yet, add the first one
                $newRunMethodContent = "\n        \$this->call(" . $seederName . "::class);\n    ";
            }

            // Replace the run method content
            $newContent = str_replace(
                $matches[0],
                str_replace($runMethodContent, $newRunMethodContent, $matches[0]),
                $content
            );

            // Add the import if needed
            $seederNamespace = $this->getNamespace('seeder');
            $importStatement = "use {$seederNamespace}\\{$seederName};";

            if (!Str::contains($newContent, $importStatement)) {
                // Find the last use statement
                if (preg_match_all('/use .*?;/s', $newContent, $useStatements)) {
                    $lastUse = end($useStatements[0]);
                    $newContent = str_replace(
                        $lastUse,
                        $lastUse . "\n" . $importStatement,
                        $newContent
                    );
                } else {
                    // No use statements yet, add after namespace
                    $newContent = preg_replace(
                        '/(namespace .*?;)/s',
                        "$1\n\n" . $importStatement,
                        $newContent
                    );
                }
            }

            try {
                if (file_put_contents($databaseSeederPath, $newContent)) {
                    $this->info("DatabaseSeeder.php updated to call {$seederName}.");
                    return true;
                } else {
                    $this->error("Failed to write updated content to DatabaseSeeder.php. Check file permissions.");
                    return false;
                }
            } catch (\Exception $e) {
                $this->error("Exception while updating DatabaseSeeder.php: " . $e->getMessage());
                return false;
            }
        }

        $this->error("Could not find run() method in DatabaseSeeder.php. Manual update required.");
        return false;
    }
}
