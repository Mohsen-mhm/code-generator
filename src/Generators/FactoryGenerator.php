<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class FactoryGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->name;
        $factoryName = $modelName . 'Factory';
        $modelNamespace = $this->getNamespace('model');
        
        $factoryPath = $this->getPath('factory') . '/' . $factoryName . '.php';
        
        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'namespace' => $this->getNamespace('factory'),
            'modelNamespace' => $modelNamespace,
            'modelName' => $modelName,
            'fields' => $this->generateFields($fields),
        ];
        
        $contents = $this->getStubContents('factory', $replacements);
        
        if ($this->writeFile($factoryPath, $contents)) {
            $this->info("Factory [{$factoryName}] created successfully.");
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate factory fields.
     *
     * @param array $fields
     * @return string
     */
    protected function generateFields($fields)
    {
        if (empty($fields)) {
            return '';
        }
        
        $factoryFields = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $factoryField = $this->getFactoryField($name, $type);
            
            if ($factoryField) {
                $factoryFields[] = "'{$name}' => {$factoryField}";
            }
        }
        
        return implode(",\n            ", $factoryFields);
    }
    
    /**
     * Get factory field for a given field.
     *
     * @param string $name
     * @param string $type
     * @return string|null
     */
    protected function getFactoryField($name, $type)
    {
        $type = Str::before($type, ':');
        
        if (Str::endsWith($name, '_id')) {
            $relatedModel = Str::studly(str_replace('_id', '', $name));
            $modelNamespace = $this->getNamespace('model');
            return "\\{$modelNamespace}\\{$relatedModel}::factory()";
        }
        
        if ($name === 'name' || $name === 'title') {
            return '$this->faker->sentence';
        }
        
        if ($name === 'email') {
            return '$this->faker->unique()->safeEmail';
        }
        
        if ($name === 'password') {
            return "bcrypt('password')";
        }
        
        if ($name === 'content' || $name === 'description' || $name === 'body') {
            return '$this->faker->paragraph';
        }
        
        if (Str::contains($name, 'image') || Str::contains($name, 'photo') || Str::contains($name, 'avatar')) {
            return '$this->faker->imageUrl()';
        }
        
        if (Str::contains($name, 'url') || Str::contains($name, 'link')) {
            return '$this->faker->url';
        }
        
        if (Str::contains($name, 'phone')) {
            return '$this->faker->phoneNumber';
        }
        
        if (Str::contains($name, 'address')) {
            return '$this->faker->address';
        }
        
        switch ($type) {
            case 'string':
                return '$this->faker->word';
            case 'text':
            case 'longText':
            case 'mediumText':
                return '$this->faker->paragraph';
            case 'integer':
            case 'bigInteger':
            case 'smallInteger':
            case 'tinyInteger':
                return '$this->faker->randomNumber()';
            case 'decimal':
            case 'float':
            case 'double':
                return '$this->faker->randomFloat()';
            case 'boolean':
                return '$this->faker->boolean';
            case 'date':
                return '$this->faker->date()';
            case 'dateTime':
            case 'timestamp':
                return '$this->faker->dateTime()';
            case 'time':
                return '$this->faker->time()';
            case 'json':
            case 'jsonb':
                return '[]';
            case 'uuid':
                return '$this->faker->uuid';
            default:
                return null;
        }
    }
} 