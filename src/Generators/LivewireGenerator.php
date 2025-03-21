<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class LivewireGenerator extends BaseGenerator
{
    public function generate()
    {
        $modelName = $this->name;
        $componentName = Str::studly($modelName);
        $viewName = Str::kebab($modelName);

        // Generate Livewire component
        $this->generateComponent($modelName, $componentName, $viewName);

        // Generate Livewire view
        $this->generateView($modelName, $viewName);

        $this->info("Livewire component and view for [{$modelName}] created successfully.");

        return true;
    }

    /**
     * Generate Livewire component.
     *
     * @param string $modelName
     * @param string $componentName
     * @param string $viewName
     * @return bool
     */
    protected function generateComponent($modelName, $componentName, $viewName)
    {
        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);

        // Find foreign keys / relations
        $relations = $this->getRelations($fields);

        // Generate relation imports
        $relationImports = $this->generateRelationImports($relations);

        // Generate relation view data
        $relationViewData = $this->generateRelationViewData($relations);

        // Generate properties for the component
        $properties = $this->generateProperties($fields);

        // Generate rules for validation
        $rules = $this->generateRules($fields);

        // Generate reset properties
        $resetProperties = $this->generateResetProperties($fields);

        // Generate fields for store/update/edit methods
        $storeFields = $this->generateStoreFields($fields);
        $updateFields = $this->generateUpdateFields($fields);
        $editFields = $this->generateEditFields($fields);

        // Find suitable layout
        $layoutInfo = $this->findOrCreateLayout();

        // Generate model variable names
        $modelVariable = Str::camel($modelName);
        $modelVariablePlural = Str::camel(Str::pluralStudly($modelName));

        $replacements = [
            'namespace' => 'App\\Livewire',
            'modelNamespace' => $this->getNamespace('model') . '\\' . $modelName,
            'relationImports' => $relationImports,
            'class' => $componentName,
            'model' => $modelName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'viewName' => $viewName,
            'properties' => $properties,
            'rules' => $rules,
            'resetProperties' => $resetProperties,
            'storeFields' => $storeFields,
            'updateFields' => $updateFields,
            'editFields' => $editFields,
            'relationViewData' => $relationViewData,
            'layoutContent' => $layoutInfo['method'],
        ];

        $contents = $this->getStubContents('livewire', $replacements);

        $componentPath = app()->basePath() . '/app/Livewire/' . $componentName . 'Component.php';

        return $this->writeFile($componentPath, $contents);
    }

    /**
     * Find existing layout or create one if needed.
     *
     * @return array
     */
    protected function findOrCreateLayout()
    {
        $basePath = app()->basePath();
        $possibleLayouts = [
            'components.layouts.app' => $basePath . '/resources/views/components/layouts/app.blade.php',
            'layouts.app' => $basePath . '/resources/views/layouts/app.blade.php',
            'layouts.guest' => $basePath . '/resources/views/layouts/guest.blade.php',
        ];

        // Check for existing layouts
        foreach ($possibleLayouts as $name => $path) {
            if (file_exists($path)) {
                // If using components-style layout
                if (Str::startsWith($name, 'components.')) {
                    return [
                        'name' => $name,
                        'path' => $path,
                        'method' => "->layout('{$name}')",
                    ];
                }

                // If using regular @extends style layout
                return [
                    'name' => $name,
                    'path' => $path,
                    'method' => "->layout('{$name}')",
                ];
            }
        }

        // No layout found, create one
        $layoutDir = $basePath . '/resources/views/layouts';
        $layoutPath = $layoutDir . '/app.blade.php';

        if (!file_exists($layoutDir)) {
            mkdir($layoutDir, 0755, true);
        }

        $layoutContent = <<<'EOT'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $header ?? config('app.name') }}
                    </h2>
                </div>
            </header>

            <main>
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
EOT;

        file_put_contents($layoutPath, $layoutContent);
        $this->info("Layout created at: {$layoutPath}");

        return [
            'name' => 'layouts.app',
            'path' => $layoutPath,
            'method' => "->layout('layouts.app')",
        ];
    }

    /**
     * Get relations from fields.
     *
     * @param array $fields
     * @return array
     */
    protected function getRelations($fields)
    {
        $relations = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            if (Str::endsWith($name, '_id')) {
                $relationName = str_replace('_id', '', $name);
                $relationModel = Str::studly($relationName);
                $relationModelPlural = Str::plural($relationModel);
                $relationPlural = Str::plural($relationName);

                $relations[] = [
                    'name' => $relationName,
                    'model' => $relationModel,
                    'model_plural' => $relationModelPlural,
                    'plural' => $relationPlural,
                ];
            }
        }

        return $relations;
    }

    /**
     * Generate relation imports.
     *
     * @param array $relations
     * @return string
     */
    protected function generateRelationImports($relations)
    {
        if (empty($relations)) {
            return '';
        }

        $imports = [];
        $modelNamespace = $this->getNamespace('model');

        foreach ($relations as $relation) {
            $imports[] = "use {$modelNamespace}\\{$relation['model']};";
        }

        return implode("\n", array_unique($imports));
    }

    /**
     * Generate relation view data.
     *
     * @param array $relations
     * @return string
     */
    protected function generateRelationViewData($relations)
    {
        if (empty($relations)) {
            return '';
        }

        $data = [];

        foreach ($relations as $relation) {
            $data[] = "'{$relation['plural']}' => {$relation['model']}::all(),";
        }

        return implode("\n            ", $data);
    }

    /**
     * Generate store fields for the component.
     *
     * @param array $fields
     * @return string
     */
    protected function generateStoreFields($fields)
    {
        if (empty($fields)) {
            return '// No fields to store';
        }

        $fieldLines = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fieldLines[] = "'{$name}' => \$this->{$name},";
        }

        return implode("\n            ", $fieldLines);
    }

    /**
     * Generate update fields for the component.
     *
     * @param array $fields
     * @return string
     */
    protected function generateUpdateFields($fields)
    {
        // Use the same implementation as storeFields
        return $this->generateStoreFields($fields);
    }

    /**
     * Generate edit field mappings for the component.
     *
     * @param array $fields
     * @return string
     */
    protected function generateEditFields($fields)
    {
        if (empty($fields)) {
            return '// No fields to map';
        }

        $modelVariable = Str::camel($this->name);
        $fieldLines = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fieldLines[] = "\$this->{$name} = \${$modelVariable}->{$name};";
        }

        return implode("\n        ", $fieldLines);
    }

    /**
     * Generate Livewire view.
     *
     * @param string $modelName
     * @param string $viewName
     * @return bool
     */
    protected function generateView($modelName, $viewName)
    {
        // Parse schema to find fields
        $fields = $this->parseSchema($this->schema);

        // Generate form fields
        $formFields = $this->generateFormFields($fields);

        // Generate table columns
        $tableColumnsData = $this->generateTableColumns($fields);
        $tableHeaders = $tableColumnsData['headers'] ?? '';
        $tableData = $tableColumnsData['data'] ?? '';

        // Generate model variable names
        $modelVariable = Str::camel($modelName);
        $modelVariablePlural = Str::camel(Str::pluralStudly($modelName));

        $replacements = [
            'title' => $modelName . ' Management',
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'formFields' => $formFields,
            'tableColumns.headers' => $tableHeaders,
            'tableColumns.data' => $tableData,
        ];

        $contents = $this->getStubContents('livewire-view', $replacements);

        $viewPath = app()->basePath() . '/resources/views/livewire/' . $viewName . '.blade.php';

        return $this->writeFile($viewPath, $contents);
    }

    /**
     * Generate properties section for Livewire component.
     *
     * @param array $fields
     * @return string
     */
    protected function generateProperties($fields)
    {
        if (empty($fields)) {
            return '// No properties defined';
        }

        $properties = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $properties[] = "#[Rule('required')]\n    public \${$name} = '';";
        }

        return implode("\n    ", $properties);
    }

    /**
     * Generate rules for Livewire component.
     *
     * @param array $fields
     * @return string
     */
    protected function generateRules($fields)
    {
        if (empty($fields)) {
            return 'return [];';
        }

        $rules = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fieldRules = $this->getRulesForField($name, $type);

            $rules[] = "'{$name}' => '{$fieldRules}'";
        }

        return "return [\n            " . implode(",\n            ", $rules) . "\n        ];";
    }

    /**
     * Get rules for a specific field.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function getRulesForField($name, $type)
    {
        if (Str::endsWith($name, '_id')) {
            return 'required|exists:' . Str::plural(str_replace('_id', '', $name)) . ',id';
        }

        switch ($type) {
            case 'string':
                return 'required|string|max:255';
            case 'text':
            case 'longText':
            case 'mediumText':
                return 'required|string';
            case 'integer':
            case 'bigInteger':
            case 'smallInteger':
            case 'tinyInteger':
                return 'required|integer';
            case 'decimal':
            case 'float':
            case 'double':
                return 'required|numeric';
            case 'boolean':
                return 'boolean';
            case 'date':
            case 'dateTime':
            case 'timestamp':
                return 'nullable|date';
            case 'email':
                return 'required|email|max:255';
            default:
                if (Str::contains($type, 'nullable')) {
                    return 'nullable';
                }
                return 'required';
        }
    }

    /**
     * Generate reset properties section for Livewire component.
     *
     * @param array $fields
     * @return string
     */
    protected function generateResetProperties($fields)
    {
        if (empty($fields)) {
            return '// No properties to reset';
        }

        $properties = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $properties[] = "'{$name}'";
        }

        return "\$this->reset([" . implode(", ", $properties) . "]);";
    }

    /**
     * Generate form fields for the view.
     *
     * @param array $fields
     * @return string
     */
    protected function generateFormFields($fields)
    {
        if (empty($fields)) {
            return '<!-- No form fields defined -->';
        }

        $formFields = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip primary keys, timestamps, etc.
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $formField = $this->getFormField($field);

            $formFields[] = $formField;
        }

        return implode("\n                        ", $formFields);
    }

    /**
     * Get form field HTML for a specific field.
     *
     * @param array $field
     * @return string
     */
    protected function getFormField($field)
    {
        $name = $field['name'];
        $type = $field['type'];
        $label = Str::title(str_replace('_', ' ', $name));

        if (Str::contains($type, 'text') || $type === 'longText' || $type === 'mediumText') {
            return '<div class="mb-4">
                    <label for="' . $name . '" class="block text-sm font-medium text-gray-700">' . $label . '</label>
                    <textarea wire:model="' . $name . '" id="' . $name . '" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    @error(\'' . $name . '\') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>';
        }

        if ($type === 'boolean') {
            return '<div class="mb-4">
                    <label for="' . $name . '" class="flex items-center">
                        <input wire:model="' . $name . '" id="' . $name . '" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-600">' . $label . '</span>
                    </label>
                    @error(\'' . $name . '\') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>';
        }

        if (Str::contains($type, 'date') || $type === 'dateTime' || $type === 'timestamp') {
            return '<div class="mb-4">
                    <label for="' . $name . '" class="block text-sm font-medium text-gray-700">' . $label . '</label>
                    <input wire:model="' . $name . '" id="' . $name . '" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error(\'' . $name . '\') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>';
        }

        if ($type === 'email') {
            return '<div class="mb-4">
                    <label for="' . $name . '" class="block text-sm font-medium text-gray-700">' . $label . '</label>
                    <input wire:model="' . $name . '" id="' . $name . '" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error(\'' . $name . '\') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>';
        }

        if (Str::endsWith($name, '_id')) {
            $relationName = str_replace('_id', '', $name);
            $relationModel = Str::studly($relationName);
            $relationPlural = Str::plural($relationName);

            return '<div class="mb-4">
                    <label for="' . $name . '" class="block text-sm font-medium text-gray-700">' . $label . '</label>
                    <select wire:model="' . $name . '" id="' . $name . '" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select ' . Str::title($relationName) . '</option>
                        @foreach($' . $relationPlural . ' as $' . $relationName . ')
                            <option value="{{ $' . $relationName . '->id }}">{{ $' . $relationName . '->name }}</option>
                        @endforeach
                    </select>
                    @error(\'' . $name . '\') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>';
        }

        // Default to text input
        return '<div class="mb-4">
                    <label for="' . $name . '" class="block text-sm font-medium text-gray-700">' . $label . '</label>
                    <input wire:model="' . $name . '" id="' . $name . '" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error(\'' . $name . '\') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>';
    }

    /**
     * Generate table columns for the view.
     *
     * @param array $fields
     * @return string
     */
    protected function generateTableColumns($fields)
    {
        if (empty($fields)) {
            return '<!-- No table columns defined -->';
        }

        $tableColumns = [];
        $dataColumns = [];

        foreach ($fields as $field) {
            $name = $field['name'];

            // Skip timestamps, large text fields, and soft deletes
            if (in_array($name, ['created_at', 'updated_at', 'deleted_at']) ||
                in_array($field['type'], ['text', 'longText', 'mediumText'])) {
                continue;
            }

            $label = Str::title(str_replace('_', ' ', $name));

            $tableColumns[] = '<th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">' . $label . '</th>';

            if ($field['type'] === 'boolean') {
                $dataColumns[] = '<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        @if($item->' . $name . ')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                        @endif
                                    </td>';
            } else {
                $dataColumns[] = '<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">{{ $item->' . $name . ' }}</td>';
            }
        }

        $columnHeaders = implode("\n                                    ", $tableColumns);
        $columnData = implode("\n                                        ", $dataColumns);

        return [
            'headers' => $columnHeaders,
            'data' => $columnData
        ];
    }
}
