<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class ResourceGenerator extends BaseGenerator
{
    public function generate()
    {
        $resourceName = $this->name;
        
        if (!Str::endsWith($resourceName, 'Resource')) {
            $resourceName .= 'Resource';
        }
        
        $resourceNamespace = $this->getNamespace('resource');
        $resourcePath = $this->getPath('resources') . '/' . $resourceName . '.php';
        
        $modelName = $this->options['model'] ?? Str::singular(str_replace('Resource', '', $resourceName));
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'namespace' => $resourceNamespace,
            'class' => $resourceName,
            'model' => $modelName,
            'resourceFields' => $this->generateResourceFields($fields),
        ];
        
        $contents = $this->getStubContents('resource', $replacements);
        
        if ($this->writeFile($resourcePath, $contents)) {
            $this->info("Resource [{$resourceName}] created successfully.");
            
            // Generate collection resource if needed
            if ($this->options['collection'] ?? false) {
                $this->generateCollectionResource($resourceName);
            }
            
            return true;
        }
        
        return false;
    }
    
    protected function generateResourceFields($fields)
    {
        if (empty($fields)) {
            return "'id' => \$this->id,";
        }
        
        $result = ["'id' => \$this->id,"];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $result[] = "'{$name}' => \$this->{$name},";
        }
        
        $result[] = "'created_at' => \$this->created_at,";
        $result[] = "'updated_at' => \$this->updated_at,";
        
        return implode(PHP_EOL . '            ', $result);
    }
    
    protected function generateCollectionResource($resourceName)
    {
        $collectionName = str_replace('Resource', 'Collection', $resourceName);
        $collectionNamespace = $this->getNamespace('resource');
        $collectionPath = $this->getPath('resources') . '/' . $collectionName . '.php';
        
        $replacements = [
            'namespace' => $collectionNamespace,
            'class' => $collectionName,
            'resource' => $resourceName,
        ];
        
        $contents = $this->getStubContents('resource-collection', $replacements);
        
        if ($this->writeFile($collectionPath, $contents)) {
            $this->info("Resource Collection [{$collectionName}] created successfully.");
            return true;
        }
        
        return false;
    }
} 