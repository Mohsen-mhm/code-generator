<?php

namespace MohsenMhm\CodeGenerator\Traits;

use Illuminate\Support\Str;

trait GeneratesFiles
{
    protected function makeDirectory($path)
    {
        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0755, true);
        }
    }

    protected function getStubContents($stub, $replacements = [])
    {
        $contents = file_get_contents($this->getStubPath($stub));

        foreach ($replacements as $search => $replace) {
            $contents = str_replace('{{ ' . $search . ' }}', $replace, $contents);
        }

        return $contents;
    }

    protected function writeFile($path, $contents)
    {
        $this->makeDirectory($path);

        if ($this->filesystem->exists($path) && !$this->options['force']) {
            if ($this->command) {
                if (!$this->command->confirm("The file {$path} already exists. Do you want to overwrite it?")) {
                    if ($this->command) {
                        $this->command->info("File generation skipped.");
                    }
                    return false;
                }
            } else {
                return false;
            }
        }

        $this->filesystem->put($path, $contents);
        
        if ($this->command) {
            $this->command->info("Created: {$path}");
        }
        
        return true;
    }
} 