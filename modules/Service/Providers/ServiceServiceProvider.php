<?php

namespace Modules\Service\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Service';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('modules/'.$this->moduleName.'/Database/migrations'));
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('modules/'.$this->moduleName.'/Routes/api.php'));

        Route::middleware(['api', 'auth:admin'])
            ->prefix('api/admin')
            ->group(base_path('modules/'.$this->moduleName.'/Routes/admin.php'));
    }
}
