<?php

namespace MohsenMhm\CodeGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateRoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:routes {name} 
                            {--api : Generate API routes}
                            {--force : Overwrite existing routes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate routes for a resource';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $isApi = $this->option('api');
        $force = $this->option('force');
        
        $routesPath = $isApi ? base_path('routes/api.php') : base_path('routes/web.php');
        $routesContent = File::get($routesPath);
        
        $controllerName = $name . 'Controller';
        $routeName = Str::kebab(Str::pluralStudly($name));
        
        $routeDefinition = $isApi
            ? "Route::apiResource('{$routeName}', {$controllerName}::class);"
            : "Route::resource('{$routeName}', {$controllerName}::class);";
        
        // Check if route already exists
        if (Str::contains($routesContent, $routeDefinition) && !$force) {
            $this->warn("Routes for [{$name}] already exist. Use --force to overwrite.");
            return;
        }
        
        // Add controller import if not already present
        $controllerImport = "use App\\Http\\Controllers\\{$controllerName};";
        if (!Str::contains($routesContent, $controllerImport)) {
            $routesContent = Str::replaceFirst("<?php", "<?php\n\n{$controllerImport}", $routesContent);
        }
        
        // Add route definition if not already present
        if (!Str::contains($routesContent, $routeDefinition)) {
            $routesContent .= "\n\n{$routeDefinition}";
        }
        
        File::put($routesPath, $routesContent);
        
        $this->info("Routes for [{$name}] generated successfully.");
    }
} 