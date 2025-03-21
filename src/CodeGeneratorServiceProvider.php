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
use MohsenMhm\CodeGenerator\Commands\GenerateRollbackCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateViewsCommand;
use MohsenMhm\CodeGenerator\Commands\RegenerateViewsCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateSeederCommand;
use MohsenMhm\CodeGenerator\Commands\GenerateRequestCommand;
use MohsenMhm\CodeGenerator\Commands\PublishStubsCommand;

class CodeGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/code-generator.php', 'code-generator'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
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
                GenerateRollbackCommand::class,
                GenerateViewsCommand::class,
                GenerateSeederCommand::class,
                GenerateRequestCommand::class,
                RegenerateViewsCommand::class,
                PublishStubsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/code-generator.php' => config_path('code-generator.php'),
            ], 'config');
            
            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/code-generator'),
            ], 'stubs');
        }
    }
} 