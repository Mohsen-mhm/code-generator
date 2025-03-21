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
            'fields' => $this->generateDetailFields($fields, $modelVariable),
        ];
        $showContents = $this->getStubContents('view-show', $showReplacements);
        
        // Write files
        $this->writeFile($indexPath, $indexContents);
        $this->writeFile($createPath, $createContents);
        $this->writeFile($editPath, $editContents);
        $this->writeFile($showPath, $showContents);
        
        $this->info("Views for [{$modelName}] created successfully.");
        
        return true;
    }
    
    /**
     * Generate table headers for index view.
     *
     * @param array $fields
     * @return string
     */
    protected function generateTableHeaders($fields)
    {
        if (empty($fields)) {
            return '<th class="px-4 py-2">ID</th>';
        }
        
        $headers = ['<th class="px-4 py-2">ID</th>'];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            
            // Skip certain fields
            if (in_array($name, ['created_at', 'updated_at', 'deleted_at', 'password'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            $headers[] = '<th class="px-4 py-2">' . $label . '</th>';
        }
        
        $headers[] = '<th class="px-4 py-2">Actions</th>';
        
        return implode("\n            ", $headers);
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
        if (empty($fields)) {
            return '<td class="border px-4 py-2">{{ $' . $modelVariable . '->id }}</td>';
        }
        
        $rows = ['<td class="border px-4 py-2">{{ $' . $modelVariable . '->id }}</td>'];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip certain fields
            if (in_array($name, ['created_at', 'updated_at', 'deleted_at', 'password'])) {
                continue;
            }
            
            if ($type === 'boolean') {
                $rows[] = '<td class="border px-4 py-2">{{ $' . $modelVariable . '->' . $name . ' ? \'Yes\' : \'No\' }}</td>';
            } elseif (in_array($type, ['date', 'dateTime', 'timestamp'])) {
                $rows[] = '<td class="border px-4 py-2">{{ $' . $modelVariable . '->' . $name . ' ? $' . $modelVariable . '->' . $name . '->format(\'Y-m-d\') : \'\' }}</td>';
            } else {
                $rows[] = '<td class="border px-4 py-2">{{ $' . $modelVariable . '->' . $name . ' }}</td>';
            }
        }
        
        return implode("\n                ", $rows);
    }
    
    /**
     * Generate detail fields for show view.
     *
     * @param array $fields
     * @param string $modelVariable
     * @return string
     */
    protected function generateDetailFields($fields, $modelVariable)
    {
        if (empty($fields)) {
            return '<div class="mb-4">
                <h2 class="text-lg font-semibold">ID</h2>
                <p>{{ $' . $modelVariable . '->id }}</p>
            </div>';
        }
        
        $detailFields = ['<div class="mb-4">
                <h2 class="text-lg font-semibold">ID</h2>
                <p>{{ $' . $modelVariable . '->id }}</p>
            </div>'];
        
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            
            // Skip certain fields
            if (in_array($name, ['password'])) {
                continue;
            }
            
            $label = Str::title(str_replace('_', ' ', $name));
            
            if ($type === 'boolean') {
                $detailFields[] = '<div class="mb-4">
                <h2 class="text-lg font-semibold">' . $label . '</h2>
                <p>{{ $' . $modelVariable . '->' . $name . ' ? \'Yes\' : \'No\' }}</p>
            </div>';
            } elseif (in_array($type, ['date', 'dateTime', 'timestamp'])) {
                $detailFields[] = '<div class="mb-4">
                <h2 class="text-lg font-semibold">' . $label . '</h2>
                <p>{{ $' . $modelVariable . '->' . $name . ' ? $' . $modelVariable . '->' . $name . '->format(\'Y-m-d\') : \'\' }}</p>
            </div>';
            } else {
                $detailFields[] = '<div class="mb-4">
                <h2 class="text-lg font-semibold">' . $label . '</h2>
                <p>{{ $' . $modelVariable . '->' . $name . ' }}</p>
            </div>';
            }
        }
        
        $detailFields[] = '<div class="mb-4">
                <h2 class="text-lg font-semibold">Created At</h2>
                <p>{{ $' . $modelVariable . '->created_at->format(\'Y-m-d\') }}</p>
            </div>';
        
        $detailFields[] = '<div class="mb-4">
                <h2 class="text-lg font-semibold">Updated At</h2>
                <p>{{ $' . $modelVariable . '->updated_at->format(\'Y-m-d\') }}</p>
            </div>';
        
        return implode("\n            ", $detailFields);
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
            
            if ($type === 'boolean') {
                $formFields[] = $this->generateCheckboxField($name, $label, $modelVariable, $isEdit);
            } elseif (in_array($type, ['text', 'longText', 'mediumText'])) {
                $formFields[] = $this->generateTextareaField($name, $label, $modelVariable, $isEdit);
            } elseif (in_array($type, ['date', 'dateTime', 'timestamp'])) {
                $formFields[] = $this->generateDateField($name, $label, $modelVariable, $isEdit);
            } elseif ($type === 'foreignId' || Str::endsWith($name, '_id')) {
                $relatedModel = Str::studly(str_replace('_id', '', $name));
                $formFields[] = $this->generateForeignKeyField($name, $label, $relatedModel, $modelVariable, $isEdit);
            } else {
                $inputType = 'text';
                
                if ($name === 'email') {
                    $inputType = 'email';
                } elseif ($name === 'password') {
                    $inputType = 'password';
                } elseif (in_array($type, ['integer', 'bigInteger', 'smallInteger', 'tinyInteger', 'decimal', 'float', 'double'])) {
                    $inputType = 'number';
                }
                
                $formFields[] = $this->generateInputField($name, $label, $inputType, $modelVariable, $isEdit);
            }
        }
        
        return implode("\n            ", $formFields);
    }
    
    /**
     * Generate an input field.
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
} 