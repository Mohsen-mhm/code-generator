<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\LivewireGenerator;

class GenerateLivewireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:livewire {name}
                            {--schema=}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Livewire components';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');

        // Check if Livewire is installed
        if (!class_exists('Livewire\Component')) {
            $this->error('Livewire is not installed. Please install Livewire first.');
            return;
        }

        $options = [
            'force' => $this->option('force'),
        ];

        $generator = new LivewireGenerator($name, $schema, $options);
        $generator->setCommand($this);

        if ($generator->generate()) {
            $this->info("Livewire components for [{$name}] generated successfully.");
        }
    }
}
