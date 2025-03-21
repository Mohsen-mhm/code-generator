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
        
        // Ensure layouts directory exists
        $layoutsPath = resource_path('views/layouts');
        if (!$this->filesystem->isDirectory($layoutsPath)) {
            $this->filesystem->makeDirectory($layoutsPath, 0755, true);
        }
        
        // Create app layout if it doesn't exist
        $appLayoutPath = $layoutsPath . '/app.blade.php';
        if (!$this->filesystem->exists($appLayoutPath)) {
            $layoutContents = $this->getStubContents('view-layout', []);
            $this->writeFile($appLayoutPath, $layoutContents);
            $this->info("Layout file [app.blade.php] created successfully.");
        }
        
        $viewPath = $this->getPath('views') . '/' . $viewName;
        
        // Create directory if it doesn't exist
        if (!$this->filesystem->isDirectory($viewPath)) {
            $this->filesystem->makeDirectory($viewPath, 0755, true);
        }
        
        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);
        
        // Generate index view
        $indexPath = $viewPath . '/index.blade.php';
        $indexReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'tableHeaders' => $this->generateTableHeaders($fields),
            'tableRows' => $this->generateTableRows($fields, $modelVariable),
        ];
        $indexContents = $this->getStubContents('view-index', $indexReplacements);
        
        // Generate create view
        $createPath = $viewPath . '/create.blade.php';
        $createReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'fields' => $this->generateFormFields($fields),
        ];
        $createContents = $this->getStubContents('view-create', $createReplacements);
        
        // Generate edit view
        $editPath = $viewPath . '/edit.blade.php';
        $editReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'fields' => $this->generateFormFields($fields, true),
        ];
        $editContents = $this->getStubContents('view-edit', $editReplacements);
        
        // Generate show view
        $showPath = $viewPath . '/show.blade.php';
        $showReplacements = [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'viewName' => $viewName,
            'routeName' => $viewName,
            'fields' => $this->generateShowFields($fields, $modelVariable),
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
     * Generate table headers for index view.
     *
     * @param array $fields
     * @return string
     */
    protected function generateTableHeaders($fields)
    {
        $headers = ['<th class="py-3 px-6 bg-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>'];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip certain fields
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at', 'password'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            $headers[] = '<th class="py-3 px-6 bg-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . $label . '</th>';
        }
        
        $headers[] = '<th class="py-3 px-6 bg-gray-100 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
        
        return implode("\n                    ", $headers);
    }
    
    /**
     * Generate table rows for index view.
     *
     * @param array $fields
     * @param string $modelVariable
     * @return string
     */
    protected function generateTableRows($fields, $modelVariable)
    {
        $rows = ['<td class="py-4 px-6">{{ $' . $modelVariable . '->id }}</td>'];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip certain fields
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at', 'password'])) {
                continue;
            }
            
            $rows[] = '<td class="py-4 px-6">{{ $' . $modelVariable . '->' . $name . ' }}</td>';
        }
        
        return implode("\n                    ", $rows);
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
        
        $formFields = [];
        $modelVariable = Str::camel($this->name);
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip certain fields
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            
            if (Str::endsWith($name, '_id')) {
                // Foreign key field
                $relatedModel = Str::studly(str_replace('_id', '', $name));
                $formFields[] = $this->generateForeignKeyField($name, $label, $relatedModel, $modelVariable, $isEdit);
            } elseif ($type === 'text' || $type === 'longText' || $type === 'mediumText') {
                // Textarea field
                $formFields[] = $this->generateTextareaField($name, $label, $modelVariable, $isEdit);
            } elseif ($type === 'boolean') {
                // Checkbox field
                $formFields[] = $this->generateCheckboxField($name, $label, $modelVariable, $isEdit);
            } elseif ($type === 'date' || $type === 'dateTime' || $type === 'timestamp') {
                // Date field
                $formFields[] = $this->generateDateField($name, $label, $modelVariable, $isEdit);
            } else {
                // Regular input field
                $inputType = $this->getInputType($name, $type);
                $formFields[] = $this->generateInputField($name, $label, $inputType, $modelVariable, $isEdit);
            }
        }
        
        return implode("\n\n            ", $formFields);
    }
    
    /**
     * Get the input type for a field.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getInputType($name, $type)
    {
        if ($name === 'email' || Str::contains($name, 'email')) {
            return 'email';
        } elseif ($name === 'password' || Str::contains($name, 'password')) {
            return 'password';
        } elseif ($type === 'integer' || $type === 'bigInteger' || $type === 'smallInteger' || $type === 'tinyInteger') {
            return 'number';
        } elseif ($type === 'decimal' || $type === 'float' || $type === 'double') {
            return 'number';
        } else {
            return 'text';
        }
    }
    
    /**
     * Generate a regular input field.
     *
     * @param string $name
     * @param string $label
     * @param string $type
     * @param string $modelVariable
     * @param bool $isEdit
     * @return string
     */
    protected function generateInputField($name, $label, $type, $modelVariable, $isEdit)
    {
        $value = $isEdit 
            ? "{{ \${$modelVariable}->{$name} }}"
            : "{{ old('{$name}') }}";
        
        return '<div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="' . $name . '">
                    ' . $label . '
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="' . $name . '" type="' . $type . '" name="' . $name . '" value="' . $value . '">
                @error(\'' . $name . '\')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>';
    }
    
    /**
     * Generate a textarea field.
     *
     * @param string $name
     * @param string $label
     * @param string $modelVariable
     * @param bool $isEdit
     * @return string
     */
    protected function generateTextareaField($name, $label, $modelVariable, $isEdit)
    {
        $value = $isEdit 
            ? "{{ \${$modelVariable}->{$name} }}"
            : "{{ old('{$name}') }}";
        
        return '<div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="' . $name . '">
                    ' . $label . '
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="' . $name . '" name="' . $name . '" rows="5">' . $value . '</textarea>
                @error(\'' . $name . '\')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>';
    }
    
    /**
     * Generate a checkbox field.
     *
     * @param string $name
     * @param string $label
     * @param string $modelVariable
     * @param bool $isEdit
     * @return string
     */
    protected function generateCheckboxField($name, $label, $modelVariable, $isEdit)
    {
        $checked = $isEdit 
            ? "{{ \${$modelVariable}->{$name} ? 'checked' : '' }}"
            : "{{ old('{$name}') ? 'checked' : '' }}";
        
        return '<div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="' . $name . '" class="form-checkbox" ' . $checked . '>
                    <span class="ml-2">' . $label . '</span>
                </label>
                @error(\'' . $name . '\')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>';
    }
    
    /**
     * Generate a date field.
     *
     * @param string $name
     * @param string $label
     * @param string $modelVariable
     * @param bool $isEdit
     * @return string
     */
    protected function generateDateField($name, $label, $modelVariable, $isEdit)
    {
        $value = $isEdit 
            ? "{{ \${$modelVariable}->{$name} ? \${$modelVariable}->{$name}->format('Y-m-d\\TH:i') : '' }}"
            : "{{ old('{$name}') }}";
        
        return '<div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="' . $name . '">
                    ' . $label . '
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="' . $name . '" type="datetime-local" name="' . $name . '" value="' . $value . '">
                @error(\'' . $name . '\')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>';
    }
    
    /**
     * Generate a foreign key field.
     *
     * @param string $name
     * @param string $label
     * @param string $relatedModel
     * @param string $modelVariable
     * @param bool $isEdit
     * @return string
     */
    protected function generateForeignKeyField($name, $label, $relatedModel, $modelVariable, $isEdit)
    {
        $value = $isEdit 
            ? "\${$modelVariable}->{$name}"
            : "old('{$name}')";
        
        return '<div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="' . $name . '">
                    ' . $label . '
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="' . $name . '" name="' . $name . '">
                    <option value="">Select ' . $relatedModel . '</option>
                    @foreach($' . $name . 'Options ?? [] as $id => $optionName)
                        <option value="{{ $id }}" @if(' . $value . ' == $id) selected @endif>{{ $optionName }}</option>
                    @endforeach
                </select>
                @error(\'' . $name . '\')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>';
    }
    
    /**
     * Generate show fields for show view.
     *
     * @param array $fields
     * @param string $modelVariable
     * @return string
     */
    protected function generateShowFields($fields, $modelVariable)
    {
        if (empty($fields)) {
            return '';
        }
        
        $showFields = [];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip certain fields
            if (in_array($name, ['password'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            
            $showFields[] = <<<HTML
            <div class="mb-4">
                <h3 class="text-gray-700 text-sm font-bold mb-2">{$label}:</h3>
                <p class="text-gray-900">{{ \${$modelVariable}->{$name} }}</p>
            </div>
            HTML;
        }
        
        return implode("\n        ", $showFields);
    }
} 