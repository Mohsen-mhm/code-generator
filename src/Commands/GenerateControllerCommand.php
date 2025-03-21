<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use MohsenMhm\CodeGenerator\Generators\ControllerGenerator;

class GenerateControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:controller {name} 
                            {--schema=} 
                            {--api : Generate an API controller}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a controller file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $options = [
            'force' => $this->option('force'),
            'api' => $this->option('api'),
            'model' => $name,
        ];

        $generator = new ControllerGenerator($name, $schema, $options);
        $generator->setCommand($this);
        $generator->generate();
    }
} 