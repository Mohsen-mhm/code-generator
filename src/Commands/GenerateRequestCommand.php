<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\RequestGenerator;

class GenerateRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:request {name} 
                            {--schema=} 
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a request file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $options = [
            'force' => $this->option('force'),
        ];

        $generator = new RequestGenerator($name, $schema, $options);
        $generator->setCommand($this);
        $generator->generate();
    }
} 