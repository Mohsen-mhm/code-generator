<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishStubsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code-generator:publish-stubs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish stubs for customization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stubsPath = __DIR__ . '/../../stubs';
        
        if (!File::isDirectory($stubsPath)) {
            $this->error('Stubs directory not found.');
            return;
        }
        
        $publishPath = base_path('stubs/code-generator');
        
        if (!File::isDirectory($publishPath)) {
            File::makeDirectory($publishPath, 0755, true);
        }
        
        $files = File::files($stubsPath);
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            File::copy($file->getPathname(), $publishPath . '/' . $filename);
        }
        
        $this->info('Stubs published successfully.');
    }
} 