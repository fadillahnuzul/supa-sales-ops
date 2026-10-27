<?php

namespace App\Providers;

use App\Models\Core\EmployeeModel;
use App\Models\Core\MaterialModel;
use App\Models\Core\ProductModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'material' => MaterialModel::class,
            'product' => ProductModel::class,
        ]);

        Gate::define(
            'manage-role-management',
            fn (EmployeeModel $employee): bool => $employee->canManageRoleManagement(),
        );
    }
}
