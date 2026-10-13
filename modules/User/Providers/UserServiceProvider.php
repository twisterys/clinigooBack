<?php

namespace Modules\User\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\User\Auth\AdminUserProvider;

class UserServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'User';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('modules/'.$this->moduleName.'/Database/migrations'));
        $this->registerUserProviders();
        $this->registerRoutes();
    }

    /**
     * Register custom authentication providers.
     */
    protected function registerUserProviders(): void
    {
        Auth::provider('admin-eloquent', function ($app, array $config) {
            return new AdminUserProvider($app['hash'], $config['model']);
        });
    }

    protected function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('modules/'.$this->moduleName.'/Routes/api.php'));
    }
}
