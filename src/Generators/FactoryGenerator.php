<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class FactoryGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->options['model'] ?? $this->name;
        
        if (!Str::endsWith($modelName, 'Factory')) {
            $factoryName = $modelName . 'Factory';
        } else {
            $factoryName = $modelName;
            $modelName = str_replace('Factory', '', $modelName);
        }
        
        // Check if factory already exists
        $factoryPath = $this->getPath('factories') . '/' . $factoryName . '.php';
        if ($this->filesystem->exists($factoryPath) && !($this->options['force'] ?? false)) {
            $this->info("Factory [{$factoryName}] already exists. Use --force to overwrite.");
            return false;
        }
        
        // Use Database\Factories namespace as per Laravel convention
        $factoryNamespace = 'Database\\Factories';
        $modelNamespace = $this->getNamespace('model') . '\\' . $modelName;
        
        // Parse schema to generate factory attributes
        $fields = $this->parseSchema($this->schema);
        $factoryAttributes = $this->generateFactoryAttributes($fields);
        
        $replacements = [
            'namespace' => $factoryNamespace,
            'class' => $factoryName,
            'model' => $modelName,
            'modelNamespace' => $modelNamespace,
            'attributes' => $factoryAttributes,
        ];
        
        $contents = $this->getStubContents('factory', $replacements);
        
        if ($this->writeFile($factoryPath, $contents)) {
            $this->info("Factory [{$factoryName}] created successfully.");
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate factory attributes based on schema fields.
     *
     * @param array $fields
     * @return string
     */
    protected function generateFactoryAttributes($fields)
    {
        $attributes = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $faker = $this->getFakerStatement($name, $type);
            $attributes[] = "'{$name}' => {$faker},";
        }
        
        return implode(PHP_EOL . '            ', $attributes);
    }
    
    /**
     * Get the appropriate Faker statement for a field.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getFakerStatement($name, $type)
    {
        // Detect field type by name
        if (Str::contains($name, ['email'])) {
            return '$this->faker->unique()->safeEmail()';
        }
        
        if (Str::contains($name, ['name', 'title'])) {
            return '$this->faker->name()';
        }
        
        if (Str::contains($name, ['password'])) {
            return 'bcrypt(\'password\')';
        }
        
        if (Str::contains($name, ['description', 'content', 'body'])) {
            return '$this->faker->paragraph()';
        }
        
        if (Str::contains($name, ['image', 'avatar', 'photo', 'picture'])) {
            return '$this->faker->imageUrl()';
        }
        
        if (Str::contains($name, ['url', 'link', 'website'])) {
            return '$this->faker->url()';
        }
        
        if (Str::contains($name, ['phone'])) {
            return '$this->faker->phoneNumber()';
        }
        
        if (Str::contains($name, ['address'])) {
            return '$this->faker->address()';
        }
        
        // Detect by type
        switch ($type) {
            case 'string':
                return '$this->faker->word()';
            case 'text':
            case 'mediumText':
            case 'longText':
                return '$this->faker->paragraph()';
            case 'integer':
            case 'bigInteger':
            case 'smallInteger':
            case 'tinyInteger':
                return '$this->faker->numberBetween(1, 100)';
            case 'float':
            case 'double':
            case 'decimal':
                return '$this->faker->randomFloat(2, 1, 100)';
            case 'boolean':
                return '$this->faker->boolean()';
            case 'date':
                return '$this->faker->date()';
            case 'dateTime':
            case 'timestamp':
                return '$this->faker->dateTime()->format(\'Y-m-d H:i:s\')';
            case 'time':
                return '$this->faker->time()';
            case 'json':
            case 'jsonb':
                return '\'{"key": "value"}\'';
            default:
                return '$this->faker->word()';
        }
    }
} 