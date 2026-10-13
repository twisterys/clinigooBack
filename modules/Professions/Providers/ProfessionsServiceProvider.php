<?php

namespace Modules\Professions\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Professions\Models\Profession;
use Modules\Professions\Models\ProfessionDocument;

class ProfessionsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Professions';

    public function register(): void
    {
        // Aliases for singular module namespace compatibility
        if (! class_exists('Modules\Profession\Models\Profession')) {
            class_alias(Profession::class, 'Modules\Profession\Models\Profession');
        }
        if (! class_exists('Modules\Profession\Models\ProfessionDocument')) {
            class_alias(ProfessionDocument::class, 'Modules\Profession\Models\ProfessionDocument');
        }
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
