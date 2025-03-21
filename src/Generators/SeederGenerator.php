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
        $databaseSeederPath = $this->getPath('seeders') . '/DatabaseSeeder.php';
        
        if (!file_exists($databaseSeederPath)) {
            $this->warn("DatabaseSeeder.php not found. Could not update it.");
            return false;
        }
        
        $content = file_get_contents($databaseSeederPath);
        
        // Check if the seeder is already called
        if (Str::contains($content, $seederName . '::class')) {
            $this->info("DatabaseSeeder already calls {$seederName}.");
            return true;
        }
        
        // Find the run method
        if (preg_match('/public function run\(\)\s*{\s*(.*?)\s*}/s', $content, $matches)) {
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
                $runMethodContent,
                $newRunMethodContent,
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
            
            if (file_put_contents($databaseSeederPath, $newContent)) {
                $this->info("DatabaseSeeder.php updated to call {$seederName}.");
                return true;
            }
        }
        
        $this->warn("Could not update DatabaseSeeder.php.");
        return false;
    }
}