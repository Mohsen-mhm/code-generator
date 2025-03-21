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
        
        // Manually register the commands
        $this->app->bind('command.code.generate', function ($app) {
            return new GenerateCommand();
        });
        
        $this->app->bind('command.code.regenerate-views', function ($app) {
            return new RegenerateViewsCommand();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                'command.code.generate',
                'command.code.regenerate-views',
            ]);
            
            $this->publishes([
                __DIR__ . '/Stubs' => resource_path('stubs/vendor/code-generator'),
            ], 'code-generator-stubs');
            
            $this->publishes([
                __DIR__ . '/../config/code-generator.php' => config_path('code-generator.php'),
            ], 'config');
        }
    }
} 