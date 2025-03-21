<?php

namespace MohsenMhm\CodeGenerator\Traits;

use Illuminate\Support\Str;

trait ParsesSchemas
{
    protected function parseSchema($schema)
    {
        if (empty($schema)) {
            return [];
        }

        $fields = [];
        $parts = explode(',', $schema);

        foreach ($parts as $part) {
            $fieldDefinition = trim($part);
            if (empty($fieldDefinition)) {
                continue;
            }

            // Format: name:type:modifier1:modifier2...
            $segments = explode(':', $fieldDefinition);
            $name = array_shift($segments);
            $type = count($segments) ? array_shift($segments) : 'string';
            $modifiers = $segments;

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'modifiers' => $modifiers,
            ];
        }

        return $fields;
    }

    protected function getFieldsAsString($fields, $format = 'migration')
    {
        if (empty($fields)) {
            return '';
        }

        $result = [];

        foreach ($fields as $field) {
            switch ($format) {
                case 'migration':
                    $result[] = $this->getMigrationField($field);
                    break;
                case 'fillable':
                    $result[] = "'" . $field['name'] . "'";
                    break;
                case 'casts':
                    $result[] = $this->getCastField($field);
                    break;
                case 'validation':
                    $result[] = $this->getValidationRule($field);
                    break;
                case 'form':
                    $result[] = $this->getFormField($field);
                    break;
                case 'table':
                    $result[] = $this->getTableColumn($field);
                    break;
            }
        }

        if ($format === 'fillable') {
            return '[' . PHP_EOL . '        ' . implode(',' . PHP_EOL . '        ', $result) . PHP_EOL . '    ]';
        }

        return implode(PHP_EOL . '            ', $result);
    }

    protected function getMigrationField($field)
    {
        $name = $field['name'];
        $type = $field['type'];
        $modifiers = $field['modifiers'];
        
        $result = "\$table->{$type}('{$name}')";
        
        foreach ($modifiers as $modifier) {
            if ($modifier === 'nullable') {
                $result .= "->nullable()";
            } elseif ($modifier === 'unique') {
                $result .= "->unique()";
            } elseif ($modifier === 'index') {
                $result .= "->index()";
            } elseif (Str::startsWith($modifier, 'default:')) {
                $default = Str::after($modifier, 'default:');
                if (is_numeric($default)) {
                    $result .= "->default({$default})";
                } else {
                    $result .= "->default('{$default}')";
                }
            } elseif (Str::startsWith($modifier, 'comment:')) {
                $comment = Str::after($modifier, 'comment:');
                $result .= "->comment('{$comment}')";
            }
        }
        
        $result .= ";";
        
        return $result;
    }

    protected function getCastField($field)
    {
        $name = $field['name'];
        $type = $field['type'];
        
        $castType = match ($type) {
            'integer', 'bigInteger', 'tinyInteger', 'smallInteger', 'mediumInteger' => 'integer',
            'decimal', 'float', 'double' => 'float',
            'boolean' => 'boolean',
            'date' => 'date',
            'dateTime', 'timestamp' => 'datetime',
            'json', 'jsonb' => 'array',
            default => 'string',
        };
        
        return "'{$name}' => '{$castType}'";
    }

    protected function getValidationRule($field)
    {
        $name = $field['name'];
        $type = $field['type'];
        $modifiers = $field['modifiers'];
        
        $rules = [];
        
        if (!in_array('nullable', $modifiers)) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }
        
        switch ($type) {
            case 'integer':
            case 'bigInteger':
            case 'tinyInteger':
            case 'smallInteger':
            case 'mediumInteger':
                $rules[] = 'integer';
                break;
            case 'decimal':
            case 'float':
            case 'double':
                $rules[] = 'numeric';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'dateTime':
            case 'timestamp':
                $rules[] = 'date_format:Y-m-d H:i:s';
                break;
            case 'time':
                $rules[] = 'date_format:H:i:s';
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'json':
            case 'jsonb':
                $rules[] = 'json';
                break;
        }
        
        if (in_array('unique', $modifiers)) {
            $table = Str::snake(Str::pluralStudly(class_basename($this->name)));
            $rules[] = "unique:{$table},{$name}";
        }
        
        return "'{$name}' => '" . implode('|', $rules) . "'";
    }

    protected function getFormField($field)
    {
        $name = $field['name'];
        $type = $field['type'];
        $label = Str::title(str_replace('_', ' ', $name));
        
        switch ($type) {
            case 'boolean':
                return "<div class=\"mb-3 form-check\">
                <input type=\"checkbox\" class=\"form-check-input\" id=\"{$name}\" wire:model=\"{$name}\">
                <label class=\"form-check-label\" for=\"{$name}\">{$label}</label>
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            case 'text':
            case 'mediumText':
            case 'longText':
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <textarea class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\" rows=\"3\"></textarea>
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            case 'date':
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <input type=\"date\" class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\">
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            case 'dateTime':
            case 'timestamp':
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <input type=\"datetime-local\" class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\">
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            case 'time':
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <input type=\"time\" class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\">
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            case 'email':
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <input type=\"email\" class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\">
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            case 'password':
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <input type=\"password\" class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\">
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
            default:
                return "<div class=\"mb-3\">
                <label for=\"{$name}\" class=\"form-label\">{$label}</label>
                <input type=\"text\" class=\"form-control\" id=\"{$name}\" wire:model=\"{$name}\">
                @error('{$name}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
            </div>";
        }
    }

    protected function getTableColumn($field)
    {
        $name = $field['name'];
        $label = Str::title(str_replace('_', ' ', $name));
        
        return "<x-table.column>
                <x-slot name=\"header\">{$label}</x-slot>
                {{ \$item->{$name} }}
            </x-table.column>";
    }
} 