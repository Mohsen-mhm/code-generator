<?php

namespace MohsenMhm\CodeGenerator\Generators;

use Illuminate\Support\Str;

class TestGenerator extends BaseGenerator
{
    public function generate()
    {
        $testName = $this->name;
        
        if (!Str::endsWith($testName, 'Test')) {
            $testName .= 'Test';
        }
        
        $testNamespace = $this->getNamespace('test');
        $isFeatureTest = $this->options['feature'] ?? true;
        $testPath = $this->getPath('tests') . '/' . ($isFeatureTest ? 'Feature' : 'Unit') . '/' . $testName . '.php';
        
        $modelName = $this->options['model'] ?? Str::singular(str_replace('Test', '', $testName));
        $modelNamespace = $this->getNamespace('model') . '\\' . $modelName;
        $modelVariable = Str::camel($modelName);
        
        $stubName = $isFeatureTest ? 'feature-test' : 'unit-test';
        
        $replacements = [
            'namespace' => $testNamespace . '\\' . ($isFeatureTest ? 'Feature' : 'Unit'),
            'class' => $testName,
            'model' => $modelName,
            'modelNamespace' => $modelNamespace,
            'modelVariable' => $modelVariable,
            'tableName' => Str::snake(Str::pluralStudly($modelName)),
            'routeName' => Str::kebab(Str::pluralStudly($modelName)),
        ];
        
        $contents = $this->getStubContents($stubName, $replacements);
        
        if ($this->writeFile($testPath, $contents)) {
            $this->info("Test [{$testName}] created successfully.");
            return true;
        }
        
        return false;
    }
} 