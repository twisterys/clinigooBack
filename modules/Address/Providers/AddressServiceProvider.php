<?php

namespace Modules\Address\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddressServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Address';

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
    }
}
