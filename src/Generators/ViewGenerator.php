<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class ViewGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->options['model'] ?? $this->name;
        $viewName = Str::kebab(Str::pluralStudly($modelName));
        $modelVariable = Str::camel($modelName);
        $modelVariablePlural = Str::camel(Str::pluralStudly($modelName));
        
        $viewPath = $this->getPath('views') . '/' . $viewName;
        
        // Create directory if it doesn't exist
        if (!$this->filesystem->isDirectory($viewPath)) {
            $this->filesystem->makeDirectory($viewPath, 0755, true);
        }
        
        // Generate index view
        $indexPath = $viewPath . '/index.blade.php';
        $indexReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'viewName' => $viewName,
            'routeName' => $viewName,
        ];
        $indexContents = $this->getStubContents('view-index', $indexReplacements);
        
        // Generate create view
        $createPath = $viewPath . '/create.blade.php';
        $createReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'fields' => $this->generateFormFields($this->parseSchema($this->schema)),
        ];
        $createContents = $this->getStubContents('view-create', $createReplacements);
        
        // Generate edit view
        $editPath = $viewPath . '/edit.blade.php';
        $editReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'fields' => $this->generateFormFields($this->parseSchema($this->schema), true),
        ];
        $editContents = $this->getStubContents('view-edit', $editReplacements);
        
        // Generate show view
        $showPath = $viewPath . '/show.blade.php';
        $showReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'fields' => $this->generateShowFields($this->parseSchema($this->schema)),
        ];
        $showContents = $this->getStubContents('view-show', $showReplacements);
        
        // Write files
        $files = [
            'index' => $indexPath,
            'create' => $createPath,
            'edit' => $editPath,
            'show' => $showPath,
        ];
        
        $created = [];
        
        foreach ($files as $type => $path) {
            $contents = ${$type . 'Contents'};
            
            if ($this->writeFile($path, $contents)) {
                $created[] = $type;
            }
        }
        
        if (!empty($created)) {
            $this->info("Views created successfully: " . implode(', ', $created));
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate form fields for create/edit views.
     *
     * @param array $fields
     * @param bool $isEdit
     * @return string
     */
    protected function generateFormFields($fields, $isEdit = false)
    {
        if (empty($fields)) {
            return '';
        }
        
        $formFields = '';
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            $value = $isEdit ? '{{ $' . Str::camel($this->name) . '->' . $name . ' }}' : '';
            
            // Determine input type
            $inputType = $this->getInputType($type, $name);
            
            if ($inputType === 'textarea') {
                $formFields .= $this->generateTextareaField($name, $label, $value);
            } elseif ($inputType === 'select') {
                $formFields .= $this->generateSelectField($name, $label, $value);
            } else {
                $formFields .= $this->generateInputField($name, $label, $inputType, $value);
            }
        }
        
        return $formFields;
    }
    
    /**
     * Generate show fields for show view.
     *
     * @param array $fields
     * @return string
     */
    protected function generateShowFields($fields)
    {
        if (empty($fields)) {
            return '';
        }
        
        $showFields = '';
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['deleted_at'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            $value = '{{ $' . Str::camel($this->name) . '->' . $name . ' }}';
            
            $showFields .= <<<HTML
            <div class="mb-4">
                <h5 class="font-bold">{$label}</h5>
                <p>{$value}</p>
            </div>

            HTML;
        }
        
        return $showFields;
    }
    
    /**
     * Get input type based on field type.
     *
     * @param string $type
     * @param string $name
     * @return string
     */
    protected function getInputType($type, $name)
    {
        if (Str::contains($name, ['password'])) {
            return 'password';
        }
        
        if (Str::contains($name, ['email'])) {
            return 'email';
        }
        
        if (Str::contains($name, ['_at', 'date'])) {
            return 'date';
        }
        
        if ($type === 'text' || $type === 'mediumText' || $type === 'longText') {
            return 'textarea';
        }
        
        if ($type === 'boolean') {
            return 'checkbox';
        }
        
        if ($type === 'foreignId' || Str::endsWith($name, '_id')) {
            return 'select';
        }
        
        return 'text';
    }
    
    /**
     * Generate an input field.
     *
     * @param string $name
     * @param string $label
     * @param string $type
     * @param string $value
     * @return string
     */
    protected function generateInputField($name, $label, $type, $value)
    {
        $valueAttr = $type !== 'checkbox' ? 'value="' . $value . '"' : 'checked';
        
        if ($type === 'checkbox') {
            return <<<HTML
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="{$name}">
                    <input type="checkbox" name="{$name}" id="{$name}" {$valueAttr}>
                    {$label}
                </label>
                @error('{$name}')
                    <p class="text-red-500 text-xs italic">{{ \$message }}</p>
                @enderror
            </div>

            HTML;
        }
        
        return <<<HTML
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="{$name}">
                {$label}
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="{$name}" type="{$type}" name="{$name}" {$valueAttr}>
            @error('{$name}')
                <p class="text-red-500 text-xs italic">{{ \$message }}</p>
            @enderror
        </div>

        HTML;
    }
    
    /**
     * Generate a textarea field.
     *
     * @param string $name
     * @param string $label
     * @param string $value
     * @return string
     */
    protected function generateTextareaField($name, $label, $value)
    {
        return <<<HTML
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="{$name}">
                {$label}
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="{$name}" name="{$name}" rows="5">{$value}</textarea>
            @error('{$name}')
                <p class="text-red-500 text-xs italic">{{ \$message }}</p>
            @enderror
        </div>

        HTML;
    }
    
    /**
     * Generate a select field.
     *
     * @param string $name
     * @param string $label
     * @param string $value
     * @return string
     */
    protected function generateSelectField($name, $label, $value)
    {
        $relatedModel = Str::studly(str_replace('_id', '', $name));
        
        return <<<HTML
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="{$name}">
                {$label}
            </label>
            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="{$name}" name="{$name}">
                <option value="">Select {$relatedModel}</option>
                @foreach(\${$name}Options as \$id => \$name)
                    <option value="{{ \$id }}" {{ {$value} == \$id ? 'selected' : '' }}>{{ \$name }}</option>
                @endforeach
            </select>
            @error('{$name}')
                <p class="text-red-500 text-xs italic">{{ \$message }}</p>
            @enderror
        </div>

        HTML;
    }
} 