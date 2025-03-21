<?php

namespace MohsenMhm\CodeGenerator;

use Illuminate\Support\ServiceProvider;
use MohsenMhm\CodeGenerator\Commands\GenerateCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateControllerCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateModelCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateLivewireCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateMigrationCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateResourceCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateRoutesCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateTestCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateFactoryCommand;

class CodeGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                GenerateControllerCommand::class,
                GenerateModelCommand::class,
                GenerateLivewireCommand::class,
                GenerateMigrationCommand::class,
                GenerateResourceCommand::class,
                GenerateRoutesCommand::class,
                GenerateTestCommand::class,
                GenerateFactoryCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/code-generator.php' => config_path('code-generator.php'),
            ], 'code-generator-config');

            $this->publishes([
                __DIR__.'/Stubs' => resource_path('stubs/vendor/code-generator'),
            ], 'code-generator-stubs');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/code-generator.php', 'code-generator'
        );
    }
} 