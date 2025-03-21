<?php

namespace MohsenMhm\CodeGenerator\Traits;

use Illuminate\Support\Str;

trait GeneratesFiles
{
    /**
     * Write contents to a file.
     *
     * @param string $path
     * @param string $contents
     * @return bool
     */
    protected function writeFile($path, $contents)
    {
        if ($this->filesystem->exists($path) && !($this->options['force'] ?? false)) {
            $this->error("File [{$path}] already exists. Use --force to overwrite.");
            return false;
        }
        
        $directory = dirname($path);
        
        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
        
        $this->filesystem->put($path, $contents);
        
        return true;
    }
    
    /**
     * Get stub contents and replace placeholders.
     *
     * @param string $stub
     * @param array $replacements
     * @return string
     */
    protected function getStubContents($stub, $replacements)
    {
        $contents = $this->filesystem->get($this->getStubPath($stub));
        
        foreach ($replacements as $search => $replace) {
            $contents = str_replace('{{ ' . $search . ' }}', $replace, $contents);
        }
        
        return $contents;
    }
    
    /**
     * Get the path to a stub file.
     *
     * @param string $stub
     * @return string
     */
    protected function getStubPath($stub)
    {
        $customPath = config('code-generator.stubs_path') . "/{$stub}.stub";
        
        if ($this->filesystem->exists($customPath)) {
            return $customPath;
        }
        
        return __DIR__ . "/../Stubs/{$stub}.stub";
    }
    
    /**
     * Get fields as a string for different contexts.
     *
     * @param array $fields
     * @param string $context
     * @return string
     */
    protected function getFieldsAsString($fields, $context)
    {
        if (empty($fields)) {
            return '';
        }
        
        $result = '';
        
        switch ($context) {
            case 'migration':
                foreach ($fields as $field) {
                    $name = $field['name'];
                    $type = $field['type'];
                    $modifiers = $field['modifiers'] ?? [];
                    
                    $result .= "\$table->{$type}('{$name}')";
                    
                    foreach ($modifiers as $modifier) {
                        $result .= "->{$modifier}()";
                    }
                    
                    $result .= ";\n            ";
                }
                break;
                
            case 'fillable':
                $fieldNames = array_map(function ($field) {
                    return "'" . $field['name'] . "'";
                }, $fields);
                
                $result = implode(",\n        ", $fieldNames);
                break;
                
            case 'casts':
                foreach ($fields as $field) {
                    $name = $field['name'];
                    $type = $field['type'];
                    
                    $result .= "'{$name}' => '{$type}',\n        ";
                }
                break;
        }
        
        return $result;
    }
} 