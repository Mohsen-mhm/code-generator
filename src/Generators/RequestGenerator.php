<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class RequestGenerator extends BaseGenerator
{
    public function generate()
    {
        $requestName = $this->name;
        
        if (!Str::endsWith($requestName, 'Request')) {
            $requestName .= 'Request';
        }
        
        $requestPath = $this->getPath('requests') . '/' . $requestName . '.php';
        
        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);
        
        $replacements = [
            'namespace' => $this->getNamespace('request'),
            'class' => $requestName,
            'rules' => $this->generateValidationRules($fields),
        ];
        
        $contents = $this->getStubContents('request', $replacements);
        
        if ($this->writeFile($requestPath, $contents)) {
            $this->info("Request [{$requestName}] created successfully.");
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate validation rules for fields.
     *
     * @param array $fields
     * @return string
     */
    protected function generateValidationRules($fields)
    {
        if (empty($fields)) {
            return "return [\n            //\n        ];";
        }
        
        $rules = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $fieldRules = [];
            
            if (Str::endsWith($name, '_id')) {
                $fieldRules[] = 'required';
                $fieldRules[] = 'exists:' . Str::plural(str_replace('_id', '', $name)) . ',id';
            } elseif ($type === 'string') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
            } elseif ($type === 'text' || $type === 'longText' || $type === 'mediumText') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'string';
            } elseif ($type === 'integer' || $type === 'bigInteger' || $type === 'smallInteger' || $type === 'tinyInteger') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'integer';
            } elseif ($type === 'decimal' || $type === 'float' || $type === 'double') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'numeric';
            } elseif ($type === 'boolean') {
                $fieldRules[] = 'boolean';
            } elseif ($type === 'date' || $type === 'dateTime' || $type === 'timestamp') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'date';
            } elseif ($type === 'time') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'date_format:H:i:s';
            } elseif ($type === 'year') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'date_format:Y';
            } elseif ($type === 'enum') {
                $fieldRules[] = 'required';
                $fieldRules[] = 'string';
            } elseif ($type === 'json') {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'json';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            // Check for nullable fields
            if (Str::contains($type, 'nullable')) {
                // Remove 'required' if it exists
                $fieldRules = array_diff($fieldRules, ['required']);
                // Add 'nullable' if it doesn't exist
                if (!in_array('nullable', $fieldRules)) {
                    $fieldRules[] = 'nullable';
                }
            }
            
            $rules[] = "'{$name}' => [" . implode(', ', array_map(function ($rule) {
                return "'{$rule}'";
            }, $fieldRules)) . "]";
        }
        
        return "return [\n            " . implode(",\n            ", $rules) . "\n        ];";
    }
}